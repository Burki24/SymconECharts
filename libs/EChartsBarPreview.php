<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/EChartsSourceIdentity.php';
require_once __DIR__ . '/EChartsVariablePresentation.php';

/** Shared SVG primitives and bounded source resolution; chart geometry stays with each module. */
final class EChartsBarPreview
{
    /** @param array<string,mixed> $values @return array{items:list<array<string,mixed>>,sample:bool} */
    public static function Sources(string $stored, array $values, string $labelField = 'Label', bool $currentValues = true, int $maximumSources = 16): array
    {
        $raw = $values['Sources'] ?? json_decode($stored, true);
        if ($maximumSources < 1 || $maximumSources > 100) {
            throw new \InvalidArgumentException('Preview source budget must be between 1 and 100.');
        }
        if (!is_array($raw) || !array_is_list($raw) || count($raw) > $maximumSources) {
            $raw = [];
        }
        $mapped = [];
        foreach ($raw as $row) {
            $mapped[] = is_array($row) ? [
                'VariableID' => $row['VariableID'] ?? null,
                'Label'      => $row[$labelField] ?? ''
            ] : $row;
        }
        try {
            $labels = EChartsSourceIdentity::LabelsForDraftSources($mapped);
        } catch (\Throwable) {
            $labels = [];
        }
        $items = [];
        $sample = !$currentValues;
        foreach (array_slice($raw, 0, $maximumSources) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['VariableID'] ?? 0);
            if (!isset($labels[$id])) {
                continue;
            }
            try {
                $value = $currentValues ? GetValue($id) : null;
            } catch (\Throwable) {
                $value = null;
            }
            if (!is_numeric($value)) {
                $sample = true;
            }
            $decimals = $row['Decimals'] ?? 1;
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6) {
                $decimals = 1;
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $id,
                0.0,
                100.0,
                is_string($row['Unit'] ?? null) ? $row['Unit'] : '',
                $decimals,
                (bool) ($row['UseVariablePresentation'] ?? true)
            );
            $items[] = [
                ...$row,
                'label'    => $labels[$id],
                'value'    => is_numeric($value) ? (float) $value : (float) (12 + $index * 6),
                'color'    => self::ConfiguredColor($row['Color'] ?? -1),
                'unit'     => $presentation['unit'],
                'decimals' => $presentation['decimals']
            ];
        }
        if ($items !== []) {
            return ['items' => $items, 'sample' => $sample];
        }

        return ['items' => [
            ['label' => 'Außen', 'value' => 12.4, 'color' => '', 'unit' => '°C', 'decimals' => 1],
            ['label' => 'Bad', 'value' => 22.6, 'color' => '', 'unit' => '°C', 'decimals' => 1],
            ['label' => 'Büro', 'value' => 24.1, 'color' => '', 'unit' => '°C', 'decimals' => 1]
        ], 'sample' => true];
    }

    public static function FormatValue(float $value, int $decimals, string $unit, string $language): string
    {
        $decimals = max(0, min(6, $decimals));
        $german = strtolower($language) === 'de';

        return trim(number_format($value, $decimals, $german ? ',' : '.', $german ? '.' : ',') . ' ' . $unit);
    }

    /** @return array<string,mixed> */
    public static function Palette(string $theme): array
    {
        return EChartsAsset::ThemePreviewPalette(EChartsAsset::IsSupportedTheme($theme) ? $theme : EChartsAsset::THEME_AUTO);
    }

    /** @param array<string,mixed> $palette @param array<string,mixed> $values */
    public static function Open(
        array $palette,
        string $title,
        bool $sample,
        bool $ipsView,
        array $values,
        string $sampleCaption,
        string $fallbackTitle,
        ?string $titleColor = null,
        int $titleFontSizePercent = 100
    ): string {
        $background = $ipsView
            ? EChartsIPSViewBackground::ResolvedPreviewBackground(
                $palette,
                (bool) ($values['IPSViewAdaptToBackground'] ?? false),
                EChartsIPSViewBackground::Color((int) ($values['IPSViewBackgroundColor'] ?? -1)),
                (int) ($values['IPSViewBackgroundOpacityPercent'] ?? 35)
            )
            : ['color' => $palette['background'], 'opacity' => 1.0];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="720" height="360" viewBox="0 0 720 360" role="img" aria-label="Chart preview">';
        $svg .= '<rect width="720" height="360" fill="' . self::E($background['color'])
            . '" fill-opacity="' . self::N($background['opacity']) . '"/>';
        $titleSize = max(10, min(40, (int) round(20 * $titleFontSizePercent / 100)));
        $svg .= '<text x="24" y="32" fill="' . self::E($titleColor ?? $palette['text'])
            . '" font-size="' . $titleSize . '">'
            . self::E($title !== '' ? $title : $fallbackTitle) . '</text>';
        if ($sample) {
            $svg .= '<text x="696" y="32" text-anchor="end" fill="' . self::E($palette['muted'])
                . '" font-size="11">' . self::E($sampleCaption) . '</text>';
        }

        return $svg;
    }

    public static function E(string $value): string
    {
        return SVGPreviewHelper::escape($value);
    }

    public static function N(float|int $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    }

    /** Shared Pie/CalendarPie sector geometry; two arcs keep complete circles visible after rounding. */
    public static function Sector(float $cx, float $cy, float $inner, float $outer, float $start, float $end, bool $clockwise = true): string
    {
        $span = min(abs($end - $start), 2 * M_PI);
        $direction = $clockwise ? 1 : -1;
        $end = $start + $direction * $span;
        $point = static fn (float $radius, float $angle): string => self::N($cx + $radius * cos($angle)) . ' ' . self::N($cy + $radius * sin($angle));
        $arc = static fn (float $radius, float $angle, int $sweep, int $large): string => ' A ' . self::N($radius) . ' ' . self::N($radius)
            . ' 0 ' . $large . ' ' . $sweep . ' ' . $point($radius, $angle);
        $sweep = $clockwise ? 1 : 0;
        $full = $span >= 2 * M_PI - 0.000001;
        $path = 'M ' . $point($outer, $start);
        if ($full) {
            $path .= $arc($outer, $start + $direction * M_PI, $sweep, 0) . $arc($outer, $end, $sweep, 0);
        } else {
            $path .= $arc($outer, $end, $sweep, $span > M_PI ? 1 : 0);
        }
        if ($inner > 0) {
            $path .= ($full ? ' Z M ' : ' L ') . $point($inner, $end);
            $path .= $full ? $arc($inner, $end - $direction * M_PI, 1 - $sweep, 0) . $arc($inner, $start, 1 - $sweep, 0)
                : $arc($inner, $start, 1 - $sweep, $span > M_PI ? 1 : 0);
        } elseif (!$full) {
            $path .= ' L ' . self::N($cx) . ' ' . self::N($cy);
        }
        return $path . ' Z';
    }

    /** Keeps schematic SVG patterns legible while reflecting the configured motif scale. */
    public static function PatternSize(int $sizePercent): float
    {
        return max(4.0, min(14.0, 7.0 * $sizePercent / 100));
    }

    public static function Color(int $configured, string $fallback): string
    {
        return $configured < 0 ? $fallback : EChartsAsset::ColorToHex($configured);
    }

    private static function ConfiguredColor(mixed $value): string
    {
        return is_numeric($value) && (int) $value >= 0 && (int) $value <= 0xFFFFFF
            ? EChartsAsset::ColorToHex((int) $value)
            : '';
    }
}
