<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Resolves local calendar-day windows without including an unfinished archive hour. */
final class HeatmapWindow
{
    public const RANGES = ['1w', '1m', '3m', '6m', '12m', 'ytd'];
    private const ARCHIVE_CHUNK_SECONDS = 60 * 86400;

    /** @return list<array{StartTimestamp:int,EndTimestamp:int}> */
    public static function ArchiveChunks(int $startTimestamp, int $endTimestamp): array
    {
        if ($startTimestamp < 1 || $endTimestamp < $startTimestamp) {
            throw new InvalidArgumentException('The Heatmap archive interval is invalid.');
        }

        $chunks = [];
        for ($start = $startTimestamp; $start <= $endTimestamp;) {
            $end = min($endTimestamp, $start + self::ARCHIVE_CHUNK_SECONDS - 1);
            $chunks[] = ['StartTimestamp' => $start, 'EndTimestamp' => $end];
            $start = $end + 1;
        }

        return $chunks;
    }

    public static function MaximumOffset(string $range, int $now, string $timezone): int
    {
        for ($offset = 0; $offset <= EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET; $offset++) {
            try {
                self::Resolve($range, $offset, $now, $timezone);
            } catch (InvalidArgumentException) {
                return max(0, $offset - 1);
            }
        }

        return EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET;
    }

    /**
     * @return array{StartTimestamp:int,EndTimestamp:int,Empty:bool,Limit:int,StartDate:string,EndDate:string,Days:list<array{date:string,weekday:int}>}
     */
    public static function Resolve(string $range, int $offset, int $now, string $timezone): array
    {
        if (!in_array($range, self::RANGES, true)
            || $offset < 0 || $offset > EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET || $now < 1) {
            throw new InvalidArgumentException('The Heatmap window is invalid.');
        }

        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('The Heatmap timezone is invalid.', 0, $exception);
        }

        $today = (new DateTimeImmutable('@' . $now))->setTimezone($zone)->setTime(0, 0);
        if ($range === '1w') {
            $lastDay = $today->modify('-' . ($offset * 7) . ' days');
            $firstDay = $lastDay->modify('-6 days');
            $endExclusive = $lastDay->modify('+1 day');
        } elseif ($range === 'ytd') {
            $year = (int) $today->format('Y') - $offset;
            $month = (int) $today->format('n');
            $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $zone);
            $day = min((int) $today->format('j'), (int) $monthStart->format('t'));
            $firstDay = new DateTimeImmutable(sprintf('%04d-01-01', $year), $zone);
            $lastDay = $monthStart->modify('+' . ($day - 1) . ' days');
            $endExclusive = $lastDay->modify('+1 day');
        } else {
            $months = (int) substr($range, 0, -1);
            $endExclusive = $today->modify('first day of this month')
                ->modify('-' . ($offset * $months) . ' months');
            $firstDay = $endExclusive->modify('-' . $months . ' months');
            $lastDay = $endExclusive->modify('-1 day');
        }
        if ($firstDay->getTimestamp() < 1) {
            throw new InvalidArgumentException('The Heatmap window precedes the supported archive epoch.');
        }
        $completedHour = EChartsArchiveQuery::AggregationWindowStart(0, $now, $timezone);
        $end = min($endExclusive->getTimestamp(), $completedHour) - 1;

        $days = [];
        if ($range === '1w') {
            for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')) {
                $days[] = ['date' => $day->format('Y-m-d'), 'weekday' => (int) $day->format('N') - 1];
            }
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
