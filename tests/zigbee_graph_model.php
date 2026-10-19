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
