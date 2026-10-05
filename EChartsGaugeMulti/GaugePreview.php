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
        if ($preset === 'weather-station') {
            return self::CreateWeatherStationSvg($items, $title, $palette);
        }
        if ($preset === 'tacho') {
            return self::CreateTachoSvg($items, $title, $palette);
        }
        if ($preset === 'chronograph') {
            return self::CreateChronographSvg($items, $title, $palette);
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

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette */
    private static function CreateWeatherStationSvg(array $items, string $title, array $palette): string
    {
        $items = array_slice($items, 0, 4);
        $count = count($items);
        $positions = match ($count) {
            2       => [[215.0, 215.0, 140.0], [540.0, 215.0, 95.0]],
            3       => [[215.0, 215.0, 140.0], [540.0, 125.0, 68.0], [540.0, 295.0, 68.0]],
            default => [[215.0, 215.0, 140.0], [460.0, 125.0, 62.0],
                [610.0, 125.0, 62.0], [535.0, 290.0, 62.0]]
        };
        $content = '';
        foreach ($items as $index => $item) {
            [$x, $y, $radius] = $positions[$index];
            $content .= self::InstrumentDialSvg($item, $x, $y, $radius, $palette, self::RingColor($index, $palette));
        }

        return self::SvgDocument($title, $palette, $content, 'weather-station');
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette */
    private static function CreateTachoSvg(array $items, string $title, array $palette): string
    {
        $items = array_slice($items, 0, 3);
        $positions = [[360.0, 205.0, 125.0], [110.0, 215.0, 78.0], [610.0, 215.0, 78.0]];
        $content = '';
        foreach ($items as $index => $item) {
            [$x, $y, $radius] = $positions[$index];
            $content .= self::InstrumentDialSvg($item, $x, $y, $radius, $palette, '#F0442D');
        }

        return self::SvgDocument($title, $palette, $content, 'tacho');
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette */
    private static function CreateChronographSvg(array $items, string $title, array $palette): string
    {
        $items = array_slice($items, 0, 6);
        $subCount = count($items) - 1;
        $offsets = match ($subCount) {
            1       => [[0.0, 0.52]],
            2       => [[-0.48, 0.28], [0.48, 0.28]],
            3       => [[-0.48, -0.08], [0.48, -0.08], [0.0, 0.53]],
            4       => [[-0.43, -0.32], [0.43, -0.32], [-0.43, 0.36], [0.43, 0.36]],
            5       => [[-0.48, -0.18], [0.48, -0.18], [-0.46, 0.43], [0.46, 0.43], [0.0, 0.55]],
            default => []
        };
        $centerY = $title === '' ? 205.0 : 219.0;
        $radius = $title === '' ? 170.0 : 155.0;
        $content = self::InstrumentDialSvg($items[0], 360.0, $centerY, $radius, $palette, self::RingColor(0, $palette), true, true);
        $subRadius = $radius * ($subCount <= 3 ? 0.23 : 0.19);
        foreach ($offsets as $index => [$offsetX, $offsetY]) {
            $content .= self::InstrumentDialSvg(
                $items[$index + 1],
                360.0 + $offsetX * $radius,
                $centerY + $offsetY * $radius,
                $subRadius,
                $palette,
                self::RingColor($index + 1, $palette),
                true
            );
        }
        $content .= self::InstrumentPointerSvg(
            $items[0],
            360.0,
            $centerY,
            $radius,
            self::RingColor(0, $palette)
        );

        return self::SvgDocument($title, $palette, $content, 'chronograph');
    }

    /** @param array<string, mixed> $item @param array<string, string> $palette */
    private static function InstrumentDialSvg(
        array $item,
        float $x,
        float $y,
        float $radius,
        array $palette,
        string $color,
        bool $embedded = false,
        bool $primary = false
    ): string {
        $startX = $x - $radius * 0.707;
        $endX = $x + $radius * 0.707;
        $arcY = $y + $radius * 0.707;
        $result = '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
            . '" r="' . self::N($radius * 1.14) . '" fill="' . SVGPreviewHelper::escape($palette['background'])
            . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '" stroke-width="2"/>';
        $result .= '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
            . '" r="' . self::N($radius * 1.04) . '" fill="none" stroke="'
            . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="3"/>';
        $result .= '<path d="M ' . self::N($startX) . ' ' . self::N($arcY)
            . ' A ' . self::N($radius) . ' ' . self::N($radius) . ' 0 1 1 '
            . self::N($endX) . ' ' . self::N($arcY) . '" fill="none" stroke="'
            . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="'
            . self::N(max(5.0, $radius * 0.09)) . '"/>';
        for ($tick = 0; $tick <= 10; $tick++) {
            $tickAngle = deg2rad(225.0 - 27.0 * $tick);
            $inner = $radius * 0.86;
            $outer = $radius * 0.97;
            $result .= '<line x1="' . self::N($x + cos($tickAngle) * $inner)
                . '" y1="' . self::N($y - sin($tickAngle) * $inner)
                . '" x2="' . self::N($x + cos($tickAngle) * $outer)
                . '" y2="' . self::N($y - sin($tickAngle) * $outer)
                . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '" stroke-width="2"/>';
        }
        $result .= self::InstrumentPointerSvg($item, $x, $y, $radius, $color);
        if ($embedded) {
            foreach ([[$startX, $arcY, $item['minimum'] ?? 0.0], [$endX, $arcY, $item['maximum'] ?? 100.0]] as [$labelX, $labelY, $limit]) {
                $result .= '<text x="' . self::N((float) $labelX) . '" y="' . self::N((float) $labelY)
                    . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                    . '" font-size="' . self::N(max(5.0, $radius * 0.09)) . '" text-anchor="middle">'
                    . SVGPreviewHelper::escape((string) $limit) . '</text>';
            }
        }
        $result .= '<text x="' . self::N($x) . '" y="' . self::N($y - $radius * ($primary ? 0.16 : 0.22))
            . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
            . '" font-size="' . self::N(max($embedded ? 5.0 : 10.0, $radius * 0.12))
            . '" text-anchor="middle">'
            . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
        $result .= '<text x="' . self::N($x) . '" y="' . self::N($y + $radius * ($primary ? 0.74 : 0.47))
            . '" fill="' . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="' . self::N(max($embedded ? 6.0 : 12.0, $radius * 0.16))
            . '" font-weight="700" text-anchor="middle">'
            . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';

        return $result;
    }

    /** @param array<string, mixed> $item */
    private static function InstrumentPointerSvg(
        array $item,
        float $x,
        float $y,
        float $radius,
        string $color
    ): string {
        $angle = deg2rad(225.0 - 270.0 * self::ValueRatio($item));

        return '<line x1="' . self::N($x) . '" y1="' . self::N($y)
            . '" x2="' . self::N($x + cos($angle) * $radius * 0.65)
            . '" y2="' . self::N($y - sin($angle) * $radius * 0.65)
            . '" stroke="' . SVGPreviewHelper::escape($color)
            . '" stroke-width="' . self::N(max(3.0, $radius * 0.04)) . '" stroke-linecap="round"/>'
            . '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
            . '" r="' . self::N(max(4.0, $radius * 0.07)) . '" fill="'
            . SVGPreviewHelper::escape($color) . '"/>';
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
