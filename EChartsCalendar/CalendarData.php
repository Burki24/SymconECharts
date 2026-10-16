<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Local calendar days; means come directly from completed Symcon daily archive aggregates. */
final class CalendarData
{
    public static function Resolve(int $configuredYear, int $offset, int $now, string $timezone): array
    {
        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid Calendar timezone.', 0, $exception);
        }
        $today = (new DateTimeImmutable('@' . $now))->setTimezone($zone)->setTime(0, 0);
        $currentYear = (int) $today->format('Y');
        $baseYear = $configuredYear === 0 ? $currentYear : $configuredYear;
        if ($baseYear < 1971 || $baseYear > $currentYear || $offset < 0 || $offset > $baseYear - 1971) {
            throw new InvalidArgumentException('Choose the current year (0) or a year from 1971 through this year.');
        }
        $year = $baseYear - $offset;
        $start = new DateTimeImmutable($year . '-01-01 00:00:00', $zone);
        $next = $start->modify('+1 year');
        $cutoff = $next < $today ? $next : $today;
        $days = [];
        for ($day = $start; $day < $next; $day = $day->modify('+1 day')) {
            $days[] = ['date' => $day->format('Y-m-d'), 'weekday' => (int) $day->format('N') - 1];
        }
        return ['Year' => $year, 'BaseYear' => $baseYear, 'Offset' => $offset, 'MaximumOffset' => $baseYear - 1971,
            'Timezone' => $timezone, 'StartTimestamp' => $start->getTimestamp(), 'EndTimestamp' => $cutoff->getTimestamp() - 1,
            'Empty'    => $cutoff <= $start, 'Days' => $days];
    }

    /** No interpolation, zero filling or re-averaging of daily values. */
    public static function Cells(array $points, array $window): array
    {
        if (!array_is_list($points) || count($points) > 366) {
            throw new InvalidArgumentException('Invalid Calendar archive points.');
        }
        $zone = new DateTimeZone($window['Timezone']);
        $previous = 0;
        $dates = [];
        $cells = [];
        foreach ($points as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= $previous
                || $point[0] < $window['StartTimestamp'] || $point[0] > $window['EndTimestamp']
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])) {
                throw new InvalidArgumentException('Invalid Calendar archive points.');
            }
            $local = (new DateTimeImmutable('@' . $point[0]))->setTimezone($zone);
            $date = $local->format('Y-m-d');
            if ($local->format('H:i:s') !== '00:00:00' || isset($dates[$date])) {
                throw new InvalidArgumentException('Calendar needs one daily archive mean per local date.');
            }
            $dates[$date] = true;
            $previous = $point[0];
            $cells[] = ['date' => $date, 'value' => (float) $point[1]];
        }
        return $cells;
    }
}
