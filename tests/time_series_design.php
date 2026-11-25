<?php

declare(strict_types=1);

use eCharts\EChartsTimeSeriesDesign;
use eCharts\EChartsTimeSeriesPreview;

require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsUnitAxes.php';
require_once __DIR__ . '/../libs/EChartsTimeSeriesDesign.php';
require_once __DIR__ . '/../EChartsTimeSeries/TimeSeriesPreview.php';

function assertTimeSeriesDesign(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$defaults = EChartsTimeSeriesDesign::SourceDesignDefaults();
$columns = EChartsTimeSeriesDesign::SourceDesignColumns();
$axisColumns = EChartsTimeSeriesDesign::AxisRangeColumns();
$form = EChartsTimeSeriesDesign::SourceEditorForm();
$annotationForm = EChartsTimeSeriesDesign::AnnotationEditorForm();
assertTimeSeriesDesign(
    ($defaults['UseIndividualDesign'] ?? null) === false
        && ($columns[0]['name'] ?? null) === 'UseIndividualDesign'
        && ($columns[0]['visible'] ?? null) === true
        && ($columns[1]['visible'] ?? null) === false
        && array_column($axisColumns, 'name') === ['OwnAxis', 'AxisRangeMode', 'AxisMinimum', 'AxisMaximum']
        && array_column($axisColumns, 'add') === [false, 'auto', 0.0, 100.0]
        && EChartsTimeSeriesDesign::AXIS_RANGE_MODES === ['auto', 'presentation', 'manual']
        && EChartsTimeSeriesDesign::ANNOTATION_TYPES === ['line', 'area']
        && EChartsTimeSeriesDesign::ANNOTATION_LINE_TYPES === ['solid', 'dashed', 'dotted']
        && str_contains(json_encode($form, JSON_THROW_ON_ERROR), 'AxisRangeMode')
        && str_contains(json_encode($annotationForm, JSON_THROW_ON_ERROR), 'OpacityPercent')
        && array_is_list($form),
    'The Time Series source designer or axis-range contract changed.'
);

$style = EChartsTimeSeriesDesign::StyleFromSource(array_merge($defaults, [
    'UseIndividualDesign'       => true,
    'SeriesLineType'            => 'dashed',
    'SeriesLineWidthPercent'    => 150,
    'SeriesSmoothLine'          => true,
    'SeriesPointSymbol'         => 'diamond',
    'SeriesPointSizePercent'    => 125,
    'SeriesAreaOpacityPercent'  => 45,
    'SeriesAreaFillMode'        => 'gradient',
    'SeriesAreaGradientColor'   => 0x123456
]));
assertTimeSeriesDesign(
    $style['lineType'] === 'dashed'
        && $style['lineWidthPercent'] === 150
        && $style['smoothLine'] === true
        && $style['pointSymbol'] === 'diamond'
        && $style['pointSizePercent'] === 125
        && $style['areaOpacityPercent'] === 45
        && $style['areaFillMode'] === 'gradient'
        && $style['areaGradientColor'] === '#123456',
    'The individual Time Series line and gradient design was not normalized.'
);

$svgStyle = EChartsTimeSeriesDesign::StyleFromSource(array_merge($defaults, [
    'UseIndividualDesign'       => true,
    'SeriesAreaFillMode'        => 'svg',
    'SeriesAreaSVG'             => '<svg viewBox="0 0 40 20"><circle cx="10" cy="10" r="4" fill="#AABBCC"/></svg>',
    'SeriesAreaSVGSizePercent'  => 175
]));
assertTimeSeriesDesign(
    str_starts_with($svgStyle['areaPatternImage'] ?? '', 'data:image/svg+xml;base64,')
        && ($svgStyle['areaPatternAspectRatio'] ?? null) === 2.0
        && ($svgStyle['areaSVGSizePercent'] ?? null) === 175,
    'The individual Time Series SVG area pattern was not imported safely.'
);
$svgPreview = EChartsTimeSeriesPreview::CreateSvg([[
    'variableID' => 4711,
    'label'      => 'Humidity',
    'color'      => '#55CBB5',
    'style'      => 'area',
    'design'     => array_merge($svgStyle, [
        'lineType'    => 'dashed',
        'pointSymbol' => 'diamond'
    ])
]], 'SVG pattern', 'dark', [], annotations: [
    [
        'type'             => 'line',
        'seriesIndex'      => 0,
        'label'            => 'Target',
        'value'            => 22.5,
        'color'            => '#E5754F',
        'lineType'         => 'dashed',
        'lineWidthPercent' => 150,
        'opacityPercent'   => 20
    ],
    [
        'type'             => 'area',
        'seriesIndex'      => 0,
        'label'            => 'Comfort',
        'value'            => 40.0,
        'maximum'          => 60.0,
        'color'            => '#55CBB5',
        'lineType'         => 'solid',
        'lineWidthPercent' => 100,
        'opacityPercent'   => 25
    ]
]);
assertTimeSeriesDesign(
    str_contains($svgPreview, '<pattern id="area-pattern-0"')
        && str_contains($svgPreview, 'data:image/svg+xml;base64,')
        && str_contains($svgPreview, 'stroke-dasharray="10 7"')
        && str_contains($svgPreview, 'data-annotation-type="line"')
        && str_contains($svgPreview, 'data-annotation-type="area"')
        && str_contains($svgPreview, 'Target')
        && str_contains($svgPreview, 'Comfort')
        && str_contains($svgPreview, '<path d="M 310'),
    'The Time Series preview does not visualize the individual SVG area pattern and line design.'
);
$axisSeries = [
    ['label'   => 'Temperature', 'color' => '#123456', 'style' => 'line',
        'unit' => '°C', 'axisPosition' => 'left'],
    ['label'   => 'Humidity', 'color' => '#654321', 'style' => 'line',
        'unit' => '%', 'axisPosition' => 'right']
];
$manualAxisPreview = EChartsTimeSeriesPreview::CreateSvg(
    $axisSeries,
    'Manual axes',
    'dark',
    ['axisColor' => '#ABCDEF']
);
assertTimeSeriesDesign(
    preg_match_all('/<line data-preview-axis="(?:left|right)"[^>]*stroke="#ABCDEF"\/>/', $manualAxisPreview) === 2,
    'An explicit Time Series axis color must override both automatic unit colors.'
);
$hiddenAxisPreview = EChartsTimeSeriesPreview::CreateSvg(
    $axisSeries,
    'Hidden axes',
    'dark',
    ['showYAxis' => false]
);
assertTimeSeriesDesign(
    !str_contains($hiddenAxisPreview, 'data-preview-axis='),
    'The Time Series preview must hide every value axis when the Y axis is disabled.'
);
$conflictingAxisPreview = EChartsTimeSeriesPreview::CreateSvg([
    $axisSeries[0],
    [...$axisSeries[0], 'label' => 'Other temperature', 'axisPosition' => 'right']
], 'Invalid draft', 'dark', []);
assertTimeSeriesDesign(
    substr_count($conflictingAxisPreview, 'data-preview-axis=') === 1,
    'Conflicting draft axis sides must leave the Time Series preview available.'
);

$ownAxisSeries = [[...$axisSeries[0], 'axisRange' => ['minimum' => -20.0, 'maximum' => 40.0]],
    [...$axisSeries[0], 'ownAxis' => true, 'color' => '#780507', 'axisPosition' => 'right',
        'axisRange'               => ['minimum' => 30.0, 'maximum' => 70.0]]];
$ownAxisPreview = EChartsTimeSeriesPreview::CreateSvg($ownAxisSeries, 'Two temperatures', 'dark', []);
assertTimeSeriesDesign(
    substr_count($ownAxisPreview, 'data-preview-axis=') === 2
        && str_contains($ownAxisPreview, '>-20 … 40</text>') && str_contains($ownAxisPreview, '>30 … 70</text>')
        && preg_match('/<line data-preview-axis="right"[^>]*stroke="#780507"/', $ownAxisPreview) === 1,
    'Two Celsius sources on independent axes must keep separate sides and source colors in the preview.'
);
$ownAxisSeries[1]['initiallyVisible'] = false;
assertTimeSeriesDesign(
    substr_count(EChartsTimeSeriesPreview::CreateSvg($ownAxisSeries, 'Selection', 'dark', []), 'data-preview-axis=') === 1,
    'Hiding an own-axis series must hide its axis even if a visible series has the same unit.'
);

$eightLeftAxes = array_fill(0, 8, [...$axisSeries[0], 'ownAxis' => true, 'initiallyVisible' => false]);
$eightLeftAxes[7]['initiallyVisible'] = true;
$lastAxisPreview = EChartsTimeSeriesPreview::CreateSvg($eightLeftAxes, 'Selection', 'dark', []);
assertTimeSeriesDesign(
    substr_count($lastAxisPreview, 'data-preview-axis=') === 1
        && str_contains($lastAxisPreview, 'data-preview-axis="left" x1="70"'),
    'When only the last of eight left axes is visible, the preview must place it at the plot edge.'
);

$roof = ['label' => 'East', 'color' => '#345678', 'style' => 'line', 'unit' => 'kW', 'stackGroup' => 1];
$west = [...$roof, 'label' => 'West', 'color' => '#654321'];
$stackPreview = EChartsTimeSeriesPreview::CreateSvg([
    $roof,
    [...$roof, 'label' => 'Hidden', 'initiallyVisible' => false],
    $west
], 'PV', 'dark', ['showValues' => true]);
assertTimeSeriesDesign(
    preg_match('/<path d="(M [^"]+) Z" fill="#345678"/', $stackPreview, $firstBand) === 1
        && preg_match('/<path d="(M [^"]+) Z" fill="#654321"/', $stackPreview, $secondBand) === 1
        && preg_match('/<path d="(M [^"]+)" fill="none" stroke="#345678"/', $stackPreview, $firstEdge) === 1,
    'A positive stack group must fill both visible bands in the source colors.'
);
$firstCoordinates = preg_split('/ [ML] /', substr($firstEdge[1], 2));
assertTimeSeriesDesign(
    str_ends_with($secondBand[1], ' L ' . implode(' L ', array_reverse($firstCoordinates)))
        && !str_contains($stackPreview, '>Hidden</text>'),
    'The second visible band must close along the entire first boundary.'
);
$separateGroups = EChartsTimeSeriesPreview::CreateSvg([$roof, [...$west, 'stackGroup' => 2]], 'PV', 'dark', []);
assertTimeSeriesDesign(
    preg_match('/<path d="[^"]+ L 70 330 Z" fill="#654321"/', $separateGroups) === 1,
    'Independent stacks must start at the common zero baseline.'
);
$unstacked = [...$roof, 'stackGroup' => 0];
$legacy = $unstacked;
unset($legacy['stackGroup']);
assertTimeSeriesDesign(
    EChartsTimeSeriesPreview::CreateSvg([$unstacked], 'PV', 'dark', []) ===
        EChartsTimeSeriesPreview::CreateSvg([$legacy], 'PV', 'dark', []),
    'A zero stack group must preserve the original preview.'
);

foreach ([
    ['SeriesLineType' => 'invalid'],
    ['SeriesPointSizePercent' => 201],
    ['SeriesAreaFillMode'     => 'svg', 'SeriesAreaSVG' => '<svg viewBox="0 0 10 10"><script>alert(1)</script></svg>']
] as $invalidValues) {
    try {
        EChartsTimeSeriesDesign::StyleFromSource(array_merge($defaults, $invalidValues));
        throw new RuntimeException('An invalid individual Time Series design was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Shared ECharts Time Series design contracts verified.\n";
