<?php

declare(strict_types=1);

namespace eCharts;

/** Shared thickness for visible ECharts zoom and navigation sliders, in CSS pixels. */
final class EChartsSliderSize
{
    public static function IsValid(int $configured): bool
    {
        return $configured >= 0 && $configured <= 64;
    }

    public static function Resolve(int $configured, int $automatic): int
    {
        return $configured > 0 && self::IsValid($configured) ? $configured : $automatic;
    }
}
