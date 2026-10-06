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

    function loadCurrentState() {
        return fetch(httpURL(transport.statePath), {
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

    function browserLocation() {
        const href = window.location.href || '';
        if (href.startsWith('data:') || href === 'about:blank' || href === 'about:srcdoc') {
            const source = href.startsWith('data:')
                ? decodeURIComponent(href)
                : ((document.head && document.head.innerHTML) || '')
                    + ((document.body && document.body.innerHTML) || '');
            const match = source.match(/<base\s+href=["'](https?:)\/\/([^\/"']+)/i);
            if (match) {
                return {protocol: match[1], host: match[2]};
            }
            try {
                return {protocol: window.top.location.protocol, host: window.top.location.host};
            } catch (error) {
                return {protocol: window.location.protocol, host: window.location.host};
            }
        }

        return {protocol: window.location.protocol, host: window.location.host};
    }

    function httpURL(path) {
        const location = browserLocation();
        return location.protocol + '//' + location.host + path;
    }

    function socketURL(path) {
        const location = browserLocation();
        const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
        return protocol + '//' + location.host + path;
    }

    function connect() {
        if (stopped) {
            return;
        }

        try {
            socket = new WebSocket(socketURL(transport.socketPath));
        } catch (error) {
            scheduleReconnect();
            return;
        }

        socket.onopen = function () {
            retryDelay = 1000;
            synchronizing = true;
            queuedMessages = [];
            loadCurrentState().finally(function () {
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
