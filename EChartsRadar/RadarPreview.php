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
        $svg = str_replace('<text x="24"', '<text font-weight="' . ($this->BarPreviewValue($values, 'TitleBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '" x="24"', $svg);
        try {
            $configuration = $this->GetValidatedConfiguration($values);
            $current = [];
            foreach (array_unique(array_column($configuration['sources'], 'VariableID')) as $id) {
                // No gateway calls: previews must not mutate transport or instance status.
                $current[$id] = ['Value' => GetValue($id), 'Timestamp' => 0];
            }
            $model = RadarModel::Build($configuration['axes'], $configuration['sources'], $current, fn (string $text): string => $this->Translate($text), $configuration['visibility']);
            $property = $ipsView && !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')) ? 'IPSViewGroupColors' : 'GroupColors';
            $groupColors = $this->ParseGroupColors($values[$property] ?? $this->ReadPropertyString($property));
        } catch (Throwable $exception) {
            return $svg . '<text x="24" y="100" font-size="12" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E(mb_strimwidth($exception->getMessage(), 0, 100, '…')) . '</text></svg>';
        }
        $groupCount = count($model['data']);
        $selection = (bool) ($values['EnableGroupSelection'] ?? $this->ReadPropertyBoolean('EnableGroupSelection'));
        $groups = $selection ? array_filter($model['data'], static fn (array $group): bool => $group['initiallyVisible']) : $model['data'];
        $selectedCount = count($groups);
        $groups = array_slice($groups, 0, 8, true);
        $projected = $selection || $groupCount > 8;
        $svg = str_replace('<svg ', '<svg data-group-count="' . $groupCount . '" data-selected-count="' . $selectedCount
            . '" data-rendered-count="' . count($groups) . '" ', $svg);
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['border']);
        $legendTop = $this->BarPreviewValue($values, 'LegendPosition', 'string', $ipsView) === 'top';
        $centerY = $legendTop && $this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView) ? 205 : 185;
        $radius = $projected ? 90 : 100;
        if ($projected) {
            $centerY = $legendTop && $this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView) ? 215 : 195;
            $svg .= '<text x="24" y="' . ($legendTop && $this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView) ? 96 : 60)
                . '" font-size="11" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E(sprintf($this->Translate('Showing %d of %d selected groups (%d configured).'), count($groups), $selectedCount, $groupCount)) . '</text>';
        }
        $n = EChartsBarPreview::N(...);
        $dash = static fn (string $type): string => match ($type) {
            'dashed' => ' stroke-dasharray="6 4"', 'dotted' => ' stroke-dasharray="2 3"', default => ''
        };
        $angle = deg2rad($this->BarPreviewValue($values, 'StartAngle', 'integer', $ipsView));
        $direction = $this->BarPreviewValue($values, 'Clockwise', 'boolean', $ipsView) ? -1 : 1;
        $point = static function (int $index, float $fraction) use ($model, $angle, $direction, $centerY, $radius): array
        {
            $a = $angle + $direction * 2 * M_PI * $index / count($model['axes']);
            return [360 + $radius * $fraction * cos($a), $centerY - $radius * $fraction * sin($a)];
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
                ? EChartsBarPreview::Color(
                    $this->BarPreviewValue($values, 'SplitAreaColor' . ($level % 2 === 0 ? '2' : '1'), 'integer', $ipsView),
                    $level % 2 === 0 ? 'rgba(128,128,128,0.09)' : 'rgba(128,128,128,0.03)'
                ) : 'none';
            $geometry = $this->BarPreviewValue($values, 'Shape', 'string', $ipsView) === 'circle'
                ? '<circle cx="360" cy="' . $centerY . '" r="' . EChartsBarPreview::N($radius * $level / $split) . '"'
                : '<polygon points="' . $points($level / $split) . '"';
            $svg .= $geometry . ' fill="' . EChartsBarPreview::E($fill) . '" fill-opacity="'
                . $n(max(0, min(100, $this->BarPreviewValue($values, 'SplitAreaOpacityPercent', 'integer', $ipsView))) / 100)
                . '" stroke="' . ($this->BarPreviewValue($values, 'ShowSplitLines', 'boolean', $ipsView) ? EChartsBarPreview::E($gridColor) : 'none')
                . '" stroke-width="' . max(0, min(8, $this->BarPreviewValue($values, 'SplitLineWidth', 'integer', $ipsView)))
                . '" stroke-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'SplitLineOpacityPercent', 'integer', $ipsView))) / 100)
                . '"' . $dash($this->BarPreviewValue($values, 'SplitLineType', 'string', $ipsView)) . '/>';
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
            if ($this->BarPreviewValue($values, 'ShowAxisLines', 'boolean', $ipsView)) {
                $svg .= '<path d="M360 ' . $centerY . ' L' . $n($x) . ' ' . $n($y)
                    . '" stroke="' . EChartsBarPreview::E($gridColor) . '" stroke-width="' . max(0, min(8, $this->BarPreviewValue($values, 'AxisLineWidth', 'integer', $ipsView)))
                    . '" stroke-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'AxisLineOpacityPercent', 'integer', $ipsView))) / 100) . '"/>';
            }
            if ($this->BarPreviewValue($values, 'ShowAxisNames', 'boolean', $ipsView)) {
                [$x, $y] = $point($index, 1 + max(0, min(40, $this->BarPreviewValue($values, 'AxisNameGap', 'integer', $ipsView))) / 100);
                $anchor = abs($x - 360) < 1 ? 'middle' : ($x < 360 ? 'end' : 'start');
                $range = EChartsBarPreview::FormatValue($axis['Minimum'], $axis['Decimals'], $axis['Unit'], $language)
                    . ' … ' . EChartsBarPreview::FormatValue($axis['Maximum'], $axis['Decimals'], $axis['Unit'], $language);
                $svg .= '<text x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                    . '" text-anchor="' . $anchor . '" font-size="' . EChartsBarPreview::N($font) . '" fill="' . EChartsBarPreview::E($axisColor)
                    . '" font-weight="' . ($this->BarPreviewValue($values, 'AxisLabelBold', 'boolean', $ipsView) ? 'bold' : 'normal') . '">'
                    . EChartsBarPreview::E(mb_strimwidth($axis['Label'], 0, 25, '…'))
                    . ($this->BarPreviewValue($values, 'ShowAxisRange', 'boolean', $ipsView) ? '<tspan x="' . $n($x)
                        . '" dy="1.25em">' . EChartsBarPreview::E(mb_strimwidth($range, 0, 30, '…')) . '</tspan>' : '') . '</text>';
            }
        }
        $occupied = [];
        $renderIndex = 0;
        foreach ($groups as $index => $group) {
            $color = $groupColors[$group['name']] ?? $palette['seriesColors'][$index % count($palette['seriesColors'])];
            $polygon = [];
            $symbols = '';
            foreach ($group['percentValues'] as $axis => $value) {
                [$x, $y] = $point($axis, $value / 100);
                $polygon[] = EChartsBarPreview::N($x) . ',' . EChartsBarPreview::N($y);
                $symbol = $this->BarPreviewValue($values, 'Symbol', 'string', $ipsView);
                $radius = max(0, min(16, $this->BarPreviewValue($values, 'SymbolSize', 'integer', $ipsView))) / 2;
                $symbols .= EChartsPreviewSymbol::Symbol($symbol, $x, $y, $radius, $color, [
                    'borderWidth'    => $this->BarPreviewValue($values, 'SymbolBorderWidth', 'integer', $ipsView),
                    'borderColor'    => EChartsBarPreview::Color($this->BarPreviewValue($values, 'SymbolBorderColor', 'integer', $ipsView), $color),
                    'rotate'         => $this->BarPreviewValue($values, 'SymbolRotate', 'integer', $ipsView),
                    'opacityPercent' => $this->BarPreviewValue($values, 'SymbolOpacityPercent', 'integer', $ipsView)
                ]);
                if ($symbol !== 'none' && $radius > 0 && $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                    $placement = EChartsLabelDesign::PreviewRect(
                        $x - $radius,
                        $y - $radius,
                        $radius * 2,
                        $radius * 2,
                        $this->BarPreviewValue($values, 'ValueLabelPosition', 'string', $ipsView),
                        $this->BarPreviewValue($values, 'ValueLabelDistance', 'integer', $ipsView)
                    );
                    $text = EChartsBarPreview::FormatValue($group['values'][$axis], $model['axes'][$axis]['Decimals'], $model['axes'][$axis]['Unit'], $language);
                    $valueSize = 12 * max(50, min(200, $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView))) / 100;
                    $width = mb_strlen($text) * $valueSize * 0.6;
                    $left = $placement['x'] - match ($placement['anchor']) {
                        'end' => $width, 'middle' => $width / 2, default => 0
                    };
                    $box = [$left, $placement['y'] - $valueSize, $left + $width, $placement['y'] + $valueSize * 0.2];
                    if ($this->BarPreviewValue($values, 'HideOverlappingValueLabels', 'boolean', $ipsView)) {
                        foreach ($occupied as $other) {
                            if ($box[0] < $other[2] && $box[2] > $other[0] && $box[1] < $other[3] && $box[3] > $other[1]) {
                                continue 2;
                            }
                        }
                    }
                    $occupied[] = $box;
                    $symbols .= '<text data-value="' . $n($group['values'][$axis]) . '" x="' . $n($placement['x']) . '" y="' . $n($placement['y'])
                        . '" text-anchor="' . $placement['anchor'] . '" font-size="' . $n(12 * max(50, min(200, $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView))) / 100)
                        . '" font-weight="' . ($this->BarPreviewValue($values, 'ValueLabelBold', 'boolean', $ipsView) ? 'bold' : 'normal')
                        . '" fill="' . EChartsBarPreview::E(EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text'])) . '">'
                        . EChartsBarPreview::E($text) . '</text>';
                }
            }
            $shadow = max(0, min(20, $this->BarPreviewValue($values, 'AreaShadowBlur', 'integer', $ipsView)));
            $svg .= '<defs><filter id="area-shadow-' . $index . '" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="0" dy="0" stdDeviation="'
                . $n($shadow / 2) . '" flood-color="' . EChartsBarPreview::E(EChartsBarPreview::Color($this->BarPreviewValue($values, 'AreaShadowColor', 'integer', $ipsView), '#000000'))
                . '" flood-opacity="' . ($this->BarPreviewValue($values, 'AreaShadowColor', 'integer', $ipsView) < 0 ? '0.35' : '1') . '"/></filter></defs>';
            $svg .= '<polygon data-group="' . EChartsBarPreview::E($group['name']) . '" points="' . implode(' ', $polygon) . '" fill="'
                . EChartsBarPreview::E($color) . '" fill-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'AreaOpacityPercent', 'integer', $ipsView))) / 100)
                . '"' . ($shadow > 0 ? ' filter="url(#area-shadow-' . $index . ')"' : '') . '/>';
            $svg .= '<polygon points="' . implode(' ', $polygon) . '" fill="none" stroke="' . EChartsBarPreview::E($color)
                . '" stroke-width="' . max(0, min(8, $this->BarPreviewValue($values, 'LineWidth', 'integer', $ipsView)))
                . '" stroke-opacity="' . $n(max(0, min(100, $this->BarPreviewValue($values, 'LineOpacityPercent', 'integer', $ipsView))) / 100)
                . '"' . $dash($this->BarPreviewValue($values, 'LineType', 'string', $ipsView)) . '/>' . $symbols;
            if ($this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView)) {
                $legendSize = 12 * max(50, min(200, $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView))) / 100;
                $svg .= '<text x="' . (24 + ($renderIndex % 4) * 170) . '" y="' . (($legendTop ? 60 : 320) + (int) ($renderIndex / 4) * ($legendSize + 4))
                    . '" font-size="' . $n($legendSize) . '" fill="' . EChartsBarPreview::E(EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $axisColor)) . '">'
                    . '<tspan fill="' . EChartsBarPreview::E($color) . '">■ </tspan>' . EChartsBarPreview::E(mb_strimwidth($group['name'], 0, 24, '…')) . '</text>';
            }
            ++$renderIndex;
        }
        if ($selectedCount === 0) {
            $svg .= '<text x="360" y="195" text-anchor="middle" font-size="12" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E($this->Translate('No groups selected')) . '</text>';
        }
        if ($model['clippedCount'] > 0) {
            $svg .= '<text x="360" y="354" text-anchor="middle" font-size="10" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E($this->Translate('Some values exceed their axis range and are visually limited.')) . '</text>';
        }
        return $svg . '</svg>';
    }
}
