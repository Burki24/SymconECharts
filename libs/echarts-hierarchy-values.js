(function (global) {
    'use strict';

    // Weights drive geometry. Original values, units and precision drive display.
    function valueText(node, override) {
        node = node || {};
        var value = override === undefined ? Number(node.originalValue ?? node.value) : Number(override);
        var fixed = Number.isInteger(node.decimals) && node.decimals >= 0 && node.decimals <= 6;
        return value.toLocaleString(undefined, fixed
            ? { minimumFractionDigits: node.decimals, maximumFractionDigits: node.decimals }
            : { maximumFractionDigits: 3 }) + (node.unit ? ' ' + node.unit : '');
    }

    function tooltip(parameter, normalized, translate) {
        function escape(value) {
            return String(value).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        var node = Object.assign({ value: parameter.value }, parameter.data || {});
        var path = (parameter.treePathInfo || []).map(function (part) { return part.name; }).filter(Boolean).join(' / ');
        var heading = escape(path || parameter.name);
        if (normalized && Array.isArray(node.children)) {
            return heading + '<br>' + escape(translate('Sum of normalized weights')) + ': '
                + escape(valueText({ value: parameter.value }));
        }
        var text = heading + ': ' + escape(valueText(node));
        if (normalized) {
            text += '<br>' + escape(translate('Normalized weight (0–100)')) + ': '
                + escape(valueText({ value: parameter.value }))
                + '<br>' + escape(translate('Normalization range')) + ': '
                + escape(valueText(node, node.minimum)) + ' – ' + escape(valueText(node, node.maximum));
        }
        return text;
    }

    global.SYMC_ECHARTS_HIERARCHY_VALUES = Object.freeze({ valueText: valueText, tooltip: tooltip });
}(window));
