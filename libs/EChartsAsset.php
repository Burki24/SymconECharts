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
    public const SHA256 = '37d8c9774f27fc12e048e269e8ec6a114782529fb85262a6be8c6b887207c07f';
    public const CARTESIAN_SHA256 = '68499685d1e4eb44c7f52f724783b0bae8e459ed86bd8a1dec4049589a4be987';
    public const TIME_SERIES_SHA256 = self::CARTESIAN_SHA256;
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
            'background'    => '#151619',
            'text'          => '#F4F5F7',
            'muted'         => '#969AA2',
            'border'        => '#A5A9B0',
            'track'         => '#34363B',
            'accent'        => '#55CBB5',
            'surface'       => '#25272B',
            'seriesColors'  => [
                '#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF',
                '#FFD10A', '#FB628B', '#785DB0', '#3FBE95'
            ],
            'gaugeAxisLine' => [[1.0, '#34363B']]
        ],
        'dark' => [
            'background'    => '#100C2A',
            'text'          => '#B9B8CE',
            'muted'         => '#817F91',
            'border'        => '#EEF1FA',
            'track'         => '#484753',
            'accent'        => '#4992FF',
            'surface'       => '#353450',
            'seriesColors'  => [
                '#4992FF', '#7CFFB2', '#FDDD60', '#FF6E76', '#58D9F9',
                '#05C091', '#FF8A45', '#8D48E3', '#DD79FF'
            ],
            'gaugeAxisLine' => [[1.0, '#484753']]
        ],
        'vintage' => [
            'background'    => '#FEF8EF',
            'text'          => '#333333',
            'muted'         => '#6E7074',
            'border'        => '#919E8B',
            'track'         => '#E5DDD2',
            'accent'        => '#D87C7C',
            'surface'       => '#FFFDF9',
            'seriesColors'  => [
                '#D87C7C', '#919E8B', '#D7AB82', '#6E7074', '#61A0A8',
                '#EFA18D', '#787464', '#CC7E63', '#724E58', '#4B565B'
            ],
            'gaugeAxisLine' => [[1.0, '#E5DDD2']]
        ],
        'macarons' => [
            'background'    => '#FFFFFF',
            'text'          => '#008ACD',
            'muted'         => '#8D98B3',
            'border'        => '#5AB1EF',
            'track'         => '#E6F4F5',
            'accent'        => '#2EC7C9',
            'surface'       => '#F6FBFC',
            'seriesColors'  => [
                '#2EC7C9', '#B6A2DE', '#5AB1EF', '#FFB980', '#D87A80',
                '#8D98B3', '#E5CF0D', '#97B552', '#95706D', '#DC69AA',
                '#07A2A4', '#9A7FD1', '#588DD5', '#F5994E', '#C05050',
                '#59678C', '#C9AB00', '#7EB00A', '#6F5553', '#C14089'
            ],
            'gaugeAxisLine' => [
                [0.2, '#2EC7C9'],
                [0.8, '#5AB1EF'],
                [1.0, '#D87A80']
            ]
        ],
        'infographic' => [
            'background'    => '#FFFFFF',
            'text'          => '#27727B',
            'muted'         => '#6E7074',
            'border'        => '#27727B',
            'track'         => '#DEE6E7',
            'accent'        => '#C1232B',
            'surface'       => '#F7F9F9',
            'seriesColors'  => [
                '#C1232B', '#27727B', '#FCCE10', '#E87C25', '#B5C334',
                '#FE8463', '#9BCA63', '#FAD860', '#F3A43B', '#60C0DD',
                '#D7504B', '#C6E579', '#F4E001', '#F0805A', '#26C0C0'
            ],
            'gaugeAxisLine' => [
                [0.2, '#B5C334'],
                [0.8, '#27727B'],
                [1.0, '#C1232B']
            ]
        ],
        'shine' => [
            'background'    => '#FFFFFF',
            'text'          => '#333333',
            'muted'         => '#6E7074',
            'border'        => '#005EAA',
            'track'         => '#DEEAF2',
            'accent'        => '#C12E34',
            'surface'       => '#F5F9FC',
            'seriesColors'  => [
                '#C12E34', '#E6B600', '#0098D9', '#2B821D',
                '#005EAA', '#339CA8', '#CDA819', '#32A487'
            ],
            'gaugeAxisLine' => [
                [0.2, '#2B821D'],
                [0.8, '#005EAA'],
                [1.0, '#C12E34']
            ]
        ],
        'roma' => [
            'background'    => '#FFFFFF',
            'text'          => '#333333',
            'muted'         => '#2E4783',
            'border'        => '#B8D2C7',
            'track'         => '#EEF2EF',
            'accent'        => '#E01F54',
            'surface'       => '#F7F5EF',
            'seriesColors'  => [
                '#E01F54', '#001852', '#F5E8C8', '#B8D2C7', '#C6B38E',
                '#A4D8C2', '#F3D999', '#D3758F', '#DCC392', '#2E4783',
                '#82B6E9', '#FF6347', '#A092F1', '#0A915D', '#EAF889',
                '#6699FF', '#FF6666', '#3CB371', '#D5B158', '#38B6B6'
            ],
            'gaugeAxisLine' => [
                [0.2, '#E01F54'],
                [0.8, '#B8D2C7'],
                [1.0, '#001852']
            ]
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

    public static function CartesianJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.cartesian.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Cartesian runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::CARTESIAN_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Cartesian runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    public static function TimeSeriesJavaScript(): string
    {
        return self::CartesianJavaScript();
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
     * @return array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string,seriesColors:list<string>,gaugeAxisLine:list<array{float,string}>}
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
     * @return array<string,array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string,seriesColors:list<string>,gaugeAxisLine:list<array{float,string}>}>
     */
    public static function ThemePalettes(): array
    {
        return self::THEME_PALETTES;
    }

    public static function ColorToHex(int $color): string
    {
        return sprintf('#%06X', max(0, min(0xFFFFFF, $color)));
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
