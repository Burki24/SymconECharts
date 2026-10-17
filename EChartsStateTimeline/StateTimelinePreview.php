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
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        $items = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false)['items'];
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
        $left = 170;
        $right = 692;
        $top = 88;
        $bottom = $this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView) ? 288 : 315;
        $height = ($bottom - $top) / max(1, count($items));
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            foreach ([0, 0.25, 0.5, 0.75, 1] as $fraction) {
                $x = $left + ($right - $left) * $fraction;
                $svg .= '<path d="M' . $n($x) . ' ' . $top . 'V' . $bottom . '" stroke="' . $e($palette['border']) . '" opacity="0.2"/>';
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
            $svg .= '<text x="158" y="' . $n($center + $axisFont / 3) . '" text-anchor="end" font-size="' . $n($axisFont)
                . '" fill="' . $e($axisColor) . '">' . $e(mb_strimwidth($item['label'], 0, max(4, (int) (145 / ($axisFont * 0.65))), '…')) . '</text>';
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
                    . '" stroke-width="' . max(0, min(5, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView))) . '"/>';
                if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView) && $barHeight >= $labelFont + 2) {
                    $shown = mb_strimwidth($label, 0, max(0, (int) (($width - 8) / ($labelFont * 0.65))), '…');
                    $svg .= '<text x="' . $n($x + $width / 2) . '" y="' . $n($center + $labelFont / 3)
                        . '" text-anchor="middle" font-size="' . $n($labelFont) . '" fill="' . $e($color('LabelColor', $readable($fill))) . '">' . $e($shown) . '</text>';
                }
            }
        }
        $range = $values['Range'] ?? $this->ReadPropertyString('Range');
        foreach (['00:00', '06:00', '12:00', '18:00', '24:00'] as $index => $time) {
            $label = $range === '24h' ? $time : sprintf('%02d.10.', 1 + $index * ($range === '30d' ? 7 : 2));
            $svg .= '<text x="' . $n($left + ($right - $left) * $index / 4) . '" y="' . $n($bottom + 10 + $axisFont)
                . '" text-anchor="middle" font-size="' . $n($axisFont) . '" fill="' . $e($axisColor) . '">' . $e($label) . '</text>';
        }
        if ($this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView)) {
            $svg .= '<rect x="170" y="330" width="522" height="12" fill="' . $e($palette['surface'] ?? $palette['background']) . '" stroke="' . $e($palette['border']) . '"/>';
        }
        return $svg . '</svg>';
    }
}
