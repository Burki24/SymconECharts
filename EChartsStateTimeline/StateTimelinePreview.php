<?php

declare(strict_types=1);

namespace SymconECharts;

/** Bounded, explicitly synthetic intervals; never reads archives while editing. */
trait StateTimelinePreview
{
    private function StateTimelinePreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $e = EChartsBarPreview::E(...);
        $n = EChartsBarPreview::N(...);
        $color = fn (string $name, string $fallback): string => EChartsBarPreview::Color(
            $this->BarPreviewValue($values, $name, 'integer', $ipsView),
            $fallback
        );
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('StateTimeline preview'),
            $color('TitleColor', $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $font = $this->BarPreviewValue($values, 'FontFamily', 'string', $ipsView);
        $font = in_array($font, ['Arial', 'Verdana', 'Georgia', 'monospace'], true) ? $font : 'Arial';
        $svg = str_replace('<svg xmlns', '<svg font-family="' . $font . '" xmlns', $svg);
        $textStyle = fn (string $prefix): string => ' font-weight="'
            . ($this->BarPreviewValue($values, $prefix . 'Bold', 'boolean', $ipsView) ? 'bold' : 'normal')
            . '" font-style="' . ($this->BarPreviewValue($values, $prefix . 'Italic', 'boolean', $ipsView) ? 'italic' : 'normal') . '"';
        $svg = str_replace('<text x="24"', '<text' . $textStyle('Title') . ' x="24"', $svg);
        $absolute = ($values['Range'] ?? $this->ReadPropertyString('Range')) === 'absolute';
        $window = null;
        if ($absolute) {
            try {
                $window = $this->ResolveTimeQuery($values);
                $zone = new \DateTimeZone(date_default_timezone_get());
                $format = static fn (int $timestamp): string => (new \DateTimeImmutable('@' . $timestamp))->setTimezone($zone)->format('Y-m-d H:i:s P');
                $svg = str_replace('<svg ', '<svg data-range="absolute" data-requested-start="' . $window['StartTimestamp']
                    . '" data-requested-end="' . $window['EndTimestamp'] . '" ', $svg);
                $svg .= '<text x="24" y="62" font-size="10" fill="' . $e($palette['muted']) . '">'
                    . $e($this->Translate('Fixed period') . ': ' . $format($window['StartTimestamp'])
                        . ' → ' . $format($window['EndTimestamp']) . ' (' . $zone->getName() . ')') . '</text>';
            } catch (\Throwable $exception) {
                $svg .= '<text x="24" y="100" font-size="12" fill="' . $e($palette['text']) . '">';
                foreach (explode("\n", wordwrap($this->Translate($exception->getMessage()), 90, "\n")) as $index => $line) {
                    $svg .= '<tspan x="24" dy="' . ($index === 0 ? '0' : '1.4em') . '">' . $e($line) . '</tspan>';
                }
                return $svg . '</text></svg>';
            }
        }
        $number = fn (string $name, int $min, int $max): int => max($min, min($max, $this->BarPreviewValue($values, $name, 'integer', $ipsView)));
        $dash = static fn (string $type, int $width): string => $type === 'dashed' ? ' stroke-dasharray="' . max(1, $width) * 4 . ' ' . max(1, $width) * 2 . '"'
            : ($type === 'dotted' ? ' stroke-dasharray="' . max(1, $width) . ' ' . max(1, $width) . '"' : '');
        $items = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false, 32)['items'];
        $totalRows = count($items);
        $items = array_slice($items, 0, 8);
        $raw = $values['Sources'] ?? $this->ReadPropertyString('Sources');
        $raw = is_string($raw) ? json_decode($raw, true) : $raw;
        $raw = is_array($raw) ? array_values($raw) : [];
        $definitions = [];
        foreach ($raw as $source) {
            if (!is_array($source) || !is_int($source['VariableID'] ?? null)) {
                continue;
            }
            try {
                $definitions[$source['VariableID']] = StateTimelineData::Definitions($source['States'] ?? [], false);
            } catch (\Throwable) {
                // Invalid drafts use illustrative numeric labels without changing the saved source.
            }
        }
        $axisFont = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $labelFont = max(6, min(24, 12 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100));
        $axisColor = $color('AxisColor', $palette['text']);
        $readable = static function (string $fill): string
        {
            $luminance = 0.0;
            foreach ([0.2126, 0.7152, 0.0722] as $index => $weight) {
                $part = hexdec(substr($fill, 1 + $index * 2, 2)) / 255;
                $luminance += $weight * ($part <= 0.04045 ? $part / 12.92 : (($part + 0.055) / 1.055) ** 2.4);
            }
            return 1.05 / ($luminance + 0.05) >= ($luminance + 0.05) / 0.05560539162420272 ? '#FFFFFF' : '#111111';
        };
        $left = $this->BarPreviewValue($values, 'ShowSourceLabels', 'boolean', $ipsView) ? 170 : 24;
        $right = 692;
        $top = 88;
        $bottom = $this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView) ? 288 : 315;
        $timeFormat = $this->BarPreviewValue($values, 'TimeLabelFormat', 'string', $ipsView);
        $margin = $number('AxisLabelMargin', 0, 30);
        $bottom -= max(0, $margin - 12) + ($timeFormat === 'dateTime' ? (int) ceil($axisFont * 1.3) : 0);
        $height = ($bottom - $top) / max(1, count($items));
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            foreach ([0, 0.25, 0.5, 0.75, 1] as $fraction) {
                $x = $left + ($right - $left) * $fraction;
                $svg .= '<path data-state-grid="true" d="M' . $n($x) . ' ' . $top . 'V' . $bottom . '" stroke="' . $e($color('GridColor', $color('AxisColor', $palette['border'])))
                    . '" stroke-width="' . $number('GridLineWidth', 0, 5) . '" opacity="' . $n($number('GridOpacityPercent', 0, 100) / 100)
                    . '"' . $dash($this->BarPreviewValue($values, 'GridLineType', 'string', $ipsView), $number('GridLineWidth', 0, 5)) . '/>';
            }
        }
        $relativeHeight = max(20, min(100, $this->BarPreviewValue($values, 'RowHeightPercent', 'integer', $ipsView))) / 100;
        $opacity = max(0, min(100, $this->BarPreviewValue($values, 'BarOpacityPercent', 'integer', $ipsView))) / 100;
        foreach ($items as $index => $item) {
            $center = $top + ($index + 0.5) * $height;
            $map = $definitions[$item['VariableID'] ?? 0] ?? [];
            $stateValues = array_keys($map);
            if ($stateValues === []) {
                $stateValues = [0, 1];
            }
            if ($this->BarPreviewValue($values, 'ShowSourceLabels', 'boolean', $ipsView)) {
                $svg .= '<text data-source-label="true"' . $textStyle('AxisLabel') . ' x="158" y="' . $n($center + $axisFont / 3) . '" text-anchor="end" font-size="' . $n($axisFont)
                    . '" fill="' . $e($axisColor) . '">' . $e(mb_strimwidth($item['label'], 0, max(4, (int) (145 / ($axisFont * 0.65))), '…')) . '</text>';
            }
            $fractions = [0, 0.12, 0.38, 0.65, 1];
            foreach ([null, $stateValues[$index % count($stateValues)], $stateValues[($index + 1) % count($stateValues)], $stateValues[$index % count($stateValues)]] as $part => $state) {
                $definition = $state === null ? null : ($map[$state] ?? null);
                $fill = $state === null ? $color('UnknownColor', '#777B83')
                    : EChartsBarPreview::Color($definition['Color'] ?? -1, $palette['seriesColors'][(($state % count($palette['seriesColors'])) + count($palette['seriesColors'])) % count($palette['seriesColors'])]);
                $label = $state === null ? $this->Translate('Unknown') : ($definition['Label'] ?? (string) $state);
                $x = $left + ($right - $left) * $fractions[$part];
                $width = ($right - $left) * ($fractions[$part + 1] - $fractions[$part]);
                $barHeight = $height * $relativeHeight;
                $svg .= '<rect data-state-interval="true" x="' . $n($x) . '" y="' . $n($center - $barHeight / 2)
                    . '" width="' . $n($width) . '" height="' . $n($barHeight) . '" fill="' . $e($fill)
                    . '" fill-opacity="' . $n($opacity) . '" stroke="' . $e($color('BorderColor', $palette['border']))
                    . '" stroke-width="' . $number('BorderWidth', 0, 5) . '" rx="' . $n(min($number('BorderRadius', 0, 20), $width / 2, $barHeight / 2))
                    . '"' . $dash($this->BarPreviewValue($values, 'BorderType', 'string', $ipsView), $number('BorderWidth', 0, 5)) . '/>';
                if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView) && $barHeight >= $labelFont + 2) {
                    $shown = mb_strimwidth($label, 0, max(0, (int) (($width - 8) / ($labelFont * 0.65))), '…');
                    $position = $this->BarPreviewValue($values, 'LabelPosition', 'string', $ipsView);
                    $labelX = $position === 'left' ? $x + 4 : ($position === 'right' ? $x + $width - 4 : $x + $width / 2);
                    $anchor = $position === 'left' ? 'start' : ($position === 'right' ? 'end' : 'middle');
                    // Font metrics vary by device; clip the schematic text to the same inner bounds as the renderer.
                    $clipID = 'state-label-' . $index . '-' . $part;
                    $svg .= '<defs><clipPath id="' . $clipID . '"><rect x="' . $n($x + 4) . '" y="' . $n($center - $barHeight / 2)
                        . '" width="' . $n(max(0, $width - 8)) . '" height="' . $n($barHeight) . '"/></clipPath></defs>';
                    $svg .= '<text data-state-label="true" clip-path="url(#' . $clipID . ')"' . $textStyle('Label') . ' x="' . $n($labelX) . '" y="' . $n($center + $labelFont / 3)
                        . '" text-anchor="' . $anchor . '" font-size="' . $n($labelFont) . '" fill="' . $e($color('LabelColor', $readable($fill))) . '">' . $e($shown) . '</text>';
                }
            }
        }
        $range = $values['Range'] ?? $this->ReadPropertyString('Range');
        try {
            $window ??= StateTimelineData::Window(
                (string) $range,
                $this->CurrentTimestamp(),
                (int) ($values['CustomRangeValue'] ?? $this->ReadPropertyInteger('CustomRangeValue')),
                (string) ($values['CustomRangeUnit'] ?? $this->ReadPropertyString('CustomRangeUnit'))
            );
        } catch (\Throwable) {
            $window = StateTimelineData::Window('7d', $this->CurrentTimestamp());
        }
        $duration = $window['EndTimestamp'] - $window['StartTimestamp'];
        $axisWidth = $number('AxisLineWidth', 0, 5);
        $axisAttributes = ' stroke="' . $e($color('AxisColor', $palette['border'])) . '" stroke-width="' . $axisWidth . '"'
            . $dash($this->BarPreviewValue($values, 'AxisLineType', 'string', $ipsView), $axisWidth);
        if ($this->BarPreviewValue($values, 'ShowAxisLine', 'boolean', $ipsView)) {
            $svg .= '<path data-time-axis="true" d="M' . $left . ' ' . $bottom . 'H' . $right . '"' . $axisAttributes . '/>';
        }
        foreach (['00:00', '06:00', '12:00', '18:00', '24:00'] as $index => $time) {
            $x = $left + ($right - $left) * $index / 4;
            if ($this->BarPreviewValue($values, 'ShowAxisTicks', 'boolean', $ipsView)) {
                $svg .= '<path data-time-tick="true" d="M' . $n($x) . ' ' . $bottom . 'v5"' . $axisAttributes . '/>';
            }
            if (!$this->BarPreviewValue($values, 'ShowTimeLabels', 'boolean', $ipsView)) {
                continue;
            }
            $exampleTimestamp = ($absolute ? $window['StartTimestamp'] : 1790812800) + (int) round($duration * $index / 4);
            $local = $absolute ? (new \DateTimeImmutable('@' . $exampleTimestamp))->setTimezone($zone) : null;
            $date = $local === null ? gmdate('d.m.', $exampleTimestamp) : $local->format('d.m.');
            $time = $range === '24h' && $timeFormat !== 'dateTime' && $index === 4
                ? '24:00' : ($local === null ? gmdate('H:i', $exampleTimestamp) : $local->format('H:i'));
            $label = $timeFormat === 'time' || ($timeFormat === 'auto' && $duration <= 172800) ? $time : $date;
            $anchor = $index === 0 ? 'start' : ($index === 4 ? 'end' : 'middle');
            $svg .= '<text data-time-label="true"' . $textStyle('AxisLabel') . ' x="' . $n($x) . '" y="' . $n($bottom + $margin + $axisFont)
                . '" text-anchor="' . $anchor . '" font-size="' . $n($axisFont) . '" fill="' . $e($axisColor) . '">' . $e($label)
                . ($timeFormat === 'dateTime' ? '<tspan x="' . $n($x) . '" dy="' . $n($axisFont * 1.3) . '">' . $e($time) . '</tspan>' : '') . '</text>';
        }
        if ($this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView)) {
            $svg .= '<rect x="' . $left . '" y="330" width="' . ($right - $left) . '" height="12" fill="' . $e($palette['surface'] ?? $palette['background']) . '" stroke="' . $e($palette['border']) . '"/>';
        }
        if ($totalRows > 8) {
            $svg .= '<text x="360" y="356" text-anchor="middle" font-size="10" fill="' . $e($palette['text']) . '">'
                . $e(sprintf($this->Translate('Preview: first %d of %d rows. Scroll in the chart to see all.'), 8, $totalRows)) . '</text>';
        }
        return $svg . '</svg>';
    }
}
