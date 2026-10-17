<?php

declare(strict_types=1);

namespace SymconECharts;

/** Synthetic geometry only: editing a designer never reads archive data. */
trait CandlestickPreview
{
    private function CandlestickPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Candlestick preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="' . EChartsBarPreview::E($this->BarPreviewValue($values, 'FontFamily', 'string', $ipsView)) . '" xmlns', $svg);
        $svg = str_replace('<text x="24"', '<text font-style="' . ($this->BarPreviewValue($values, 'TitleItalic', 'boolean', $ipsView) ? 'italic' : 'normal')
            . '" font-weight="' . ($this->BarPreviewValue($values, 'TitleBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '" x="24"', $svg);
        $items = array_map(static fn (int $day): array => ['label' => '10-' . $day], range(10, 16));
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $axis = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $axisLine = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisLineColor', 'integer', $ipsView), $palette['border']);
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $bold = $this->BarPreviewValue($values, 'AxisLabelBold', 'boolean', $ipsView) ? 'bold' : 'normal';
        $italic = $this->BarPreviewValue($values, 'AxisLabelItalic', 'boolean', $ipsView) ? 'italic' : 'normal';
        $margin = max(0, min(30, $this->BarPreviewValue($values, 'AxisLabelMargin', 'integer', $ipsView)));
        $minimumValue = $this->BarPreviewValue($values, 'IncludeZero', 'boolean', $ipsView) ? 0 : 4;
        $n = EChartsBarPreview::N(...);
        $e = EChartsBarPreview::E(...);
        $dash = static fn (string $type): string => match ($type) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $line = static fn ($x1, $y1, $x2, $y2, $color, $width): string => '<path d="M' . $n($x1) . ' ' . $n($y1) . 'L' . $n($x2) . ' ' . $n($y2) . '" fill="none" stroke="' . $e($color) . '" stroke-width="' . $n($width) . '"/>';
        $rotation = max(-90, min(90, $this->BarPreviewValue($values, 'AxisLabelRotate', 'integer', $ipsView)));
        $requestedWidth = max(0, min(300, $this->BarPreviewValue($values, 'AxisLabelMaxWidth', 'integer', $ipsView)));
        $slot = ($horizontal ? 220 : 600) / count($items);
        $labelWidth = $requestedWidth > 0 ? min($requestedWidth, $horizontal ? 135 : 650) : ($horizontal ? 135 : $slot - 8);
        $sine = abs(sin(deg2rad($rotation)));
        if ($rotation !== 0) {
            $labelWidth = min($labelWidth, max(12, ($horizontal ? $slot * 0.7 : 90) / $sine));
        }
        $bottom = 295 - (!$horizontal && $this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView) ? $labelWidth * $sine : 0);
        $shadow = max(0, min(20, $this->BarPreviewValue($values, 'ShadowBlur', 'integer', $ipsView)));
        $offsetX = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetX', 'integer', $ipsView)));
        $offsetY = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetY', 'integer', $ipsView)));
        $shadowEnabled = $shadow > 0 || $offsetX !== 0 || $offsetY !== 0;
        $svg .= '<defs><filter id="candle-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="' . $n($offsetX) . '" dy="' . $n($offsetY)
            . '" stdDeviation="' . $n($shadow / 2) . '" flood-color="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView), '#000000'))
            . '" flood-opacity="' . ($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView) < 0 ? '0.35' : '1') . '"/></filter></defs>';
        $splits = max(2, min(10, $this->BarPreviewValue($values, 'SplitNumber', 'integer', $ipsView)));
        $grid = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['border']);
        $svg .= '<g data-grid="true" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'GridOpacityPercent', 'integer', $ipsView))) / 100)
            . '"' . $dash($this->BarPreviewValue($values, 'GridLineType', 'string', $ipsView)) . '>';
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            for ($tick = 0; $tick <= $splits; $tick++) {
                $position = $horizontal ? 160 + $tick * 520 / $splits : $bottom - $tick * ($bottom - 75) / $splits;
                $gridWidth = max(0, min(5, $this->BarPreviewValue($values, 'GridLineWidth', 'integer', $ipsView)));
                $svg .= $horizontal ? $line($position, 75, $position, $bottom, $grid, $gridWidth)
                    : $line(80, $position, 680, $position, $grid, $gridWidth);
            }
        }
        $svg .= '</g>';
        $axisWidth = max(0, min(5, $this->BarPreviewValue($values, 'AxisLineWidth', 'integer', $ipsView)));
        $svg .= '<g' . $dash($this->BarPreviewValue($values, 'AxisLineType', 'string', $ipsView)) . '>';
        $svg .= $horizontal ? $line(160, 75, 160, $bottom, $axisLine, $axisWidth)
            : $line(80, $bottom, 680, $bottom, $axisLine, $axisWidth);
        $svg .= '</g>';
        if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
            for ($tick = 0; $tick <= $splits; $tick++) {
                $x = $horizontal ? 160 + $tick * 520 / $splits : 78 - $margin;
                $y = $horizontal ? $bottom + $font + $margin : $bottom - $tick * ($bottom - 75) / $splits + 4;
                $svg .= '<text x="' . $n($x) . '" y="' . $n($y) . '" text-anchor="' . ($horizontal ? 'middle' : 'end')
                    . '" font-size="' . $n($font) . '" font-style="' . $italic . '" font-weight="' . $bold . '" fill="' . $e($axis) . '">' . $n($minimumValue + (40 - $minimumValue) * $tick / $splits) . '</text>';
            }
        }
        $width = min(max(8, $this->BarPreviewValue($values, 'CandleWidth', 'integer', $ipsView)), $slot * 0.65);
        $minimum = min(max(1, $this->BarPreviewValue($values, 'MinCandleWidth', 'integer', $ipsView)), $width);
        // ECharts clamps its automatic width between the configured bounds.
        $width = max($minimum, min($width, $slot * 0.8 - 2));
        $fixed = max(0, min(80, $this->BarPreviewValue($values, 'FixedCandleWidth', 'integer', $ipsView)));
        if ($fixed > 0) {
            $width = max($minimum, min($fixed, $width));
        }
        foreach ($items as $index => $item) {
            $position = $this->BarPreviewValue($values, 'ReverseTimeAxis', 'boolean', $ipsView) ? count($items) - $index - 1 : $index;
            $center = ($horizontal ? 75 : 80) + $slot * ($horizontal ? count($items) - $position - 0.5 : $position + 0.5);
            $offset = ($index % 4) * 0.07;
            $stats = array_map(static function ($v) use ($horizontal, $offset, $minimumValue, $bottom): float
            {
                $fraction = (($v + $offset) * 40 - $minimumValue) / (40 - $minimumValue);
                return $horizontal ? 160 + 520 * $fraction : $bottom - ($bottom - 75) * $fraction;
            }, [0.10, 0.30, 0.45, 0.60, 0.75]);
            $kind = ['RiseColor', 'FallColor', 'DojiColor'][$index % 3];
            $color = EChartsBarPreview::Color($this->BarPreviewValue($values, $kind, 'integer', $ipsView), $kind === 'DojiColor' ? $palette['text'] : ($kind === 'RiseColor' ? $palette['accent'] : ($palette['seriesColors'][3] ?? '#E66B6B')));
            $border = $kind === 'DojiColor' ? $color : EChartsBarPreview::Color(
                $this->BarPreviewValue($values, $kind === 'RiseColor' ? 'RiseBorderColor' : 'FallBorderColor', 'integer', $ipsView),
                EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $color)
            );
            $hollow = $kind === 'DojiColor' || $this->BarPreviewValue($values, $kind === 'RiseColor' ? 'RiseHollow' : 'FallHollow', 'boolean', $ipsView);
            if ($index % 3 === 2) {
                $stats[3] = $stats[1];
            }
            $stroke = max(0, min(8, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
            $svg .= '<g data-candle="' . $index . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'CandleOpacityPercent', 'integer', $ipsView))) / 100)
                . '"' . $dash($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView)) . ($shadowEnabled ? ' filter="url(#candle-shadow)"' : '') . '>';
            if ($horizontal) {
                $svg .= $line($stats[0], $center, $stats[4], $center, $border, $stroke);
            } else {
                $svg .= $line($center, $stats[0], $center, $stats[4], $border, $stroke);
            }
            $svg .= '<rect x="' . $n($horizontal ? $stats[1] : $center - $width / 2) . '" y="' . $n($horizontal ? $center - $width / 2 : $stats[3])
                . '" width="' . $n($horizontal ? $stats[3] - $stats[1] : $width) . '" height="' . $n($horizontal ? $width : $stats[1] - $stats[3])
                . '" fill="' . $e($hollow ? 'none' : $color) . '" stroke="' . $e($border) . '" stroke-width="' . $n($stroke) . '"/>';
            $svg .= '</g>';
            if ($this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView)) {
                $svg .= $horizontal ? $line(155, $center, 160, $center, $palette['border'], 1) : $line($center, $bottom, $center, $bottom + 5, $palette['border'], 1);
            }
            if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
                $x = $horizontal ? 158 - $margin : $center;
                $y = $horizontal ? $center + 4 : $bottom + $font + $margin;
                $anchor = $horizontal || $rotation > 0 ? 'end' : ($rotation < 0 ? 'start' : 'middle');
                $svg .= '<text x="' . $n($x) . '" y="' . $n($y) . '" transform="rotate(' . $n(-$rotation) . ' ' . $n($x) . ' ' . $n($y)
                    . ')" text-anchor="' . $anchor . '" font-size="' . $n($font) . '" font-style="' . $italic . '" font-weight="' . $bold . '" fill="' . $e($axis) . '">'
                    . $e(mb_strimwidth($item['label'], 0, max(3, (int) ($labelWidth / ($font * 0.55))), '…')) . '</text>';
            }
        }
        // Hover effects cannot be triggered inside the static SVG; explain that boundary.
        $id = $values['VariableID'] ?? $this->ReadPropertyInteger('VariableID');
        if (is_int($id) && $id > 0 && IPS_VariableExists($id)) {
            $label = EChartsSourceIdentity::LabelsForSources([['VariableID' => $id, 'Label' => '']])[$id];
            $svg .= '<text x="24" y="335" font-size="10" fill="' . $e($palette['muted']) . '">'
                . $e(mb_strimwidth($label, 0, 95, '…')) . '</text>';
        }
        return $svg . '<text x="24" y="350" font-size="10" fill="' . $e($palette['muted']) . '">'
            . $e($this->Translate('Hover effects are visible in the chart.')) . '</text></svg>';
    }
}
