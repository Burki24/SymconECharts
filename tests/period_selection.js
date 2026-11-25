'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const origin = fs.readFileSync('libs/helper/visualization/http-origin.js', 'utf8');
const controller = fs.readFileSync('libs/echarts-period-selection.js', 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function openView(output) {
    const received = [], requests = [], timers = new Map(), listeners = {};
    let nextTimer = 1;
    const select = {value: '', addEventListener(type, fn) { listeners[type] = fn; }};
    const status = {textContent: ''};
    const control = {dataset: {output, path: '/hook/eCharts/state/1234/' + 'a'.repeat(32)},
        querySelector(selector) { return selector === 'select' ? select : status; }};
    const document = {hidden: false, baseURI: 'http://symcon.test:3777/', documentElement: {lang: 'de'},
        getElementById() { return control; }, addEventListener(type, fn) { listeners[type] = fn; }};
    const window = {document, location: {href: 'data:text/html,fixture'}, handleMessage: state => received.push(state),
        addEventListener(type, fn) { listeners[type] = fn; },
        setTimeout(fn, delay) { const id = nextTimer++; timers.set(id, {fn, delay}); return id; },
        clearTimeout(id) { timers.delete(id); }};
    function fetch(url, options) {
        assert.equal(options.credentials, 'same-origin');
        return new Promise(resolve => requests.push({url: new URL(url), options, resolve}));
    }
    vm.runInNewContext(origin + '\n' + controller, {window, document, URL, AbortController, fetch});
    return {window, document, select, status, received, requests, timers, listeners,
        choose(key) { select.value = key; listeners.change(); },
        reply(index, period, error = '') {
            requests[index].resolve({ok: true, async json() { return {status: error ? 'error' : 'ready', error, viewPeriod: period}; }});
            return flush();
        }};
}

(async () => {
    const tile = openView('tile'), ips = openView('ipsview');
    tile.choose('7d');
    tile.choose('today');
    ips.choose('6h');
    assert.ok(tile.requests[0].options.signal.aborted);
    assert.equal(tile.requests[1].url.origin, 'http://symcon.test:3777');
    assert.equal(tile.requests[1].url.searchParams.get('output'), 'tile');
    assert.equal(ips.requests[0].url.searchParams.get('output'), 'ipsview');
    assert.equal(ips.requests[0].url.searchParams.get('period'), '6h');
    await tile.reply(1, 'today');
    await tile.reply(0, '7d');
    await ips.reply(0, '6h');
    assert.deepEqual(tile.received.map(s => s.viewPeriod), ['today']);
    assert.deepEqual(ips.received.map(s => s.viewPeriod), ['6h']);
    const url = new URL(tile.window.eChartsPeriodSelection.withPeriod('/state?offset=2'));
    assert.equal(url.origin, 'http://symcon.test:3777');
    assert.equal(url.searchParams.get('offset'), '2');
    assert.equal(url.searchParams.get('period'), 'today');

    for (let i = 0; i < 100; i++) { tile.window.handleMessage({messageType: 'append'}); }
    assert.equal(tile.received.length, 1);
    assert.equal(tile.requests.length, 2);
    assert.equal(tile.timers.size, 1);
    tile.document.hidden = true;
    tile.listeners.visibilitychange();
    assert.equal(tile.timers.size, 0);
    tile.document.hidden = false;
    tile.listeners.visibilitychange();
    const [id, timer] = [...tile.timers][0];
    tile.timers.delete(id);
    timer.fn();
    assert.equal(tile.requests[2].url.searchParams.get('period'), 'today');
    await tile.reply(2, 'today');
    tile.choose('30d');
    await tile.reply(3, 'today'); // A stale/older gateway must never silently display a different window.
    assert.equal(tile.select.value, 'today');
    assert.match(tile.status.textContent, /konnte nicht geladen/);
    tile.choose('30d');
    await tile.reply(4, '30d', 'Das Archiv ist nicht erreichbar.');
    assert.equal(tile.select.value, 'today');
    assert.equal(tile.status.textContent, 'Das Archiv ist nicht erreichbar.');
    assert.equal(tile.received.at(-1).viewPeriod, 'today');
    tile.choose('');
    assert.equal(tile.requests[5].url.searchParams.has('period'), false);
    await tile.reply(5, '');
    tile.window.handleMessage({messageType: 'append'});
    assert.equal(tile.received.at(-1).messageType, 'append');
    assert.equal(tile.timers.size, 0);
    ips.choose('24h');
    ips.listeners.pagehide();
    assert.ok(ips.requests[1].options.signal.aborted);
    await ips.reply(1, '24h');
    assert.equal(ips.received.length, 1);
    console.log('Period picker: independent outputs, opaque origins, refresh, navigation, races, failure and reset verified.');
})().catch(error => { console.error(error); process.exitCode = 1; });
