<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/EChartsArchiveQuery.php';

use SymconECharts\EChartsArchiveQuery;

function assertAbsoluteArchive(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function rejectAbsoluteArchive(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException($message);
}

$berlin = 'Europe/Berlin';
$selection = ['year' => 2026, 'month' => 10, 'day' => 19, 'hour' => 12, 'minute' => 34, 'second' => 56];
$expected = strtotime('2026-10-19T12:34:56+02:00');
foreach ([$selection, (object) $selection, json_encode($selection, JSON_THROW_ON_ERROR)] as $raw) {
    assertAbsoluteArchive(
        EChartsArchiveQuery::ParseLocalDateTime($raw, $berlin) === $expected,
        'Native date/time objects and their JSON representation resolve in the installation timezone.'
    );
}
assertAbsoluteArchive(EChartsArchiveQuery::ParseLocalDateTime(
    ['year' => 2024, 'month' => 2, 'day' => 29, 'hour' => 1, 'minute' => 2, 'second' => 3],
    'UTC'
) === strtotime('2024-02-29T01:02:03Z'), 'Gregorian leap dates are accepted.');
assertAbsoluteArchive(EChartsArchiveQuery::ParseLocalDateTime($selection, '+05:30')
    === strtotime('2026-10-19T12:34:56+05:30'), 'Fixed-offset timezones without transition lists are supported.');
assertAbsoluteArchive(EChartsArchiveQuery::ParseLocalDateTime(
    ['year' => 9999, 'month' => 12, 'day' => 31, 'hour' => 23, 'minute' => 59, 'second' => 59],
    'UTC'
) === 253402300799, 'Parsing supports four-digit future years without imposing query-time policy.');
$invalid = [null, true, 1792400000, '', '{}', '[]', '{', str_repeat(' ', 1025),
    array_fill_keys(array_keys($selection), 0), array_diff_key($selection, ['second' => true]),
    [...$selection, 'offset' => 7200], [...$selection, 'year'                        => 2025, 'month' => 2, 'day' => 29],
    [...$selection, 'month' => 13], [...$selection, 'day'                            => 32], [...$selection, 'hour' => 24],
    [...$selection, 'minute' => 60], [...$selection, 'second'                        => 60], [...$selection, 'year' => 10000],
    [...$selection, 'year' => 1969], [...$selection, 'minute'                        => '34'], [...$selection, 'minute' => 34.0],
    [...$selection, 'minute' => true], [...$selection, 'second'                      => []]];
foreach ($invalid as $raw) {
    rejectAbsoluteArchive(
        static fn () => EChartsArchiveQuery::ParseLocalDateTime($raw, $berlin),
        'Malformed, unset, non-integer or non-Gregorian date/time input must be rejected.'
    );
}
foreach ([[2026, 3, 29, 2, 30], [2026, 10, 25, 2, 30]] as [$year, $month, $day, $hour, $minute]) {
    $dstSelection = compact('year', 'month', 'day', 'hour', 'minute') + ['second' => 0];
    rejectAbsoluteArchive(static fn () => EChartsArchiveQuery::ParseLocalDateTime(
        $dstSelection,
        $berlin
    ), 'Nonexistent and ambiguous local DST hours must not be guessed.');
}
rejectAbsoluteArchive(
    static fn () => EChartsArchiveQuery::ParseLocalDateTime($selection, 'Invalid/Zone'),
    'Invalid installation timezones must produce a controlled argument error.'
);
rejectAbsoluteArchive(static fn () => EChartsArchiveQuery::ParseLocalDateTime(
    ['year' => 1970, 'month' => 1, 'day' => 1, 'hour' => 0, 'minute' => 0, 'second' => 0],
    'UTC'
), 'Unix epoch zero is outside positive archive timestamps.');

$start = strtotime('2026-10-01T08:15:00+02:00');
$end = strtotime('2026-10-02T11:45:00+02:00');
$now = strtotime('2026-10-20T12:34:56+02:00');
$query = EChartsArchiveQuery::ResolveAbsolute($start, $end, 'hour', 32000, 32, $now, $berlin);
assertAbsoluteArchive($query === [
    'DurationSeconds' => $end - $start,
    'StartTimestamp'  => strtotime('2026-10-01T09:00:00+02:00'),
    'EndTimestamp'    => strtotime('2026-10-02T11:00:00+02:00') - 1,
    'Mode'            => 'aggregated', 'AggregationLevel' => 0, 'Limit' => 1000,
    'CalendarAligned' => true, 'AcceptLiveUpdates' => false, 'Empty' => false
], 'Historical windows include only fully contained completed intervals with unchanged shared budgets.');
assertAbsoluteArchive(
    EChartsArchiveQuery::ResolveAbsolute($start, $end, 'hour', 32000, 32, $now + 86400, $berlin) === $query,
    'A fixed historical request must remain stable when the current time advances.'
);
$partialNow = strtotime('2026-10-02T11:45:00+02:00');
assertAbsoluteArchive(
    EChartsArchiveQuery::ResolveAbsolute($start, $partialNow, 'hour', 1, 32, $partialNow, $berlin)['EndTimestamp']
    === strtotime('2026-10-02T11:00:00+02:00') - 1,
    'An end equal to now excludes the ongoing hour.'
);
foreach ([1, 32, 100000] as $count) {
    $limited = EChartsArchiveQuery::ResolveAbsolute($start, $end, 'hour', 32000, $count, $now, $berlin);
    assertAbsoluteArchive(
        $limited['Limit'] === max(1, min(2000, intdiv(32000, $count))),
        'The planner keeps the existing per-source Gateway limit contract.'
    );
}
foreach ([['2026-10-01T08:15:00Z', '2026-10-01T08:45:00Z', 'hour'],
    ['2026-10-01T08:15:00Z', '2026-10-02T08:45:00Z', 'day']] as [$from, $to, $interval]) {
    $a = strtotime($from);
    $b = strtotime($to);
    $empty = EChartsArchiveQuery::ResolveAbsolute($a, $b, $interval, 1000, 1, $now, 'UTC');
    assertAbsoluteArchive(
        $empty['Empty'] && $empty['StartTimestamp'] === $a && $empty['EndTimestamp'] === $b - 1,
        'A valid window without a full interval remains valid but empty, not an invalid reversed query.'
    );
}
foreach ([['2026-03-29T00:00:00+01:00', '2026-03-30T00:00:00+02:00', 23],
    ['2026-10-25T00:00:00+02:00', '2026-10-26T00:00:00+01:00', 25]] as [$from, $to, $hours]) {
    $a = strtotime($from);
    $b = strtotime($to);
    foreach (['day' => 1, 'hour' => 0] as $interval => $level) {
        $dst = EChartsArchiveQuery::ResolveAbsolute($a, $b, $interval, 2000, 1, $b, $berlin);
        assertAbsoluteArchive(
            !$dst['Empty'] && $dst['StartTimestamp'] === $a && $dst['EndTimestamp'] === $b - 1
            && $dst['DurationSeconds'] === $hours * 3600 && $dst['AggregationLevel'] === $level,
            'Complete local DST days retain 23/25 elapsed hours rather than fixed 24-hour rounding.'
        );
    }
}
$secondFallHour = strtotime('2026-10-25T02:15:00+01:00');
$fallEnd = strtotime('2026-10-25T04:15:00+01:00');
$fall = EChartsArchiveQuery::ResolveAbsolute($secondFallHour, $fallEnd, 'hour', 2000, 1, $fallEnd, $berlin);
assertAbsoluteArchive(
    $fall['StartTimestamp'] === strtotime('2026-10-25T03:00:00+01:00')
    && $fall['EndTimestamp'] === strtotime('2026-10-25T04:00:00+01:00') - 1,
    'Partial second autumn hours preserve the actual offset when rounding upward.'
);
$springStart = strtotime('2026-03-29T01:15:00+01:00');
$springEnd = strtotime('2026-03-29T04:15:00+02:00');
$spring = EChartsArchiveQuery::ResolveAbsolute($springStart, $springEnd, 'hour', 2000, 1, $springEnd, $berlin);
assertAbsoluteArchive(
    $spring['StartTimestamp'] === strtotime('2026-03-29T03:00:00+02:00')
    && $spring['EndTimestamp'] === strtotime('2026-03-29T04:00:00+02:00') - 1,
    'Rounding across the spring gap advances to the next actual local hour.'
);
$firstFallHour = strtotime('2026-10-25T02:00:00+02:00');
$secondFallBoundary = strtotime('2026-10-25T02:00:00+01:00');
$repeated = EChartsArchiveQuery::ResolveAbsolute($firstFallHour, $secondFallBoundary, 'hour', 2000, 1, $fallEnd, $berlin);
assertAbsoluteArchive(
    !$repeated['Empty'] && $repeated['StartTimestamp'] === $firstFallHour
    && $repeated['EndTimestamp'] === $secondFallBoundary - 1,
    'An entire first repeated hour is kept as a distinct elapsed interval.'
);
$indiaStart = strtotime('2026-10-01T08:15:00+05:30');
$indiaEnd = strtotime('2026-10-01T11:45:00+05:30');
$india = EChartsArchiveQuery::ResolveAbsolute($indiaStart, $indiaEnd, 'hour', 2000, 1, $now, 'Asia/Kolkata');
assertAbsoluteArchive(
    $india['StartTimestamp'] === strtotime('2026-10-01T09:00:00+05:30')
    && $india['EndTimestamp'] === strtotime('2026-10-01T11:00:00+05:30') - 1,
    'Local hour boundaries need not align with UTC whole hours.'
);
$utc = EChartsArchiveQuery::ResolveAbsolute(
    strtotime('2026-10-01T00:00:00Z'),
    strtotime('2026-10-02T00:00:00Z'),
    'day',
    2000,
    1,
    $now,
    'UTC'
);
assertAbsoluteArchive(
    !$utc['Empty'] && $utc['EndTimestamp'] - $utc['StartTimestamp'] + 1 === 86400,
    'UTC daily windows preserve inclusive archive end timestamps.'
);
foreach ([[0, $end, 'hour', 1000, 1, $now], [$start, $start, 'hour', 1000, 1, $now],
    [$end, $start, 'hour', 1000, 1, $now], [$start, $now + 1, 'hour', 1000, 1, $now],
    [$start, $end, 'minute', 1000, 1, $now], [$start, $end, 'hour', 0, 1, $now],
    [$start, $end, 'hour', 1000, 0, $now], [$start, $end, 'hour', 1000, 1, 0],
    [PHP_INT_MAX - 3600, PHP_INT_MAX, 'day', 1000, 1, PHP_INT_MAX]] as $args) {
    rejectAbsoluteArchive(
        static fn () => EChartsArchiveQuery::ResolveAbsolute(...[...$args, $berlin]),
        'Invalid absolute windows must be rejected before archive access and arithmetic.'
    );
}
rejectAbsoluteArchive(static fn () => EChartsArchiveQuery::ResolveAbsolute(
    strtotime('2026-10-04T01:45:00+10:30'),
    strtotime('2026-10-04T04:00:00+11:00'),
    'hour',
    1000,
    1,
    strtotime('2026-10-20T12:00:00Z'),
    'Australia/Lord_Howe'
), 'Non-hour offset transitions must be rejected rather than queried from a partial local hour.');
rejectAbsoluteArchive(static fn () => EChartsArchiveQuery::ResolveAbsolute(
    strtotime('2026-10-04T00:00:00+10:30'),
    strtotime('2026-10-04T04:00:00+11:00'),
    'hour',
    1000,
    1,
    strtotime('2026-10-20T12:00:00Z'),
    'Australia/Lord_Howe'
), 'Non-hour transitions inside an otherwise aligned window must also be rejected.');
$afterTransition = EChartsArchiveQuery::ResolveAbsolute(
    strtotime('2026-10-04T03:00:00+11:00'),
    strtotime('2026-10-04T04:00:00+11:00'),
    'hour',
    1000,
    1,
    strtotime('2026-10-20T12:00:00Z'),
    'Australia/Lord_Howe'
);
assertAbsoluteArchive(
    !$afterTransition['Empty'] && $afterTransition['EndTimestamp'] - $afterTransition['StartTimestamp'] + 1 === 3600,
    'A complete local hour after the non-hour transition remains usable.'
);

echo "Absolute archive windows, strict local date/time parsing, fixed budgets and DST semantics verified.\n";
