<?php

declare(strict_types=1);

namespace SymconECharts;

/** Bounded synthetic bands; no archive request is performed while editing. */
trait ThemeRiverPreview
{
    private function ThemeRiverPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('ThemeRiver preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        $items = array_slice(EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false)['items'], 0, 8);
        $count = count($items);
        $n = EChartsBarPreview::N(...);
        $e = EChartsBarPreview::E(...);
        $top = 86;
        $bottom = 264;
        $legend = $this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView);
        $bandOpacity = max(0, min(100, $this->BarPreviewValue($values, 'BandOpacityPercent', 'integer', $ipsView))) / 100;
        $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['border']);
        $borderWidth = max(0, min(5, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100));
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        // A complete run on either side of one visibly empty example interval.
        foreach ([[60, 328], [378, 680]] as [$left, $right]) {
            $edges = [];
            for ($point = 0; $point < 9; ++$point) {
                $parts = [];
                for ($source = 0; $source < $count; ++$source) {
                    $parts[] = 10 + 8 * (1 + sin($point * 0.7 + $source * 1.3));
                }
                $scale = ($bottom - $top) / ($count * 26);
                $y = ($top + $bottom - array_sum($parts) * $scale) / 2;
                foreach ($parts as $source => $value) {
                    $edges[$source][] = [$left + ($right - $left) * $point / 8, $y, $y + $value * $scale];
                    $y += $value * $scale;
                }
            }
            foreach ($items as $source => $item) {
                $color = $item['color'] ?: $palette['seriesColors'][$source % count($palette['seriesColors'])];
                $upper = array_map(static fn (array $p): string => $n($p[0]) . ',' . $n($p[1]), $edges[$source]);
                $lower = array_map(static fn (array $p): string => $n($p[0]) . ',' . $n($p[2]), array_reverse($edges[$source]));
                $svg .= '<polygon data-band="' . $source . '" points="' . implode(' ', [...$upper, ...$lower])
                    . '" fill="' . $e($color) . '" fill-opacity="' . $n($bandOpacity) . '" stroke="' . $e($border)
                    . '" stroke-width="' . $n($borderWidth) . '"/>';
                if ($left === 60 && $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                    $p = $edges[$source][4];
                    $svg .= '<text x="' . $n($p[0]) . '" y="' . $n(($p[1] + $p[2]) / 2 + 4)
                        . '" text-anchor="middle" font-size="' . $n($font) . '" fill="' . $e($labelColor) . '">'
                        . $e(mb_strimwidth($item['label'], 0, 18, '…')) . '</text>';
                }
            }
        }
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $axisFont = max(6, min(24, 11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $svg .= '<path d="M60 280H680" stroke="' . $e($axisColor) . '"/>';
        $daily = ($values['Aggregation'] ?? $this->ReadPropertyString('Aggregation')) === 'day';
        foreach ([60, 215, 370, 525, 680] as $index => $x) {
            if ($this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView)) {
                $svg .= '<path d="M' . $x . ' 280v5" stroke="' . $e($axisColor) . '"/>';
            }
            if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
                $svg .= '<text x="' . $x . '" y="302" text-anchor="middle" fill="' . $e($axisColor) . '" font-size="' . $n($axisFont) . '">'
                    . ($daily ? ('0' . ($index + 1) . '.10.') : str_pad((string) ($index * 6), 2, '0', STR_PAD_LEFT) . ':00') . '</text>';
            }
        }
        if ($legend) {
            $legendFont = max(6, min(24, 11 * $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView) / 100));
            $legendColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $palette['text']);
            foreach ($items as $index => $item) {
                $x = 24 + $index * 672 / $count;
                $color = $item['color'] ?: $palette['seriesColors'][$index % count($palette['seriesColors'])];
                $svg .= '<rect x="' . $n($x) . '" y="326" width="10" height="10" fill="' . $e($color) . '"/>'
                    . '<text x="' . $n($x + 14) . '" y="336" font-size="' . $n($legendFont) . '" fill="' . $e($legendColor) . '">'
                    . $e(mb_strimwidth($item['label'], 0, max(3, (int) (672 / $count / ($legendFont * 0.65)) - 3), '…')) . '</text>';
            }
        }
        return $svg . '</svg>';
    }
}
