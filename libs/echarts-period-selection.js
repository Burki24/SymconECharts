(function () {
    'use strict';

    const control = document.getElementById('echarts-period-picker');
    if (!control || typeof window.handleMessage !== 'function') { return; }
    const select = control.querySelector('select');
    const status = control.querySelector('[role=status]');
    const german = document.documentElement.lang.startsWith('de');
    const deliver = window.handleMessage;
    const refreshDelay = 60000;
    let active = '';
    let pending = null;
    let timer = null;
    let stopped = false;

    function withPeriod(address) {
        const url = new URL(address, window.SymconHTTPOrigin.resolve(window));
        if (active) { url.searchParams.set('period', active); }
        else { url.searchParams.delete('period'); }
        return url.toString();
    }
    window.eChartsPeriodSelection = {withPeriod: withPeriod};

    function schedule(delay) {
        if (!active || stopped || document.hidden || pending || timer !== null) { return; }
        timer = window.setTimeout(function () {
            timer = null;
            load(active);
        }, delay);
    }
    function clearTimer() {
        if (timer !== null) { window.clearTimeout(timer); timer = null; }
    }
    async function load(key) {
        clearTimer();
        if (pending) { pending.abort(); }
        const request = new AbortController();
        pending = request;
        const switching = key !== active;
        if (switching) { status.textContent = german ? 'Wird geladen …' : 'Loading…'; }
        try {
            const url = new URL(control.dataset.path, window.SymconHTTPOrigin.resolve(window));
            url.searchParams.set('output', control.dataset.output);
            if (key) { url.searchParams.set('period', key); }
            const response = await fetch(url.toString(), {signal: request.signal, cache: 'no-store',
                credentials: 'same-origin', headers: {Accept: 'application/json'}});
            if (!response.ok) { throw new Error('Period request failed'); }
            const state = await response.json();
            if (pending !== request || stopped) { return; }
            if (!state || typeof state.status !== 'string' || (state.viewPeriod || '') !== key) {
                throw new Error('Unexpected period response');
            }
            if (key && state.status !== 'ready') {
                select.value = active;
                const message = typeof state.error === 'string' ? state.error : '';
                const translations = (window.SYMC_VISUALIZATION || {}).translations || {};
                status.textContent = translations[message] || message
                    || (german ? 'Zeitraum ist nicht verfügbar.' : 'Period unavailable.');
                return;
            }
            active = key;
            select.value = key;
            status.textContent = '';
            deliver(state);
        } catch (error) {
            if (pending !== request || stopped) { return; }
            select.value = active;
            status.textContent = german ? 'Zeitraum konnte nicht geladen werden.' : 'Could not load the period.';
        } finally {
            if (pending === request) {
                pending = null;
                schedule(refreshDelay);
            }
        }
    }
    select.addEventListener('change', function () { load(select.value); });
    window.handleMessage = function (message) {
        if (!active) { deliver(message); return; }
        // Canonical pushes (including raw append events) always belong to the configured window.
        // Coalesce them so a busy source cannot force one archive query per value.
        schedule(refreshDelay);
    };
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { clearTimer(); }
        else if (active && !pending) { schedule(0); }
    });
    window.addEventListener('pagehide', function () {
        stopped = true;
        clearTimer();
        if (pending) { pending.abort(); }
    });
    window.addEventListener('pageshow', function () {
        if (stopped) { stopped = false; schedule(0); }
    });
}());
