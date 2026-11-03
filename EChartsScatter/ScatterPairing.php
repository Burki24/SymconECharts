<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Pairs independent archive series by an identical completed interval timestamp. */
final class ScatterPairing
{
    /**
     * @param list<array{int,int|float}> $x
     * @param list<array{int,int|float}> $y
     * @param list<array{int,int|float}>|null $size
     * @return list<array{int,float,float}|array{int,float,float,float}>
     */
    public static function Pair(array $x, array $y, ?array $size = null): array
    {
        $xValues = self::Index($x);
        $yValues = self::Index($y);
        $sizeValues = $size === null ? null : self::Index($size);
        $paired = [];
        foreach ($xValues as $timestamp => $xValue) {
            if (!array_key_exists($timestamp, $yValues)
                || ($sizeValues !== null && !array_key_exists($timestamp, $sizeValues))) {
                continue;
            }
            $point = [$timestamp, $xValue, $yValues[$timestamp]];
            if ($sizeValues !== null) {
                $point[] = $sizeValues[$timestamp];
            }
            $paired[] = $point;
        }

        return $paired;
    }

    /** @param list<array{int,int|float}> $points @return array<int,float> */
    private static function Index(array $points): array
    {
        $result = [];
        $previous = 0;
        foreach ($points as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= $previous
                || (!is_int($point[1]) && !is_float($point[1]))
                || !is_finite((float) $point[1])) {
                throw new InvalidArgumentException('Scatter archive points must be finite and strictly time-ordered.');
            }
            $result[$point[0]] = (float) $point[1];
            $previous = $point[0];
        }

        return $result;
    }
}
