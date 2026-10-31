<?php

declare(strict_types=1);

namespace SymconECharts;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Explicit legacy calendar rules. Quirks are preserved, never substituted by a similarly named native range. */
final class EChartsJSLiveTime
{
    public static function Window(array $time, string $family, int $now, int $offset = 0): array
    {
        $selection = $time['selection'];
        $period = $selection['Period']['value'] ?? null;
        $relative = $selection['Relativ']['value'] ?? null;
        if (!is_int($period) || $period < 0 || $period > 7 || !is_bool($relative) || $offset < 0 || $offset > 10000) {
            throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: invalid legacy period controls.');
        }
        $zone = new DateTimeZone($time['timezone']);
        $anchor = $family === 'cartesian-history' && ($selection['Now']['value'] ?? true) === false
            ? ($selection['StartDate']['value'] ?? 0) : $now;
        if (!is_int($anchor) || $anchor <= 0) {
            throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: invalid historical anchor.');
        }
        $date = (new DateTimeImmutable('@' . $anchor))->setTimezone($zone);
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');
        if ($relative) {
            $end = match ($period) {
                0    => $date->setDate($year, 1, 1)->setTime(0, 0),
                1, 2 => $date->setDate($year, $month, 1)->setTime(0, 0),
                3, 4 => $date->setTime(0, 0),
                5    => $date->setTime((int) $date->format('G'), 0),
                6    => $date->setTime((int) $date->format('G'), (int) $date->format('i')),
                7    => $date
            };
            $start = match ($period) {
                0 => $end->setDate($year - 9, 1, 1),
                1 => $end->setDate($year, $month - 11, 1),
                2 => $end->setDate($year, $month - 2, 1),
                3 => $end->setDate($year, $month - 1, $day + 1),
                4 => $end->setDate($year, $month, $day - 6),
                5 => $end->setTime((int) $date->format('G') - 23, 0),
                6 => $end->setTime((int) $date->format('G'), (int) $date->format('i') - 59),
                7 => $end->setTime((int) $date->format('G'), (int) $date->format('i'), (int) $date->format('s') - 59)
            };
        } else {
            $start = match ($period) {
                0 => $date->setDate(intdiv($year, 10) * 10, 1, 1)->setTime(0, 0),
                1 => $date->setDate($year, 1, 1)->setTime(0, 0),
                2 => $date->setDate($year, intdiv($month - 1, 3) * 3 + 1, 1)->setTime(0, 0),
                3 => $date->setDate($year, $month, 1)->setTime(0, 0),
                // Retain the source's year reset at a calendar-year boundary.
                4 => $date->modify('monday this week')->setDate($year, (int) $date->modify('monday this week')->format('n'), (int) $date->modify('monday this week')->format('j'))->setTime(0, 0),
                5 => $date->setTime(0, 0),
                6 => $date->setTime((int) $date->format('G'), 0),
                7 => $date->setTime((int) $date->format('G'), (int) $date->format('i'))
            };
            $end = match ($period) {
                0 => $start->setDate((int) $start->format('Y') + 9, 1, 1)->setTime(0, 0, -1),
                1 => $start->setDate($year, 12, 1)->setTime(0, 0, -1),
                2 => $start->setDate($year, (int) $start->format('n') + 2, 1)->setTime(0, 0, -1),
                3 => $start->setDate($year, $month + 1, 1)->setTime(0, 0, -1),
                4 => $start->setDate((int) $start->format('Y'), (int) $start->format('n'), (int) $start->format('j') + 6)->setTime(0, 0, -1),
                5 => $start->setDate($year, $month, $day + 1)->setTime(0, 0, -1),
                6 => $start->setTime((int) $date->format('G') + 1, 0, -1),
                7 => $start->setTime((int) $date->format('G'), (int) $date->format('i') + 1, -1)
            };
            if ($family === 'radar-history') {
                $end = match ($period) {
                    0       => $start->setDate((int) $start->format('Y') + 9, 12, 31)->setTime(23, 59, 59),
                    1       => $start->setDate($year, 13, 0)->setTime(23, 59, 59),
                    2       => $start->setDate($year, (int) $start->format('n') + 3, 0)->setTime(23, 59, 59),
                    4       => $start->setDate((int) $start->format('Y'), (int) $start->format('n'), (int) $start->format('j') + 6)->setTime(23, 59, 59),
                    default => $end
                };
            }
        }
        $displayOffset = 0;
        if ($offset > 0) {
            [$unit, $amount, $seconds] = match ($period) {
                0 => ['M', $offset * 120, $offset * 120 * 2592000],
                1 => ['M', $offset * 12, $offset * 12 * 2592000],
                2 => ['M', $offset * 3, $offset * 3 * 2592000],
                3 => ['M', $offset, $offset * 2592000],
                4 => ['D', $offset * 7, $offset * 7 * 86400],
                5 => ['D', $offset, $offset * 86400],
                6 => ['H', $offset, $offset * 3600],
                7 => ['m', $offset, $offset * 60]
            };
            $delta = new DateInterval(in_array($unit, ['H', 'm'], true) ? 'PT' . $amount . strtoupper($unit) : 'P' . $amount . $unit);
            $start = $start->sub($delta);
            if ($relative) {
                $end = $end->sub($delta);
            } else {
                $y = (int) $start->format('Y');
                $m = (int) $start->format('n');
                $d = (int) $start->format('j');
                $end = match ($period) {
                    0 => $end->setDate(intdiv($y, 10) * 10 + 9, 12, 31)->setTime(23, 59, 59),
                    1 => $end->setDate($y, 13, 0)->setTime(23, 59, 59),
                    2 => $end->setDate($y, $m + 3, 0)->setTime(23, 59, 59),
                    3 => $end->setDate($y, $m + 1, 0)->setTime(23, 59, 59),
                    4 => $end->setDate($y, $m, $d + 6)->setTime(23, 59, 59),
                    5 => $end->setDate($y, $m, $d)->setTime(23, 59, 59),
                    6 => $end->setDate($y, $m, $d)->setTime((int) $start->format('G'), 59, 59),
                    7 => $end->setDate($y, $m, $d)->setTime((int) $start->format('G'), (int) $start->format('i'), 59)
                };
            }
            $displayOffset = $seconds;
        }
        $level = $family === 'radar-history' && $relative ? [3, 2, 1, 1, 0, 0, 6, 6][$period] : [4, 3, 3, 1, 1, 0, 6, 6][$period];
        return ['fromInclusive'    => $start->getTimestamp(), 'untilExclusive' => $end->getTimestamp() + 1,
            'displayOffsetSeconds' => $displayOffset, 'aggregationLevel' => $level, 'period' => $period];
    }

    /** Exact legacy reduction including raw counter boundary and missing-data fallback. Input remains newest first. */
    public static function Cartesian(array $rows, array $rule, mixed $current, int $now, ?array $booleanLabels = null): array
    {
        $points = [];
        $index = $rule['decimation'];
        $old = 0;
        foreach ($rows as $row) {
            --$index;
            if ($index <= 0) {
                $index = $rule['decimation'];
            }
            if ($rule['mode'] === 'raw' && $rule['period'] !== 7 && $index < $rule['decimation']) {
                continue;
            }
            $value = $row[$rule['mode'] === 'raw' ? 'Value' : 'Avg'];
            if ($booleanLabels !== null) {
                $value = $booleanLabels[$value == 1 ? 0 : 1];
            } else {
                if ($rule['counter'] && $rule['mode'] === 'raw') {
                    $value = abs($old > 0 ? $old - $value : $value - $current);
                    $old = $row['Value'];
                }
                $value = round((float) $value, $rule['precision']);
            }
            $points[] = ['x' => ($row['TimeStamp'] + $rule['displayOffsetSeconds']) * 1000, 'y' => $value];
        }
        if ($points === [] && $booleanLabels === null) {
            $value = is_bool($current) ? (int) $current : $current;
            $points = [['x' => $rule['fromInclusive'] * 1000, 'y' => $value], ['x' => $now * 1000, 'y' => $value]];
        }
        // PHP's source sorts ties by the complete row; preserve that contract explicitly.
        usort($points, static fn (array $a, array $b): int => ($a['x'] <=> $b['x']) ?: ($a['y'] <=> $b['y']));
        return $points;
    }
}
