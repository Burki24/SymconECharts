<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Joins numeric archive axes only at identical, completed interval timestamps. */
final class ParallelPairing
{
    /**
     * @param list<list<array{int,int|float}>> $series
     * @return list<array{timestamp:int,values:list<float>}>
     */
    public static function Pair(array $series, int $limit): array
    {
        if (count($series) < 2 || $limit < 1) {
            throw new InvalidArgumentException('Parallel needs at least two axes and a positive line limit.');
        }
        $indexed = [];
        foreach ($series as $points) {
            $index = [];
            $previous = 0;
            foreach ($points as $point) {
                if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                    || !is_int($point[0]) || $point[0] <= $previous
                    || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])) {
                    throw new InvalidArgumentException('Parallel archive points must be finite and strictly time-ordered.');
                }
                $index[$point[0]] = (float) $point[1];
                $previous = $point[0];
            }
            $indexed[] = $index;
        }
        $rows = [];
        foreach ($indexed[0] as $timestamp => $value) {
            $values = [$value];
            for ($axis = 1, $count = count($indexed); $axis < $count; ++$axis) {
                if (!array_key_exists($timestamp, $indexed[$axis])) {
                    continue 2;
                }
                $values[] = $indexed[$axis][$timestamp];
            }
            $rows[] = ['timestamp' => $timestamp, 'values' => $values];
        }
        return array_slice($rows, -$limit);
    }
}
