<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';

/** Completed local archive intervals and strict, nonnegative common means. */
final class ThemeRiverData
{
    public static function Window(string $range, string $interval, int $now, string $timezone, int $customRangeValue = 7, string $customRangeUnit = 'day'): array
    {
        return EChartsArchiveQuery::CompletedIntervals($range, $customRangeValue, $customRangeUnit, $interval, $now, $timezone);
    }

    /** A missing value on any source makes the whole interval unavailable, never zero. */
    public static function Join(array $series, array $intervals): array
    {
        if (!array_is_list($series) || count($series) < 1 || count($series) > 8
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
                    || !is_int($point[0]) || $point[0] <= $previous || !isset($allowed[$point[0]])
                    || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1]) || $point[1] < 0) {
                    throw new InvalidArgumentException('ThemeRiver archive means must be nonnegative, finite and strictly ordered at exact completed interval starts.');
                }
                $index[$point[0]] = (float) $point[1];
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
