<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Family limits and shares of a complete, explicitly chosen population. */
final class PieData
{
    public const MAXIMUM_SOURCE_COUNT = 100;

    /** @param list<int|float> $values @return list<float> Percentages, including true zero weights. */
    public static function Shares(array $values): array
    {
        if (!array_is_list($values) || count($values) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Invalid Pie weights.');
        }
        foreach ($values as $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0) {
                throw new InvalidArgumentException('Pie weights must be finite and non-negative.');
            }
        }
        $maximum = $values === [] ? 0.0 : (float) max($values);
        if ($maximum === 0.0) {
            return array_fill(0, count($values), 0.0);
        }
        $scaled = array_map(static fn (float|int $value): float => $value / $maximum, $values);
        $sum = array_sum($scaled);
        return array_map(static fn (float $value): float => $value / $sum * 100, $scaled);
    }
}
