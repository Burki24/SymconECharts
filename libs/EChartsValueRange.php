<?php

declare(strict_types=1);

namespace SymconECharts;

/** Numeric range validation only; callers own projection, axis semantics and messages. */
final class EChartsValueRange
{
    public static function IsValid(mixed $minimum, mixed $maximum): bool
    {
        return (is_int($minimum) || is_float($minimum)) && (is_int($maximum) || is_float($maximum))
            && is_finite((float) $minimum) && is_finite((float) $maximum)
            && (float) $minimum < (float) $maximum && is_finite((float) $maximum - (float) $minimum);
    }
}
