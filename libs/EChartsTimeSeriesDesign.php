<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsSvgImage.php';

/**
 * Source-specific design contract for ECharts time-series lines and areas.
 */
final class EChartsTimeSeriesDesign
{
    public const LINE_TYPES = ['solid', 'dashed', 'dotted'];
    public const POINT_SYMBOLS = ['none', 'circle', 'rect', 'roundRect', 'triangle', 'diamond', 'pin', 'arrow'];
    public const AREA_FILL_MODES = ['color', 'gradient', 'svg'];
    public const AXIS_RANGE_MODES = ['auto', 'presentation', 'manual'];
    public const ANNOTATION_TYPES = ['line', 'area'];
    public const ANNOTATION_LINE_TYPES = ['solid', 'dashed', 'dotted'];

    /** @var array<string, int|string|bool> */
    private const SOURCE_DESIGN_DEFAULTS = [
        'UseIndividualDesign'      => false,
        'SeriesLineType'           => 'solid',
        'SeriesLineWidthPercent'   => 100,
        'SeriesSmoothLine'         => false,
        'SeriesPointSymbol'        => 'none',
        'SeriesPointSizePercent'   => 100,
        'SeriesAreaOpacityPercent' => 22,
        'SeriesAreaFillMode'       => 'color',
        'SeriesAreaGradientColor'  => -1,
        'SeriesAreaSVG'            => '',
        'SeriesAreaSVGSizePercent' => 100
    ];

    /** @return array<string, int|string|bool> */
    public static function SourceDesignDefaults(): array
    {
        return self::SOURCE_DESIGN_DEFAULTS;
    }

    /** @return list<array<string, mixed>> */
    public static function SourceDesignColumns(): array
    {
        $columns = [];
        foreach (self::SOURCE_DESIGN_DEFAULTS as $name => $default) {
            $visible = $name === 'UseIndividualDesign';
            $columns[] = [
                'caption' => $visible ? 'Individual design' : $name,
                'name'    => $name,
                'width'   => $visible ? '125px' : '1px',
                'visible' => $visible,
                'save'    => true,
                'add'     => $default
            ];
        }

        return $columns;
    }

    /** @return list<array<string, mixed>> */
    public static function AxisRangeColumns(): array
    {
        return [
            [
                'caption' => 'AxisRangeMode',
                'name'    => 'AxisRangeMode',
                'width'   => '1px',
                'visible' => false,
                'save'    => true,
                'add'     => 'auto'
            ],
            [
                'caption' => 'AxisMinimum',
                'name'    => 'AxisMinimum',
                'width'   => '1px',
                'visible' => false,
                'save'    => true,
                'add'     => 0.0
            ],
            [
                'caption' => 'AxisMaximum',
                'name'    => 'AxisMaximum',
                'width'   => '1px',
                'visible' => false,
                'save'    => true,
                'add'     => 100.0
            ]
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function SourceEditorForm(): array
    {
        return [
            ['type' => 'SelectVariable', 'name' => 'VariableID', 'caption' => 'Variable', 'validVariableTypes' => [1, 2]],
            ['type' => 'ValidationTextBox', 'name' => 'Label', 'caption' => 'Label'],
            ['type' => 'CheckBox', 'name' => 'UseVariablePresentation', 'caption' => 'Variable presentation'],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'ValidationTextBox', 'name' => 'Unit', 'caption' => 'Unit'],
                ['type' => 'NumberSpinner', 'name' => 'Decimals', 'caption' => 'Decimals', 'minimum' => 0, 'maximum' => 6],
                ['type' => 'SelectColor', 'name' => 'Color', 'caption' => 'Color', 'allowTransparent' => true, 'transparentCaption' => 'Automatic']
            ]],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'Select', 'name' => 'Style', 'caption' => 'Style', 'options' => self::Options(['line', 'area'])],
                ['type' => 'Select', 'name' => 'Reducer', 'caption' => 'Reducer', 'options' => self::Options(['auto', 'average', 'sum', 'minimum', 'maximum'])],
                ['type' => 'Select', 'name' => 'AxisPosition', 'caption' => 'Axis side', 'options' => self::Options(['auto', 'left', 'right'])]
            ]],
            ['type' => 'ExpansionPanel', 'caption' => 'Value axis range', 'expanded' => false, 'items' => [
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'AxisRangeMode', 'caption' => 'Axis range', 'options' => self::Options(self::AXIS_RANGE_MODES)],
                    ['type' => 'NumberSpinner', 'name' => 'AxisMinimum', 'caption' => 'Axis minimum', 'digits' => 3],
                    ['type' => 'NumberSpinner', 'name' => 'AxisMaximum', 'caption' => 'Axis maximum', 'digits' => 3]
                ]],
                ['type' => 'Label', 'caption' => 'Automatic sources adopt an explicit range of their unit group. Conflicting explicit ranges for the same unit are invalid.']
            ]],
            ['type' => 'CheckBox', 'name' => 'UseIndividualDesign', 'caption' => 'Use individual series design'],
            ['type' => 'ExpansionPanel', 'caption' => 'Line and data points', 'expanded' => false, 'items' => [
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'SeriesLineType', 'caption' => 'Line type', 'options' => self::Options(self::LINE_TYPES)],
                    ['type' => 'NumberSpinner', 'name' => 'SeriesLineWidthPercent', 'caption' => 'Line width', 'minimum' => 50, 'maximum' => 200, 'suffix' => ' %'],
                    ['type' => 'CheckBox', 'name' => 'SeriesSmoothLine', 'caption' => 'Smooth line']
                ]],
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'SeriesPointSymbol', 'caption' => 'Point symbol', 'options' => self::Options(self::POINT_SYMBOLS)],
                    ['type' => 'NumberSpinner', 'name' => 'SeriesPointSizePercent', 'caption' => 'Point size', 'minimum' => 50, 'maximum' => 200, 'suffix' => ' %']
                ]]
            ]],
            ['type' => 'ExpansionPanel', 'caption' => 'Area fill', 'expanded' => false, 'items' => [
                ['type' => 'RowLayout', 'items' => [
                    ['type' => 'Select', 'name' => 'SeriesAreaFillMode', 'caption' => 'Area fill', 'options' => self::Options(self::AREA_FILL_MODES)],
                    ['type' => 'NumberSpinner', 'name' => 'SeriesAreaOpacityPercent', 'caption' => 'Area opacity', 'minimum' => 0, 'maximum' => 100, 'suffix' => ' %'],
                    ['type' => 'SelectColor', 'name' => 'SeriesAreaGradientColor', 'caption' => 'Gradient end color', 'allowTransparent' => true, 'transparentCaption' => 'Transparent']
                ]],
                ['type' => 'SelectFile', 'name' => 'SeriesAreaSVG', 'caption' => 'Area SVG pattern', 'extensions' => '.svg'],
                ['type' => 'NumberSpinner', 'name' => 'SeriesAreaSVGSizePercent', 'caption' => 'SVG pattern size', 'minimum' => 25, 'maximum' => 400, 'suffix' => ' %']
            ]]
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function AnnotationEditorForm(): array
    {
        return [
            ['type' => 'SelectVariable', 'name' => 'VariableID', 'caption' => 'Source', 'validVariableTypes' => [1, 2]],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'Select', 'name' => 'Type', 'caption' => 'Marker type', 'options' => [
                    ['caption' => 'Reference line', 'value' => 'line'],
                    ['caption' => 'Value range', 'value' => 'area']
                ]],
                ['type' => 'ValidationTextBox', 'name' => 'Label', 'caption' => 'Label'],
                ['type' => 'SelectColor', 'name' => 'Color', 'caption' => 'Color', 'allowTransparent' => true, 'transparentCaption' => 'Series color']
            ]],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'NumberSpinner', 'name' => 'Value', 'caption' => 'Value / minimum', 'digits' => 3],
                ['type' => 'NumberSpinner', 'name' => 'Maximum', 'caption' => 'Maximum', 'digits' => 3],
                ['type' => 'NumberSpinner', 'name' => 'OpacityPercent', 'caption' => 'Range opacity', 'minimum' => 0, 'maximum' => 100, 'suffix' => ' %']
            ]],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'Select', 'name' => 'LineType', 'caption' => 'Line type', 'options' => self::Options(self::ANNOTATION_LINE_TYPES)],
                ['type' => 'NumberSpinner', 'name' => 'LineWidthPercent', 'caption' => 'Line width', 'minimum' => 50, 'maximum' => 200, 'suffix' => ' %']
            ]],
            ['type' => 'Label', 'caption' => 'Reference lines use Value. Value ranges use Value as minimum and Maximum as upper boundary.']
        ];
    }

    /** @param array<string, mixed> $source @return array<string, mixed> */
    public static function StyleFromSource(array $source): array
    {
        $values = [];
        foreach (self::SOURCE_DESIGN_DEFAULTS as $name => $default) {
            $values[$name] = $source[$name] ?? $default;
        }
        if (!is_bool($values['UseIndividualDesign'])
            || !is_string($values['SeriesLineType'])
            || !in_array($values['SeriesLineType'], self::LINE_TYPES, true)
            || !is_bool($values['SeriesSmoothLine'])
            || !is_string($values['SeriesPointSymbol'])
            || !in_array($values['SeriesPointSymbol'], self::POINT_SYMBOLS, true)
            || !is_string($values['SeriesAreaFillMode'])
            || !in_array($values['SeriesAreaFillMode'], self::AREA_FILL_MODES, true)
        ) {
            throw new InvalidArgumentException('The selected individual time series design is not supported.');
        }
        foreach ([
            'SeriesLineWidthPercent'   => [50, 200],
            'SeriesPointSizePercent'   => [50, 200],
            'SeriesAreaOpacityPercent' => [0, 100],
            'SeriesAreaSVGSizePercent' => [25, 400]
        ] as $name => [$minimum, $maximum]) {
            if (!is_int($values[$name]) || $values[$name] < $minimum || $values[$name] > $maximum) {
                throw new InvalidArgumentException('The individual time series design percentages are invalid.');
            }
        }
        $gradientColor = self::NormalizeOptionalColor($values['SeriesAreaGradientColor']);
        if ($gradientColor === null) {
            throw new InvalidArgumentException('The individual time series gradient color is invalid.');
        }

        $style = [
            'lineType'           => $values['SeriesLineType'],
            'lineWidthPercent'   => $values['SeriesLineWidthPercent'],
            'smoothLine'         => $values['SeriesSmoothLine'],
            'pointSymbol'        => $values['SeriesPointSymbol'],
            'pointSizePercent'   => $values['SeriesPointSizePercent'],
            'areaOpacityPercent' => $values['SeriesAreaOpacityPercent'],
            'areaFillMode'       => $values['SeriesAreaFillMode'],
            'areaGradientColor'  => $gradientColor,
            'areaSVGSizePercent' => $values['SeriesAreaSVGSizePercent']
        ];
        if ($values['SeriesAreaFillMode'] === 'svg') {
            if (!is_string($values['SeriesAreaSVG'])) {
                throw new InvalidArgumentException('The individual time series SVG pattern is invalid.');
            }
            $image = EChartsSvgImage::Import($values['SeriesAreaSVG']);
            $style['areaPatternImage'] = $image['dataUri'];
            $style['areaPatternAspectRatio'] = $image['width'] / $image['height'];
        }

        return $style;
    }

    /** @param list<string> $values @return list<array{caption:string,value:string}> */
    private static function Options(array $values): array
    {
        $captions = [
            'auto'         => 'Automatic',
            'presentation' => 'Variable presentation',
            'manual'       => 'Manual',
            'roundRect'    => 'RoundRect',
            'svg'          => 'SVG'
        ];

        return array_map(
            static fn (string $value): array => [
                'caption' => $captions[$value] ?? ucwords(str_replace('-', ' ', $value)),
                'value'   => $value
            ],
            $values
        );
    }

    private static function NormalizeOptionalColor(mixed $color): ?string
    {
        if (is_int($color)) {
            if ($color === -1) {
                return '';
            }

            return $color >= 0 && $color <= 0xFFFFFF ? sprintf('#%06X', $color) : null;
        }
        if (!is_string($color)) {
            return null;
        }
        $color = trim($color);
        if ($color === '') {
            return '';
        }

        return preg_match('/^#[0-9A-F]{6}$/i', $color) === 1 ? strtoupper($color) : null;
    }
}
