<?php

declare(strict_types=1);

namespace SymconECharts;

use DomainException;
use Throwable;

/** Bounded draft-only preview; no Gateway requests, status or configuration writes. */
trait FunnelPreview
{
    private function FunnelPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $raw = $values['Sources'] ?? json_decode($this->ReadPropertyString('Sources'), true);
        $sample = $raw === [];
        $invalid = false;
        $items = [];
        try {
            if ($sample) {
                foreach ([100, 70, 45, 20] as $index => $value) {
                    $items[] = ['Label' => $this->Translate('Stage') . ' ' . ($index + 1), 'value' => (float) $value,
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
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            $sample,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Funnel preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="' . EChartsBarPreview::E($this->BarPreviewValue($values, 'FontFamily', 'string', $ipsView)) . '" xmlns', $svg);
        $svg = str_replace('<text x="24"', '<text font-weight="' . ($this->BarPreviewValue($values, 'TitleBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '" x="24"', $svg);
        $e = EChartsBarPreview::E(...);
        $n = EChartsBarPreview::N(...);
        $maximum = $items === [] ? 0 : max(array_column($items, 'value'));
        $totalCount = count($items);
        $sourceSelection = (bool) ($values['EnableSourceSelection'] ?? $this->ReadPropertyBoolean('EnableSourceSelection'));
        $paged = $totalCount > 16 || $sourceSelection;
        if ($sourceSelection) {
            $items = array_values(array_filter($items, static fn (array $item): bool => ($item['InitiallyVisible'] ?? true) === true));
        }
        $sort = $this->BarPreviewValue($values, 'SortOrder', 'string', $ipsView);
        if (in_array($sort, ['ascending', 'descending'], true)) {
            usort($items, static fn (array $a, array $b): int => ($sort === 'ascending'
                ? $a['value'] <=> $b['value'] : $b['value'] <=> $a['value']) ?: $a['order'] <=> $b['order']);
        }
        $selectedCount = count($items);
        $selectedMaximum = $items === [] ? 0 : max(array_column($items, 'value'));
        if ($paged) {
            $items = array_slice($items, 0, 8);
        }
        $svg = str_replace('<svg ', '<svg data-source-count="' . $totalCount . '" data-selected-count="' . $selectedCount
            . '" data-rendered-count="' . count($items) . '" data-maximum="' . $e((string) $maximum) . '" ', $svg);
        if ($invalid || $items === [] || $maximum <= 0 || $selectedMaximum <= 0) {
            $message = $invalid ? 'Preview requires valid non-negative stage values.'
                : ($items === [] ? 'No sources selected' : ($maximum <= 0 ? 'All stage values are zero.' : 'All selected stage values are zero.'));
            return $svg . '<text x="360" y="190" text-anchor="middle" fill="' . $e($palette['text']) . '">'
                . $e($this->Translate($message)) . '</text></svg>';
        }
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $outside = $this->BarPreviewValue($values, 'LabelPosition', 'string', $ipsView) === 'outside';
        $outsideStart = $outside && $this->BarPreviewValue($values, 'OutsideLabelSide', 'string', $ipsView) === 'start';
        $hideOverlap = $this->BarPreviewValue($values, 'HideOverlappingLabels', 'boolean', $ipsView);
        $font = max(6, min(26, 13 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100));
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $lineLength = max(0, min(60, $this->BarPreviewValue($values, 'LabelLineLength', 'integer', $ipsView)));
        $reserve = $horizontal ? min(360 * 0.35, $font * 3 + 26 + $lineLength - 10)
            : min(720 * 0.42, max(80, $font * 13) + $lineLength - 10);
        $left = $outsideStart && !$horizontal ? $reserve : 18;
        $top = 65;
        if ($outsideStart && $horizontal) {
            $top += $reserve;
        }
        $width = 720 - $left - ($outside && !$outsideStart && !$horizontal ? $reserve : 18);
        $height = 360 - $top - ($outside && !$outsideStart && $horizontal ? $reserve : 18);
        $extent = $horizontal ? $width : $height;
        $gap = min(max(0, $this->BarPreviewValue($values, 'Gap', 'integer', $ipsView)), $extent / (count($items) * 2));
        $step = ($extent - $gap * (count($items) - 1)) / count($items);
        $alignment = $this->BarPreviewValue($values, 'Alignment', 'string', $ipsView);
        $offset = $alignment === 'start' ? 0 : ($alignment === 'end' ? 1 : 0.5);
        $cross = $horizontal ? $height : $width;
        $minimumSize = max(0, min(100, $this->BarPreviewValue($values, 'MinSizePercent', 'integer', $ipsView))) / 100;
        $maximumSize = max($minimumSize, min(100, $this->BarPreviewValue($values, 'MaxSizePercent', 'integer', $ipsView)) / 100);
        $line = static function (float $value, float $position) use ($horizontal, $left, $top, $cross, $offset, $maximum, $minimumSize, $maximumSize): array
        {
            $size = ($minimumSize + $value / $maximum * ($maximumSize - $minimumSize)) * $cross;
            $start = ($cross - $size) * $offset;
            return $horizontal ? [[$left + $position, $top + $start], [$left + $position, $top + $start + $size]]
                : [[$left + $start, $top + $position], [$left + $start + $size, $top + $position]];
        };
        $dash = static fn (string $type): string => match ($type) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $shadow = max(0, min(20, $this->BarPreviewValue($values, 'ShadowBlur', 'integer', $ipsView)));
        $offsetX = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetX', 'integer', $ipsView)));
        $offsetY = max(-20, min(20, $this->BarPreviewValue($values, 'ShadowOffsetY', 'integer', $ipsView)));
        $shadowEnabled = $shadow > 0 || $offsetX !== 0 || $offsetY !== 0;
        $svg .= '<defs><filter id="funnel-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="' . $n($offsetX) . '" dy="' . $n($offsetY)
            . '" stdDeviation="' . $n($shadow / 2) . '" flood-color="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView), '#000000'))
            . '" flood-opacity="' . ($this->BarPreviewValue($values, 'ShadowColor', 'integer', $ipsView) < 0 ? '0.35' : '1') . '"/></filter></defs>';
        $labelBounds = [];
        foreach ($items as $index => $item) {
            $first = $line($item['value'], $index * ($step + $gap));
            $last = $line($items[$index + 1]['value'] ?? 0.0, $index * ($step + $gap) + $step);
            $points = [$first[0], $first[1], $last[1], $last[0]];
            $color = EChartsBarPreview::Color($item['Color'], $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])]);
            $svg .= '<polygon data-stage="' . $item['order'] . '" data-source-id="variable-' . ($item['VariableID'] ?? $item['order'])
                . '" data-value="' . $e((string) $item['value']) . '" points="' . implode(' ', array_map(static fn (array $point): string => $n($point[0]) . ',' . $n($point[1]), $points))
                . '" fill="' . $e($color) . '" opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'OpacityPercent', 'integer', $ipsView))) / 100)
                . '" stroke="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['background']))
                . '" stroke-width="' . $n(max(0, min(8, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)))) . '"'
                . $dash($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView)) . ($shadowEnabled ? ' filter="url(#funnel-shadow)"' : '')
                . '><title>' . $e($item['Label']) . '</title></polygon>';
            if (!$this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView) || ($hideOverlap && !$horizontal && !$outside && $step < $font * ($showValues ? 2 : 1))) {
                continue;
            }
            $x = array_sum(array_column($points, 0)) / 4;
            $y = array_sum(array_column($points, 1)) / 4;
            $anchor = 'middle';
            $leader = '';
            $labelWidth = $horizontal ? $step - 8 : (($minimumSize + ($item['value'] / $maximum + (($items[$index + 1]['value'] ?? 0) / $maximum)) / 2 * ($maximumSize - $minimumSize)) * $width - 8);
            if ($outside) {
                $side = $outsideStart ? 0 : 1;
                $direction = $outsideStart ? -1 : 1;
                $edgeX = $horizontal ? $x : ($first[$side][0] + $last[$side][0]) / 2;
                $edgeY = $horizontal ? ($first[$side][1] + $last[$side][1]) / 2 : $y;
                $x = $edgeX + ($horizontal ? 0 : $direction * ($lineLength + 5));
                $y = $edgeY + ($horizontal ? $direction * ($lineLength + 5) : 0);
                $anchor = $horizontal ? 'middle' : ($outsideStart ? 'end' : 'start');
                $labelWidth = $horizontal ? $step - 6 : $reserve - $lineLength - 12;
                if ($this->BarPreviewValue($values, 'ShowLabelLines', 'boolean', $ipsView)) {
                    $leader = '<path data-guide="true" d="M' . $n($edgeX) . ' ' . $n($edgeY) . 'L' . $n($edgeX + ($horizontal ? 0 : $direction * $lineLength)) . ' ' . $n($edgeY + ($horizontal ? $direction * $lineLength : 0))
                        . '" stroke="' . $e(EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelLineColor', 'integer', $ipsView), $color))
                        . '" stroke-width="' . $n(max(0, min(5, $this->BarPreviewValue($values, 'LabelLineWidth', 'integer', $ipsView))))
                        . '" stroke-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'LabelLineOpacityPercent', 'integer', $ipsView))) / 100) . '"'
                        . $dash($this->BarPreviewValue($values, 'LabelLineType', 'string', $ipsView)) . '/>';
                }
            }
            $requestedWidth = max(0, min(300, $this->BarPreviewValue($values, 'LabelMaxWidth', 'integer', $ipsView)));
            if ($requestedWidth > 0) {
                $labelWidth = min($labelWidth, $requestedWidth);
            }
            $limit = max(1, (int) floor(max(1, $labelWidth) / ($font * 0.6)));
            $label = mb_strimwidth($item['Label'], 0, $limit, '…', 'UTF-8');
            $textColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $outside ? $palette['text'] : self::FunnelPreviewTextColor($color));
            $textWidth = min(max(1, $labelWidth), max(mb_strlen($label), 4) * $font * 0.6);
            $bound = ['left' => $x - ($anchor === 'middle' ? $textWidth / 2 : ($anchor === 'end' ? $textWidth : 0)),
                'right'      => $x + ($anchor === 'middle' ? $textWidth / 2 : ($anchor === 'end' ? 0 : $textWidth)),
                'top'        => $y - $font * ($showValues ? 1 : 0.5), 'bottom' => $y + $font * ($showValues ? 1 : 0.5)];
            foreach ($labelBounds as $previous) {
                if ($hideOverlap && $bound['left'] < $previous['right'] && $bound['right'] > $previous['left']
                    && $bound['top'] < $previous['bottom'] && $bound['bottom'] > $previous['top']) {
                    continue 2;
                }
            }
            $labelBounds[] = $bound;
            $svg .= $leader;
            $svg .= '<text x="' . $n($x) . '" y="' . $n($y - ($showValues ? $font * 0.5 : 0)) . '" text-anchor="' . $anchor
                . '" dominant-baseline="middle" font-size="' . $n($font) . '" font-weight="' . ($this->BarPreviewValue($values, 'LabelBold', 'boolean', $ipsView) ? 'bold' : 'normal')
                . '" fill="' . $e($textColor) . '">' . $e($label);
            if ($showValues) {
                $formatted = EChartsBarPreview::FormatValue($item['value'], $item['Decimals'], $item['Unit'], $this->ResolveHelperTranslationLanguage());
                $svg .= '<tspan x="' . $n($x) . '" dy="' . $n($font) . '">' . $e(mb_strimwidth($formatted, 0, $limit, '…', 'UTF-8')) . '</tspan>';
            }
            $svg .= '</text>';
        }
        return $svg . '<text x="24" y="354" font-size="10" fill="' . $e($palette['muted']) . '">'
            . $e($paged ? sprintf($this->Translate('Showing %d of %d selected stages (%d configured).'), count($items), $selectedCount, $totalCount)
                : $this->Translate('Hover effects are visible in the chart.')) . '</text></svg>';
    }

    /** Same white/#111111 luminance comparison as the shared JavaScript designer. */
    private static function FunnelPreviewTextColor(string $hex): string
    {
        $luminance = 0.0;
        foreach ([0.2126, 0.7152, 0.0722] as $index => $weight) {
            $channel = hexdec(substr($hex, 1 + 2 * $index, 2)) / 255;
            $luminance += $weight * ($channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4);
        }
        $dark = ((17 / 255 + 0.055) / 1.055) ** 2.4;
        return 1.05 / ($luminance + 0.05) >= ($luminance + 0.05) / ($dark + 0.05) ? '#FFFFFF' : '#111111';
    }
}
