<?php

declare(strict_types=1);

namespace eCharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

final class EChartsGaugeWindPreview
{
    /** @param array<string, mixed> $chart */
    public static function CreateSvg(array $chart): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($chart['theme']);
        $style = $chart['style'];
        $background = EChartsIPSViewBackground::PreviewBackground($palette, $style);
        $textColor = static fn (string $name, string $fallback): string => SVGPreviewHelper::escape($style[$name] ?: $fallback);
        $labels = $chart['labels'];
        $direction = $chart['direction'];
        $dialOffset = $style['rotateCompass'] ? -$direction : 0;
        $german = str_starts_with($chart['language'], 'de');
        $readings = [];
        if ($chart['speed'] !== null) {
            $speed = number_format($chart['speed'], $chart['speedDecimals'], $german ? ',' : '.', $german ? '.' : ',');
            $readings[] = trim($chart['windLabel'] . ': ' . $speed . ' ' . $chart['speedUnit']);
        }
        if ($style['showDegreeValue']) {
            $degrees = rtrim(rtrim(number_format($direction, 1, '.', ''), '0'), '.');
            $readings[] = $labels[(int) round($direction / 45) % 8] . ' ' . ($german ? str_replace('.', ',', $degrees) : $degrees) . '°';
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 320" role="img">'
            . '<defs><linearGradient id="rim" x2="1" y2="1" gradientUnits="objectBoundingBox">'
            . '<stop stop-color="#EEF3F7"/><stop offset=".32" stop-color="#A6ACB3"/>'
            . '<stop offset=".62" stop-color="#555D64"/><stop offset="1" stop-color="#B6BDC4"/></linearGradient></defs>'
            . '<rect width="320" height="320" fill="' . SVGPreviewHelper::escape($background['color'])
            . '" fill-opacity="' . $background['opacity'] . '"/>';
        $top = 4;
        foreach ([[$chart['title'], 'titleColor'], [implode(' · ', $readings), 'valueColor']] as [$line, $color]) {
            if ($line !== '') {
                $svg .= '<text x="160" y="' . ($top + 18) . '" text-anchor="middle" font-family="sans-serif"'
                    . ' font-size="21" font-weight="600" fill="' . $textColor($color, $palette['text']) . '">'
                    . SVGPreviewHelper::escape($line) . '</text>';
                $top += 25;
            }
        }
        $center = (320 + $top) / 2;
        $scale = (320 - $top) * 0.0048;
        $svg .= '<g transform="translate(160 ' . $center . ') scale(' . $scale . ')">';
        if (!empty($style['compassImage'])) {
            $svg .= '<image x="-100" y="-100" width="200" height="200" transform="rotate(' . $dialOffset . ')"'
                . ' href="' . SVGPreviewHelper::escape($style['compassImage']) . '"/>';
        } else {
            $svg .= '<circle r="100" fill="url(#rim)"/><circle r="91" fill="' . $textColor('dialColor', '#616871') . '"/>'
                . '<circle r="85.25" fill="none" stroke="' . $textColor('ringColor', '#1678BD') . '" stroke-width="9.5"/>';
            for ($i = 0; $i < 48; $i++) {
                $major = $i % 6 === 0;
                $svg .= '<path transform="rotate(' . ($i * 7.5 + $dialOffset) . ')" d="M0 -90v' . ($major ? '10' : '5.4') . '"'
                    . ' stroke="' . $textColor('tickColor', $major ? '#F47B60' : '#EEF4FA') . '" stroke-width="' . ($major ? '1.6' : '1') . '"/>';
            }
            foreach ($labels as $i => $label) {
                $angle = deg2rad(45 * $i + $dialOffset);
                $svg .= '<text x="' . round(69 * sin($angle), 2) . '" y="' . round(-69 * cos($angle), 2) . '"'
                    . ' text-anchor="middle" dominant-baseline="central" font-family="sans-serif" font-weight="bold"'
                    . ' font-size="12.6" fill="' . $textColor('cardinalColor', '#FFFFFF') . '">' . SVGPreviewHelper::escape($label) . '</text>';
            }
        }
        if ($style['showRotor']) {
            $svg .= '<path transform="translate(0 34)" d="M0 -13.5 Q11 0 -2 8 L-4 13 L-3 6 Q-10 0 0 -13.5Z"'
                . ' fill="' . $textColor('rotorColor', '#70CBAF') . '"/>';
        }
        return $svg . '<path transform="rotate(' . ($style['rotateCompass'] ? 0 : $direction) . ')" d="M0 -66 L6.5 -3.3 L0 0 L-6.5 -3.3Z"'
            . ' fill="' . $textColor('pointerColor', '#42B7FF') . '"/>'
            . '<circle r="7" fill="' . $textColor('hubColor', '#F1F5F8') . '" stroke="#858E97" stroke-width="1"/>'
            . '</g></svg>';
    }
}
