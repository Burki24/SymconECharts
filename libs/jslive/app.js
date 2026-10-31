(function () {
    'use strict';
    const bootstrap = window.SYMC_VISUALIZATION || {};
    const element = document.getElementById('echarts-import-chart');
    const root = document.getElementById('echarts-import-root');
    const error = document.getElementById('echarts-import-error');
    const warning = document.getElementById('echarts-import-warning');
    const options = bootstrap.options || {};
    const design = window.SYMC_ECHARTS_DESIGN;
    root.style.setProperty('--symc-tile-header-inset', String(options.tileHeaderInset || 0) + 'px');
    let chart = null;
    let current = null;
    let sequence = 0;
    const fonts = new Set();
    let graphicIDs=[];
    const translate = function(text){return bootstrap.translations && bootstrap.translations[text] || text;};
    function loadFonts(resources) {
        if (typeof window.FontFace !== 'function') { return; }
        (resources || []).forEach(function (resource) {
            if (resource.kind !== 'font' || fonts.has(resource.sha256)) { return; }
            fonts.add(resource.sha256);
            const face = new FontFace(resource.family, 'url(data:font/woff2;base64,' + resource.data + ')');
            face.load().then(function (loaded) { document.fonts.add(loaded); redraw(); }).catch(function () {
                warning.textContent = translate('An imported font could not be loaded.'); warning.hidden = false;
            });
        });
    }
    function redraw() {
        if (!current || current.status !== 'ready') { return; }
        if (!chart) { chart = window.echarts.init(element, null, {renderer: 'canvas'}); }
        chart.resize();
        const option = window.SYMC_JSLIVE_OPTION(current.chart, element.clientWidth, element.clientHeight);
        if(option.legend){
            const previous=(chart.getOption()||{}).legend;
            if(previous&&previous[0]){option.legend.selected=previous[0].selected;}
        }
        if(Array.isArray(option.graphic)){
            const ids=option.graphic.map(function(item){return item.id;});
            graphicIDs.filter(function(id){return !ids.includes(id);}).forEach(function(id){option.graphic.push({id:id,$action:'remove'});});
            graphicIDs=ids;
        }
        chart.setOption(option, {notMerge:false,replaceMerge:option.series?['series']:[]});
    }
    function render(state) {
        if (!state || state.status !== 'ready') {
            if (current && current.status === 'ready') {
                warning.textContent = state && state.error || translate('The data could not be refreshed.'); warning.hidden = false; return;
            }
            error.textContent = state && state.error || translate('The imported data could not be loaded.');
            error.hidden = false; return;
        }
        current = state;
        error.hidden = true; warning.hidden = true;
        element.style.backgroundColor = state.chart.background;
        loadFonts(state.chart.resources);
        redraw();
    }
    window.handleMessage = function (message) {
        const state = design.parseVisualizationState(message);
        if (state) { ++sequence; render(state); }
    };
    if (window.ResizeObserver) { new ResizeObserver(redraw).observe(element); }
    else { window.addEventListener('resize', redraw); }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    const start = sequence;
    const cancelStartup = window.SYMC_ECHARTS_STARTUP(bootstrap, function (state) {
        if (start === sequence) { render(state); }
    }, function () { render({status: 'error', error: translate('The startup data could not be loaded.')}); });
    window.addEventListener('beforeunload', cancelStartup);
}());
