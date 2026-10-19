<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Complete-source Polar limits and finite preview coordinates, independent of view windows. */
final class PolarData
{
    public const MAXIMUM_SOURCE_COUNT = 100;

    /** @param list<int|float> $values @return array{float,float} */
    public static function Bounds(array $values): array
    {
        if (!array_is_list($values) || count($values) < 1 || count($values) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Invalid Polar values.');
        }
        $minimum = 0.0;
        $maximum = 0.0;
        foreach ($values as $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                throw new InvalidArgumentException('Polar values must be finite numbers.');
            }
            $minimum = min($minimum, (float) $value);
            $maximum = max($maximum, (float) $value);
        }
        return [$minimum, $maximum > $minimum ? $maximum : 1.0];
    }

    public static function Fraction(float $value, float $minimum, float $maximum): float
    {
        if (!is_finite($value) || !is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            throw new InvalidArgumentException('Invalid Polar scale.');
        }
        $magnitude = max(1.0, abs($minimum), abs($maximum));
        return max(0.0, min(1.0, ($value / $magnitude - $minimum / $magnitude)
            / ($maximum / $magnitude - $minimum / $magnitude)));
    }
}
