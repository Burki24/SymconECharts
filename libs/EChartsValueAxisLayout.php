<?php

declare(strict_types=1);

namespace eCharts;

/** Layout of additional value axes in the TimeSeries and BarHistory designer previews. */
final class EChartsValueAxisLayout
{
    public static function IsValidSpacing(int $spacing): bool
    {
        return $spacing >= 0 && $spacing <= 120;
    }

    public static function Spacing(int $configured, float $automaticLimit, float $availableSpace, int $extraAxisCount): float
    {
        if ($extraAxisCount <= 0) {
            return 0.0;
        }
        $requested = $configured > 0 && self::IsValidSpacing($configured) ? $configured : $automaticLimit;

        return min($requested, max(0.0, $availableSpace) / $extraAxisCount);
    }
}
