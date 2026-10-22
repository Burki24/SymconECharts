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
        $svg = str_replace('<text x="24"', '<text font-weight="' . ($this->BarPreviewValue($values, 'TitleBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '" x="24"', $svg);
        $whiskers = $values['WhiskerMode'] ?? $this->ReadPropertyString('WhiskerMode');
        if (!in_array($whiskers, ['minmax', 'iqr'], true)) {
            return $svg . '<text x="24" y="100" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E($this->Translate('The Boxplot settings are invalid.')) . '</text></svg>';
        }
        $svg = str_replace('<svg ', '<svg data-whiskers="' . $whiskers . '" ', $svg);
        if ($whiskers === 'iqr') {
            $svg .= '<text x="24" y="51" font-size="10" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E($this->Translate('Completed hourly means · whiskers: 1.5 IQR')) . '</text>';
        }
        if (($values['Range'] ?? $this->ReadPropertyString('Range')) === 'absolute') {
            try {
                $query = $this->ResolveTimeQuery(1, $values);
                $zone = new \DateTimeZone(date_default_timezone_get());
                $format = static fn (int $timestamp): string => (new \DateTimeImmutable('@' . $timestamp))->setTimezone($zone)->format('Y-m-d H:i:s P');
                $svg = str_replace('<svg ', '<svg data-range="absolute" data-requested-start="' . $query['RequestedStartTimestamp']
                    . '" data-requested-end="' . $query['RequestedEndTimestamp'] . '" ', $svg);
                $svg .= '<text x="24" y="62" font-size="10" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                    . EChartsBarPreview::E($this->Translate('Fixed period') . ': ' . $format($query['RequestedStartTimestamp'])
                        . ' → ' . $format($query['RequestedEndTimestamp']) . ' (' . $zone->getName() . ')') . '</text>';
            } catch (\Throwable $exception) {
                $lines = explode("\n", wordwrap($this->Translate($exception->getMessage()), 90, "\n"));
                $svg .= '<text x="24" y="100" font-size="12" fill="' . EChartsBarPreview::E($palette['text']) . '">';
                foreach ($lines as $index => $line) {
                    $svg .= '<tspan x="24" dy="' . ($index === 0 ? '0' : '1.4em') . '">' . EChartsBarPreview::E($line) . '</tspan>';
                }
                return $svg . '</text></svg>';
            }
        }
        $allItems = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false, 32)['items'];
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $axis = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['border']);
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $minimumSpan = max($horizontal ? 22 : 40, $font * ($horizontal ? 1.5 : 2.4) + 4);
        $items = array_slice($allItems, 0, max(1, min(10, (int) floor(($horizontal ? 220 : 600) / $minimumSpan))));
        $bold = $this->BarPreviewValue($values, 'AxisLabelBold', 'boolean', $ipsView) ? 'bold' : 'normal';
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
        $svg .= '<defs><filter id="box-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="' . $n($offsetX) . '" dy="' . $n($offsetY)
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
        $svg .= $horizontal ? $line(160, 75, 160, $bottom, $palette['border'], $axisWidth)
            : $line(80, $bottom, 680, $bottom, $palette['border'], $axisWidth);
        if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
            for ($tick = 0; $tick <= $splits; $tick++) {
                $x = $horizontal ? 160 + $tick * 520 / $splits : 70;
                $y = $horizontal ? 315 : $bottom - $tick * ($bottom - 75) / $splits + 4;
                $svg .= '<text x="' . $n($x) . '" y="' . $n($y) . '" text-anchor="' . ($horizontal ? 'middle' : 'end')
                    . '" font-size="' . $n($font) . '" font-weight="' . $bold . '" fill="' . $e($axis) . '">' . $n(40 * $tick / $splits) . '</text>';
            }
        }
        $width = min(max(8, $this->BarPreviewValue($values, 'BoxWidth', 'integer', $ipsView)), $slot * 0.65);
        $minimum = min(max(1, $this->BarPreviewValue($values, 'MinBoxWidth', 'integer', $ipsView)), $width);
        // ECharts clamps its automatic width between the configured bounds.
        $width = max($minimum, min($width, $slot * 0.8 - 2));
        foreach ($items as $index => $item) {
            $center = ($horizontal ? 75 : 80) + $slot * ($horizontal ? count($items) - $index - 0.5 : $index + 0.5);
            $offset = ($index % 4) * 0.07;
            $position = static fn ($v): float => $horizontal ? 160 + 520 * $v : $bottom - ($bottom - 75) * $v;
            $sampleSummary = null;
            if ($whiskers === 'iqr') {
                $points = [];
                foreach ([0.01, 0.20, 0.24, 0.28, 0.32, 0.36, 0.40, 0.72] as $i => $v) {
                    $points[] = [$i + 1, ($v + $offset) * 40];
                }
                $sampleSummary = BoxplotStatistics::Summarize($points, 1, 8, $whiskers);
            }
            $stats = $sampleSummary !== null ? array_map(static fn ($v): float => $position($v / 40), $sampleSummary['statistics'])
                : array_map(static fn ($v): float => $position($v + $offset), [0.10, 0.30, 0.45, 0.60, 0.75]);
            $color = $item['color'] ?: $palette['seriesColors'][$index % count($palette['seriesColors'])];
            $stroke = max(0, min(8, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
            $svg .= '<g data-box="' . $index . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'BoxOpacityPercent', 'integer', $ipsView))) / 100)
                . '"' . $dash($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView)) . ($shadowEnabled ? ' filter="url(#box-shadow)"' : '') . '>';
            if ($horizontal) {
                $svg .= $line($stats[0], $center, $stats[4], $center, $border, $stroke);
                foreach ([0, 4] as $end) {
                    $svg .= $line($stats[$end], $center - $width / 2, $stats[$end], $center + $width / 2, $border, $stroke);
                }
            } else {
                $svg .= $line($center, $stats[0], $center, $stats[4], $border, $stroke);
                foreach ([0, 4] as $end) {
                    $svg .= $line($center - $width / 2, $stats[$end], $center + $width / 2, $stats[$end], $border, $stroke);
                }
            }
            $svg .= '<rect x="' . $n($horizontal ? $stats[1] : $center - $width / 2) . '" y="' . $n($horizontal ? $center - $width / 2 : $stats[3])
                . '" width="' . $n($horizontal ? $stats[3] - $stats[1] : $width) . '" height="' . $n($horizontal ? $width : $stats[1] - $stats[3])
                . '" fill="' . $e($color) . '" stroke="' . $e($border) . '" stroke-width="' . $n($stroke) . '"/>';
            $svg .= $horizontal ? $line($stats[2], $center - $width / 2, $stats[2], $center + $width / 2, $border, $stroke)
                : $line($center - $width / 2, $stats[2], $center + $width / 2, $stats[2], $border, $stroke);
            $svg .= '</g>';
            foreach ($sampleSummary['outliers'] ?? [] as [$timestamp, $outlier]) {
                $p = $position($outlier / 40);
                $svg .= '<circle data-outlier="' . $index . '-' . $timestamp . '" cx="' . $n($horizontal ? $p : $center)
                    . '" cy="' . $n($horizontal ? $center : $p) . '" r="3" fill="' . $e($color) . '"><title>'
                    . $e($item['label'] . ' · ' . $this->Translate('Outlier') . ': ' . $n($outlier)) . '</title></circle>';
            }
            if ($this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView)) {
                $svg .= $horizontal ? $line(155, $center, 160, $center, $palette['border'], 1) : $line($center, $bottom, $center, $bottom + 5, $palette['border'], 1);
            }
            if ($this->BarPreviewValue($values, 'ShowAxisLabels', 'boolean', $ipsView)) {
                $x = $horizontal ? 150 : $center;
                $y = $horizontal ? $center + 4 : $bottom + $font + 8;
                $anchor = $horizontal || $rotation > 0 ? 'end' : ($rotation < 0 ? 'start' : 'middle');
                $svg .= '<text x="' . $n($x) . '" y="' . $n($y) . '" transform="rotate(' . $n(-$rotation) . ' ' . $n($x) . ' ' . $n($y)
                    . ')" text-anchor="' . $anchor . '" font-size="' . $n($font) . '" font-weight="' . $bold . '" fill="' . $e($axis) . '">'
                    . $e(mb_strimwidth($item['label'], 0, max(3, (int) ($labelWidth / ($font * 0.55))), '…')) . '</text>';
            }
        }
        // Hover effects cannot be triggered inside the static SVG; explain that boundary.
        return EChartsPreviewSourceCount::Append(
            $svg . '<text x="24" y="350" font-size="10" fill="' . $e($palette['muted']) . '">'
                . $e($this->Translate('Hover effects are visible in the chart.')) . '</text></svg>',
            count($items),
            count($allItems),
            $this->Translate('Preview shows %d of %d sources'),
            $palette['muted'],
            696,
            333
        );
    }
}
