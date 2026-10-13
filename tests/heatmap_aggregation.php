<?php

declare(strict_types=1);

use SymconECharts\HeatmapAggregation;

require_once __DIR__ . '/../EChartsHeatmap/HeatmapAggregation.php';

function heatmapAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$timezone = new DateTimeZone('Europe/Berlin');
$time = static fn (string $value): int => (new DateTimeImmutable($value, $timezone))->getTimestamp();

$cells = HeatmapAggregation::ByWeekdayAndHour([
    [$time('2026-10-05 08:00:00'), 10.0],
    [$time('2026-10-06 08:00:00'), 0.0],
    [$time('2026-10-12 08:00:00'), 20.0]
], 'Europe/Berlin');
heatmapAssert(count($cells) === 2, 'Only populated weekday/hour cells should exist.');
heatmapAssert(
    $cells[0] === ['hour' => 8, 'weekday' => 0, 'value' => 15.0, 'count' => 2],
    'Repeated Monday values should be averaged without interpolation.'
);
heatmapAssert(
    $cells[1] === ['hour' => 8, 'weekday' => 1, 'value' => 0.0, 'count' => 1],
    'Zero values must remain distinct from missing cells.'
);

$thirtyDayCells = HeatmapAggregation::ByWeekdayAndHour([
    [$time('2026-09-14 08:00:00'), 10.0],
    [$time('2026-09-21 08:00:00'), 20.0],
    [$time('2026-09-28 08:00:00'), 30.0],
    [$time('2026-10-05 08:00:00'), 40.0],
    [$time('2026-10-12 08:00:00'), 50.0]
], 'Europe/Berlin');
heatmapAssert(
    $thirtyDayCells === [['hour' => 8, 'weekday' => 0, 'value' => 30.0, 'count' => 5]],
    'A thirty-day Monday/hour average must include all five available Mondays, not only the last week.'
);

$fallback = HeatmapAggregation::ByWeekdayAndHour([
    [1792886400, 10.0], // 2026-10-25 02:00 CEST
    [1792890000, 20.0]  // 2026-10-25 02:00 CET
], 'Europe/Berlin');
heatmapAssert(
    $fallback === [['hour' => 2, 'weekday' => 6, 'value' => 15.0, 'count' => 2]],
    'Both real intervals of a repeated local DST hour must contribute.'
);

$spring = HeatmapAggregation::ByWeekdayAndHour([
    [$time('2026-03-29 01:00:00'), 1.0],
    [$time('2026-03-29 03:00:00'), 3.0]
], 'Europe/Berlin');
heatmapAssert(array_column($spring, 'hour') === [1, 3], 'The missing DST hour must stay empty.');

foreach ([[[1, INF]], [[1, PHP_FLOAT_MAX], [2, PHP_FLOAT_MAX]], [[1, 2], [1, 3]], [[2, 2], [1, 3]]] as $invalid) {
    try {
        HeatmapAggregation::ByWeekdayAndHour($invalid, 'UTC');
        throw new RuntimeException('Invalid archive points should be rejected.');
    } catch (InvalidArgumentException) {
    }
}

echo "Heatmap aggregation verified.\n";
