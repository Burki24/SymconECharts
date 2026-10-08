<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Pure archive-query planning shared by historical Cartesian charts.
 */
final class EChartsArchiveQuery
{
    public const RANGE_SECONDS = [
        '1h'  => 3600,
        '6h'  => 21600,
        '24h' => 86400,
        '7d'  => 604800,
        '30d' => 2592000
    ];
    public const CALENDAR_RANGES = ['today', 'yesterday', 'current-week', 'current-month'];
    public const CUSTOM_RANGE_UNITS = [
        'minute' => 60,
        'hour'   => 3600,
        'day'    => 86400,
        'week'   => 604800
    ];
    public const AGGREGATION_LEVELS = [
        'minute'          => 6,
        'five-minutes'    => 5,
        'fifteen-minutes' => 8,
        'hour'            => 0,
        'day'             => 1
    ];
    public const AGGREGATION_SECONDS = [6 => 60, 5 => 300, 8 => 900, 0 => 3600, 1 => 86400];

    /**
     * @return array{DurationSeconds:int,StartTimestamp:int,EndTimestamp:int,Mode:string,AggregationLevel:int|null,Limit:int,CalendarAligned:bool,AcceptLiveUpdates:bool,Empty:bool}
     */
    public static function Resolve(
        string $range,
        int $customRangeValue,
        string $customRangeUnit,
        string $mode,
        int $pointBudget,
        int $sourceCount,
        int $now,
        string $timezone
    ): array {
        if (!self::IsValidRange($range, $customRangeValue, $customRangeUnit)
            || !in_array($mode, ['realtime', 'raw', 'auto', ...array_keys(self::AGGREGATION_LEVELS)], true)
            || $pointBudget < 1 || $sourceCount < 1 || $now < 1
        ) {
            throw new InvalidArgumentException('The archive query settings are invalid.');
        }

        $window = self::ResolveWindow($range, $customRangeValue, $customRangeUnit, $now, $timezone);
        $limit = max(1, min(2000, intdiv($pointBudget, $sourceCount)));
        $level = null;
        if (!in_array($mode, ['raw', 'realtime'], true)) {
            if ($mode === 'auto') {
                foreach (self::AGGREGATION_SECONDS as $candidate => $seconds) {
                    $level = $candidate;
                    if ((int) ceil($window['DurationSeconds'] / $seconds) <= $limit) {
                        break;
                    }
                }
            } else {
                $level = self::AGGREGATION_LEVELS[$mode];
            }
        }

        $end = $window['EndTimestamp'];
        if ($level !== null) {
            $completedBoundary = self::AggregationWindowStart($level, $now, $timezone);
            $end = min($window['EndTimestamp'] + 1, $completedBoundary) - 1;
        }
        $start = $window['CalendarAligned']
            ? $window['StartTimestamp']
            : $end - $window['DurationSeconds'] + ($level === null ? 0 : 1);
        $empty = $end < $start;

        return [
            'DurationSeconds'   => $window['DurationSeconds'],
            'StartTimestamp'    => max(1, $start),
            'EndTimestamp'      => max(1, $empty ? $window['EndTimestamp'] : $end),
            'Mode'              => $mode === 'realtime' ? 'realtime' : ($level === null ? 'raw' : 'aggregated'),
            'AggregationLevel'  => $level,
            'Limit'             => $limit,
            'CalendarAligned'   => $window['CalendarAligned'],
            'AcceptLiveUpdates' => $window['AcceptLiveUpdates'] && in_array($mode, ['raw', 'realtime'], true),
            'Empty'             => $empty
        ];
    }

    public static function IsValidRange(string $range, int $customRangeValue, string $customRangeUnit): bool
    {
        return (array_key_exists($range, self::RANGE_SECONDS)
                || in_array($range, self::CALENDAR_RANGES, true)
                || $range === 'custom')
            && ($range !== 'custom'
                || ($customRangeValue >= 1 && $customRangeValue <= 1000
                    && array_key_exists($customRangeUnit, self::CUSTOM_RANGE_UNITS)));
    }

    public static function AggregationWindowStart(int $level, int $timestamp, string $timezone): int
    {
        $local = (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone($timezone));
        if ($level === 1) {
            return $local->setTime(0, 0)->getTimestamp();
        }
        if ($level === 0) {
            return $local->setTime((int) $local->format('G'), 0)->getTimestamp();
        }

        $minutes = intdiv(self::AGGREGATION_SECONDS[$level] ?? 60, 60);
        $minute = intdiv((int) $local->format('i'), $minutes) * $minutes;

        return $local->setTime((int) $local->format('G'), $minute)->getTimestamp();
    }

    /** @return array{DurationSeconds:int,StartTimestamp:int,EndTimestamp:int,CalendarAligned:bool,AcceptLiveUpdates:bool} */
    private static function ResolveWindow(
        string $range,
        int $customRangeValue,
        string $customRangeUnit,
        int $now,
        string $timezone
    ): array {
        if (!in_array($range, self::CALENDAR_RANGES, true)) {
            $duration = self::RANGE_SECONDS[$range]
                ?? $customRangeValue * self::CUSTOM_RANGE_UNITS[$customRangeUnit];

            return [
                'DurationSeconds'   => $duration,
                'StartTimestamp'    => max(1, $now - $duration),
                'EndTimestamp'      => $now,
                'CalendarAligned'   => false,
                'AcceptLiveUpdates' => true
            ];
        }

        $localNow = (new DateTimeImmutable('@' . $now))->setTimezone(new DateTimeZone($timezone));
        $today = $localNow->setTime(0, 0);
        $start = match ($range) {
            'today'         => $today,
            'yesterday'     => $today->modify('-1 day'),
            'current-week'  => $today->modify('monday this week'),
            'current-month' => $today->modify('first day of this month')
        };
        $end = $range === 'yesterday' ? $today->getTimestamp() - 1 : $now;

        return [
            'DurationSeconds'   => max(1, $end - $start->getTimestamp() + 1),
            'StartTimestamp'    => $start->getTimestamp(),
            'EndTimestamp'      => $end,
            'CalendarAligned'   => true,
            'AcceptLiveUpdates' => $range !== 'yesterday'
        ];
    }
}
