<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Builds one value axis per unit for charts with several numeric sources. */
final class EChartsUnitAxes
{
    /**
     * @param list<array{Unit:string,AxisPosition:string,AxisRange?:?array{minimum:float,maximum:float}}> $sources
     * @return array{Axes:list<array{unit:string,position:string,positionIndex:int,minimum?:float,maximum?:float}>,Indexes:array<string,int>}
     */
    public static function Build(array $sources): array
    {
        $groups = [];
        foreach ($sources as $source) {
            $unit = $source['Unit'];
            $position = $source['AxisPosition'];
            if (!in_array($position, ['auto', 'left', 'right'], true)) {
                throw new InvalidArgumentException('The value axis side is invalid.');
            }
            if (!array_key_exists($unit, $groups)) {
                $groups[$unit] = ['unit' => $unit, 'position' => 'auto', 'range' => null];
            }
            if ($position !== 'auto') {
                if ($groups[$unit]['position'] !== 'auto' && $groups[$unit]['position'] !== $position) {
                    throw new InvalidArgumentException('Sources with the same unit must use the same axis side.');
                }
                $groups[$unit]['position'] = $position;
            }
            $range = $source['AxisRange'] ?? null;
            if ($range !== null) {
                $existing = $groups[$unit]['range'];
                if ($existing !== null
                    && (!self::ValuesEqual($existing['minimum'], $range['minimum'])
                        || !self::ValuesEqual($existing['maximum'], $range['maximum']))) {
                    throw new InvalidArgumentException('Sources with the same unit must use the same explicit axis range.');
                }
                $groups[$unit]['range'] = $range;
            }
        }

        $counts = ['left' => 0, 'right' => 0];
        foreach ($groups as $group) {
            if ($group['position'] !== 'auto') {
                ++$counts[$group['position']];
            }
        }
        foreach ($groups as &$group) {
            if ($group['position'] === 'auto') {
                $group['position'] = $counts['left'] <= $counts['right'] ? 'left' : 'right';
                ++$counts[$group['position']];
            }
        }
        unset($group);

        $axes = [];
        $indexes = [];
        $positionIndexes = ['left' => 0, 'right' => 0];
        foreach ($groups as $unit => $group) {
            $position = $group['position'];
            $indexes[$unit] = count($axes);
            $axis = [
                'unit'          => $group['unit'],
                'position'      => $position,
                'positionIndex' => $positionIndexes[$position]++
            ];
            if ($group['range'] !== null) {
                $axis['minimum'] = $group['range']['minimum'];
                $axis['maximum'] = $group['range']['maximum'];
            }
            $axes[] = $axis;
        }

        return ['Axes' => $axes, 'Indexes' => $indexes];
    }

    private static function ValuesEqual(float $left, float $right): bool
    {
        return abs($left - $right) <= 1e-9 * max(1.0, abs($left), abs($right));
    }
}
