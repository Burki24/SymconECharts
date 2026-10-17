<?php

declare(strict_types=1);

namespace SymconECharts;

use DomainException;
use Throwable;

/** Bounded draft-only preview; no Gateway requests, status or configuration writes. */
trait PictorialBarPreview
{
    private function PictorialBarPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $raw = $values['Sources'] ?? json_decode($this->ReadPropertyString('Sources'), true);
        $sample = $raw === [];
        $invalid = false;
        $items = [];
        try {
            if ($sample) {
                foreach ([100, 70, 45, 20] as $index => $value) {
                    $items[] = ['Label' => $this->Translate('Category') . ' ' . ($index + 1), 'value' => (float) $value,
                        'Color'         => -1, 'Unit' => '', 'Decimals' => 0, 'order' => $index];
                }
            } else {
                $sources = $this->GetValidatedSources(json_encode($raw, JSON_THROW_ON_ERROR));
                foreach ($sources as $index => $source) {
                    $value = GetValue($source['VariableID']);
                    if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0) {
                        throw new DomainException('Invalid preview value.');
                    }
                    $items[] = [...$source, 'value' => (float) $value, 'order' => $index];
                }
            }
        } catch (Throwable) {
            $invalid = true;
            $items = [];
        }
        $e = EChartsBarPreview::E(...);
        $n = EChartsBarPreview::N(...);
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            $sample,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('PictorialBar preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<text x="24"', '<text font-weight="' . ($this->BarPreviewValue($values, 'TitleBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '" x="24"', $svg);
        $family = $this->BarPreviewValue($values, 'FontFamily', 'string', $ipsView);
        if (!in_array($family, ['Arial, sans-serif', 'Verdana, sans-serif', 'Georgia, serif', 'monospace'], true)) {
            $family = 'Arial, sans-serif';
        }
        $svg = str_replace('<svg xmlns', '<svg font-family="' . $e($family) . '" xmlns', $svg);
        if ($invalid) {
            return $svg . '<text x="360" y="190" text-anchor="middle" fill="' . $e($palette['text']) . '">'
                . $e($this->Translate('Preview requires valid non-negative values.')) . '</text></svg>';
        }
        $sort = $this->BarPreviewValue($values, 'SortOrder', 'string', $ipsView);
        if (in_array($sort, ['ascending', 'descending'], true)) {
            usort($items, static fn (array $a, array $b): int => ($sort === 'ascending' ? $a['value'] <=> $b['value'] : $b['value'] <=> $a['value']) ?: $a['order'] <=> $b['order']);
        }
        $maximum = max(1.0, ...array_column($items, 'value'));
        $fixed = $this->BarPreviewValue($values, 'ScaleMaximum', 'integer', $ipsView);
        if ($fixed > 0) {
            $maximum = $fixed;
        }
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $left = $horizontal ? 140 : 65;
        $right = 640;
        $top = 80;
        $bottom = 300;
        $major = $horizontal ? $right - $left : $bottom - $top;
        $cross = ($horizontal ? $bottom - $top : $right - $left) / max(1, count($items));
        $width = min(max(4, $this->BarPreviewValue($values, 'BarWidth', 'integer', $ipsView)), $cross * 0.65);
        $symbol = $this->BarPreviewValue($values, 'Symbol', 'string', $ipsView);
        $size = max(4, min(40, $this->BarPreviewValue($values, 'SymbolSize', 'integer', $ipsView)));
        $crossSize = min($size, $width);
        $gap = max(0, min(20, $this->BarPreviewValue($values, 'SymbolGap', 'integer', $ipsView)));
        $repeat = $this->BarPreviewValue($values, 'SymbolRepeat', 'boolean', $ipsView);
        $strokeWidth = max(0, min(5, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $valueFont = max(6, min(24, 12 * $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView) / 100));
        $axis = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $grid = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['border']);
        $dash = match ($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView)) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $shape = static function (string $kind, float $x, float $y, float $w, float $h, string $fill, string $border) use ($n, $e, $strokeWidth, $dash): string
        {
            $style = ' fill="' . $e($fill) . '" stroke="' . $e($border) . '" stroke-width="' . $n($strokeWidth) . '"' . $dash . '/>';
            return match ($kind) {
                'circle'   => '<ellipse cx="' . $n($x + $w / 2) . '" cy="' . $n($y + $h / 2) . '" rx="' . $n($w / 2) . '" ry="' . $n($h / 2) . '"' . $style,
                'diamond'  => '<polygon points="' . $n($x + $w / 2) . ',' . $n($y) . ' ' . $n($x + $w) . ',' . $n($y + $h / 2) . ' ' . $n($x + $w / 2) . ',' . $n($y + $h) . ' ' . $n($x) . ',' . $n($y + $h / 2) . '"' . $style,
                'triangle' => '<polygon points="' . $n($x + $w / 2) . ',' . $n($y) . ' ' . $n($x + $w) . ',' . $n($y + $h) . ' ' . $n($x) . ',' . $n($y + $h) . '"' . $style,
                default    => '<rect x="' . $n($x) . '" y="' . $n($y) . '" width="' . $n($w) . '" height="' . $n($h) . '" rx="' . ($kind === 'roundRect' ? $n(min($w, $h) / 4) : '0') . '"' . $style
            };
        };
        $shadow = max(0, min(20, $this->BarPreviewValue($values, 'ShadowBlur', 'integer', $ipsView)));
        $shadowX = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetX', 'integer', $ipsView)));
        $shadowY = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetY', 'integer', $ipsView)));
        $hasShadow = $shadow > 0 || $shadowX !== 0 || $shadowY !== 0;
        if ($hasShadow) {
            $svg .= '<defs><filter id="symbol-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="' . $n($shadowX)
                . '" dy="' . $n($shadowY) . '" stdDeviation="' . $n($shadow / 2) . '" flood-color="'
                . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView), '#000000'))
                . '" flood-opacity="' . ($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView) < 0 ? '0.35' : '1') . '"/></filter></defs>';
        }
        $gridDash = match ($this->BarPreviewValue($values, 'GridType', 'string', $ipsView)) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $gradient = $this->BarPreviewValue($values, 'ColorMode', 'string', $ipsView) === 'gradient';
        if ($gradient) {
            $low = $this->BarPreviewValue($values, 'ColorMinimum', 'float', $ipsView);
            $high = $this->BarPreviewValue($values, 'ColorMaximum', 'float', $ipsView);
            $points = [0.0, ...array_filter([$low, $low + ($high - $low) / 2, $high], static fn (float $v): bool => $v > 0 && $v < $maximum), $maximum];
            $svg .= '<defs><linearGradient id="scale-gradient" gradientUnits="userSpaceOnUse" x1="' . $n($left)
                . '" y1="' . $n($bottom) . '" x2="' . $n($horizontal ? $right : $left) . '" y2="' . $n($horizontal ? $bottom : $top) . '">';
            foreach ($points as $point) {
                $svg .= '<stop offset="' . $n($point / $maximum) . '" stop-color="' . $e($this->PictorialBarPreviewColor($values, $ipsView, $point)) . '"/>';
            }
            $svg .= '</linearGradient></defs>';
        }
        for ($tick = 0; $tick <= 4; ++$tick) {
            $ratio = $tick / 4;
            $x = $horizontal ? $left + $major * $ratio : $left;
            $y = $horizontal ? $bottom : $bottom - $major * $ratio;
            if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
                $svg .= '<path d="M' . $n($x) . ' ' . $n($horizontal ? $top : $y) . 'L' . $n($horizontal ? $x : $right) . ' ' . $n($bottom === $y ? $bottom : $y)
                    . '" stroke="' . $e($grid) . '" stroke-width="' . $n(max(0, min(5, $this->BarPreviewValue($values, 'GridWidth', 'integer', $ipsView))))
                    . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'GridOpacityPercent', 'integer', $ipsView))) / 100) . '"' . $gridDash . '/>';
            }
            if ($this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView)) {
                $svg .= '<path d="M' . $n($x) . ' ' . $n($y) . 'l' . ($horizontal ? '0 5' : '-5 0') . '" stroke="' . $e($axis) . '"/>';
            }
            if ($this->BarPreviewValue($values, 'ShowAxisValues', 'boolean', $ipsView)) {
                $svg .= '<text x="' . $n($horizontal ? $x : $left - 10) . '" y="' . $n($horizontal ? $bottom + 20 : $y + 4)
                . '" text-anchor="' . ($horizontal ? 'middle' : 'end') . '" font-size="' . $n($font) . '" fill="' . $e($axis) . '">'
                . $e(EChartsBarPreview::FormatValue($maximum * $ratio, 1, '', $this->ResolveHelperTranslationLanguage())) . '</text>';
            }
        }
        $valueRects = [];
        foreach ($items as $index=>$item) {
            $center = ($horizontal ? $top : $left) + ($index + 0.5) * $cross;
            $length = min(1, $item['value'] / $maximum) * $major;
            $x = $horizontal ? $left : $center - $width / 2;
            $y = $horizontal ? $center - $width / 2 : $bottom - $length;
            $w = $horizontal ? $length : $width;
            $h = $horizontal ? $width : $length;
            $color = EChartsBarPreview::Color($item['Color'], $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])]);
            if ($this->BarPreviewValue($values, 'ColorMode', 'string', $ipsView) === 'value') {
                $color = $this->PictorialBarPreviewColor($values, $ipsView, $item['value']);
            } elseif ($gradient) {
                $color = 'url(#scale-gradient)';
            }
            $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $color);
            $svg .= '<g data-source="' . $item['order'] . '"' . ($hasShadow ? ' filter="url(#symbol-shadow)"' : '') . ' opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'OpacityPercent', 'integer', $ipsView))) / 100) . '">';
            if ($length > 0) {
                if ($repeat && $gradient) {
                    // Draw in chart coordinates so the gradient does not restart
                    // inside each glyph/pattern. Clip the full group at the value.
                    $count = max(1, min(128, (int) floor(($major + 2 * $gap) / ($size + 2 * $gap))));
                    $step = $count > 1 ? ($major - $size) / ($count - 1) : 0;
                    $id = 'gradient-clip-' . $index;
                    $svg .= '<defs><clipPath id="' . $id . '"><rect x="' . $n($x) . '" y="' . $n($y) . '" width="' . $n($w) . '" height="' . $n($h) . '"/></clipPath></defs><g clip-path="url(#' . $id . ')">';
                    for ($glyph = 0; $glyph < $count; ++$glyph) {
                        $svg .= $shape(
                            $symbol,
                            $horizontal ? $left + $glyph * $step : $x + ($width - $crossSize) / 2,
                            $horizontal ? $y + ($width - $crossSize) / 2 : $bottom - $size - $glyph * $step,
                            $horizontal ? $size : $crossSize,
                            $horizontal ? $crossSize : $size,
                            $color,
                            $border
                        );
                    }
                    $svg .= '</g>';
                } elseif ($repeat) {
                    $id = 'symbols-' . $index;
                    $svg .= '<defs><pattern id="' . $id . '" patternUnits="userSpaceOnUse" x="' . $n($x) . '" y="' . $n($horizontal ? $y : $bottom - $size)
                        . '" width="' . $n($horizontal ? $size + 2 * $gap : $width) . '" height="' . $n($horizontal ? $width : $size + 2 * $gap) . '">'
                        . $shape($symbol, $horizontal ? 0 : ($width - $crossSize) / 2, $horizontal ? ($width - $crossSize) / 2 : 0, $horizontal ? $size : $crossSize, $horizontal ? $crossSize : $size, $color, $border) . '</pattern></defs>';
                    $svg .= '<rect x="' . $n($x) . '" y="' . $n($y) . '" width="' . $n($w) . '" height="' . $n($h) . '" fill="url(#' . $id . ')"/>';
                } else {
                    $svg .= $shape($symbol, $x, $y, $w, $h, $color, $border);
                }
            }
            $svg .= '</g>';
            if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                $labelX = $horizontal ? $left - 10 : $center;
                $labelY = $horizontal ? $center + 4 : $bottom + 20;
                $rotation = max(-90, min(90, $this->BarPreviewValue($values, 'CategoryLabelRotate', 'integer', $ipsView)));
                $svg .= '<text x="' . $n($horizontal ? $left - 10 : $center) . '" y="' . $n($horizontal ? $center + 4 : $bottom + 20)
                    . '" transform="rotate(' . $n(-$rotation) . ' ' . $n($labelX) . ' ' . $n($labelY) . ')'
                    . '" text-anchor="' . ($horizontal ? 'end' : 'middle') . '" font-size="' . $n($font) . '" fill="' . $e($axis) . '">'
                    . $e(mb_strimwidth($item['Label'], 0, $horizontal ? 19 : max(3, (int) ($cross / ($font * 0.55))), '…')) . '</text>';
            }
            if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                $position = $this->BarPreviewValue($values, 'ValuePosition', 'string', $ipsView);
                $distance = max(0, min(30, $this->BarPreviewValue($values, 'ValueDistance', 'integer', $ipsView)));
                $along = match ($position) {
                    'insideStart' => $distance, 'insideEnd' => $length - $distance, 'inside' => $length / 2, default => $length + $distance
                };
                $text = EChartsBarPreview::FormatValue($item['value'], $item['Decimals'], $item['Unit'], $this->ResolveHelperTranslationLanguage());
                $textWidth = mb_strlen($text) * $valueFont * 0.6;
                $textX = $horizontal ? $left + $along : $center;
                $textY = $horizontal ? $center + 4 : $bottom - $along;
                $anchor = $horizontal ? match ($position) {
                    'insideEnd' => 'end', 'inside' => 'middle', default => 'start'
                } : 'middle';
                $rectX = $textX - match ($anchor) {
                    'end' => $textWidth, 'middle' => $textWidth / 2, default => 0
                };
                $rect = [$rectX, $textY - $valueFont, $rectX + $textWidth, $textY + 2];
                if ($this->BarPreviewValue($values, 'HideOverlap', 'boolean', $ipsView)) {
                    foreach ($valueRects as $prior) {
                        if ($rect[0] < $prior[2] && $rect[2] > $prior[0] && $rect[1] < $prior[3] && $rect[3] > $prior[1]) {
                            continue 2;
                        }
                    }
                }
                $valueRects[] = $rect;
                $svg .= '<text x="' . $n($horizontal ? $left + $along : $center) . '" y="' . $n($horizontal ? $center + 4 : $bottom - $along)
                    . '" font-weight="' . ($this->BarPreviewValue($values, 'ValueBold', 'boolean', $ipsView) ? 'bold' : 'normal')
                    . '" text-anchor="' . $anchor . '" font-size="' . $n($valueFont)
                    . '" fill="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text'])) . '">'
                    . $e($text) . '</text>';
            }
        }
        return $svg . '</svg>';
    }

    private function PictorialBarPreviewColor(array $values, bool $ipsView, float $value): string
    {
        $minimum = $this->BarPreviewValue($values, 'ColorMinimum', 'float', $ipsView);
        $maximum = $this->BarPreviewValue($values, 'ColorMaximum', 'float', $ipsView);
        $ratio = $maximum > $minimum ? max(0, min(1, ($value - $minimum) / ($maximum - $minimum))) : 0;
        $stops = array_map(fn (string $name): int => max(0, min(0xFFFFFF, $this->BarPreviewValue($values, $name, 'integer', $ipsView))), ['LowColor', 'MiddleColor', 'HighColor']);
        $a = $stops[$ratio <= 0.5 ? 0 : 1];
        $b = $stops[$ratio <= 0.5 ? 1 : 2];
        $t = $ratio <= 0.5 ? 2 * $ratio : 2 * ($ratio - 0.5);
        $channels = array_map(static fn (int $shift): int => (int) round((($a >> $shift) & 255) + ((($b >> $shift) & 255) - (($a >> $shift) & 255)) * $t), [16, 8, 0]);
        return sprintf('#%02x%02x%02x', ...$channels);
    }
}
