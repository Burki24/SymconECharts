<?php

declare(strict_types=1);

namespace eCharts;

/** Source-list defaults and editor for archive series sharing the value-axis model. */
final class EChartsValueAxisForm
{
    /** @return list<array<string,mixed>> */
    public static function Columns(): array
    {
        $columns = [];
        foreach (['OwnAxis' => false, 'AxisRangeMode' => 'auto', 'AxisMinimum' => 0.0, 'AxisMaximum' => 100.0] as $name => $default) {
            $columns[] = [
                'caption' => $name,
                'name'    => $name,
                'width'   => '1px',
                'visible' => false,
                'save'    => true,
                'add'     => $default
            ];
        }

        return $columns;
    }

    /** @return array<string,mixed> */
    public static function Panel(): array
    {
        return ['type' => 'ExpansionPanel', 'caption' => 'Value axis range', 'expanded' => false, 'items' => [
            ['type' => 'CheckBox', 'name' => 'OwnAxis', 'caption' => 'Own value axis'],
            ['type' => 'RowLayout', 'items' => [
                ['type' => 'Select', 'name' => 'AxisRangeMode', 'caption' => 'Axis range', 'options' => [
                    ['caption' => 'Automatic', 'value' => 'auto'],
                    ['caption' => 'Variable presentation', 'value' => 'presentation'],
                    ['caption' => 'Manual', 'value' => 'manual']
                ]],
                ['type' => 'NumberSpinner', 'name' => 'AxisMinimum', 'caption' => 'Axis minimum', 'digits' => 3],
                ['type' => 'NumberSpinner', 'name' => 'AxisMaximum', 'caption' => 'Axis maximum', 'digits' => 3]
            ]],
            ['type' => 'Label', 'caption' => 'Series on a shared axis adopt its explicit range. Use an own value axis to set a separate side and range.']
        ]];
    }
}
