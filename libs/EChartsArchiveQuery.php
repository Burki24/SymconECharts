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
    // Explicit raw-value interval lists, not additional Symcon aggregation modes.
    public const RAW_INTERVALS = ['fifteen-minutes', 'thirty-minutes', 'hour', 'day', 'week'];
    private const MINUTE_INTERVAL_SECONDS = ['fifteen-minutes' => 900, 'thirty-minutes' => 1800];

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

    /** Strict native date fields; local day boundaries follow the calendar's midnight-DST normalization. */
    public static function ParseLocalDate(mixed $raw, string $timezone): int
    {
        if (is_string($raw) && strlen($raw) <= 1024) {
            try {
                $raw = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $raw = null;
            }
        } elseif ($raw instanceof \stdClass) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw) || count($raw) !== 3 || array_diff(['year', 'month', 'day'], array_keys($raw)) !== []) {
            throw new InvalidArgumentException('The historical date and time selection is invalid.');
        }
        try {
            return self::ParseLocalDateTime($raw + ['hour' => 0, 'minute' => 0, 'second' => 0], $timezone);
        } catch (InvalidArgumentException $exception) {
            if (!in_array($exception->getMessage(), [
                'The historical date and time is ambiguous in the selected timezone.',
                'The historical date and time does not exist in the selected timezone.'
            ], true)) {
                throw $exception;
            }
            // Unlike a time-of-day selection, a date denotes the local calendar
            // boundary. Normalize midnight DST, but never accept a removed date.
            $date = sprintf('%04d-%02d-%02d', $raw['year'], $raw['month'], $raw['day']);
            $boundary = new DateTimeImmutable($date . ' 00:00:00', new DateTimeZone($timezone));
            if ($boundary->format('Y-m-d') !== $date) {
                throw $exception;
            }
            return $boundary->getTimestamp();
        }
    }

    /** Bounded civil-date enumeration, shared by calendar layouts; never carry a normalized DST time into the next date. */
    public static function CalendarDays(string $from, string $until, int $cutoff, string $timezone, int $maximumDays = 366): array
    {
        $zone = new DateTimeZone($timezone);
        $utc = new DateTimeZone('UTC');
        $parse = static function (string $date) use ($utc): DateTimeImmutable
        {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $m)
                || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                throw new InvalidArgumentException('The historical date and time selection is invalid.');
            }
            return new DateTimeImmutable($date . ' 12:00:00', $utc);
        };
        $start = $parse($from);
        $end = $parse($until);
        $count = (int) $start->diff($end)->format('%r%a');
        if ($maximumDays < 1 || $maximumDays > 366 || $count < 1 || $count > $maximumDays) {
            throw new InvalidArgumentException('Calendar periods support at most 366 calendar days.');
        }
        $days = [];
        for ($civil = $start; $civil < $end; $civil = $civil->modify('+1 day')) {
            $day = new DateTimeImmutable($civil->format('Y-m-d') . ' 00:00:00', $zone);
            $next = new DateTimeImmutable($civil->modify('+1 day')->format('Y-m-d') . ' 00:00:00', $zone);
            if ($day->format('Y-m-d') !== $civil->format('Y-m-d')) {
                continue;
            }
            $days[] = ['date'    => $civil->format('Y-m-d'), 'weekday' => (int) $civil->format('N') - 1,
                'startTimestamp' => $day->getTimestamp(), 'endTimestamp' => $next->getTimestamp(), 'completed' => $next->getTimestamp() <= $cutoff];
        }
        return $days;
    }

    /** Resolve the six integer fields produced by the native SelectDateTime control. */
    public static function ParseLocalDateTime(mixed $raw, string $timezone): int
    {
        $invalid = 'The historical date and time selection is invalid.';
        if (is_string($raw)) {
            if (strlen($raw) > 1024) {
                throw new InvalidArgumentException($invalid);
            }
            try {
                $raw = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new InvalidArgumentException($invalid);
            }
        } elseif ($raw instanceof \stdClass) {
            $raw = get_object_vars($raw);
        }
        $fields = ['year', 'month', 'day', 'hour', 'minute', 'second'];
        if (!is_array($raw) || count($raw) !== count($fields)
            || array_diff($fields, array_keys($raw)) !== []) {
            throw new InvalidArgumentException($invalid);
        }
        foreach ($fields as $field) {
            if (!is_int($raw[$field])) {
                throw new InvalidArgumentException($invalid);
            }
        }
        if ($raw['year'] < 1970 || $raw['year'] > 9999
            || !checkdate($raw['month'], $raw['day'], $raw['year'])
            || $raw['hour'] < 0 || $raw['hour'] > 23 || $raw['minute'] < 0 || $raw['minute'] > 59
            || $raw['second'] < 0 || $raw['second'] > 59) {
            throw new InvalidArgumentException($invalid);
        }
        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Exception) {
            throw new InvalidArgumentException($invalid);
        }
        $label = sprintf('%04d-%02d-%02d %02d:%02d:%02d', ...array_map(static fn (string $field): int => $raw[$field], $fields));
        $naive = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $label, new DateTimeZone('UTC'));
        if ($naive === false) {
            throw new InvalidArgumentException($invalid);
        }
        // Test every nearby UTC offset instead of accepting PHP's silent DST choice/normalization.
        $transitions = $zone->getTransitions($naive->getTimestamp() - 172800, $naive->getTimestamp() + 172800);
        $offsets = [$zone->getOffset($naive)];
        if ($transitions !== false) {
            $offsets = [...$offsets, ...array_column($transitions, 'offset')];
        }
        $matches = [];
        foreach (array_unique($offsets) as $offset) {
            $candidate = $naive->getTimestamp() - $offset;
            if ((new DateTimeImmutable('@' . $candidate))->setTimezone($zone)->format('Y-m-d H:i:s') === $label) {
                $matches[] = $candidate;
            }
        }
        if (count($matches) > 1) {
            throw new InvalidArgumentException('The historical date and time is ambiguous in the selected timezone.');
        }
        if ($matches === []) {
            throw new InvalidArgumentException('The historical date and time does not exist in the selected timezone.');
        }
        if ($matches[0] < 1) {
            throw new InvalidArgumentException($invalid);
        }
        return $matches[0];
    }

    /**
     * Exact historical bounds before any family-specific interval rounding.
     * EndTimestamp is exclusive for a duration model; archive adapters keep their own boundary contract.
     * @return array{StartTimestamp:int,EndTimestamp:int}
     */
    public static function ResolveAbsoluteWindow(
        int $startTimestamp,
        int $endExclusiveTimestamp,
        int $now,
        string $timezone
    ): array {
        $invalid = 'The absolute archive query settings are invalid.';
        if ($startTimestamp < 1 || $startTimestamp >= $endExclusiveTimestamp || $endExclusiveTimestamp > $now
            || $now < 1) {
            throw new InvalidArgumentException($invalid);
        }
        try {
            $zone = new DateTimeZone($timezone);
            $startLocal = (new DateTimeImmutable('@' . $startTimestamp))->setTimezone($zone);
            $endLocal = (new DateTimeImmutable('@' . $endExclusiveTimestamp))->setTimezone($zone);
        } catch (\Exception) {
            throw new InvalidArgumentException($invalid);
        }
        foreach ([$startLocal, $endLocal] as $local) {
            if ((int) $local->format('Y') < 1970 || (int) $local->format('Y') > 9999) {
                throw new InvalidArgumentException($invalid);
            }
        }
        return ['StartTimestamp' => $startTimestamp, 'EndTimestamp' => $endExclusiveTimestamp];
    }

    /**
     * Inclusive start/exclusive end; only fully contained completed local intervals are queried.
     * @return array{DurationSeconds:int,StartTimestamp:int,EndTimestamp:int,Mode:string,AggregationLevel:int,Limit:int,CalendarAligned:bool,AcceptLiveUpdates:bool,Empty:bool}
     */
    public static function ResolveAbsolute(
        int $startTimestamp,
        int $endExclusiveTimestamp,
        string $interval,
        int $pointBudget,
        int $sourceCount,
        int $now,
        string $timezone
    ): array {
        if ($pointBudget < 1 || $sourceCount < 1 || !in_array($interval, ['hour', 'day'], true)) {
            throw new InvalidArgumentException('The absolute archive query settings are invalid.');
        }
        $window = self::ResolveAbsoluteWindow($startTimestamp, $endExclusiveTimestamp, $now, $timezone);
        $zone = new DateTimeZone($timezone);
        $startLocal = (new DateTimeImmutable('@' . $window['StartTimestamp']))->setTimezone($zone);
        $endLocal = (new DateTimeImmutable('@' . $window['EndTimestamp']))->setTimezone($zone);
        // Elapsed subtraction keeps both actual offsets of the repeated autumn hour distinct.
        if ($interval === 'hour') {
            $start = $startTimestamp - (int) $startLocal->format('i') * 60 - (int) $startLocal->format('s');
            if ($start < $startTimestamp) {
                $start += 3600;
            }
            $end = $endExclusiveTimestamp - (int) $endLocal->format('i') * 60 - (int) $endLocal->format('s');
        } else {
            $startBoundary = $startLocal->setTime(0, 0);
            $start = ($startBoundary->getTimestamp() < $startTimestamp ? $startBoundary->modify('+1 day') : $startBoundary)->getTimestamp();
            $end = $endLocal->setTime(0, 0)->getTimestamp();
        }
        $empty = $start >= $end;
        if ($interval === 'hour' && !$empty) {
            // Hour aggregates around non-hour offset changes have no established
            // cross-platform contract. Never treat a shifted :30 boundary as :00.
            $unsupported = 'Hourly historical periods across non-hour timezone changes are not supported.';
            foreach ([$start, $end] as $boundary) {
                if ((new DateTimeImmutable('@' . $boundary))->setTimezone($zone)->format('i:s') !== '00:00') {
                    throw new InvalidArgumentException($unsupported);
                }
            }
            $transitions = $zone->getTransitions($startTimestamp, $endExclusiveTimestamp + 1);
            $previousOffset = null;
            foreach ($transitions === false ? [] : $transitions as $transition) {
                if ($previousOffset !== null && ($transition['offset'] - $previousOffset) % 3600 !== 0) {
                    throw new InvalidArgumentException($unsupported);
                }
                $previousOffset = $transition['offset'];
            }
        }
        return [
            'DurationSeconds'   => $endExclusiveTimestamp - $startTimestamp,
            'StartTimestamp'    => $empty ? $startTimestamp : $start,
            'EndTimestamp'      => $empty ? $endExclusiveTimestamp - 1 : $end - 1,
            'Mode'              => 'aggregated',
            'AggregationLevel'  => $interval === 'day' ? 1 : 0,
            'Limit'             => max(1, min(2000, intdiv($pointBudget, $sourceCount))),
            'CalendarAligned'   => true,
            'AcceptLiveUpdates' => false,
            'Empty'             => $empty
        ];
    }

    /** Whole intervals for families which cannot summarize a partial leading interval. */
    public static function CompletedIntervals(
        string $range,
        int $customRangeValue,
        string $customRangeUnit,
        string $interval,
        int $now,
        string $timezone,
        int $maximumIntervals = 720
    ): array {
        if (!in_array($range, ['24h', '7d', '30d', 'custom'], true)
            || !self::IsValidRange($range, $customRangeValue, $customRangeUnit)
            || !in_array($interval, self::RAW_INTERVALS, true) || $now < 1 || $maximumIntervals < 1 || $maximumIntervals > 1000) {
            throw new InvalidArgumentException('The archive interval settings are invalid.');
        }
        $zone = new DateTimeZone($timezone);
        $local = (new DateTimeImmutable('@' . $now))->setTimezone($zone);
        if (isset(self::MINUTE_INTERVAL_SECONDS[$interval]) || $interval === 'week') {
            $end = self::RawIntervalStart($local, $interval);
            $duration = $range === 'custom' ? $customRangeValue * self::CUSTOM_RANGE_UNITS[$customRangeUnit]
                : self::RANGE_SECONDS[$range];
            // Like daily presets, weekly presets count local dates. Custom units
            // retain their existing elapsed-seconds contract, including DST.
            $earliest = $interval === 'week' && $range !== 'custom'
                ? $end->modify('-' . intdiv($duration, 86400) . ' days')->getTimestamp()
                : max(1, $end->getTimestamp() - $duration);
            return self::RawIntervalList($earliest, $end->getTimestamp(), $interval, $zone, $maximumIntervals);
        }
        // Elapsed subtraction preserves the actual offset of a repeated autumn hour.
        $end = $interval === 'day' ? $local->setTime(0, 0)
            : $local->setTimestamp($now - (int) $local->format('i') * 60 - (int) $local->format('s'));
        if ($range !== 'custom' && $interval === 'day') {
            // Existing daily presets count calendar days, including 23-/25-hour days.
            $days = intdiv(self::RANGE_SECONDS[$range], 86400);
            $cursor = $end->modify('-' . $days . ' days');
        } else {
            $duration = $range === 'custom' ? $customRangeValue * self::CUSTOM_RANGE_UNITS[$customRangeUnit]
                : self::RANGE_SECONDS[$range];
            $earliest = max(1, $end->getTimestamp() - $duration);
            if ($interval === 'hour') {
                $wholeHours = intdiv($end->getTimestamp() - $earliest, 3600);
                $cursor = $end->setTimestamp($end->getTimestamp() - $wholeHours * 3600);
            } else {
                $cursor = (new DateTimeImmutable('@' . $earliest))->setTimezone($zone);
                $midnight = $cursor->setTime(0, 0);
                $cursor = $midnight->getTimestamp() < $earliest ? $midnight->modify('+1 day') : $midnight;
            }
        }
        return self::IntervalList($cursor, $end, $interval, $maximumIntervals);
    }

    /** Same bounded whole-interval model, with an explicit historical window. */
    public static function AbsoluteIntervals(
        int $startTimestamp,
        int $endExclusiveTimestamp,
        string $interval,
        int $now,
        string $timezone,
        int $maximumIntervals = 720
    ): array {
        if ($maximumIntervals < 1 || $maximumIntervals > 1000) {
            throw new InvalidArgumentException('The archive interval settings are invalid.');
        }
        if (isset(self::MINUTE_INTERVAL_SECONDS[$interval]) || $interval === 'week') {
            self::ResolveAbsoluteWindow($startTimestamp, $endExclusiveTimestamp, $now, $timezone);
            return self::RawIntervalList($startTimestamp, $endExclusiveTimestamp, $interval, new DateTimeZone($timezone), $maximumIntervals);
        }
        $query = self::ResolveAbsolute($startTimestamp, $endExclusiveTimestamp, $interval, $maximumIntervals, 1, $now, $timezone);
        if ($query['Empty']) {
            return ['StartTimestamp' => $query['StartTimestamp'], 'EndTimestamp' => $query['EndTimestamp'],
                'Intervals'          => [], 'AggregationLevel' => $query['AggregationLevel'], 'Limit' => 1000, 'Empty' => true];
        }
        $zone = new DateTimeZone($timezone);
        return self::IntervalList(
            (new DateTimeImmutable('@' . $query['StartTimestamp']))->setTimezone($zone),
            (new DateTimeImmutable('@' . ($query['EndTimestamp'] + 1)))->setTimezone($zone),
            $interval,
            $maximumIntervals
        );
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

    /** Shared enumeration keeps rolling and historical interval labels/budgets identical. */
    private static function IntervalList(DateTimeImmutable $cursor, DateTimeImmutable $end, string $interval, int $maximumIntervals): array
    {
        $start = $cursor->getTimestamp();
        $intervals = [];
        while ($cursor < $end) {
            if (count($intervals) >= $maximumIntervals) {
                throw new InvalidArgumentException('Too many archive intervals. Choose a shorter range or a longer interval.');
            }
            $next = self::NextInterval($cursor, $interval);
            $intervals[] = ['startTimestamp' => $cursor->getTimestamp(), 'endTimestamp' => $next->getTimestamp() - 1,
                'label'                      => $cursor->format(match ($interval) {
                    'day' => 'Y-m-d', 'week' => 'o-\\WW', default => 'Y-m-d H:i P'
                })];
            $cursor = $next;
        }
        return ['StartTimestamp' => $intervals === [] ? $end->getTimestamp() - 1 : $start,
            'EndTimestamp'       => $end->getTimestamp() - 1, 'Intervals' => $intervals,
            'AggregationLevel'   => match ($interval) {
                'day' => 1, 'hour' => 0, default => null
            },
            'Limit'              => 1000, 'Empty' => $intervals === []];
    }

    /** Minute slots preserve elapsed offsets; weeks use local ISO-Monday boundaries. */
    private static function RawIntervalStart(DateTimeImmutable $local, string $interval): DateTimeImmutable
    {
        if ($interval === 'week') {
            return new DateTimeImmutable($local->modify('monday this week')->format('Y-m-d') . ' 00:00:00', $local->getTimezone());
        }
        $seconds = self::MINUTE_INTERVAL_SECONDS[$interval];
        return $local->setTimestamp($local->getTimestamp() - ((int) $local->format('i') * 60 + (int) $local->format('s')) % $seconds);
    }

    private static function NextInterval(DateTimeImmutable $cursor, string $interval): DateTimeImmutable
    {
        return match ($interval) {
            'day'   => $cursor->modify('+1 day'),
            'week'  => new DateTimeImmutable($cursor->modify('+7 days')->format('Y-m-d') . ' 00:00:00', $cursor->getTimezone()),
            default => $cursor->setTimestamp($cursor->getTimestamp() + (self::MINUTE_INTERVAL_SECONDS[$interval] ?? 3600))
        };
    }

    /** Whole raw intervals only; keep aggregate-query consumers and their hour/day contracts unchanged. */
    private static function RawIntervalList(int $from, int $until, string $interval, DateTimeZone $zone, int $maximumIntervals): array
    {
        $cursor = self::RawIntervalStart((new DateTimeImmutable('@' . $from))->setTimezone($zone), $interval);
        if ($cursor->getTimestamp() < $from) {
            $cursor = self::NextInterval($cursor, $interval);
        }
        $end = self::RawIntervalStart((new DateTimeImmutable('@' . $until))->setTimezone($zone), $interval);
        if ($cursor >= $end) {
            return ['StartTimestamp' => $from, 'EndTimestamp' => $until - 1,
                'Intervals'          => [], 'AggregationLevel' => null, 'Limit' => 1000, 'Empty' => true];
        }
        if (isset(self::MINUTE_INTERVAL_SECONDS[$interval])) {
            $seconds = self::MINUTE_INTERVAL_SECONDS[$interval];
            $transitions = $zone->getTransitions($cursor->getTimestamp(), $end->getTimestamp() + 1);
            $previousOffset = null;
            foreach ($transitions === false ? [] : $transitions as $transition) {
                if ($previousOffset !== null && ($transition['offset'] - $previousOffset) % $seconds !== 0) {
                    throw new InvalidArgumentException('Minute historical periods across non-aligned timezone changes are not supported.');
                }
                $previousOffset = $transition['offset'];
            }
        }
        return self::IntervalList($cursor, $end, $interval, $maximumIntervals);
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
