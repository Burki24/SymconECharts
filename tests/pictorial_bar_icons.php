<?php

declare(strict_types=1);

require_once __DIR__ . '/../EChartsPictorialBar/PictorialBarIcon.php';

use SymconECharts\PictorialBarIcon;

// Synthetic icon data; no installed or licensed icon artwork is distributed.
$fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/pictorial-icons.json'), true, 512, JSON_THROW_ON_ERROR);
$script = 'var brands=' . json_encode($fixture['brands']) . ';add("fab",brands);'
    . 'var light=' . json_encode($fixture['light']) . ';add("fal",light);'
    . 'var kit=' . json_encode($fixture['kit']) . ',prefixes=[null,\'fak\',,\'fa-kit\'];';
if (PictorialBarIcon::ResolveFromScript('cell', $script)['width'] !== 24
    || PictorialBarIcon::ResolveFromScript('mark', $script)['path'] !== 'M0 0L16 16Z'
    || !str_contains(PictorialBarIcon::ResolveFromScript('sensor', $script)['path'], 'M4 4')) {
    throw new RuntimeException('Symcon icon tuples, pack priority and multi-path icons must resolve.');
}
foreach (['', '../cell', 'https://example.invalid', 'cell" onclick="x', 'path://M0 0', 'unknown', 'Transparent'] as $name) {
    try {
        PictorialBarIcon::ResolveFromScript($name, $script);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Unsafe/unavailable icon accepted: ' . $name);
}
foreach (['M0 0<script/>', 'M0 0 L1e999 2', 'javascript:alert(1)'] as $path) {
    try {
        PictorialBarIcon::ResolveFromScript('bad', 'var p={bad:[16,16,[],"0",' . json_encode($path) . ']};add("fal",p);');
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Unsafe icon path accepted.');
}
foreach (['var p={cell:[0,16,[],"0","M0 0Z"]};add("fal",p);',
    'var p={cell:[16,16,[],"0",[12]]};add("fal",p);',
    'var p={cell:[16,16,[],"0",execute()]};add("fal",p);',
    'var p={cell:[16,16,[],"0","M0 0' . str_repeat('L1 1', 5000) . '"]};add("fal",p);',
    str_repeat(' ', 8388609), 'unsupported format'] as $invalid) {
    try {
        PictorialBarIcon::ResolveFromScript('cell', $invalid);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Malformed/oversized icon data accepted.');
}
echo "Symcon icon selection parser verified.\n";
