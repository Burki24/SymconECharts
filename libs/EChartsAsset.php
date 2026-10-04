<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/**
 * Loads the pinned, reproducible Gauge runtime and official ECharts themes.
 */
final class EChartsAsset
{
    public const VERSION = '6.1.0';
    public const SHA256 = '0eef7a38f5bd44691e0d629756166ba0a7c3024a781746c1acd58d8d6b0a3f8b';
    public const THEME_AUTO = 'auto';
    public const THEME_SHA256 = [
        'dark'        => 'ae60e563617cb87514690c1946ee202e78c9f1487820490614b58934ed037458',
        'vintage'     => '15c22b1f26961f972d23a9297362d42c4e59764e4b38025364f341cf144fb84f',
        'macarons'    => '1aa73f933f0fd92b02e3e1de18299b15a2d536eb29c4b9755b8ab25d54f5640d',
        'infographic' => '43f803926e8625a1f1cbf6b6189d1735c0526e3e9e7e12aa8531a8296550c1b4',
        'shine'       => '33f28aaec40952dbad84de2d8cb3d58f49ea131806fad9497f06ad4d46f2a8f3',
        'roma'        => '01303d34787f242664d5ad35bb1d535bb21d701ff60f6f1412e8c872008b5a05'
    ];

    private const THEME_PALETTES = [
        self::THEME_AUTO => [
            'background' => '#151619',
            'text'       => '#F4F5F7',
            'muted'      => '#969AA2',
            'border'     => '#A5A9B0',
            'track'      => '#34363B',
            'accent'     => '#55CBB5',
            'surface'    => '#25272B'
        ],
        'dark' => [
            'background' => '#100C2A',
            'text'       => '#B9B8CE',
            'muted'      => '#817F91',
            'border'     => '#EEF1FA',
            'track'      => '#484753',
            'accent'     => '#4992FF',
            'surface'    => '#353450'
        ],
        'vintage' => [
            'background' => '#FEF8EF',
            'text'       => '#333333',
            'muted'      => '#6E7074',
            'border'     => '#919E8B',
            'track'      => '#E5DDD2',
            'accent'     => '#D87C7C',
            'surface'    => '#FFFDF9'
        ],
        'macarons' => [
            'background' => '#FFFFFF',
            'text'       => '#008ACD',
            'muted'      => '#8D98B3',
            'border'     => '#5AB1EF',
            'track'      => '#E6F4F5',
            'accent'     => '#2EC7C9',
            'surface'    => '#F6FBFC'
        ],
        'infographic' => [
            'background' => '#FFFFFF',
            'text'       => '#27727B',
            'muted'      => '#6E7074',
            'border'     => '#27727B',
            'track'      => '#DEE6E7',
            'accent'     => '#C1232B',
            'surface'    => '#F7F9F9'
        ],
        'shine' => [
            'background' => '#FFFFFF',
            'text'       => '#333333',
            'muted'      => '#6E7074',
            'border'     => '#005EAA',
            'track'      => '#DEEAF2',
            'accent'     => '#C12E34',
            'surface'    => '#F5F9FC'
        ],
        'roma' => [
            'background' => '#FFFFFF',
            'text'       => '#333333',
            'muted'      => '#2E4783',
            'border'     => '#B8D2C7',
            'track'      => '#EEF2EF',
            'accent'     => '#E01F54',
            'surface'    => '#F7F5EF'
        ]
    ];

    public static function JavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.gauge.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts runtime could not be loaded.');
        }
        if (!self::HasExpectedIntegrity($content)) {
            throw new RuntimeException('The bundled Apache ECharts runtime failed its integrity check.');
        }

        return self::NormalizeLineEndings($content);
    }

    /** @return list<string> */
    public static function ThemeIDs(): array
    {
        return [self::THEME_AUTO, ...array_keys(self::THEME_SHA256)];
    }

    public static function IsSupportedTheme(string $themeID): bool
    {
        return in_array($themeID, self::ThemeIDs(), true);
    }

    /**
     * Loads all pinned official themes so an open HTML-SDK tile can switch
     * themes after ApplyChanges without loading external resources.
     */
    public static function ThemeJavaScript(): string
    {
        $themes = [];
        foreach (array_keys(self::THEME_SHA256) as $themeID) {
            $path = __DIR__ . '/echarts/' . self::VERSION . '/themes/' . $themeID . '.js';
            $content = @file_get_contents($path);
            if ($content === false || $content === '') {
                throw new RuntimeException('The bundled Apache ECharts theme could not be loaded: ' . $themeID);
            }
            if (!self::HasExpectedThemeIntegrity($themeID, $content)) {
                throw new RuntimeException('The bundled Apache ECharts theme failed its integrity check: ' . $themeID);
            }

            $themes[] = self::NormalizeLineEndings($content);
        }

        return implode("\n;\n", $themes);
    }

    public static function HasExpectedThemeIntegrity(string $themeID, string $content): bool
    {
        $expectedHash = self::THEME_SHA256[$themeID] ?? null;
        if ($expectedHash === null) {
            return false;
        }

        $canonicalContent = self::NormalizeLineEndings($content);
        if (str_contains($canonicalContent, "\r")) {
            return false;
        }

        return hash_equals($expectedHash, hash('sha256', $canonicalContent));
    }

    /**
     * Returns stable colors for the lightweight SVG form preview. The native
     * tile continues to use the actual registered Apache ECharts theme.
     *
     * @return array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string}
     */
    public static function ThemePreviewPalette(string $themeID): array
    {
        $palette = self::THEME_PALETTES[$themeID] ?? null;
        if ($palette === null) {
            throw new RuntimeException('The requested ECharts theme is not supported: ' . $themeID);
        }

        return $palette;
    }

    /**
     * Returns all stable palettes used to keep custom layouts readable when an
     * upstream theme assumes a different background or component geometry.
     *
     * @return array<string,array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string}>
     */
    public static function ThemePalettes(): array
    {
        return self::THEME_PALETTES;
    }

    /**
     * Verifies the upstream bytes while tolerating a Windows checkout that
     * converted the original LF line endings to CRLF.
     */
    public static function HasExpectedIntegrity(string $content): bool
    {
        $canonicalContent = self::NormalizeLineEndings($content);
        if (str_contains($canonicalContent, "\r")) {
            return false;
        }

        return hash_equals(self::SHA256, hash('sha256', $canonicalContent));
    }

    private static function NormalizeLineEndings(string $content): string
    {
        return str_replace("\r\n", "\n", $content);
    }
}
