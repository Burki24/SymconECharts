<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/EChartsSourceIdentity.php';

/** Shared SVG primitives and bounded source resolution; chart geometry stays with each module. */
final class EChartsBarPreview
{
    /** @param array<string,mixed> $values @return array{items:list<array<string,mixed>>,sample:bool} */
    public static function Sources(string $stored, array $values, string $labelField = 'Label', bool $currentValues = true): array
    {
        $raw = $values['Sources'] ?? json_decode($stored, true);
        if (!is_array($raw) || !array_is_list($raw) || count($raw) > 16) {
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
        foreach (array_slice($raw, 0, 16) as $index => $row) {
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
            $items[] = [
                ...$row,
                'label' => $labels[$id],
                'value' => is_numeric($value) ? (float) $value : (float) (12 + $index * 6),
                'color' => self::ConfiguredColor($row['Color'] ?? -1),
                'unit'  => trim(is_string($row['Unit'] ?? null) ? $row['Unit'] : '')
            ];
        }
        if ($items !== []) {
            return ['items' => $items, 'sample' => $sample];
        }

        return ['items' => [
            ['label' => 'Außen', 'value' => 12.4, 'color' => '', 'unit' => '°C'],
            ['label' => 'Bad', 'value' => 22.6, 'color' => '', 'unit' => '°C'],
            ['label' => 'Büro', 'value' => 24.1, 'color' => '', 'unit' => '°C']
        ], 'sample' => true];
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
        string $fallbackTitle
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
        $svg .= '<text x="24" y="32" fill="' . self::E($palette['text']) . '" font-size="20">'
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
