<?php

declare(strict_types=1);

namespace SymconECharts;

use Throwable;

/** Read-only schematic preview using the same normalized current-value model as the renderer. */
trait RadarPreview
{
    private function RadarPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            false,
            $ipsView,
            $values,
            '',
            $this->Translate('Radar preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" xmlns', $svg);
        try {
            $configuration = $this->GetValidatedConfiguration($values);
            $current = [];
            foreach (array_unique(array_column($configuration['sources'], 'VariableID')) as $id) {
                // No gateway calls: previews must not mutate transport or instance status.
                $current[$id] = ['Value' => GetValue($id), 'Timestamp' => 0];
            }
            $model = RadarModel::Build($configuration['axes'], $configuration['sources'], $current, fn (string $text): string => $this->Translate($text));
        } catch (Throwable $exception) {
            return $svg . '<text x="24" y="100" font-size="12" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E(mb_strimwidth($exception->getMessage(), 0, 100, '…')) . '</text></svg>';
        }
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['border']);
        $angle = deg2rad($this->BarPreviewValue($values, 'StartAngle', 'integer', $ipsView));
        $direction = $this->BarPreviewValue($values, 'Clockwise', 'boolean', $ipsView) ? -1 : 1;
        $point = static function (int $index, float $fraction) use ($model, $angle, $direction): array
        {
            $a = $angle + $direction * 2 * M_PI * $index / count($model['axes']);
            return [360 + 100 * $fraction * cos($a), 185 - 100 * $fraction * sin($a)];
        };
        $points = static function (float $fraction) use ($model, $point): string
        {
            $result = [];
            foreach ($model['axes'] as $index => $_) {
                $result[] = implode(',', array_map(EChartsBarPreview::N(...), $point($index, $fraction)));
            }
            return implode(' ', $result);
        };
        $split = max(1, min(10, $this->BarPreviewValue($values, 'SplitNumber', 'integer', $ipsView)));
        for ($level = $split; $level > 0; --$level) {
            $fill = $this->BarPreviewValue($values, 'ShowSplitArea', 'boolean', $ipsView)
                ? ($level % 2 === 0 ? 'rgba(128,128,128,0.03)' : 'rgba(128,128,128,0.09)') : 'none';
            $geometry = $this->BarPreviewValue($values, 'Shape', 'string', $ipsView) === 'circle'
                ? '<circle cx="360" cy="185" r="' . EChartsBarPreview::N(100 * $level / $split) . '"'
                : '<polygon points="' . $points($level / $split) . '"';
            $svg .= $geometry . ' fill="' . $fill . '" stroke="' . EChartsBarPreview::E($gridColor) . '" stroke-opacity="0.4"/>';
            if ($this->BarPreviewValue($values, 'ShowAxisValues', 'boolean', $ipsView)) {
                [$x, $y] = $point(0, $level / $split);
                $svg .= '<text x="' . EChartsBarPreview::N($x + 4) . '" y="' . EChartsBarPreview::N($y) . '" font-size="10" fill="'
                    . EChartsBarPreview::E($axisColor) . '">' . EChartsBarPreview::N(100 * $level / $split) . '%</text>';
            }
        }
        $font = max(6, min(24, 12 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100));
        $language = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        foreach ($model['axes'] as $index => $axis) {
            [$x, $y] = $point($index, 1);
            $svg .= '<path d="M360 185 L' . EChartsBarPreview::N($x) . ' ' . EChartsBarPreview::N($y)
                . '" stroke="' . EChartsBarPreview::E($gridColor) . '" stroke-opacity="0.5"/>';
            if ($this->BarPreviewValue($values, 'ShowAxisNames', 'boolean', $ipsView)) {
                [$x, $y] = $point($index, 1 + max(0, min(40, $this->BarPreviewValue($values, 'AxisNameGap', 'integer', $ipsView))) / 100);
                $anchor = abs($x - 360) < 1 ? 'middle' : ($x < 360 ? 'end' : 'start');
                $range = EChartsBarPreview::FormatValue($axis['Minimum'], $axis['Decimals'], $axis['Unit'], $language)
                    . ' … ' . EChartsBarPreview::FormatValue($axis['Maximum'], $axis['Decimals'], $axis['Unit'], $language);
                $svg .= '<text x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                    . '" text-anchor="' . $anchor . '" font-size="' . EChartsBarPreview::N($font) . '" fill="' . EChartsBarPreview::E($axisColor) . '">'
                    . EChartsBarPreview::E(mb_strimwidth($axis['Label'], 0, 25, '…')) . '<tspan x="' . EChartsBarPreview::N($x)
                    . '" dy="1.25em">' . EChartsBarPreview::E(mb_strimwidth($range, 0, 30, '…')) . '</tspan></text>';
            }
        }
        foreach ($model['data'] as $index => $group) {
            $color = $palette['seriesColors'][$index % count($palette['seriesColors'])];
            $polygon = [];
            $symbols = '';
            foreach ($group['percentValues'] as $axis => $value) {
                [$x, $y] = $point($axis, $value / 100);
                $polygon[] = EChartsBarPreview::N($x) . ',' . EChartsBarPreview::N($y);
                $symbols .= '<circle cx="' . EChartsBarPreview::N($x) . '" cy="' . EChartsBarPreview::N($y) . '" r="'
                    . max(0, min(16, $this->BarPreviewValue($values, 'SymbolSize', 'integer', $ipsView))) / 2 . '" fill="' . EChartsBarPreview::E($color) . '"/>';
            }
            $svg .= '<polygon data-group="' . EChartsBarPreview::E($group['name']) . '" points="' . implode(' ', $polygon) . '" fill="'
                . EChartsBarPreview::E($color) . '" fill-opacity="' . max(0, min(100, $this->BarPreviewValue($values, 'AreaOpacityPercent', 'integer', $ipsView))) / 100
                . '" stroke="' . EChartsBarPreview::E($color) . '" stroke-width="' . max(0, min(8, $this->BarPreviewValue($values, 'LineWidth', 'integer', $ipsView)))
                . '" stroke-opacity="' . max(0, min(100, $this->BarPreviewValue($values, 'LineOpacityPercent', 'integer', $ipsView))) / 100 . '"/>' . $symbols;
            if ($this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView)) {
                $svg .= '<text x="' . (24 + ($index % 4) * 170) . '" y="' . (320 + (int) ($index / 4) * 16)
                    . '" font-size="11" fill="' . EChartsBarPreview::E($color) . '">' . EChartsBarPreview::E(mb_strimwidth($group['name'], 0, 24, '…')) . '</text>';
            }
        }
        if ($model['clippedCount'] > 0) {
            $svg .= '<text x="360" y="354" text-anchor="middle" font-size="10" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E($this->Translate('Some values exceed their axis range and are visually limited.')) . '</text>';
        }
        return $svg . '</svg>';
    }
}
