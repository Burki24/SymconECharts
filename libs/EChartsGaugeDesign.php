<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/EChartsSvgImage.php';
require_once __DIR__ . '/EChartsSvgPath.php';

/**
 * Shared, ECharts-specific Gauge design contracts and SVG import adapters.
 *
 * Module properties, preset geometry and configuration forms remain owned by
 * the individual Gauge modules. This class centralizes only design semantics
 * that must behave identically across Gauge families.
 */
final class EChartsGaugeDesign
{
    public const POINTER_SHAPES = ['preset', 'needle', 'line', 'arrow', 'custom'];
    public const POINTER_PIVOT_MODES = ['svg', 'custom'];
    public const PLATE_BACKGROUND_FITS = ['contain', 'cover', 'stretch'];

    public static function ColorToHex(int $color): string
    {
        return sprintf('#%06X', max(0, min(0xFFFFFF, $color)));
    }

    /**
     * @return array{
     *     pointerPath: string,
     *     pointerViewBox: string,
     *     pointerPivotX?: float,
     *     pointerPivotY?: float,
     *     pointerShowAnchor?: bool
     * }
     */
    public static function ImportPointer(
        string $fileData,
        string $pivotMode = 'svg',
        float $pivotXPercent = 50.0,
        float $pivotYPercent = 100.0
    ): array {
        if (!in_array($pivotMode, self::POINTER_PIVOT_MODES, true)) {
            throw new \InvalidArgumentException('The selected SVG pointer pivot mode is not supported.');
        }
        if (!is_finite($pivotXPercent) || !is_finite($pivotYPercent)
            || $pivotXPercent < 0.0 || $pivotXPercent > 100.0
            || $pivotYPercent < 0.0 || $pivotYPercent > 100.0) {
            throw new \InvalidArgumentException('Custom SVG pointer pivot values must be between 0 and 100 percent.');
        }

        $pointer = EChartsSvgPath::Import($fileData);
        $style = ['pointerPath' => $pointer['path'], 'pointerViewBox' => $pointer['viewBox']];
        if ($pivotMode === 'custom') {
            [$minimumX, $minimumY, $width, $height] = array_map('floatval', explode(' ', $pointer['viewBox']));
            $style['pointerPivotX'] = $minimumX + $width * $pivotXPercent / 100.0;
            $style['pointerPivotY'] = $minimumY + $height * $pivotYPercent / 100.0;
            $style['pointerShowAnchor'] = true;
        } elseif (isset($pointer['pivotX'], $pointer['pivotY'])) {
            $style['pointerPivotX'] = $pointer['pivotX'];
            $style['pointerPivotY'] = $pointer['pivotY'];
        }

        return $style;
    }

    /**
     * @return array{
     *     plateBackgroundEnabled: true,
     *     plateBackgroundFit: string,
     *     plateBackgroundSizePercent: int,
     *     plateBackgroundOffsetXPercent: int,
     *     plateBackgroundOffsetYPercent: int,
     *     plateBackgroundOpacityPercent: int,
     *     plateBackgroundRotation: float,
     *     plateBackgroundImage: string,
     *     plateBackgroundAspectRatio: float
     * }
     */
    public static function ImportPlateBackground(
        string $fileData,
        string $fit = 'cover',
        int $sizePercent = 100,
        int $offsetXPercent = 0,
        int $offsetYPercent = 0,
        int $opacityPercent = 100,
        float $rotation = 0.0
    ): array {
        if (!in_array($fit, self::PLATE_BACKGROUND_FITS, true)) {
            throw new \InvalidArgumentException('The selected SVG plate background fitting is not supported.');
        }
        if ($sizePercent < 25 || $sizePercent > 200
            || $offsetXPercent < -100 || $offsetXPercent > 100
            || $offsetYPercent < -100 || $offsetYPercent > 100
            || $opacityPercent < 0 || $opacityPercent > 100) {
            throw new \InvalidArgumentException('Plate SVG background values are outside their supported ranges.');
        }
        if (!is_finite($rotation) || $rotation < -180.0 || $rotation > 180.0) {
            throw new \InvalidArgumentException('Plate SVG background rotation must be between -180 and 180 degrees.');
        }

        $background = EChartsSvgImage::Import($fileData);

        return [
            'plateBackgroundEnabled'        => true,
            'plateBackgroundFit'            => $fit,
            'plateBackgroundSizePercent'    => $sizePercent,
            'plateBackgroundOffsetXPercent' => $offsetXPercent,
            'plateBackgroundOffsetYPercent' => $offsetYPercent,
            'plateBackgroundOpacityPercent' => $opacityPercent,
            'plateBackgroundRotation'       => $rotation,
            'plateBackgroundImage'          => $background['dataUri'],
            'plateBackgroundAspectRatio'    => $background['width'] / $background['height']
        ];
    }
}
