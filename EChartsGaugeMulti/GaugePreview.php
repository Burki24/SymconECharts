<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

final class EChartsGaugeMultiPreview
{
    /** @param list<array<string, mixed>> $items */
    public static function CreateSvg(array $items, string $title, string $theme, string $preset = 'multi-title'): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($theme);
        if ($preset === 'ring-grid') {
            return self::CreateRingGridSvg($items, $title, $palette);
        }
        if ($preset === 'ring-concentric') {
            return self::CreateConcentricSvg($items, $title, $palette);
        }

        $items = array_slice($items, 0, 4);
        $count = count($items);
        $columns = $count <= 2 ? max(1, $count) : 2;
        $rows = (int) ceil($count / $columns);
        $cellWidth = 720.0 / $columns;
        $cellHeight = 400.0 / max(1, $rows);
        $content = '';

        foreach ($items as $index => $item) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);
            $centerX = $column * $cellWidth + $cellWidth / 2;
            $centerY = $row * $cellHeight + $cellHeight * 0.56;
            $radius = min($cellWidth * 0.31, $cellHeight * 0.36);
            $minimum = (float) ($item['minimum'] ?? 0.0);
            $maximum = (float) ($item['maximum'] ?? 100.0);
            $value = (float) ($item['value'] ?? $minimum);
            $ratio = $maximum > $minimum ? max(0.0, min(1.0, ($value - $minimum) / ($maximum - $minimum))) : 0.0;
            $angle = deg2rad(225.0 - 270.0 * $ratio);
            $pointerX = $centerX + cos($angle) * $radius * 0.68;
            $pointerY = $centerY - sin($angle) * $radius * 0.68;
            $decimals = max(0, min(6, (int) ($item['decimals'] ?? 1)));
            $formatted = number_format($value, $decimals, ',', '.');
            $unit = trim((string) ($item['unit'] ?? ''));
            $label = trim((string) ($item['label'] ?? ''));
            $startX = $centerX - $radius * 0.707;
            $startY = $centerY + $radius * 0.707;
            $endX = $centerX + $radius * 0.707;
            $endY = $startY;

            $content .= '<path d="M ' . self::N($startX) . ' ' . self::N($startY)
                . ' A ' . self::N($radius) . ' ' . self::N($radius) . ' 0 1 1 '
                . self::N($endX) . ' ' . self::N($endY) . '" fill="none" stroke="'
                . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="' . self::N(max(8.0, $radius * 0.12))
                . '" stroke-linecap="round"/>';
            $content .= '<line x1="' . self::N($centerX) . '" y1="' . self::N($centerY)
                . '" x2="' . self::N($pointerX) . '" y2="' . self::N($pointerY)
                . '" stroke="' . SVGPreviewHelper::escape($palette['accent'])
                . '" stroke-width="' . self::N(max(3.0, $radius * 0.045)) . '" stroke-linecap="round"/>';
            $content .= '<circle cx="' . self::N($centerX) . '" cy="' . self::N($centerY)
                . '" r="' . self::N(max(5.0, $radius * 0.07)) . '" fill="'
                . SVGPreviewHelper::escape($palette['accent']) . '"/>';
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY + $radius * 0.98)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="' . self::N(max(14.0, $radius * 0.18)) . '" font-weight="700" text-anchor="middle">'
                . SVGPreviewHelper::escape(trim($formatted . ' ' . $unit)) . '</text>';
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY - $radius * 0.18)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="' . self::N(max(11.0, $radius * 0.13)) . '" text-anchor="middle">'
                . SVGPreviewHelper::escape($label) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'multi-title');
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette */
    private static function CreateRingGridSvg(array $items, string $title, array $palette): string
    {
        $items = array_slice($items, 0, 4);
        $count = count($items);
        $columns = $count <= 2 ? max(1, $count) : 2;
        $rows = (int) ceil($count / $columns);
        $cellWidth = 720.0 / $columns;
        $cellHeight = 400.0 / max(1, $rows);
        $content = '';

        foreach ($items as $index => $item) {
            $centerX = ($index % $columns) * $cellWidth + $cellWidth / 2;
            $centerY = intdiv($index, $columns) * $cellHeight + $cellHeight * 0.55;
            $radius = min($cellWidth * 0.31, $cellHeight * 0.36);
            $color = self::RingColor($index, $palette);
            $content .= self::RingCircles(
                $centerX,
                $centerY,
                $radius,
                max(6.0, $radius * 0.13),
                self::ValueRatio($item),
                $color,
                $palette['track']
            );
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY - 8.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="13" text-anchor="middle">'
                . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY + 20.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="18" font-weight="700" text-anchor="middle">'
                . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'ring-grid');
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette */
    private static function CreateConcentricSvg(array $items, string $title, array $palette): string
    {
        $items = array_slice($items, 0, 4);
        $content = '';
        foreach ($items as $index => $item) {
            $radius = 150.0 - $index * 30.0;
            $color = self::RingColor($index, $palette);
            $content .= self::RingCircles(
                215.0,
                210.0,
                $radius,
                18.0,
                self::ValueRatio($item),
                $color,
                $palette['track']
            );
            $legendY = 110.0 + $index * 62.0;
            $content .= '<circle cx="420" cy="' . self::N($legendY)
                . '" r="7" fill="' . SVGPreviewHelper::escape($color) . '"/>';
            $content .= '<text x="438" y="' . self::N($legendY - 3.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="14">' . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
            $content .= '<text x="438" y="' . self::N($legendY + 20.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="17" font-weight="700">'
                . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'ring-concentric');
    }

    /** @param array<string, string> $palette */
    private static function SvgDocument(string $title, array $palette, string $content, string $preset): string
    {
        $titleElement = trim($title) === '' ? '' : '<text x="360" y="28" fill="'
            . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="18" font-weight="600" text-anchor="middle">'
            . SVGPreviewHelper::escape($title) . '</text>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 400" role="img" data-preset="'
            . SVGPreviewHelper::escape($preset) . '">'
            . '<rect width="720" height="400" rx="16" fill="' . SVGPreviewHelper::escape($palette['background']) . '"/>'
            . $titleElement . $content . '</svg>';
    }

    private static function RingCircles(
        float $centerX,
        float $centerY,
        float $radius,
        float $width,
        float $ratio,
        string $color,
        string $track
    ): string {
        $circumference = 2.0 * pi() * $radius;
        $base = '<circle cx="' . self::N($centerX) . '" cy="' . self::N($centerY)
            . '" r="' . self::N($radius) . '" fill="none" stroke-width="' . self::N($width) . '"';

        return $base . ' stroke="' . SVGPreviewHelper::escape($track) . '"/>'
            . $base . ' stroke="' . SVGPreviewHelper::escape($color)
            . '" stroke-dasharray="' . self::N($circumference * $ratio) . ' ' . self::N($circumference)
            . '" transform="rotate(-90 ' . self::N($centerX) . ' ' . self::N($centerY) . ')"/>';
    }

    /** @param array<string, mixed> $item */
    private static function ValueRatio(array $item): float
    {
        $minimum = (float) ($item['minimum'] ?? 0.0);
        $maximum = (float) ($item['maximum'] ?? 100.0);
        if ($maximum <= $minimum) {
            return 0.0;
        }

        return max(0.0, min(1.0, ((float) ($item['value'] ?? $minimum) - $minimum) / ($maximum - $minimum)));
    }

    /** @param array<string, mixed> $item */
    private static function FormattedValue(array $item): string
    {
        $decimals = max(0, min(6, (int) ($item['decimals'] ?? 1)));

        return trim(number_format((float) ($item['value'] ?? 0.0), $decimals, ',', '.')
            . ' ' . (string) ($item['unit'] ?? ''));
    }

    /** @param array<string, string> $palette */
    private static function RingColor(int $index, array $palette): string
    {
        $colors = [$palette['accent'], '#5C83E9', '#DB7393', '#E6A547'];

        return $colors[$index % count($colors)];
    }

    private static function N(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
