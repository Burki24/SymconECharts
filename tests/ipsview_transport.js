'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts', 'ipsview-transport.js'),
    'utf8'
);

(async function () {
    const messages = [];
    const listeners = {};
    const sockets = [];
    const fetched = [];
    const state = {status: 'ready', chart: {value: 42.5}};

    class TestWebSocket {
        constructor(url) {
            this.url = url;
            sockets.push(this);
        }

        close() {}
    }

    const window = {
        SYMC_VISUALIZATION: {
            mode: 'ipsview',
            options: {
                ipsViewTransport: {
                    statePath: '/hook/SymconECharts/state/5000/0123456789abcdef0123456789abcdef',
                    socketPath: '/hook/SymconECharts/WS/5000/0123456789abcdef0123456789abcdef'
                }
            }
        },
        location: {href: 'about:blank', protocol: 'about:', host: ''},
        top: {location: {protocol: 'https:', host: 'symcon.example.test'}},
        handleMessage: message => messages.push(message),
        addEventListener: (name, listener) => { listeners[name] = listener; },
        setTimeout: listener => listener()
    };
    const context = {
        window,
        document: {head: {innerHTML: ''}, body: {innerHTML: ''}},
        WebSocket: TestWebSocket,
        fetch: async (url, options) => {
            fetched.push({url, options});
            return {ok: true, json: async () => state};
        },
        JSON,
        URL,
        Error
    };

    vm.runInNewContext(source, context);
    assert.equal(sockets.length, 1, 'IPSView must open one persistent WebSocket.');
    assert.equal(
        sockets[0].url,
        'wss://symcon.example.test/hook/SymconECharts/WS/5000/0123456789abcdef0123456789abcdef'
    );

    sockets[0].onopen();
    const append = {messageType: 'append', variableID: 4711, timestamp: 1780000000, value: 43.0};
    sockets[0].onmessage({data: JSON.stringify(append)});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(fetched.length, 1, 'Opening the socket must request one fresh full state.');
    assert.equal(
        fetched[0].url,
        'https://symcon.example.test/hook/SymconECharts/state/5000/0123456789abcdef0123456789abcdef'
    );
    assert.deepEqual(messages[0], state);

    assert.deepEqual(
        messages[1],
        append,
        'Socket updates received during state synchronization must be applied afterwards.'
    );

    async function embeddedCase({href = 'data:text/html,%3Chtml%3E', baseURI = href, referrer = '', ancestors = [], origin = null, topThrows = true, parent}) {
        const delivered = [], opened = [], requests = [], retries = [], events = {};
        const embeddedWindow = {
            SYMC_VISUALIZATION: window.SYMC_VISUALIZATION,
            location: {href, protocol: href.split(':')[0] + ':', host: '', ancestorOrigins: ancestors},
            parent,
            handleMessage: message => delivered.push(message),
            addEventListener: (name, handler) => {events[name] = handler;},
            setTimeout: (handler, delay) => {retries.push({handler, delay});}
        };
        Object.defineProperty(embeddedWindow, 'top', {get() {
            if (topThrows) {throw new Error('Cross-origin frame access denied.');}
            return window.top;
        }});
        const embeddedDocument = {baseURI, referrer, head: {innerHTML: ''}, body: {innerHTML: ''}};
        const before = JSON.stringify({location: embeddedWindow.location, document: embeddedDocument, bootstrap: embeddedWindow.SYMC_VISUALIZATION});
        vm.runInNewContext(source, {
            window: embeddedWindow, document: embeddedDocument, URL, JSON, Error,
            WebSocket: class {constructor(url) {this.url = url; opened.push(this);} close() {}},
            fetch: async (url, options) => {requests.push({url, options}); return {ok: true, json: async () => state};}
        });
        if (origin === null) {
            assert.equal(opened.length, 0, 'No usable HTTP origin must not produce ws:///hook or send a channel to an invented host.');
            assert.equal(requests.length, 0);
            assert.equal(retries.length, 1, 'Unresolved origins retain bounded reconnect behavior.');
        } else {
            assert.equal(opened.length, 1);
            assert.equal(opened[0].url, origin.replace(/^http/, 'ws') + window.SYMC_VISUALIZATION.options.ipsViewTransport.socketPath,
                'Opaque data frames must resolve the actual embedding server without reading cross-origin top.location.');
            opened[0].onopen();
            opened[0].onmessage({data: JSON.stringify(append)});
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(requests[0].url, origin + window.SYMC_VISUALIZATION.options.ipsViewTransport.statePath);
            assert.equal(requests[0].options.credentials, 'same-origin', 'Do not broaden credential transmission for opaque embeds.');
            assert.deepEqual(delivered, [state, append], 'Fresh state must precede queued live updates in an embedded frame.');
            opened[0].onclose();
            assert.equal(retries[0].delay, 1000);
        }
        assert.equal(JSON.stringify({location: embeddedWindow.location, document: embeddedDocument, bootstrap: embeddedWindow.SYMC_VISUALIZATION}), before,
            'Address resolution must not rewrite bootstrap configuration or insert a base element.');
        events.pagehide();
        retries[0].handler();
        assert.equal(opened.length, origin === null ? 0 : 1, 'Closing an embedded widget must stop reconnecting.');
    }
    await embeddedCase({referrer: 'https://symcon.example.test:8443/tile/?private=ignored#presentation', origin: 'https://symcon.example.test:8443'});
    await embeddedCase({href: 'data:text/html;base64,PGh0bWw+', ancestors: ['null', 'http://symcon.example.test:3777'], origin: 'http://symcon.example.test:3777'});
    await embeddedCase({href: 'about:srcdoc', referrer: 'https://symcon.example.test/dashboard', origin: 'https://symcon.example.test'});
    await embeddedCase({href: 'about:blank', topThrows: false, origin: 'https://symcon.example.test'});
    await embeddedCase({href: 'about:srcdoc', parent: {location: {href: 'https://parent.example.test:8443/tile/'}}, origin: 'https://parent.example.test:8443'});
    await embeddedCase({href: 'https://own.example.test:8443/widgets/', referrer: 'https://other.example.test', origin: 'https://own.example.test:8443'});
    await embeddedCase({href: 'data:text/html,%broken', baseURI: 'https://explicit.example.test:8443/widgets/', referrer: 'https://other.example.test', origin: 'https://explicit.example.test:8443'});
    await embeddedCase({href: 'blob:https://symcon.example.test/example', referrer: 'https://symcon.example.test/', origin: 'https://symcon.example.test'});
    await embeddedCase({referrer: 'https://[2001:db8::1]:8443/tile/', origin: 'https://[2001:db8::1]:8443'});
    await embeddedCase({referrer: 'javascript:alert(1)', ancestors: ['file://', 'null', 'https://symcon.example.test'], origin: 'https://symcon.example.test'});
    await embeddedCase({referrer: 'https://user:secret@rejected.example.test/', ancestors: ['https://symcon.example.test'], origin: 'https://symcon.example.test'});
    await embeddedCase({referrer: '//host.example.test'});
    await embeddedCase({referrer: 'not a URL', ancestors: ['null', 'file://']});
    console.log('Persistent IPSView transport and opaque embedded origins verified.');
}()).catch(error => {
    console.error(error);
    process.exit(1);
});
