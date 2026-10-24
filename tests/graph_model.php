<?php

declare(strict_types=1);

use SymconECharts\GraphModel;

require_once dirname(__DIR__) . '/libs/GraphModel.php';

function assertGraph(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$objects = [
    101 => ['ObjectType' => 0, 'ObjectName' => 'Room'],
    102 => ['ObjectType' => 2, 'ObjectName' => 'Battery'],
    103 => ['ObjectType' => 2, 'ObjectName' => 'Battery']
];
$readObject = static fn (int $id): array => $objects[$id];
$readValue = static fn (int $id): string => $id === 102 ? '40 %' : '30 %';
$nodes = [['ObjectID' => 101, 'Label' => ''], ['ObjectID' => 102, 'Label' => 'Sensor A'], ['ObjectID' => 103, 'Label' => '']];
$edges = [['SourceObjectID' => 101, 'TargetObjectID' => 102], ['SourceObjectID' => 101, 'TargetObjectID' => 103]];
$graph = GraphModel::Build($nodes, $edges, $readObject, $readValue);
GraphModel::ValidateRows($nodes, $edges);
assertGraph(count($graph['nodes']) === 3 && count($graph['links']) === 2, 'Graph model size is wrong.');
assertGraph($graph['nodes'][1]['id'] === 'object-102' && $graph['nodes'][2]['id'] === 'object-103', 'Object IDs must distinguish equal names.');
assertGraph($graph['nodes'][1]['name'] === 'Sensor A' && $graph['nodes'][2]['name'] === 'Battery', 'Explicit labels must override Symcon names.');
assertGraph($graph['nodes'][1]['displayValue'] === '40 %', 'Variable value is missing.');
assertGraph($graph['links'][0]['source'] === 'object-101' && $graph['links'][0]['target'] === 'object-102', 'Configured edges changed.');

$reciprocalEdges = [...$edges, ['SourceObjectID' => 102, 'TargetObjectID' => 101]];
$directed = GraphModel::Build($nodes, $reciprocalEdges, $readObject, $readValue, true);
assertGraph($directed['directed'] === true && count($directed['links']) === 3, 'Directed Graph must preserve both directions.');
assertGraph($directed['links'][0]['reciprocal'] && $directed['links'][2]['reciprocal']
    && !$directed['links'][1]['reciprocal'], 'Only actual reciprocal pairs need separated curves.');
assertGraph($directed['links'][2]['source'] === 'object-102'
    && $directed['links'][2]['target'] === 'object-101', 'Direction must follow configured endpoints, not node order.');
assertGraph($directed['nodes'] === $graph['nodes'], 'Direction must not change node identities, labels or values.');
foreach ([
    [...$reciprocalEdges, $edges[0]],
    [['SourceObjectID' => 101, 'TargetObjectID' => 101]],
    [['SourceObjectID' => 101, 'TargetObjectID' => 999]]
] as $badEdges) {
    try {
        GraphModel::ValidateRows($nodes, $badEdges, true);
        throw new RuntimeException('Directed Graph accepted a duplicate, self edge or unknown endpoint.');
    } catch (InvalidArgumentException) {
    }
}

foreach ([
    [$nodes, [['SourceObjectID' => 101, 'TargetObjectID' => 999]], 'unknown endpoint'],
    [$nodes, [['SourceObjectID' => 101, 'TargetObjectID' => 101]], 'self edge'],
    [$nodes, [...$edges, ['SourceObjectID' => 102, 'TargetObjectID' => 101]], 'duplicate edge'],
    [[...$nodes, ['ObjectID' => 102]], $edges, 'duplicate node'],
    [array_slice($nodes, 0, 1), $edges, 'too few nodes']
] as [$badNodes, $badEdges, $reason]) {
    try {
        GraphModel::ValidateRows($badNodes, $badEdges);
        throw new RuntimeException('Graph configuration accepted ' . $reason . '.');
    } catch (InvalidArgumentException) {
    }
    try {
        GraphModel::Build($badNodes, $badEdges, $readObject, $readValue);
        throw new RuntimeException('Graph accepted ' . $reason . '.');
    } catch (InvalidArgumentException) {
    }
}

try {
    GraphModel::Build($nodes, $edges, static fn (int $id): array => throw new RuntimeException('Read failed'), $readValue);
    throw new RuntimeException('Graph accepted a failed object read.');
} catch (RuntimeException $exception) {
    assertGraph($exception->getMessage() === 'Read failed', 'A read failure must remain distinct from invalid configuration.');
}

$largeNodes = [];
$largeEdges = [];
for ($index = 0; $index < 300; ++$index) {
    $largeNodes[] = ['ObjectID' => 1000 + $index, 'Label' => 'Same name'];
}
for ($from = 0; count($largeEdges) < 1000; ++$from) {
    for ($to = $from + 1; $to < 300 && count($largeEdges) < 1000; ++$to) {
        $largeEdges[] = ['SourceObjectID' => 1000 + $from, 'TargetObjectID' => 1000 + $to];
    }
}
$start = hrtime(true);
$large = GraphModel::Build(
    $largeNodes,
    $largeEdges,
    static fn (int $id): array => ['ObjectType' => 2, 'ObjectName' => 'Same name'],
    static fn (int $id): string => '50 %'
);
assertGraph(count($large['nodes']) === 300 && count($large['links']) === 1000, 'All 300 nodes and 1000 edges must survive.');
assertGraph(count(array_unique(array_column($large['nodes'], 'id'))) === 300, 'Equal names must not merge nodes.');
$largeDirectedEdges = [...array_slice($largeEdges, 0, 999), ['SourceObjectID' => 1001, 'TargetObjectID' => 1000]];
$largeDirected = GraphModel::Build(
    $largeNodes,
    $largeDirectedEdges,
    static fn (int $id): array => ['ObjectType' => 2, 'ObjectName' => 'Same name'],
    $readValue,
    true
);
assertGraph(count($largeDirected['nodes']) === 300 && count($largeDirected['links']) === 1000
    && $largeDirected['links'][999]['reciprocal'], 'Directed maximum must preserve every node and reciprocal edge.');
foreach ([
    [[...$largeNodes, ['ObjectID' => 1300]], $largeDirectedEdges],
    [$largeNodes, [...$largeDirectedEdges, ['SourceObjectID' => 1004, 'TargetObjectID' => 1005]]]
] as [$badNodes, $badEdges]) {
    try {
        GraphModel::ValidateRows($badNodes, $badEdges, true);
        throw new RuntimeException('Directed mode bypassed the existing N+1 protection.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([
    [[...$largeNodes, ['ObjectID' => 1300]], $largeEdges],
    [$largeNodes, [...$largeEdges, ['SourceObjectID' => 1004, 'TargetObjectID' => 1005]]],
    [$largeNodes, [...array_slice($largeEdges, 0, 999), ['SourceObjectID' => 1001, 'TargetObjectID' => 1000]]]
] as [$badNodes, $badEdges]) {
    try {
        GraphModel::ValidateRows($badNodes, $badEdges);
        throw new RuntimeException('Over-budget or reversed duplicate Graph accepted.');
    } catch (InvalidArgumentException) {
    }
}
printf("Graph model verified (300/1000 projection %.2f ms).\n", (hrtime(true) - $start) / 1e6);
