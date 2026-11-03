<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/helper/SVGPreviewHelper.php';

/** Shared bounded SVG point geometry, extracted from the TimeSeries preview. */
final class EChartsPreviewSymbol
{
    public static function Symbol(string $symbol, float $x, float $y, float $radius, string $color, array $style = []): string
    {
        if ($symbol === 'none' || $radius <= 0) {
            return '';
        }
        $fill = SVGPreviewHelper::escape($color);
        $xValue = self::N($x);
        $yValue = self::N($y);
        $radiusValue = self::N($radius);

        $svg = match ($symbol) {
            'rect', 'roundRect' => '<rect x="' . self::N($x - $radius) . '" y="' . self::N($y - $radius)
                . '" width="' . self::N($radius * 2) . '" height="' . self::N($radius * 2) . '"'
                . ($symbol === 'roundRect' ? ' rx="' . self::N($radius * 0.45) . '"' : '') . ' fill="' . $fill . '"/>',
            'triangle' => '<path d="M ' . $xValue . ' ' . self::N($y - $radius)
                . ' L ' . self::N($x + $radius) . ' ' . self::N($y + $radius)
                . ' L ' . self::N($x - $radius) . ' ' . self::N($y + $radius) . ' Z" fill="' . $fill . '"/>',
            'diamond' => '<path d="M ' . $xValue . ' ' . self::N($y - $radius)
                . ' L ' . self::N($x + $radius) . ' ' . $yValue
                . ' L ' . $xValue . ' ' . self::N($y + $radius)
                . ' L ' . self::N($x - $radius) . ' ' . $yValue . ' Z" fill="' . $fill . '"/>',
            'pin', 'arrow' => '<path d="M ' . $xValue . ' ' . self::N($y - $radius)
                . ' L ' . self::N($x + $radius) . ' ' . $yValue
                . ' L ' . $xValue . ' ' . self::N($y + $radius)
                . ' L ' . self::N($x - $radius) . ' ' . $yValue . ' Z" fill="' . $fill . '"/>',
            default => '<circle cx="' . $xValue . '" cy="' . $yValue . '" r="' . $radiusValue
                . '" fill="' . $fill . '"/>'
        };
        if ($style === []) {
            return $svg;
        }
        $border = SVGPreviewHelper::escape((string) ($style['borderColor'] ?? $color));
        $width = max(0, min(8, (int) ($style['borderWidth'] ?? 0)));
        if ($symbol === 'emptyCircle') {
            $svg = str_replace('fill="' . $fill . '"', 'fill="#FFFFFF"', $svg);
            $width = max(2, $width);
        }
        $svg = str_replace('/>', ' stroke="' . $border . '" stroke-width="' . $width . '"/>', $svg);
        return '<g transform="rotate(' . self::N(-max(-180, min(180, (int) ($style['rotate'] ?? 0)))) . ' '
            . $xValue . ' ' . $yValue . ')" opacity="' . self::N(max(0, min(100, (int) ($style['opacityPercent'] ?? 100))) / 100) . '">' . $svg . '</g>';
    }

    private static function N(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}

