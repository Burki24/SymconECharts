<?php

declare(strict_types=1);

use SymconECharts\PictorialBarSvg;

require_once __DIR__ . '/../EChartsPictorialBar/PictorialBarSvg.php';

$example = PictorialBarSvg::Import(file_get_contents(__DIR__ . '/../EChartsPictorialBar/examples/battery.svg'));
if ($example['width'] !== 100.0 || $example['height'] !== 50.0 || $example['minimumX'] !== 0.0 || $example['minimumY'] !== 0.0
    || $example['path'] !== 'M0 0 H90 V15 H100 V35 H90 V50 H0 Z') {
    throw new RuntimeException('The documented battery example must remain importable with its intended geometry.');
}

$file = file_get_contents(__DIR__ . '/fixtures/pictorial-symbol-custom.svg');
$expected = ['path' => 'M-12 -4 l16 0 l0 -2 l8 6 l-8 6 l0 -2 l-16 0 z',
    'width'         => 24.0, 'height' => 12.0, 'minimumX' => -12.0, 'minimumY' => -6.0];
foreach ([$file, base64_encode($file), 'data:image/svg+xml;charset=utf-8;base64,' . base64_encode($file)] as $input) {
    if (PictorialBarSvg::Import($input) !== $expected) {
        throw new RuntimeException('SVG upload formats must produce the same path and nonzero viewBox.');
    }
}
$multiple = PictorialBarSvg::Import('<svg viewBox="0 0 24 12"><path d="m0 0 20 0 0 12 -20 0z"/><path d="m22 4 2 0 0 4 -2 0z"/></svg>');
if ($multiple['path'] !== 'M0 0 l20 0 l0 12 l-20 0 z M22 4 l2 0 l0 4 l-2 0 z') {
    throw new RuntimeException('Independent relative path origins must not drift when combined.');
}
$commands = PictorialBarSvg::Import('<svg viewBox="0 0 24 24"><path d="M0,0H2V3C1 2 3 4 5 6S1 2 3 4Q1 2 3 4T5 6A2 3 45 0110 10z"/></svg>');
if (!str_contains($commands['path'], 'A2 3 45 0 1 10 10 z')) {
    throw new RuntimeException('All SVG path commands and adjacent arc flags must normalize.');
}
foreach ([
    '', '../symbol.svg', 'https://example.invalid/symbol.svg', base64_encode('not svg'),
    '<svg viewBox="0 0 24 12"><path d="M0 0L1 1Z">',
    '<svg viewBox="0 0 24 12"><script>alert(1)</script><path d="M0 0Z"/></svg>',
    '<svg viewBox="0 0 24 12"><path onload="alert(1)" d="M0 0Z"/></svg>',
    '<svg viewBox="0 0 24 12"><image href="https://example.invalid/a.svg"/></svg>',
    '<svg viewBox="0 0 24 12"><g transform="rotate(30)"><path d="M0 0Z"/></g></svg>',
    '<svg viewBox="0 0 24 12"><rect width="12" height="12"/></svg>',
    '<svg viewBox="0 0 24 12"><text>name</text><path d="M0 0Z"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0 L1"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0Z 1 2"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0A2 3 0 2 1 2 3Z"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0A-2 3 0 0 1 2 3Z"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0L1e999 1Z"/></svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0L1000001 1Z"/></svg>',
    '<svg viewBox="0 0 24 0.00000001"><path d="M0 0Z"/></svg>',
    '<svg viewBox="0 0 0 24"><path d="M0 0Z"/></svg>',
    '<svg xmlns="https://example.invalid" viewBox="0 0 24 12"><path d="M0 0Z"/></svg>',
    '<svg viewBox="0 0 24 12">' . str_repeat('<path d="M0 0Z"/>', 33) . '</svg>',
    '<svg viewBox="0 0 24 12"><path d="M0 0' . str_repeat('L1 1 ', 4000) . 'Z"/></svg>',
    '<svg viewBox="0 0 24 12"><!--' . str_repeat('x', 131073) . '--><path d="M0 0Z"/></svg>',
    '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY source SYSTEM "file:///unavailable">]><svg viewBox="0 0 24 12"><path d="M0 0Z"/></svg>'
] as $input) {
    try {
        PictorialBarSvg::Import($input);
    } catch (InvalidArgumentException $exception) {
        if ($exception->getMessage() !== 'The custom SVG shape is invalid or unsupported. Use a path-only SVG with a viewBox.') {
            throw new RuntimeException('Untrusted upload details must not leak in errors.');
        }
        continue;
    }
    throw new RuntimeException('Invalid or oversized SVG shape accepted.');
}
echo "PictorialBar SVG upload, path normalization and rejection limits verified.\n";
