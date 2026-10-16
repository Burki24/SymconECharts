<?php

declare(strict_types=1);

namespace SymconECharts;

/** Synthetic geometry only: editing a designer never reads archive data. */
trait BoxplotPreview
{
    private function BoxplotPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Boxplot preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        $items = array_slice(EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false)['items'], 0, 10);
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $axis = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['border']);
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $n = EChartsBarPreview::N(...);
        $e = EChartsBarPreview::E(...);
        $line = static fn ($x1, $y1, $x2, $y2, $color, $width): string => '<path d="M' . $n($x1) . ' ' . $n($y1) . 'L' . $n($x2) . ' ' . $n($y2) . '" fill="none" stroke="' . $e($color) . '" stroke-width="' . $n($width) . '"/>';
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            for ($tick = 0; $tick <= 4; $tick++) {
                $position = $horizontal ? 160 + $tick * 130 : 295 - $tick * 55;
                $svg .= $horizontal ? $line($position, 65, $position, 295, $palette['border'], 0.5)
                    : $line(70, $position, 680, $position, $palette['border'], 0.5);
            }
        }
        $slot = ($horizontal ? 220 : 600) / count($items);
        $width = min(max(8, $this->BarPreviewValue($values, 'BoxWidth', 'integer', $ipsView)), $slot * 0.65);
        foreach ($items as $index => $item) {
            $center = ($horizontal ? 75 : 80) + $slot * ($horizontal ? count($items) - $index - 0.5 : $index + 0.5);
            $offset = ($index % 4) * 0.07;
            $stats = array_map(static fn ($v): float => $horizontal ? 160 + 520 * ($v + $offset) : 295 - 220 * ($v + $offset), [0.10, 0.30, 0.45, 0.60, 0.75]);
            $color = $item['color'] ?: $palette['seriesColors'][$index % count($palette['seriesColors'])];
            $stroke = max(0, min(8, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
            $svg .= '<g data-box="' . $index . '">';
            if ($horizontal) {
                $svg .= $line($stats[0], $center, $stats[4], $center, $border, $stroke);
                foreach ([0, 4] as $end) {
                    $svg .= $line($stats[$end], $center - $width / 3, $stats[$end], $center + $width / 3, $border, $stroke);
                }
            } else {
                $svg .= $line($center, $stats[0], $center, $stats[4], $border, $stroke);
                foreach ([0, 4] as $end) {
                    $svg .= $line($center - $width / 3, $stats[$end], $center + $width / 3, $stats[$end], $border, $stroke);
                }
            }
            $svg .= '<rect x="' . $n($horizontal ? $stats[1] : $center - $width / 2) . '" y="' . $n($horizontal ? $center - $width / 2 : $stats[3])
                . '" width="' . $n($horizontal ? $stats[3] - $stats[1] : $width) . '" height="' . $n($horizontal ? $width : $stats[1] - $stats[3])
                . '" fill="' . $e($color) . '" fill-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'BoxOpacityPercent', 'integer', $ipsView))) / 100)
                . '" stroke="' . $e($border) . '" stroke-width="' . $n($stroke) . '"/>';
            $svg .= $horizontal ? $line($stats[2], $center - $width / 2, $stats[2], $center + $width / 2, $border, $stroke)
                : $line($center - $width / 2, $stats[2], $center + $width / 2, $stats[2], $border, $stroke);
            if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
                $svg .= '<text x="' . $n($horizontal ? 150 : $center) . '" y="' . $n($horizontal ? $center + 4 : 315)
                    . '" text-anchor="' . ($horizontal ? 'end' : 'middle') . '" font-size="' . $n($font) . '" fill="' . $e($axis) . '">'
                    . $e(mb_strimwidth($item['label'], 0, $horizontal ? 23 : max(5, (int) ($slot / 7)), '…')) . '</text>';
            }
            $svg .= '</g>';
        }
        return $svg . '</svg>';
    }
}
