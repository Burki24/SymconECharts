<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

final class EChartsTimeSeriesPreview
{
    /** @param list<array{label:string,color:string,style:string}> $series @param array<string,mixed> $design */
    public static function CreateSvg(array $series, string $title, string $theme, array $design): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($theme);
        $series = $series !== [] ? array_slice($series, 0, 4) : [
            ['label' => 'Temperature', 'color' => '', 'style' => 'line'],
            ['label' => 'Humidity', 'color' => '', 'style' => 'area']
        ];
        $fallbackColors = $palette['seriesColors'];
        $effectiveColors = [];
        foreach ($series as $index => $item) {
            $effectiveColors[] = preg_match('/^#[0-9A-F]{6}$/i', $item['color']) === 1
                ? $item['color']
                : $fallbackColors[$index % count($fallbackColors)];
        }
        $legendPosition = (string) ($design['legendPosition'] ?? 'top');
        $lineWidth = 2.5 * max(50, min(200, (int) ($design['lineWidthPercent'] ?? 100))) / 100;
        $symbolRadius = 3.5 * max(50, min(200, (int) ($design['symbolSizePercent'] ?? 100))) / 100;
        $showSymbols = (bool) ($design['showSymbols'] ?? false);
        $smoothLines = (bool) ($design['smoothLines'] ?? false);
        $showGrid = (bool) ($design['showGrid'] ?? true);
        $showXAxis = (bool) ($design['showXAxis'] ?? true);
        $showYAxis = (bool) ($design['showYAxis'] ?? true);
        $areaOpacity = max(0, min(100, (int) ($design['areaOpacityPercent'] ?? 22))) / 100;
        $plotTop = $legendPosition === 'top' ? 92 : 62;
        $plotBottom = $legendPosition === 'bottom' ? 300 : 330;
        $content = '';

        if ($showGrid) {
            for ($index = 0; $index < 5; ++$index) {
                $y = $plotTop + ($plotBottom - $plotTop) * $index / 4;
                $content .= '<line x1="70" y1="' . self::N($y) . '" x2="680" y2="' . self::N($y)
                    . '" stroke="' . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="1"/>';
            }
        }
        if ($showXAxis) {
            $content .= '<line x1="70" y1="' . self::N($plotBottom) . '" x2="680" y2="'
                . self::N($plotBottom) . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '"/>';
        }
        if ($showYAxis) {
            $content .= '<line x1="70" y1="' . self::N($plotTop) . '" x2="70" y2="'
                . self::N($plotBottom) . '" stroke="'
                . SVGPreviewHelper::escape($effectiveColors[0] ?? $palette['border']) . '"/>';
        }

        foreach ($series as $index => $item) {
            $color = $effectiveColors[$index];
            $offset = $index * 24.0;
            $path = $smoothLines
                ? 'M 70 ' . self::N(250 - $offset)
                    . ' C 155 ' . self::N(210 + $offset) . ', 225 ' . self::N(270 - $offset)
                    . ', 310 ' . self::N(185 + $offset / 2)
                    . ' S 470 ' . self::N(225 - $offset) . ', 540 ' . self::N(135 + $offset)
                    . ' S 625 ' . self::N(170 + $offset / 2) . ', 680 ' . self::N(105 + $offset)
                : 'M 70 ' . self::N(250 - $offset)
                    . ' L 190 ' . self::N(215 + $offset)
                    . ' L 310 ' . self::N(185 + $offset / 2)
                    . ' L 430 ' . self::N(225 - $offset)
                    . ' L 540 ' . self::N(135 + $offset)
                    . ' L 680 ' . self::N(105 + $offset);
            if (($item['style'] ?? 'line') === 'area') {
                $content .= '<path d="' . $path . ' L 680 ' . self::N($plotBottom) . ' L 70 '
                    . self::N($plotBottom) . ' Z" fill="' . SVGPreviewHelper::escape($color)
                    . '" opacity="' . self::N($areaOpacity) . '"/>';
            }
            $content .= '<path d="' . $path . '" fill="none" stroke="' . SVGPreviewHelper::escape($color)
                . '" stroke-width="' . self::N($lineWidth) . '" stroke-linecap="round"/>';
            if ($showSymbols) {
                $content .= '<circle cx="310" cy="' . self::N(185 + $offset / 2) . '" r="'
                    . self::N($symbolRadius) . '" fill="' . SVGPreviewHelper::escape($color) . '"/>';
            }
        }

        if ($legendPosition !== 'hidden') {
            $legendY = $legendPosition === 'bottom' ? 352 : 64;
            $legendX = 80.0;
            foreach ($series as $index => $item) {
                $color = $effectiveColors[$index];
                $label = trim($item['label']) !== '' ? $item['label'] : 'Series ' . ($index + 1);
                $content .= '<line x1="' . self::N($legendX) . '" y1="' . self::N($legendY)
                    . '" x2="' . self::N($legendX + 22) . '" y2="' . self::N($legendY)
                    . '" stroke="' . SVGPreviewHelper::escape($color) . '" stroke-width="3"/>';
                $content .= '<text x="' . self::N($legendX + 29) . '" y="' . self::N($legendY + 5)
                    . '" fill="' . SVGPreviewHelper::escape($palette['text']) . '" font-size="13">'
                    . SVGPreviewHelper::escape($label) . '</text>';
                $legendX += min(190.0, 65.0 + strlen($label) * 7.0);
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 750 390" role="img"'
            . ' data-legend-position="' . SVGPreviewHelper::escape($legendPosition) . '"'
            . ' data-smooth-lines="' . ($smoothLines ? 'true' : 'false') . '"'
            . ' data-show-symbols="' . ($showSymbols ? 'true' : 'false') . '"'
            . ' data-area-opacity="' . self::N($areaOpacity) . '"'
            . ' data-axis-color="'
            . SVGPreviewHelper::escape($effectiveColors[0] ?? $palette['border']) . '">'
            . '<rect width="750" height="390" rx="18" fill="' . SVGPreviewHelper::escape($palette['background']) . '"/>'
            . '<text x="375" y="31" fill="' . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="20" font-weight="600" text-anchor="middle">'
            . SVGPreviewHelper::escape(trim($title) !== '' ? $title : 'Time Series') . '</text>'
            . $content . '</svg>';
    }

    private static function N(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
