<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

/** Family-local, bounded raw archive reading and OCLH calculation. */
final class CandlestickData
{
    public static function Window(string $range, string $interval, int $now, string $timezone): array
    {
        $days = match ($range) {
            '24h'   => 1, '7d' => 7, '30d' => 30,
            default => throw new InvalidArgumentException('Invalid Candlestick range.')
        };
        $zone = new DateTimeZone($timezone);
        $local = (new DateTimeImmutable('@' . $now))->setTimezone($zone);
        if ($interval === 'day') {
            $end = $local->setTime(0, 0);
            $cursor = $end->modify('-' . $days . ' days');
        } elseif ($interval === 'hour') {
            // Subtract elapsed minutes to retain the actual offset in a repeated hour.
            $end = (new DateTimeImmutable('@' . ($now - (int) $local->format('i') * 60 - (int) $local->format('s'))))->setTimezone($zone);
            $cursor = $end->setTimestamp($end->getTimestamp() - $days * 86400);
        } else {
            throw new InvalidArgumentException('Invalid Candlestick interval.');
        }
        $start = $cursor->getTimestamp();
        $intervals = [];
        while ($cursor < $end) {
            $next = $interval === 'day' ? $cursor->modify('+1 day') : $cursor->setTimestamp($cursor->getTimestamp() + 3600);
            $intervals[] = ['startTimestamp' => $cursor->getTimestamp(), 'endTimestamp' => $next->getTimestamp() - 1,
                'label'                      => $cursor->format($interval === 'day' ? 'Y-m-d' : 'Y-m-d H:i P')];
            $cursor = $next;
        }
        return ['StartTimestamp' => $start, 'EndTimestamp' => $end->getTimestamp() - 1, 'Intervals' => $intervals];
    }

    /** Discard truncated fragments; bisect disjoint inclusive windows instead. */
    public static function Read(int $variableID, int $start, int $end, callable $fetch): array
    {
        $calls = 0;
        $points = [];
        $read = static function (int $from, int $to) use (&$read, &$calls, &$points, $variableID, $fetch): void
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
            if (count($points) + count($data['Points']) > 64000) {
                throw new RuntimeException('Candlestick archive is too dense; choose a shorter range.');
            }
            array_push($points, ...$data['Points']);
        };
        $read($start, $end);
        return $points;
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
