<?php

declare(strict_types=1);

use SymconECharts\HeatmapWindow;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../EChartsHeatmap/HeatmapWindow.php';

$zone = new DateTimeZone('Europe/Berlin');
$timestamp = static fn (string $date): int => (new DateTimeImmutable($date, $zone))->getTimestamp();
$now = $timestamp('2026-10-13 12:34:00');
$current = HeatmapWindow::Resolve('7d', 0, $now, 'Europe/Berlin');
if ($current['StartDate'] !== '2026-10-07' || $current['EndDate'] !== '2026-10-13'
    || $current['StartTimestamp'] !== $timestamp('2026-10-07 00:00:00')
    || $current['EndTimestamp'] !== $timestamp('2026-10-13 12:00:00') - 1
    || count($current['Days']) !== 7 || $current['Days'][0]['weekday'] !== 2
    || $current['Days'][6]['weekday'] !== 1) {
    throw new RuntimeException('The current seven-day window must include today and six preceding local dates.');
}

$previous = HeatmapWindow::Resolve('7d', 1, $now, 'Europe/Berlin');
if ($previous['StartDate'] !== '2026-09-30' || $previous['EndDate'] !== '2026-10-06'
    || $previous['EndTimestamp'] !== $current['StartTimestamp'] - 1) {
    throw new RuntimeException('Back navigation must select the immediately preceding seven calendar days.');
}

$thirty = HeatmapWindow::Resolve('30d', 0, $now, 'Europe/Berlin');
if ($thirty['StartDate'] !== '2026-09-14' || $thirty['EndDate'] !== '2026-10-13'
    || count($thirty['Days']) !== 30
    || HeatmapWindow::Resolve('30d', 1, $now, 'Europe/Berlin')['EndTimestamp']
        !== $thirty['StartTimestamp'] - 1) {
    throw new RuntimeException('Thirty-day windows must remain distinct and navigate without gaps.');
}

$dst = HeatmapWindow::Resolve('7d', 0, $timestamp('2026-03-31 12:00:00'), 'Europe/Berlin');
$dstPrevious = HeatmapWindow::Resolve('7d', 1, $timestamp('2026-03-31 12:00:00'), 'Europe/Berlin');
if ($dst['StartDate'] !== '2026-03-25' || $dst['EndDate'] !== '2026-03-31'
    || $dst['EndTimestamp'] - $dst['StartTimestamp'] + 1 !== 6 * 86400 + 11 * 3600
    || $dstPrevious['EndTimestamp'] !== $dst['StartTimestamp'] - 1) {
    throw new RuntimeException('A spring DST transition must retain seven local dates, not 168 fixed hours.');
}

foreach ([['7d', -1], ['7d', 121], ['14d', 0]] as [$range, $offset]) {
    try {
        HeatmapWindow::Resolve($range, $offset, $now, 'Europe/Berlin');
        throw new RuntimeException('Invalid Heatmap windows must be rejected.');
    } catch (InvalidArgumentException) {
    }
}

echo "Heatmap calendar windows verified.\n";
