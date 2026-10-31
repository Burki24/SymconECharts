(function (root) {
    'use strict';
    function color(value, alpha, fallback) {
        if (!Number.isInteger(value) || value < 0 || value > 0xffffff) { return fallback || 'transparent'; }
        return 'rgba(' + [(value >> 16) & 255, (value >> 8) & 255, value & 255, Math.max(0, Math.min(1, alpha == null ? 1 : alpha))].join(',') + ')';
    }
    function paint(settings, name, fallback) { return color(settings[name], settings[name + '_Alpha'], fallback); }
    function escape(value) { return String(value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function number(value, digits, decimal, thousands) {
        if (!Number.isFinite(Number(value))) { return String(value); }
        const locale = root.SYMC_VISUALIZATION && root.SYMC_VISUALIZATION.options && root.SYMC_VISUALIZATION.options.locale || 'de-DE';
        const parts = new Intl.NumberFormat(locale, {minimumFractionDigits: Math.min(20, Math.max(0, digits)), maximumFractionDigits: Math.min(20, Math.max(0, digits))}).formatToParts(Number(value));
        return parts.map(function (part) {
            if (part.type === 'decimal') { return typeof decimal === 'string' && decimal !== 'Client' ? decimal : part.value; }
            if (part.type === 'group') { return typeof thousands === 'string' && thousands !== 'Client' ? thousands : part.value; }
            return part.value;
        }).join('');
    }
    function format(value, native) {
        const p = native && native.parameters || {};
        const options = p.OPTIONS || [];
        const found = options.find(function (option) { return option.Value === value || (typeof value === 'boolean' && Number(option.Value) === Number(value)); });
        if (found) { return String(found.Caption); }
        if (typeof value === 'boolean' || typeof value === 'string') { return String(value); }
        // At a shared boundary the interval starting at that value takes precedence (native Symcon contract).
        const interval = p.INTERVALS_ACTIVE && (p.INTERVALS || []).slice().sort(function(a,b){return b.IntervalMinValue-a.IntervalMinValue;})
            .find(function (range) { return value >= range.IntervalMinValue && value <= range.IntervalMaxValue; });
        if (interval && interval.ConstantActive) { return String(interval.ConstantValue); }
        let numeric = Number(value), prefix = p.PREFIX || '', suffix = p.SUFFIX || '', digits = p.DIGITS == null ? 2 : p.DIGITS;
        if (interval) {
            numeric /= interval.ConversionFactor || 1;
            if (interval.PrefixActive) { prefix = interval.PrefixValue; }
            if (interval.SuffixActive) { suffix = interval.SuffixValue; }
            if (interval.DigitsActive) { digits = interval.DigitsValue; }
        } else if (p.PERCENTAGE && Number(p.MAX) !== Number(p.MIN)) { numeric = (numeric - Number(p.MIN || 0)) / (Number(p.MAX) - Number(p.MIN || 0)) * 100; }
        return prefix + number(numeric, digits, p.DECIMAL_SEPARATOR, p.THOUSANDS_SEPARATOR) + suffix;
    }
    function textStyle(settings, prefix, fallback) {
        return {color: paint(settings, prefix + '_fontColor', fallback || '#000'), fontSize: Number(settings[prefix + '_fontSize'] || 12) * (settings.__fontScale || 1), fontFamily: settings[prefix + '_fontFamily'] || 'Arial'};
    }
    function symbol(point) {
        return {rectRounded:'roundRect',rectRot:'diamond',star:'path://M10 0L13 7L20 7L15 12L17 20L10 15L3 20L5 12L0 7L7 7Z',
            cross:'path://M8 0H12V8H20V12H12V20H8V12H0V8H8Z',crossRot:'path://M0 3L3 0L10 7L17 0L20 3L13 10L20 17L17 20L10 13L3 20L0 17L7 10Z',
            dash:'path://M0 0H20',line:'path://M0 20L20 0'}[point] || (['circle','rect','roundRect','triangle','diamond','none'].includes(point)?point:'circle');
    }
    function easing(value) {
        const map={linear:'linear',elastic:'elasticOut',bounce:'bounceOut'};
        ['Quad','Cubic','Quart','Quint','Sine','Expo','Circ','Elastic','Back','Bounce'].forEach(function(name){
            const native={Quad:'quadratic',Quart:'quartic',Quint:'quintic',Expo:'exponential',Circ:'circular'}[name]||name.toLowerCase();
            ['In','Out','InOut'].forEach(function(direction){map['ease'+direction+name]=native+direction;});
        });
        return map[value] || 'linear';
    }
    function dataLabel(c,s) {
        const align=c.datalabels_align||'center';
        return {show:!!s.datalabels_enable,position:{center:'inside',start:'left',end:'right',top:'top',bottom:'bottom',left:'left',right:'right'}[align]||'inside',
            distance:c.datalabels_offset||0,align:'center',verticalAlign:'middle',
            color:color(s.datalabels_FontColor<0?c.datalabels_fontColor:s.datalabels_FontColor,1,'#000'),
            fontFamily:c.datalabels_fontFamily||'Arial',fontSize:c.datalabels_fontSize*(c.__fontScale||1),
            backgroundColor:paint(s,'datalabels_BackgroundColor'),borderColor:paint(s,'datalabels_BorderColor'),
            borderWidth:c.datalabels_borderWidth||0,borderRadius:c.datalabels_borderRadius||0,padding:2};
    }
    function dataLabelValue(value,native,c,s) {
        const p=native&&native.parameters||{};
        return (s.datalabels_showPrefix?p.PREFIX||'':'')+number(value,c.data_precision,',','')+(s.datalabels_showSuffix?p.SUFFIX||'':'');
    }
    function graphicTransitions(elements, tooltip) {
        elements.forEach(function(element){
            element.transition=['shape','style','x','y','scaleX','scaleY'];
            if(element.clipPath){element.clipPath.transition=['shape'];}
            if(tooltip){element.tooltip={show:true,formatter:tooltip};}
            if(element.children){graphicTransitions(element.children,tooltip);}
        });
        return elements;
    }
    function base(state, width, height) {
        const c = state.settings, title = c.title_display ? (c.title_text || '') : '', legend = c.legend_display;
        const titleSize = textStyle(c, 'title').fontSize;
        const legendSize = textStyle(c, 'legend').fontSize;
        const side = legend && ['left', 'right'].includes(c.legend_position);
        const top = 16 + (title && c.title_position !== 'bottom' ? titleSize * 1.7 : 0) + (legend && c.legend_position === 'top' ? legendSize * 2 : 0);
        const bottom = 24 + (title && c.title_position === 'bottom' ? titleSize * 1.7 : 0) + (legend && c.legend_position === 'bottom' ? legendSize * 2 : 0);
        const legendWidth = side ? Math.min(width * 0.3, 190) : 0;
        return {animation: c.__animate !== false, animationDuration: Number(c.animation_duration || 0), animationDurationUpdate: Number(c.animation_duration || 0),
            animationEasing:easing(c.animation_easing),animationEasingUpdate:easing(c.animation_easing),
            backgroundColor: state.background,
            title: {show: !!title, text: title, left: 'center', top: c.title_position === 'bottom' ? undefined : 4, bottom: c.title_position === 'bottom' ? 4 : undefined, textStyle: textStyle(c, 'title')},
            legend: {show: !!legend, type: 'scroll', orient: side ? 'vertical' : 'horizontal',
                left: c.legend_position === 'left' ? 8 : (c.legend_position === 'right' ? undefined : (c.legend_align==='start'?8:c.legend_align==='end'?undefined:'center')),
                right: c.legend_position === 'right' || (!side&&c.legend_align==='end') ? 8 : undefined,
                top: c.legend_position === 'top' ? (title ? titleSize * 1.7 + 8 : 8) : (side ? 'middle' : undefined),
                bottom: c.legend_position === 'bottom' ? (title && c.title_position === 'bottom' ? titleSize * 1.7 + 8 : 8) : undefined,
                itemWidth: c.legend_boxWidth || 40, textStyle: textStyle(c, 'legend')},
            tooltip: {show: c.tooltips_enabled !== false, confine: true, backgroundColor: paint(c, 'tooltips_backgroundColor', '#000'), borderRadius: c.tooltips_cornerRadius,
                textStyle: textStyle(c, 'tooltips', '#fff')},
            __layout: {top: top, bottom: bottom, left: 16 + (side && c.legend_position === 'left' ? legendWidth : 0), right: 16 + (side && c.legend_position === 'right' ? legendWidth : 0), width: width, height: height}};
    }
    const api = {color: color, paint: paint, escape: escape, number: number, format: format, textStyle: textStyle, base: base,
        symbol:symbol,easing:easing,dataLabel:dataLabel,dataLabelValue:dataLabelValue,graphicTransitions:graphicTransitions};
    root.SYMC_JSLIVE_FORMAT = api;
    if (typeof module === 'object' && module.exports) { module.exports = api; }
}(typeof window === 'object' ? window : globalThis));
