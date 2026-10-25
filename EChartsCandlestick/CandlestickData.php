<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';

/** Family-local, bounded raw archive reading and OCLH calculation. */
final class CandlestickData
{
    public static function Window(string $range, string $interval, int $now, string $timezone, int $customRangeValue = 7, string $customRangeUnit = 'day'): array
    {
        return EChartsArchiveQuery::CompletedIntervals($range, $customRangeValue, $customRangeUnit, $interval, $now, $timezone);
    }

    /** Discard truncated fragments; bisect disjoint inclusive windows instead. */
    public static function Read(int $variableID, int $start, int $end, callable $fetch): array
    {
        return self::ReadMany([$variableID], $start, $end, $fetch)[$variableID];
    }

    /** All panels share one query/retained-sample budget; no partial model escapes. */
    public static function ReadMany(array $variableIDs, int $start, int $end, callable $fetch): array
    {
        if (!array_is_list($variableIDs) || count($variableIDs) < 1 || count($variableIDs) > 4
            || count(array_unique($variableIDs, SORT_REGULAR)) !== count($variableIDs)
            || array_filter($variableIDs, static fn ($id): bool => !is_int($id) || $id <= 0) !== []) {
            throw new InvalidArgumentException('Configure one to four unique numeric Candlestick panel variables.');
        }
        $calls = 0;
        $retained = 0;
        $result = [];
        foreach ($variableIDs as $variableID) {
            $points = [];
            $read = static function (int $from, int $to) use (&$read, &$calls, &$retained, &$points, $variableID, $fetch): void
            {
                if (++$calls > 63) {
                    throw new RuntimeException('Candlestick archive is too dense; choose a shorter range.');
                }
                $request = ['VariableID' => $variableID, 'StartTimestamp' => $from, 'EndTimestamp' => $to,
                    'Mode'               => 'raw', 'Reducer' => 'auto', 'Limit' => 2000];
                $data = $fetch($request);
                if (($data['ArchiveAggregationType'] ?? null) === 'counter') {
                    throw new RuntimeException('Candlestick requires standard archive variables, not counters.');
                }
                if (($data['VariableID'] ?? null) !== $variableID || !in_array($data['VariableType'] ?? null, [1, 2], true)
                    || ($data['StartTimestamp'] ?? null) !== $from || ($data['EndTimestamp'] ?? null) !== $to
                    || ($data['EffectiveReducer'] ?? null) !== 'raw' || ($data['ArchiveAggregationType'] ?? null) !== 'standard'
                    || !is_bool($data['Truncated'] ?? null) || !is_array($data['Points'] ?? null)
                    || !array_is_list($data['Points']) || count($data['Points']) > 2000) {
                    throw new RuntimeException('Invalid Candlestick raw archive response.');
                }
                self::ValidatePoints($data['Points'], $from, $to);
                if ($data['Truncated']) {
                    if ($to - $from < 3) {
                        throw new RuntimeException('Candlestick archive is too dense; choose a shorter range.');
                    }
                    $middle = $from + intdiv($to - $from, 2);
                    $read($from, $middle);
                    $read($middle + 1, $to);
                    return;
                }
                if ($retained + count($data['Points']) > 64000) {
                    throw new RuntimeException('Candlestick archive is too dense; choose a shorter range.');
                }
                $retained += count($data['Points']);
                array_push($points, ...$data['Points']);
            };
            $read($start, $end);
            $result[$variableID] = $points;
        }
        return $result;
    }

    public static function Summarize(array $points, array $window): array
    {
        self::ValidatePoints($points, $window['StartTimestamp'], $window['EndTimestamp']);
        $result = [];
        $index = 0;
        foreach ($window['Intervals'] as $interval) {
            $values = null;
            $count = 0;
            $first = null;
            $last = null;
            while (isset($points[$index]) && $points[$index][0] <= $interval['endTimestamp']) {
                [$time, $value] = $points[$index++];
                $value = (float) $value;
                $values = $values === null ? [$value, $value, $value, $value]
                    : [$values[0], $value, min($values[2], $value), max($values[3], $value)];
                $first ??= $time;
                $last = $time;
                ++$count;
            }
            $result[] = ['id' => (string) $interval['startTimestamp'], ...$interval,
                'statistics'  => $values, 'count' => $count, 'firstTimestamp' => $first, 'lastTimestamp' => $last];
        }
        return $result;
    }

    private static function ValidatePoints(array $points, int $start, int $end): void
    {
        if (!array_is_list($points) || count($points) > 64000) {
            throw new InvalidArgumentException('Invalid Candlestick samples.');
        }
        $previous = 0;
        foreach ($points as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] < $start || $point[0] > $end || $point[0] <= $previous
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])) {
                throw new InvalidArgumentException('Invalid Candlestick samples.');
            }
            $previous = $point[0];
        }
    }
}
