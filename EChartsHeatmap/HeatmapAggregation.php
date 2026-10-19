<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Groups completed hourly archive values by local weekday and hour. */
final class HeatmapAggregation
{
    /**
     * Each archive point represents one completed hourly interval. Repeated
     * local hours (DST fallback) contribute separately; absent hours do not
     * create a zero-valued cell.
     *
     * @param list<array{0:int,1:int|float}> $points
     * @return list<array{hour:int,weekday:int,value:float,count:int}>
     */
    public static function ByWeekdayAndHour(array $points, string $timezone, string $metric = 'average'): array
    {
        if (!in_array($metric, ['average', 'minimum', 'maximum', 'consumption'], true)) {
            throw new InvalidArgumentException('Invalid Heatmap metric.');
        }
        if (!array_is_list($points)) {
            throw new InvalidArgumentException('Archive points must be a list.');
        }
        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid heatmap timezone.', 0, $exception);
        }

        $buckets = [];
        $previousTimestamp = 0;
        foreach ($points as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= $previousTimestamp
                || (!is_int($point[1]) && !is_float($point[1]))
                || !is_finite((float) $point[1]) || ($metric === 'consumption' && $point[1] < 0)) {
                throw new InvalidArgumentException('Invalid or unordered heatmap archive point.');
            }
            $previousTimestamp = $point[0];
            $local = (new DateTimeImmutable('@' . $point[0]))->setTimezone($zone);
            $weekday = (int) $local->format('N') - 1;
            $hour = (int) $local->format('G');
            $index = $weekday * 24 + $hour;
            if (!isset($buckets[$index])) {
                $buckets[$index] = ['sum' => 0.0, 'minimum' => (float) $point[1],
                    'maximum'             => (float) $point[1], 'count' => 0];
            }
            if ($metric === 'average' || $metric === 'consumption') {
                $buckets[$index]['sum'] += (float) $point[1];
            }
            $buckets[$index]['minimum'] = min($buckets[$index]['minimum'], (float) $point[1]);
            $buckets[$index]['maximum'] = max($buckets[$index]['maximum'], (float) $point[1]);
            if (!is_finite($buckets[$index]['sum'])) {
                throw new InvalidArgumentException('Heatmap archive values exceed the finite range.');
            }
            $buckets[$index]['count']++;
        }

        ksort($buckets, SORT_NUMERIC);
        $cells = [];
        foreach ($buckets as $index => $bucket) {
            $cells[] = [
                'hour'    => $index % 24,
                'weekday' => intdiv($index, 24),
                'value'   => match ($metric) {
                    'minimum'     => $bucket['minimum'], 'maximum' => $bucket['maximum'],
                    'consumption' => $bucket['sum'], default => $bucket['sum'] / $bucket['count']
                },
                'count'   => $bucket['count']
            ];
        }

        return $cells;
    }
}
