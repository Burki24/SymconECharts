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
echo "Calendar day windows and archive points verified.\n";
