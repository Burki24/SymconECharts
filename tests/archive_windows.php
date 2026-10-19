<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/EChartsArchiveQuery.php';

use SymconECharts\EChartsArchiveQuery;

function assertArchiveWindow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$zone = 'Europe/Berlin';
$now = strtotime('2026-10-19T12:34:56+02:00');
$hour = EChartsArchiveQuery::CompletedIntervals('custom', 90, 'minute', 'hour', $now, $zone);
assertArchiveWindow(
    count($hour['Intervals']) === 1
    && $hour['StartTimestamp'] === strtotime('2026-10-19T11:00:00+02:00')
    && $hour['EndTimestamp'] === strtotime('2026-10-19T12:00:00+02:00') - 1,
    'A custom duration must include only whole completed intervals, not its partial leading hour.'
);
assertArchiveWindow(
    EChartsArchiveQuery::CompletedIntervals('custom', 1, 'minute', 'hour', $now, $zone)['Empty'],
    'A shorter-than-interval duration is valid but empty.'
);
$daily = EChartsArchiveQuery::CompletedIntervals('custom', 36, 'hour', 'day', $now, $zone);
assertArchiveWindow(
    count($daily['Intervals']) === 1 && $daily['StartTimestamp'] === strtotime('2026-10-18T00:00:00+02:00'),
    'Daily custom windows must exclude the partial leading local day.'
);
foreach (['2026-03-30T12:00:00+02:00' => 23, '2026-10-26T12:00:00+01:00' => 25] as $date => $hours) {
    $legacy = EChartsArchiveQuery::CompletedIntervals('24h', 0, 'unused', 'day', strtotime($date), $zone);
    assertArchiveWindow(
        count($legacy['Intervals']) === 1
        && $legacy['EndTimestamp'] - $legacy['StartTimestamp'] + 1 === $hours * 3600,
        'Preset daily windows preserve their existing local calendar-day semantics across DST.'
    );
}
$fall = EChartsArchiveQuery::CompletedIntervals('custom', 24, 'hour', 'hour', strtotime('2026-10-25T02:30:00+01:00'), $zone);
assertArchiveWindow(
    count($fall['Intervals']) === 24
    && $fall['EndTimestamp'] === strtotime('2026-10-25T02:00:00+01:00') - 1
    && count(array_unique(array_column($fall['Intervals'], 'label'))) === 24,
    'Repeated hours keep their actual offset and unique labels.'
);
$shortDay = EChartsArchiveQuery::CompletedIntervals('custom', 1, 'day', 'day', strtotime('2026-03-30T12:00:00+02:00'), $zone);
$longDay = EChartsArchiveQuery::CompletedIntervals('custom', 1, 'day', 'day', strtotime('2026-10-26T12:00:00+01:00'), $zone);
assertArchiveWindow(
    count($shortDay['Intervals']) === 1 && $longDay['Empty'],
    'Custom days mean elapsed 24 hours: a 23-hour local day fits, a 25-hour day does not.'
);
assertArchiveWindow(
    count(EChartsArchiveQuery::CompletedIntervals('custom', 30, 'day', 'hour', $now, $zone)['Intervals']) === 720,
    'The complete 720-interval boundary is allowed.'
);
foreach ([['custom', 721, 'hour', 'hour'], ['custom', 1000, 'week', 'day'],
    ['custom', 0, 'day', 'hour'], ['custom', 1001, 'day', 'hour'], ['custom', 1, 'month', 'hour'],
    ['today', 7, 'day', 'hour'], ['7d', 7, 'day', 'minute']] as [$range, $value, $unit, $interval]) {
    try {
        EChartsArchiveQuery::CompletedIntervals($range, $value, $unit, $interval, $now, $zone);
        throw new LogicException('Invalid or unbounded interval windows must be rejected before archive access.');
    } catch (InvalidArgumentException) {
    }
}
echo "Custom completed archive windows, budgets and DST semantics verified.\n";
