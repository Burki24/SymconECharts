<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsStateTimeline/StateTimelineData.php';

use SymconECharts\StateTimelineData;

function timelineAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$segments = StateTimelineData::Segments([[150, 1], [170, 1], [190, 0]], null, 100, 200);
timelineAssert($segments === [
    ['startTimestamp' => 100, 'endTimestamp' => 150, 'value' => null],
    ['startTimestamp' => 150, 'endTimestamp' => 190, 'value' => 1],
    ['startTimestamp' => 190, 'endTimestamp' => 200, 'value' => 0]
], 'Unknown initial state and repeated samples must produce exact half-open intervals.');
timelineAssert(StateTimelineData::Segments([], [90, 0], 100, 200) === [
    ['startTimestamp' => 100, 'endTimestamp' => 200, 'value' => 0]
], 'A predecessor must carry into a window without changes.');
timelineAssert(StateTimelineData::Segments([[100, 1], [200, 0]], [90, 0], 100, 200) === [
    ['startTimestamp' => 100, 'endTimestamp' => 200, 'value' => 1]
], 'Left boundary supersedes predecessor; right boundary must not create a zero-width interval.');
timelineAssert(StateTimelineData::Segments([], null, 100, 200) === [
    ['startTimestamp' => 100, 'endTimestamp' => 200, 'value' => null]
], 'Empty archives must be visibly unknown rather than silently zero.');
timelineAssert(
    count(StateTimelineData::Segments([[100, -5], [101, 0], [102, 5]], null, 100, 103)) === 3,
    'Negative integer states and real zero must remain distinct.'
);
timelineAssert(
    count(StateTimelineData::Segments([[150, 1], [150, 1]], null, 100, 200)) === 2,
    'Identical duplicate samples must be deterministic.'
);
foreach ([[[99, 1]], [[201, 1]], [[160, 1], [150, 0]], [[150, 1], [150, 0]], [[150, 1.5]], [[150, true]], [[150, INF]], [[150, '1']]] as $points) {
    $thrown = false;
    try {
        StateTimelineData::Segments($points, null, 100, 200);
    } catch (InvalidArgumentException) {
        $thrown = true;
    }
    timelineAssert($thrown, 'Malformed, conflicting or out-of-window points must be rejected.');
}
foreach ([[100, 0], [0, 1], [90, 2.5]] as $previous) {
    try {
        StateTimelineData::Segments([], $previous, 100, 200);
        throw new RuntimeException('Invalid predecessor was accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach (['24h' => 86400, '7d' => 604800, '30d' => 2592000] as $range => $seconds) {
    $now = (new DateTimeImmutable('2026-10-25 12:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp();
    $window = StateTimelineData::Window($range, $now);
    timelineAssert(
        $window === ['StartTimestamp' => $now - $seconds, 'EndTimestamp' => $now],
        'Ranges are exact elapsed durations including DST.'
    );
}
$now = (new DateTimeImmutable('2026-10-25 12:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp();
foreach ([[90, 'minute', 5400], [2, 'week', 1209600], [1000, 'day', 86400000]] as [$value, $unit, $seconds]) {
    timelineAssert(StateTimelineData::Window('custom', $now, $value, $unit) === [
        'StartTimestamp' => $now - $seconds, 'EndTimestamp' => $now
    ], 'Custom state windows must use exact elapsed durations up to the current second, including DST.');
}
foreach ([[0, 'day'], [1001, 'minute'], [7, 'month']] as [$value, $unit]) {
    try {
        StateTimelineData::Window('custom', $now, $value, $unit);
        throw new RuntimeException('Invalid custom state window was accepted.');
    } catch (InvalidArgumentException) {
    }
}
timelineAssert(
    StateTimelineData::Window('7d', $now, 0, 'invalid')['StartTimestamp'] === $now - 604800,
    'Inactive custom settings must not invalidate a saved preset.'
);
foreach ([[[100, 2]], [[100, -1]]] as $points) {
    try {
        StateTimelineData::Segments($points, null, 100, 200, true);
        throw new RuntimeException('Invalid Boolean state was accepted.');
    } catch (InvalidArgumentException) {
    }
}
timelineAssert(
    StateTimelineData::Definitions([['Value' => -2, 'Label' => 'Signed', 'Color' => 0]], false)[-2]['Color'] === 0,
    'Negative Integer state and explicitly black color must survive.'
);
foreach ([null, [['Value'                                                       => 0, 'Label' => '']], [['Value' => 0, 'Label' => 'x', 'Color' => 0x1000000]],
    [['Value' => 0, 'Label' => 'A'], ['Value' => 0, 'Label' => 'B']], [['Value' => 1.5, 'Label' => 'Fraction']]] as $definitions) {
    try {
        StateTimelineData::Definitions($definitions, false);
        throw new RuntimeException('Invalid state definitions were accepted.');
    } catch (InvalidArgumentException) {
    }
}
echo "StateTimeline windows and discrete intervals verified.\n";
