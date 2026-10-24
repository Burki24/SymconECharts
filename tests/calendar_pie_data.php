<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsCalendarPie/CalendarPieData.php';

use SymconECharts\CalendarPieData;
use SymconECharts\StateTimelineData;

function calendarPieAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function calendarPieReject(callable $operation, string $message): void
{
    try {
        $operation();
        throw new RuntimeException($message);
    } catch (InvalidArgumentException) {
    }
}

$zone = new DateTimeZone('Europe/Berlin');
$stamp = static fn (string $date): int => (new DateTimeImmutable($date, $zone))->getTimestamp();
$now = $stamp('2026-10-18 12:00:00');
$current = CalendarPieData::Resolve('', 0, $now, 'Europe/Berlin');
calendarPieAssert(
    $current['Month'] === '2026-10' && $current['BaseMonth'] === '2026-10'
    && $current['EndTimestamp'] === $stamp('2026-10-18') && count($current['Days']) === 31,
    'Current month includes only completed local days and uses an exclusive cutoff.'
);
calendarPieAssert($current['Days'][16]['completed'] && !$current['Days'][17]['completed']
    && !$current['Days'][30]['completed'], 'Today and future days must remain pending.');
calendarPieAssert(CalendarPieData::Resolve('', 2, $now, 'Europe/Berlin')['Month'] === '2026-08', 'Navigation moves by calendar months.');
calendarPieAssert(
    CalendarPieData::Resolve('2024-03', 0, $now, 'Europe/Berlin')['Days'][30]['endTimestamp']
    - CalendarPieData::Resolve('2024-03', 0, $now, 'Europe/Berlin')['Days'][30]['startTimestamp'] === 82800,
    'The spring DST day has 23 real hours.'
);
$fall = CalendarPieData::Resolve('2024-10', 0, $now, 'Europe/Berlin');
calendarPieAssert($fall['Days'][26]['endTimestamp'] - $fall['Days'][26]['startTimestamp'] === 90000, 'The autumn DST day has 25 real hours.');
$leap = CalendarPieData::Resolve('2024-02', 0, $now, 'Europe/Berlin');
calendarPieAssert(count($leap['Days']) === 29 && $leap['Days'][28]['date'] === '2024-02-29', 'Leap months retain February 29.');
calendarPieAssert(CalendarPieData::Resolve('1971-01', 0, $now, 'UTC')['MaximumOffset'] === 0, 'Navigation never precedes January 1971.');
calendarPieAssert($current['MaximumOffset'] === 120, 'Month navigation has a ten-year query bound.');
$midnightDST = CalendarPieData::Resolve('2018-11', 0, $now, 'America/Sao_Paulo');
$saoPaulo = new DateTimeZone('America/Sao_Paulo');
$midnightStamp = static fn (string $date): int => (new DateTimeImmutable($date . ' 00:00:00', $saoPaulo))->getTimestamp();
calendarPieAssert(
    count($midnightDST['Days']) === 30 && !in_array(false, array_column($midnightDST['Days'], 'completed'), true)
    && $midnightDST['EndTimestamp'] === $midnightStamp('2018-12-01'),
    'A midnight DST gap must not hide the last historical day or shift the month cutoff.'
);
calendarPieAssert(
    $midnightDST['Days'][3]['endTimestamp'] - $midnightDST['Days'][3]['startTimestamp'] === 82800
    && $midnightDST['Days'][4]['startTimestamp'] === $midnightStamp('2018-11-05')
    && $midnightDST['Days'][29]['endTimestamp'] === $midnightStamp('2018-12-01'),
    'Each civil day must resolve its own midnight instead of retaining an earlier DST-normalized hour.'
);
$havana = CalendarPieData::Resolve('2001-04', 0, $now, 'America/Havana');
$cuba = new DateTimeZone('America/Havana');
calendarPieAssert(
    $havana['EndTimestamp'] === (new DateTimeImmutable('2001-05-01 00:00:00', $cuba))->getTimestamp()
    && $havana['Days'][0]['endTimestamp'] - $havana['Days'][0]['startTimestamp'] === 82800
    && !in_array(false, array_column($havana['Days'], 'completed'), true),
    'A DST gap on the first day must not shift every subsequent day or the next month boundary.'
);
$samoa = CalendarPieData::Resolve('2011-12', 0, $now, 'Pacific/Apia');
calendarPieAssert(
    count($samoa['Days']) === 30 && !in_array('2011-12-30', array_column($samoa['Days'], 'date'), true)
    && count(array_unique(array_column($samoa['Days'], 'date'))) === 30
    && !in_array(false, array_column($samoa['Days'], 'completed'), true),
    'A skipped civil date has no elapsed interval and must not duplicate a normalized following date.'
);
foreach ($samoa['Days'] as $day) {
    calendarPieAssert($day['endTimestamp'] > $day['startTimestamp'], 'Existing local dates must have strictly positive elapsed durations.');
}
$first = CalendarPieData::Resolve('', 0, $stamp('2026-10-01 12:00:00'), 'Europe/Berlin');
calendarPieAssert($first['Empty'] && $first['StartTimestamp'] === $first['EndTimestamp'], 'First day of the month has no completed day.');
foreach ([['2026-11', 0], ['1970-12', 0], ['2024-2', 0], ['2024-02-01', 0], ['2024-13', 0], ['', -1], ['', 121], ['1971-01', 1]] as [$month, $offset]) {
    calendarPieReject(static fn () => CalendarPieData::Resolve($month, $offset, $now, 'Europe/Berlin'), 'Invalid month/navigation accepted.');
}
calendarPieReject(static fn () => CalendarPieData::Resolve('', 0, $now, 'Invalid/Zone'), 'Invalid timezone accepted.');

$sources = [['VariableID' => 701, 'Label' => 'Same', 'Color' => '#000000'], ['VariableID' => 702, 'Label' => 'Same', 'Color' => '']];
$flexibleSources = [];
$flexiblePoints = [];
for ($count = 1; $count <= 6; $count++) {
    $id = 710 + $count;
    $flexibleSources[] = ['VariableID' => $id, 'Label' => 'Source ' . $count, 'Color' => ''];
    $flexiblePoints[$id] = [[$stamp('2024-02-01'), $count]];
    if ($count <= 5) {
        $flexibleDay = CalendarPieData::NumericDays($flexibleSources, $flexiblePoints, $leap)[0];
        calendarPieAssert(
            $flexibleDay['status'] === 'complete' && count($flexibleDay['slices']) === $count
            && array_column($flexibleDay['slices'], 'value') === array_map(floatval(...), range(1, $count)),
            'Each selected source count from one to five must retain every daily value.'
        );
    }
}
calendarPieReject(static fn () => CalendarPieData::NumericDays($flexibleSources, $flexiblePoints, $leap), 'A sixth numeric source must be rejected, not silently omitted.');
$points = [701 => [[$stamp('2024-02-01 12:34:56'), 0], [$stamp('2024-02-02'), 5], [$stamp('2024-02-04'), 3]],
    702        => [[$stamp('2024-02-01 09:00:00'), 0], [$stamp('2024-02-02'), 7]]];
$days = CalendarPieData::NumericDays($sources, $points, $leap);
calendarPieAssert(
    count($days) === 29 && $days[0]['status'] === 'zero' && $days[1]['status'] === 'complete'
    && $days[2]['status'] === 'missing' && $days[3]['status'] === 'partial',
    'Real zeros, complete, missing and partial numeric days remain distinct.'
);
calendarPieAssert($days[0]['slices'][0] === ['id' => 'variable-701', 'label' => 'Same', 'value' => 0.0, 'color' => '#000000']
    && $days[0]['slices'][1]['id'] === 'variable-702', 'Equal labels must never merge variable identities or lose explicit black.');
calendarPieAssert($days[3]['missing'] === ['variable-702'] && count($days[3]['slices']) === 1
    && $days[2]['slices'] === [] && $days[2]['missing'] === ['variable-701', 'variable-702'], 'No missing source may be silently filled with zero.');
$longLabel = str_repeat('Name', 100);
calendarPieAssert(
    CalendarPieData::NumericDays(
        [['VariableID' => 701, 'Label' => $longLabel, 'Color' => '']],
        [701 => [[$stamp('2024-02-01'), 1]]],
        $leap
    )[0]['slices'][0]['label'] === $longLabel,
    'Resolved object names are not silently truncated or treated as source identities.'
);
calendarPieAssert(CalendarPieData::NumericDays($sources, [], $first)[0]['status'] === 'pending', 'No archive point is required for pending days.');
calendarPieAssert(CalendarPieData::NumericDays($sources, [], $current)[17]['status'] === 'pending', 'Today remains pending rather than missing.');
foreach ([[[$stamp('2024-02-01'), -1]], [[$stamp('2024-02-01'), INF]], [[$stamp('2024-02-01'), '1']],
    [[$stamp('2024-02-01'), 1], [$stamp('2024-02-01 01:00:00'), 2]], [[$stamp('2024-03-01'), 1]],
    [[$stamp('2024-02-02'), 1], [$stamp('2024-02-01'), 2]]] as $invalidPoints) {
    calendarPieReject(static fn () => CalendarPieData::NumericDays($sources, [701 => $invalidPoints], $leap), 'Invalid daily values accepted.');
}
calendarPieReject(static fn () => CalendarPieData::NumericDays([], [], $leap), 'Empty source selection accepted.');
calendarPieReject(static fn () => CalendarPieData::NumericDays([$sources[0], $sources[0]], [], $leap), 'Duplicate source identity accepted.');
calendarPieReject(static fn () => CalendarPieData::NumericDays($sources, [701 => 'invalid'], $leap), 'Malformed source points accepted.');
calendarPieReject(static fn () => CalendarPieData::NumericDays($sources, [703 => []], $leap), 'Unconfigured archive source accepted.');
calendarPieReject(static fn () => CalendarPieData::NumericDays($sources, [701 => [[$stamp('2024-02-01'), PHP_FLOAT_MAX]],
    702                                                                       => [[$stamp('2024-02-01'), PHP_FLOAT_MAX]]], $leap), 'Overflowing daily slice totals accepted.');
calendarPieReject(static fn () => CalendarPieData::NumericDays($sources, [701 => [[$current['EndTimestamp'], 1]]], $current), 'Current-day point accepted.');

$normalizedSources = [
    ['VariableID' => 701, 'Label' => 'Temperature', 'Color' => '', 'Minimum' => 0, 'Maximum' => 40, 'Unit' => '°C', 'Decimals' => 1],
    ['VariableID' => 702, 'Label' => 'Humidity', 'Color' => '', 'Minimum' => 0, 'Maximum' => 100, 'Unit' => '%', 'Decimals' => 0]
];
$normalized = CalendarPieData::NumericDays(
    $normalizedSources,
    [701 => [[$stamp('2024-02-01'), 20]], 702 => [[$stamp('2024-02-01'), 60]]],
    $leap,
    true
);
calendarPieAssert(
    array_column($normalized[0]['slices'], 'value') === [50.0, 60.0]
    && array_column($normalized[0]['slices'], 'originalValue') === [20.0, 60.0]
    && array_column($normalized[0]['slices'], 'unit') === ['°C', '%']
    && array_column($normalized[0]['slices'], 'decimals') === [1, 0],
    'Normalized slices must use source ranges but preserve original daily values and individual units/precision.'
);
$bounded = CalendarPieData::NumericDays($normalizedSources, [701 => [[$stamp('2024-02-01'), -10], [$stamp('2024-02-02'), 45]],
    702                                                          => [[$stamp('2024-02-01'), 150], [$stamp('2024-02-02'), -1]]], $leap, true);
calendarPieAssert(
    array_column($bounded[0]['slices'], 'value') === [0.0, 100.0]
    && array_column($bounded[1]['slices'], 'value') === [100.0, 0.0]
    && array_column($bounded[0]['slices'], 'originalValue') === [-10.0, 150.0],
    'Only normalized weights are clipped at the fixed range; original values are never clipped.'
);
$negativeRange = $normalizedSources;
$negativeRange[0]['Minimum'] = -20;
calendarPieAssert(
    CalendarPieData::NumericDays(
        $negativeRange,
        [701 => [[$stamp('2024-02-01'), -5]], 702 => [[$stamp('2024-02-01'), 0]]],
        $leap,
        true
    )[0]['slices'][0]['value'] === 25.0,
    'Negative temperature ranges must normalize to their explicit physical interval.'
);
$zeroRange = $normalizedSources;
$zeroRange[0]['Minimum'] = 10;
calendarPieAssert(
    CalendarPieData::NumericDays(
        $zeroRange,
        [701 => [[$stamp('2024-02-01'), 5]], 702 => [[$stamp('2024-02-01'), 0]]],
        $leap,
        true
    )[0]['status'] === 'zero',
    'A day with all zero normalized weights cannot create artificial equal slices.'
);
$partialNormalized = CalendarPieData::NumericDays($normalizedSources, [701 => [[$stamp('2024-02-01'), -5]]], $leap, true)[0];
calendarPieAssert($partialNormalized['status'] === 'partial' && $partialNormalized['missing'] === ['variable-702']
    && $partialNormalized['slices'][0]['originalValue'] === -5.0, 'Normalization must preserve partial days and their available raw values.');
foreach ([[0, 0], [2, 1], ['0', 100], [null, 100], [false, 100], [NAN, 100], [0, INF], [-PHP_FLOAT_MAX, PHP_FLOAT_MAX]] as [$minimum, $maximum]) {
    $invalid = $normalizedSources;
    $invalid[0]['Minimum'] = $minimum;
    $invalid[0]['Maximum'] = $maximum;
    calendarPieReject(static fn () => CalendarPieData::NumericDays($invalid, [], $leap, true), 'Invalid normalization range accepted.');
}
$inactiveRange = $normalizedSources;
$inactiveRange[0]['Minimum'] = 'inactive invalid value';
calendarPieAssert(
    CalendarPieData::NumericDays($inactiveRange, $points, $leap) === CalendarPieData::NumericDays($normalizedSources, $points, $leap),
    'Disabled normalization must preserve the original daily model and ignore inactive ranges.'
);
for ($count = 1; $count <= 5; $count++) {
    $selected = array_map(static fn (array $source): array => $source + ['Minimum' => 0, 'Maximum' => 10, 'Unit' => 'x', 'Decimals' => 2], array_slice($flexibleSources, 0, $count));
    $selectedPoints = array_intersect_key($flexiblePoints, array_flip(array_column($selected, 'VariableID')));
    calendarPieAssert(array_column(CalendarPieData::NumericDays($selected, $selectedPoints, $leap, true)[0]['slices'], 'value')
        === array_map(static fn (int $value): float => $value * 10.0, range(1, $count)), 'Every supported source count must normalize independently.');
}

$definitions = StateTimelineData::Definitions([['Value' => 0, 'Label' => 'Off', 'Color' => 0], ['Value' => 1, 'Label' => 'On', 'Color' => 0x00FF00]], true);
foreach ([$midnightDST, $havana, $samoa] as $civilWindow) {
    $civilStates = CalendarPieData::StateDays([], [$civilWindow['StartTimestamp'] - 1, 1], true, $definitions, $civilWindow);
    calendarPieAssert(
        array_sum(array_column($civilStates, 'daySeconds')) === $civilWindow['EndTimestamp'] - $civilWindow['StartTimestamp']
        && array_sum(array_map(static fn (array $day): int => array_sum(array_column($day['slices'], 'value')), $civilStates))
            === $civilWindow['EndTimestamp'] - $civilWindow['StartTimestamp'],
        'Daily state durations must partition every real second of historical months, including midnight and date-line transitions.'
    );
}
$stateDays = CalendarPieData::StateDays([[$stamp('2024-02-01 06:00:00'), 1], [$stamp('2024-02-01 12:00:00'), 0],
    [$stamp('2024-02-02'), 1], [$stamp('2024-03-01'), 0]], [$stamp('2024-01-31 23:00:00'), 0], true, $definitions, $leap);
calendarPieAssert(
    $stateDays[0]['status'] === 'complete' && array_sum(array_column($stateDays[0]['slices'], 'value')) === 86400,
    'A predecessor plus all state transitions must cover a completed local day exactly.'
);
$byID = array_column($stateDays[0]['slices'], null, 'id');
calendarPieAssert($byID['state-0']['value'] === 64800 && $byID['state-0']['color'] === '#000000'
    && $byID['state-1']['value'] === 21600, 'Repeated state durations aggregate without losing zero state or color.');
calendarPieAssert(
    $stateDays[1]['slices'] === [['id' => 'state-1', 'label' => 'On', 'value' => 86400, 'color' => '#00FF00']],
    'A transition exactly at midnight belongs to the following day.'
);
calendarPieAssert($stateDays[28]['slices'][0]['id'] === 'state-1', 'A point at the exclusive month end creates no time in the displayed month.');
$unknown = CalendarPieData::StateDays([[$stamp('2024-02-01 06:00:00'), -2]], null, false, [], $leap);
$unknownSlices = array_column($unknown[0]['slices'], null, 'id');
calendarPieAssert($unknown[0]['status'] === 'partial' && $unknownSlices['unknown']['value'] === 21600
    && $unknownSlices['state--2']['value'] === 64800 && $unknownSlices['state--2']['label'] === '-2'
    && $unknown[0]['missing'] === ['unknown'], 'Unknown prefixes and unmapped negative integer states stay explicit.');
$emptyStates = CalendarPieData::StateDays([], null, true, $definitions, $leap);
calendarPieAssert($emptyStates[0]['status'] === 'missing' && $emptyStates[0]['slices'][0]['id'] === 'unknown'
    && $emptyStates[0]['slices'][0]['value'] === 86400, 'An empty archive remains unknown, never current state or zero.');
$spring = CalendarPieData::Resolve('2024-03', 0, $now, 'Europe/Berlin');
foreach ([[$spring, 30, 82800], [$fall, 26, 90000]] as [$window, $index, $seconds]) {
    $result = CalendarPieData::StateDays([], [$window['StartTimestamp'] - 1, 1], true, $definitions, $window);
    calendarPieAssert(
        $result[$index]['daySeconds'] === $seconds && $result[$index]['slices'][0]['value'] === $seconds,
        'State proportions use actual 23/25-hour local-day durations.'
    );
}
calendarPieAssert(CalendarPieData::StateDays([], null, true, $definitions, $first)[0]['status'] === 'pending', 'Future state days have no fabricated durations.');
calendarPieReject(static fn () => CalendarPieData::StateDays([[$stamp('2024-02-01'), 2]], null, true, $definitions, $leap), 'Invalid Boolean state accepted.');
calendarPieReject(static fn () => CalendarPieData::StateDays([[$stamp('2024-02-01'), 1], [$stamp('2024-02-01'), 0]], null, true, $definitions, $leap), 'Conflicting state timestamp accepted.');
calendarPieReject(static fn () => CalendarPieData::StateDays(array_fill(0, 2001, [$stamp('2024-02-01'), 0]), null, true, $definitions, $leap), 'Truncated state point budget accepted.');
$manyStates = [];
for ($index = 0; $index < 33; $index++) {
    $manyStates[] = [$stamp('2024-02-01') + $index, $index];
}
calendarPieReject(static fn () => CalendarPieData::StateDays($manyStates, null, false, [], $leap), 'More than 32 visible states accepted.');

// Display is a policy only: partial days keep their missing IDs and original slices.
$partialDay = ['status' => 'partial', 'slices' => [['value' => 2.0]], 'missing' => ['variable-2']];
calendarPieAssert(!CalendarPieData::ShowsPie($partialDay, 'average'), 'Strict numeric default must not draw partial days.');
foreach (['average', 'counter'] as $mode) {
    calendarPieAssert(CalendarPieData::ShowsPie($partialDay, $mode, true), 'Opt-in must draw available positive slices.');
    calendarPieAssert(!CalendarPieData::ShowsPie([...$partialDay, 'slices' => [['value' => 0.0]]], $mode, true), 'Partial zero weights must not invent a circle.');
    foreach (['missing', 'pending', 'zero'] as $status) {
        calendarPieAssert(!CalendarPieData::ShowsPie([...$partialDay, 'status' => $status], $mode, true), 'Opt-in must not draw missing, pending or zero days.');
    }
}
foreach ([$unknown[0], $emptyStates[0]] as $day) {
    calendarPieAssert(CalendarPieData::ShowsPie($day, 'states') === CalendarPieData::ShowsPie($day, 'states', true)
        && CalendarPieData::ShowsPie($day, 'states'), 'Unknown state duration keeps the actual daily denominator regardless of the numeric option.');
}

echo "CalendarPie completed months, numeric slices and state durations verified.\n";
