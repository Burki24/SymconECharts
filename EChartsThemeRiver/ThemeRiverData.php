<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';

/** Completed local archive intervals and strict, nonnegative common values. */
final class ThemeRiverData
{
    public const MAXIMUM_SOURCE_COUNT = 16;
    public const MAXIMUM_AGGREGATES_PER_SOURCE = 1000;
    public const MAXIMUM_LOADED_AGGREGATES = 16000;

    /** @return array{reducer:string,archiveType:string} */
    public static function ArchiveContract(string $metric): array
    {
        return match ($metric) {
            'average'     => ['reducer' => 'average', 'archiveType' => 'standard'],
            'consumption' => ['reducer' => 'sum', 'archiveType' => 'counter'],
            default       => throw new InvalidArgumentException('The ThemeRiver metric is invalid.')
        };
    }

    /** Count received aggregates before joining or discarding any missing intervals. */
    public static function CountLoadedAggregates(int $loaded, int $next): int
    {
        if ($loaded < 0 || $next < 0 || $next > self::MAXIMUM_AGGREGATES_PER_SOURCE
            || $loaded > self::MAXIMUM_LOADED_AGGREGATES - $next) {
            throw new InvalidArgumentException('The ThemeRiver archive load budget was exceeded.');
        }
        return $loaded + $next;
    }

    public static function Window(string $range, string $interval, int $now, string $timezone, int $customRangeValue = 7, string $customRangeUnit = 'day'): array
    {
        return EChartsArchiveQuery::CompletedIntervals($range, $customRangeValue, $customRangeUnit, $interval, $now, $timezone);
    }

    /** Missing values and a source's first partial aggregate leave shared gaps, never zero. */
    public static function Join(array $series, array $intervals): array
    {
        if (!array_is_list($series) || count($series) < 1 || count($series) > self::MAXIMUM_SOURCE_COUNT
            || !array_is_list($intervals) || count($intervals) > 720) {
            throw new InvalidArgumentException('Invalid ThemeRiver data size.');
        }
        $allowed = array_fill_keys(array_column($intervals, 'startTimestamp'), true);
        $indexed = [];
        foreach ($series as $points) {
            if (!is_array($points) || !array_is_list($points) || count($points) > count($intervals)) {
                throw new InvalidArgumentException('Invalid ThemeRiver archive points.');
            }
            $index = [];
            $previous = 0;
            foreach ($points as $point) {
                if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                    || !is_int($point[0]) || $point[0] <= $previous
                    || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1]) || $point[1] < 0) {
                    throw new InvalidArgumentException('ThemeRiver archive values must be nonnegative, finite and strictly ordered at exact completed interval starts.');
                }
                if (!isset($allowed[$point[0]])) {
                    // Symcon timestamps the first partial aggregate at the actual archive start.
                    // Do not shift it to a slot boundary or use its potentially misleading total.
                    $partial = false;
                    if ($previous === 0) {
                        foreach ($intervals as $interval) {
                            if ($point[0] > $interval['startTimestamp'] && $point[0] <= $interval['endTimestamp']) {
                                $partial = true;
                                break;
                            }
                        }
                    }
                    if (!$partial) {
                        throw new InvalidArgumentException('ThemeRiver archive values must be nonnegative, finite and strictly ordered at exact completed interval starts.');
                    }
                } else {
                    $index[$point[0]] = (float) $point[1];
                }
                $previous = $point[0];
            }
            $indexed[] = $index;
        }
        $rows = [];
        $missing = 0;
        foreach ($intervals as $interval) {
            $values = [];
            foreach ($indexed as $index) {
                if (!array_key_exists($interval['startTimestamp'], $index)) {
                    $values = null;
                    ++$missing;
                    break;
                }
                $values[] = $index[$interval['startTimestamp']];
            }
            $rows[] = [...$interval, 'values' => $values];
        }
        return ['rows' => $rows, 'missingIntervals' => $missing];
    }
}
