<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;
use InvalidArgumentException;

/**
 * Builds the family-specific SVG preview for a single Gauge.
 */
final class EChartsGaugeSinglePreview
{
    private const CENTER_X = 360.0;
    private const SUPPORTED_PRESETS = ['basic', 'simple', 'progress', 'speed'];

    public static function CreateSvg(
        float $value,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $language,
        string $preset = 'simple',
        string $theme = EChartsAsset::THEME_AUTO
    ): string {
        if (!is_finite($value) || !is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            throw new InvalidArgumentException('A finite Gauge value and a valid range are required.');
        }
        if (!in_array($preset, self::SUPPORTED_PRESETS, true)) {
            throw new InvalidArgumentException('A supported Gauge preset is required.');
        }
        if (!EChartsAsset::IsSupportedTheme($theme)) {
            throw new InvalidArgumentException('A supported ECharts theme is required.');
        }

        $design = self::PresetDesign($preset);
        $palette = EChartsAsset::ThemePreviewPalette($theme);
        $decimals = max(0, min(6, $decimals));
        $ratio = max(0.0, min(1.0, ($value - $minimum) / ($maximum - $minimum)));
        $trackElements = self::TrackSegments($palette['gaugeAxisLine'], $design);
        $progressPath = $design['showProgress'] ? self::ArcPath($ratio, $design) : '';
        $ticks = self::Ticks($minimum, $maximum, $language, $design);
        $pointer = self::Pointer($ratio, $design);
        $rawValue = self::FormatNumber($value, $decimals, $language);
        $rawUnit = trim($unit);
        $rawTitle = trim($title);
        $formattedValue = SVGPreviewHelper::escape($rawValue);
        $formattedUnit = SVGPreviewHelper::escape($rawUnit);
        $formattedTitle = SVGPreviewHelper::escape($rawTitle);
        $valueText = trim($formattedValue . ($formattedUnit === '' ? '' : ' ' . $formattedUnit));
        $ariaLabel = SVGPreviewHelper::escape(trim(
            ($rawTitle === '' ? '' : $rawTitle . ': ')
            . $rawValue
            . ($rawUnit === '' ? '' : ' ' . $rawUnit)
        ));
        $titleElement = $formattedTitle === ''
            ? ''
            : '<text x="360" y="' . self::Coordinate($design['titleY']) . '" class="title">' . $formattedTitle . '</text>';
        $progressElement = $progressPath === ''
            ? ''
            : '<path d="' . $progressPath . '" class="progress" style="stroke-width:'
                . self::Coordinate($design['lineWidth']) . 'px"/>';
        $detailBackground = $design['detailBox']
            ? '<rect x="238" y="280" width="244" height="58" rx="10" class="detail-box"/>'
            : '';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="400" viewBox="0 0 720 400" role="img" aria-label="{$ariaLabel}" data-preset="{$preset}" data-theme="{$theme}">
  <style>
    .surface{fill:{$palette['background']}}.theme-track-segment{fill:none;stroke-linecap:round;stroke-linejoin:round}.progress{fill:none;stroke:{$palette['accent']};stroke-linecap:round;stroke-linejoin:round}.minor{stroke:{$palette['muted']};stroke-width:1}.major{stroke:{$palette['border']};stroke-width:2}.axis{fill:{$palette['muted']};font:14px 'Segoe UI',Arial,sans-serif;text-anchor:middle;dominant-baseline:middle}.pointer{fill:{$palette['accent']}}.anchor{fill:{$palette['accent']};stroke:{$palette['text']};stroke-width:2}.detail-box{fill:{$palette['surface']};stroke:{$palette['border']};stroke-width:2}.value{fill:{$palette['text']};font:600 38px 'Segoe UI',Arial,sans-serif;text-anchor:middle}.title{fill:{$palette['muted']};font:18px 'Segoe UI',Arial,sans-serif;text-anchor:middle}svg[data-preset="progress"] .value{font-size:46px}svg[data-preset="speed"] .value{font-size:32px}
  </style>
  <rect class="surface" width="720" height="400" rx="12"/>
  {$trackElements}
  {$progressElement}
  {$ticks}
  {$pointer}
  {$detailBackground}
  <text x="360" y="{$design['detailY']}" class="value">{$valueText}</text>
  {$titleElement}
</svg>
SVG;
    }

    public static function CreateErrorSvg(string $message): string
    {
        $message = SVGPreviewHelper::escape($message);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="240" viewBox="0 0 720 240" role="img" aria-label="{$message}">
  <rect fill="#151619" width="720" height="240" rx="12"/>
  <circle cx="360" cy="88" r="28" fill="none" stroke="#ef6969" stroke-width="4"/>
  <path d="M360 72v22M360 105v2" stroke="#ef6969" stroke-width="4" stroke-linecap="round"/>
  <text x="360" y="160" fill="#f4f5f7" font-family="Segoe UI,Arial,sans-serif" font-size="18" text-anchor="middle">{$message}</text>
</svg>
SVG;
    }

    /**
     * @return array{
     *     startAngle: float,
     *     endAngle: float,
     *     centerY: float,
     *     radius: float,
     *     labelRadius: float,
     *     pointerLength: float,
     *     lineWidth: float,
     *     detailY: float,
     *     titleY: float,
     *     showProgress: bool,
     *     showMinorTicks: bool,
     *     detailBox: bool
     * }
     */
    private static function PresetDesign(string $preset): array
    {
        return match ($preset) {
            'basic' => [
                'startAngle'     => 210.0,
                'endAngle'       => -30.0,
                'centerY'        => 196.0,
                'radius'         => 132.0,
                'labelRadius'    => 108.0,
                'pointerLength'  => 102.0,
                'lineWidth'      => 16.0,
                'detailY'        => 294.0,
                'titleY'         => 352.0,
                'showProgress'   => false,
                'showMinorTicks' => true,
                'detailBox'      => false
            ],
            'progress' => [
                'startAngle'     => 210.0,
                'endAngle'       => -30.0,
                'centerY'        => 190.0,
                'radius'         => 132.0,
                'labelRadius'    => 108.0,
                'pointerLength'  => 94.0,
                'lineWidth'      => 20.0,
                'detailY'        => 306.0,
                'titleY'         => 360.0,
                'showProgress'   => true,
                'showMinorTicks' => false,
                'detailBox'      => false
            ],
            'speed' => [
                'startAngle'     => 180.0,
                'endAngle'       => 0.0,
                'centerY'        => 232.0,
                'radius'         => 142.0,
                'labelRadius'    => 118.0,
                'pointerLength'  => 108.0,
                'lineWidth'      => 18.0,
                'detailY'        => 319.0,
                'titleY'         => 370.0,
                'showProgress'   => true,
                'showMinorTicks' => true,
                'detailBox'      => true
            ],
            default => [
                'startAngle'     => 210.0,
                'endAngle'       => -30.0,
                'centerY'        => 196.0,
                'radius'         => 132.0,
                'labelRadius'    => 108.0,
                'pointerLength'  => 98.0,
                'lineWidth'      => 18.0,
                'detailY'        => 294.0,
                'titleY'         => 352.0,
                'showProgress'   => true,
                'showMinorTicks' => true,
                'detailBox'      => false
            ]
        };
    }

    /** @param array<string, float|bool> $design */
    private static function ArcPath(float $ratio, array $design): string
    {
        return self::ArcSegmentPath(0.0, $ratio, $design);
    }

    /**
     * @param list<array{float,string}> $segments
     * @param array<string, float|bool> $design
     */
    private static function TrackSegments(array $segments, array $design): string
    {
        $elements = [];
        $start = 0.0;
        foreach ($segments as $segment) {
            $end = max($start, min(1.0, (float) $segment[0]));
            if ($end <= $start) {
                continue;
            }

            $elements[] = '<path d="' . self::ArcSegmentPath($start, $end, $design)
                . '" class="theme-track-segment" style="stroke:' . SVGPreviewHelper::escape($segment[1])
                . ';stroke-width:' . self::Coordinate($design['lineWidth']) . 'px"/>';
            $start = $end;
        }

        return implode("\n  ", $elements);
    }

    /** @param array<string, float|bool> $design */
    private static function ArcSegmentPath(float $startRatio, float $endRatio, array $design): string
    {
        if ($endRatio <= $startRatio) {
            return '';
        }

        $ratio = $endRatio - $startRatio;
        $segments = max(1, (int) ceil(80 * $ratio));
        $points = [];
        for ($index = 0; $index <= $segments; ++$index) {
            $fraction = $startRatio + $ratio * $index / $segments;
            $points[] = self::Point(
                (float) $design['radius'],
                self::Angle($fraction, $design),
                (float) $design['centerY']
            );
        }

        $path = '';
        foreach ($points as $index => $point) {
            $path .= ($index === 0 ? 'M' : ' L') . self::Coordinate($point[0]) . ' ' . self::Coordinate($point[1]);
        }

        return $path;
    }

    /** @param array<string, float|bool> $design */
    private static function Ticks(float $minimum, float $maximum, string $language, array $design): string
    {
        $elements = [];
        for ($index = 0; $index <= 50; ++$index) {
            $fraction = $index / 50;
            $major = $index % 5 === 0;
            if (!$major && !$design['showMinorTicks']) {
                continue;
            }

            $angle = self::Angle($fraction, $design);
            $outerRadius = (float) $design['radius'] + 28.0;
            $outer = self::Point($outerRadius, $angle, (float) $design['centerY']);
            $inner = self::Point($outerRadius - ($major ? 11.0 : 6.0), $angle, (float) $design['centerY']);
            $class = $major ? 'major' : 'minor';
            $elements[] = '<line x1="' . self::Coordinate($inner[0])
                . '" y1="' . self::Coordinate($inner[1])
                . '" x2="' . self::Coordinate($outer[0])
                . '" y2="' . self::Coordinate($outer[1])
                . '" class="' . $class . '"/>';

            if (!$major) {
                continue;
            }

            $label = self::Point(
                (float) $design['labelRadius'],
                $angle,
                (float) $design['centerY']
            );
            $labelValue = $minimum + ($maximum - $minimum) * $fraction;
            $elements[] = '<text x="' . self::Coordinate($label[0])
                . '" y="' . self::Coordinate($label[1])
                . '" class="axis" data-label-index="' . $index . '">'
                . SVGPreviewHelper::escape(self::FormatAxisNumber($labelValue, $language))
                . '</text>';
        }

        return implode("\n  ", $elements);
    }

    /** @param array<string, float|bool> $design */
    private static function Pointer(float $ratio, array $design): string
    {
        $angle = self::Angle($ratio, $design);
        $centerY = (float) $design['centerY'];
        $tip = self::Point((float) $design['pointerLength'], $angle, $centerY);
        $radians = deg2rad($angle);
        $perpendicularX = sin($radians) * 7.0;
        $perpendicularY = cos($radians) * 7.0;
        $leftX = self::CENTER_X - $perpendicularX;
        $leftY = $centerY - $perpendicularY;
        $rightX = self::CENTER_X + $perpendicularX;
        $rightY = $centerY + $perpendicularY;
        $points = self::Coordinate($tip[0]) . ',' . self::Coordinate($tip[1])
            . ' ' . self::Coordinate($leftX) . ',' . self::Coordinate($leftY)
            . ' ' . self::Coordinate($rightX) . ',' . self::Coordinate($rightY);

        return '<polygon points="' . $points . '" class="pointer"/>'
            . '<circle cx="360" cy="' . self::Coordinate($centerY) . '" r="11" class="anchor"/>';
    }

    /** @return array{float,float} */
    private static function Point(float $radius, float $angle, float $centerY): array
    {
        $radians = deg2rad($angle);

        return [
            self::CENTER_X + $radius * cos($radians),
            $centerY - $radius * sin($radians)
        ];
    }

    /** @param array<string, float|bool> $design */
    private static function Angle(float $ratio, array $design): float
    {
        $startAngle = (float) $design['startAngle'];

        return $startAngle + ((float) $design['endAngle'] - $startAngle) * $ratio;
    }

    private static function Coordinate(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private static function FormatAxisNumber(float $value, string $language): string
    {
        $decimals = abs($value - round($value)) < 0.05 ? 0 : 1;

        return self::FormatNumber($value, $decimals, $language);
    }

    private static function FormatNumber(float $value, int $decimals, string $language): string
    {
        $german = strtolower($language) === 'de';

        return number_format($value, $decimals, $german ? ',' : '.', $german ? '.' : ',');
    }
}
