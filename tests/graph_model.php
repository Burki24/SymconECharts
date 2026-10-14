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

echo "Graph model verified.\n";
