<?php

declare(strict_types=1);

use SymconECharts\ZigbeeGraphModel;

require_once dirname(__DIR__) . '/libs/ZigbeeGraphModel.php';

function assertZigbeeGraph(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$a = '0x0000000000000001';
$b = '0x0000000000000002';
$c = '0x0000000000000003';
$topology = [
    'captured_at'     => 1800000000,
    'routes_included' => false,
    'nodes'           => [
        ['ieeeAddr' => $a, 'friendlyName' => 'Bridge', 'type' => 'Coordinator'],
        ['ieeeAddr' => $b, 'friendlyName' => 'Sensor', 'type' => 'Router'],
        ['ieeeAddr' => $c, 'friendlyName' => 'Sensor', 'type' => 'EndDevice']
    ],
    'links' => [
        ['source' => ['ieeeAddr' => $a], 'target' => ['ieeeAddr' => $b], 'lqi' => 70],
        ['sourceIeeeAddr' => $a, 'targetIeeeAddr' => $b, 'lqi' => 90],
        ['source' => ['ieeeAddr' => $b], 'target' => ['ieeeAddr' => $a], 'lqi' => 80],
        ['source' => ['ieeeAddr' => $b], 'target' => ['ieeeAddr' => $c], 'lqi' => 30]
    ]
];

$model = ZigbeeGraphModel::Build($topology, 50, 2);
assertZigbeeGraph(count($model['nodes']) === 3, 'All three devices must be visible, including equal names.');
assertZigbeeGraph(count($model['links']) === 2, 'The weak link must be filtered and duplicate direction deduplicated.');
assertZigbeeGraph($model['links'][0]['lqi'] === 90, 'The strongest duplicate must survive.');
assertZigbeeGraph($model['links'][0]['source'] !== $model['links'][1]['source'], 'Reverse directions must remain distinct.');
assertZigbeeGraph($model['totalLinks'] === 4 && $model['filteredLinks'] === 2, 'Link counts must describe the raw and filtered sets.');
assertZigbeeGraph($model['capturedAt'] === 1800000000 && !$model['routesIncluded'], 'Snapshot metadata changed.');

$capped = ZigbeeGraphModel::Build($topology, 0, 1);
assertZigbeeGraph(count($capped['links']) === 1 && $capped['links'][0]['lqi'] === 90, 'The cap must retain the strongest link.');

$weakest = ZigbeeGraphModel::Build($topology, 0, 1, 'weakest');
assertZigbeeGraph(array_column($weakest['links'], 'lqi') === [30], 'Weakest-first must apply before the display cap.');
$range = ZigbeeGraphModel::Build($topology, 30, 2, 'range', 80);
assertZigbeeGraph(array_column($range['links'], 'lqi') === [80, 30], 'Range endpoints must be inclusive, with strongest eligible links first.');
assertZigbeeGraph($range['filteredLinks'] === 2, 'A lower duplicate must not reappear when the strongest duplicate is outside the range.');
assertZigbeeGraph(ZigbeeGraphModel::Build($topology, 80, 2, 'range', 80)['links'][0]['lqi'] === 80, 'An exact LQI range must be supported.');
$empty = ZigbeeGraphModel::Build($topology, 91, 2, 'range', 255);
assertZigbeeGraph($empty['nodes'] === $model['nodes'] && $empty['links'] === [] && $empty['filteredLinks'] === 0, 'Empty edge selections must retain all device identities.');
assertZigbeeGraph($empty['capturedAt'] === $model['capturedAt'] && $empty['totalLinks'] === 4, 'Selection must retain export metadata.');
assertZigbeeGraph($range['linkSelection'] === 'range' && $range['minimumLQI'] === 30 && $range['maximumLQI'] === 80, 'Selection metadata must describe effective bounds.');
assertZigbeeGraph(ZigbeeGraphModel::Build($topology, 50, 2, 'weakest', -1)['maximumLQI'] === 255, 'An inactive upper bound must not affect strongest/weakest modes.');

$ties = $topology;
$ties['links'] = array_map(static fn (array $link): array => [...$link, 'lqi' => 128], $topology['links']);
foreach (['strongest', 'weakest', 'range'] as $selection) {
    $ordered = ZigbeeGraphModel::Build($ties, 0, 2000, $selection, 255);
    $reversed = ZigbeeGraphModel::Build([...$ties, 'links' => array_reverse($ties['links'])], 0, 2000, $selection, 255);
    assertZigbeeGraph($ordered['links'] === $reversed['links'], 'Equal-LQI ordering must be deterministic and independent of export order.');
}
$zero = [...$topology, 'links' => [
    ['sourceIeeeAddr' => $a, 'targetIeeeAddr' => $b, 'lqi' => 0],
    ['sourceIeeeAddr' => $b, 'targetIeeeAddr' => $c, 'lqi' => 255],
    ['sourceIeeeAddr' => $c, 'targetIeeeAddr' => $a, 'lqi' => NAN],
    ['sourceIeeeAddr' => $c, 'targetIeeeAddr' => $b, 'lqi' => INF]
]];
assertZigbeeGraph(array_column(ZigbeeGraphModel::Build($zero, 0, 2000, 'weakest')['links'], 'lqi') === [0, 255], 'Finite endpoints, including LQI zero, must remain valid.');
foreach ([['unknown', 0, 255], ['range', 81, 80], ['range', 0, 256], ['range', 0, -1]] as [$selection, $minimum, $maximum]) {
    try {
        ZigbeeGraphModel::Build($topology, $minimum, 300, $selection, $maximum);
        throw new RuntimeException('Invalid connection selection was accepted.');
    } catch (InvalidArgumentException) {
    }
}

// Manual Graph growth must never change the separate Z2M protections.
$atBudget = $topology;
$atBudget['nodes'] = array_fill(0, 500, $topology['nodes'][0]);
$atBudget['links'] = array_fill(0, 10000, $topology['links'][0]);
assertZigbeeGraph(
    count(ZigbeeGraphModel::Build($atBudget, 0, 2000)['nodes']) === 1,
    'Raw Z2M budget boundaries remain accepted with normal identity deduplication.'
);
foreach ([
    [[], 0, 300], [$topology, -1, 300], [$topology, 256, 300], [$topology, 0, 0], [$topology, 0, 2001],
    [[...$atBudget, 'nodes' => [...$atBudget['nodes'], $topology['nodes'][0]]], 0, 300],
    [[...$atBudget, 'links' => [...$atBudget['links'], $topology['links'][0]]], 0, 300]
] as [$bad, $minimum, $limit]) {
    try {
        ZigbeeGraphModel::Build($bad, $minimum, $limit);
        throw new RuntimeException('An invalid map or filter was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Zigbee graph model verified.\n";
