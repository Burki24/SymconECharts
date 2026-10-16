<?php

declare(strict_types=1);

use SymconECharts\SankeyModel;

require_once dirname(__DIR__) . '/EChartsSankey/SankeyModel.php';

$nodes = [
    ['Key' => 'grid', 'Label' => 'Supply'], ['Key' => 'solar', 'Label' => 'Supply'],
    ['Key' => 'house', 'Label' => 'House'], ['Key' => 'load', 'Label' => 'Load']
];
$sources = [
    ['Source' => 'grid', 'Target' => 'house', 'VariableID' => 10],
    ['Source' => 'solar', 'Target' => 'house', 'VariableID' => 11],
    ['Source' => 'house', 'Target' => 'load', 'VariableID' => 12]
];
$values = [10 => ['Value' => 20.0, 'Timestamp' => 100],
    11        => ['Value' => 10.0, 'Timestamp' => 101], 12 => ['Value' => 0.0, 'Timestamp' => 102]];
$model = SankeyModel::Build($nodes, $sources, $values);
if ($model['positiveCount'] !== 2 || count($model['data']) !== 3
    || $model['data'][0]['name'] !== 'grid' || $model['data'][1]['name'] !== 'solar'
    || $model['data'][0]['label'] !== $model['data'][1]['label']
    || $model['links'][0]['value'] !== 20.0 || $model['links'][1]['timestamp'] !== 101
    || $model['data'][2]['value'] !== 30.0) {
    throw new RuntimeException('Sankey must retain distinct node keys, current edge values and positive topology.');
}
$reused = SankeyModel::Build($nodes, array_replace($sources, [1 => [
    'Source' => 'solar', 'Target' => 'house', 'VariableID' => 10
]]), $values);
if ($reused['links'][1]['value'] !== 20.0) {
    throw new RuntimeException('An explicitly reused source variable must retain its full value on each edge.');
}
$zeros = SankeyModel::Build($nodes, $sources, array_map(
    static fn (array $value): array => ['Value' => 0.0, 'Timestamp' => $value['Timestamp']],
    $values
));
if ($zeros['data'] !== [] || $zeros['links'] !== [] || $zeros['positiveCount'] !== 0) {
    throw new RuntimeException('Zero flows must produce an empty Sankey.');
}
foreach ([
    [[$nodes[0], $nodes[0]], $sources, $values],
    [$nodes, [$sources[0], $sources[0]], $values],
    [$nodes, [['Source' => 'grid', 'Target' => 'grid', 'VariableID' => 10]], $values],
    [$nodes, [['Source' => 'missing', 'Target' => 'house', 'VariableID' => 10]], $values],
    [$nodes, [...$sources, ['Source' => 'load', 'Target' => 'grid', 'VariableID' => 10]], $values],
    [$nodes, $sources, array_replace($values, [10 => ['Value' => -1.0, 'Timestamp' => 100]])],
    [$nodes, $sources, array_replace($values, [10 => ['Value' => INF, 'Timestamp' => 100]])],
    [$nodes, $sources, array_replace($values, [10 => ['Value' => PHP_FLOAT_MAX, 'Timestamp' => 100],
        11                                        => ['Value' => PHP_FLOAT_MAX, 'Timestamp' => 100]])],
    [$nodes, $sources, []],
    [array_fill(0, 101, $nodes[0]), $sources, $values],
    [$nodes, array_fill(0, 201, $sources[0]), $values]
] as [$invalidNodes, $invalidSources, $invalidValues]) {
    try {
        SankeyModel::Build($invalidNodes, $invalidSources, $invalidValues);
        throw new RuntimeException('Invalid Sankey configuration or values were accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Sankey model verified.\n";
