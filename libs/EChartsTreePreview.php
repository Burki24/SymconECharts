<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/EChartsBarPreview.php';
require_once __DIR__ . '/TreeHierarchy.php';

/** Pure, bounded structure preview shared by the two object-tree families. */
final class EChartsTreePreview
{
    /** @param array<string,mixed> $root @param array<string,mixed> $style @param array<string,string> $colors */
    public static function Draw(string $svg, array $root, array $style, array $colors, string $hint): string
    {
        $initialDepth = max(0, min(TreeHierarchy::MAXIMUM_DEPTH, (int) ($style['initialDepth'] ?? 2)));
        $nodes = [];
        $collect = static function (array $node, int $depth, ?int $parent) use (&$collect, &$nodes, $initialDepth): ?int
        {
            if (count($nodes) >= 80 || $depth > TreeHierarchy::MAXIMUM_DEPTH) {
                return null;
            }
            $index = count($nodes);
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $nodes[] = ['node' => $node, 'depth' => $depth, 'parent' => $parent, 'children' => [], 'collapsed' => false];
            if ($depth < $initialDepth) {
                foreach ($children as $child) {
                    if (!is_array($child)) {
                        continue;
                    }
                    $childIndex = $collect($child, $depth + 1, $index);
                    if ($childIndex === null) {
                        break;
                    }
                    $nodes[$index]['children'][] = $childIndex;
                }
            }
            $nodes[$index]['collapsed'] = count($children) > count($nodes[$index]['children']);
            return $index;
        };
        $collect($root, 0, null);
        $leafCount = 0;
        $place = static function (int $index) use (&$place, &$nodes, &$leafCount): float
        {
            $children = $nodes[$index]['children'];
            if ($children === []) {
                return $nodes[$index]['breadth'] = (float) $leafCount++;
            }
            $positions = array_map($place, $children);
            return $nodes[$index]['breadth'] = ($positions[0] + $positions[count($positions) - 1]) / 2;
        };
        $place(0);
        $maximumDepth = max(1, max(array_column($nodes, 'depth')));
        $radial = ($style['treeLayout'] ?? '') === 'radial';
        $orientation = in_array($style['orientation'] ?? '', ['LR', 'RL', 'TB', 'BT'], true) ? $style['orientation'] : 'LR';
        $fontSize = max(8, min(24, (int) round(11 * (int) ($style['labelFontSizePercent'] ?? 100) / 100)));
        $radius = max(2, min(15, (int) ($style['nodeSize'] ?? 10) / 2));
        $deep = $maximumDepth > 6;
        $vertical = !$radial && in_array($orientation, ['TB', 'BT'], true);
        $levelSpacing = ($radial ? 108 : ($vertical ? 210 : 560)) / $maximumDepth;
        if ($deep) {
            $radius = min($radius, max(2, $levelSpacing / 3));
        }
        $labelStride = $deep && $radial ? max(1, (int) ceil(($fontSize + 4) / $levelSpacing)) : 1;
        $labelColor = (string) ($style['labelColor'] ?? '#969AA2');
        $edgeColor = (string) ($style['edgeColor'] ?? '#969AA2');
        foreach ($nodes as &$item) {
            $along = $item['depth'] / $maximumDepth;
            $across = $leafCount <= 1 ? 0.5 : $item['breadth'] / ($leafCount - 1);
            if ($radial) {
                $angle = $leafCount <= 1 ? -M_PI / 2 : 2 * M_PI * ($item['breadth'] + 0.5) / $leafCount - M_PI / 2;
                $item['x'] = 360 + 108 * $along * cos($angle);
                $item['y'] = 192 + 108 * $along * sin($angle);
            } else {
                [$item['x'], $item['y']] = match ($orientation) {
                    'RL'    => [690 - 560 * $along, 88 + 210 * $across],
                    'TB'    => [35 + 650 * $across, 88 + 210 * $along],
                    'BT'    => [35 + 650 * $across, 298 - 210 * $along],
                    default => [30 + 560 * $along, 88 + 210 * $across]
                };
            }
        }
        unset($item);
        $svg .= '<defs><clipPath id="tree-preview-area"><rect x="20" y="75" width="680" height="235"/></clipPath></defs>'
            . '<g clip-path="url(#tree-preview-area)">';
        foreach ($nodes as $item) {
            if ($item['parent'] === null) {
                continue;
            }
            $parent = $nodes[$item['parent']];
            $px = $parent['x'];
            $py = $parent['y'];
            $x = $item['x'];
            $y = $item['y'];
            $vertical = !$radial && in_array($orientation, ['TB', 'BT'], true);
            $middleX = ($px + $x) / 2;
            $middleY = ($py + $y) / 2;
            $path = 'M ' . EChartsBarPreview::N($px) . ' ' . EChartsBarPreview::N($py);
            if (($style['edgeShape'] ?? '') === 'polyline') {
                $path .= $vertical
                    ? ' L ' . EChartsBarPreview::N($px) . ' ' . EChartsBarPreview::N($middleY) . ' L ' . EChartsBarPreview::N($x) . ' ' . EChartsBarPreview::N($middleY)
                    : ' L ' . EChartsBarPreview::N($middleX) . ' ' . EChartsBarPreview::N($py) . ' L ' . EChartsBarPreview::N($middleX) . ' ' . EChartsBarPreview::N($y);
                $path .= ' L ' . EChartsBarPreview::N($x) . ' ' . EChartsBarPreview::N($y);
            } else {
                $path .= $vertical
                    ? ' C ' . EChartsBarPreview::N($px) . ' ' . EChartsBarPreview::N($middleY) . ' ' . EChartsBarPreview::N($x) . ' ' . EChartsBarPreview::N($middleY)
                    : ' C ' . EChartsBarPreview::N($middleX) . ' ' . EChartsBarPreview::N($py) . ' ' . EChartsBarPreview::N($middleX) . ' ' . EChartsBarPreview::N($y);
                $path .= ' ' . EChartsBarPreview::N($x) . ' ' . EChartsBarPreview::N($y);
            }
            $svg .= '<path data-parent="' . EChartsBarPreview::E((string) ($parent['node']['id'] ?? ''))
                . '" data-child="' . EChartsBarPreview::E((string) ($item['node']['id'] ?? '')) . '" d="' . $path
                . '" fill="none" stroke="' . EChartsBarPreview::E($edgeColor) . '" stroke-width="1.5"/>';
        }
        foreach ($nodes as $item) {
            $node = $item['node'];
            $kind = (string) ($node['kind'] ?? 'category');
            $health = (string) ($node['health'] ?? '');
            $color = $colors[$health] ?? $colors[$kind] ?? $colors['category'] ?? '#5070DD';
            $x = $item['x'];
            $y = $item['y'];
            $svg .= '<g data-node="' . EChartsBarPreview::E((string) ($node['id'] ?? '')) . '" data-depth="' . $item['depth']
                . '" data-collapsed="' . ($item['collapsed'] ? 'true' : 'false') . '"><title>'
                . EChartsBarPreview::E((string) ($node['name'] ?? '')) . '</title><circle cx="' . EChartsBarPreview::N($x)
                . '" cy="' . EChartsBarPreview::N($y) . '" r="' . EChartsBarPreview::N($radius)
                . '" fill="' . EChartsBarPreview::E($color) . '"/>';
            if ($item['collapsed']) {
                $svg .= '<circle cx="' . EChartsBarPreview::N($x) . '" cy="' . EChartsBarPreview::N($y)
                    . '" r="' . EChartsBarPreview::N($radius + 2) . '" fill="none" stroke="'
                    . EChartsBarPreview::E($color) . '" stroke-width="1"/>';
            }
            if (($style['showLabels'] ?? true) === true
                && ($item['depth'] === 0 || ($maximumDepth - $item['depth']) % $labelStride === 0)) {
                $left = $radial ? $x < 360 : $orientation === 'RL';
                $vertical = !$radial && in_array($orientation, ['TB', 'BT'], true);
                $available = $radial ? 170 : ($vertical ? min(110, 650 / max(1, $leafCount)) : min(125, 560 / $maximumDepth - 12));
                $label = (string) ($node['name'] ?? '');
                if (($style['showValues'] ?? false) === true && $kind === 'variable' && ($node['displayValue'] ?? '') !== '') {
                    $label .= ' ' . (string) $node['displayValue'];
                }
                $label = mb_strimwidth($label, 0, max(2, (int) floor(max(12, $available) / ($fontSize * 0.6))), '…', 'UTF-8');
                $above = $vertical && !$deep;
                $labelX = $above ? $x : $x + ($left ? -1 : 1) * ($radius + 5);
                $labelY = $above ? $y - $radius - 4 : $y - 4;
                $anchor = $above ? 'middle' : ($left ? 'end' : 'start');
                $svg .= '<text x="' . EChartsBarPreview::N($labelX) . '" y="' . EChartsBarPreview::N($labelY)
                    . '" text-anchor="' . $anchor . '" fill="' . EChartsBarPreview::E($labelColor)
                    . '" font-size="' . $fontSize . '">' . EChartsBarPreview::E($label) . '</text>';
            }
            $svg .= '</g>';
        }
        return $svg . '</g><text x="24" y="342" fill="' . EChartsBarPreview::E($labelColor) . '" font-size="10">'
            . EChartsBarPreview::E($hint) . '</text></svg>';
    }
}
