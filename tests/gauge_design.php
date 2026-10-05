<?php

declare(strict_types=1);

use SymconECharts\EChartsGaugeDesign;

require_once __DIR__ . '/../libs/EChartsGaugeDesign.php';

function assertGaugeDesign(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pointer = EChartsGaugeDesign::ImportPointer(
    '<svg viewBox="10 20 40 80"><path d="M30 20L50 100L10 100Z"/></svg>',
    'custom',
    25.0,
    75.0
);
assertGaugeDesign(
    $pointer['pointerPath'] === 'M30 20L50 100L10 100Z'
        && $pointer['pointerViewBox'] === '10 20 40 80'
        && $pointer['pointerPivotX'] === 20.0
        && $pointer['pointerPivotY'] === 80.0
        && $pointer['pointerShowAnchor'] === true,
    'The shared Gauge pointer import contract changed.'
);

$anchor = EChartsGaugeDesign::ImportAnchor(
    '<svg viewBox="0 0 100 100"><path d="M50 0L100 50L50 100L0 50Z"/></svg>'
);
assertGaugeDesign(
    $anchor['anchorPath'] === 'M50 0L100 50L50 100L0 50Z'
        && $anchor['anchorViewBox'] === '0 0 100 100',
    'The shared Gauge anchor import contract changed.'
);

$background = EChartsGaugeDesign::ImportPlateBackground(
    '<svg viewBox="0 0 200 100"><rect width="200" height="100" fill="#123456"/></svg>',
    'contain',
    125,
    10,
    -5,
    60,
    15.0
);
assertGaugeDesign(
    $background['plateBackgroundEnabled'] === true
        && $background['plateBackgroundFit'] === 'contain'
        && $background['plateBackgroundAspectRatio'] === 2.0
        && str_starts_with($background['plateBackgroundImage'], 'data:image/svg+xml;base64,'),
    'The shared Gauge plate background import contract changed.'
);
assertGaugeDesign(EChartsGaugeDesign::ColorToHex(-1) === '#000000', 'Negative colors must be clamped.');
assertGaugeDesign(EChartsGaugeDesign::ColorToHex(0x123456) === '#123456', 'RGB conversion changed.');
assertGaugeDesign(EChartsGaugeDesign::ColorToHex(0xFFFFFF + 1) === '#FFFFFF', 'Large colors must be clamped.');

$sourceDefaults = EChartsGaugeDesign::SourceDesignDefaults();
$sourceColumns = EChartsGaugeDesign::SourceDesignColumns();
$sourceForm = EChartsGaugeDesign::SourceEditorForm();
assertGaugeDesign(
    ($sourceDefaults['UseIndividualDesign'] ?? null) === false
        && count($sourceColumns) === count($sourceDefaults) * 2
        && ($sourceColumns[0]['name'] ?? null) === 'UseIndividualDesign'
        && ($sourceColumns[0]['save'] ?? null) === true
        && array_is_list($sourceForm),
    'The shared source-row Gauge design form contract changed.'
);
$sourceStyle = EChartsGaugeDesign::StyleFromSource(array_merge($sourceDefaults, [
    'PointerShape'               => 'custom',
    'CustomPointerSVG'           => '<svg viewBox="10 20 40 80"><path d="M30 20L50 100L10 100Z"/></svg>',
    'CustomPointerPivotMode'     => 'custom',
    'CustomPointerPivotXPercent' => 25.0,
    'CustomPointerPivotYPercent' => 75.0,
    'AnchorShape'                => 'custom',
    'CustomAnchorSVG'            => '<svg viewBox="0 0 100 100"><path d="M50 0L100 50L50 100L0 50Z"/></svg>',
    'PlateDesignMode'            => 'custom',
    'PlateBackgroundEnabled'     => true,
    'PlateBackgroundSVG'         => '<svg viewBox="0 0 200 100"><rect width="200" height="100"/></svg>',
    'PlateBackgroundFit'         => 'contain'
]));
$ipsViewSourceStyle = EChartsGaugeDesign::StyleFromSource([
    'IPSViewPointerShape'    => 'line',
    'IPSViewPlateDesignMode' => 'hidden'
], 'IPSView');
assertGaugeDesign(
    ($sourceStyle['pointerPivotX'] ?? null) === 20.0
        && ($sourceStyle['anchorPath'] ?? null) === 'M50 0L100 50L50 100L0 50Z'
        && ($sourceStyle['anchorViewBox'] ?? null) === '0 0 100 100'
        && ($sourceStyle['plateMode'] ?? null) === 'custom'
        && ($sourceStyle['plateBackgroundAspectRatio'] ?? null) === 2.0
        && ($ipsViewSourceStyle['pointerShape'] ?? null) === 'line'
        && ($ipsViewSourceStyle['plateMode'] ?? null) === 'hidden',
    'The shared source-row SVG design could not be materialized.'
);

foreach ([
    static fn (): array => EChartsGaugeDesign::ImportPointer('<svg/>', 'invalid'),
    static fn (): array => EChartsGaugeDesign::ImportAnchor(
        '<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L10 10Z"/></svg>'
    ),
    static fn (): array => EChartsGaugeDesign::ImportPointer(
        '<svg viewBox="0 0 10 10"><path d="M0 0L10 10Z"/></svg>',
        'custom',
        101.0,
        50.0
    ),
    static fn (): array => EChartsGaugeDesign::ImportPlateBackground(
        '<svg viewBox="0 0 10 10"><circle cx="5" cy="5" r="5"/></svg>',
        'invalid'
    )
] as $invalidImport) {
    try {
        $invalidImport();
        throw new RuntimeException('An invalid shared Gauge design was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Shared ECharts Gauge design contracts verified.\n";
