<?php

declare(strict_types=1);

namespace SymconECharts;

use Throwable;

/** Read-only SVG preview with explicit example weights and unsaved node labels. */
trait SankeyPreview
{
    private function SankeyPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Sankey preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        try {
            $nodes = $this->DecodeRows($values['Nodes'] ?? $this->ReadPropertyString('Nodes'));
            $sources = $this->DecodeRows($values['Sources'] ?? $this->ReadPropertyString('Sources'));
            if (count($nodes) > 12 || count($sources) > 20) {
                throw new \InvalidArgumentException('Preview budget exceeded.');
            }
            $configuration = SankeyModel::Validate($nodes, $sources);
        } catch (Throwable) {
            $configuration = ['nodes' => [
                ['Key' => 'a', 'Label' => $this->Translate('Source A')],
                ['Key' => 'b', 'Label' => $this->Translate('Source B')],
                ['Key' => 'c', 'Label' => $this->Translate('Distribution')],
                ['Key' => 'd', 'Label' => $this->Translate('Consumer A')],
                ['Key' => 'e', 'Label' => $this->Translate('Consumer B')]
            ], 'sources' => [
                ['Source' => 'a', 'Target' => 'c', 'VariableID' => 1],
                ['Source' => 'b', 'Target' => 'c', 'VariableID' => 2],
                ['Source' => 'c', 'Target' => 'd', 'VariableID' => 3],
                ['Source' => 'c', 'Target' => 'e', 'VariableID' => 4]
            ]];
        }
        $sampleValues = [];
        foreach ($configuration['sources'] as $index => $source) {
            $sampleValues[$source['VariableID']] = ['Value' => (float) (30 + $index * 7), 'Timestamp' => 0];
        }
        $model = SankeyModel::Build($configuration['nodes'], $configuration['sources'], $sampleValues);
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
        $gap = min($gap, $crossSize / max(1, max(array_map('count', $layers)) - 1) * 0.5);
        $scale = INF;
        foreach ($layers as $layer) {
            $scale = min($scale, max(1, $crossSize - (count($layer) - 1) * $gap) / array_sum(array_column($layer, 'value')));
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
            $cross = ($crossSize - array_sum(array_column($layer, 'value')) * $scale - (count($layer) - 1) * $gap) / 2;
            foreach ($layer as $node) {
                $key = $node['name'];
                $positions[$key] = [$depth * ($mainSize - $nodeWidth) / $maximumDepth, $cross, $node['value'] * $scale];
                $colors[$key] = $nodeColors[$key] ?? EChartsBarPreview::Color(
                    $this->BarPreviewValue($values, 'NodeColor', 'integer', $ipsView),
                    $palette['seriesColors'][count($colors) % count($palette['seriesColors'])]
                );
                $cross += $node['value'] * $scale + $gap;
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
            $width = $link['value'] * $scale;
            $main1 += $nodeWidth;
            $cross1 += $offsetOut[$source] + $width / 2;
            $cross2 += $offsetIn[$target] + $width / 2;
            $offsetOut[$source] += $width;
            $offsetIn[$target] += $width;
            $delta = ($main2 - $main1) * $curve;
            $coordinates = $vertical ? [$cross1, $main1, $cross1, $main1 + $delta, $cross2, $main2 - $delta, $cross2, $main2]
                : [$main1, $cross1, $main1 + $delta, $cross1, $main2 - $delta, $cross2, $main2, $cross2];
            $path = 'M ' . EChartsBarPreview::N($coordinates[0]) . ' ' . EChartsBarPreview::N($coordinates[1]) . ' C '
                . implode(' ', array_map(static fn (float $number): string => EChartsBarPreview::N($number), array_slice($coordinates, 2)));
            $color = match ($mode) {
                'source' => $colors[$source], 'target' => $colors[$target],
                'fixed'  => EChartsBarPreview::Color($this->BarPreviewValue($values, 'EdgeColor', 'integer', $ipsView), $palette['muted']),
                default  => 'url(#flow-' . $index . ')'
            };
            $svg .= '<defs><linearGradient id="flow-' . $index . '" x2="' . ($vertical ? '0%' : '100%') . '" y2="' . ($vertical ? '100%' : '0%')
                . '"><stop stop-color="' . EChartsBarPreview::E($colors[$source]) . '"/><stop offset="1" stop-color="'
                . EChartsBarPreview::E($colors[$target]) . '"/></linearGradient></defs>';
            $svg .= '<path d="' . $path . '" fill="none" stroke="' . EChartsBarPreview::E($color) . '" stroke-width="'
                . EChartsBarPreview::N($width) . '" stroke-opacity="' . EChartsBarPreview::N($opacity) . '"/>';
            if ($this->BarPreviewValue($values, 'ShowEdgeValues', 'boolean', $ipsView)) {
                $edgeColor = EChartsBarPreview::Color(
                    $this->BarPreviewValue($values, 'EdgeLabelColor', 'integer', $ipsView),
                    EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text'])
                );
                $edgeSize = max(6, min(24, 12 * $this->BarPreviewValue($values, 'EdgeLabelFontSizePercent', 'integer', $ipsView) / 100));
                $edgeLabels .= '<text x="' . EChartsBarPreview::N(($coordinates[0] + $coordinates[6]) / 2)
                    . '" y="' . EChartsBarPreview::N(($coordinates[1] + $coordinates[7]) / 2)
                    . '" text-anchor="middle" font-size="' . EChartsBarPreview::N($edgeSize) . '" fill="'
                    . EChartsBarPreview::E($edgeColor) . '">' . EChartsBarPreview::E((string) (int) $link['value'] . ($unit !== '' ? ' ' . $unit : '')) . '</text>';
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
            $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y) . '" width="'
                . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height) . '" fill="'
                . EChartsBarPreview::E($colors[$node['name']]) . '" rx="' . $radius . '" opacity="' . EChartsBarPreview::N($nodeOpacity)
                . '" stroke="' . EChartsBarPreview::E($borderColor) . '" stroke-width="' . $borderWidth . '"'
                . ($shadow > 0 ? ' filter="url(#node-shadow)"' : '') . '/>';
            if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                $label = $node['label'];
                if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                    $label .= "\n" . (string) (int) $node['value'] . ($unit !== '' ? ' ' . $unit : '');
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
                $lines = $this->SankeyPreviewLabelLines(
                    $label,
                    max(1, (int) ($maxWidth / ($fontSize * 0.6))),
                    $this->BarPreviewValue($values, 'LabelOverflow', 'string', $ipsView)
                );
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
