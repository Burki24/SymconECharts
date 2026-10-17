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
        $shape = static function (string $kind, float $x, float $y, float $w, float $h, string $fill, string $border) use ($n, $e, $strokeWidth): string
        {
            $style = ' fill="' . $e($fill) . '" stroke="' . $e($border) . '" stroke-width="' . $n($strokeWidth) . '"/>';
            return match ($kind) {
                'circle'   => '<ellipse cx="' . $n($x + $w / 2) . '" cy="' . $n($y + $h / 2) . '" rx="' . $n($w / 2) . '" ry="' . $n($h / 2) . '"' . $style,
                'diamond'  => '<polygon points="' . $n($x + $w / 2) . ',' . $n($y) . ' ' . $n($x + $w) . ',' . $n($y + $h / 2) . ' ' . $n($x + $w / 2) . ',' . $n($y + $h) . ' ' . $n($x) . ',' . $n($y + $h / 2) . '"' . $style,
                'triangle' => '<polygon points="' . $n($x + $w / 2) . ',' . $n($y) . ' ' . $n($x + $w) . ',' . $n($y + $h) . ' ' . $n($x) . ',' . $n($y + $h) . '"' . $style,
                default    => '<rect x="' . $n($x) . '" y="' . $n($y) . '" width="' . $n($w) . '" height="' . $n($h) . '" rx="' . ($kind === 'roundRect' ? $n(min($w, $h) / 4) : '0') . '"' . $style
            };
        };
        for ($tick = 0; $tick <= 4; ++$tick) {
            $ratio = $tick / 4;
            $x = $horizontal ? $left + $major * $ratio : $left;
            $y = $horizontal ? $bottom : $bottom - $major * $ratio;
            if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
                $svg .= '<path d="M' . $n($x) . ' ' . $n($horizontal ? $top : $y) . 'L' . $n($horizontal ? $x : $right) . ' ' . $n($bottom === $y ? $bottom : $y)
                    . '" stroke="' . $e($grid) . '" opacity="0.25"/>';
            }
            $svg .= '<text x="' . $n($horizontal ? $x : $left - 10) . '" y="' . $n($horizontal ? $bottom + 20 : $y + 4)
                . '" text-anchor="' . ($horizontal ? 'middle' : 'end') . '" font-size="' . $n($font) . '" fill="' . $e($axis) . '">'
                . $e(EChartsBarPreview::FormatValue($maximum * $ratio, 1, '', $this->ResolveHelperTranslationLanguage())) . '</text>';
        }
        foreach ($items as $index=>$item) {
            $center = ($horizontal ? $top : $left) + ($index + 0.5) * $cross;
            $length = min(1, $item['value'] / $maximum) * $major;
            $x = $horizontal ? $left : $center - $width / 2;
            $y = $horizontal ? $center - $width / 2 : $bottom - $length;
            $w = $horizontal ? $length : $width;
            $h = $horizontal ? $width : $length;
            $color = EChartsBarPreview::Color($item['Color'], $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])]);
            $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $color);
            $svg .= '<g data-source="' . $item['order'] . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'OpacityPercent', 'integer', $ipsView))) / 100) . '">';
            if ($length > 0) {
                if ($repeat) {
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
                $svg .= '<text x="' . $n($horizontal ? $left - 10 : $center) . '" y="' . $n($horizontal ? $center + 4 : $bottom + 20)
                    . '" text-anchor="' . ($horizontal ? 'end' : 'middle') . '" font-size="' . $n($font) . '" fill="' . $e($axis) . '">'
                    . $e(mb_strimwidth($item['Label'], 0, $horizontal ? 19 : max(3, (int) ($cross / ($font * 0.55))), '…')) . '</text>';
            }
            if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                $svg .= '<text x="' . $n($horizontal ? $left + $length + 5 : $center) . '" y="' . $n($horizontal ? $center + 4 : $bottom - $length - 5)
                    . '" text-anchor="' . ($horizontal ? 'start' : 'middle') . '" font-size="' . $n($valueFont)
                    . '" fill="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text'])) . '">'
                    . $e(EChartsBarPreview::FormatValue($item['value'], $item['Decimals'], $item['Unit'], $this->ResolveHelperTranslationLanguage())) . '</text>';
            }
        }
        return str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg) . '</svg>';
    }
}
