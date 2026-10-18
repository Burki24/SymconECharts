<?php

declare(strict_types=1);

namespace SymconECharts;

require_once dirname(__DIR__) . '/EChartsCalendar/CalendarData.php';
require_once dirname(__DIR__) . '/EChartsStateTimeline/StateTimelineData.php';

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Completed local month days with no interpolation, current-value fallback or zero filling. */
final class CalendarPieData
{
    /** EndTimestamp is exclusive; Days also includes explicitly pending calendar cells. */
    public static function Resolve(string $month, int $offset, int $now, string $timezone): array
    {
        try {
            $zone = new DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid CalendarPie timezone.', 0, $exception);
        }
        $today = (new DateTimeImmutable('@' . $now))->setTimezone($zone)->setTime(0, 0);
        $baseMonth = $month === '' ? $today->format('Y-m') : $month;
        if (!preg_match('/^([0-9]{4})-(0[1-9]|1[0-2])$/D', $baseMonth, $matches)
            || (int) $matches[1] < 1971 || $baseMonth > $today->format('Y-m')) {
            throw new InvalidArgumentException('Choose the current month or a month from January 1971 through this month.');
        }
        $maximum = min(120, ((int) $matches[1] - 1971) * 12 + (int) $matches[2] - 1);
        if ($offset < 0 || $offset > $maximum) {
            throw new InvalidArgumentException('The CalendarPie month offset is invalid.');
        }
        // Iterate civil dates independently from local wall-clock normalization.
        // A DST gap at midnight can resolve to 01:00; carrying that time into
        // later days or the following month would silently shift their bounds.
        $civilStart = (new DateTimeImmutable($baseMonth . '-01 12:00:00', new DateTimeZone('UTC')))->modify('-' . $offset . ' months');
        $civilNext = $civilStart->modify('+1 month');
        $midnight = static fn (DateTimeImmutable $date): DateTimeImmutable => new DateTimeImmutable($date->format('Y-m-d') . ' 00:00:00', $zone);
        $start = $midnight($civilStart);
        $next = $midnight($civilNext);
        $cutoff = min($next, $today);
        $days = [];
        for ($civil = $civilStart; $civil < $civilNext; $civil = $civil->modify('+1 day')) {
            $day = $midnight($civil);
            $following = $midnight($civil->modify('+1 day'));
            // Date-line changes can remove an entire civil date. It has no
            // elapsed interval and must not duplicate the normalized next date.
            if ($day->format('Y-m-d') !== $civil->format('Y-m-d')) {
                continue;
            }
            $days[] = ['date'  => $civil->format('Y-m-d'), 'startTimestamp' => $day->getTimestamp(),
                'endTimestamp' => $following->getTimestamp(), 'completed' => $following <= $cutoff];
        }
        return ['Month'     => $civilStart->format('Y-m'), 'BaseMonth' => $baseMonth, 'Offset' => $offset,
            'MaximumOffset' => $maximum, 'Timezone' => $timezone, 'StartTimestamp' => $start->getTimestamp(),
            'EndTimestamp'  => $cutoff->getTimestamp(), 'Empty' => $cutoff <= $start, 'Days' => $days];
    }

    /** Values are already daily archive means or counter-consumption aggregates, never raw counter differences. */
    public static function NumericDays(array $sources, array $pointsByID, array $window, bool $normalize = false): array
    {
        if (!array_is_list($sources) || count($sources) < 1 || count($sources) > 5) {
            throw new InvalidArgumentException('Configure one to five CalendarPie sources.');
        }
        $values = [];
        $identities = [];
        foreach ($sources as $source) {
            $id = is_array($source) ? ($source['VariableID'] ?? null) : null;
            $label = is_array($source) ? ($source['Label'] ?? null) : null;
            $color = is_array($source) ? ($source['Color'] ?? null) : null;
            if (!is_int($id) || $id <= 0 || isset($identities[$id]) || !is_string($label)
                || trim($label) === '' || !is_string($color)
                || ($color !== '' && preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1)) {
                throw new InvalidArgumentException('CalendarPie sources need distinct variable IDs, labels and valid colors.');
            }
            $identities[$id] = true;
            if ($normalize) {
                self::NormalizationRange($source);
                if (!is_string($source['Unit'] ?? null) || !is_int($source['Decimals'] ?? null)
                    || $source['Decimals'] < 0 || $source['Decimals'] > 6) {
                    throw new InvalidArgumentException('Normalized CalendarPie sources need their original unit and precision.');
                }
            }
            // CalendarData validates the daily aggregate's actual first observation timestamp,
            // ascending order and unique local dates. Adapt its inclusive upper bound only.
            $inclusive = $window;
            $inclusive['EndTimestamp']--;
            $points = $pointsByID[$id] ?? [];
            if (!is_array($points)) {
                throw new InvalidArgumentException('Invalid CalendarPie daily archive points.');
            }
            $cells = CalendarData::Cells($points, $inclusive);
            foreach ($cells as $cell) {
                if (!$normalize && $cell['value'] < 0) {
                    throw new InvalidArgumentException('CalendarPie slices require nonnegative daily values.');
                }
                $values[$id][$cell['date']] = $cell['value'];
            }
        }
        foreach (array_keys($pointsByID) as $id) {
            if (!isset($identities[$id])) {
                throw new InvalidArgumentException('CalendarPie archive data contains an unconfigured source.');
            }
        }
        $days = [];
        foreach ($window['Days'] as $day) {
            $result = self::Day($day);
            if ($day['completed']) {
                $total = 0.0;
                foreach ($sources as $source) {
                    $id = $source['VariableID'];
                    if (!isset($values[$id][$day['date']])) {
                        $result['missing'][] = 'variable-' . $id;
                        continue;
                    }
                    $original = $values[$id][$day['date']];
                    $value = $normalize ? self::NormalizedValue($original, $source) : $original;
                    $total += $value;
                    $slice = ['id'              => 'variable-' . $id, 'label' => $source['Label'],
                        'value'                 => $value, 'color' => $source['Color']];
                    if ($normalize) {
                        [$minimum, $maximum] = self::NormalizationRange($source);
                        $slice += ['originalValue' => $original, 'unit' => $source['Unit'], 'decimals' => $source['Decimals'],
                            'minimum'              => $minimum, 'maximum' => $maximum];
                    }
                    $result['slices'][] = $slice;
                }
                if (!is_finite($total)) {
                    throw new InvalidArgumentException('The CalendarPie daily total is not finite.');
                }
                $result['status'] = $result['slices'] === [] ? 'missing'
                    : ($result['missing'] !== [] ? 'partial' : ($total === 0.0 ? 'zero' : 'complete'));
            }
            $days[] = $result;
        }
        return $days;
    }

    /** Explicit ranges never inherit presentation limits or the observed monthly extrema. */
    public static function NormalizationRange(array $source): array
    {
        $minimum = array_key_exists('Minimum', $source) ? $source['Minimum'] : 0;
        $maximum = array_key_exists('Maximum', $source) ? $source['Maximum'] : 100;
        if ((!is_int($minimum) && !is_float($minimum)) || (!is_int($maximum) && !is_float($maximum))
            || !is_finite((float) $minimum) || !is_finite((float) $maximum) || $minimum >= $maximum
            || !is_finite((float) $maximum - (float) $minimum)) {
            throw new InvalidArgumentException('Normalization requires finite source limits with minimum below maximum.');
        }
        return [(float) $minimum, (float) $maximum];
    }

    public static function NormalizedValue(float $value, array $source): float
    {
        [$minimum, $maximum] = self::NormalizationRange($source);
        if (!is_finite($value)) {
            throw new InvalidArgumentException('CalendarPie normalization requires finite values.');
        }
        return (max($minimum, min($maximum, $value)) - $minimum) / ($maximum - $minimum) * 100;
    }

    /** Known state durations and unknown prefixes share each day's actual 23/24/25-hour denominator. */
    public static function StateDays(array $points, ?array $previous, bool $boolean, array $definitions, array $window): array
    {
        if ($window['Empty']) {
            if ($points !== [] || $previous !== null) {
                throw new InvalidArgumentException('An empty CalendarPie month cannot contain archive samples.');
            }
            return array_map(self::Day(...), $window['Days']);
        }
        $segments = StateTimelineData::Segments($points, $previous, $window['StartTimestamp'], $window['EndTimestamp'], $boolean);
        $states = [];
        foreach ($segments as $segment) {
            if ($segment['value'] !== null) {
                $states[$segment['value']] = true;
            }
        }
        if (count($states) > 32) {
            throw new InvalidArgumentException('CalendarPie supports at most 32 distinct archived states per month.');
        }
        $days = [];
        foreach ($window['Days'] as $day) {
            $result = self::Day($day);
            if (!$day['completed']) {
                $days[] = $result;
                continue;
            }
            $durations = [];
            $unknown = 0;
            foreach ($segments as $segment) {
                $seconds = min($segment['endTimestamp'], $day['endTimestamp']) - max($segment['startTimestamp'], $day['startTimestamp']);
                if ($seconds <= 0) {
                    continue;
                }
                if ($segment['value'] === null) {
                    $unknown += $seconds;
                } else {
                    $value = $segment['value'];
                    $durations[$value] = ($durations[$value] ?? 0) + $seconds;
                }
            }
            ksort($durations, SORT_NUMERIC);
            foreach ($durations as $value => $seconds) {
                $definition = $definitions[$value] ?? ['Label' => (string) $value, 'Color' => -1];
                $result['slices'][] = ['id' => 'state-' . $value, 'label' => $definition['Label'], 'value' => $seconds,
                    'color'                 => $definition['Color'] < 0 ? '' : sprintf('#%06X', $definition['Color'])];
            }
            if ($unknown > 0) {
                $result['missing'][] = 'unknown';
                $result['slices'][] = ['id' => 'unknown', 'label' => 'Unknown', 'value' => $unknown, 'color' => '#777B83'];
            }
            $result['status'] = $durations === [] ? 'missing' : ($unknown > 0 ? 'partial' : 'complete');
            $days[] = $result;
        }
        return $days;
    }

    private static function Day(array $day): array
    {
        return ['date'   => $day['date'], 'status' => 'pending', 'slices' => [], 'missing' => [],
            'daySeconds' => $day['endTimestamp'] - $day['startTimestamp']];
    }
}
