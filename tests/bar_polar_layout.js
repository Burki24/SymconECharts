'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarPolar', 'visualization', 'app.js'), 'utf8'
);
const designSource = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8'
);
const chartElement = { hidden: false, clientWidth: 625, clientHeight: 560 };
const errorElement = { hidden: true, textContent: '' };
const options = [];
const handlers = {};
const actions = [];
let initialized = 0;
let resizeCallback = null;
let resized = 0;
const chart = {
    setOption: next => { options.push(next); },
    on: (event, query, handler) => { handlers[event] = handler; },
    dispatchAction: action => { actions.push(action); },
    clear: () => {}, resize: () => { resized++; }, dispose: () => {}
};
const palette = {
    background: '#111111', text: '#eeeeee', muted: '#aaaaaa', border: '#777777',
    accent: '#55cbb5', seriesColors: ['#123456', '#abcdef']
};
const state = {
    status: 'ready',
    chart: {
        theme: 'dark',
        polar: {
            title: 'Polar test', unit: '°C', decimals: 1,
            style: {
                mode: 'radial', sortOrder: 'descending', showCategoryLabels: true,
                showValues: true, showGrid: true, barWidthPercent: 60,
                innerRadiusPercent: 12, outerRadiusPercent: 76,
                startAngle: 90, clockwise: true, roundCaps: false
            }
        },
        items: [
            { id: 'variable-1', label: '<Room>', value: 5, decimals: 1, color: '', order: 0 },
            { id: 'variable-2', label: '<Room>', value: 10, decimals: 0, color: '#ff5500', order: 1 },
            { id: 'variable-3', label: 'Outside', value: -3, decimals: 2, color: '', order: 2 }
        ]
    }
};
const window = {
    SYMC_VISUALIZATION: {
        mode: 'symcon', state,
        options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true },
        translations: {}
    },
    echarts: { init: () => { initialized++; return chart; } },
    ResizeObserver: class {
        constructor(callback) { resizeCallback = callback; }
        observe() {}
    },
    addEventListener: () => {}
};
const document = {
    getElementById: id => id === 'echarts-bar-polar-chart' ? chartElement : errorElement,
    createElement: () => ({ style: {}, remove: () => {} }),
    body: { appendChild: () => {} }
};
vm.runInNewContext(designSource, { window, document, console });
vm.runInNewContext(source, { window, document, console, ResizeObserver: window.ResizeObserver });

assert.equal(initialized, 1);
function pixels(value, basis) {
    return typeof value === 'string' ? parseFloat(value) * basis / 100 : value;
}
function assertProtectedGeometry(option, width, height, topClearance) {
    const centerX = pixels(option.polar.center[0], width);
    const centerY = pixels(option.polar.center[1], height);
    const outerRadius = pixels(option.polar.radius[1], Math.min(width, height) / 2);
    const categoryAxis = option.angleAxis.type === 'category' ? option.angleAxis : option.radiusAxis;
    const sideClearance = categoryAxis.axisLabel.width + categoryAxis.axisLabel.margin + 8;
    assert.ok(centerY - outerRadius >= topClearance, 'Polar ring must clear the tile heading and scale.');
    assert.ok(centerY + outerRadius <= height - Math.min(45, height * 0.14),
        'Polar ring must leave room for bottom labels.');
    assert.ok(centerX - outerRadius >= sideClearance, 'Polar ring must leave room for left labels.');
    assert.ok(centerX + outerRadius <= width - sideClearance, 'Polar ring must leave room for right labels.');
}
const radial = options.at(-1);
assert.equal(radial.animation, true);
assert.equal(radial.animationDuration, 350);
assert.equal(radial.animationDurationUpdate, 500);
const initialOptionCount = options.length;
resizeCallback();
assert.equal(resized, 0, 'The initial ResizeObserver callback must not cancel an unchanged chart animation.');
assert.equal(options.length, initialOptionCount,
    'An unchanged chart size must not trigger a redundant option update.');
assertProtectedGeometry(radial, 625, 560, 108);
assert.equal(radial.radiusAxis.min, undefined, 'Existing Polar Tiles must retain automatic scale by default.');
assert.equal(radial.series[0].showBackground, false, 'Background tracks must be off by default.');
assert.equal(radial.series[0].coordinateSystem, 'polar');
assert.equal(radial.angleAxis.type, 'category');
assert.equal(radial.radiusAxis.type, 'value');
assert.deepEqual(Array.from(radial.angleAxis.data), ['<Room>', '<Room>', 'Outside']);
assert.ok(radial.angleAxis.axisLabel.margin >= 20, 'Radial category labels need room outside the outer ring.');
assert.equal(radial.angleAxis.axisLabel.interval, 0, 'Radial categories must not be skipped by a coarse automatic interval.');
assert.equal(radial.angleAxis.axisLabel.hideOverlap, true, 'Only actual radial label collisions may hide a category.');
assert.deepEqual(Array.from(radial.series[0].data, item => item.value), [10, 5, -3]);
assert.equal(radial.series[0].data[0].itemStyle.color, '#ff5500');
assert.equal(radial.series[0].data[1].itemStyle.color, '#abcdef');
assert.equal(radial.series[0].data[0].itemStyle.opacity, 1,
    'Existing Polar charts must keep fully opaque bars by default.');
assert.equal(radial.series[0].data[0].itemStyle.borderWidth, 0,
    'Existing Polar charts must not gain a visible outline by default.');
assert.equal(radial.series[0].data[0].itemStyle.shadowBlur, undefined,
    'Existing Polar bars must remain without a shadow by default.');
assert.equal(radial.series[0].emphasis, undefined,
    'Existing Polar charts must keep the bundled ECharts hover behavior by default.');
assert.equal(radial.angleAxis.startAngle, 90);
assert.equal(radial.angleAxis.endAngle, undefined, 'Existing full-circle Polar charts must keep their default angle extent.');
assert.equal(radial.angleAxis.axisLine.show, true, 'Full-circle grid outlines must remain visible.');
assert.equal(radial.series[0].label.rotate, 0, 'Polar value labels must remain horizontally readable.');
assert.equal(radial.angleAxis.axisLabel.fontSize, 10, 'Category labels must use a compact default font size.');
assert.equal(radial.radiusAxis.axisLabel.fontSize, 10, 'Scale labels must use a compact default font size.');
assert.equal(radial.series[0].label.fontSize, 10, 'Value labels must use a compact default font size.');
assert.equal(radial.series[0].label.position, 'middle', 'Value anchors must be centered inside their bars.');
assert.equal(radial.series[0].data[1].label.color, '#111111', 'Light bars need dark value text.');
assert.equal(radial.series[0].data[2].label.color, '#eeeeee', 'Dark bars need light value text.');
assert.equal(
    radial.series[0].label.formatter({ value: 5, dataIndex: 1 }),
    Number(5).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' °C'
);
assert.equal(radial.series[0].label.formatter({ value: 10, dataIndex: 0 }), '10 °C');
assert.equal(
    radial.series[0].label.formatter({ value: -3, dataIndex: 2 }),
    Number(-3).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' °C'
);
assert.match(radial.tooltip.formatter({ dataIndex: 0 }), /&lt;Room&gt;/);
assert.doesNotMatch(radial.tooltip.formatter({ dataIndex: 0 }), /<Room>/);

window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: {
            ...state.chart.polar.style,
            animationEnabled: true, animationDuration: 700, animationDurationUpdate: 1200,
            animationEasing: 'bounceOut', animationEasingUpdate: 'linear',
            animationDelay: 120, animationDelayUpdate: 240
        } }
    }
});
const customAnimation = options.at(-1);
assert.equal(customAnimation.animation, true);
assert.equal(customAnimation.animationDuration, 700);
assert.equal(customAnimation.animationDurationUpdate, 1200);
assert.equal(customAnimation.animationEasing, 'bounceOut');
assert.equal(customAnimation.animationEasingUpdate, 'linear');
assert.equal(customAnimation.animationDelay, 120);
assert.equal(customAnimation.animationDelayUpdate, 240);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: {
            ...state.chart.polar.style,
            animationEnabled: true, animationDuration: 0, animationDurationUpdate: 3000
        } }
    }
});
const separateAnimation = options.at(-1);
assert.equal(separateAnimation.animation, true);
assert.equal(separateAnimation.animationDuration, 0);
assert.equal(separateAnimation.animationDurationUpdate, 3000);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: {
            ...state.chart.polar.style, animationEnabled: false,
            animationDuration: 700, animationDurationUpdate: 1200
        } }
    }
});
const disabledAnimation = options.at(-1);
assert.equal(disabledAnimation.animation, false);
assert.equal(disabledAnimation.animationDuration, 0);
assert.equal(disabledAnimation.animationDurationUpdate, 0);
window.matchMedia = query => ({ matches: query === '(prefers-reduced-motion: reduce)' });
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: {
            ...state.chart.polar.style, animationEnabled: true,
            animationDuration: 700, animationDurationUpdate: 1200
        } }
    }
});
const reducedAnimation = options.at(-1);
assert.equal(reducedAnimation.animation, false);
assert.equal(reducedAnimation.animationDuration, 0);
assert.equal(reducedAnimation.animationDurationUpdate, 0);
window.matchMedia = () => ({ matches: false });
window.SYMC_VISUALIZATION.mode = 'symcon';

window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...state.chart.polar.style, barOutlineWidth: 2, barOutlineColor: '#224466' }
        }
    }
});
const outlinedRadial = options.at(-1);
assert.equal(outlinedRadial.series[0].data[0].itemStyle.borderWidth, 2);
assert.equal(outlinedRadial.series[0].data[0].itemStyle.borderColor, '#224466');
assert.equal(outlinedRadial.series[0].backgroundStyle.borderWidth, undefined,
    'The bar outline must not frame background tracks.');

window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...state.chart.polar.style,
                barShadowBlur: 12, barShadowColor: '#223344', barShadowOpacityPercent: 60
            }
        }
    }
});
const shadowedRadial = options.at(-1);
assert.equal(shadowedRadial.series[0].data[0].itemStyle.shadowBlur, 12);
assert.equal(shadowedRadial.series[0].data[0].itemStyle.shadowColor, 'rgba(34,51,68,0.6)');
assert.equal(shadowedRadial.series[0].backgroundStyle.shadowBlur, undefined,
    'The bar shadow must not affect the background tracks.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...state.chart.polar.style, mode: 'tangential',
                barShadowBlur: 8, barShadowColor: '', barShadowOpacityPercent: 35
            }
        }
    }
});
const shadowedIPSView = options.at(-1);
assert.equal(shadowedIPSView.series[0].data[0].itemStyle.shadowBlur, 8);
assert.equal(shadowedIPSView.series[0].data[0].itemStyle.shadowColor, 'rgba(119,119,119,0.35)',
    'Independent IPSView shadows must use the active theme when no color is set.');
window.SYMC_VISUALIZATION.mode = 'symcon';

const focusState = {
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...state.chart.polar.style,
                barHighlightMode: 'focus', barHighlightColor: '#cc00aa'
            }
        }
    }
};
window.handleMessage(focusState);
const focusedRadial = options.at(-1);
assert.equal(focusedRadial.series[0].emphasis.focus, 'self');
assert.equal(focusedRadial.series[0].emphasis.blurScope, 'series');
assert.equal(focusedRadial.series[0].emphasis.itemStyle.borderColor, '#cc00aa');
assert.equal(focusedRadial.series[0].emphasis.itemStyle.borderWidth, 3);
assert.equal(focusedRadial.series[0].emphasis.itemStyle.opacity, 1);
assert.equal(focusedRadial.series[0].blur.itemStyle.opacity, 0.25);
assert.equal(focusedRadial.series[0].selectedMode, undefined,
    'Built-in selection would merge equal display names and must remain disabled.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage(focusState);
assert.equal(options.at(-1).series[0].emphasis.focus, 'self',
    'The IPSView renderer must support the same focus behavior with an independent design.');
window.SYMC_VISUALIZATION.mode = 'symcon';
assert.equal(typeof handlers.click, 'function');
handlers.click({ componentType: 'series', seriesIndex: 0, dataIndex: 0 });
assert.equal(actions.at(-1).type, 'highlight');
assert.equal(actions.at(-1).dataIndex, 0);
window.handleMessage({
    ...focusState,
    chart: {
        ...focusState.chart,
        polar: {
            ...focusState.chart.polar,
            style: { ...focusState.chart.polar.style, sortOrder: 'ascending' }
        }
    }
});
assert.equal(actions.at(-1).type, 'highlight');
assert.equal(actions.at(-1).dataIndex, 2,
    'A pinned item must follow its variable ID when value sorting changes.');
handlers.click({ componentType: 'series', seriesIndex: 0, dataIndex: 2 });
assert.equal(actions.at(-1).type, 'downplay', 'Tapping the same bar must release its highlight.');
assert.equal(actions.at(-1).dataIndex, 2);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...state.chart.polar.style, barHighlightMode: 'outline', barHighlightColor: '' }
        }
    }
});
const highlightedOnly = options.at(-1);
assert.equal(highlightedOnly.series[0].emphasis.focus, 'none');
assert.equal(highlightedOnly.series[0].emphasis.itemStyle.borderColor, palette.text,
    'Automatic highlight color must follow the active theme.');
assert.equal(highlightedOnly.series[0].blur, undefined);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...state.chart.polar.style, barHighlightMode: 'off' }
        }
    }
});
assert.equal(options.at(-1).series[0].emphasis.disabled, true);
const positionedRadialOptions = [];
for (const position of ['insideStart', 'insideEnd']) {
    window.handleMessage({
        status: 'ready', chart: {
            ...state.chart,
            polar: { ...state.chart.polar, style: { ...state.chart.polar.style, valueLabelPosition: position } }
        }
    });
    const positioned = options.at(-1);
    positionedRadialOptions.push(positioned);
    assert.equal(positioned.series[0].label.position, position,
        'The selected inner value-label position must reach the ECharts renderer.');
    assert.equal(positioned.series[0].label.rotate, 0,
        'Alternative inner positions must keep value text horizontal.');
    assert.equal(positioned.series[0].data[1].label.color, '#111111',
        'Alternative inner positions must retain contrast on light bars.');
}

window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: { ...state.chart.polar.style, valueLabelPosition: 'outside' } }
    }
});
const outsideRadial = options.at(-1);
assert.equal(outsideRadial.series[0].label.position, 'outside',
    'Outside value labels must be anchored beyond each radial bar.');
assert.equal(outsideRadial.series[0].data[1].label.color, palette.text,
    'Outside labels need the chart text color, not contrast against the bar fill.');

const tangentialState = {
    status: 'ready',
    chart: {
        theme: 'dark',
        polar: {
            title: '', unit: '%', decimals: 0,
            style: {
                mode: 'tangential', sortOrder: 'configured', showCategoryLabels: true,
                showValues: false, showGrid: false, barWidthPercent: 80,
                innerRadiusPercent: 20, outerRadiusPercent: 90,
                startAngle: 180, clockwise: false, roundCaps: true
            }
        },
        items: state.chart.items
    }
};
window.handleMessage(tangentialState);
const tangential = options.at(-1);
window.handleMessage({
    status: 'ready', chart: {
        ...tangentialState.chart,
        polar: {
            ...tangentialState.chart.polar,
            style: { ...tangentialState.chart.polar.style, barOutlineWidth: 3, barOutlineColor: '' }
        }
    }
});
const outlinedTangential = options.at(-1);
assert.equal(outlinedTangential.series[0].data[0].itemStyle.borderWidth, 3);
assert.equal(outlinedTangential.series[0].data[0].itemStyle.borderColor, palette.border,
    'Automatic outline color must follow the theme on concentric arcs.');
assert.equal(initialized, 1, 'Live updates must reuse the chart instance.');
assert.equal(tangential.angleAxis.type, 'value');
assert.equal(tangential.radiusAxis.type, 'category');
assert.deepEqual(Array.from(tangential.radiusAxis.data), ['<Room>', '<Room>', 'Outside']);
assert.ok(tangential.radiusAxis.axisLabel.margin >= 20, 'Concentric category labels need axis spacing.');
assert.equal(tangential.radiusAxis.axisLabel.interval, 0,
    'Concentric categories must not be skipped by a coarse automatic interval.');
assert.equal(tangential.radiusAxis.axisLabel.hideOverlap, true,
    'Only actual concentric label collisions may hide a category.');
assert.equal(tangential.angleAxis.clockwise, false);
assert.equal(tangential.angleAxis.endAngle, undefined, 'Existing counterclockwise charts must remain full circles.');
assert.equal(tangential.series[0].roundCap, true);
assert.equal(tangential.series[0].label.show, false);
assert.equal(tangential.series[0].label.rotate, 0);
assert.equal(tangential.series[0].label.position, 'middle');
assertProtectedGeometry(tangential, 625, 560, 70);
assert.ok(tangential.polar.radius[0] > 0 && tangential.polar.radius[0] < tangential.polar.radius[1]);
assert.equal(tangential.angleAxis.splitLine.show, false);

window.handleMessage({
    status: 'ready', chart: {
        ...tangentialState.chart,
        polar: {
            ...tangentialState.chart.polar,
            style: {
                ...tangentialState.chart.polar.style,
                showValues: true, roundCaps: false, valueLabelPosition: 'insideEnd'
            }
        }
    }
});
const positionedTangential = options.at(-1);
assert.equal(positionedTangential.series[0].label.position, 'insideEnd');
assert.equal(positionedTangential.series[0].label.rotate, 0);

window.handleMessage({
    status: 'ready', chart: {
        ...tangentialState.chart,
        polar: {
            ...tangentialState.chart.polar,
            style: {
                ...tangentialState.chart.polar.style,
                showValues: true, roundCaps: false, valueLabelPosition: 'outside'
            }
        }
    }
});
const outsideTangential = options.at(-1);
assert.equal(outsideTangential.series[0].label.position, 'outside');
assert.equal(outsideTangential.series[0].data[1].label.color, palette.text);

window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: { ...state.chart.polar.style, valueLabelPosition: 'outside' } }
    }
});
const outsideIPSView = options.at(-1);
assert.equal(outsideIPSView.series[0].label.position, 'outside',
    'IPSView must use the same outside value-label placement.');
window.SYMC_VISUALIZATION.mode = 'symcon';

const gradientStyle = {
    ...state.chart.polar.style, barFillMode: 'gradient', barGradientColor: '#223344'
};
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart, polar: { ...state.chart.polar, style: gradientStyle }
    }
});
const gradientRadial = options.at(-1);
assert.equal(gradientRadial.series[0].data[0].itemStyle.color.type, 'linear');
assert.equal(gradientRadial.series[0].data[0].itemStyle.color.colorStops[0].color, '#ff5500',
    'Gradient fill must begin with the configured source color.');
assert.equal(gradientRadial.series[0].data[0].itemStyle.color.colorStops[1].color, '#223344');
assert.equal(gradientRadial.series[0].data[0].label.textBorderWidth, 2,
    'Labels over gradients need a contrasting outline.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...tangentialState.chart,
        polar: { ...tangentialState.chart.polar, style: {
            ...tangentialState.chart.polar.style, barFillMode: 'gradient', barGradientColor: ''
        } }
    }
});
const gradientIPSView = options.at(-1);
assert.equal(gradientIPSView.series[0].data[1].itemStyle.color.colorStops[1].color, palette.background,
    'Automatic gradient end color in IPSView must follow the selected theme.');
window.SYMC_VISUALIZATION.mode = 'symcon';

window.handleMessage({
    status: 'ready', chart: {
        ...state.chart, polar: { ...state.chart.polar, style: {
            ...state.chart.polar.style, barOpacityPercent: 35
        } }
    }
});
const translucentRadial = options.at(-1);
assert.equal(translucentRadial.series[0].data[0].itemStyle.opacity, 0.35,
    'The Tile must apply bar opacity only to Polar bars.');
assert.equal(translucentRadial.series[0].data[0].label.textBorderWidth, 2,
    'Text inside translucent bars needs a contrast outline.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...tangentialState.chart, polar: { ...tangentialState.chart.polar, style: {
            ...tangentialState.chart.polar.style, barOpacityPercent: 65,
            barFillMode: 'gradient', barGradientColor: '#223344'
        } }
    }
});
const translucentIPSView = options.at(-1);
assert.equal(translucentIPSView.series[0].data[0].itemStyle.opacity, 0.65,
    'Independent IPSView opacity must also apply to concentric arcs.');
assert.equal(translucentIPSView.series[0].backgroundStyle.opacity, 0.25,
    'Bar opacity must not alter the independent background-track opacity.');
window.SYMC_VISUALIZATION.mode = 'symcon';

const fixedStyle = {
    ...state.chart.polar.style,
    valueAxisRangeMode: 'manual', valueAxisMinimum: -5, valueAxisMaximum: 25,
    showBarBackground: true, barBackgroundColor: '#778899', barBackgroundOpacityPercent: 40
};
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: fixedStyle }
    }
});
const fixedRadial = options.at(-1);
assert.equal(fixedRadial.radiusAxis.min, -5);
assert.equal(fixedRadial.radiusAxis.max, 25);
assert.equal(fixedRadial.radiusAxis.startValue, -5,
    'Radial bars must also start at the configured scale minimum.');
assert.equal(fixedRadial.series[0].showBackground, true);
assert.equal(fixedRadial.series[0].backgroundStyle.color, '#778899');
assert.equal(fixedRadial.series[0].backgroundStyle.opacity, 0.4);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: { ...fixedStyle, mode: 'tangential' } }
    }
});
const fixedTangential = options.at(-1);
assert.equal(fixedTangential.angleAxis.min, -5);
assert.equal(fixedTangential.angleAxis.max, 25);
assert.equal(fixedTangential.angleAxis.startValue, -5);
assert.equal(fixedTangential.series[0].showBackground, true);
const offsetItems = [
    { id: 'outside', label: 'Außen', value: 12.1, decimals: 1, color: '', order: 0 },
    { id: 'bath', label: 'Bad', value: 22.9, decimals: 1, color: '', order: 1 },
    { id: 'office', label: 'Büro', value: 24.3, decimals: 1, color: '', order: 2 }
];
const offsetState = {
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...fixedStyle, mode: 'tangential', sortOrder: 'configured',
                valueAxisMinimum: 5, valueAxisMaximum: 100
            }
        },
        items: offsetItems
    }
};
window.handleMessage(offsetState);
const offsetTangential = options.at(-1);
assert.equal(offsetTangential.angleAxis.min, 5);
assert.equal(offsetTangential.angleAxis.max, 100);
assert.equal(offsetTangential.angleAxis.startValue, 5,
    'Concentric arcs must start at the configured scale minimum instead of zero.');
assert.deepEqual(Array.from(offsetTangential.series[0].data, item => item.value), [12.1, 22.9, 24.3],
    'The rendered data and displayed values must retain their real values.');
assert.equal(offsetTangential.series[0].label.formatter({ value: 12.1, dataIndex: 0 }),
    Number(12.1).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' °C',
    'Concentric value labels must display the original values.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage(offsetState);
const offsetIPSView = options.at(-1);
assert.equal(offsetIPSView.angleAxis.startValue, 5,
    'The IPSView renderer must keep the fixed-scale baseline at its minimum.');
assert.equal(offsetIPSView.series[0].showBackground, true);
assert.equal(offsetIPSView.series[0].backgroundStyle.color, '#778899');
assert.equal(offsetIPSView.series[0].backgroundStyle.opacity, 0.4);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...fixedStyle, startAngle: 90, angularSpan: 180 }
        }
    }
});
const halfRadial = options.at(-1);
assert.equal(halfRadial.angleAxis.endAngle, -90,
    'Clockwise radial bars must run from the top toward the right-hand half-circle.');
assert.equal(halfRadial.angleAxis.axisLine.show, false,
    'A partial grid with an inner radius must not retain the full-circle axis outline.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...fixedStyle, mode: 'tangential', startAngle: 180, clockwise: false, angularSpan: 270 }
        }
    }
});
const partialTangential = options.at(-1);
assert.equal(partialTangential.angleAxis.endAngle, 450,
    'Counterclockwise concentric arcs must stop at their configured end angle.');
assert.equal(partialTangential.angleAxis.axisLine.show, false,
    'Concentric partial grids must not retain the full-circle axis outline.');
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...fixedStyle, mode: 'tangential', startAngle: 0, angularSpan: 120 }
        }
    }
});
const partialIPSView = options.at(-1);
assert.equal(partialIPSView.angleAxis.endAngle, -120,
    'The IPSView renderer must also honor partial-circle geometry.');
assert.equal(partialIPSView.angleAxis.axisLine.show, false,
    'The IPSView partial grid must follow its own configured angular extent.');
window.SYMC_VISUALIZATION.mode = 'symcon';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...fixedStyle,
                valueAxisRangeMode: 'auto',
                barBackgroundColor: '',
                barBackgroundOpacityPercent: 0
            }
        }
    }
});
const automaticTracks = options.at(-1);
assert.equal(automaticTracks.radiusAxis.min, undefined, 'Automatic scale must not keep fixed limits.');
assert.equal(automaticTracks.series[0].backgroundStyle.color, palette.border,
    'Automatic track color must follow the active theme.');
assert.equal(automaticTracks.series[0].backgroundStyle.opacity, 0,
    'Zero track opacity must stay fully transparent.');

chartElement.clientWidth = 1000;
chartElement.clientHeight = 700;
resizeCallback();
assert.equal(resized, 1, 'A size change must resize the ECharts instance.');
assertProtectedGeometry({
    ...automaticTracks,
    ...options.at(-1),
    angleAxis: { ...automaticTracks.angleAxis, ...options.at(-1).angleAxis }
}, 1000, 700, 70);
assert.ok(options.at(-1).polar.radius[1] > tangential.polar.radius[1], 'The ring must grow with the tile.');

chartElement.clientWidth = 300;
chartElement.clientHeight = 300;
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, title: '' }
    }
});
const compact = options.at(-1);
assertProtectedGeometry(compact, 300, 300, 70);
assert.ok(compact.polar.radius[1] >= 80,
    'Compact Symcon tiles must keep a legible Polar diameter instead of collapsing to a tiny ring.');
assert.ok(compact.angleAxis.axisLabel.width < radial.angleAxis.axisLabel.width,
    'Compact category labels must use less side clearance.');
assert.equal(compact.radiusAxis.axisLabel.show, false,
    'Compact Polar tiles must hide scale numbers that collide with bar values.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            title: '',
            style: { ...state.chart.polar.style, showValues: false }
        }
    }
});
assert.equal(options.at(-1).radiusAxis.axisLabel.show, true,
    'Compact Polar tiles without bar values may keep the numeric scale.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, title: '' },
        items: [
            { id: 'outside', label: 'Außen', value: 9.5, decimals: 1, color: '#586ee0', order: 0 },
            { id: 'bath', label: 'Bad', value: 22.6, decimals: 1, color: '#bed939', order: 1 },
            { id: 'office', label: 'Büro', value: 24.5, decimals: 1, color: '#555674', order: 2 }
        ]
    }
});
const compactUserValues = options.at(-1);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            title: '',
            style: { ...state.chart.polar.style, valueLabelPosition: 'outside' }
        },
        items: [
            { id: 'outside', label: 'Außen', value: 9.5, decimals: 1, color: '#586ee0', order: 0 },
            { id: 'bath', label: 'Bad', value: 22.6, decimals: 1, color: '#bed939', order: 1 },
            { id: 'office', label: 'Büro', value: 24.5, decimals: 1, color: '#555674', order: 2 }
        ]
    }
});
const compactOutside = options.at(-1);
assert.ok(compactOutside.polar.radius[1] >= 70,
    'Outside labels must not collapse a compact Polar chart into a tiny dial.');

chartElement.clientWidth = 925;
chartElement.clientHeight = 690;
const reportedState = {
    status: 'ready', chart: {
        theme: 'dark',
        polar: {
            title: '', unit: '°C', decimals: 1,
            style: {
                mode: 'tangential', sortOrder: 'ascending', showCategoryLabels: true,
                showValues: true, valueLabelPosition: 'insideEnd', showGrid: true,
                barWidthPercent: 60, innerRadiusPercent: 12, outerRadiusPercent: 76,
                startAngle: 90, angularSpan: 180, clockwise: true, roundCaps: true,
                valueAxisRangeMode: 'manual', valueAxisMinimum: 5, valueAxisMaximum: 30,
                showBarBackground: true, barBackgroundColor: '#E70D0D', barBackgroundOpacityPercent: 20
            }
        },
        items: [
            { id: 'outside', label: 'Außen', value: 12.2, decimals: 1, color: '#586ee0', order: 0 },
            { id: 'bath', label: 'Bad', value: 22.7, decimals: 1, color: '#bed939', order: 1 },
            { id: 'office', label: 'Büro', value: 25.1, decimals: 1, color: '#555674', order: 2 }
        ]
    }
};
window.handleMessage(reportedState);
const reportedUserLayout = options.at(-1);
assert.equal(reportedUserLayout.radiusAxis.z, 3,
    'Concentric category labels must render in front of background tracks and bars.');
assert.equal(reportedUserLayout.radiusAxis.axisLabel.textBorderColor, palette.background,
    'Category labels need a theme-matched outline over colored tracks.');
assert.equal(reportedUserLayout.series[0].label.align, 'center',
    'Horizontal values inside concentric arcs must be centered on their anchor.');

chartElement.clientWidth = 435;
chartElement.clientHeight = 324;
resizeCallback();
const reportedResizeUpdate = options.at(-1);
window.handleMessage(reportedState);
const compactConcentric = options.at(-1);

window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...state.chart.polar.style,
                categoryLabelFontSize: 9, valueLabelFontSize: 13, scaleLabelFontSize: 8
            }
        }
    }
});
const customIPSViewFonts = options.at(-1);
assert.equal(customIPSViewFonts.angleAxis.axisLabel.fontSize, 9);
assert.equal(customIPSViewFonts.radiusAxis.axisLabel.fontSize, 8);
assert.equal(customIPSViewFonts.series[0].label.fontSize, 13);

const simulatedTracks = Array.from({ length: 3 }, () => ({
    stopAnimation() {},
    setShape(shape) { this.shape = shape; }
}));
chart.getModel = () => ({
    getSeriesByIndex: () => ({}),
    getComponent: () => ({ axis: { getExtent: () => [90, -90] } })
});
chart.getViewOfSeriesModel = () => ({ _backgroundEls: simulatedTracks });
window.handleMessage(reportedState);
assert.ok(simulatedTracks.every(track => Math.abs(track.shape.startAngle + Math.PI / 2) < 0.001
    && Math.abs(track.shape.endAngle - Math.PI / 2) < 0.001 && track.shape.clockwise),
'The Polar renderer must align its background sectors even when the full ECharts runtime is unavailable.');

const categoryLabelState = {
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: { ...state.chart.polar.style, categoryLabelFontSize: 20, sortOrder: 'configured' }
        },
        items: [
            { id: 'outside', label: 'Temperatur Außen', value: 12, order: 0 },
            { id: 'bath', label: 'Temperatur Bad', value: 23, order: 1 },
            { id: 'living', label: 'Temperatur Wohnzimmer', value: 22, order: 2 },
            { id: 'office', label: 'Temperatur Büro', value: 25, order: 3 }
        ]
    }
};
const previousWidth = chartElement.clientWidth;
const previousHeight = chartElement.clientHeight;
chartElement.clientWidth = 1500;
chartElement.clientHeight = 900;
window.SYMC_VISUALIZATION.mode = 'ipsview';
window.handleMessage(categoryLabelState);
const wideIPSViewCategories = options.at(-1);
assert.ok(wideIPSViewCategories.angleAxis.axisLabel.width >= 230,
    'At 20 px, IPSView must reserve enough label width for full category names when space exists.');
chartElement.clientWidth = 500;
chartElement.clientHeight = 450;
resizeCallback();
assert.ok(options.at(-1).angleAxis.axisLabel.width < wideIPSViewCategories.angleAxis.axisLabel.width,
    'Resizing IPSView must update the category label width without waiting for new values.');
window.handleMessage(categoryLabelState);
const compactIPSViewCategories = options.at(-1);
assert.ok(compactIPSViewCategories.angleAxis.axisLabel.width
    < wideIPSViewCategories.angleAxis.axisLabel.width,
'Narrow IPSView widgets must bound category labels instead of squeezing the Polar chart away.');
assert.ok(compactIPSViewCategories.polar.radius[1] >= 75,
    'Narrow IPSView widgets must preserve a readable Polar radius.');
chartElement.clientWidth = 1500;
chartElement.clientHeight = 900;
window.SYMC_VISUALIZATION.mode = 'symcon';
window.handleMessage(categoryLabelState);
assert.ok(options.at(-1).angleAxis.axisLabel.width <= 90,
    'The existing compact Tile label policy must remain unchanged.');
chartElement.clientWidth = previousWidth;
chartElement.clientHeight = previousHeight;

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    window.echarts.format = echarts.format;
    chartElement.clientWidth = 1500;
    chartElement.clientHeight = 900;
    window.SYMC_VISUALIZATION.mode = 'ipsview';
    window.handleMessage(categoryLabelState);
    const measuredIPSViewCategories = options.at(-1);
    const longestCategoryWidth = Math.max(...categoryLabelState.chart.items.map(item =>
        echarts.format.getTextRect(item.label, '20px sans-serif').width));
    assert.ok(measuredIPSViewCategories.angleAxis.axisLabel.width >= longestCategoryWidth + 8,
        'The bundled ECharts text measurement must determine the IPSView label width.');
    delete window.echarts.format;
    chartElement.clientWidth = previousWidth;
    chartElement.clientHeight = previousHeight;
    window.SYMC_VISUALIZATION.mode = 'symcon';
    const wideCategoryChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 1500, height: 900 });
    wideCategoryChart.setOption(measuredIPSViewCategories);
    const renderedCategoryNames = wideCategoryChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan')
        .map(element => element.style.text);
    assert.ok(categoryLabelState.chart.items.every(item => renderedCategoryNames.includes(item.label)),
        'The bundled ECharts runtime must render every 20 px IPSView category name without truncation.');
    wideCategoryChart.dispose();
    const animationChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 625, height: 560 });
    animationChart.setOption(customAnimation, true);
    const animatedData = () => animationChart.getModel().getSeriesByIndex(0).getData();
    assert.ok(animatedData().getItemGraphicEl(0).animators.some(animator =>
        animator.scope === 'enter' && animator._maxTime === 700),
    'The bundled ECharts runtime must use the configured initial animation duration.');
    animationChart.setOption({
        ...customAnimation,
        series: [{
            ...customAnimation.series[0],
            data: customAnimation.series[0].data.map((item, index) => ({
                ...item, value: index === 0 ? item.value + 1 : item.value
            }))
        }]
    }, true);
    assert.ok(animatedData().getItemGraphicEl(0).animators.some(animator =>
        animator.scope === 'update' && animator._maxTime === 1200),
    'The bundled ECharts runtime must use the configured update animation duration.');
    animationChart.dispose();
    const focusChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 625, height: 560 });
    focusChart.setOption(focusedRadial);
    focusChart.dispatchAction({ type: 'highlight', seriesIndex: 0, dataIndex: 0 });
    const focusData = focusChart.getModel().getSeriesByIndex(0).getData();
    assert.equal(focusData.getItemGraphicEl(0).hoverState, 2,
        'The bundled ECharts runtime must highlight the selected bar.');
    assert.equal(focusData.getItemGraphicEl(1).hoverState, 1,
        'Focusing a bar must dim other bars, even when their display names match.');
    focusChart.dispatchAction({ type: 'downplay', seriesIndex: 0, dataIndex: 0 });
    assert.equal(focusData.getItemGraphicEl(0).hoverState, 0,
        'Tapping the same bar again must restore its normal state.');
    focusChart.dispose();
    for (const [option, expectedColor, expectedWidth] of [
        [outlinedRadial, '#224466', 2],
        [outlinedTangential, palette.border, 3]
    ]) {
        const outlineChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
        outlineChart.setOption(option);
        outlineChart.renderToSVGString();
        const outlinedBars = outlineChart.getZr().storage.getDisplayList().filter(
            element => ['sector', 'sausage'].includes(element.type)
                && element.style.stroke === expectedColor
        );
        assert.equal(outlinedBars.length, 3, 'Both Polar modes must render three outlined bars.');
        assert.ok(outlinedBars.every(element => element.style.stroke === expectedColor
            && element.style.lineWidth === expectedWidth),
        'The bundled ECharts runtime must draw the configured outline on each Polar bar.');
        outlineChart.dispose();
    }
    const livePolarElement = { hidden: false, clientWidth: 925, clientHeight: 690 };
    const livePolarError = { hidden: true, textContent: '' };
    const livePolarChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 925, height: 690 });
    let liveResizeCallback = null;
    const livePolarWindow = {
        SYMC_VISUALIZATION: {
            mode: 'symcon', state: reportedState,
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true },
            translations: {}
        },
        echarts: { init: () => livePolarChart },
        ResizeObserver: class {
            constructor(callback) { liveResizeCallback = callback; }
            observe() {}
        },
        addEventListener: () => {}
    };
    const livePolarDocument = {
        getElementById: id => id === 'echarts-bar-polar-chart' ? livePolarElement : livePolarError,
        createElement: () => ({ style: {}, remove: () => {} }),
        body: { appendChild: () => {} }
    };
    vm.runInNewContext(designSource, { window: livePolarWindow, document: livePolarDocument, console });
    vm.runInNewContext(source, {
        window: livePolarWindow, document: livePolarDocument, console,
        ResizeObserver: livePolarWindow.ResizeObserver
    });
    const initialBar = livePolarChart.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0);
    assert.ok(initialBar.animators.some(animator =>
        animator.scope === 'enter' && animator._maxTime === 350),
    'The bundled ECharts runtime must begin the Polar bar entrance animation.');
    liveResizeCallback();
    assert.ok(initialBar.animators.some(animator =>
        animator.scope === 'enter' && animator._maxTime === 350),
    'An unchanged ResizeObserver notification must leave the entrance animation running.');
    livePolarWindow.handleMessage({
        ...reportedState,
        chart: {
            ...reportedState.chart,
            items: reportedState.chart.items.map((item, index) => ({
                ...item, value: index === 0 ? item.value + 1 : item.value
            }))
        }
    });
    const updatedBar = livePolarChart.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0);
    assert.ok(updatedBar.animators.some(animator =>
        animator.scope === 'update' && animator._maxTime === 500),
    'The bundled ECharts runtime must animate a changed Polar value.');
    liveResizeCallback();
    assert.ok(updatedBar.animators.some(animator =>
        animator.scope === 'update' && animator._maxTime === 500),
    'An unchanged ResizeObserver notification must leave the update animation running.');
    function liveTracks() {
        return livePolarChart.getZr().storage.getDisplayList().filter(
            element => element.type === 'sector' && element.style.fill === '#E70D0D'
        );
    }
    const initialTracks = liveTracks();
    assert.equal(initialTracks.length, 3, 'The reported concentric Tile must retain all three background tracks.');
    assert.ok(initialTracks.every(element => Math.abs(element.shape.endAngle - element.shape.startAngle)
        <= Math.PI + 0.001),
    'The reported 180-degree concentric tracks must stay inside the selected half-circle.');
    assert.ok(initialTracks.every(element => Math.abs(element.shape.startAngle + Math.PI / 2) < 0.001
        && Math.abs(element.shape.endAngle - Math.PI / 2) < 0.001),
    'Concentric tracks must align with the chosen start and end angles.');
    livePolarWindow.SYMC_VISUALIZATION.mode = 'ipsview';
    livePolarWindow.handleMessage({
        ...reportedState,
        chart: {
            ...reportedState.chart,
            polar: {
                ...reportedState.chart.polar,
                style: {
                    ...reportedState.chart.polar.style,
                    startAngle: 180, angularSpan: 120, clockwise: false
                }
            }
        }
    });
    assert.ok(liveTracks().every(element => Math.abs(element.shape.endAngle - element.shape.startAngle)
        <= 120 * Math.PI / 180 + 0.001 && element.shape.clockwise === false),
    'Counterclockwise IPSView tracks must follow its independent angular span.');
    livePolarElement.clientWidth = 435;
    livePolarElement.clientHeight = 324;
    livePolarChart.resize({ width: 435, height: 324 });
    liveResizeCallback();
    assert.ok(liveTracks().every(element => Math.abs(element.shape.endAngle - element.shape.startAngle)
        <= 120 * Math.PI / 180 + 0.001),
    'Resizing must not restore full-circle concentric background tracks.');
    livePolarWindow.handleMessage({
        ...reportedState,
        chart: {
            ...reportedState.chart,
            polar: {
                ...reportedState.chart.polar,
                style: { ...reportedState.chart.polar.style, angularSpan: 360 }
            }
        }
    });
    assert.ok(liveTracks().every(element => Math.abs(element.shape.endAngle - element.shape.startAngle)
        >= 2 * Math.PI - 0.001),
    'Full-circle concentric tracks must retain their original 360-degree behavior.');
    livePolarChart.dispose();
    const gradientChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 625, height: 560 });
    gradientChart.setOption(gradientRadial);
    const gradientSVG = gradientChart.renderToSVGString();
    assert.match(gradientSVG, /<linearGradient\b/,
        'The bundled ECharts renderer must draw Polar bars with a linear gradient.');
    assert.match(gradientSVG, /#223344/i,
        'The configured gradient end color must reach the rendered Polar chart.');
    gradientChart.dispose();
    const translucentChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 625, height: 560 });
    translucentChart.setOption(translucentRadial);
    assert.ok(translucentChart.getZr().storage.getDisplayList().some(element =>
        element.type === 'sector' && element.style.fill === '#ff5500'
            && element.style.opacity === 0.35),
    'The bundled ECharts renderer must draw a Polar bar with configured opacity.');
    translucentChart.dispose();
    const shadowChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 625, height: 560 });
    shadowChart.setOption(shadowedRadial);
    assert.ok(shadowChart.getZr().storage.getDisplayList().some(element =>
        element.type === 'sector' && element.style.shadowBlur === 12
            && element.style.shadowColor === 'rgba(34,51,68,0.6)'),
    'The bundled ECharts renderer must apply shadow only to a Polar bar.');
    shadowChart.dispose();
    const compactConcentricChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 435, height: 324 });
    compactConcentricChart.setOption(compactConcentric);
    assert.deepEqual(compactConcentricChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan' && ['Außen', 'Bad', 'Büro'].includes(element.style.text))
        .map(element => element.style.text), ['Außen', 'Bad', 'Büro'],
    'Resizing the concentric Tile must keep every category label, including Bad.');
    compactConcentricChart.dispose();
    const resizedConcentricChart = echarts.init(null, null,
        { renderer: 'svg', ssr: true, width: 925, height: 690 });
    resizedConcentricChart.setOption(reportedUserLayout);
    resizedConcentricChart.resize({ width: 435, height: 324 });
    resizedConcentricChart.setOption(reportedResizeUpdate);
    assert.deepEqual(resizedConcentricChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan' && ['Außen', 'Bad', 'Büro'].includes(element.style.text))
        .map(element => element.style.text), ['Außen', 'Bad', 'Büro'],
    'The existing concentric chart must retain all categories after an actual ECharts resize.');
    resizedConcentricChart.dispose();
    const reportedChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 925, height: 690 });
    reportedChart.setOption(reportedUserLayout);
    const reportedElements = reportedChart.getZr().storage.getDisplayList();
    const categoryNames = ['Außen', 'Bad', 'Büro'];
    const reportedBars = reportedElements.map((element, index) => ({ element, index }))
        .filter(({ element }) => element.type === 'sausage');
    const reportedCategories = reportedElements.map((element, index) => ({ element, index }))
        .filter(({ element }) => element.type === 'tspan' && categoryNames.includes(element.style.text));
    assert.equal(reportedBars.length, 3, 'The reported concentric layout must render every bar.');
    assert.deepEqual(reportedCategories.map(({ element }) => element.style.text), categoryNames,
        'Every category, including the previously hidden middle label, must render.');
    assert.ok(reportedCategories.every(({ index }) => index > reportedBars.at(-1).index),
        'Category labels must be painted after all bars and background tracks.');
    const reportedValues = reportedElements.filter(element => element.type === 'tspan'
        && / °C$/.test(element.style.text));
    assert.equal(reportedValues.length, 3, 'All three value labels must remain visible.');
    for (let index = 0; index < reportedValues.length; index++) {
        const label = reportedValues[index];
        const bar = reportedBars[index].element;
        const bounds = label.getBoundingRect().clone();
        bounds.applyTransform(label.getComputedTransform());
        assert.equal(label.style.fill, index === 2 ? palette.text : palette.background,
            'Value-label contrast must follow its own bar color.');
        for (const [x, y] of [
            [bounds.x, bounds.y], [bounds.x + bounds.width, bounds.y],
            [bounds.x, bounds.y + bounds.height], [bounds.x + bounds.width, bounds.y + bounds.height]
        ]) {
            const dx = x - bar.shape.cx;
            const dy = y - bar.shape.cy;
            const radius = Math.hypot(dx, dy);
            const angle = Math.atan2(dy, dx);
            assert.ok(radius >= bar.shape.r0 - 3 && radius <= bar.shape.r + 3
                && angle >= bar.shape.startAngle - 0.05 && angle <= bar.shape.endAngle + 0.05,
            'Each value label must stay on its own colored arc: ' + label.style.text);
        }
    }
    reportedChart.dispose();
    const roundedStartChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 925, height: 690 });
    roundedStartChart.setOption({
        ...reportedUserLayout,
        series: [{
            ...reportedUserLayout.series[0],
            label: { ...reportedUserLayout.series[0].label, position: 'insideStart' }
        }]
    });
    assert.equal(roundedStartChart.getZr().storage.getDisplayList().filter(
        element => element.type === 'tspan' && / °C$/.test(element.style.text)
    ).length, 3, 'Rounded inside-start positions must retain every value label.');
    roundedStartChart.dispose();
    const compactChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 300, height: 300 });
    compactChart.setOption(compact);
    const compactTexts = compactChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan')
        .map(element => element.style.text);
    assert.ok(!compactTexts.includes('12'), 'Compact Polar tiles must not render overlapping value-axis ticks.');
    assert.ok(compactTexts.includes('10 °C'), 'Compact Polar tiles must keep the bar values readable.');
    compactChart.dispose();
    const userChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 300, height: 300 });
    userChart.setOption(compactUserValues);
    const valueLabels = userChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan' && /°C$/.test(element.style.text))
        .map(element => {
            const bounds = element.getBoundingRect().clone();
            bounds.applyTransform(element.getComputedTransform());
            return bounds;
        });
    assert.equal(valueLabels.length, 3, 'All three values from the compact user scenario must remain visible.');
    for (let left = 0; left < valueLabels.length; left++) {
        for (let right = left + 1; right < valueLabels.length; right++) {
            assert.equal(valueLabels[left].intersect(valueLabels[right]), false,
                'Compact user values must not overlap one another.');
        }
    }
    userChart.dispose();
    for (const [option, width, height] of [
        [outsideRadial, 625, 560], [outsideTangential, 625, 560],
        [outsideIPSView, 625, 560], [compactOutside, 300, 300]
    ]) {
        const outsideChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width, height });
        outsideChart.setOption(option);
        const sectors = outsideChart.getZr().storage.getDisplayList().filter(
            element => element.type === 'sector' && element.getTextContent()
        );
        assert.equal(sectors.length, 3, 'Outside placement must keep every bar value visible.');
        const isRadial = option.angleAxis.type === 'category';
        assert.ok(sectors.every(element => (isRadial ? ['startArc', 'endArc'] : ['startAngle', 'endAngle'])
            .includes(element.textConfig.position)), 'ECharts must place values beyond the bar ends.');
        assert.ok(sectors.every(element => element.getTextContent().style.fill === palette.text),
            'Outside labels must use the chart text color in the rendered SVG.');
        const texts = outsideChart.getZr().storage.getDisplayList().filter(
            element => element.type === 'tspan' && / (?:°C|%)$/.test(element.style.text)
        );
        assert.equal(texts.length, 3, 'All outside value labels must render.');
        const valueBounds = [];
        for (const element of texts) {
            const bounds = element.getBoundingRect().clone();
            bounds.applyTransform(element.getComputedTransform());
            valueBounds.push({ text: element.style.text, bounds });
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width
                && bounds.y >= (option === outsideIPSView ? 8 : 58) && bounds.y + bounds.height <= height,
            'Outside value labels must stay within the usable chart area.');
        }
        const categoryBounds = outsideChart.getZr().storage.getDisplayList().filter(
            element => element.type === 'tspan' && ['<Room>', 'Outside', 'Außen', 'Bad', 'Büro']
                .includes(element.style.text)
        ).map(element => {
            const bounds = element.getBoundingRect().clone();
            bounds.applyTransform(element.getComputedTransform());
            return { text: element.style.text, bounds };
        });
        for (const value of valueBounds) {
            for (const category of categoryBounds) {
                assert.equal(value.bounds.intersect(category.bounds), false,
                    'Outside value labels must not overlap category labels: '
                    + value.text + ' / ' + category.text + ' / ' + option.angleAxis.type);
            }
        }
        for (let left = 0; left < valueBounds.length; left++) {
            for (let right = left + 1; right < valueBounds.length; right++) {
                assert.equal(valueBounds[left].bounds.intersect(valueBounds[right].bounds), false,
                    'Outside bar values must not overlap one another in the tested layouts.');
            }
        }
        outsideChart.dispose();
    }
    for (const option of [radial, tangential, fixedRadial, fixedTangential,
        halfRadial, partialTangential, partialIPSView, ...positionedRadialOptions, positionedTangential]) {
        const realChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
        realChart.setOption(option);
        assert.ok(realChart.renderToSVGString().includes('<svg'), 'Both polar modes must render in ECharts.');
        if (option === radial) {
            assert.ok(realChart.getZr().storage.getDisplayList().some(element => element.type === 'ring'),
                'Existing full-circle charts must retain their circular grid outline.');
        }
        if (option === halfRadial || option === partialTangential || option === partialIPSView) {
            const axis = realChart.getModel().getComponent('angleAxis').axis;
            assert.deepEqual(Array.from(axis.getExtent()),
                option === halfRadial ? [90, -90] : option === partialTangential ? [180, 450] : [0, -120],
                'ECharts must apply the requested partial angular extent.');
            const elements = realChart.getZr().storage.getDisplayList();
            assert.equal(elements.filter(element => element.type === 'ring').length, 0,
                'Partial polar charts must not render a 360-degree grid ring.');
            assert.ok(elements.some(element => element.type === 'path' && element.style.fill === null),
                'Partial polar charts must retain their angular grid arcs.');
            if (option === halfRadial) {
                const centerX = option.polar.center[0];
                const gridArc = elements.find(element => element.type === 'path' && element.style.fill === null);
                assert.ok(gridArc.getBoundingRect().x >= centerX - 5,
                    'The radial grid arcs must occupy the same right-hand half as the bars.');
                const bars = elements.filter(element => element.type === 'sector' && element.getTextContent());
                assert.ok(bars.every(element => element.getBoundingRect().x >= centerX - 5),
                    'The radial bars must stay in the configured right-hand half-circle.');
                const tracks = elements.filter(element => element.type === 'sector'
                    && element.style.fill === '#778899');
                assert.equal(tracks.length, 3, 'The partial radial layout must retain its background tracks.');
                assert.ok(tracks.every(element => element.getBoundingRect().x >= centerX - 5),
                    'Background tracks must follow the same half-circle as the radial bars.');
            }
        }
        if (option === fixedRadial || option === fixedTangential) {
            const tracks = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'sector' && element.style.fill === '#778899'
            );
            assert.equal(tracks.length, 3, 'Each Polar value must have one ECharts background track.');
            assert.ok(tracks.every(element => element.style.opacity === 0.4),
                'The configured background-track opacity must reach the rendered Polar sectors.');
        }
        if (option === radial) {
            const categoryLabels = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'tspan' && ['<Room>', 'Outside'].includes(element.style.text)
            );
            assert.equal(categoryLabels.length, 3);
            for (const element of categoryLabels) {
                const bounds = element.getBoundingRect();
                assert.ok(bounds.x >= 8 && bounds.x + bounds.width <= 617, 'Categories must stay inside tile sides.');
                assert.ok(bounds.y >= 100 && bounds.y + bounds.height <= 548, 'Categories must clear the title and tile bottom.');
            }
            const valueSectors = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'sector' && element.getTextContent()
            );
            assert.ok(valueSectors.length >= 3, 'Radial values must render as polar bar labels.');
            assert.ok(
                valueSectors.every(element => element.textConfig.rotation === 0),
                'Rendered polar values must not follow the bar angle.'
            );
            assert.ok(
                valueSectors.every(element => element.textConfig.position === 'middle'),
                'Rendered polar values must be centered inside their bars.'
            );
            const renderedTextColors = valueSectors.map(element => element.getTextContent().style.fill);
            assert.ok(renderedTextColors.includes('#111111'), 'Light bars must render dark value text.');
            assert.ok(renderedTextColors.includes('#eeeeee'), 'Dark bars must render light value text.');
        }
        if (positionedRadialOptions.includes(option) || option === positionedTangential) {
            const expectedPosition = option.series[0].label.position;
            const valueSectors = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'sector' && element.getTextContent()
            );
            assert.equal(valueSectors.length, 3, 'Alternative label positions must retain all Polar values.');
            assert.ok(valueSectors.every(element => element.textConfig.position === expectedPosition),
                'ECharts must retain the selected inner value-label position.');
            if (option !== positionedTangential) {
                for (const sector of valueSectors) {
                    const anchor = sector.calculateTextPosition(null,
                        { position: expectedPosition, distance: 5 }, sector.getBoundingRect());
                    const radius = Math.hypot(anchor.x - sector.shape.cx, anchor.y - sector.shape.cy);
                    const expectedRadius = expectedPosition === 'insideStart'
                        ? sector.shape.r0 + 5 : sector.shape.r - 5;
                    assert.ok(Math.abs(radius - expectedRadius) < 0.001,
                        'ECharts must anchor radial labels at the configured inner bar end.');
                }
            }
            assert.ok(valueSectors.every(element => element.textConfig.rotation === 0),
                'Alternative positions must keep rendered labels horizontal.');
        }
        realChart.dispose();
    }
    const offsetChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
    offsetChart.setOption(offsetTangential);
    const scaleAxis = offsetChart.getModel().getComponent('angleAxis').axis;
    const expectedStart = -scaleAxis.dataToCoord(5) * Math.PI / 180;
    const offsetData = offsetChart.getModel().getSeriesByIndex(0).getData();
    for (let index = 0; index < offsetData.count(); index++) {
        const sector = offsetData.getItemLayout(index);
        assert.ok(Math.abs(sector.startAngle - expectedStart) < 0.000001,
            'Every concentric arc must start at the 5-unit scale tick.');
    }
    offsetChart.dispose();
    const radialChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
    radialChart.setOption(fixedRadial);
    const radiusAxis = radialChart.getModel().getComponent('radiusAxis').axis;
    const expectedRadiusStart = radiusAxis.dataToCoord(-5);
    const radialData = radialChart.getModel().getSeriesByIndex(0).getData();
    for (let index = 0; index < radialData.count(); index++) {
        assert.ok(Math.abs(radialData.getItemLayout(index).r0 - expectedRadiusStart) < 0.000001,
            'Every radial bar must start at its fixed-scale minimum.');
    }
    radialChart.dispose();
}

window.handleMessage({ status: 'error', error: 'Configure valid Polar sources.' });
assert.equal(chartElement.hidden, true);
assert.equal(errorElement.hidden, false);

console.log('Polar Bar layout verified.');
