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
    private const CENTER_Y = 196.0;
    private const RADIUS = 132.0;
    private const START_ANGLE = 210.0;
    private const ANGLE_RANGE = 240.0;

    public static function CreateSvg(
        float $value,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $language
    ): string {
        if (!is_finite($value) || !is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            throw new InvalidArgumentException('A finite Gauge value and a valid range are required.');
        }

        $decimals = max(0, min(6, $decimals));
        $ratio = max(0.0, min(1.0, ($value - $minimum) / ($maximum - $minimum)));
        $backgroundPath = self::ArcPath(1.0);
        $progressPath = self::ArcPath($ratio);
        $ticks = self::Ticks($minimum, $maximum, $language);
        $pointer = self::Pointer($ratio);
        $formattedValue = SVGPreviewHelper::escape(self::FormatNumber($value, $decimals, $language));
        $formattedUnit = SVGPreviewHelper::escape(trim($unit));
        $formattedTitle = SVGPreviewHelper::escape(trim($title));
        $valueText = trim($formattedValue . ($formattedUnit === '' ? '' : ' ' . $formattedUnit));
        $titleElement = $formattedTitle === ''
            ? ''
            : '<text x="360" y="352" class="title">' . $formattedTitle . '</text>';
        $progressElement = $progressPath === ''
            ? ''
            : '<path d="' . $progressPath . '" class="progress"/>';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="400" viewBox="0 0 720 400" role="img" aria-label="{$valueText}">
  <style>
    .surface{fill:#151619}.track{fill:none;stroke:#34363b;stroke-width:18;stroke-linecap:round;stroke-linejoin:round}.progress{fill:none;stroke:#55cbb5;stroke-width:18;stroke-linecap:round;stroke-linejoin:round}.minor{stroke:#62666d;stroke-width:1}.major{stroke:#a5a9b0;stroke-width:2}.axis{fill:#969aa2;font:14px 'Segoe UI',Arial,sans-serif;text-anchor:middle;dominant-baseline:middle}.pointer{fill:#55cbb5}.anchor{fill:#55cbb5;stroke:#f4f5f7;stroke-width:2}.value{fill:#f4f5f7;font:600 38px 'Segoe UI',Arial,sans-serif;text-anchor:middle}.title{fill:#b7bac1;font:18px 'Segoe UI',Arial,sans-serif;text-anchor:middle}
  </style>
  <rect class="surface" width="720" height="400" rx="12"/>
  <path d="{$backgroundPath}" class="track"/>
  {$progressElement}
  {$ticks}
  {$pointer}
  <text x="360" y="294" class="value">{$valueText}</text>
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

    private static function ArcPath(float $ratio): string
    {
        if ($ratio <= 0.0) {
            return '';
        }

        $segments = max(1, (int) ceil(80 * $ratio));
        $points = [];
        for ($index = 0; $index <= $segments; ++$index) {
            $fraction = $ratio * $index / $segments;
            $points[] = self::Point(self::RADIUS, self::Angle($fraction));
        }

        $path = '';
        foreach ($points as $index => $point) {
            $path .= ($index === 0 ? 'M' : ' L') . self::Coordinate($point[0]) . ' ' . self::Coordinate($point[1]);
        }

        return $path;
    }

    private static function Ticks(float $minimum, float $maximum, string $language): string
    {
        $elements = [];
        for ($index = 0; $index <= 50; ++$index) {
            $fraction = $index / 50;
            $major = $index % 5 === 0;
            $outer = self::Point(160.0, self::Angle($fraction));
            $inner = self::Point($major ? 149.0 : 154.0, self::Angle($fraction));
            $class = $major ? 'major' : 'minor';
            $elements[] = '<line x1="' . self::Coordinate($inner[0])
                . '" y1="' . self::Coordinate($inner[1])
                . '" x2="' . self::Coordinate($outer[0])
                . '" y2="' . self::Coordinate($outer[1])
                . '" class="' . $class . '"/>';

            if (!$major) {
                continue;
            }

            $label = self::Point(180.0, self::Angle($fraction));
            $labelValue = $minimum + ($maximum - $minimum) * $fraction;
            $elements[] = '<text x="' . self::Coordinate($label[0])
                . '" y="' . self::Coordinate($label[1])
                . '" class="axis">'
                . SVGPreviewHelper::escape(self::FormatAxisNumber($labelValue, $language))
                . '</text>';
        }

        return implode("\n  ", $elements);
    }

    private static function Pointer(float $ratio): string
    {
        $angle = self::Angle($ratio);
        $tip = self::Point(98.0, $angle);
        $radians = deg2rad($angle);
        $perpendicularX = sin($radians) * 7.0;
        $perpendicularY = cos($radians) * 7.0;
        $leftX = self::CENTER_X - $perpendicularX;
        $leftY = self::CENTER_Y - $perpendicularY;
        $rightX = self::CENTER_X + $perpendicularX;
        $rightY = self::CENTER_Y + $perpendicularY;
        $points = self::Coordinate($tip[0]) . ',' . self::Coordinate($tip[1])
            . ' ' . self::Coordinate($leftX) . ',' . self::Coordinate($leftY)
            . ' ' . self::Coordinate($rightX) . ',' . self::Coordinate($rightY);

        return '<polygon points="' . $points . '" class="pointer"/>'
            . '<circle cx="360" cy="196" r="11" class="anchor"/>';
    }

    /** @return array{float,float} */
    private static function Point(float $radius, float $angle): array
    {
        $radians = deg2rad($angle);

        return [
            self::CENTER_X + $radius * cos($radians),
            self::CENTER_Y - $radius * sin($radians)
        ];
    }

    private static function Angle(float $ratio): float
    {
        return self::START_ANGLE - self::ANGLE_RANGE * $ratio;
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
