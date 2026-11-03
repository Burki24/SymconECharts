<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsCandlestick/CandlestickData.php';

use SymconECharts\CandlestickData;
use SymconECharts\EChartsArchiveQuery;

(static function (): void
{
    $check = static function (bool $ok, string $message): void
    {
        if (!$ok) {
            throw new RuntimeException($message);
        }
    };
    $zone = 'Europe/Berlin';
    $ts = static fn (string $date): int => (new DateTimeImmutable($date, new DateTimeZone($zone)))->getTimestamp();
    $now = $ts('2026-10-22 12:43:27');
    foreach (['fifteen-minutes' => [900, 96, '12:30'], 'thirty-minutes' => [1800, 48, '12:30']] as $interval => [$seconds, $count, $endTime]) {
        $window = CandlestickData::Window('24h', $interval, $now, $zone);
        $check(
            count($window['Intervals']) === $count && $window['EndTimestamp'] === $ts('2026-10-22 ' . $endTime) - 1,
            'Minute windows end at the last completed local interval.'
        );
        $check($window['AggregationLevel'] === null, 'New raw intervals must not pretend to be Symcon aggregates.');
        foreach ($window['Intervals'] as $index => $item) {
            $check(
                $item['endTimestamp'] - $item['startTimestamp'] + 1 === $seconds
                && ($index === 0 || $window['Intervals'][$index - 1]['endTimestamp'] + 1 === $item['startTimestamp']),
                'Minute intervals are disjoint, complete and contiguous.'
            );
        }
        $empty = CandlestickData::Window('custom', $interval, $now, $zone, 1, 'minute');
        $check($empty['Empty'] && $empty['Intervals'] === [], 'Short durations must not invent a minute candle.');
        $from = $ts('2026-10-12 08:07:00');
        $until = $ts('2026-10-12 10:07:00');
        $fixed = EChartsArchiveQuery::AbsoluteIntervals($from, $until, $interval, $now, $zone);
        $check($fixed['StartTimestamp'] === $ts('2026-10-12 ' . ($seconds === 900 ? '08:15' : '08:30'))
            && $fixed['EndTimestamp'] === $ts('2026-10-12 10:00') - 1
            && count($fixed['Intervals']) === ($seconds === 900 ? 7 : 3), 'Historical minute windows exclude partial leading/trailing intervals.');
        $start = $fixed['StartTimestamp'];
        $data = CandlestickData::Summarize([[$start, 2], [$start + 1, -4], [$start + 2, 9], [$start + 3, 3], [$start + 2 * $seconds, 0]], $fixed);
        $check($data[0]['statistics'] === [2.0, 3.0, -4.0, 9.0] && $data[1]['statistics'] === null
            && $data[2]['statistics'] === [0.0, 0.0, 0.0, 0.0], 'New candles retain true OCLH, gaps, negative values and single zero samples.');
        foreach ([720, 721] as $n) {
            try {
                $bounded = EChartsArchiveQuery::AbsoluteIntervals($start, $start + $n * $seconds, $interval, $ts('2026-11-01'), $zone);
                $check($n === 720 && count($bounded['Intervals']) === 720, 'Exactly 720 minute intervals must fit.');
            } catch (InvalidArgumentException $exception) {
                $check($n === 721 && str_contains($exception->getMessage(), 'Too many archive intervals'), '721 intervals fail with the existing budget error.');
            }
        }
        foreach ([['2026-03-29', '2026-03-30', 23], ['2026-10-25', '2026-10-26', 25]] as [$fromDate, $untilDate, $hours]) {
            $dst = EChartsArchiveQuery::AbsoluteIntervals($ts($fromDate), $ts($untilDate), $interval, $ts('2026-11-01'), $zone);
            $labels = array_column($dst['Intervals'], 'label');
            $check(
                count($labels) === $hours * intdiv(3600, $seconds) && count(array_unique($labels)) === count($labels),
                'Minute windows retain real DST intervals with unique offset labels.'
            );
        }
    }
    $week = CandlestickData::Window('7d', 'week', $now, $zone);
    $check(count($week['Intervals']) === 1 && $week['StartTimestamp'] === $ts('2026-10-12')
        && $week['EndTimestamp'] === $ts('2026-10-19') - 1, 'Weeks run Monday to Monday; the current week is excluded.');
    $check(CandlestickData::Window('24h', 'week', $now, $zone)['Empty']
        && count(CandlestickData::Window('30d', 'week', $now, $zone)['Intervals']) === 4, 'Week presets include only complete calendar weeks.');
    $fixed = EChartsArchiveQuery::AbsoluteIntervals($ts('2026-09-30 08:00'), $ts('2026-10-21 08:00'), 'week', $now, $zone);
    $check(array_column($fixed['Intervals'], 'label') === ['2026-W41', '2026-W42']
        && $fixed['EndTimestamp'] === $ts('2026-10-19') - 1, 'Weekly historical windows exclude incomplete weeks and label ISO weeks.');
    foreach ([['2026-03-23', '2026-03-30', 167], ['2026-10-19', '2026-10-26', 169]] as [$from, $until, $hours]) {
        $dst = EChartsArchiveQuery::AbsoluteIntervals($ts($from), $ts($until), 'week', $ts('2026-11-01'), $zone);
        $check(
            count($dst['Intervals']) === 1 && $dst['EndTimestamp'] - $dst['StartTimestamp'] + 1 === $hours * 3600,
            'Calendar weeks keep their true 167/169-hour DST duration.'
        );
        $preset = CandlestickData::Window('7d', 'week', $ts($until . ' 12:00'), $zone);
        $check(count($preset['Intervals']) === 1, 'Seven-day weekly presets count local dates across DST.');
    }
    $check(
        CandlestickData::Window('custom', 'week', $ts('2026-10-26 12:00'), $zone, 1, 'week')['Empty'],
        'Custom weeks remain exactly 168 elapsed hours, not an implicit calendar duration.'
    );
    $iso = EChartsArchiveQuery::AbsoluteIntervals($ts('2025-12-29'), $ts('2026-01-05'), 'week', $now, $zone);
    $check($iso['Intervals'][0]['label'] === '2026-W01', 'Week labels use the ISO week year across New Year.');
    foreach ([720, 721] as $n) {
        try {
            $until = (new DateTimeImmutable('2010-01-04', new DateTimeZone($zone)))->modify('+' . $n . ' weeks')->getTimestamp();
            $many = EChartsArchiveQuery::AbsoluteIntervals($ts('2010-01-04'), $until, 'week', $now, $zone);
            $check($n === 720 && count($many['Intervals']) === 720, '720 weekly intervals are allowed.');
        } catch (InvalidArgumentException $exception) {
            $check($n === 721 && str_contains($exception->getMessage(), 'Too many archive intervals'), 'Weekly interval budgets remain bounded.');
        }
    }
    foreach (['fifteen-minutes', 'thirty-minutes'] as $interval) {
        $halfHourDST = EChartsArchiveQuery::AbsoluteIntervals(
            (new DateTimeImmutable('2026-10-04 00:00 Australia/Lord_Howe'))->getTimestamp(),
            (new DateTimeImmutable('2026-10-04 04:00 Australia/Lord_Howe'))->getTimestamp(),
            $interval,
            $now,
            'Australia/Lord_Howe'
        );
        $check(count($halfHourDST['Intervals']) === ($interval === 'fifteen-minutes' ? 14 : 7), 'Aligned half-hour DST changes remain complete minute intervals.');
        $kathmandu = EChartsArchiveQuery::AbsoluteIntervals(
            (new DateTimeImmutable('2026-10-12 08:07 Asia/Kathmandu'))->getTimestamp(),
            (new DateTimeImmutable('2026-10-12 10:07 Asia/Kathmandu'))->getTimestamp(),
            $interval,
            $now,
            'Asia/Kathmandu'
        );
        $check(
            $kathmandu['Intervals'][0]['label'] === '2026-10-12 ' . ($interval === 'fifteen-minutes' ? '08:15' : '08:30') . ' +05:45',
            'Minute grids use local wall-clock boundaries, not UTC modulo rounding.'
        );
        try {
            EChartsArchiveQuery::AbsoluteIntervals(
                (new DateTimeImmutable('1985-12-31 22:00 Asia/Kathmandu'))->getTimestamp(),
                (new DateTimeImmutable('1986-01-01 02:00 Asia/Kathmandu'))->getTimestamp(),
                $interval,
                $now,
                'Asia/Kathmandu'
            );
            $check($interval === 'fifteen-minutes', 'A 15-minute offset transition cannot form full half-hour slots.');
        } catch (InvalidArgumentException $exception) {
            $check(
                $interval === 'thirty-minutes' && str_contains($exception->getMessage(), 'non-aligned timezone changes'),
                'Unaligned historical transitions fail explicitly instead of inventing partial intervals.'
            );
        }
    }
    echo "Candlestick minute/week interval planning verified.\n";
})();
