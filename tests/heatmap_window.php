<?php

declare(strict_types=1);

use SymconECharts\HeatmapWindow;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../EChartsHeatmap/HeatmapWindow.php';

$zone = new DateTimeZone('Europe/Berlin');
$timestamp = static fn (string $date): int => (new DateTimeImmutable($date, $zone))->getTimestamp();
$now = $timestamp('2026-10-13 12:34:00');
$current = HeatmapWindow::Resolve('1w', 0, $now, 'Europe/Berlin');
if ($current['StartDate'] !== '2026-10-07' || $current['EndDate'] !== '2026-10-13'
    || $current['StartTimestamp'] !== $timestamp('2026-10-07 00:00:00')
    || $current['EndTimestamp'] !== $timestamp('2026-10-13 12:00:00') - 1
    || count($current['Days']) !== 7 || $current['Days'][0]['weekday'] !== 2
    || $current['Days'][6]['weekday'] !== 1) {
    throw new RuntimeException('The current seven-day window must include today and six preceding local dates.');
}

$previous = HeatmapWindow::Resolve('1w', 1, $now, 'Europe/Berlin');
if ($previous['StartDate'] !== '2026-09-30' || $previous['EndDate'] !== '2026-10-06'
    || $previous['EndTimestamp'] !== $current['StartTimestamp'] - 1) {
    throw new RuntimeException('Back navigation must select the immediately preceding seven calendar days.');
}

$calendarMonths = [
    '1m'  => ['2026-09-01', '2026-09-30', '2026-08-01', '2026-08-31'],
    '3m'  => ['2026-07-01', '2026-09-30', '2026-04-01', '2026-06-30'],
    '6m'  => ['2026-04-01', '2026-09-30', '2025-10-01', '2026-03-31'],
    '12m' => ['2025-10-01', '2026-09-30', '2024-10-01', '2025-09-30']
];
foreach ($calendarMonths as $range => [$start, $end, $priorStart, $priorEnd]) {
    $period = HeatmapWindow::Resolve($range, 0, $now, 'Europe/Berlin');
    $prior = HeatmapWindow::Resolve($range, 1, $now, 'Europe/Berlin');
    if ($period['StartDate'] !== $start || $period['EndDate'] !== $end
        || $prior['StartDate'] !== $priorStart || $prior['EndDate'] !== $priorEnd
        || $prior['EndTimestamp'] !== $period['StartTimestamp'] - 1
        || $period['Days'] !== []) {
        throw new RuntimeException('Calendar-month windows must include only complete months and navigate without gaps: ' . $range);
    }
}
$yearToDate = HeatmapWindow::Resolve('ytd', 0, $now, 'Europe/Berlin');
$priorYearToDate = HeatmapWindow::Resolve('ytd', 1, $now, 'Europe/Berlin');
if ($yearToDate['StartDate'] !== '2026-01-01' || $yearToDate['EndDate'] !== '2026-10-13'
    || $yearToDate['EndTimestamp'] !== $timestamp('2026-10-13 12:00:00') - 1
    || $priorYearToDate['StartDate'] !== '2025-01-01' || $priorYearToDate['EndDate'] !== '2025-10-13') {
    throw new RuntimeException('Year-to-date navigation must compare the same calendar portion in prior years.');
}
$leap = HeatmapWindow::Resolve('ytd', 1, $timestamp('2028-02-29 12:00:00'), 'Europe/Berlin');
if ($leap['StartDate'] !== '2027-01-01' || $leap['EndDate'] !== '2027-02-28') {
    throw new RuntimeException('Prior-year comparisons must clamp leap-day dates.');
}
$oldestYearOffset = HeatmapWindow::MaximumOffset('12m', $now, 'Europe/Berlin');
if ($oldestYearOffset >= 120) {
    throw new RuntimeException('Year navigation must stop before archive timestamps become invalid.');
}
HeatmapWindow::Resolve('12m', $oldestYearOffset, $now, 'Europe/Berlin');
try {
    HeatmapWindow::Resolve('12m', $oldestYearOffset + 1, $now, 'Europe/Berlin');
    throw new RuntimeException('The first unsupported yearly window must be rejected.');
} catch (InvalidArgumentException) {
}

$chunks = HeatmapWindow::ArchiveChunks($timestamp('2025-10-01 00:00:00'), $timestamp('2026-10-01 00:00:00') - 1);
if (count($chunks) < 6 || $chunks[0]['StartTimestamp'] !== $timestamp('2025-10-01 00:00:00')
    || $chunks[count($chunks) - 1]['EndTimestamp'] !== $timestamp('2026-10-01 00:00:00') - 1) {
    throw new RuntimeException('Long Heatmap periods must cover all timestamps with bounded archive requests.');
}
foreach ($chunks as $index => $chunk) {
    if ($chunk['EndTimestamp'] - $chunk['StartTimestamp'] + 1 > 60 * 86400
        || ($index > 0 && $chunk['StartTimestamp'] !== $chunks[$index - 1]['EndTimestamp'] + 1)) {
        throw new RuntimeException('Archive chunks must be bounded, adjacent and non-overlapping.');
    }
}

$dst = HeatmapWindow::Resolve('1w', 0, $timestamp('2026-03-31 12:00:00'), 'Europe/Berlin');
$dstPrevious = HeatmapWindow::Resolve('1w', 1, $timestamp('2026-03-31 12:00:00'), 'Europe/Berlin');
if ($dst['StartDate'] !== '2026-03-25' || $dst['EndDate'] !== '2026-03-31'
    || $dst['EndTimestamp'] - $dst['StartTimestamp'] + 1 !== 6 * 86400 + 11 * 3600
    || $dstPrevious['EndTimestamp'] !== $dst['StartTimestamp'] - 1) {
    throw new RuntimeException('A spring DST transition must retain seven local dates, not 168 fixed hours.');
}

foreach ([['1w', -1], ['1w', 121], ['7d', 0], ['30d', 0], ['14d', 0]] as [$range, $offset]) {
    try {
        HeatmapWindow::Resolve($range, $offset, $now, 'Europe/Berlin');
        throw new RuntimeException('Invalid Heatmap windows must be rejected.');
    } catch (InvalidArgumentException) {
    }
}

echo "Heatmap calendar windows verified.\n";
