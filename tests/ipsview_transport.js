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

    console.log('Persistent IPSView transport verified.');
}()).catch(error => {
    console.error(error);
    process.exit(1);
});
