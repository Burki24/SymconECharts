<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';

/** Pure half-open intervals from validated discrete archive samples. */
final class StateTimelineData
{
    /** @return array<int,array{Label:string,Color:int}> */
    public static function Definitions(mixed $raw, bool $boolean): array
    {
        if (!is_array($raw) || !array_is_list($raw) || count($raw) > 32) {
            throw new InvalidArgumentException('Configure at most 32 state definitions per source.');
        }
        $definitions = [];
        foreach ($raw as $entry) {
            $value = is_array($entry) ? ($entry['Value'] ?? null) : null;
            $label = is_array($entry) ? ($entry['Label'] ?? null) : null;
            $color = is_array($entry) ? ($entry['Color'] ?? -1) : null;
            if (!is_int($value) || abs((float) $value) > 9007199254740991
                || ($boolean && !in_array($value, [0, 1], true)) || isset($definitions[$value])
                || !is_string($label) || trim($label) === '' || mb_strlen(trim($label)) > 80
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException('State definitions need distinct integer values, labels and valid colors.');
            }
            $definitions[$value] = ['Label' => trim($label), 'Color' => $color];
        }
        return $definitions;
    }

    /** @return array{StartTimestamp:int,EndTimestamp:int} */
    public static function Window(string $range, int $now, int $customValue = 7, string $customUnit = 'day'): array
    {
        if (!in_array($range, ['24h', '7d', '30d', 'custom'], true) || $now <= 1) {
            throw new InvalidArgumentException('The StateTimeline time range is invalid.');
        }
        $query = EChartsArchiveQuery::Resolve($range, $customValue, $customUnit, 'raw', 1, 1, $now, date_default_timezone_get());
        return ['StartTimestamp' => $query['StartTimestamp'], 'EndTimestamp' => $query['EndTimestamp']];
    }

    /**
     * @param list<array{0:int,1:int}> $points
     * @param array{0:int,1:int}|null $previous
     * @return list<array{startTimestamp:int,endTimestamp:int,value:int|null}>
     */
    public static function Segments(array $points, ?array $previous, int $start, int $end, bool $boolean = false): array
    {
        if ($start < 1 || $end <= $start || !array_is_list($points) || count($points) > 2000) {
            throw new InvalidArgumentException('The StateTimeline archive window is invalid.');
        }
        $validPoint = static fn (mixed $point): bool => is_array($point) && array_is_list($point)
            && count($point) === 2 && is_int($point[0]) && $point[0] > 0 && is_int($point[1])
            && (!$boolean || in_array($point[1], [0, 1], true))
            && abs((float) $point[1]) <= 9007199254740991;
        if ($previous !== null && (!$validPoint($previous) || $previous[0] >= $start)) {
            throw new InvalidArgumentException('The StateTimeline predecessor is invalid.');
        }
        $cursor = $start;
        $value = $previous[1] ?? null;
        $lastTimestamp = null;
        $lastValue = null;
        $segments = [];
        foreach ($points as $point) {
            if (!$validPoint($point) || $point[0] < $start || $point[0] > $end
                || ($lastTimestamp !== null && ($point[0] < $lastTimestamp
                    || ($point[0] === $lastTimestamp && $point[1] !== $lastValue)))) {
                throw new InvalidArgumentException('The StateTimeline archive points are invalid.');
            }
            [$timestamp, $nextValue] = $point;
            $lastTimestamp = $timestamp;
            $lastValue = $nextValue;
            if ($nextValue !== $value) {
                if ($timestamp > $cursor) {
                    $segments[] = ['startTimestamp' => $cursor, 'endTimestamp' => $timestamp, 'value' => $value];
                }
                $cursor = $timestamp;
                $value = $nextValue;
            }
        }
        if ($cursor < $end) {
            $segments[] = ['startTimestamp' => $cursor, 'endTimestamp' => $end, 'value' => $value];
        }
        return $segments;
    }
}
