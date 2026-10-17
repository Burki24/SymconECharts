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
        $svg = str_replace('<svg xmlns', '<svg font-family="' . EChartsBarPreview::E($this->BarPreviewValue($values, 'FontFamily', 'string', $ipsView)) . '" xmlns', $svg);
        $textStyle = fn (string $prefix): string => ' font-weight="' . ($this->BarPreviewValue($values, $prefix . 'Bold', 'boolean', $ipsView) ? 'bold' : 'normal')
            . '" font-style="' . ($this->BarPreviewValue($values, $prefix . 'Italic', 'boolean', $ipsView) ? 'italic' : 'normal') . '"';
        $svg = str_replace('<text x="24"', '<text' . $textStyle('Title') . ' x="24"', $svg);
        $items = array_slice(EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false)['items'], 0, 8);
        $count = count($items);
        $n = EChartsBarPreview::N(...);
        $e = EChartsBarPreview::E(...);
        $legend = $this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView);
        $legendAtTop = $this->BarPreviewValue($values, 'LegendPosition', 'string', $ipsView) === 'top';
        $legendWidth = max(6, min(40, $this->BarPreviewValue($values, 'LegendItemWidth', 'integer', $ipsView)));
        $legendItemHeight = max(6, min(30, $this->BarPreviewValue($values, 'LegendItemHeight', 'integer', $ipsView)));
        $legendGap = max(0, min(30, $this->BarPreviewValue($values, 'LegendItemGap', 'integer', $ipsView)));
        $axisMargin = max(0, min(30, $this->BarPreviewValue($values, 'AxisLabelMargin', 'integer', $ipsView)));
        $dateTime = $this->BarPreviewValue($values, 'TimeLabelFormat', 'string', $ipsView) === 'dateTime';
        $axisLabels = $this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView);
        $axisTicks = $this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView);
        $axisFont = max(6, min(24, 11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $legendFont = max(6, min(24, 11 * $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView) / 100));
        $legendHeight = max(16, $legendItemHeight, ceil($legendFont * 1.25)) + 10;
        $legendTop = $legendAtTop ? 60 : 360 - 14 - $legendHeight;
        $top = $legend && $legendAtTop ? $legendTop + $legendHeight + 16 : 86;
        $axisExtent = $axisLabels ? $axisMargin + ceil($axisFont * 1.25) * ($dateTime ? 2 : 1) : ($axisTicks ? 5 : 0);
        $axisY = ($legend && !$legendAtTop ? $legendTop - 20 : 342) - $axisExtent;
        $bottom = $axisY - 16;
        $bandOpacity = max(0, min(100, $this->BarPreviewValue($values, 'BandOpacityPercent', 'integer', $ipsView))) / 100;
        $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['border']);
        $borderWidth = max(0, min(5, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100));
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $dash = static fn (string $type): string => match ($type) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $shadow = max(0, min(20, $this->BarPreviewValue($values, 'ShadowBlur', 'integer', $ipsView)));
        $offsetX = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetX', 'integer', $ipsView)));
        $offsetY = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetY', 'integer', $ipsView)));
        $svg .= '<defs><filter id="river-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="' . $n($offsetX) . '" dy="' . $n($offsetY)
            . '" stdDeviation="' . $n($shadow / 2) . '" flood-color="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView), '#000000'))
            . '" flood-opacity="' . ($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView) < 0 ? '0.35' : '1') . '"/></filter></defs>';
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['border']);
            foreach ([60, 215, 370, 525, 680] as $x) {
                $svg .= '<path data-grid="true" d="M' . $x . ' ' . $n($top) . 'V' . $n($axisY) . '" stroke="' . $e($gridColor)
                    . '" stroke-width="' . max(0, min(5, $this->BarPreviewValue($values, 'GridLineWidth', 'integer', $ipsView)))
                    . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'GridOpacityPercent', 'integer', $ipsView))) / 100)
                    . '"' . $dash($this->BarPreviewValue($values, 'GridLineType', 'string', $ipsView)) . '/>';
            }
        }
        // A complete run on either side of one visibly empty example interval.
        foreach ([[60, 328], [378, 680]] as [$left, $right]) {
            $edges = [];
            for ($point = 0; $point < 9; ++$point) {
                $parts = [];
                for ($source = 0; $source < $count; ++$source) {
                    $parts[] = 10 + 8 * (1 + sin($point * 0.7 + $source * 1.3));
                }
                $scale = ($bottom - $top) / ($count * 26) * max(20, min(100, $this->BarPreviewValue($values, 'BandHeightPercent', 'integer', $ipsView))) / 100;
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
                    . '" stroke-width="' . $n($borderWidth) . '"' . $dash($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView))
                    . ($shadow > 0 || $offsetX !== 0 || $offsetY !== 0 ? ' filter="url(#river-shadow)"' : '') . '/>';
                if ($left === 60 && $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                    $p = $edges[$source][4];
                    $svg .= '<text x="' . $n($p[0]) . '" y="' . $n(($p[1] + $p[2]) / 2 + 4)
                        . '" text-anchor="middle" font-size="' . $n($font) . '" fill="' . $e($labelColor) . '"' . $textStyle('Label') . '>'
                        . $e(mb_strimwidth($item['label'], 0, 18, '…')) . '</text>';
                }
            }
        }
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        if ($this->BarPreviewValue($values, 'ShowAxisLine', 'boolean', $ipsView)) {
            $svg .= '<path data-axis-line="true" d="M60 ' . $n($axisY) . 'H680" stroke="' . $e($axisColor) . '" stroke-width="'
                . max(0, min(5, $this->BarPreviewValue($values, 'AxisLineWidth', 'integer', $ipsView))) . '"'
                . $dash($this->BarPreviewValue($values, 'AxisLineType', 'string', $ipsView)) . '/>';
        }
        $dateOnly = $this->BarPreviewValue($values, 'TimeLabelFormat', 'string', $ipsView) === 'date' || (!$dateTime
            && (($values['Aggregation'] ?? $this->ReadPropertyString('Aggregation')) === 'day'
                || ($values['Range'] ?? $this->ReadPropertyString('Range')) !== '24h'));
        foreach ([60, 215, 370, 525, 680] as $index => $x) {
            if ($axisTicks) {
                $svg .= '<path d="M' . $x . ' ' . $n($axisY) . 'v5" stroke="' . $e($axisColor) . '"/>';
            }
            if ($axisLabels) {
                $date = '0' . ($index + 1) . '.10.';
                $time = str_pad((string) ($index * 6), 2, '0', STR_PAD_LEFT) . ':00';
                $svg .= '<text data-axis-label="true" x="' . $x . '" y="' . $n($axisY + $axisMargin + $axisFont) . '" text-anchor="middle" fill="' . $e($axisColor) . '" font-size="' . $n($axisFont) . '"' . $textStyle('AxisLabel') . '>'
                    . ($dateTime ? '<tspan x="' . $x . '">' . $date . '</tspan><tspan x="' . $x . '" dy="' . $n(ceil($axisFont * 1.25)) . '">' . $time . '</tspan>' : ($dateOnly ? $date : $time)) . '</text>';
            }
        }
        if ($legend) {
            $legendColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $palette['text']);
            // A scroll legend occupies one row; show a bounded first page rather than squeezing eight large labels.
            $visible = min($count, max(1, (int) floor((648 + $legendGap) / ($legendWidth + 6 + $legendFont * 3 * 0.65 + $legendGap))));
            $available = $visible < $count ? 648 : 672;
            $slot = ($available - ($visible - 1) * $legendGap) / $visible;
            $legendItems = [];
            foreach (array_slice($items, 0, $visible) as $item) {
                $label = mb_strimwidth($item['label'], 0, max(3, (int) (($slot - $legendWidth - 6) / ($legendFont * 0.65))), '…');
                $legendItems[] = ['label' => $label, 'width' => $legendWidth + 6 + mb_strwidth($label) * $legendFont * 0.65];
            }
            $totalWidth = array_sum(array_column($legendItems, 'width')) + ($visible - 1) * $legendGap;
            $alignment = $this->BarPreviewValue($values, 'LegendAlignment', 'string', $ipsView);
            $x = $alignment === 'center' ? (720 - $totalWidth) / 2 : ($alignment === 'right' ? 696 - $totalWidth : 24);
            foreach (array_slice($items, 0, $visible) as $index => $item) {
                $color = $item['color'] ?: $palette['seriesColors'][$index % count($palette['seriesColors'])];
                $icon = $this->BarPreviewValue($values, 'LegendIcon', 'string', $ipsView);
                $scaleX = $icon === 'circle' ? min($legendWidth, $legendItemHeight) / 2 : $legendWidth / 2;
                $scaleY = $icon === 'circle' ? $scaleX : $legendItemHeight / 2;
                $svg .= '<g transform="translate(' . $n($x + $legendWidth / 2) . ' ' . $n($legendTop + $legendHeight / 2) . ') scale(' . $n($scaleX) . ' ' . $n($scaleY) . ')">'
                    . EChartsPreviewSymbol::Symbol($icon, 0, 0, 1, $color) . '</g>'
                    . '<text data-legend-label="true" x="' . $n($x + $legendWidth + 6) . '" y="' . $n($legendTop + ($legendHeight + $legendFont) / 2 - 2) . '" font-size="' . $n($legendFont) . '" fill="' . $e($legendColor) . '"' . $textStyle('Legend') . '>'
                    . $e($legendItems[$index]['label']) . '</text>';
                $x += $legendItems[$index]['width'] + $legendGap;
            }
            if ($visible < $count) {
                $svg .= '<text x="696" y="' . $n($legendTop + $legendHeight / 2 + 4) . '" text-anchor="end" font-size="12" fill="' . $e($legendColor) . '">…</text>';
            }
        }
        return $svg . '</svg>';
    }
}
