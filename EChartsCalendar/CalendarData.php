<?php

declare(strict_types=1);

namespace SymconECharts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';

/** Local calendar days; values come directly from completed Symcon daily archive aggregates. */
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
        $days = array_map(
            static fn (array $day): array => ['date' => $day['date'], 'weekday' => $day['weekday']],
            EChartsArchiveQuery::CalendarDays($start->format('Y-m-d'), $next->format('Y-m-d'), $cutoff->getTimestamp(), $timezone)
        );
        return ['Year' => $year, 'BaseYear' => $baseYear, 'Offset' => $offset, 'MaximumOffset' => $baseYear - 1971,
            'Timezone' => $timezone, 'StartTimestamp' => $start->getTimestamp(), 'EndTimestamp' => $cutoff->getTimestamp() - 1,
            'Empty'    => $cutoff <= $start, 'Days' => $days];
    }

    /** Additive view planning: the legacy Resolve() result remains unchanged. */
    public static function ResolveView(array $settings, int $offset, int $now, string $timezone): array
    {
        $period = $settings['Period'] ?? 'year';
        if (!in_array($period, ['year', 'quarter', 'month', 'absolute'], true)) {
            throw new InvalidArgumentException('The Calendar period settings are invalid.');
        }
        $zone = new DateTimeZone($timezone);
        $utc = new DateTimeZone('UTC');
        $today = (new DateTimeImmutable('@' . $now))->setTimezone($zone)->setTime(0, 0);
        $year = $settings['Year'] ?? 0;
        $baseYear = $year === 0 ? (int) $today->format('Y') : $year;
        $legacy = null;
        if ($period === 'absolute') {
            $from = EChartsArchiveQuery::ParseLocalDate($settings['AbsoluteStart'] ?? '{}', $timezone);
            $until = EChartsArchiveQuery::ParseLocalDate($settings['AbsoluteEnd'] ?? '{}', $timezone);
            EChartsArchiveQuery::ResolveAbsoluteWindow($from, $until, $now, $timezone);
            $baseStart = new DateTimeImmutable((new DateTimeImmutable('@' . $from))->setTimezone($zone)->format('Y-m-d') . ' 12:00:00', $utc);
            $baseEnd = new DateTimeImmutable((new DateTimeImmutable('@' . $until))->setTimezone($zone)->format('Y-m-d') . ' 12:00:00', $utc);
            $baseYear = (int) $baseStart->format('Y');
            $span = (int) $baseStart->diff($baseEnd)->format('%a');
            if ($span < 1 || $span > 366 || $baseStart->format('Y') < '1971') {
                throw new InvalidArgumentException('Calendar periods support at most 366 calendar days.');
            }
            $earliest = new DateTimeImmutable('1971-01-01 12:00:00', $utc);
            $maximum = min(120, intdiv((int) $earliest->diff($baseStart)->format('%a'), $span));
            $shift = '-' . ($offset * $span) . ' days';
        } else {
            if (!is_int($baseYear) || $baseYear < 1971 || $baseYear > (int) $today->format('Y')) {
                throw new InvalidArgumentException('Choose the current year (0) or a year from 1971 through this year.');
            }
            $step = match ($period) {
                'month' => 1, 'quarter' => 3, default => 12
            };
            $month = 1;
            if ($period !== 'year') {
                $selection = $settings[$period === 'month' ? 'Month' : 'Quarter'] ?? 0;
                if (!is_int($selection) || $selection < 0 || $selection > ($period === 'month' ? 12 : 4)) {
                    throw new InvalidArgumentException('The Calendar period settings are invalid.');
                }
                $month = $period === 'month' ? ($selection ?: (int) $today->format('n'))
                    : (($selection ?: intdiv((int) $today->format('n') - 1, 3) + 1) - 1) * 3 + 1;
            }
            $baseStart = new DateTimeImmutable(sprintf('%04d-%02d-01 12:00:00', $baseYear, $month), $utc);
            if ($baseStart->format('Y-m-d') > $today->format('Y-m-d')) {
                throw new InvalidArgumentException('The Calendar period settings are invalid.');
            }
            $baseEnd = $baseStart->modify('+' . $step . ' months');
            $maximum = intdiv(($baseYear - 1971) * 12 + $month - 1, $step);
            if ($period !== 'year') {
                $maximum = min(120, $maximum);
            } else {
                $legacy = self::Resolve($year, $offset, $now, $timezone);
            }
            $shift = '-' . ($offset * $step) . ' months';
        }
        if ($offset < 0 || $offset > $maximum) {
            throw new InvalidArgumentException('The Calendar navigation offset is invalid.');
        }
        $civilStart = $baseStart->modify($shift);
        $civilEnd = $baseEnd->modify($shift);
        $start = new DateTimeImmutable($civilStart->format('Y-m-d') . ' 00:00:00', $zone);
        $end = new DateTimeImmutable($civilEnd->format('Y-m-d') . ' 00:00:00', $zone);
        $cutoff = min($end, $today);
        $days = $legacy['Days'] ?? array_map(
            static fn (array $d): array => ['date' => $d['date'], 'weekday' => $d['weekday']],
            EChartsArchiveQuery::CalendarDays($civilStart->format('Y-m-d'), $civilEnd->format('Y-m-d'), $cutoff->getTimestamp(), $timezone)
        );
        $last = $civilEnd->modify('-1 day')->format('Y-m-d');
        $label = match ($period) {
            'year'    => $civilStart->format('Y'), 'month' => $civilStart->format('Y-m'),
            'quarter' => $civilStart->format('Y') . ' Q' . (intdiv((int) $civilStart->format('n') - 1, 3) + 1),
            default   => $civilStart->format('Y-m-d') . ' – ' . $last
        };
        return ($legacy ?? ['Year' => (int) $civilStart->format('Y'), 'BaseYear' => $baseYear, 'Offset' => $offset,
            'MaximumOffset'        => $maximum, 'Timezone' => $timezone, 'StartTimestamp' => $start->getTimestamp(),
            'EndTimestamp'         => $cutoff->getTimestamp() - 1, 'Empty' => $cutoff <= $start, 'Days' => $days]) + [
                'Period'        => $period, 'Label' => $label, 'StartDate' => $civilStart->format('Y-m-d'), 'EndDate' => $last,
                'BaseStartDate' => $baseStart->format('Y-m-d'), 'BaseEndDate' => $baseEnd->format('Y-m-d'),
                'CalendarRange' => $period === 'year' ? $civilStart->format('Y') : [$civilStart->format('Y-m-d'), $last],
                'StartWeekday'  => (int) $civilStart->format('w'),
                'DayCount'      => (int) $civilStart->diff($civilEnd)->format('%a'), 'MonthCount' => count(array_unique(array_map(static fn (array $d): string => substr($d['date'], 0, 7), $days)))
            ];
    }

    /** No interpolation, zero filling or re-averaging of daily values. */
    public static function Cells(array $points, array $window, string $metric = 'average'): array
    {
        if (!in_array($metric, ['average', 'minimum', 'maximum', 'consumption'], true)
            || !array_is_list($points) || count($points) > 366) {
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
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])
                || ($metric === 'consumption' && $point[1] < 0)) {
                throw new InvalidArgumentException('Invalid Calendar archive points.');
            }
            $local = (new DateTimeImmutable('@' . $point[0]))->setTimezone($zone);
            $date = $local->format('Y-m-d');
            // A daily aggregate can start at the first observation (e.g. when logging
            // begins during the day). The query fixes its daily aggregation level;
            // use the local date, not an assumed midnight alignment.
            if (isset($dates[$date])) {
                throw new InvalidArgumentException('Calendar needs one daily archive value per local date.');
            }
            $dates[$date] = true;
            $previous = $point[0];
            $cells[] = ['date' => $date, 'value' => (float) $point[1]];
        }
        return $cells;
    }
}
