<?php

declare(strict_types=1);

namespace eCharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class EChartsArchiveStack
{
    /** @param array<int,string> $units */
    public static function ValidateGroup(mixed $group, string $unit, string $mode, array &$units, bool $ownAxis = false): int
    {
        if (!is_int($group) || $group < 0 || $group > 32) {
            throw new InvalidArgumentException('The stack group must be a whole number between 0 and 32.');
        }
        if ($group === 0) {
            return 0;
        }
        if ($ownAxis) {
            throw new InvalidArgumentException('A stacked series must use the shared value axis.');
        }
        if (in_array($mode, ['raw', 'realtime'], true)) {
            throw new InvalidArgumentException('Stack groups require automatic or fixed aggregation.');
        }
        $units[$group] ??= $unit;
        if ($units[$group] !== $unit) {
            throw new InvalidArgumentException('All sources in a stack group must use the same unit.');
        }
        return $group;
    }

    /**
     * One break per completely unobserved span, even for a long custom range.
     * Partial intervals are handled by the renderer using the original observations.
     *
     * @param list<array<string,mixed>> $series
     * @return array<int,list<int>> Unix timestamps in seconds keyed by stack group
     */
    public static function GapTimestamps(array $series, int $level, string $timezone): array
    {
        $timestamps = [];
        foreach ($series as $item) {
            if (!isset($item['stackGroup'])) {
                continue;
            }
            foreach ($item['points'] as $point) {
                $timestamps[$item['stackGroup']][$point[0]] = true;
            }
        }
        $gaps = [];
        $zone = new DateTimeZone($timezone);
        foreach ($timestamps as $group => $observed) {
            $times = array_keys($observed);
            sort($times, SORT_NUMERIC);
            for ($i = 1, $count = count($times); $i < $count; ++$i) {
                $previous = $times[$i - 1];
                // A local day can contain 23 or 25 hours. Subdaily buckets are elapsed time.
                $next = $level === 1
                    ? (new DateTimeImmutable('@' . $previous))->setTimezone($zone)->modify('+1 day')->getTimestamp()
                    : $previous + EChartsArchiveQuery::AGGREGATION_SECONDS[$level];
                if ($next < $times[$i]) {
                    $gaps[$group][] = $next;
                }
            }
        }
        return $gaps;
    }
}
