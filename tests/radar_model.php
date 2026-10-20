<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsRadar/RadarModel.php';

use SymconECharts\RadarModel;

$axes = [
    ['Key' => 'temperature', 'Label' => 'Temperature', 'Minimum' => -20, 'Maximum' => 40, 'Unit' => '°C'],
    ['Key' => 'humidity', 'Label' => 'Humidity', 'Minimum' => 0, 'Maximum' => 100, 'Unit' => '%'],
    ['Key' => 'co2', 'Label' => 'CO2', 'Minimum' => 0, 'Maximum' => 2000, 'Unit' => 'ppm']
];
$sources = [
    ['Group' => 'Room', 'Axis' => 'co2', 'VariableID' => 1003],
    ['Group' => 'Room', 'Axis' => 'temperature', 'VariableID' => 1001],
    ['Group' => 'Room', 'Axis' => 'humidity', 'VariableID' => 1002]
];
$values = [1001 => ['Value' => -5, 'Timestamp' => 100], 1002 => ['Value' => 0, 'Timestamp' => 101],
    1003        => ['Value' => 2500, 'Timestamp' => 102]];
$model = RadarModel::Build($axes, $sources, $values);
if ($model['data'][0]['values'] !== [-5.0, 0.0, 2500.0] || $model['data'][0]['percentValues'] !== [25.0, 0.0, 100.0]
    || $model['clippedCount'] !== 1 || $model['data'][0]['variableIDs'] !== [1001, 1002, 1003]) {
    throw new RuntimeException('Radar must preserve raw values and IDs, align axes and bound only visual percentages.');
}
foreach ([array_slice($sources, 0, 2), [...$sources, $sources[0]], [...$sources, ['Group' => 'Other', 'Axis' => 'missing', 'VariableID' => 1004]]] as $invalid) {
    try {
        RadarModel::Validate($axes, $invalid);
        throw new RuntimeException('Invalid Radar mapping was accepted.');
    } catch (InvalidArgumentException) {
    }
}
try {
    RadarModel::Build($axes, $sources, [1001 => ['Value' => INF, 'Timestamp' => 100]]);
    throw new RuntimeException('Missing or nonfinite Radar values were accepted.');
} catch (InvalidArgumentException) {
}
$manySources = [];
for ($group = 0; $group < 16; ++$group) {
    foreach ($sources as $source) {
        $manySources[] = [...$source, 'Group' => 'Room ' . $group];
    }
}
$many = RadarModel::Build($axes, $manySources, $values);
if (count($many['data']) !== 16 || $many['clippedCount'] !== 16
    || $many['data'][15]['values'] !== [-5.0, 0.0, 2500.0]) {
    throw new RuntimeException('Sixteen complete groups must retain original values and full clipping counts.');
}
try {
    RadarModel::Validate($axes, [...$manySources, ...array_map(static fn (array $row): array => [...$row, 'Group' => 'Room 16'], $sources)]);
    throw new RuntimeException('A seventeenth Radar group was accepted.');
} catch (InvalidArgumentException) {
}
echo "Radar current-value model verified.\n";
