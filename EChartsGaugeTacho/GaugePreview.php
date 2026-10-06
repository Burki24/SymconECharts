<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

final class EChartsGaugeTachoPreview
{
    /** @param list<array<string, mixed>> $items @param array<string, int|string> $style */
    public static function CreateSvg(
        array $items,
        string $title,
        string $theme,
        string $preset = 'multi-title',
        array $style = []
    ): string {
        $palette = self::StyledPalette(EChartsAsset::ThemePreviewPalette($theme), $style);
        if ($preset === 'ring-grid') {
            return self::CreateRingGridSvg($items, $title, $palette, $style);
        }
        if ($preset === 'ring-concentric') {
            return self::CreateConcentricSvg($items, $title, $palette, $style);
        }
        if ($preset === 'weather-station') {
            return self::CreateWeatherStationSvg($items, $title, $palette, $style);
        }
        if ($preset === 'tacho') {
            return self::CreateTachoSvg($items, $title, $palette, $style);
        }
        if ($preset === 'chronograph') {
            return self::CreateChronographSvg($items, $title, $palette, $style);
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
            $value = (float) ($item['value'] ?? 0.0);
            $decimals = max(0, min(6, (int) ($item['decimals'] ?? 1)));
            $formatted = number_format($value, $decimals, ',', '.');
            $unit = trim((string) ($item['unit'] ?? ''));
            $label = trim((string) ($item['label'] ?? ''));
            $startX = $centerX - $radius * 0.707;
            $startY = $centerY + $radius * 0.707;
            $endX = $centerX + $radius * 0.707;
            $endY = $startY;

            $content .= self::DialPlateSvg($centerX, $centerY, $radius, $palette, $style, false);
            $content .= '<path d="M ' . self::N($startX) . ' ' . self::N($startY)
                . ' A ' . self::N($radius) . ' ' . self::N($radius) . ' 0 1 1 '
                . self::N($endX) . ' ' . self::N($endY) . '" fill="none" stroke="'
                . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="' . self::N(max(8.0, $radius * 0.12))
                . '" stroke-linecap="round"/>';
            $content .= self::InstrumentPointerSvg($item, $centerX, $centerY, $radius, $palette['accent'], $style);
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY + $radius * 0.98)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="' . self::N(max(14.0, $radius * 0.18) * self::Scale($style, 'valueFontSizePercent'))
                . '" font-weight="700" text-anchor="middle">'
                . SVGPreviewHelper::escape(trim($formatted . ' ' . $unit)) . '</text>';
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY - $radius * 0.18)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="' . self::N(max(11.0, $radius * 0.13) * self::Scale($style, 'titleFontSizePercent'))
                . '" text-anchor="middle">'
                . SVGPreviewHelper::escape($label) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'multi-title', $style);
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette @param array<string, int|string> $style */
    private static function CreateRingGridSvg(array $items, string $title, array $palette, array $style): string
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
            $color = ($style['colorMode'] ?? 'theme') === 'custom'
                ? self::StyleColor($style, 'progressColor', $palette['accent'])
                : self::RingColor($index, $palette);
            $content .= self::RingCircles(
                $centerX,
                $centerY,
                $radius,
                max(6.0, $radius * 0.13) * self::Scale($style, 'ringWidthPercent'),
                self::ValueRatio($item),
                $color,
                $palette['track']
            );
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY - 8.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="' . self::N(13.0 * self::Scale($style, 'titleFontSizePercent')) . '" text-anchor="middle">'
                . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
            $content .= '<text x="' . self::N($centerX) . '" y="' . self::N($centerY + 20.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="' . self::N(18.0 * self::Scale($style, 'valueFontSizePercent'))
                . '" font-weight="700" text-anchor="middle">'
                . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'ring-grid', $style);
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette @param array<string, int|string> $style */
    private static function CreateConcentricSvg(array $items, string $title, array $palette, array $style): string
    {
        $items = array_slice($items, 0, 4);
        $content = '';
        foreach ($items as $index => $item) {
            $radius = 150.0 - $index * 30.0;
            $color = ($style['colorMode'] ?? 'theme') === 'custom'
                ? self::StyleColor($style, 'progressColor', $palette['accent'])
                : self::RingColor($index, $palette);
            $content .= self::RingCircles(
                215.0,
                210.0,
                $radius,
                18.0 * self::Scale($style, 'ringWidthPercent'),
                self::ValueRatio($item),
                $color,
                $palette['track']
            );
            $legendY = 110.0 + $index * 62.0;
            $content .= '<circle cx="420" cy="' . self::N($legendY)
                . '" r="7" fill="' . SVGPreviewHelper::escape($color) . '"/>';
            $content .= '<text x="438" y="' . self::N($legendY - 3.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                . '" font-size="' . self::N(14.0 * self::Scale($style, 'titleFontSizePercent')) . '">'
                . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
            $content .= '<text x="438" y="' . self::N($legendY + 20.0)
                . '" fill="' . SVGPreviewHelper::escape($palette['text'])
                . '" font-size="' . self::N(17.0 * self::Scale($style, 'valueFontSizePercent')) . '" font-weight="700">'
                . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';
        }

        return self::SvgDocument($title, $palette, $content, 'ring-concentric', $style);
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette @param array<string, int|string> $style */
    private static function CreateWeatherStationSvg(array $items, string $title, array $palette, array $style): string
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
            $content .= self::InstrumentDialSvg(
                $item,
                $x,
                $y,
                $radius,
                $palette,
                self::RingColor($index, $palette),
                $style
            );
        }

        return self::SvgDocument($title, $palette, $content, 'weather-station', $style);
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette @param array<string, int|string> $style */
    private static function CreateTachoSvg(array $items, string $title, array $palette, array $style): string
    {
        $items = array_slice($items, 0, 5);
        $positions = match (count($items)) {
            4       => [[335.0, 205.0, 115.0], [95.0, 205.0, 72.0], [610.0, 110.0, 72.0],
                [610.0, 300.0, 72.0]],
            5       => [[360.0, 205.0, 115.0], [125.0, 110.0, 72.0], [595.0, 110.0, 72.0],
                [125.0, 300.0, 72.0], [595.0, 300.0, 72.0]],
            default => [[360.0, 165.0, 115.0], [125.0, 180.0, 72.0], [595.0, 180.0, 72.0]]
        };
        $content = '';
        foreach ($items as $index => $item) {
            [$x, $y, $radius] = $positions[$index];
            $content .= self::InstrumentDialSvg(
                $item,
                $x,
                $y,
                $radius,
                $palette,
                '#F0442D',
                array_merge($style, is_array($item['style'] ?? null) ? $item['style'] : [])
            );
        }

        return self::SvgDocument($title, $palette, $content, 'tacho', $style);
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $palette @param array<string, int|string> $style */
    private static function CreateChronographSvg(array $items, string $title, array $palette, array $style): string
    {
        $items = array_slice($items, 0, 5);
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
        $content = self::InstrumentDialSvg(
            $items[0],
            360.0,
            $centerY,
            $radius,
            $palette,
            self::RingColor(0, $palette),
            array_merge($style, is_array($items[0]['style'] ?? null) ? $items[0]['style'] : []),
            true,
            true
        );
        $subRadius = $radius * ($subCount <= 3 ? 0.23 : 0.19);
        foreach ($offsets as $index => [$offsetX, $offsetY]) {
            $content .= self::InstrumentDialSvg(
                $items[$index + 1],
                360.0 + $offsetX * $radius,
                $centerY + $offsetY * $radius,
                $subRadius,
                $palette,
                self::RingColor($index + 1, $palette),
                array_merge($style, is_array($items[$index + 1]['style'] ?? null) ? $items[$index + 1]['style'] : []),
                true
            );
        }
        $content .= self::InstrumentPointerSvg(
            $items[0],
            360.0,
            $centerY,
            $radius,
            self::RingColor(0, $palette),
            array_merge($style, is_array($items[0]['style'] ?? null) ? $items[0]['style'] : [])
        );

        return self::SvgDocument($title, $palette, $content, 'chronograph', $style);
    }

    /** @param array<string, mixed> $item @param array<string, string> $palette @param array<string, int|string> $style */
    private static function InstrumentDialSvg(
        array $item,
        float $x,
        float $y,
        float $radius,
        array $palette,
        string $color,
        array $style,
        bool $embedded = false,
        bool $primary = false
    ): string {
        $startX = $x - $radius * 0.707;
        $endX = $x + $radius * 0.707;
        $arcY = $y + $radius * 0.707;
        $result = self::DialPlateSvg($x, $y, $radius, $palette, $style, true);
        $result .= '<path d="M ' . self::N($startX) . ' ' . self::N($arcY)
            . ' A ' . self::N($radius) . ' ' . self::N($radius) . ' 0 1 1 '
            . self::N($endX) . ' ' . self::N($arcY) . '" fill="none" stroke="'
            . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="'
            . self::N(max(5.0, $radius * 0.09)) . '"/>';
        $majorSplitCount = (int) ($style['majorSplitCount'] ?? 0);
        $majorSplitCount = $majorSplitCount >= 2 && $majorSplitCount <= 24 ? $majorSplitCount : 10;
        for ($tick = 0; $tick <= $majorSplitCount; $tick++) {
            $tickAngle = deg2rad(225.0 - 270.0 / $majorSplitCount * $tick);
            $inner = $radius * 0.86;
            $outer = $radius * 0.97;
            $result .= '<line x1="' . self::N($x + cos($tickAngle) * $inner)
                . '" y1="' . self::N($y - sin($tickAngle) * $inner)
                . '" x2="' . self::N($x + cos($tickAngle) * $outer)
                . '" y2="' . self::N($y - sin($tickAngle) * $outer)
                . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '" stroke-width="2"/>';
        }
        $result .= self::InstrumentPointerSvg($item, $x, $y, $radius, $color, $style);
        if ($embedded) {
            foreach ([[$startX, $arcY, $item['minimum'] ?? 0.0], [$endX, $arcY, $item['maximum'] ?? 100.0]] as [$labelX, $labelY, $limit]) {
                $result .= '<text x="' . self::N((float) $labelX) . '" y="' . self::N((float) $labelY)
                    . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
                    . '" font-size="' . self::N(max(5.0, $radius * 0.09) * self::Scale($style, 'scaleFontSizePercent'))
                    . '" text-anchor="middle">'
                    . SVGPreviewHelper::escape((string) $limit) . '</text>';
            }
        }
        $result .= '<text x="' . self::N($x) . '" y="' . self::N($y - $radius * ($primary ? 0.16 : 0.22))
            . '" fill="' . SVGPreviewHelper::escape($palette['muted'])
            . '" font-size="' . self::N(max($embedded ? 5.0 : 10.0, $radius * 0.12)
                * self::Scale($style, 'titleFontSizePercent'))
            . '" text-anchor="middle">'
            . SVGPreviewHelper::escape((string) ($item['label'] ?? '')) . '</text>';
        $result .= '<text x="' . self::N($x) . '" y="' . self::N($y + $radius * ($primary ? 0.74 : 0.47))
            . '" fill="' . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="' . self::N(max($embedded ? 6.0 : 12.0, $radius * 0.16)
                * self::Scale($style, 'valueFontSizePercent'))
            . '" font-weight="700" text-anchor="middle">'
            . SVGPreviewHelper::escape(self::FormattedValue($item)) . '</text>';

        return $result;
    }

    /** @param array<string, mixed> $item @param array<string, int|string> $style */
    private static function InstrumentPointerSvg(
        array $item,
        float $x,
        float $y,
        float $radius,
        string $color,
        array $style
    ): string {
        $angle = deg2rad(225.0 - 270.0 * self::ValueRatio($item));
        $length = $radius * 0.65 * self::Scale($style, 'pointerLengthPercent');
        $width = max(2.0, $radius * 0.04) * self::Scale($style, 'pointerWidthPercent');
        $customColors = ($style['colorMode'] ?? 'theme') === 'custom';
        $pointerColor = $customColors ? self::StyleColor($style, 'pointerColor', $color) : $color;
        $pointerX = $x + cos($angle) * $length;
        $pointerY = $y - sin($angle) * $length;
        $shape = (string) ($style['pointerShape'] ?? 'preset');
        if ($shape === 'custom') {
            $path = (string) ($style['pointerPath'] ?? '');
            $viewBox = (string) ($style['pointerViewBox'] ?? '');
            if ($path !== ''
                && preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $path) === 1
                && preg_match('/^[+\-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+\-]?\d+)?(?:\s+[+\-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+\-]?\d+)?){3}$/D', $viewBox) === 1) {
                [$minimumX, $minimumY, $viewBoxWidth, $viewBoxHeight] = array_map('floatval', explode(' ', $viewBox));
                $customWidth = max(2.0, $length * $viewBoxWidth / $viewBoxHeight
                    * self::Scale($style, 'pointerWidthPercent'));
                $pivotX = max($minimumX, min(
                    $minimumX + $viewBoxWidth,
                    (float) ($style['pointerPivotX'] ?? $minimumX + $viewBoxWidth / 2.0)
                ));
                $pivotY = max($minimumY, min(
                    $minimumY + $viewBoxHeight,
                    (float) ($style['pointerPivotY'] ?? $minimumY + $viewBoxHeight)
                ));
                $customX = $x - ($pivotX - $minimumX) / $viewBoxWidth * $customWidth;
                $customY = $y - ($pivotY - $minimumY) / $viewBoxHeight * $length;
                $rotation = 90.0 - rad2deg($angle);
                $pointer = '<g transform="rotate(' . self::N($rotation) . ' ' . self::N($x) . ' ' . self::N($y)
                    . ')"><svg x="' . self::N($customX) . '" y="' . self::N($customY)
                    . '" width="' . self::N($customWidth) . '" height="' . self::N($length)
                    . '" viewBox="' . SVGPreviewHelper::escape($viewBox)
                    . '" preserveAspectRatio="none" overflow="visible"><path d="'
                    . SVGPreviewHelper::escape($path) . '" fill="'
                    . SVGPreviewHelper::escape($pointerColor) . '" data-pointer-shape="custom"/></svg></g>';
            } else {
                $pointer = '';
            }
        } elseif (in_array($shape, ['needle', 'arrow'], true)) {
            $normalX = sin($angle) * $width;
            $normalY = cos($angle) * $width;
            $backX = $x - cos($angle) * $length * ($shape === 'arrow' ? 0.12 : 0.06);
            $backY = $y + sin($angle) * $length * ($shape === 'arrow' ? 0.12 : 0.06);
            $pointer = '<polygon points="' . self::N($pointerX) . ',' . self::N($pointerY)
                . ' ' . self::N($backX + $normalX) . ',' . self::N($backY + $normalY)
                . ' ' . self::N($backX - $normalX) . ',' . self::N($backY - $normalY)
                . '" fill="' . SVGPreviewHelper::escape($pointerColor) . '"/>';
        } else {
            $pointer = '<line x1="' . self::N($x) . '" y1="' . self::N($y)
                . '" x2="' . self::N($pointerX) . '" y2="' . self::N($pointerY)
                . '" stroke="' . SVGPreviewHelper::escape($pointerColor)
                . '" stroke-width="' . self::N($width) . '" stroke-linecap="round"/>';
        }

        $anchorShape = (string) ($style['anchorShape'] ?? 'preset');
        $customPointerShowsAnchor = (bool) ($style['pointerShowAnchor']
            ?? !isset($style['pointerPivotX'], $style['pointerPivotY']));
        if ($anchorShape === 'none'
            || ($shape === 'custom' && $anchorShape === 'preset' && !$customPointerShowsAnchor)) {
            return $pointer;
        }
        $anchorColor = $customColors ? self::StyleColor($style, 'anchorColor', $pointerColor) : $pointerColor;
        $anchorBorder = $customColors ? self::StyleColor($style, 'anchorBorderColor', $pointerColor) : $pointerColor;
        $anchorRadius = max(4.0, $radius * 0.07) * self::Scale($style, 'anchorSizePercent');
        $borderWidth = max(1.0, $radius * 0.01) * self::Scale($style, 'anchorBorderWidthPercent');

        if ($anchorShape === 'custom') {
            $anchorPath = trim((string) ($style['anchorPath'] ?? ''));
            $anchorViewBox = trim((string) ($style['anchorViewBox'] ?? ''));
            if ($anchorPath === '' || $anchorViewBox === ''
                || preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $anchorPath) !== 1) {
                return $pointer;
            }
            $anchorSize = $anchorRadius * 2.0;

            return $pointer . '<svg x="' . self::N($x - $anchorRadius) . '" y="' . self::N($y - $anchorRadius)
                . '" width="' . self::N($anchorSize) . '" height="' . self::N($anchorSize)
                . '" viewBox="' . SVGPreviewHelper::escape($anchorViewBox)
                . '" preserveAspectRatio="xMidYMid meet" overflow="visible"><path d="'
                . SVGPreviewHelper::escape($anchorPath) . '" fill="' . SVGPreviewHelper::escape($anchorColor)
                . '" stroke="' . SVGPreviewHelper::escape($anchorBorder) . '" stroke-width="'
                . self::N($borderWidth) . '" vector-effect="non-scaling-stroke" data-anchor-shape="custom"/></svg>';
        }

        return $pointer . '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
            . '" r="' . self::N($anchorRadius) . '" fill="'
            . ($anchorShape === 'ring' ? 'none' : SVGPreviewHelper::escape($anchorColor))
            . '" stroke="' . SVGPreviewHelper::escape($anchorBorder)
            . '" stroke-width="' . self::N($borderWidth) . '"/>';
    }

    /** @param array<string, string> $palette @param array<string, int|string> $style */
    private static function SvgDocument(string $title, array $palette, string $content, string $preset, array $style): string
    {
        $titleElement = trim($title) === '' ? '' : '<text x="360" y="28" fill="'
            . SVGPreviewHelper::escape($palette['text'])
            . '" font-size="18" font-weight="600" text-anchor="middle">'
            . SVGPreviewHelper::escape($title) . '</text>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 400" role="img" data-preset="'
            . SVGPreviewHelper::escape($preset) . '" data-pointer-shape="'
            . SVGPreviewHelper::escape((string) ($style['pointerShape'] ?? 'preset')) . '" data-anchor-shape="'
            . SVGPreviewHelper::escape((string) ($style['anchorShape'] ?? 'preset')) . '" data-plate-mode="'
            . SVGPreviewHelper::escape((string) ($style['plateMode'] ?? 'preset')) . '">'
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

    /** @param array<string, string> $palette @param array<string, int|string> $style */
    private static function DialPlateSvg(
        float $x,
        float $y,
        float $radius,
        array $palette,
        array $style,
        bool $presetHasPlate
    ): string {
        $mode = (string) ($style['plateMode'] ?? 'preset');
        if ($mode === 'hidden' || ($mode === 'preset' && !$presetHasPlate)) {
            return '';
        }
        if ($mode === 'preset') {
            return '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
                . '" r="' . self::N($radius * 1.14) . '" fill="' . SVGPreviewHelper::escape($palette['background'])
                . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '" stroke-width="2"/>'
                . '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
                . '" r="' . self::N($radius * 1.04) . '" fill="none" stroke="'
                . SVGPreviewHelper::escape($palette['track']) . '" stroke-width="3"/>';
        }

        $plateRadius = $radius * 1.08 * self::Scale($style, 'plateSizePercent');
        $fill = SVGPreviewHelper::escape(self::StyleColor($style, 'plateColor', $palette['background']));
        $border = SVGPreviewHelper::escape(self::StyleColor($style, 'plateBorderColor', $palette['border']));
        $borderWidth = self::N(2.0 * self::Scale($style, 'plateBorderWidthPercent'));
        $plate = '<circle cx="' . self::N($x) . '" cy="' . self::N($y)
            . '" r="' . self::N($plateRadius) . '" fill="' . $fill
            . '" stroke="' . $border . '" stroke-width="' . $borderWidth . '"/>';
        if (!(bool) ($style['plateBackgroundEnabled'] ?? false)) {
            return $plate;
        }

        $image = (string) ($style['plateBackgroundImage'] ?? '');
        $aspectRatio = (float) ($style['plateBackgroundAspectRatio'] ?? 0.0);
        if (preg_match('/^data:image\/svg\+xml;base64,[A-Za-z0-9+\/=]+$/D', $image) !== 1
            || !is_finite($aspectRatio) || $aspectRatio <= 0.0) {
            return $plate;
        }
        $diameter = 2.0 * $plateRadius;
        $imageWidth = $diameter;
        $imageHeight = $diameter;
        $fit = (string) ($style['plateBackgroundFit'] ?? 'cover');
        if ($fit === 'contain') {
            if ($aspectRatio > 1.0) {
                $imageHeight = $diameter / $aspectRatio;
            } else {
                $imageWidth = $diameter * $aspectRatio;
            }
        } elseif ($fit === 'cover') {
            if ($aspectRatio > 1.0) {
                $imageWidth = $diameter * $aspectRatio;
            } else {
                $imageHeight = $diameter / $aspectRatio;
            }
        }
        $backgroundScale = max(25, min(200, (int) ($style['plateBackgroundSizePercent'] ?? 100))) / 100.0;
        $imageWidth *= $backgroundScale;
        $imageHeight *= $backgroundScale;
        $imageCenterX = $x + $diameter
            * max(-100, min(100, (int) ($style['plateBackgroundOffsetXPercent'] ?? 0))) / 100.0;
        $imageCenterY = $y + $diameter
            * max(-100, min(100, (int) ($style['plateBackgroundOffsetYPercent'] ?? 0))) / 100.0;
        $opacity = max(0, min(100, (int) ($style['plateBackgroundOpacityPercent'] ?? 100))) / 100.0;
        $rotation = max(-180.0, min(180.0, (float) ($style['plateBackgroundRotation'] ?? 0.0)));
        $clipID = 'multi-plate-' . str_replace(['-', '.'], ['n', 'p'], self::N($x) . '-' . self::N($y));
        $background = '<defs><clipPath id="' . $clipID . '"><circle cx="' . self::N($x)
            . '" cy="' . self::N($y) . '" r="' . self::N($plateRadius) . '"/></clipPath></defs>'
            . '<image href="' . SVGPreviewHelper::escape($image) . '" x="'
            . self::N($imageCenterX - $imageWidth / 2.0) . '" y="'
            . self::N($imageCenterY - $imageHeight / 2.0) . '" width="' . self::N($imageWidth)
            . '" height="' . self::N($imageHeight) . '" opacity="' . self::N($opacity)
            . '" preserveAspectRatio="none" clip-path="url(#' . $clipID . ')" transform="rotate('
            . self::N($rotation) . ' ' . self::N($imageCenterX) . ' ' . self::N($imageCenterY) . ')"/>'
            . '<circle cx="' . self::N($x) . '" cy="' . self::N($y) . '" r="'
            . self::N($plateRadius) . '" fill="none" stroke="' . $border
            . '" stroke-width="' . $borderWidth . '"/>';

        return $plate . $background;
    }

    /** @param array<string, string> $palette @param array<string, int|string> $style @return array<string, string> */
    private static function StyledPalette(array $palette, array $style): array
    {
        if (($style['colorMode'] ?? 'theme') !== 'custom') {
            return $palette;
        }
        $palette['accent'] = self::StyleColor($style, 'progressColor', $palette['accent']);
        $palette['track'] = self::StyleColor($style, 'ringColor', $palette['track']);
        $palette['border'] = self::StyleColor($style, 'scaleColor', $palette['border']);
        $palette['text'] = self::StyleColor($style, 'valueColor', $palette['text']);
        $palette['muted'] = self::StyleColor($style, 'titleColor', $palette['muted']);

        return $palette;
    }

    /** @param array<string, int|string> $style */
    private static function StyleColor(array $style, string $name, string $fallback): string
    {
        $color = (string) ($style[$name] ?? '');

        return preg_match('/^#[0-9A-F]{6}$/i', $color) === 1 ? strtoupper($color) : $fallback;
    }

    /** @param array<string, int|string> $style */
    private static function Scale(array $style, string $name): float
    {
        return max(50, min(150, (int) ($style[$name] ?? 100))) / 100.0;
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
