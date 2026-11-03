<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

/** Marks a bounded SVG preview without changing the underlying chart model. */
final class EChartsPreviewSourceCount
{
    public static function Append(
        string $svg,
        int $visibleCount,
        int $sourceCount,
        string $caption,
        string $color,
        int $x,
        int $y,
        int $fontSize = 11
    ): string {
        if ($visibleCount >= $sourceCount) {
            return $svg;
        }
        if (!str_ends_with($svg, '</svg>')) {
            throw new \InvalidArgumentException('A preview SVG must end with </svg>.');
        }

        $notice = '<text x="' . $x . '" y="' . $y . '" text-anchor="end" font-size="'
            . $fontSize . '" fill="' . SVGPreviewHelper::escape($color)
            . '" data-preview-sources="' . $visibleCount . '/' . $sourceCount . '">'
            . SVGPreviewHelper::escape(sprintf($caption, $visibleCount, $sourceCount)) . '</text>';

        return substr($svg, 0, -strlen('</svg>')) . $notice . '</svg>';
    }
}
