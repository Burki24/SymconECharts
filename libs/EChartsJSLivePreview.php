<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/helper/SVGPreviewHelper.php';

/** Explicitly schematic color key, never substituted for the downloadable native ECharts comparison. */
final class EChartsJSLivePreview
{
    public static function ColorKey(array $model, array $design, string $caption): string
    {
        $background = $design['background'] === -1 ? 'url(#transparent-grid)' : sprintf('#%06X', $design['background']);
        $outline = $design['background'] < 0x808080 ? '#eee' : '#333';
        $plot = match ($model['family']) {
            'cartesian-history' => '<path d="M35 35V120H360M55 103L115 75L170 90L240 45L330 65" fill="none" stroke="#647bd6" stroke-width="4"/><path d="M80 118V95H110V118M200 118V70H230V118" fill="#4cae9a"/>',
            'pie'               => '<circle cx="200" cy="78" r="46" fill="none" stroke="#647bd6" stroke-width="25"/><path d="M200 32A46 46 0 0 1 246 78" fill="none" stroke="#4cae9a" stroke-width="25"/>',
            'radar-history'     => '<path d="M200 22L300 60L275 120H125L100 60Z M200 22L200 80L300 60M200 80L275 120M200 80L125 120M200 80L100 60" fill="none" stroke="#647bd6"/><path d="M200 45L270 67L245 105H150L130 65Z" fill="#4cae9a" fill-opacity=".4" stroke="#4cae9a"/>',
            'gauge'             => str_contains($model['settings']['template'], '(vertical)')
                ? '<rect x="188" y="20" width="24" height="110" fill="#647bd6"/><path d="M175 65H225M180 25H220M180 125H220" stroke="#4cae9a" stroke-width="4"/>'
                : (
                    str_contains($model['settings']['template'], 'Linear')
                ? '<rect x="70" y="58" width="260" height="22" fill="#647bd6"/><path d="M210 45V96M80 50V90M320 50V90" stroke="#4cae9a" stroke-width="4"/>'
                : '<circle cx="200" cy="78" r="46" fill="none" stroke="#647bd6" stroke-width="8"/><path d="M200 78L225 47" stroke="#4cae9a" stroke-width="5"/>'
                ),
            'progress' => self::Progress($model['settings'])
        };
        return SVGPreviewHelper::dataUri('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 190" role="img"><defs><pattern id="transparent-grid" width="16" height="16" patternUnits="userSpaceOnUse"><path fill="#ddd" d="M0 0H16V16H0Z"/><path fill="#bbb" d="M0 0H8V8H0ZM8 8H16V16H8Z"/></pattern></defs><rect x="1" y="1" width="398" height="150" fill="' . $background . '" stroke="' . $outline . '"/>'
            . $plot . '<path d="M15 135L40 160" fill="none" stroke="#888"/><text x="45" y="174" font-family="Arial" font-size="13" fill="#777">'
            . SVGPreviewHelper::escape($caption) . '</text></svg>');
    }

    private static function Progress(array $settings): string
    {
        if (is_array($settings['shape_svg'])) {
            return '<image x="110" y="22" width="180" height="110" href="' . SVGPreviewHelper::escape($settings['shape_svg']['dataUri']) . '"/>';
        }
        return match ($settings['shape_preset']) {
            'circle', 'bubble' => '<circle cx="200" cy="78" r="46" fill="#647bd6"/><path d="M154 78A46 46 0 0 0 246 78Z" fill="#4cae9a"/>',
            'fan'              => '<path d="M150 115A50 50 0 0 1 250 115Z" fill="#647bd6"/><path d="M150 115A50 50 0 0 1 200 65V115Z" fill="#4cae9a"/>',
            'text'             => '<text x="200" y="80" text-anchor="middle" font-family="Arial" font-size="30" fill="#4cae9a">LOADING</text>',
            default            => '<path d="M65 72H335" stroke="#647bd6" stroke-width="22"/><path d="M65 72H240" stroke="#4cae9a" stroke-width="22"/>'
        };
    }
}
