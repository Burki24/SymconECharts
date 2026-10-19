<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

/** Ordered signed accumulation shared by the complete model and its preview. */
final class WaterfallData
{
    public const MAXIMUM_SOURCE_COUNT = 100;

    /** @return list<float> Running totals, including the absolute start value. */
    public static function Totals(array $values): array
    {
        if (!array_is_list($values) || $values === [] || count($values) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Invalid Waterfall values.');
        }
        $totals = [];
        $total = 0.0;
        foreach ($values as $index => $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                throw new InvalidArgumentException('Waterfall values must be finite numbers.');
            }
            $total = $index === 0 ? (float) $value : $total + $value;
            if (!is_finite($total)) {
                throw new RuntimeException('The Waterfall total is not finite.');
            }
            $totals[] = $total;
        }
        return $totals;
    }
}
