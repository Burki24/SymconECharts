<?php

declare(strict_types=1);

use eCharts\EChartsSvgPath;

require_once dirname(__DIR__) . '/libs/EChartsSvgPath.php';

function assertSvgPath(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-10 0 20 100">'
    . '<g><path d="M 0 0 L 6 100 L -6 100 Z"/></g></svg>';
$expected = [
    'path'    => 'M 0 0 L 6 100 L -6 100 Z',
    'viewBox' => '-10 0 20 100'
];

assertSvgPath(EChartsSvgPath::Import($svg) === $expected, 'Raw SVG import changed.');
assertSvgPath(EChartsSvgPath::Import(base64_encode($svg)) === $expected, 'Base64 SVG import changed.');
assertSvgPath(
    EChartsSvgPath::Import('data:image/svg+xml;charset=utf-8;base64,' . base64_encode($svg)) === $expected,
    'SVG data-URI import changed.'
);

$ornatePointerSvg = file_get_contents(__DIR__ . '/fixtures/gauge-pointer-ornate.svg');
assertSvgPath(is_string($ornatePointerSvg), 'Ornate Gauge pointer fixture cannot be read.');
$ornatePointer = EChartsSvgPath::Import($ornatePointerSvg);
assertSvgPath(
    $ornatePointer['viewBox'] === '22 0 56 397'
        && str_contains($ornatePointer['path'], 'M50 397 A27 27')
        && str_contains($ornatePointer['path'], 'M48.5 57 C48.8 40')
        && ($ornatePointer['pivotX'] ?? null) === 50.0
        && ($ornatePointer['pivotY'] ?? null) === 370.0,
    'Ornate Gauge pointer fixture changed or cannot be imported.'
);

$pointerWithPivot = EChartsSvgPath::Import(
    '<svg viewBox="0 0 10 20" data-echarts-pivot="5 18"><path d="M5 0L10 18L5 20L0 18Z"/></svg>'
);
assertSvgPath(
    ($pointerWithPivot['pivotX'] ?? null) === 5.0 && ($pointerWithPivot['pivotY'] ?? null) === 18.0,
    'A valid optional Gauge pointer pivot must be imported.'
);

$multiple = EChartsSvgPath::Import(
    '<svg viewBox="0,0,10,20"><path d="M0 0L10 20Z"/><path d="M2 2L8 18Z"/></svg>'
);
assertSvgPath(
    $multiple['path'] === 'M0 0L10 20Z M2 2L8 18Z' && $multiple['viewBox'] === '0 0 10 20',
    'Multiple SVG paths must be combined without forwarding markup.'
);

$multipart = '<svg viewBox="0 0 100 100" data-echarts-pivot="8 9">'
    . '<path d="M10 10L20 20"/><!-- unused <path d="M90 90L99 99"/> -->'
    . '<g><path d="m5 5 3 3 -2 0"/></g></svg>';
$geometry = EChartsSvgPath::Import($multipart);
// Ignore insignificant formatting. After the second independent move the two pairs still mean relative lines.
$tokens = trim(preg_replace('/\s+/', ' ', preg_replace('/([MLl])/', ' $1 ', $geometry['path'])));
assertSvgPath(
    in_array($tokens, ['M 10 10 L 20 20 M 5 5 l 3 3 -2 0', 'M 10 10 L 20 20 M 5 5 l 3 3 l -2 0'], true)
        && $geometry['pivotX'] === 8.0 && $geometry['pivotY'] === 9.0,
    'Each real path must start at its own origin, preserving its implicit relative lines and the saved pivot.'
);
$connected = EChartsSvgPath::Import('<svg viewBox="0 0 100 100"><path d="M10 10L20 20m5 5l3 3"/></svg>');
assertSvgPath($connected['path'] === 'M10 10L20 20m5 5l3 3', 'A relative move inside one path must still start at the preceding point.');

$prolog = "\xEF\xBB\xBF" . '<?xml version="1.0" encoding="UTF-8"?>' . "\n<!-- Export note: removed <!DOCTYPE svg> -->\n<!-- Second note -->\n";
$export = $prolog . $multipart;
foreach ([$export, base64_encode($export), 'data:image/svg+xml;base64,' . base64_encode($export)] as $input) {
    assertSvgPath(EChartsSvgPath::Import($input) === $geometry, 'An exporter preamble must preserve all symbol geometry and pivot data.');
}

$largeGauge = EChartsSvgPath::Import('<svg viewBox="0 0 2000000 2000000"><path d="M1500000 0V2000000"/></svg>');
assertSvgPath($largeGauge['path'] === 'M1500000 0V2000000', 'Gauge coordinates above the PictorialBar limit must remain usable.');
$longPath = 'M0 0' . str_repeat('H1', 32765) . 'Z';
$gaugeDocument = '<svg viewBox="0 0 10 10"><path d="' . $longPath . '"/></svg>';
assertSvgPath(EChartsSvgPath::Import($gaugeDocument)['path'] === $longPath, 'A valid compact 65535-byte Gauge path must retain its original representation.');
$firstPath = 'M0 0' . str_repeat('H1', 32760) . 'H9';
$fullMultipart = '<svg viewBox="0 0 10 10"><path d="' . $firstPath . '"/><path d="m1 2-3-4"/></svg>';
assertSvgPath(EChartsSvgPath::Import($fullMultipart)['path'] === $firstPath . 'M1 2l-3-4', 'Correcting an independent move must also fit at the combined path limit.');
$limitSvg = '<svg viewBox="0 0 10 10"><path d="M0 0L1 1"/></svg>';
$atLimit = "\xEF\xBB\xBF<!--" . str_repeat('x', 131072 - strlen($limitSvg) - 10) . '-->' . $limitSvg;
assertSvgPath(EChartsSvgPath::Import(base64_encode($atLimit))['path'] === 'M0 0L1 1', 'A 128 KiB path file must remain importable.');

foreach ([
    'short-command'  => '<svg viewBox="0 0 10 10"><path d="M0 0L"/></svg>',
    'short-cubic'    => '<svg viewBox="0 0 10 10"><path d="M0 0C1 2 3 4 5"/></svg>',
    'path-plus-one'  => str_replace('H1Z', 'H10Z', $gaugeDocument),
    'malformed-xml'  => '<svg viewBox="0 0 10 10"><g><path d="M0 0"/></svg>',
    'unbound-prefix' => '<svg viewBox="0 0 10 10"><path d="M0 0" xlink:href="#other"/></svg>',
    'preamble-size'  => base64_encode(str_replace('-->', 'x-->', $atLimit)),
    'preamble-dtd'   => $prolog . '<!DOCTYPE svg>' . $svg,
    'preamble-pi'    => $prolog . '<?drawing-editor mode="preview"?>' . $svg,
    'empty'          => '',
    'viewBox'        => '<svg><path d="M0 0L1 1Z"/></svg>',
    'script'         => '<svg viewBox="0 0 10 10"><script>alert(1)</script><path d="M0 0L1 1Z"/></svg>',
    'reference'      => '<svg viewBox="0 0 10 10"><image href="https://example.invalid/a.png"/></svg>',
    'transformation' => '<svg viewBox="0 0 10 10"><path transform="rotate(90)" d="M0 0L1 1Z"/></svg>',
    'event'          => '<svg viewBox="0 0 10 10"><path onload="alert(1)" d="M0 0L1 1Z"/></svg>',
    'path-data'      => '<svg viewBox="0 0 10 10"><path d="javascript:alert(1)"/></svg>',
    'pivot-format'   => '<svg viewBox="0 0 10 10" data-echarts-pivot="center"><path d="M0 0L1 1Z"/></svg>',
    'pivot-outside'  => '<svg viewBox="0 0 10 10" data-echarts-pivot="5 11"><path d="M0 0L1 1Z"/></svg>',
    'viewBox-size'   => '<svg viewBox="0 0 0 10"><path d="M0 0L1 1Z"/></svg>',
    'path-size'      => '<svg viewBox="0 0 10 10"><path d="M0 0 ' . str_repeat('L1 1 ', 14000) . 'Z"/></svg>',
    'file-size'      => '<svg viewBox="0 0 10 10"><!--' . str_repeat('x', 131073)
        . '--><path d="M0 0L1 1Z"/></svg>'
] as $case => $invalidSvg) {
    try {
        EChartsSvgPath::Import($invalidSvg);
        throw new RuntimeException('Invalid SVG input was accepted: ' . $case);
    } catch (InvalidArgumentException) {
    }
}

echo "ECharts SVG path import verified.\n";
