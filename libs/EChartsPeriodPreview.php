<?php

declare(strict_types=1);

namespace eCharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

/** Reserve a footer below a bounded chart preview for the view-local period control. */
final class EChartsPeriodPreview
{
    public static function Apply(string $svg, bool $enabled, string $label, array $palette): string
    {
        if (!$enabled) {
            return $svg;
        }
        $end = strpos($svg, '>');
        $root = substr($svg, 0, $end + 1);
        if (!preg_match('/viewBox="0 0 (\d+) (\d+)"/', $root, $box)) {
            return $svg;
        }
        $height = (int) $box[2] + 30;
        $root = str_replace([$box[0], 'height="' . $box[2] . '"'], [
            'viewBox="0 0 ' . $box[1] . ' ' . $height . '"', 'height="' . $height . '"'
        ], $root);
        $width = min((int) $box[1] - 16, 24 + 8 * mb_strlen($label));
        $color = SVGPreviewHelper::escape($palette['text']);
        $control = '<g data-preview-period-selection="true" transform="translate(' . ((int) $box[1] - $width - 16) . ' ' . $height
            . ')"><rect x="8" y="-27" rx="4" width="' . $width
            . '" height="24" fill="' . SVGPreviewHelper::escape($palette['background'])
            . '" stroke="' . SVGPreviewHelper::escape($palette['border']) . '"/>'
            . '<text x="15" y="-11" fill="' . $color . '" font-size="12">' . SVGPreviewHelper::escape($label)
            . '</text><path d="M ' . ($width - 5) . ' -17 l 3 3 3 -3" fill="none" stroke="' . $color . '"/></g>';
        return $root . $control . substr($svg, $end + 1);
    }
}
