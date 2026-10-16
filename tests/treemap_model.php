<?php

declare(strict_types=1);

use SymconECharts\TreemapModel;

require_once dirname(__DIR__) . '/libs/TreemapModel.php';

$sources = [
    ['VariableID' => 10, 'Group' => 'House', 'Subgroup' => 'Power', 'Label' => ''],
    ['VariableID' => 11, 'Group' => 'House', 'Subgroup' => 'Power', 'Label' => 'Light'],
    ['VariableID' => 12, 'Group' => 'Garden', 'Subgroup' => '', 'Label' => '']
];
$values = [
    10 => ['Value' => 20.0, 'Timestamp' => 100, 'Name' => 'Light'],
    11 => ['Value' => 10.0, 'Timestamp' => 101, 'Name' => 'Other'],
    12 => ['Value' => 0.0, 'Timestamp' => 102, 'Name' => 'Pump']
];
$model = TreemapModel::Build($sources, $values);
if ($model['positiveCount'] !== 2 || $model['total'] !== 30.0
    || $model['data'][0]['name'] !== 'House'
    || $model['data'][0]['value'] !== 30.0
    || $model['data'][0]['children'][0]['value'] !== 30.0
    || $model['data'][0]['children'][0]['children'][0]['id'] !== 'variable-10'
    || $model['data'][0]['children'][0]['children'][1]['id'] !== 'variable-11'
    || $model['data'][1]['children'][0]['id'] !== 'variable-12') {
    throw new RuntimeException('Treemap grouping, sums or variable identities are incorrect.');
}
$mixed = TreemapModel::Build([
    ['VariableID' => 12, 'Group' => 'House', 'Subgroup' => '', 'Label' => 'Pump'],
    ...array_slice($sources, 0, 2)
], $values);
if ($mixed['data'][0]['children'][0]['id'] !== 'variable-12'
    || $mixed['data'][0]['children'][1]['name'] !== 'Power') {
    throw new RuntimeException('Treemap must retain configured order when direct leaves and subgroups are mixed.');
}
$zeros = TreemapModel::Build([$sources[2]], $values);
if ($zeros['positiveCount'] !== 0 || $zeros['total'] !== 0.0) {
    throw new RuntimeException('Zero-valued Treemap data must remain valid and visibly empty.');
}
foreach ([
    [[$sources[0], $sources[0]], $values],
    [$sources, array_replace($values, [10 => ['Value' => -1.0, 'Timestamp' => 100, 'Name' => 'Light']])],
    [$sources, array_replace($values, [10 => ['Value' => INF, 'Timestamp' => 100, 'Name' => 'Light']])],
    [array_fill(0, 101, $sources[0]), $values]
] as [$invalidSources, $invalidValues]) {
    try {
        TreemapModel::Build($invalidSources, $invalidValues);
        throw new RuntimeException('Invalid Treemap data was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Treemap model verified.\n";
