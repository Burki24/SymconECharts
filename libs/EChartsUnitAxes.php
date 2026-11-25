<?php

declare(strict_types=1);

namespace eCharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsValueRange.php';
require_once __DIR__ . '/EChartsVariablePresentation.php';

/** Assigns numeric archive series to shared or individual value axes. */
final class EChartsUnitAxes
{
    /** @return ?array{minimum:float,maximum:float} */
    public static function ResolveRange(array $source): ?array
    {
        $mode = $source['AxisRangeMode'] ?? 'auto';
        if ($mode === 'auto') {
            return null;
        }
        if (!in_array($mode, ['manual', 'presentation'], true)) {
            throw new InvalidArgumentException('The value axis range mode is invalid.');
        }
        $minimum = $source['AxisMinimum'] ?? 0.0;
        $maximum = $source['AxisMaximum'] ?? 100.0;
        if (!EChartsValueRange::IsValid($minimum, $maximum)) {
            throw new InvalidArgumentException('The value axis minimum must be smaller than its maximum.');
        }
        if ($mode === 'presentation') {
            $metadata = EChartsVariablePresentation::Resolve(
                is_int($source['VariableID'] ?? null) ? $source['VariableID'] : 0,
                (float) $minimum,
                (float) $maximum,
                '',
                0,
                true
            );
            return ['minimum' => $metadata['minimum'], 'maximum' => $metadata['maximum']];
        }
        return ['minimum' => (float) $minimum, 'maximum' => (float) $maximum];
    }

    /**
     * @param list<array{Unit:string,AxisPosition:string,OwnAxis?:bool,AxisRange?:?array{minimum:float,maximum:float}}> $sources
     * @return array{Axes:list<array{unit:string,position:string,positionIndex:int,minimum?:float,maximum?:float}>,Indexes:array<string,int>,SeriesIndexes:list<int>}
     */
    public static function Build(array $sources): array
    {
        $groups = [];
        $sharedIndexes = [];
        $seriesIndexes = [];
        foreach ($sources as $source) {
            $unit = $source['Unit'];
            $position = $source['AxisPosition'];
            if (!in_array($position, ['auto', 'left', 'right'], true)) {
                throw new InvalidArgumentException('The value axis side is invalid.');
            }
            $ownAxis = $source['OwnAxis'] ?? false;
            $index = $ownAxis ? null : ($sharedIndexes[$unit] ?? null);
            if ($index === null) {
                $index = count($groups);
                $groups[] = ['unit' => $unit, 'position' => 'auto', 'range' => null];
                if (!$ownAxis) {
                    $sharedIndexes[$unit] = $index;
                }
            }
            $seriesIndexes[] = $index;
            if ($position !== 'auto') {
                if ($groups[$index]['position'] !== 'auto' && $groups[$index]['position'] !== $position) {
                    throw new InvalidArgumentException('Series sharing a value axis must use the same axis side.');
                }
                $groups[$index]['position'] = $position;
            }
            $range = $source['AxisRange'] ?? null;
            if ($range !== null) {
                $existing = $groups[$index]['range'];
                if ($existing !== null
                    && (!self::ValuesEqual($existing['minimum'], $range['minimum'])
                        || !self::ValuesEqual($existing['maximum'], $range['maximum']))) {
                    throw new InvalidArgumentException('Series sharing a value axis must use the same explicit axis range.');
                }
                $groups[$index]['range'] = $range;
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
        $positionIndexes = ['left' => 0, 'right' => 0];
        foreach ($groups as $group) {
            $position = $group['position'];
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

        return ['Axes' => $axes, 'Indexes' => $sharedIndexes, 'SeriesIndexes' => $seriesIndexes];
    }

    private static function ValuesEqual(float $left, float $right): bool
    {
        return abs($left - $right) <= 1e-9 * max(1.0, abs($left), abs($right));
    }
}
