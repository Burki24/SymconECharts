<?php

declare(strict_types=1);

require_once __DIR__ . '/../EChartsPie/PieData.php';

use SymconECharts\PieData;

function assertPieData(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$shares = PieData::Shares([10, 30, 60]);
foreach ([10, 30, 60] as $index => $expected) {
    assertPieData(abs($shares[$index] - $expected) < 1.0e-12, 'Shares must refer to the entire supplied population.');
}
assertPieData(PieData::Shares(array_fill(0, 100, 1)) === array_fill(0, 100, 1.0), 'All hundred sources must participate.');
assertPieData(PieData::Shares([0, 0]) === [0.0, 0.0] && PieData::Shares([]) === [], 'Zero and empty selections must not invent sectors.');
assertPieData(PieData::Shares([PHP_FLOAT_MAX, PHP_FLOAT_MAX]) === [50.0, 50.0], 'Finite weights must not overflow their denominator.');
assertPieData(PieData::Shares([1.0e-300, 1.0e-300]) === [50.0, 50.0], 'Small weights must keep proportions.');
foreach ([[-1], [NAN], [INF], ['1'], array_fill(0, 101, 1), [1 => 1]] as $invalid) {
    try {
        PieData::Shares($invalid);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Invalid Pie weights must be rejected.');
}
echo "Pie full-population and overflow-safe shares verified.\n";
