<?php

declare(strict_types=1);

use SymconECharts\EChartsTreePreview;

require_once dirname(__DIR__) . '/libs/EChartsTreePreview.php';

function treePreviewSVG(array $root, array $style = []): DOMXPath
{
    $svg = EChartsTreePreview::Draw(
        '<svg xmlns="http://www.w3.org/2000/svg" width="720" height="360">',
        $root,
        array_replace(['initialDepth' => 12, 'showLabels' => true, 'showValues' => true], $style),
        ['category' => '#123456', 'instance' => '#abcdef', 'variable' => '#654321', 'critical' => '#ff0000'],
        'Schematic tree; limited preview.'
    );
    $document = new DOMDocument();
    if (!$document->loadXML($svg)) {
        throw new RuntimeException('The Tree preview must be well-formed SVG.');
    }
    return new DOMXPath($document);
}

function treePreviewAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$chain = ['id' => 'object-12', 'name' => 'Deep sensor', 'kind' => 'variable', 'children' => []];
for ($depth = 11; $depth >= 0; --$depth) {
    $chain = ['id' => 'object-' . $depth, 'name' => 'Level ' . $depth, 'kind' => 'category', 'children' => [$chain]];
}
$deep = treePreviewSVG($chain);
treePreviewAssert($deep->query('//*[@data-node]')->length === 13, 'The Tree preview must include root plus twelve descendant levels.');
treePreviewAssert($deep->query('//*[@data-node="object-12" and @data-depth="12"]')->length === 1, 'The deepest Tree preview identity must remain visible.');
treePreviewAssert($deep->query('//*[@data-parent and @data-child]')->length === 12, 'A deep chain must have one real edge per descendant.');
$deepRadial = treePreviewSVG($chain, ['treeLayout' => 'radial']);
$lastY = null;
foreach ($deepRadial->query('//*[@data-node]/*[local-name()="text"]') as $label) {
    $y = (float) $label->getAttribute('y');
    treePreviewAssert(
        $lastY === null || abs($y - $lastY) >= 11,
        'Twelve radial preview levels must not stack adjacent text lines on top of each other.'
    );
    $lastY = $y;
}
$collapsed = treePreviewSVG($chain, ['initialDepth' => 0]);
treePreviewAssert($collapsed->query('//*[@data-node]')->length === 1, 'Initial depth zero must show only the root.');
treePreviewAssert($collapsed->query('//*[@data-node="object-0" and @data-collapsed="true"]')->length === 1, 'A closed group must be recognizable.');

$branch = ['id' => 'root', 'name' => 'Root', 'kind' => 'category', 'children' => [
    ['id' => 'left', 'name' => 'Left', 'kind' => 'instance', 'children' => [
        ['id' => 'sensor', 'name' => '<script>&"' . str_repeat('long label ', 20), 'kind' => 'variable', 'displayValue' => '<b>42</b>', 'health' => 'critical', 'children' => []]
    ]],
    ['id' => 'right', 'name' => 'Right', 'kind' => 'variable', 'children' => []]
]];
foreach (['LR', 'RL', 'TB', 'BT', 'radial'] as $orientation) {
    $xpath = treePreviewSVG($branch, ['orientation' => $orientation, 'treeLayout' => $orientation === 'radial' ? 'radial' : 'orthogonal']);
    treePreviewAssert(
        $xpath->query('//*[@data-parent="root" and @data-child="left"]')->length === 1
        && $xpath->query('//*[@data-parent="root" and @data-child="right"]')->length === 1
        && $xpath->query('//*[@data-parent="left" and @data-child="sensor"]')->length === 1
        && $xpath->query('//*[@data-parent="sensor" and @data-child="right"]')->length === 0,
        'The preview must retain real parent-child edges in every layout.'
    );
    treePreviewAssert(
        $xpath->query('//*[@data-node="sensor"]/*[local-name()="circle" and @fill="#ff0000"]')->length === 1,
        'Health colors must override native kind colors.'
    );
    treePreviewAssert(
        $xpath->query('//*[@data-node="left"]/*[local-name()="circle" and @fill="#abcdef"]')->length === 1,
        'Unrated nodes must retain their native kind colors.'
    );
    treePreviewAssert($xpath->query('//*[local-name()="script" or local-name()="b"]')->length === 0, 'Preview labels must never become SVG markup.');
    foreach ($xpath->query('//*[@data-node]/*[local-name()="circle"]') as $circle) {
        treePreviewAssert(
            (float) $circle->getAttribute('cx') >= 20 && (float) $circle->getAttribute('cx') <= 700
            && (float) $circle->getAttribute('cy') >= 75 && (float) $circle->getAttribute('cy') <= 310,
            'All preview nodes must fit the reserved drawing area.'
        );
    }
}
$wide = $branch;
$wide['children'] = array_map(static fn (int $id): array => [
    'id' => 'wide-' . $id, 'name' => 'Node ' . $id, 'kind' => 'variable', 'children' => []
], range(1, 100));
treePreviewAssert(treePreviewSVG($wide)->query('//*[@data-node]')->length === 80, 'The shared Tree preview must retain its eighty-node safety bound.');

if (($argv[1] ?? null) === '--svg') {
    $orientation = in_array($argv[2] ?? '', ['LR', 'RL', 'TB', 'BT', 'radial'], true) ? $argv[2] : 'LR';
    echo treePreviewSVG($chain, ['orientation' => $orientation,
        'treeLayout'                           => $orientation === 'radial' ? 'radial' : 'orthogonal'])->document->saveXML();
} else {
    echo "Recursive Tree preview verified.\n";
}
