<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Resolves local calendar-day windows without including an unfinished archive hour. */
final class HeatmapWindow
{
    /**
     * @return array{StartTimestamp:int,EndTimestamp:int,Empty:bool,Limit:int,StartDate:string,EndDate:string,Days:list<array{date:string,weekday:int}>}
     */
    public static function Resolve(string $range, int $offset, int $now, string $timezone): array
    {
        if (!in_array($range, ['7d', '30d'], true)
            || $offset < 0 || $offset > EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET || $now < 1) {
            throw new InvalidArgumentException('The Heatmap window is invalid.');
        }

        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('The Heatmap timezone is invalid.', 0, $exception);
        }

        $length = $range === '7d' ? 7 : 30;
        $today = (new DateTimeImmutable('@' . $now))->setTimezone($zone)->setTime(0, 0);
        $lastDay = $today->modify('-' . ($offset * $length) . ' days');
        $firstDay = $lastDay->modify('-' . ($length - 1) . ' days');
        $endExclusive = $lastDay->modify('+1 day')->getTimestamp();
        $completedHour = EChartsArchiveQuery::AggregationWindowStart(0, $now, $timezone);
        $end = min($endExclusive, $completedHour) - 1;

        $days = [];
        for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')) {
            $days[] = ['date' => $day->format('Y-m-d'), 'weekday' => (int) $day->format('N') - 1];
        }

        return [
            'StartTimestamp' => $firstDay->getTimestamp(),
            'EndTimestamp'   => $end,
            'Empty'          => $end < $firstDay->getTimestamp(),
            'Limit'          => 2000,
            'StartDate'      => $firstDay->format('Y-m-d'),
            'EndDate'        => $lastDay->format('Y-m-d'),
            'Days'           => $days
        ];
    }
}
