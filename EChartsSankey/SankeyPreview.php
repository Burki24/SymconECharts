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
        foreach ($layers as $depth => $layer) {
            $cross = ($crossSize - array_sum(array_column($layer, 'value')) * $scale - (count($layer) - 1) * $gap) / 2;
            foreach ($layer as $node) {
                $key = $node['name'];
                $positions[$key] = [$depth * ($mainSize - $nodeWidth) / $maximumDepth, $cross, $node['value'] * $scale];
                $colors[$key] = EChartsBarPreview::Color(
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
        }
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $fontSize = max(6, min(22, (int) round(12 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        foreach ($model['data'] as $node) {
            [$main, $cross, $extent] = $positions[$node['name']];
            [$x, $y, $width, $height] = $vertical ? [$cross, $main, $extent, $nodeWidth] : [$main, $cross, $nodeWidth, $extent];
            $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y) . '" width="'
                . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height) . '" fill="'
                . EChartsBarPreview::E($colors[$node['name']]) . '"/>';
            if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                $label = mb_strimwidth($node['label'], 0, 18, '…');
                if ($this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
                    $label .= ' ' . (string) (int) $node['value'];
                }
                $end = $depths[$node['name']] === $maximumDepth;
                $labelX = $vertical ? $x + $width / 2 : ($end ? $x - 4 : $x + $width + 4);
                $labelY = $vertical ? ($end ? $y - 6 : $y + $height + $fontSize) : $y + $height / 2 + 4;
                $svg .= '<text x="' . EChartsBarPreview::N($labelX) . '" y="' . EChartsBarPreview::N($labelY)
                    . '" text-anchor="' . ($vertical ? 'middle' : ($end ? 'end' : 'start')) . '" font-size="' . $fontSize
                    . '" fill="' . EChartsBarPreview::E($labelColor) . '">' . EChartsBarPreview::E($label) . '</text>';
            }
        }
        return $svg . '</g></svg>';
    }
}
