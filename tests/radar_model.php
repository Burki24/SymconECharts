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
$partialSources = array_values(array_filter($sources, static fn (array $source): bool => $source['Axis'] !== 'humidity'));
$partial = RadarModel::Build($axes, $partialSources, $values, null, [], true);
if ($partial['data'][0]['values'] !== [-5.0, null, 2500.0]
    || $partial['data'][0]['percentValues'] !== [25.0, null, 100.0]
    || $partial['data'][0]['variableIDs'] !== [1001, null, 1003]
    || $partial['data'][0]['timestamps'] !== [100, null, 102]
    || $partial['data'][0]['missingAxes'] !== ['humidity']
    || $partial['data'][0]['segments'] !== [[2, 0]] || $partial['incompleteGroupCount'] !== 1) {
    throw new RuntimeException('Opt-in Radar groups must retain aligned nulls and only join adjacent present axes, including wrap-around.');
}
$single = RadarModel::Build($axes, [$sources[1]], $values, null, [], true);
if ($single['data'][0]['segments'] !== [] || $single['data'][0]['values'] !== [-5.0, null, null]) {
    throw new RuntimeException('A single measured axis must remain an isolated point, never a filled or closed shape.');
}
try {
    RadarModel::Build($axes, $partialSources, $values);
    throw new RuntimeException('Strict Radar default accepted an incomplete group.');
} catch (InvalidArgumentException) {
}
try {
    RadarModel::Build($axes, $partialSources, [1001 => ['Value' => -5, 'Timestamp' => 100]], null, [], true);
    throw new RuntimeException('Opt-in Radar must not disguise an invalid configured source as an unassigned axis.');
} catch (InvalidArgumentException) {
}
$inverseAxes = $axes;
$inverseAxes[0]['Inverse'] = true;
$inverseAxes[2]['Inverse'] = true;
$inverse = RadarModel::Build($inverseAxes, $sources, $values);
if ($inverse['data'][0]['percentValues'] !== [75.0, 0.0, 0.0]
    || $inverse['data'][0]['values'] !== $model['data'][0]['values']
    || $inverse['data'][0]['variableIDs'] !== $model['data'][0]['variableIDs']
    || $inverse['data'][0]['timestamps'] !== $model['data'][0]['timestamps']
    || $inverse['data'][0]['id'] !== $model['data'][0]['id'] || $inverse['clippedCount'] !== 1) {
    throw new RuntimeException('Inverse Radar axes must reverse only the bounded radius, retaining raw values, identities, timestamps and clipping.');
}
$explicitNormal = array_map(static fn (array $axis): array => [...$axis, 'Inverse' => false], $axes);
if (RadarModel::Build($explicitNormal, $sources, $values) !== $model) {
    throw new RuntimeException('Legacy axes and explicit false must produce identical Radar models.');
}
foreach ([[-30, 100.0, 1], [-20, 100.0, 0], [10, 50.0, 0], [40, 0.0, 0], [50, 0.0, 1]] as [$value, $radius, $clipped]) {
    $case = $values;
    $case[1001]['Value'] = $value;
    $case[1003]['Value'] = 1000;
    $actual = RadarModel::Build($inverseAxes, $sources, $case);
    if ($actual['data'][0]['percentValues'][0] !== $radius || $actual['clippedCount'] !== $clipped) {
        throw new RuntimeException('Inverse Radar must place minimum outside, maximum inside and clip after preserving raw values.');
    }
}
foreach ([null, 0, 1, 'true', 'false', [], new stdClass()] as $invalid) {
    $badAxes = $axes;
    $badAxes[2]['Inverse'] = $invalid;
    try {
        RadarModel::Validate($badAxes, $sources);
        throw new RuntimeException('Nonboolean Radar inversion was accepted.');
    } catch (InvalidArgumentException) {
    }
}
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
$sparseGroups = array_map(static fn (int $index): array => [...$sources[1], 'Group' => 'Room ' . $index], range(0, 15));
if (count(RadarModel::Build($axes, $sparseGroups, $values, null, [], true)['data']) !== 16) {
    throw new RuntimeException('Sixteen partial groups must remain available within the existing group budget.');
}
foreach ([[], [...$sparseGroups, [...$sources[1], 'Group'           => 'Room 16']], [...$partialSources, $partialSources[0]],
    [[...$sources[1], 'VariableID' => 0]], [[...$sources[1], 'Axis' => 'unknown']]] as $invalid) {
    try {
        RadarModel::Validate($axes, $invalid, null, true);
        throw new RuntimeException('Opt-in must retain nonempty/group/identity/duplicate validation.');
    } catch (InvalidArgumentException) {
    }
}
echo "Radar current-value model verified.\n";
