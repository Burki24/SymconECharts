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
    private const SUPPORTED_POINTER_SHAPES = ['preset', 'needle', 'line', 'arrow', 'custom'];
    private const SUPPORTED_ANCHOR_SHAPES = ['preset', 'circle', 'ring', 'custom', 'hidden'];
    private const SUPPORTED_PLATE_SHAPES = ['hidden', 'circle', 'arc'];
    private const SUPPORTED_PLATE_FILL_MODES = ['solid', 'linear', 'radial'];
    private const SUPPORTED_PLATE_BACKGROUND_FITS = ['contain', 'cover', 'stretch'];
    private const SUPPORTED_PLATE_GRADIENT_DIRECTIONS = [
        'top-bottom',
        'left-right',
        'diagonal-down',
        'diagonal-up'
    ];

    public static function CreateSvg(
        float $value,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $language,
        string $preset = 'simple',
        string $theme = EChartsAsset::THEME_AUTO,
        array $style = []
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
        $pointerShape = (string) ($style['pointerShape'] ?? 'preset');
        if (!in_array($pointerShape, self::SUPPORTED_POINTER_SHAPES, true)) {
            throw new InvalidArgumentException('A supported Gauge pointer shape is required.');
        }
        $anchorShape = (string) ($style['anchorShape'] ?? 'preset');
        if (!in_array($anchorShape, self::SUPPORTED_ANCHOR_SHAPES, true)) {
            throw new InvalidArgumentException('A supported Gauge anchor shape is required.');
        }
        $plateShape = (string) ($style['plateShape'] ?? 'hidden');
        if (!in_array($plateShape, self::SUPPORTED_PLATE_SHAPES, true)) {
            throw new InvalidArgumentException('A supported Gauge plate shape is required.');
        }
        $plateFillMode = (string) ($style['plateFillMode'] ?? 'solid');
        if (!in_array($plateFillMode, self::SUPPORTED_PLATE_FILL_MODES, true)) {
            throw new InvalidArgumentException('A supported Gauge plate fill mode is required.');
        }
        $plateGradientDirection = (string) ($style['plateGradientDirection'] ?? 'top-bottom');
        if (!in_array($plateGradientDirection, self::SUPPORTED_PLATE_GRADIENT_DIRECTIONS, true)) {
            throw new InvalidArgumentException('A supported Gauge plate gradient direction is required.');
        }
        $plateBackgroundFit = (string) ($style['plateBackgroundFit'] ?? 'cover');
        if (!in_array($plateBackgroundFit, self::SUPPORTED_PLATE_BACKGROUND_FITS, true)) {
            throw new InvalidArgumentException('A supported Gauge plate background fit is required.');
        }

        $design = self::ApplyAdvancedDesign(
            self::ApplyArcDesign(
                self::ApplyStyleScales(self::PresetDesign($preset, $minimum, $maximum), $style),
                $style
            ),
            $style
        );
        $palette = EChartsAsset::ThemePreviewPalette($theme);
        $customColors = ($style['colorMode'] ?? 'theme') === 'custom';
        $pointerColor = $customColors ? self::StyleColor($style, 'pointerColor', $palette['accent']) : $palette['accent'];
        $progressColor = $customColors ? self::StyleColor($style, 'progressColor', $palette['accent']) : $palette['accent'];
        $ringColor = $customColors ? self::StyleColor($style, 'ringColor', $palette['track']) : null;
        $scaleColor = $customColors ? self::StyleColor($style, 'scaleColor', $palette['muted']) : $palette['muted'];
        $minorColor = $customColors ? $scaleColor : $palette['muted'];
        $majorColor = $customColors ? $scaleColor : $palette['border'];
        $detailBorderColor = $customColors ? $scaleColor : $palette['border'];
        $valueColor = $customColors ? self::StyleColor($style, 'valueColor', $palette['text']) : $palette['text'];
        $titleColor = $customColors ? self::StyleColor($style, 'titleColor', $palette['muted']) : $palette['muted'];
        $arcMode = SVGPreviewHelper::escape((string) ($style['arcMode'] ?? 'preset'));
        $startPosition = self::Coordinate(self::NormalizePosition($style['startPosition'] ?? 270.0));
        $endPosition = self::Coordinate(self::NormalizePosition($style['endPosition'] ?? 90.0));
        $pointerLengthPercent = max(50, min(150, (int) ($style['pointerLengthPercent'] ?? 100)));
        $unitColor = $customColors ? $valueColor : ($preset === 'speed' ? $palette['muted'] : $palette['text']);
        $valueFontWeight = $preset === 'speed' ? 800 : 600;
        $unitFontWeight = $preset === 'speed' ? 400 : 600;
        $decimals = max(0, min(6, $decimals));
        $ratio = max(0.0, min(1.0, ($value - $minimum) / ($maximum - $minimum)));
        $trackSegments = $ringColor === null ? $palette['gaugeAxisLine'] : [[1.0, $ringColor]];
        if ((bool) ($style['scaleZonesEnabled'] ?? false)
            && is_array($style['scaleZones'] ?? null)
            && $style['scaleZones'] !== []) {
            $trackSegments = $style['scaleZones'];
            $lastZone = end($trackSegments);
            if (is_array($lastZone) && (float) ($lastZone[0] ?? 0.0) < 1.0) {
                $trackSegments[] = [1.0, $ringColor ?? $palette['track']];
            }
        }
        $trackElements = $design['showRing'] ? self::TrackSegments($trackSegments, $design) : '';
        $progressPath = $design['showProgress'] ? self::ArcPath($ratio, $design) : '';
        $ticks = self::Ticks($minimum, $maximum, $language, $design);
        $pointer = $design['showPointer'] ? self::Pointer($ratio, $design, $pointerShape, $style) : '';
        $anchorColorMode = (string) ($style['anchorColorMode'] ?? 'theme');
        $anchorColor = $anchorColorMode === 'custom'
            ? self::StyleColor($style, 'anchorColor', $pointerColor)
            : $pointerColor;
        $anchorBorderColor = $anchorColorMode === 'custom'
            ? self::StyleColor($style, 'anchorBorderColor', $valueColor)
            : $valueColor;
        $anchorBorderWidth = 2.0 * max(50, min(150, (int) ($style['anchorBorderWidthPercent'] ?? 100))) / 100;
        $anchor = self::Anchor($design, $pointerShape, $anchorShape, $style);
        $plateColorMode = (string) ($style['plateColorMode'] ?? 'theme');
        $plateTransparent = (bool) ($style['plateTransparent'] ?? false);
        $plateColor = $plateTransparent
            ? 'none'
            : ($plateColorMode === 'custom'
                ? self::StyleColor($style, 'plateColor', $palette['surface'])
                : $palette['surface']);
        $plateGradientDefinition = '';
        if (!$plateTransparent
            && $plateColorMode === 'custom'
            && in_array($plateFillMode, ['linear', 'radial'], true)) {
            $gradientStops = '<stop offset="0%" stop-color="' . $plateColor . '"/>';
            if ((bool) ($style['plateGradientMiddleEnabled'] ?? false)) {
                $gradientStops .= '<stop offset="50%" stop-color="'
                    . self::StyleColor($style, 'plateGradientMiddleColor', $plateColor) . '"/>';
            }
            $gradientStops .= '<stop offset="100%" stop-color="'
                . self::StyleColor($style, 'plateGradientEndColor', $plateColor) . '"/>';
            if ($plateFillMode === 'linear') {
                [$x1, $y1, $x2, $y2] = match ($plateGradientDirection) {
                    'left-right'    => ['0%', '50%', '100%', '50%'],
                    'diagonal-down' => ['0%', '0%', '100%', '100%'],
                    'diagonal-up'   => ['0%', '100%', '100%', '0%'],
                    default         => ['50%', '0%', '50%', '100%']
                };
                $plateGradientDefinition = '<linearGradient id="plate-fill-gradient" x1="' . $x1
                    . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '">'
                    . $gradientStops . '</linearGradient>';
            } else {
                $centerX = max(0, min(100, (int) ($style['plateGradientCenterXPercent'] ?? 50)));
                $centerY = max(0, min(100, (int) ($style['plateGradientCenterYPercent'] ?? 50)));
                $radius = max(25, min(150, (int) ($style['plateGradientRadiusPercent'] ?? 75)));
                $plateGradientDefinition = '<radialGradient id="plate-fill-gradient" cx="' . $centerX
                    . '%" cy="' . $centerY . '%" r="' . $radius . '%">'
                    . $gradientStops . '</radialGradient>';
            }
            $plateColor = 'url(#plate-fill-gradient)';
        }
        $plateBorderColor = $plateColorMode === 'custom'
            ? self::StyleColor($style, 'plateBorderColor', $palette['border'])
            : $palette['border'];
        $plateBorderWidth = 2.0 * max(50, min(150, (int) ($style['plateBorderWidthPercent'] ?? 100))) / 100;
        $plate = self::Plate($design, $plateShape, $style);
        [$plateBackgroundDefinition, $plateBackground] = self::PlateBackground(
            $design,
            $plateShape,
            $style,
            $plateBackgroundFit
        );
        $plateOutline = $plateBackground === '' ? '' : self::Plate($design, $plateShape, $style, 'outline');
        $rawValue = self::FormatNumber($value, $decimals, $language);
        $rawUnit = trim($unit);
        $rawTitle = trim($title);
        $formattedValue = SVGPreviewHelper::escape($rawValue);
        $formattedUnit = SVGPreviewHelper::escape($rawUnit);
        $formattedTitle = SVGPreviewHelper::escape($rawTitle);
        $gaugeOffsetXValue = (float) $design['gaugeOffsetX'];
        $valueElement = !$design['showValue']
            ? ''
            : '<text x="' . self::Coordinate(
                self::CENTER_X + $gaugeOffsetXValue + (float) $design['valueOffsetX']
            )
                . '" y="' . self::Coordinate($design['detailY'])
                . '" class="value"><tspan class="value-number">' . $formattedValue . '</tspan>'
                . (!$design['showUnit'] || $formattedUnit === ''
                    ? ''
                    : '<tspan class="value-unit" dx="8">' . $formattedUnit . '</tspan>')
                . '</text>';
        $ariaLabel = SVGPreviewHelper::escape(trim(
            ($rawTitle === '' ? '' : $rawTitle . ': ')
            . $rawValue
            . ($rawUnit === '' ? '' : ' ' . $rawUnit)
        ));
        $titleElement = $formattedTitle === '' || !$design['showTitle']
            ? ''
            : '<text x="' . self::Coordinate(
                self::CENTER_X + $gaugeOffsetXValue + (float) $design['titleOffsetX']
            )
                . '" y="' . self::Coordinate($design['titleY']) . '" class="title">' . $formattedTitle . '</text>';
        $progressElement = $progressPath === ''
            ? ''
            : '<path d="' . $progressPath . '" class="progress' . ($preset === 'speed' ? ' speed-progress-shadow' : '')
                . ((bool) ($design['progressShadow'] ?? false) ? ' effect-shadow' : '')
                . '" style="stroke-width:'
                . self::Coordinate($design['progressWidth']) . 'px"/>';
        $detailBackground = $design['detailBox'] && $design['showValue']
            ? '<rect x="' . self::Coordinate(238.0 + $gaugeOffsetXValue + (float) $design['valueOffsetX'])
                . '" y="' . self::Coordinate((float) $design['detailY'] - 39.0)
                . '" width="244" height="58" rx="' . self::Coordinate(10.0 * max(50, min(150, (int) ($style['detailCornerRadiusPercent'] ?? 100))) / 100)
                . '" class="detail-box' . ((bool) ($style['detailShadow'] ?? false) ? ' detail-shadow' : '') . '"/>'
            : '';
        $gaugeOffsetX = self::Coordinate((float) $design['gaugeOffsetX']);
        $detailBackgroundColor = ($style['detailColorMode'] ?? 'theme') === 'custom'
            ? self::StyleColor($style, 'detailBackgroundColor', $palette['surface'])
            : $palette['surface'];
        $detailBorderColor = ($style['detailColorMode'] ?? 'theme') === 'custom'
            ? self::StyleColor($style, 'detailBorderColor', $detailBorderColor)
            : $detailBorderColor;
        $detailBorderWidth = 2.0 * max(50, min(150, (int) ($style['detailBorderWidthPercent'] ?? 100))) / 100;
        $effectShadow = 'url(#effect-shadow)';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="400" viewBox="0 0 720 400" role="img" aria-label="{$ariaLabel}" data-preset="{$preset}" data-theme="{$theme}" data-arc-mode="{$arcMode}" data-start-position="{$startPosition}" data-end-position="{$endPosition}" data-pointer-length-percent="{$pointerLengthPercent}" data-major-splits="{$design['majorSplits']}">
  <defs><filter id="speed-progress-shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="2" dy="2" stdDeviation="4" flood-color="{$progressColor}" flood-opacity="0.45"/></filter><filter id="speed-pointer-shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="2" dy="2" stdDeviation="4" flood-color="{$pointerColor}" flood-opacity="0.45"/></filter><filter id="effect-shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="2" dy="2" stdDeviation="4" flood-color="{$pointerColor}" flood-opacity="0.45"/></filter><filter id="plate-shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.35"/></filter>{$plateGradientDefinition}{$plateBackgroundDefinition}</defs>
  <style>
    .surface{fill:{$palette['background']}}.plate{fill:{$plateColor};stroke:{$plateBorderColor};stroke-width:{$plateBorderWidth}}.plate-outline{fill:none;stroke:{$plateBorderColor};stroke-width:{$plateBorderWidth}}.plate-shadow{filter:url(#plate-shadow)}.theme-track-segment{fill:none;stroke-linecap:round;stroke-linejoin:round}.progress{fill:none;stroke:{$progressColor};stroke-linecap:round;stroke-linejoin:round}.speed-progress-shadow{filter:url(#speed-progress-shadow)}.speed-pointer-shadow{filter:url(#speed-pointer-shadow)}.effect-shadow{filter:{$effectShadow}}.detail-shadow{filter:{$effectShadow}}.minor{stroke:{$minorColor};stroke-width:1}.major{stroke:{$majorColor};stroke-width:2}.axis{fill:{$scaleColor};font-size:{$design['axisFontSize']}px;font-family:'Segoe UI',Arial,sans-serif;text-anchor:middle;dominant-baseline:middle}.pointer,.speed-pointer{fill:{$pointerColor}}.anchor{fill:{$anchorColor};stroke:{$anchorBorderColor};stroke-width:{$anchorBorderWidth}}.detail-box{fill:{$detailBackgroundColor};stroke:{$detailBorderColor};stroke-width:{$detailBorderWidth}}.value{fill:{$valueColor};font-family:'Segoe UI',Arial,sans-serif;text-anchor:middle}.value-number{font-size:{$design['valueFontSize']}px;font-weight:{$valueFontWeight}}.value-unit{fill:{$unitColor};font-size:{$design['unitFontSize']}px;font-weight:{$unitFontWeight}}.title{fill:{$titleColor};font-size:{$design['titleFontSize']}px;font-family:'Segoe UI',Arial,sans-serif;text-anchor:middle}svg[data-preset="speed"] .minor{stroke-width:2}svg[data-preset="speed"] .major{stroke-width:3}
  </style>
  <rect class="surface" width="720" height="400" rx="12"/>
  <g transform="translate({$gaugeOffsetX} 0)">
  {$plate}
  {$plateBackground}
  {$plateOutline}
  {$trackElements}
  {$progressElement}
  {$ticks}
  {$pointer}
  {$anchor}
  </g>
  {$detailBackground}
  {$valueElement}
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
     *     tickDistance: float,
     *     tickLength: float,
     *     splitLength: float,
     *     axisFontSize: float,
     *     valueFontSize: float,
     *     unitFontSize: float,
     *     titleFontSize: float,
     *     pointerWidth: float,
     *     detailY: float,
     *     titleY: float,
     *     showProgress: bool,
     *     showMinorTicks: bool,
     *     detailBox: bool,
     *     speedPointer: bool,
     *     majorSplits: int,
     *     minorSplits: int
     * }
     */
    private static function PresetDesign(string $preset, float $minimum, float $maximum): array
    {
        return match ($preset) {
            'basic' => [
                'startAngle'      => 210.0,
                'endAngle'        => -30.0,
                'centerY'         => 196.0,
                'radius'          => 132.0,
                'labelRadius'     => 97.0,
                'pointerLength'   => 102.0,
                'lineWidth'       => 16.0,
                'tickDistance'    => 4.0,
                'tickLength'      => 5.0,
                'splitLength'     => 10.0,
                'axisFontSize'    => 14.0,
                'valueFontSize'   => 38.0,
                'unitFontSize'    => 38.0,
                'titleFontSize'   => 18.0,
                'pointerWidth'    => 14.0,
                'detailY'         => 294.0,
                'titleY'          => 352.0,
                'showProgress'    => false,
                'showMinorTicks'  => true,
                'detailBox'       => false,
                'speedPointer'    => false,
                'majorSplits'     => 10,
                'minorSplits'     => 5
            ],
            'progress' => [
                'startAngle'      => 210.0,
                'endAngle'        => -30.0,
                'centerY'         => 190.0,
                'radius'          => 132.0,
                'labelRadius'     => 94.0,
                'pointerLength'   => 94.0,
                'lineWidth'       => 20.0,
                'tickDistance'    => 4.0,
                'tickLength'      => 5.0,
                'splitLength'     => 14.0,
                'axisFontSize'    => 14.0,
                'valueFontSize'   => 46.0,
                'unitFontSize'    => 46.0,
                'titleFontSize'   => 18.0,
                'pointerWidth'    => 14.0,
                'detailY'         => 306.0,
                'titleY'          => 360.0,
                'showProgress'    => true,
                'showMinorTicks'  => false,
                'detailBox'       => false,
                'speedPointer'    => false,
                'majorSplits'     => 10,
                'minorSplits'     => 5
            ],
            'speed' => [
                'startAngle'      => 180.0,
                'endAngle'        => 0.0,
                'centerY'         => 232.0,
                'radius'          => 142.0,
                'labelRadius'     => 105.0,
                'pointerLength'   => 113.25,
                'lineWidth'       => 18.0,
                'tickDistance'    => 4.0,
                'tickLength'      => 5.0,
                'splitLength'     => 12.0,
                'axisFontSize'    => 18.0,
                'valueFontSize'   => 42.0,
                'unitFontSize'    => 18.0,
                'titleFontSize'   => 18.0,
                'pointerWidth'    => 16.0,
                'detailY'         => 319.0,
                'titleY'          => 370.0,
                'showProgress'    => true,
                'showMinorTicks'  => true,
                'detailBox'       => true,
                'speedPointer'    => true,
                'majorSplits'     => self::ResolveSpeedSplitNumber($minimum, $maximum),
                'minorSplits'     => 2
            ],
            default => [
                'startAngle'      => 210.0,
                'endAngle'        => -30.0,
                'centerY'         => 196.0,
                'radius'          => 132.0,
                'labelRadius'     => 97.0,
                'pointerLength'   => 98.0,
                'lineWidth'       => 18.0,
                'tickDistance'    => 4.0,
                'tickLength'      => 5.0,
                'splitLength'     => 10.0,
                'axisFontSize'    => 14.0,
                'valueFontSize'   => 38.0,
                'unitFontSize'    => 38.0,
                'titleFontSize'   => 18.0,
                'pointerWidth'    => 14.0,
                'detailY'         => 294.0,
                'titleY'          => 352.0,
                'showProgress'    => true,
                'showMinorTicks'  => true,
                'detailBox'       => false,
                'speedPointer'    => false,
                'majorSplits'     => 10,
                'minorSplits'     => 5
            ]
        };
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, int> $style
     * @return array<string, float|bool|int>
     */
    private static function ApplyStyleScales(array $design, array $style): array
    {
        $labelGap = (float) $design['radius']
            - (float) $design['lineWidth'] / 2.0
            - (float) $design['tickDistance']
            - (float) $design['splitLength']
            - (float) $design['labelRadius'];
        foreach ([
            'scaleFontSizePercent'   => 'axisFontSize',
            'valueFontSizePercent'   => 'valueFontSize',
            'unitFontSizePercent'    => 'unitFontSize',
            'titleFontSizePercent'   => 'titleFontSize',
            'ringWidthPercent'       => 'lineWidth',
            'pointerWidthPercent'    => 'pointerWidth',
            'pointerLengthPercent'   => 'pointerLength',
            'minorTickLengthPercent' => 'tickLength',
            'majorTickLengthPercent' => 'splitLength'
        ] as $styleName => $designName) {
            $percent = max(50, min(150, (int) ($style[$styleName] ?? 100)));
            $design[$designName] = (float) $design[$designName] * $percent / 100;
        }
        $design['labelRadius'] = (float) $design['radius']
            - (float) $design['lineWidth'] / 2.0
            - (float) $design['tickDistance']
            - (float) $design['splitLength']
            - $labelGap;

        return $design;
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     * @return array<string, float|bool|int|string>
     */
    private static function ApplyAdvancedDesign(array $design, array $style): array
    {
        $visibility = static function (mixed $mode, bool $preset): bool
        {
            return match ((string) $mode) {
                'show'  => true,
                'hide'  => false,
                default => $preset
            };
        };
        $radiusScale = max(50, min(150, (int) ($style['gaugeRadiusPercent'] ?? 100))) / 100;
        $design['radius'] = (float) $design['radius'] * $radiusScale;
        $design['pointerLength'] = (float) $design['pointerLength'] * $radiusScale;
        $gaugeOffsetY = max(-50, min(50, (int) ($style['gaugeOffsetYPercent'] ?? 0))) * 2.0;
        $design['centerY'] = (float) $design['centerY'] + $gaugeOffsetY;
        $design['detailY'] = (float) $design['detailY'] + $gaugeOffsetY
            + max(-100, min(100, (int) ($style['valueOffsetYPercent'] ?? 0)));
        $design['titleY'] = (float) $design['titleY'] + $gaugeOffsetY
            + max(-100, min(100, (int) ($style['titleOffsetYPercent'] ?? 0)));
        $design['gaugeOffsetX'] = max(-50, min(50, (int) ($style['gaugeOffsetXPercent'] ?? 0))) * 2.0;
        $design['valueOffsetX'] = max(-100, min(100, (int) ($style['valueOffsetXPercent'] ?? 0)));
        $design['titleOffsetX'] = max(-100, min(100, (int) ($style['titleOffsetXPercent'] ?? 0)));
        $design['majorSplits'] = max(2, min(24, (int) ($style['majorSplitCount'] ?? 0) ?: (int) $design['majorSplits']));
        $design['minorSplits'] = max(1, min(10, (int) ($style['minorSplitCount'] ?? 0) ?: (int) $design['minorSplits']));
        $baseTickDistance = (float) $design['tickDistance'];
        $design['tickDistance'] = $baseTickDistance
            * max(50, min(150, (int) ($style['minorTickDistancePercent'] ?? 100))) / 100;
        $majorDistanceScale = max(50, min(150, (int) ($style['majorTickDistancePercent'] ?? 100))) / 100;
        $design['majorTickDistance'] = $baseTickDistance * $majorDistanceScale;
        $labelDistanceScale = max(50, min(150, (int) ($style['scaleLabelDistancePercent'] ?? 100))) / 100;
        $design['labelRadius'] = (float) $design['radius']
            - ((float) $design['radius'] - (float) $design['labelRadius']) * $labelDistanceScale;
        $design['showPointer'] = $visibility($style['pointerVisibility'] ?? 'preset', true);
        $design['showProgress'] = $visibility($style['progressVisibility'] ?? 'preset', (bool) $design['showProgress']);
        $design['showRing'] = $visibility($style['ringVisibility'] ?? 'preset', true);
        $design['showMinorTicks'] = $visibility(
            $style['minorTicksVisibility'] ?? 'preset',
            (bool) $design['showMinorTicks']
        );
        $design['showMajorTicks'] = $visibility($style['majorTicksVisibility'] ?? 'preset', true);
        $design['showScaleLabels'] = $visibility($style['scaleLabelsVisibility'] ?? 'preset', true);
        $design['showValue'] = $visibility($style['valueVisibility'] ?? 'preset', true);
        $design['showUnit'] = $visibility($style['unitVisibility'] ?? 'preset', true);
        $design['showTitle'] = $visibility($style['titleVisibility'] ?? 'preset', true);
        $design['detailBox'] = $visibility($style['detailBoxVisibility'] ?? 'preset', (bool) $design['detailBox']);
        $design['labelRotation'] = (string) ($style['scaleLabelRotation'] ?? 'horizontal');
        $design['counterclockwise'] = ($style['gaugeDirection'] ?? 'clockwise') === 'counterclockwise';
        $design['ringShadow'] = (bool) ($style['ringShadow'] ?? false);
        $design['pointerShadow'] = (bool) ($style['pointerShadow'] ?? false);
        $design['progressShadow'] = (bool) ($style['progressShadow'] ?? false);
        $design['anchorShadow'] = (bool) ($style['anchorShadow'] ?? false);
        $design['progressWidth'] = (float) $design['lineWidth']
            * max(50, min(150, (int) ($style['progressWidthPercent'] ?? 100))) / 100;

        return $design;
    }

    /**
     * Converts the user-facing clock position (0° at the top, clockwise) to
     * the mathematical angles used by the SVG preview.
     *
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     * @return array<string, float|bool|int>
     */
    private static function ApplyArcDesign(array $design, array $style): array
    {
        $mode = (string) ($style['arcMode'] ?? 'preset');
        if ($mode === 'preset') {
            return $design;
        }

        $startPosition = self::NormalizePosition($style['startPosition'] ?? 0.0);
        $sweep = match ($mode) {
            'full'          => 360.0,
            'three-quarter' => 270.0,
            'half'          => 180.0,
            'quarter'       => 90.0,
            'custom'        => fmod(
                self::NormalizePosition($style['endPosition'] ?? 90.0) - $startPosition + 360.0,
                360.0
            ),
            default         => 0.0
        };
        if ($sweep <= 0.0) {
            return $design;
        }

        $design['startAngle'] = 90.0 - $startPosition;
        $design['endAngle'] = (float) $design['startAngle'] - $sweep;

        return $design;
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     */
    private static function Plate(array $design, string $shape, array $style, string $role = 'plate'): string
    {
        if ($shape === 'hidden') {
            return '';
        }

        $sizeScale = max(50, min(150, (int) ($style['plateSizePercent'] ?? 100))) / 100;
        $arcRadius = ((float) $design['radius'] + (float) $design['lineWidth'] / 2.0 + 10.0) * $sizeScale;
        $circleRadius = max(
            $arcRadius,
            (abs((float) $design['detailY'] - (float) $design['centerY'])
                + ((bool) $design['detailBox'] ? 29.0 : (float) $design['valueFontSize'] * 0.6)
                + 10.0) * $sizeScale,
            (abs((float) $design['titleY'] - (float) $design['centerY'])
                + (float) $design['titleFontSize']
                + 8.0) * $sizeScale
        );
        $attributes = match ($role) {
            'clip'    => '',
            'outline' => ' class="plate-outline"',
            default   => ' class="plate' . ((bool) ($style['plateShadow'] ?? false) ? ' plate-shadow' : '')
                . '" data-plate-shape="' . $shape . '"'
        };
        if ($shape === 'circle') {
            return '<circle cx="360" cy="' . self::Coordinate((float) $design['centerY'])
                . '" r="' . self::Coordinate($circleRadius) . '"' . $attributes . '/>';
        }

        if (abs((float) $design['startAngle'] - (float) $design['endAngle']) >= 359.999) {
            return '<circle cx="360" cy="' . self::Coordinate((float) $design['centerY'])
                . '" r="' . self::Coordinate($arcRadius) . '"' . $attributes . '/>';
        }

        $segments = max(12, (int) ceil(abs((float) $design['startAngle'] - (float) $design['endAngle']) / 5.0));
        $path = 'M360 ' . self::Coordinate((float) $design['centerY']);
        for ($index = 0; $index <= $segments; ++$index) {
            $fraction = $index / $segments;
            $angle = (float) $design['startAngle']
                + ((float) $design['endAngle'] - (float) $design['startAngle']) * $fraction;
            $point = self::Point($arcRadius, $angle, (float) $design['centerY']);
            $path .= ' L' . self::Coordinate($point[0]) . ' ' . self::Coordinate($point[1]);
        }

        return '<path d="' . $path . ' Z"' . $attributes . '/>';
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     * @return array{string, string}
     */
    private static function PlateBackground(
        array $design,
        string $shape,
        array $style,
        string $fit
    ): array {
        if ($shape === 'hidden' || !(bool) ($style['plateBackgroundEnabled'] ?? false)) {
            return ['', ''];
        }

        $image = (string) ($style['plateBackgroundImage'] ?? '');
        $aspectRatio = (float) ($style['plateBackgroundAspectRatio'] ?? 0.0);
        if (preg_match('/^data:image\/svg\+xml;base64,[A-Za-z0-9+\/=]+$/D', $image) !== 1
            || !is_finite($aspectRatio)
            || $aspectRatio <= 0.0) {
            throw new InvalidArgumentException('A validated SVG plate background is required.');
        }

        $sizeScale = max(50, min(150, (int) ($style['plateSizePercent'] ?? 100))) / 100;
        $arcRadius = ((float) $design['radius'] + (float) $design['lineWidth'] / 2.0 + 10.0) * $sizeScale;
        $circleRadius = max(
            $arcRadius,
            (abs((float) $design['detailY'] - (float) $design['centerY'])
                + ((bool) $design['detailBox'] ? 29.0 : (float) $design['valueFontSize'] * 0.6)
                + 10.0) * $sizeScale,
            (abs((float) $design['titleY'] - (float) $design['centerY'])
                + (float) $design['titleFontSize']
                + 8.0) * $sizeScale
        );
        $centerX = self::CENTER_X;
        $centerY = (float) $design['centerY'];
        $sweep = abs((float) $design['startAngle'] - (float) $design['endAngle']);
        $renderCircle = $shape === 'circle' || $sweep >= 359.999;
        $radius = $shape === 'circle' ? $circleRadius : $arcRadius;
        if ($renderCircle) {
            $minimumX = $centerX - $radius;
            $minimumY = $centerY - $radius;
            $boxWidth = 2.0 * $radius;
            $boxHeight = 2.0 * $radius;
        } else {
            $points = [[$centerX, $centerY]];
            $segments = max(12, (int) ceil($sweep / 5.0));
            for ($index = 0; $index <= $segments; ++$index) {
                $fraction = $index / $segments;
                $angle = (float) $design['startAngle']
                    + ((float) $design['endAngle'] - (float) $design['startAngle']) * $fraction;
                $points[] = self::Point($radius, $angle, $centerY);
            }
            $xValues = array_column($points, 0);
            $yValues = array_column($points, 1);
            $minimumX = min($xValues);
            $minimumY = min($yValues);
            $boxWidth = max($xValues) - $minimumX;
            $boxHeight = max($yValues) - $minimumY;
        }

        $imageWidth = $boxWidth;
        $imageHeight = $boxHeight;
        $boxAspectRatio = $boxWidth / max(1.0, $boxHeight);
        if ($fit === 'contain') {
            if ($boxAspectRatio > $aspectRatio) {
                $imageWidth = $boxHeight * $aspectRatio;
            } else {
                $imageHeight = $boxWidth / $aspectRatio;
            }
        } elseif ($fit === 'cover') {
            if ($boxAspectRatio > $aspectRatio) {
                $imageHeight = $boxWidth / $aspectRatio;
            } else {
                $imageWidth = $boxHeight * $aspectRatio;
            }
        }
        $backgroundScale = max(25, min(200, (int) ($style['plateBackgroundSizePercent'] ?? 100))) / 100;
        $imageWidth *= $backgroundScale;
        $imageHeight *= $backgroundScale;
        $imageCenterX = $minimumX + $boxWidth / 2.0
            + $boxWidth * max(-100, min(100, (int) ($style['plateBackgroundOffsetXPercent'] ?? 0))) / 100.0;
        $imageCenterY = $minimumY + $boxHeight / 2.0
            + $boxHeight * max(-100, min(100, (int) ($style['plateBackgroundOffsetYPercent'] ?? 0))) / 100.0;
        $rotation = max(-180.0, min(180.0, (float) ($style['plateBackgroundRotation'] ?? 0.0)));
        $opacity = max(0, min(100, (int) ($style['plateBackgroundOpacityPercent'] ?? 100))) / 100;
        $definition = '<clipPath id="plate-background-clip">'
            . self::Plate($design, $shape, $style, 'clip') . '</clipPath>';
        $element = '<image href="' . SVGPreviewHelper::escape($image)
            . '" x="' . self::Coordinate($imageCenterX - $imageWidth / 2.0)
            . '" y="' . self::Coordinate($imageCenterY - $imageHeight / 2.0)
            . '" width="' . self::Coordinate($imageWidth)
            . '" height="' . self::Coordinate($imageHeight)
            . '" opacity="' . self::Coordinate($opacity)
            . '" preserveAspectRatio="none" clip-path="url(#plate-background-clip)" transform="rotate('
            . self::Coordinate($rotation) . ' ' . self::Coordinate($imageCenterX) . ' '
            . self::Coordinate($imageCenterY) . ')"/>';

        return [$definition, $element];
    }

    /** @param array<string, float|bool|int> $design */
    private static function ArcPath(float $ratio, array $design): string
    {
        return self::ArcSegmentPath(0.0, $ratio, $design);
    }

    /**
     * @param list<array{float,string}> $segments
     * @param array<string, float|bool|int> $design
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
                . '" class="theme-track-segment' . ((bool) ($design['ringShadow'] ?? false) ? ' effect-shadow' : '')
                . '" style="stroke:' . SVGPreviewHelper::escape($segment[1])
                . ';stroke-width:' . self::Coordinate($design['lineWidth']) . 'px"/>';
            $start = $end;
        }

        return implode("\n  ", $elements);
    }

    /** @param array<string, float|bool|int> $design */
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

    /** @param array<string, float|bool|int> $design */
    private static function Ticks(float $minimum, float $maximum, string $language, array $design): string
    {
        $elements = [];
        $majorSplits = (int) $design['majorSplits'];
        $minorSplits = (int) $design['minorSplits'];
        $totalSplits = $majorSplits * $minorSplits;
        for ($index = 0; $index <= $totalSplits; ++$index) {
            $fraction = $index / $totalSplits;
            $major = $index % $minorSplits === 0;
            if ((!$major && !$design['showMinorTicks']) || ($major && !$design['showMajorTicks'])) {
                continue;
            }

            $angle = self::Angle($fraction, $design);
            $outerRadius = (float) $design['radius']
                - (float) $design['lineWidth'] / 2.0
                - (float) ($major ? $design['majorTickDistance'] : $design['tickDistance']);
            $outer = self::Point($outerRadius, $angle, (float) $design['centerY']);
            $lineLength = $major ? (float) $design['splitLength'] : (float) $design['tickLength'];
            $inner = self::Point($outerRadius - $lineLength, $angle, (float) $design['centerY']);
            $class = $major ? 'major' : 'minor';
            $elements[] = '<line x1="' . self::Coordinate($inner[0])
                . '" y1="' . self::Coordinate($inner[1])
                . '" x2="' . self::Coordinate($outer[0])
                . '" y2="' . self::Coordinate($outer[1])
                . '" class="' . $class . '"/>';

            if (!$major || !$design['showScaleLabels']) {
                continue;
            }

            $label = self::Point(
                (float) $design['labelRadius'],
                $angle,
                (float) $design['centerY']
            );
            $labelValue = $minimum + ($maximum - $minimum) * $fraction;
            $rotation = match ($design['labelRotation'] ?? 'horizontal') {
                'tangential' => 90.0 - $angle,
                'radial'     => -$angle,
                default      => 0.0
            };
            $elements[] = '<text x="' . self::Coordinate($label[0])
                . '" y="' . self::Coordinate($label[1])
                . ($rotation === 0.0 ? '' : ' transform="rotate(' . self::Coordinate($rotation) . ' '
                    . self::Coordinate($label[0]) . ' ' . self::Coordinate($label[1]) . ')"')
                . '" class="axis" data-label-index="' . $index . '">'
                . SVGPreviewHelper::escape(self::FormatAxisNumber($labelValue, $language))
                . '</text>';
        }

        return implode("\n  ", $elements);
    }

    /** @param array<string, float|bool|int> $design */
    private static function Pointer(float $ratio, array $design, string $shape, array $style): string
    {
        if ($shape === 'custom') {
            return self::CustomPointer($ratio, $design, $style);
        }
        if ($shape === 'preset' && $design['speedPointer']) {
            return self::SpeedPointer($ratio, $design);
        }

        $angle = self::Angle($ratio, $design);
        $centerY = (float) $design['centerY'];
        $radians = deg2rad($angle);
        $halfPointerWidth = (float) $design['pointerWidth'] / 2.0;
        $length = (float) $design['pointerLength'];
        $point = static function (float $axial, float $perpendicular) use ($radians, $centerY): array
        {
            return [
                self::CENTER_X + cos($radians) * $axial + sin($radians) * $perpendicular,
                $centerY - sin($radians) * $axial + cos($radians) * $perpendicular
            ];
        };
        $points = match ($shape) {
            'line' => [
                $point($length, -$halfPointerWidth / 2.0),
                $point($length, $halfPointerWidth / 2.0),
                $point(-10.0, $halfPointerWidth / 2.0),
                $point(-10.0, -$halfPointerWidth / 2.0)
            ],
            'arrow' => [
                $point($length, 0.0),
                $point($length * 0.78, $halfPointerWidth),
                $point($length * 0.78, $halfPointerWidth * 0.32),
                $point(-10.0, $halfPointerWidth * 0.32),
                $point(-10.0, -$halfPointerWidth * 0.32),
                $point($length * 0.78, -$halfPointerWidth * 0.32),
                $point($length * 0.78, -$halfPointerWidth)
            ],
            default => [
                $point($length, 0.0),
                $point(0.0, $halfPointerWidth),
                $point(0.0, -$halfPointerWidth)
            ]
        };
        $serialized = array_map(
            static fn (array $coordinates): string => self::Coordinate($coordinates[0])
                . ',' . self::Coordinate($coordinates[1]),
            $points
        );

        return '<polygon points="' . implode(' ', $serialized) . '" class="pointer'
            . ($design['speedPointer'] ? ' speed-pointer-shadow' : '')
            . ((bool) ($design['pointerShadow'] ?? false) ? ' effect-shadow' : '') . '" data-pointer-shape="'
            . SVGPreviewHelper::escape($shape) . '"/>';
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     */
    private static function CustomPointer(float $ratio, array $design, array $style): string
    {
        $path = (string) ($style['pointerPath'] ?? '');
        $viewBox = (string) ($style['pointerViewBox'] ?? '');
        if ($path === ''
            || preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $path) !== 1
            || preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?(?:\s+[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?){3}$/D', $viewBox) !== 1) {
            throw new InvalidArgumentException('A validated custom SVG pointer is required.');
        }

        $angle = self::Angle($ratio, $design);
        $rotation = 90.0 - $angle;
        $centerY = (float) $design['centerY'];
        $length = (float) $design['pointerLength'];
        [$minimumX, $minimumY, $viewBoxWidth, $viewBoxHeight] = array_map('floatval', explode(' ', $viewBox));
        $widthScale = max(50, min(150, (int) ($style['pointerWidthPercent'] ?? 100))) / 100;
        $width = max(2.0, $length * $viewBoxWidth / $viewBoxHeight * $widthScale);
        $hasPivot = isset($style['pointerPivotX'], $style['pointerPivotY']);
        $pivotX = max(
            $minimumX,
            min($minimumX + $viewBoxWidth, (float) ($style['pointerPivotX'] ?? $minimumX + $viewBoxWidth / 2.0))
        );
        $pivotY = max(
            $minimumY,
            min($minimumY + $viewBoxHeight, (float) ($style['pointerPivotY'] ?? $minimumY + $viewBoxHeight))
        );
        $x = self::CENTER_X - ($pivotX - $minimumX) / $viewBoxWidth * $width;
        $y = $centerY - ($pivotY - $minimumY) / $viewBoxHeight * $length;
        $shadowClass = $design['speedPointer'] ? ' speed-pointer-shadow' : '';
        $shadowClass .= (bool) ($design['pointerShadow'] ?? false) ? ' effect-shadow' : '';

        return '<g transform="rotate(' . self::Coordinate($rotation) . ' 360 '
            . self::Coordinate($centerY) . ')"><svg x="' . self::Coordinate($x)
            . '" y="' . self::Coordinate($y) . '" width="' . self::Coordinate($width)
            . '" height="' . self::Coordinate($length) . '" viewBox="' . SVGPreviewHelper::escape($viewBox)
            . '" preserveAspectRatio="none" overflow="visible" data-pointer-pivot-x="'
            . self::Coordinate($pivotX) . '" data-pointer-pivot-y="' . self::Coordinate($pivotY)
            . '"><path d="' . SVGPreviewHelper::escape($path)
            . '" class="pointer' . $shadowClass . '" data-pointer-shape="custom"/></svg></g>';
    }

    /**
     * @param array<string, float|bool|int> $design
     * @param array<string, mixed> $style
     */
    private static function Anchor(array $design, string $pointerShape, string $anchorShape, array $style): string
    {
        $pointerDefaultShowsAnchor = $pointerShape === 'custom'
            ? (isset($style['pointerShowAnchor'])
                ? (bool) $style['pointerShowAnchor']
                : !isset($style['pointerPivotX'], $style['pointerPivotY']))
            : !($pointerShape === 'preset' && (bool) $design['speedPointer']);
        if ($anchorShape === 'hidden' || ($anchorShape === 'preset' && !$pointerDefaultShowsAnchor)) {
            return '';
        }

        $size = 22.0 * max(50, min(150, (int) ($style['anchorSizePercent'] ?? 100))) / 100;
        $halfSize = $size / 2.0;
        $centerY = (float) $design['centerY'];
        if ($anchorShape === 'custom') {
            $path = (string) ($style['anchorPath'] ?? '');
            $viewBox = (string) ($style['anchorViewBox'] ?? '');
            if ($path === ''
                || preg_match('/^[MmZzLlHhVvCcSsQqTtAa0-9eE+.,\-\s]+$/D', $path) !== 1
                || preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?(?:\s+[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?){3}$/D', $viewBox) !== 1) {
                throw new InvalidArgumentException('A validated custom SVG anchor is required.');
            }

            return '<svg x="' . self::Coordinate(self::CENTER_X - $halfSize)
                . '" y="' . self::Coordinate($centerY - $halfSize)
                . '" width="' . self::Coordinate($size) . '" height="' . self::Coordinate($size)
                . '" viewBox="' . SVGPreviewHelper::escape($viewBox)
                . '" preserveAspectRatio="xMidYMid meet" overflow="visible" data-anchor-shape="custom"><path d="'
                . SVGPreviewHelper::escape($path) . '" class="anchor'
                . ((bool) ($design['anchorShadow'] ?? false) ? ' effect-shadow' : '')
                . '" vector-effect="non-scaling-stroke"/></svg>';
        }

        return '<circle cx="360" cy="' . self::Coordinate($centerY) . '" r="'
            . self::Coordinate($halfSize) . '" class="anchor'
            . ((bool) ($design['anchorShadow'] ?? false) ? ' effect-shadow' : '')
            . '" data-anchor-shape="'
            . SVGPreviewHelper::escape($anchorShape) . '"'
            . ($anchorShape === 'ring' ? ' style="fill:none"' : '') . '/>';
    }

    /** @param array<string, float|bool|int> $design */
    private static function SpeedPointer(float $ratio, array $design): string
    {
        $angle = self::Angle($ratio, $design);
        $radians = deg2rad($angle);
        $offsetY = (float) $design['radius'] * 0.05;
        $centerY = (float) $design['centerY'] + $offsetY;
        $tip = self::Point((float) $design['pointerLength'], $angle, $centerY);
        $perpendicularX = sin($radians);
        $perpendicularY = cos($radians);
        $pointerScale = (float) $design['pointerWidth'] / 16.0;
        $points = [
            [$tip[0] - $perpendicularX * 2.0 * $pointerScale, $tip[1] - $perpendicularY * 2.0 * $pointerScale],
            [$tip[0] + $perpendicularX * 2.0 * $pointerScale, $tip[1] + $perpendicularY * 2.0 * $pointerScale],
            [self::CENTER_X + $perpendicularX * 8.0 * $pointerScale, $centerY + $perpendicularY * 8.0 * $pointerScale],
            [self::CENTER_X - $perpendicularX * 8.0 * $pointerScale, $centerY - $perpendicularY * 8.0 * $pointerScale]
        ];
        $serialized = array_map(
            static fn (array $point): string => self::Coordinate($point[0]) . ',' . self::Coordinate($point[1]),
            $points
        );

        return '<polygon points="' . implode(' ', $serialized) . '" class="speed-pointer speed-pointer-shadow'
            . ((bool) ($design['pointerShadow'] ?? false) ? ' effect-shadow' : '') . '"/>';
    }

    private static function ResolveSpeedSplitNumber(float $minimum, float $maximum): int
    {
        $range = abs($maximum - $minimum);
        $niceSteps = [1.0, 2.0, 2.5, 3.0, 5.0, 10.0];
        foreach ([12, 10, 8, 6, 5, 4] as $candidate) {
            $step = $range / $candidate;
            $magnitude = 10 ** floor(log10($step));
            $normalized = $step / $magnitude;
            foreach ($niceSteps as $niceStep) {
                if (abs($normalized - $niceStep) < 0.000000001) {
                    return $candidate;
                }
            }
        }

        return 10;
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

    /** @param array<string, float|bool|int> $design */
    private static function Angle(float $ratio, array $design): float
    {
        $startAngle = (float) $design['startAngle'];

        if ((bool) ($design['counterclockwise'] ?? false)) {
            $clockwiseSweep = $startAngle - (float) $design['endAngle'];
            if ($clockwiseSweep <= 0.0) {
                $clockwiseSweep += 360.0;
            }

            if ($clockwiseSweep >= 359.999) {
                return $startAngle + 360.0 * $ratio;
            }

            return $startAngle + (360.0 - $clockwiseSweep) * $ratio;
        }

        return $startAngle + ((float) $design['endAngle'] - $startAngle) * $ratio;
    }

    private static function Coordinate(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private static function NormalizePosition(mixed $position): float
    {
        $normalized = fmod((float) $position, 360.0);

        return $normalized < 0.0 ? $normalized + 360.0 : $normalized;
    }

    /** @param array<string, mixed> $style */
    private static function StyleColor(array $style, string $name, string $fallback): string
    {
        $color = strtoupper((string) ($style[$name] ?? ''));

        return preg_match('/^#[0-9A-F]{6}$/', $color) === 1 ? $color : $fallback;
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
