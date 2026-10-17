(function () {
    'use strict';

    var bootstrap = window.SYMC_VISUALIZATION || {};
    var design = window.SYMC_ECHARTS_DESIGN;
    var rootElement = document.getElementById('echarts-pictorial-bar-root');
    var chartElement = document.getElementById('echarts-pictorial-bar-chart');
    var emptyElement = document.getElementById('echarts-pictorial-bar-empty');
    var errorElement = document.getElementById('echarts-pictorial-bar-error');
    var warningElement = document.getElementById('echarts-pictorial-bar-warning');
    var chart = null;
    var currentState = bootstrap.state || null;
    var currentTheme = null;

    var tileHeaderInset = bootstrap.mode === 'symcon'
        && (!bootstrap.options || bootstrap.options.tileHeaderVisible !== false) ? 58 : 0;
    var chartBootstrap = tileHeaderInset ? { mode: 'symcon', options: { tileHeaderVisible: false } } : bootstrap;
    chartElement.style.top = tileHeaderInset + 'px';
    chartElement.style.height = tileHeaderInset ? 'calc(100% - ' + tileHeaderInset + 'px)' : '100%';

    function translate(text) { return (bootstrap.translations || {})[text] || text; }
    var retainOnTransientError = design.createTransientFailureRetainer(
        chartElement, warningElement, translate, 'The PictorialBar values could not be loaded.'
    );
    function normalizeTheme(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return Object.prototype.hasOwnProperty.call(palettes, theme) ? theme : 'auto';
    }
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }
    function palette(theme) {
        var palettes = bootstrap.options && bootstrap.options.echartsThemes || {};
        return palettes[theme] || palettes.auto || {
            background: '#151619', text: '#F4F5F7', muted: '#969AA2',
            border: '#A5A9B0', accent: '#55CBB5'
        };
    }
    function resolveColor(variable, fallback) {
        var probe = document.createElement('span');
        probe.style.color = 'var(' + variable + ', ' + fallback + ')';
        probe.style.display = 'none';
        document.body.appendChild(probe);
        var resolved = window.getComputedStyle(probe).color;
        probe.remove();
        return resolved || fallback;
    }
    function colorsFor(theme) {
        var colors = palette(theme);
        if (theme !== 'auto') { return colors; }
        return {
            background: resolveColor('--symc-background', colors.background),
            text: resolveColor('--symc-text', colors.text),
            muted: resolveColor('--symc-text-muted', colors.muted),
            border: resolveColor('--symc-border', colors.border),
            accent: resolveColor('--symc-accent', colors.accent),
            seriesColors: colors.seriesColors
        };
    }
    function buildOption(model, theme) {
        var colors = colorsFor(theme);
        var bar = model.pictorialBar || {};
        var style = bar.style || {};
        var items = (bar.data || []).slice();
        if (style.sortOrder === 'ascending' || style.sortOrder === 'descending') {
            items.sort(function (a,b) { return (style.sortOrder === 'ascending' ? a.value-b.value : b.value-a.value) || a.order-b.order; });
        }
        var horizontal = style.orientation !== 'vertical';
        var width = chartElement.clientWidth || 1;
        var height = chartElement.clientHeight || 1;
        var axisFont = design.fontSizePercent(style.axisFontSizePercent, width < 430 ? 10 : 12);
        var valueFont = design.fontSizePercent(style.valueFontSizePercent, width < 430 ? 10 : 12);
        var titleSize = design.fontSizePercent(style.titleFontSizePercent,18);
        var title = design.chartTitleLayout(chartBootstrap,bar.title,titleSize);
        var top = title.contentTop + axisFont + 14;
        var left = horizontal && style.showLabels !== false ? Math.min(width * 0.32,axisFont * 11 + 14) : Math.min(width * 0.22,axisFont * 5);
        var right = horizontal && style.showValues !== false ? Math.min(width * 0.28,valueFont * 8 + 14) : 20;
        var bottom = horizontal ? axisFont * 2 + 22 : (style.showLabels !== false ? axisFont * 3 + 20 : 24);
        var major = Math.max(1,horizontal ? width-left-right : height-top-bottom);
        var cross = Math.max(1,(horizontal ? height-top-bottom : width-left-right)/Math.max(1,items.length));
        var maximum = style.scaleMaximum > 0 ? style.scaleMaximum : Math.max.apply(null,[1].concat(items.map(function(x){return x.value;})));
        var barWidth = Math.max(1,Math.min(style.barWidth || 40,cross*0.65));
        var size = Math.max(4,style.symbolSize || 12,Math.ceil(major/128));
        var crossSize = Math.max(1,Math.min(size,barWidth));
        var repeated = style.symbolRepeat !== false;
        var fontFamily = style.fontFamily || 'Arial, sans-serif';
        function itemColor(item) {
            if (style.colorMode !== 'value') {
                return item.color || (colors.seriesColors || [colors.accent])[item.order % (colors.seriesColors || [colors.accent]).length];
            }
            var minimum = style.colorMinimum == null ? 0 : style.colorMinimum;
            var maximum = style.colorMaximum == null ? 100 : style.colorMaximum;
            var ratio = Math.max(0, Math.min(1, (item.value - minimum) / (maximum - minimum || 1)));
            var stops = [style.lowColor || '#ee6666', style.middleColor || '#fac858', style.highColor || '#91cc75'];
            return design.interpolateColorStops(stops, ratio);
        }
        var valuePositions = horizontal ? {outside:'right',inside:'inside',insideStart:'insideLeft',insideEnd:'insideRight'}
            : {outside:'top',inside:'inside',insideStart:'insideBottom',insideEnd:'insideTop'};
        var labels = Object.create(null);
        items.forEach(function(x){labels[x.id]=x.label;});
        function format(item) {
            return Number(item.value).toLocaleString(bootstrap.language || undefined,{maximumFractionDigits:item.decimals == null ? 2 : item.decimals})
                + (item.unit ? ' '+item.unit : '');
        }
        var category = {type:'category',data:items.map(function(x){return x.id;}),inverse:horizontal,
            axisLabel:{show:style.showLabels !== false,fontSize:axisFont,color:style.axisColor || colors.text,
                rotate:style.categoryLabelRotate || 0,fontFamily:fontFamily,
                width:horizontal ? Math.max(8,left-16) : Math.max(10,(width-left-right)/Math.max(1,items.length)-6),
                overflow:'truncate',hideOverlap:true,interval:'auto',formatter:function(id){return labels[id] || '';}},
            axisTick:{show:style.showAxisTicks === true},axisLine:{lineStyle:{color:colors.border}}};
        var value = {type:'value',min:0,max:maximum,name:bar.unit || '',nameLocation:horizontal?'middle':'end',
            nameGap:horizontal?axisFont+18:15,nameTextStyle:{fontSize:axisFont,color:style.axisColor || colors.text},
            axisLabel:{show:style.showAxisValues !== false,fontSize:axisFont,fontFamily:fontFamily,color:style.axisColor || colors.text,hideOverlap:true},
            axisTick:{show:style.showAxisTicks === true},
            splitLine:{show:style.showGrid !== false,lineStyle:{color:style.gridColor || colors.border,
                width:style.gridWidth == null ? 1 : style.gridWidth,type:style.gridType || 'solid',
                opacity:style.gridOpacityPercent == null ? 0.25 : style.gridOpacityPercent/100}}};
        return {
            ...design.animationOptions(style,{enabled:true,initialDuration:350,updateDuration:500}),
            backgroundColor:'transparent',textStyle:{fontFamily:fontFamily,color:colors.text},
            aria:{enabled:true,label:{description:(bar.title || 'PictorialBar')+'. '+items.map(function(item){return item.label+': '+format(item);}).join('; ')}},
            title:{show:title.show,text:bar.title || '',left:'center',top:title.top,textStyle:{
                color:style.titleColor || colors.text,fontSize:titleSize,fontFamily:fontFamily,fontWeight:style.titleBold === false?'normal':'bold',
                overflow:'truncate',width:Math.max(1,width-36)}},
            grid:{left:left,right:right,top:top,bottom:bottom,containLabel:false},
            xAxis:horizontal?value:category,yAxis:horizontal?category:value,
            tooltip:{show:style.showTooltip !== false,trigger:'item',confine:true,
                extraCssText:'max-width:'+Math.max(80,width-24)+'px;white-space:normal;overflow-wrap:anywhere;',
                formatter:function(p){var item=items[p.dataIndex];return item?escapeHTML(item.label)+'<br>'+escapeHTML(format(item)):'';}},
            series:[{type:'pictorialBar',symbol:style.symbol || 'rect',symbolRepeat:repeated,symbolRepeatDirection:'start',
                symbolSize:repeated?(horizontal?[size,crossSize]:[crossSize,size]):['100%','100%'],
                symbolMargin:style.symbolGap == null ? 2 : style.symbolGap,symbolClip:repeated,
                ...(repeated?{symbolBoundingData:maximum}:{}),symbolPosition:'start',barWidth:barWidth,clip:true,
                itemStyle:{opacity:style.opacityPercent == null ? 0.9 : style.opacityPercent/100,
                    borderWidth:style.borderWidth || 0,borderType:style.borderType || 'solid',
                    shadowBlur:style.shadowBlur || 0,shadowColor:style.shadowColor || 'rgba(0,0,0,0.35)',
                    shadowOffsetX:style.shadowOffsetX || 0,shadowOffsetY:style.shadowOffsetY || 0,
                    ...(style.borderColor?{borderColor:style.borderColor}:{})},
                emphasis:{disabled:style.emphasisDisabled === true,focus:style.focus || 'self'},
                blur:{itemStyle:{opacity:style.blurOpacityPercent == null ? 0.1 : style.blurOpacityPercent/100}},
                label:{show:style.showValues !== false,position:valuePositions[style.valuePosition || 'outside'],color:style.valueColor || colors.text,
                    distance:style.valueDistance == null ? 5 : style.valueDistance,fontFamily:fontFamily,fontWeight:style.valueBold === true ? 'bold' : 'normal',
                    fontSize:valueFont,overflow:'truncate',width:horizontal?Math.max(8,right-10):Math.max(12,cross*0.95),
                    formatter:function(p){return items[p.dataIndex]?format(items[p.dataIndex]):'';}},
                labelLayout:{hideOverlap:style.hideOverlap !== false},
                data:items.map(function(x){return {id:x.id,name:x.label,value:Math.min(maximum,x.value),
                    itemStyle:{color:itemColor(x)}};})
            }]
        };
    }
    function displayError(message) {
        if (chart) { chart.clear(); }
        chartElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        errorElement.textContent = translate(message || 'The PictorialBar values could not be loaded.');
        errorElement.hidden = false;
    }
    function render(state) {
        if (retainOnTransientError(state, currentState, chart)) { return; }
        currentState = state;
        if (!state || state.status !== 'ready' || !state.chart) {
            displayError(state && state.error);
            return;
        }
        var model = state.chart;
        if (!window.echarts || typeof window.echarts.init !== 'function') {
            displayError('Apache ECharts could not be initialized.');
            return;
        }
        var wasHidden = chartElement.hidden;
        chartElement.hidden = false;
        errorElement.hidden = true;
        emptyElement.hidden = true;
        warningElement.hidden = true;
        rootElement.style.backgroundColor = bootstrap.mode === 'ipsview' && bootstrap.options.adaptToBackground === true
            ? 'transparent' : colorsFor(normalizeTheme(model.theme || 'auto')).background;
        var theme = normalizeTheme(model.theme || 'auto');
        if (chart && currentTheme !== theme) { chart.dispose(); chart = null; }
        if (!chart) {
            chart = window.echarts.init(chartElement, theme === 'auto' ? null : theme, { renderer: 'canvas' });
            currentTheme = theme;
        } else if (wasHidden) {
            chart.resize();
        }
        chart.setOption(buildOption(model, theme), true);

    }
    window.handleMessage = function (message) {
        var state = design.parseVisualizationState(message);
        if (state) { render(state); }
    };
    if (window.ResizeObserver) {
        new ResizeObserver(function () {
            if (!design.resizeChartIfNeeded(chart, chartElement)) { return; }
            if (currentState && currentState.status === 'ready') {
                chart.setOption(buildOption(currentState.chart, currentTheme || 'auto'), true);
            }
        }).observe(chartElement);
    } else {
        window.addEventListener('resize', function () { if (chart) { chart.resize(); } });
    }
    window.addEventListener('beforeunload', function () { if (chart) { chart.dispose(); } });
    render(currentState);
}());
