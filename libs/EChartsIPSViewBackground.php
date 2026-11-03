<?php

declare(strict_types=1);

namespace SymconECharts;

final class EChartsIPSViewBackground
{
    public const DEFAULT_COLOR = -1;
    public const DEFAULT_OPACITY_PERCENT = 35;

    public static function IsValid(int $color, int $opacityPercent): bool
    {
        return $color >= self::DEFAULT_COLOR
            && $color <= 0xFFFFFF
            && $opacityPercent >= 0
            && $opacityPercent <= 100;
    }

    public static function Color(int $color): string
    {
        return $color < 0 ? '' : EChartsAsset::ColorToHex($color);
    }

    /** @param array<string, mixed> $style @return array<string, mixed> */
    public static function WithPreviewStyle(
        array $style,
        bool $adaptToBackground,
        int $configuredColor,
        int $opacityPercent
    ): array {
        $style['ipsViewAdaptToBackground'] = $adaptToBackground;
        $style['ipsViewBackgroundColor'] = $configuredColor;
        $style['ipsViewBackgroundOpacityPercent'] = $opacityPercent;

        return $style;
    }

    /** @param array<string, string> $palette @param array<string, mixed> $style @return array{color: string, opacity: float} */
    public static function PreviewBackground(array $palette, array $style): array
    {
        return self::ResolvedPreviewBackground(
            $palette,
            (bool) ($style['ipsViewAdaptToBackground'] ?? false),
            self::Color((int) ($style['ipsViewBackgroundColor'] ?? self::DEFAULT_COLOR)),
            (int) ($style['ipsViewBackgroundOpacityPercent'] ?? self::DEFAULT_OPACITY_PERCENT)
        );
    }

    /** @param array<string, string> $palette @return array{color: string, opacity: float} */
    public static function ResolvedPreviewBackground(
        array $palette,
        bool $adaptToBackground,
        string $configuredColor,
        int $opacityPercent
    ): array {
        $configuredColor = preg_match('/^#[0-9A-F]{6}$/i', $configuredColor) === 1
            ? strtoupper($configuredColor)
            : '';

        return [
            'color'   => $adaptToBackground && $configuredColor !== '' ? $configuredColor : $palette['background'],
            'opacity' => $adaptToBackground
                ? max(0, min(100, $opacityPercent)) / 100
                : 1.0
        ];
    }

    /** @param array<string, string> $palette */
    public static function ThemeCSS(
        array $palette,
        string $rootSelector,
        bool $adaptToBackground,
        int $configuredColor,
        int $opacityPercent
    ): string {
        $configuredBackground = self::Color($configuredColor);
        $backgroundColor = $adaptToBackground && $configuredBackground !== ''
            ? $configuredBackground
            : $palette['background'];
        $background = $adaptToBackground
            ? 'color-mix(in srgb, ' . $backgroundColor . ' ' . $opacityPercent . '%, transparent)'
            : $backgroundColor;

        return ':root {'
            . '--symc-background:' . $palette['background'] . ';'
            . '--symc-text:' . $palette['text'] . ';'
            . '--symc-text-muted:' . $palette['muted'] . ';'
            . '--symc-border:' . $palette['border'] . ';'
            . '--symc-accent:' . $palette['accent'] . ';'
            . '--symc-surface:' . $palette['surface'] . ';'
            . '} html, body { background:' . ($adaptToBackground ? 'transparent' : $background) . '; }'
            . ' ' . $rootSelector . ' { background:' . $background . '; }';
    }

    /** @return array<string, mixed> */
    public static function FormRow(string $onChange = ''): array
    {
        $items = [
            [
                'type'    => 'CheckBox',
                'name'    => 'IPSViewAdaptToBackground',
                'caption' => 'Adapt to IPSView background'
            ],
            [
                'type'               => 'SelectColor',
                'name'               => 'IPSViewBackgroundColor',
                'caption'            => 'Background color',
                'allowTransparent'   => true,
                'transparentCaption' => 'Automatic (theme)',
                'width'              => '180px'
            ],
            [
                'type'    => 'NumberSpinner',
                'name'    => 'IPSViewBackgroundOpacityPercent',
                'caption' => 'Background opacity',
                'minimum' => 0,
                'maximum' => 100,
                'suffix'  => '%',
                'width'   => '180px'
            ]
        ];
        if ($onChange !== '') {
            foreach ($items as &$item) {
                $item['onChange'] = $onChange;
            }
            unset($item);
        }

        return ['type' => 'RowLayout', 'items' => $items];
    }
}
