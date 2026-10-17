(function () {
    'use strict';

    const bootstrap = window.SYMC_VISUALIZATION || {};
    const transport = bootstrap.options && bootstrap.options.ipsViewTransport;
    if (bootstrap.mode !== 'ipsview' || !transport || typeof window.handleMessage !== 'function') {
        return;
    }

    let socket = null;
    let retryDelay = 1000;
    let stopped = false;
    let synchronizing = false;
    let queuedMessages = [];

    function deliver(payload) {
        if (payload && typeof payload === 'object') {
            window.handleMessage(payload);
        }
    }

    function loadCurrentState(origin) {
        return fetch(httpURL(transport.statePath, origin), {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {Accept: 'application/json'}
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('IPSView state request failed.');
                }
                return response.json();
            })
            .then(deliver)
            .catch(function () {
                // The embedded bootstrap remains a usable fallback until the next socket update.
            });
    }

    function httpOrigin(candidate) {
        if (typeof candidate !== 'string') { return null; }
        try {
            const url = new URL(candidate);
            if ((url.protocol === 'http:' || url.protocol === 'https:') && url.host && !url.username && !url.password) {
                return {protocol: url.protocol, host: url.host};
            }
        } catch (error) {
            // Missing, opaque and malformed addresses are not transport endpoints.
        }
        return null;
    }

    function readOrigin(reader) {
        try { return httpOrigin(reader()); } catch (error) { return null; }
    }

    function locationAddress(target) {
        const location = target.location;
        return location.href || location.protocol + '//' + location.host;
    }

    function browserLocation() {
        const ownOrigin = readOrigin(function () { return locationAddress(window); });
        if (ownOrigin) { return ownOrigin; }
        // data: frames have opaque origins. The parsed DOM base also supports base64 data URLs.
        const baseOrigin = readOrigin(function () { return document.baseURI; });
        if (baseOrigin) { return baseOrigin; }
        const parentOrigin = readOrigin(function () { return locationAddress(window.parent); });
        if (parentOrigin) { return parentOrigin; }
        const referrerOrigin = readOrigin(function () { return document.referrer; });
        if (referrerOrigin) { return referrerOrigin; }
        // Chromium/WebKit expose ancestor origins even when cross-origin parent access is forbidden.
        try {
            const ancestors = window.location.ancestorOrigins || [];
            for (let index = 0; index < ancestors.length; index += 1) {
                const origin = httpOrigin(ancestors[index]);
                if (origin) { return origin; }
            }
        } catch (error) {
            // Referrer-less hosts may still provide the legacy same-origin top window below.
        }
        return readOrigin(function () { return locationAddress(window.top); });
    }

    function httpURL(path, origin) {
        return origin.protocol + '//' + origin.host + path;
    }

    function socketURL(path, origin) {
        const protocol = origin.protocol === 'https:' ? 'wss:' : 'ws:';
        return protocol + '//' + origin.host + path;
    }

    function connect() {
        if (stopped) {
            return;
        }

        const origin = browserLocation();
        if (!origin) {
            scheduleReconnect();
            return;
        }
        try {
            socket = new WebSocket(socketURL(transport.socketPath, origin));
        } catch (error) {
            scheduleReconnect();
            return;
        }

        socket.onopen = function () {
            retryDelay = 1000;
            synchronizing = true;
            queuedMessages = [];
            loadCurrentState(origin).finally(function () {
                synchronizing = false;
                queuedMessages.splice(0).forEach(deliver);
            });
        };
        socket.onmessage = function (event) {
            try {
                const message = JSON.parse(event.data);
                if (synchronizing) {
                    queuedMessages.push(message);
                } else {
                    deliver(message);
                }
            } catch (error) {
                // Ignore malformed transport messages and preserve the last valid chart state.
            }
        };
        socket.onclose = scheduleReconnect;
        socket.onerror = function () {
            socket.close();
        };
    }

    function scheduleReconnect() {
        if (stopped) {
            return;
        }
        window.setTimeout(connect, retryDelay);
        retryDelay = Math.min(retryDelay * 2, 10000);
    }

    window.addEventListener('pagehide', function () {
        stopped = true;
        if (socket) {
            socket.close();
        }
    }, {once: true});

    connect();
}());
