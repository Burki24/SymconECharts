<?php

declare(strict_types=1);

require_once __DIR__ . '/../EChartsBarPolar/PolarData.php';

use SymconECharts\PolarData;

function assertPolarData(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
assertPolarData(PolarData::Bounds([5, 10, -20]) === [-20.0, 10.0], 'The full signed domain includes zero.');
assertPolarData(PolarData::Bounds(array_fill(0, 100, 0)) === [0.0, 1.0], 'An all-zero domain stays drawable without inventing values.');
assertPolarData(PolarData::Bounds([-10, -5]) === [-10.0, 0.0], 'Negative-only values still include zero.');
assertPolarData(PolarData::Fraction(0, -PHP_FLOAT_MAX, PHP_FLOAT_MAX) === 0.5, 'Finite wide signed scales must not overflow coordinate division.');
assertPolarData(abs(PolarData::Fraction(10, -20, 100) - 0.25) < 1.0e-12, 'Fractions refer to the whole configured domain.');
assertPolarData(PolarData::Fraction(10, 20, 30) === 0.0 && PolarData::Fraction(40, 20, 30) === 1.0, 'Manual-domain clipping is geometric only.');
foreach ([[], [1 => 1], ['1'], [NAN], [INF], array_fill(0, 101, 1)] as $invalid) {
    try {
        PolarData::Bounds($invalid);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Invalid Polar data must be rejected.');
}
echo "Complete Polar bounds and finite preview fractions verified.\n";
