<?php

declare(strict_types=1);

use SymconECharts\EChartsTimeSeriesDesign;
use SymconECharts\EChartsTimeSeriesPreview;

require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
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
        && array_column($axisColumns, 'name') === ['AxisRangeMode', 'AxisMinimum', 'AxisMaximum']
        && array_column($axisColumns, 'add') === ['auto', 0.0, 100.0]
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
