<?php

declare(strict_types=1);

namespace SymconECharts;

use Throwable;

/** Read-only current-value SVG preview of the complete (possibly unsaved) flow configuration. */
trait SankeyPreview
{
    private function SankeyPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            false,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Sankey preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg font-family="Arial, sans-serif" color="' . EChartsBarPreview::E($palette['text']) . '" xmlns', $svg);
        try {
            $configuration = $this->GetValidatedConfiguration($values);
            $current = [];
            // Form previews must not call ReadCurrentSource(): transport failures there change instance status.
            foreach (array_unique(array_column($configuration['sources'], 'VariableID')) as $id) {
                $current[$id] = ['Value' => GetValue($id), 'Timestamp' => 0];
            }
            $model = SankeyModel::Build(
                $configuration['nodes'],
                $configuration['sources'],
                $current,
                fn (string $text): string => $this->Translate($text)
            );
        } catch (Throwable $exception) {
            return $this->SankeyPreviewStatus($svg, $exception->getMessage());
        }
        if ($model['links'] === []) {
            return $this->SankeyPreviewStatus($svg, $this->Translate('No positive flows'));
        }
        // Normalize geometry only: finite extreme values must not produce an infinite SVG scale.
        $maximumValue = max(array_column($model['data'], 'value'));
        $minimumValue = min(array_column($model['data'], 'value'));
        $depths = array_fill_keys(array_column($model['data'], 'name'), 0);
        for ($pass = 0; $pass < count($depths); ++$pass) {
            foreach ($model['links'] as $link) {
                $depths[$link['target']] = max($depths[$link['target']], $depths[$link['source']] + 1);
            }
        }
        $maximumDepth = max(1, max($depths));
        $alignment = $this->BarPreviewValue($values, 'NodeAlign', 'string', $ipsView);
        if ($alignment === 'justify') {
            $outgoingKeys = array_column($model['links'], 'source');
            foreach ($depths as $key => &$depth) {
                if (!in_array($key, $outgoingKeys, true)) {
                    $depth = $maximumDepth;
                }
            }
            unset($depth);
        } elseif ($alignment === 'right') {
            $remaining = array_fill_keys(array_keys($depths), 0);
            for ($pass = 0; $pass < count($depths); ++$pass) {
                foreach ($model['links'] as $link) {
                    $remaining[$link['source']] = max($remaining[$link['source']], $remaining[$link['target']] + 1);
                }
            }
            foreach ($depths as $key => &$depth) {
                $depth = $maximumDepth - $remaining[$key];
            }
            unset($depth);
        }
        $vertical = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'vertical';
        $mainSize = $vertical ? 220 : 580;
        $crossSize = $vertical ? 580 : 220;
        $nodeWidth = max(4, min(40, $this->BarPreviewValue($values, 'NodeWidth', 'integer', $ipsView)));
        $gap = max(0, min(40, $this->BarPreviewValue($values, 'NodeGap', 'integer', $ipsView)));
        $layers = [];
        foreach ($model['data'] as $node) {
            $layers[$depths[$node['name']]][] = $node;
        }
        $gap = min($gap, $crossSize / max(1, count($model['data']) - 1) * 0.5);
        $scale = INF;
        foreach ($layers as $layer) {
            $total = array_sum(array_map(static fn (array $node): float => $node['value'] / $maximumValue, $layer));
            if ($total > 0) {
                $scale = min($scale, max(1, $crossSize - (count($layer) - 1) * $gap) / $total);
            }
        }
        $positions = [];
        $colors = [];
        try {
            $property = $ipsView && !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign'))
                ? 'IPSViewNodeColors' : 'NodeColors';
            $nodeColors = $this->ParseNodeColors($values[$property] ?? $this->ReadPropertyString($property));
        } catch (Throwable) {
            $nodeColors = [];
        }
        foreach ($layers as $depth => $layer) {
            $layerExtent = array_sum(array_map(static fn (array $node): float => $node['value'] / $maximumValue * $scale, $layer));
            $cross = ($crossSize - $layerExtent - (count($layer) - 1) * $gap) / 2;
            foreach ($layer as $node) {
                $key = $node['name'];
                $extent = $node['value'] / $maximumValue * $scale;
                $positions[$key] = [$depth * ($mainSize - $nodeWidth) / $maximumDepth, $cross, $extent];
                $colors[$key] = $nodeColors[$key] ?? EChartsBarPreview::Color(
                    $this->BarPreviewValue($values, 'NodeColor', 'integer', $ipsView),
                    $this->SankeyPreviewNodeColor($node['value'], $minimumValue, $maximumValue, $palette['seriesColors'])
                );
                $cross += $extent + $gap;
            }
        }
        $offsetIn = $offsetOut = array_fill_keys(array_keys($depths), 0.0);
        $mode = $this->BarPreviewValue($values, 'EdgeColorMode', 'string', $ipsView);
        $opacity = max(0, min(100, $this->BarPreviewValue($values, 'EdgeOpacityPercent', 'integer', $ipsView))) / 100;
        $curve = max(0, min(100, $this->BarPreviewValue($values, 'EdgeCurvenessPercent', 'integer', $ipsView))) / 100;
        $svg .= '<g transform="translate(70 85)">';
        $edgeLabels = '';
        $unit = trim((string) ($values['Unit'] ?? $this->ReadPropertyString('Unit')));
        foreach ($model['links'] as $index => $link) {
            $source = $link['source'];
            $target = $link['target'];
            [$main1, $cross1] = $positions[$source];
            [$main2, $cross2] = $positions[$target];
            $width = $link['value'] / $maximumValue * $scale;
            $main1 += $nodeWidth;
            $cross1 += $offsetOut[$source] + $width / 2;
            $cross2 += $offsetIn[$target] + $width / 2;
            $offsetOut[$source] += $width;
            $offsetIn[$target] += $width;
            $delta = ($main2 - $main1) * $curve;
            $coordinates = $vertical ? [$cross1, $main1, $cross1, $main1 + $delta, $cross2, $main2 - $delta, $cross2, $main2]
                : [$main1, $cross1, $main1 + $delta, $cross1, $main2 - $delta, $cross2, $main2, $cross2];
            // Two Bezier boundaries form a ribbon. A thick center stroke overshoots tight bends.
            $upper = $lower = $coordinates;
            foreach ($vertical ? [0, 2, 4, 6] : [1, 3, 5, 7] as $axis) {
                $upper[$axis] -= $width / 2;
                $lower[$axis] += $width / 2;
            }
            $path = 'M ' . EChartsBarPreview::N($upper[0]) . ' ' . EChartsBarPreview::N($upper[1]) . ' C '
                . implode(' ', array_map(EChartsBarPreview::N(...), array_slice($upper, 2))) . ' L '
                . EChartsBarPreview::N($lower[6]) . ' ' . EChartsBarPreview::N($lower[7]) . ' C '
                . implode(' ', array_map(EChartsBarPreview::N(...), [$lower[4], $lower[5], $lower[2], $lower[3], $lower[0], $lower[1]])) . ' Z';
            $color = match ($mode) {
                'source' => $colors[$source], 'target' => $colors[$target],
                'fixed'  => EChartsBarPreview::Color($this->BarPreviewValue($values, 'EdgeColor', 'integer', $ipsView), $palette['muted']),
                default  => 'url(#flow-' . $index . ')'
            };
            $svg .= '<defs><linearGradient id="flow-' . $index . '" x2="' . ($vertical ? '0%' : '100%') . '" y2="' . ($vertical ? '100%' : '0%')
                . '"><stop stop-color="' . EChartsBarPreview::E($colors[$source]) . '"/><stop offset="1" stop-color="'
                . EChartsBarPreview::E($colors[$target]) . '"/></linearGradient></defs>';
            $svg .= '<path data-source="' . EChartsBarPreview::E($source) . '" data-target="' . EChartsBarPreview::E($target)
                . '" d="' . $path . '" fill="' . EChartsBarPreview::E($color) . '" fill-opacity="' . EChartsBarPreview::N($opacity) . '"/>';
            if ($this->BarPreviewValue($values, 'ShowEdgeValues', 'boolean', $ipsView)) {
                $edgeColor = EChartsBarPreview::Color(
                    $this->BarPreviewValue($values, 'EdgeLabelColor', 'integer', $ipsView),
                    EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text'])
                );
                $edgeSize = max(6, min(24, 12 * $this->BarPreviewValue($values, 'EdgeLabelFontSizePercent', 'integer', $ipsView) / 100));
                $edgeLabels .= '<text x="' . EChartsBarPreview::N(($coordinates[0] + $coordinates[6]) / 2)
                    . '" y="' . EChartsBarPreview::N(($coordinates[1] + $coordinates[7]) / 2)
                    . '" text-anchor="middle" font-size="' . EChartsBarPreview::N($edgeSize) . '" fill="'
                    . EChartsBarPreview::E($edgeColor) . '">' . EChartsBarPreview::E($this->SankeyPreviewFormatValue($link['value'], $unit)) . '</text>';
            }
        }
        $svg .= $edgeLabels;
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $fontSize = max(6, min(22, (int) round(12 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        $borderColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'NodeBorderColor', 'integer', $ipsView), $palette['border']);
        $borderWidth = max(0, min(8, $this->BarPreviewValue($values, 'NodeBorderWidth', 'integer', $ipsView)));
        $radius = max(0, min(20, $this->BarPreviewValue($values, 'NodeBorderRadius', 'integer', $ipsView)));
        $nodeOpacity = max(0, min(100, $this->BarPreviewValue($values, 'NodeOpacityPercent', 'integer', $ipsView))) / 100;
        $shadow = max(0, min(20, $this->BarPreviewValue($values, 'NodeShadowBlur', 'integer', $ipsView)));
        $shadowColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'NodeShadowColor', 'integer', $ipsView), '#000000');
        $svg .= '<defs><filter id="node-shadow" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="0" dy="0" stdDeviation="'
            . EChartsBarPreview::N($shadow / 2) . '" flood-color="' . EChartsBarPreview::E($shadowColor) . '" flood-opacity="0.35"/></filter></defs>';
        $occupied = [];
        foreach ($model['data'] as $node) {
            [$main, $cross, $extent] = $positions[$node['name']];
            [$x, $y, $width, $height] = $vertical ? [$cross, $main, $extent, $nodeWidth] : [$main, $cross, $nodeWidth, $extent];
            $svg .= '<rect data-node="' . EChartsBarPreview::E($node['name']) . '" x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y) . '" width="'
                . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height) . '" fill="'
                . EChartsBarPreview::E($colors[$node['name']]) . '" rx="' . $radius . '" opacity="' . EChartsBarPreview::N($nodeOpacity)
                . '" stroke="' . EChartsBarPreview::E($borderColor) . '" stroke-width="' . $borderWidth . '"'
                . ($shadow > 0 ? ' filter="url(#node-shadow)"' : '') . '><title>' . EChartsBarPreview::E($node['label'] . ': '
                    . $this->SankeyPreviewFormatValue($node['value'], $unit)) . '</title></rect>';
            if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                $label = $node['label'];
                if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                    $label .= "\n" . $this->SankeyPreviewFormatValue($node['value'], $unit);
                }
                $position = $this->BarPreviewValue($values, 'LabelPosition', 'string', $ipsView);
                if ($position === 'auto') {
                    $end = !in_array($node['name'], array_column($model['links'], 'source'), true);
                    $position = $vertical ? ($end ? 'top' : 'bottom') : ($end ? 'left' : 'right');
                }
                $placement = EChartsLabelDesign::PreviewRect(
                    $x,
                    $y,
                    $width,
                    $height,
                    $position,
                    $this->BarPreviewValue($values, 'LabelDistance', 'integer', $ipsView)
                );
                $maxWidth = max(0, min(400, $this->BarPreviewValue($values, 'LabelMaxWidth', 'integer', $ipsView))) ?: 150;
                if ($vertical || $position === 'inside') {
                    $maxWidth = min($maxWidth, max(0, $width - 4));
                }
                if ($maxWidth < $fontSize) {
                    continue;
                }
                $lines = $this->SankeyPreviewLabelLines(
                    $label,
                    max(1, (int) ($maxWidth / ($fontSize * 0.6))),
                    $this->BarPreviewValue($values, 'LabelOverflow', 'string', $ipsView)
                );
                if (!$vertical || $position === 'inside') {
                    $lines = array_slice($lines, 0, max(0, (int) (($height - 2) / ($fontSize * 1.2))));
                }
                if ($lines === []) {
                    continue;
                }
                if (in_array($position, ['left', 'right', 'inside'], true)) {
                    $placement['y'] -= (count($lines) - 1) * $fontSize * 1.2 / 2;
                } elseif ($position === 'top') {
                    $placement['y'] -= (count($lines) - 1) * $fontSize * 1.2;
                }
                $textWidth = min($maxWidth, max(array_map('mb_strlen', $lines)) * $fontSize * 0.6);
                $left = $placement['x'] - match ($placement['anchor']) {
                    'end' => $textWidth, 'middle' => $textWidth / 2, default => 0
                };
                $box = [$left, $placement['y'] - $fontSize, $left + $textWidth, $placement['y'] + (count($lines) - 1) * $fontSize * 1.2];
                if ($this->BarPreviewValue($values, 'HideOverlappingLabels', 'boolean', $ipsView)) {
                    foreach ($occupied as $other) {
                        if ($box[0] < $other[2] && $box[2] > $other[0] && $box[1] < $other[3] && $box[3] > $other[1]) {
                            continue 2;
                        }
                    }
                }
                $occupied[] = $box;
                $svg .= '<text x="' . EChartsBarPreview::N($placement['x']) . '" y="' . EChartsBarPreview::N($placement['y'])
                    . '" text-anchor="' . $placement['anchor'] . '" font-size="' . $fontSize
                    . '" font-weight="' . ($this->BarPreviewValue($values, 'LabelBold', 'boolean', $ipsView) ? 'bold' : 'normal')
                    . '" fill="' . EChartsBarPreview::E($labelColor) . '">';
                foreach ($lines as $index => $line) {
                    $svg .= '<tspan x="' . EChartsBarPreview::N($placement['x']) . '" dy="' . ($index === 0 ? '0' : '1.2em') . '">'
                        . EChartsBarPreview::E($line) . '</tspan>';
                }
                $svg .= '</text>';
            }
        }
        return $svg . '</g></svg>';
    }

    private function SankeyPreviewStatus(string $svg, string $message): string
    {
        $svg .= '<text x="360" y="170" text-anchor="middle" font-size="12" fill="currentColor">';
        foreach (array_slice($this->SankeyPreviewLabelLines($message, 85, 'break'), 0, 5) as $index => $line) {
            $svg .= '<tspan x="360" dy="' . ($index === 0 ? '0' : '1.3em') . '">' . EChartsBarPreview::E($line) . '</tspan>';
        }
        return $svg . '</text></svg>';
    }

    private function SankeyPreviewFormatValue(float $value, string $unit): string
    {
        $language = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        $number = rtrim(rtrim(EChartsBarPreview::FormatValue($value, 3, '', $language), '0'), '.,');
        return $number . ($unit !== '' ? ' ' . $unit : '');
    }

    /** ECharts Sankey maps node values linearly across the theme palette, not by node index. */
    private function SankeyPreviewNodeColor(float $value, float $minimum, float $maximum, array $colors): string
    {
        $normalized = $maximum > $minimum ? ($value - $minimum) / ($maximum - $minimum) : 0.5;
        $position = max(0, min(1, $normalized)) * (count($colors) - 1);
        $index = (int) floor($position);
        $fraction = $position - $index;
        $start = hexdec(ltrim($colors[$index], '#'));
        $end = hexdec(ltrim($colors[min($index + 1, count($colors) - 1)], '#'));
        $color = 0;
        foreach ([16, 8, 0] as $shift) {
            $first = ($start >> $shift) & 0xFF;
            $last = ($end >> $shift) & 0xFF;
            $color |= (int) round($first + ($last - $first) * $fraction) << $shift;
        }
        return EChartsAsset::ColorToHex($color);
    }

    /** Schematic wrapping; the renderer measures the actual browser font. */
    private function SankeyPreviewLabelLines(string $text, int $width, string $overflow): array
    {
        $lines = [];
        foreach (explode("\n", $text) as $paragraph) {
            if ($overflow === 'truncate') {
                $lines[] = mb_strimwidth($paragraph, 0, $width, '…');
                continue;
            }
            while (mb_strlen($paragraph) > $width) {
                $length = $width;
                if ($overflow === 'break') {
                    $space = mb_strrpos(mb_substr($paragraph, 0, $width + 1), ' ');
                    $length = $space !== false && $space > 0 ? $space : $width;
                }
                $lines[] = mb_substr($paragraph, 0, $length);
                $paragraph = ltrim(mb_substr($paragraph, $length));
            }
            $lines[] = $paragraph;
        }
        return $lines;
    }
}
