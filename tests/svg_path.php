<?php

declare(strict_types=1);

use SymconECharts\EChartsSvgPath;

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
        && str_contains($ornatePointer['path'], 'M46 397 C45 350')
        && str_contains($ornatePointer['path'], 'M48.5 57 C48.8 40')
        && !isset($ornatePointer['pivotX'], $ornatePointer['pivotY']),
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

foreach ([
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
