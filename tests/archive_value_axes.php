<?php

declare(strict_types=1);

use SymconECharts\EChartsUnitAxes;

require_once __DIR__ . '/../libs/EChartsUnitAxes.php';

// A private axis can occur before, between or after shared rows with exactly the same unit.
$sources = [
    ['Unit' => '°C', 'OwnAxis' => true, 'AxisPosition' => 'left', 'AxisRange' => ['minimum' => -20.0, 'maximum' => 40.0]],
    ['Unit' => '%', 'AxisPosition' => 'left', 'AxisRange' => ['minimum' => 0.0, 'maximum' => 100.0]],
    ['Unit' => '°C', 'AxisPosition' => 'auto'],
    ['Unit' => '°C', 'OwnAxis' => true, 'AxisPosition' => 'auto'],
    ['Unit' => '°C', 'AxisPosition' => 'right', 'AxisRange' => ['minimum' => 20.0, 'maximum' => 60.0]]
];
$model = EChartsUnitAxes::Build($sources);
if ($model['SeriesIndexes'] !== [0, 1, 2, 3, 2]
    || array_column($model['Axes'], 'position') !== ['left', 'left', 'right', 'right']
    || array_column($model['Axes'], 'positionIndex') !== [0, 1, 0, 1]
    || $model['Axes'][0]['minimum'] !== -20.0 || $model['Axes'][0]['maximum'] !== 40.0
    || $model['Axes'][2]['minimum'] !== 20.0 || $model['Axes'][2]['maximum'] !== 60.0
    || isset($model['Axes'][3]['minimum']) || isset($model['Axes'][3]['maximum'])) {
    throw new RuntimeException('Mixed shared and individual series lost their scale, side or axis assignment.');
}

// The empty unit also forms a legitimate shared axis.
$sources = [
    ['Unit' => '', 'AxisPosition' => 'auto'],
    ['Unit' => '', 'OwnAxis' => true, 'AxisPosition' => 'auto'],
    ['Unit' => '', 'OwnAxis' => false, 'AxisPosition' => 'auto']
];
if (EChartsUnitAxes::Build($sources)['SeriesIndexes'] !== [0, 1, 0]) {
    throw new RuntimeException('Independent unitless series must not absorb shared unitless rows.');
}

// A presentation without scale metadata uses the configured fallback range.
if (EChartsUnitAxes::ResolveRange(['AxisRangeMode' => 'presentation', 'AxisMinimum' => -10, 'AxisMaximum' => 25])
        !== ['minimum' => -10.0, 'maximum' => 25.0]
    || EChartsUnitAxes::ResolveRange(['AxisRangeMode' => 'auto', 'AxisMinimum' => 100, 'AxisMaximum' => 0]) !== null) {
    throw new RuntimeException('Automatic and presentation scales must preserve their range fallback rules.');
}

echo "Shared and individual archive axis assignments verified.\n";
