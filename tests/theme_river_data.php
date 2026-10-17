<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsThemeRiver/ThemeRiverData.php';

use SymconECharts\ThemeRiverData;

function assertRiver(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$window = ThemeRiverData::Window('24h', 'hour', strtotime('2026-10-17T12:34:56+02:00'), 'Europe/Berlin');
assertRiver(count($window['Intervals']) === 24 && $window['EndTimestamp'] === strtotime('2026-10-17T12:00:00+02:00') - 1, 'Only completed hours belong to the window.');
$slots = $window['Intervals'];
$t = array_column($slots, 'startTimestamp');
$data = ThemeRiverData::Join([
    [[$t[0], 0], [$t[1], 10], [$t[3], 30]],
    [[$t[0], 5], [$t[1], 20], [$t[2], 2], [$t[3], 40]]
], $slots);
assertRiver($data['rows'][0]['values'] === [0.0, 5.0] && $data['rows'][1]['values'] === [10.0, 20.0]
    && $data['rows'][2]['values'] === null && $data['rows'][3]['values'] === [30.0, 40.0]
    && $data['missingIntervals'] === 21, 'Join must preserve real zero, use exact common timestamps and mark gaps without zero filling.');
foreach ([
    [[[$t[0], -1]]], [[[$t[0], NAN]]], [[[$t[0], INF]]], [[[$t[0], '1']]],
    [[[$t[0] + 1, 1]]], [[[$t[1], 1], [$t[0], 2]]], [[[$t[0], 1], [$t[0], 2]]],
    [[[$t[0] - 3600, 1]]], [[[$t[0], 1, 2]]]
] as $bad) {
    try {
        ThemeRiverData::Join($bad, $slots);
        throw new RuntimeException('Invalid points were accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach (['2026-03-30T12:00:00+02:00' => 23, '2026-10-26T12:00:00+01:00' => 25] as $date => $hours) {
    $day = ThemeRiverData::Window('24h', 'day', strtotime($date), 'Europe/Berlin');
    assertRiver(count($day['Intervals']) === 1 && $day['EndTimestamp'] - $day['StartTimestamp'] + 1 === $hours * 3600, 'Local daily intervals must honor DST.');
}
$repeated = ThemeRiverData::Window('24h', 'hour', strtotime('2026-10-25T02:30:00+01:00'), 'Europe/Berlin');
assertRiver($repeated['EndTimestamp'] === strtotime('2026-10-25T02:00:00+01:00') - 1, 'Repeated hour must retain the actual UTC offset.');
assertRiver(count(ThemeRiverData::Window('30d', 'hour', strtotime('2026-10-26T12:00:00+01:00'), 'Europe/Berlin')['Intervals']) === 720, 'Maximum hourly window must remain bounded.');
assertRiver(count(ThemeRiverData::Window('30d', 'day', strtotime('2026-10-26T12:00:00+01:00'), 'Europe/Berlin')['Intervals']) === 30, 'Daily windows count local days.');
echo "ThemeRiver data verified.\n";
