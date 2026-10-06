<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

final class EChartsTimeSeriesPreview
{
    /** @param list<array{label:string,color:string,style:string,design?:array<string,mixed>}> $series @param array<string,mixed> $design */
    public static function CreateSvg(
        array $series,
        string $title,
        string $theme,
        array $design,
        bool $adaptToBackground = false,
        string $backgroundColor = '',
        int $backgroundOpacityPercent = 35
    ): string {
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
        $backgroundOpacityPercent = max(0, min(100, $backgroundOpacityPercent));
        $backgroundColor = $adaptToBackground && preg_match('/^#[0-9A-F]{6}$/i', $backgroundColor) === 1
            ? strtoupper($backgroundColor)
            : $palette['background'];
        $plotTop = $legendPosition === 'top' ? 92 : 62;
        $plotBottom = $legendPosition === 'bottom' ? 300 : 330;
        $content = '';
        $definitions = '';

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
            $sourceDesign = is_array($item['design'] ?? null) ? $item['design'] : [];
            $sourceLineWidth = $sourceDesign !== []
                ? 2.5 * max(50, min(200, (int) ($sourceDesign['lineWidthPercent'] ?? 100))) / 100
                : $lineWidth;
            $sourceSmooth = $sourceDesign !== []
                ? (bool) ($sourceDesign['smoothLine'] ?? false)
                : $smoothLines;
            $sourceSymbol = $sourceDesign !== []
                ? (string) ($sourceDesign['pointSymbol'] ?? 'none')
                : ($showSymbols ? 'circle' : 'none');
            $sourceSymbolRadius = $sourceDesign !== []
                ? 3.5 * max(50, min(200, (int) ($sourceDesign['pointSizePercent'] ?? 100))) / 100
                : $symbolRadius;
            $sourceAreaOpacity = $sourceDesign !== []
                ? max(0, min(100, (int) ($sourceDesign['areaOpacityPercent'] ?? 22))) / 100
                : $areaOpacity;
            $offset = $index * 24.0;
            $path = $sourceSmooth
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
                $fill = $color;
                $fillMode = (string) ($sourceDesign['areaFillMode'] ?? 'color');
                if ($fillMode === 'gradient') {
                    $gradientID = 'area-gradient-' . $index;
                    $gradientEnd = preg_match('/^#[0-9A-F]{6}$/i', (string) ($sourceDesign['areaGradientColor'] ?? '')) === 1
                        ? (string) $sourceDesign['areaGradientColor']
                        : $color;
                    $gradientEndOpacity = $gradientEnd === $color ? '0' : '1';
                    $definitions .= '<linearGradient id="' . $gradientID . '" x1="0" y1="0" x2="0" y2="1">'
                        . '<stop offset="0%" stop-color="' . SVGPreviewHelper::escape($color) . '"/>'
                        . '<stop offset="100%" stop-color="' . SVGPreviewHelper::escape($gradientEnd)
                        . '" stop-opacity="' . $gradientEndOpacity . '"/></linearGradient>';
                    $fill = 'url(#' . $gradientID . ')';
                } elseif ($fillMode === 'svg'
                    && str_starts_with((string) ($sourceDesign['areaPatternImage'] ?? ''), 'data:image/svg+xml;base64,')) {
                    $patternID = 'area-pattern-' . $index;
                    $patternWidth = 64.0 * max(25, min(400, (int) ($sourceDesign['areaSVGSizePercent'] ?? 100))) / 100;
                    $aspectRatio = max(0.05, min(20.0, (float) ($sourceDesign['areaPatternAspectRatio'] ?? 1.0)));
                    $patternHeight = $patternWidth / $aspectRatio;
                    $definitions .= '<pattern id="' . $patternID . '" width="' . self::N($patternWidth)
                        . '" height="' . self::N($patternHeight) . '" patternUnits="userSpaceOnUse">'
                        . '<image href="' . SVGPreviewHelper::escape((string) $sourceDesign['areaPatternImage'])
                        . '" width="' . self::N($patternWidth) . '" height="' . self::N($patternHeight)
                        . '" preserveAspectRatio="xMidYMid meet"/></pattern>';
                    $fill = 'url(#' . $patternID . ')';
                }
                $content .= '<path d="' . $path . ' L 680 ' . self::N($plotBottom) . ' L 70 '
                    . self::N($plotBottom) . ' Z" fill="' . SVGPreviewHelper::escape($fill)
                    . '" opacity="' . self::N($sourceAreaOpacity) . '"/>';
            }
            $dashArray = match ((string) ($sourceDesign['lineType'] ?? 'solid')) {
                'dashed' => '10 7',
                'dotted' => '2 6',
                default  => ''
            };
            $content .= '<path d="' . $path . '" fill="none" stroke="' . SVGPreviewHelper::escape($color)
                . '" stroke-width="' . self::N($sourceLineWidth) . '" stroke-linecap="round"'
                . ($dashArray !== '' ? ' stroke-dasharray="' . $dashArray . '"' : '') . '/>';
            if ($sourceSymbol !== 'none') {
                $content .= self::Symbol(
                    $sourceSymbol,
                    310.0,
                    185 + $offset / 2,
                    $sourceSymbolRadius,
                    $color
                );
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
            . ' data-adapt-to-background="' . ($adaptToBackground ? 'true' : 'false') . '"'
            . ' data-background-opacity="' . $backgroundOpacityPercent . '"'
            . ' data-area-opacity="' . self::N($areaOpacity) . '"'
            . ' data-axis-color="'
            . SVGPreviewHelper::escape($effectiveColors[0] ?? $palette['border']) . '">'
            . '<rect width="750" height="390" rx="18" fill="' . SVGPreviewHelper::escape($backgroundColor) . '"'
            . ($adaptToBackground ? ' fill-opacity="' . self::N($backgroundOpacityPercent / 100) . '"' : '') . '/>'
            . ($definitions !== '' ? '<defs>' . $definitions . '</defs>' : '')
            . '<text x="375" y="31" fill="' . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="20" font-weight="600" text-anchor="middle">'
            . SVGPreviewHelper::escape(trim($title) !== '' ? $title : 'Time Series') . '</text>'
            . $content . '</svg>';
    }

    private static function Symbol(string $symbol, float $x, float $y, float $radius, string $color): string
    {
        $fill = SVGPreviewHelper::escape($color);
        $xValue = self::N($x);
        $yValue = self::N($y);
        $radiusValue = self::N($radius);

        return match ($symbol) {
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
    }

    private static function N(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
