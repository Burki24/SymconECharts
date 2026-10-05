<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/EChartsSvgImage.php';
require_once __DIR__ . '/EChartsSvgPath.php';

/**
 * Shared, ECharts-specific Gauge design contracts and SVG import adapters.
 *
 * Module properties and preset geometry remain owned by the individual Gauge
 * modules. This class centralizes design semantics and reusable source-row
 * controls that must behave identically across Gauge families.
 */
final class EChartsGaugeDesign
{
    public const POINTER_SHAPES = ['preset', 'needle', 'line', 'arrow', 'custom'];
    public const POINTER_PIVOT_MODES = ['svg', 'custom'];
    public const PLATE_BACKGROUND_FITS = ['contain', 'cover', 'stretch'];

    /** @var array<string, int|float|string|bool> */
    private const SOURCE_DESIGN_DEFAULTS = [
        'UseIndividualDesign'           => false,
        'PointerShape'                  => 'preset', 'PointerWidthPercent' => 100, 'PointerLengthPercent' => 100,
        'CustomPointerSVG'              => '', 'CustomPointerPivotMode' => 'svg',
        'CustomPointerPivotXPercent'    => 50.0, 'CustomPointerPivotYPercent' => 100.0,
        'AnchorShape'                   => 'preset', 'AnchorSizePercent' => 100, 'AnchorBorderWidthPercent' => 100,
        'GaugeColorMode'                => 'theme', 'PointerColor' => 0x55CBB5, 'AnchorColor' => 0x55CBB5,
        'AnchorBorderColor'             => 0xF4F5F7, 'RingColor' => 0x45474C, 'ScaleColor' => 0xA7A9AE,
        'ValueColor'                    => 0xF4F5F7, 'TitleColor' => 0xA7A9AE, 'ProgressColor' => 0x55CBB5,
        'RingWidthPercent'              => 100, 'ScaleFontSizePercent' => 100, 'ValueFontSizePercent' => 100,
        'TitleFontSizePercent'          => 100, 'MajorSplitCount' => 0, 'MinorSplitCount' => 0,
        'PlateDesignMode'               => 'preset', 'PlateSizePercent' => 100, 'PlateBorderWidthPercent' => 100,
        'PlateColor'                    => 0x25272B, 'PlateBorderColor' => 0xA5A9B0,
        'PlateBackgroundEnabled'        => false, 'PlateBackgroundSVG' => '', 'PlateBackgroundFit' => 'cover',
        'PlateBackgroundSizePercent'    => 100, 'PlateBackgroundOffsetXPercent' => 0,
        'PlateBackgroundOffsetYPercent' => 0, 'PlateBackgroundOpacityPercent' => 100,
        'PlateBackgroundRotation'       => 0.0
    ];

    /** @return array<string, int|float|string|bool> */
    public static function SourceDesignDefaults(string $prefix = ''): array
    {
        if ($prefix === '') {
            return self::SOURCE_DESIGN_DEFAULTS;
        }

        $defaults = [];
        foreach (self::SOURCE_DESIGN_DEFAULTS as $name => $value) {
            if ($name !== 'UseIndividualDesign') {
                $defaults[$prefix . $name] = $value;
            }
        }

        return $defaults;
    }

    /** @return list<array<string, mixed>> */
    public static function SourceDesignColumns(): array
    {
        $columns = [];
        $defaults = array_merge(
            self::SOURCE_DESIGN_DEFAULTS,
            ['IPSViewUseTileDesign' => true],
            self::SourceDesignDefaults('IPSView')
        );
        foreach ($defaults as $name => $default) {
            $visible = $name === 'UseIndividualDesign';
            $columns[] = [
                'caption' => $visible ? 'Individual design' : $name,
                'name'    => $name,
                'width'   => $visible ? '130px' : '1px',
                'visible' => $visible,
                'save'    => true,
                'add'     => $default
            ];
        }

        return $columns;
    }

    /** @return list<array<string, mixed>> */
    public static function SourceEditorForm(): array
    {
        $percent = static fn (string $name, string $caption): array => [
            'type'    => 'NumberSpinner', 'name' => $name, 'caption' => $caption,
            'minimum' => 50, 'maximum' => 150, 'suffix' => ' %'
        ];

        $form = [
            ['type' => 'SelectVariable', 'name' => 'VariableID', 'caption' => 'Variable', 'validVariableTypes' => [1, 2]],
            ['type' => 'ValidationTextBox', 'name' => 'Label', 'caption' => 'Label'],
            ['type' => 'CheckBox', 'name' => 'UseVariablePresentation', 'caption' => 'Use variable presentation'],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'NumberSpinner', 'name' => 'Minimum', 'caption' => 'Minimum', 'digits' => 3],
                ['type' => 'NumberSpinner', 'name' => 'Maximum', 'caption' => 'Maximum', 'digits' => 3],
                ['type' => 'ValidationTextBox', 'name' => 'Unit', 'caption' => 'Unit'],
                ['type' => 'NumberSpinner', 'name' => 'Decimals', 'caption' => 'Decimal places', 'minimum' => 0, 'maximum' => 6]
            ]],
            ['type' => 'CheckBox', 'name' => 'UseIndividualDesign', 'caption' => 'Use individual Gauge design'],
            ['type' => 'ExpansionPanel', 'caption' => 'Pointer and anchor', 'expanded' => false, 'items' => [
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'PointerShape', 'caption' => 'Pointer shape', 'options' => self::Options(['preset', 'needle', 'line', 'arrow', 'custom'])],
                    ['type' => 'Select', 'name' => 'AnchorShape', 'caption' => 'Hub design', 'options' => self::Options(['preset', 'circle', 'ring', 'none'])]
                ]],
                ['type' => 'RowLayout', 'items' => [$percent('PointerWidthPercent', 'Pointer width'), $percent('PointerLengthPercent', 'Pointer length')]],
                ['type' => 'SelectFile', 'name' => 'CustomPointerSVG', 'caption' => 'Custom pointer SVG', 'extensions' => '.svg'],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'CustomPointerPivotMode', 'caption' => 'SVG pivot', 'options' => self::Options(['svg', 'custom'])],
                    ['type' => 'NumberSpinner', 'name' => 'CustomPointerPivotXPercent', 'caption' => 'Pivot X', 'minimum' => 0, 'maximum' => 100, 'digits' => 1, 'suffix' => ' %'],
                    ['type' => 'NumberSpinner', 'name' => 'CustomPointerPivotYPercent', 'caption' => 'Pivot Y', 'minimum' => 0, 'maximum' => 100, 'digits' => 1, 'suffix' => ' %']
                ]],
                ['type' => 'RowLayout', 'items' => [$percent('AnchorSizePercent', 'Hub size'), $percent('AnchorBorderWidthPercent', 'Hub border')]]
            ]],
            ['type' => 'ExpansionPanel', 'caption' => 'Scale and colors', 'expanded' => false, 'items' => [
                ['type' => 'Select', 'name' => 'GaugeColorMode', 'caption' => 'Colors', 'options' => self::Options(['theme', 'custom'])],
                ['type' => 'RowLayout', 'items' => [$percent('RingWidthPercent', 'Ring width'), $percent('ScaleFontSizePercent', 'Scale font'), $percent('ValueFontSizePercent', 'Value font'), $percent('TitleFontSizePercent', 'Title font')]],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'NumberSpinner', 'name' => 'MajorSplitCount', 'caption' => 'Major divisions', 'minimum' => 0, 'maximum' => 24],
                    ['type' => 'NumberSpinner', 'name' => 'MinorSplitCount', 'caption' => 'Minor divisions', 'minimum' => 0, 'maximum' => 10]
                ]],
                ['type' => 'RowLayout', 'items' => array_map(static fn (string $name): array => ['type' => 'SelectColor', 'name' => $name, 'caption' => $name], ['PointerColor', 'AnchorColor', 'AnchorBorderColor', 'RingColor', 'ScaleColor'])],
                ['type' => 'RowLayout', 'items' => array_map(static fn (string $name): array => ['type' => 'SelectColor', 'name' => $name, 'caption' => $name], ['ValueColor', 'TitleColor', 'ProgressColor'])]
            ]],
            ['type' => 'ExpansionPanel', 'caption' => 'Dial plate', 'expanded' => false, 'items' => [
                ['type' => 'Select', 'name' => 'PlateDesignMode', 'caption' => 'Plate design', 'options' => self::Options(['preset', 'custom', 'hidden'])],
                ['type' => 'RowLayout', 'items' => [$percent('PlateSizePercent', 'Plate size'), $percent('PlateBorderWidthPercent', 'Plate border')]],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'SelectColor', 'name' => 'PlateColor', 'caption' => 'Plate color'],
                    ['type' => 'SelectColor', 'name' => 'PlateBorderColor', 'caption' => 'Plate border color']
                ]],
                ['type' => 'CheckBox', 'name' => 'PlateBackgroundEnabled', 'caption' => 'Use SVG dial background'],
                ['type' => 'SelectFile', 'name' => 'PlateBackgroundSVG', 'caption' => 'Dial background SVG', 'extensions' => '.svg'],
                ['type' => 'Select', 'name' => 'PlateBackgroundFit', 'caption' => 'Fitting', 'options' => self::Options(['contain', 'cover', 'stretch'])],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'NumberSpinner', 'name' => 'PlateBackgroundSizePercent', 'caption' => 'Size', 'minimum' => 25, 'maximum' => 200, 'suffix' => ' %'],
                    ['type' => 'NumberSpinner', 'name' => 'PlateBackgroundOffsetXPercent', 'caption' => 'Offset X', 'minimum' => -100, 'maximum' => 100, 'suffix' => ' %'],
                    ['type' => 'NumberSpinner', 'name' => 'PlateBackgroundOffsetYPercent', 'caption' => 'Offset Y', 'minimum' => -100, 'maximum' => 100, 'suffix' => ' %']
                ]],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'NumberSpinner', 'name' => 'PlateBackgroundOpacityPercent', 'caption' => 'Opacity', 'minimum' => 0, 'maximum' => 100, 'suffix' => ' %'],
                    ['type' => 'NumberSpinner', 'name' => 'PlateBackgroundRotation', 'caption' => 'Rotation', 'minimum' => -180, 'maximum' => 180, 'digits' => 1, 'suffix' => ' °']
                ]]
            ]]
        ];
        $ipsViewItems = self::PrefixControlNames(array_slice($form, 5), 'IPSView');
        $form[] = [
            'type'    => 'CheckBox',
            'name'    => 'IPSViewUseTileDesign',
            'caption' => 'Use tile design for this Gauge in IPSView'
        ];
        foreach ($ipsViewItems as $item) {
            $item['caption'] = 'IPSView ' . (string) ($item['caption'] ?? 'design');
            $form[] = $item;
        }

        return $form;
    }

    /** @param array<string, mixed> $source @return array<string, mixed> */
    public static function StyleFromSource(array $source, string $prefix = ''): array
    {
        $values = [];
        foreach (self::SOURCE_DESIGN_DEFAULTS as $name => $default) {
            $values[$name] = $source[$prefix . $name] ?? $default;
        }
        if (!in_array($values['PointerShape'], self::POINTER_SHAPES, true)
            || !in_array($values['AnchorShape'], ['preset', 'circle', 'ring', 'none'], true)
            || !in_array($values['GaugeColorMode'], ['theme', 'custom'], true)
            || !in_array($values['PlateDesignMode'], ['preset', 'custom', 'hidden'], true)) {
            throw new \InvalidArgumentException('The selected individual Gauge element design is not supported.');
        }
        foreach ([
            'PointerWidthPercent', 'PointerLengthPercent', 'AnchorSizePercent', 'AnchorBorderWidthPercent',
            'RingWidthPercent', 'ScaleFontSizePercent', 'ValueFontSizePercent', 'TitleFontSizePercent',
            'PlateSizePercent', 'PlateBorderWidthPercent'
        ] as $name) {
            if (!is_int($values[$name]) || $values[$name] < 50 || $values[$name] > 150) {
                throw new \InvalidArgumentException('Individual Gauge design percentages must be between 50 and 150.');
            }
        }
        if (!is_int($values['MajorSplitCount']) || $values['MajorSplitCount'] < 0 || $values['MajorSplitCount'] > 24
            || !is_int($values['MinorSplitCount']) || $values['MinorSplitCount'] < 0 || $values['MinorSplitCount'] > 10) {
            throw new \InvalidArgumentException('Individual Gauge scale divisions are invalid.');
        }
        $style = [];
        foreach ($values as $name => $value) {
            if ($name === 'UseIndividualDesign' || str_starts_with($name, 'CustomPointer')
                || str_starts_with($name, 'PlateBackground')) {
                continue;
            }
            $field = match ($name) {
                'GaugeColorMode' => 'colorMode', 'PlateDesignMode' => 'plateMode', default => lcfirst($name)
            };
            $style[$field] = str_ends_with($name, 'Color') ? self::ColorToHex((int) $value) : $value;
        }
        $style['plateBackgroundEnabled'] = (bool) $values['PlateBackgroundEnabled'];
        $style['plateBackgroundFit'] = (string) $values['PlateBackgroundFit'];
        $style['plateBackgroundSizePercent'] = (int) $values['PlateBackgroundSizePercent'];
        $style['plateBackgroundOffsetXPercent'] = (int) $values['PlateBackgroundOffsetXPercent'];
        $style['plateBackgroundOffsetYPercent'] = (int) $values['PlateBackgroundOffsetYPercent'];
        $style['plateBackgroundOpacityPercent'] = (int) $values['PlateBackgroundOpacityPercent'];
        $style['plateBackgroundRotation'] = (float) $values['PlateBackgroundRotation'];
        if ((string) $values['PointerShape'] === 'custom') {
            $style = array_merge($style, self::ImportPointer(
                (string) $values['CustomPointerSVG'],
                (string) $values['CustomPointerPivotMode'],
                (float) $values['CustomPointerPivotXPercent'],
                (float) $values['CustomPointerPivotYPercent']
            ));
        }
        if ((bool) $values['PlateBackgroundEnabled']) {
            $style = array_merge($style, self::ImportPlateBackground(
                (string) $values['PlateBackgroundSVG'],
                (string) $values['PlateBackgroundFit'],
                (int) $values['PlateBackgroundSizePercent'],
                (int) $values['PlateBackgroundOffsetXPercent'],
                (int) $values['PlateBackgroundOffsetYPercent'],
                (int) $values['PlateBackgroundOpacityPercent'],
                (float) $values['PlateBackgroundRotation']
            ));
        }

        return $style;
    }

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

    /** @param list<string> $values @return list<array{caption: string, value: string}> */
    private static function Options(array $values): array
    {
        return array_map(static fn (string $value): array => ['caption' => ucfirst($value), 'value' => $value], $values);
    }

    /** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
    private static function PrefixControlNames(array $items, string $prefix): array
    {
        foreach ($items as &$item) {
            if (isset($item['name']) && is_string($item['name'])) {
                $item['name'] = $prefix . $item['name'];
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = self::PrefixControlNames($item['items'], $prefix);
            }
        }
        unset($item);

        return $items;
    }
}
