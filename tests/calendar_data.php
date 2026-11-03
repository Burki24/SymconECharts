<?php

declare(strict_types=1);

require_once __DIR__ . '/../EChartsCalendar/CalendarData.php';

use SymconECharts\CalendarData;

function checkCalendar(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$zone = new DateTimeZone('Europe/Berlin');
$now = (new DateTimeImmutable('2026-10-16 12:00:00', $zone))->getTimestamp();
$current = CalendarData::Resolve(0, 0, $now, 'Europe/Berlin');
checkCalendar(
    $current['Year'] === 2026 && $current['EndTimestamp'] === (new DateTimeImmutable('2026-10-16 00:00:00', $zone))->getTimestamp() - 1,
    'The current day must never contribute a partial daily mean.'
);
$leap = CalendarData::Resolve(2024, 0, $now, 'Europe/Berlin');
checkCalendar(count($leap['Days']) === 366 && $leap['Days'][59]['date'] === '2024-02-29', 'Leap years must contain February 29.');
checkCalendar($leap['EndTimestamp'] === (new DateTimeImmutable('2025-01-01', $zone))->getTimestamp() - 1, 'Historical years must include the whole last day.');
checkCalendar(CalendarData::Resolve(0, 2, $now, 'Europe/Berlin')['Year'] === 2024, 'Navigation must move by whole calendar years.');
$january = CalendarData::Resolve(0, 0, (new DateTimeImmutable('2026-01-01 12:00:00', $zone))->getTimestamp(), 'Europe/Berlin');
checkCalendar($january['Empty'], 'January 1 has no completed day in the current year.');
$points = [];
foreach (['2024-03-30', '2024-03-31', '2024-04-01', '2024-10-27', '2024-10-28'] as $index => $date) {
    $points[] = [(new DateTimeImmutable($date, $zone))->getTimestamp(), $index === 0 ? 0.0 : -5.0 + $index];
}
$cells = CalendarData::Cells($points, $leap);
checkCalendar(count($cells) === 5 && $cells[0] === ['date' => '2024-03-30', 'value' => 0.0]
    && $cells[2]['date'] === '2024-04-01' && $cells[4]['date'] === '2024-10-28', 'Zero, negative means and DST dates must be preserved without filling gaps.');
$partialPoints = [
    [(new DateTimeImmutable('2024-03-30 09:25:10', $zone))->getTimestamp(), -2.5],
    [(new DateTimeImmutable('2024-04-01 00:00:00', $zone))->getTimestamp(), 0.0]
];
checkCalendar(CalendarData::Cells($partialPoints, $leap) === [
    ['date' => '2024-03-30', 'value' => -2.5], ['date' => '2024-04-01', 'value' => 0.0]
], 'An archive beginning during the day must retain its daily mean and all later available days.');
foreach ([[$points[0], $points[0]], [[$points[0][0], 12], [$points[0][0] + 3600, 13]], [[$points[0][0], INF]], [[1, 12]]] as $invalid) {
    try {
        CalendarData::Cells($invalid, $leap);
        throw new RuntimeException('Invalid daily points were accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([-1, 9999] as $offset) {
    try {
        CalendarData::Resolve(0, $offset, $now, 'Europe/Berlin');
        throw new RuntimeException('Invalid navigation was accepted.');
    } catch (InvalidArgumentException) {
    }
}
try {
    CalendarData::Cells($partialPoints, $leap, 'consumption');
    throw new RuntimeException('Negative daily consumption was accepted.');
} catch (InvalidArgumentException) {
}
checkCalendar(
    CalendarData::Cells([[$points[0][0], 0.0]], $leap, 'consumption')[0]['value'] === 0.0,
    'A genuine zero consumption day must remain present.'
);
$settings = ['Period' => 'month', 'Year' => 2024, 'Month' => 2];
$month = CalendarData::ResolveView($settings, 0, $now, 'Europe/Berlin');
checkCalendar(count($month['Days']) === 29 && $month['StartDate'] === '2024-02-01' && $month['EndDate'] === '2024-02-29'
    && $month['CalendarRange'] === ['2024-02-01', '2024-02-29'], 'A selected month must use its actual leap-year date range.');
checkCalendar(
    CalendarData::ResolveView($settings, 2, $now, 'Europe/Berlin')['StartDate'] === '2023-12-01',
    'Month navigation must cross years without overflowing month ends.'
);
$quarter = CalendarData::ResolveView(['Period' => 'quarter', 'Year' => 2024, 'Quarter' => 1], 0, $now, 'Europe/Berlin');
checkCalendar(count($quarter['Days']) === 91 && $quarter['EndDate'] === '2024-03-31', 'Quarter one in a leap year contains 91 days.');
checkCalendar(
    CalendarData::ResolveView(['Period' => 'quarter', 'Year' => 2024, 'Quarter' => 1], 1, $now, 'Europe/Berlin')['StartDate'] === '2023-10-01',
    'Quarter navigation must move by three whole calendar months.'
);
$dateField = static fn (int $year, int $month, int $day): string => json_encode(compact('year', 'month', 'day'), JSON_THROW_ON_ERROR);
$fixedSettings = ['Period' => 'absolute', 'AbsoluteStart' => $dateField(2024, 12, 20), 'AbsoluteEnd' => $dateField(2025, 1, 10)];
$fixed = CalendarData::ResolveView($fixedSettings, 0, $now, 'Europe/Berlin');
checkCalendar(
    count($fixed['Days']) === 21 && $fixed['StartDate'] === '2024-12-20' && $fixed['EndDate'] === '2025-01-09',
    'Fixed calendar dates may cross a year boundary, with an explicitly excluded Until date.'
);
checkCalendar(
    CalendarData::ResolveView($fixedSettings, 1, $now, 'Europe/Berlin')['EndDate'] === '2024-12-19',
    'Fixed-window navigation must shift by its civil-day count, without overlaps or missing dates.'
);
foreach ([['2024-03-30', '2024-04-02'], ['2024-10-26', '2024-10-29']] as [$from, $until]) {
    $parts = static fn (string $s): string => $dateField(...array_map('intval', explode('-', $s)));
    $s = ['Period' => 'absolute', 'AbsoluteStart' => $parts($from), 'AbsoluteEnd' => $parts($until)];
    $w = CalendarData::ResolveView($s, 0, $now, 'Europe/Berlin');
    checkCalendar(
        count($w['Days']) === 3 && CalendarData::ResolveView($s, 1, $now, 'Europe/Berlin')['EndDate'] === date('Y-m-d', strtotime($from . ' -1 day')),
        'Date navigation counts calendar days, not 86400-second durations across DST.'
    );
}
$limit = ['Period' => 'absolute', 'AbsoluteStart' => $dateField(2023, 12, 20), 'AbsoluteEnd' => $dateField(2024, 12, 20)];
checkCalendar(
    count(CalendarData::ResolveView($limit, 0, $now, 'Europe/Berlin')['Days']) === 366,
    'Cross-year windows may contain exactly 366 days without increasing the archive budget.'
);
$currentMonth = CalendarData::ResolveView(['Period' => 'month', 'Year' => 0, 'Month' => 0], 0, $now, 'Europe/Berlin');
checkCalendar($currentMonth['StartDate'] === '2026-10-01' && $currentMonth['EndDate'] === '2026-10-31'
    && $currentMonth['EndTimestamp'] === $current['EndTimestamp'], 'The current month shows pending cells but never queries the running day.');
checkCalendar(
    CalendarData::ResolveView(['Period' => 'month'], 0, strtotime('2026-11-01 12:00:00'), 'Europe/Berlin')['Empty'],
    'The first day of a month has no completed days; empty query windows remain valid.'
);
foreach ([['Period' => 'year', 'Month' => -5, 'Quarter' => -5, 'AbsoluteStart' => 'invalid'],
    ['Period' => 'year']] as $legacy) {
    $w = CalendarData::ResolveView($legacy, 0, $now, 'Europe/Berlin');
    foreach ($current as $key => $value) {
        checkCalendar($w[$key] === $value, 'Legacy annual windows and inactive settings retain their original contract: ' . $key);
    }
}
foreach ([['Period' => 'invalid'], ['Period'                        => 'month', 'Month' => 13], ['Period' => 'quarter', 'Quarter' => 5],
    ['Period' => 'month', 'Year' => 2026, 'Month' => 12], ['Period' => 'absolute'],
    [...$fixedSettings, 'AbsoluteStart' => $dateField(2025, 2, 30)],
    [...$fixedSettings, 'AbsoluteEnd' => $fixedSettings['AbsoluteStart']],
    [...$fixedSettings, 'AbsoluteEnd' => $dateField(2026, 12, 1)],
    [...$limit, 'AbsoluteEnd' => $dateField(2024, 12, 21)]] as $bad) {
    try {
        CalendarData::ResolveView($bad, 0, $now, 'Europe/Berlin');
        throw new RuntimeException('Invalid Calendar view was accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([-1, 121] as $badOffset) {
    try {
        CalendarData::ResolveView($fixedSettings, $badOffset, $now, 'Europe/Berlin');
        throw new RuntimeException('Invalid fixed-calendar navigation offset was accepted.');
    } catch (InvalidArgumentException) {
    }
}
$midnightSettings = ['Period' => 'absolute', 'AbsoluteStart' => $dateField(2018, 11, 4), 'AbsoluteEnd' => $dateField(2018, 11, 6)];
$midnight = CalendarData::ResolveView($midnightSettings, 0, $now, 'America/Sao_Paulo');
checkCalendar(
    count($midnight['Days']) === 2 && $midnight['Days'][0]['date'] === '2018-11-04'
    && (new DateTimeImmutable('@' . $midnight['StartTimestamp']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') === '01:00',
    'Date-only selection uses the real local day boundary when DST removes midnight.'
);
$days = SymconECharts\EChartsArchiveQuery::CalendarDays('2018-11-04', '2018-11-06', $now, 'America/Sao_Paulo');
checkCalendar(
    $days[1]['startTimestamp'] - $days[0]['startTimestamp'] === 23 * 3600,
    'A normalized midnight must not shift the following civil day.'
);
$skipped = SymconECharts\EChartsArchiveQuery::CalendarDays('2011-12-29', '2012-01-01', $now, 'Pacific/Apia');
checkCalendar(array_column($skipped, 'date') === ['2011-12-29', '2011-12-31'], 'An entirely removed local date is not a synthetic archive day.');
$removedNavigation = CalendarData::ResolveView(['Period' => 'absolute', 'AbsoluteStart' => $dateField(2011, 12, 31), 'AbsoluteEnd' => $dateField(2012, 1, 1)], 1, $now, 'Pacific/Apia');
checkCalendar(
    $removedNavigation['Days'] === [] && $removedNavigation['Empty'] && $removedNavigation['StartWeekday'] === 5,
    'Navigating across a removed civil day yields an empty window without indexing nonexistent days.'
);
try {
    CalendarData::ResolveView(['Period' => 'absolute', 'AbsoluteStart' => $dateField(2011, 12, 30), 'AbsoluteEnd' => $dateField(2012, 1, 1)], 0, $now, 'Pacific/Apia');
    throw new RuntimeException('A removed local date was accepted.');
} catch (InvalidArgumentException) {
}
echo "Calendar day windows, views and archive points verified.\n";
