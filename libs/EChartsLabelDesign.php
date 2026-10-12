<?php

declare(strict_types=1);

namespace SymconECharts;

/** Shared value-label choices and form controls; each chart retains its own defaults. */
final class EChartsLabelDesign
{
    public const CARTESIAN_POSITIONS = [
        'auto', 'top', 'bottom', 'left', 'right', 'inside',
        'insideTop', 'insideBottom', 'insideLeft', 'insideRight'
    ];
    public const POLAR_POSITIONS = ['middle', 'insideStart', 'insideEnd', 'outside', 'start', 'end'];

    public static function IsValidPosition(string $position, array $allowed): bool
    {
        return in_array($position, $allowed, true);
    }

    public static function IsValidDistance(int $distance, bool $allowAutomatic = false): bool
    {
        return $distance >= ($allowAutomatic ? -1 : 0) && $distance <= 100;
    }

    /** @return list<array<string, mixed>> */
    public static function CartesianControls(string $prefix = ''): array
    {
        $options = [];
        $captions = [
            'auto'       => 'Automatic', 'top' => 'Above', 'bottom' => 'Below',
            'left'       => 'Left', 'right' => 'Right', 'inside' => 'Inside center',
            'insideTop'  => 'Inside top', 'insideBottom' => 'Inside bottom',
            'insideLeft' => 'Inside left', 'insideRight' => 'Inside right'
        ];
        foreach (self::CARTESIAN_POSITIONS as $position) {
            $options[] = ['caption' => $captions[$position], 'value' => $position];
        }

        return [
            ['type'       => 'Select', 'name' => $prefix . 'ValueLabelPosition',
                'caption' => 'Value label position', 'options' => $options],
            ['type'       => 'NumberSpinner', 'name' => $prefix . 'ValueLabelDistance',
                'caption' => 'Value label distance', 'minimum' => 0, 'maximum' => 100, 'suffix' => 'px']
        ];
    }

    /** Insert chart-specific controls alongside existing controls, in tile and IPSView designers. */
    public static function InsertAfter(array &$items, string $name, array $controls): bool
    {
        foreach ($items as $index => &$item) {
            if (($item['name'] ?? '') === $name) {
                array_splice($items, $index + 1, 0, $controls);
                return true;
            }
            if (isset($item['items']) && is_array($item['items'])
                && self::InsertAfter($item['items'], $name, $controls)) {
                return true;
            }
        }

        return false;
    }

    public static function AppendOptions(array &$items, string $name, array $options): bool
    {
        foreach ($items as &$item) {
            if (($item['name'] ?? '') === $name && isset($item['options'])) {
                array_push($item['options'], ...$options);
                return true;
            }
            if (isset($item['items']) && is_array($item['items'])
                && self::AppendOptions($item['items'], $name, $options)) {
                return true;
            }
        }

        return false;
    }

    public static function InsertAfterRowContaining(array &$items, string $name, array $controls): bool
    {
        foreach ($items as $index => &$item) {
            if (($item['type'] ?? '') === 'RowLayout'
                && in_array($name, array_column($item['items'] ?? [], 'name'), true)) {
                array_splice($items, $index + 1, 0, $controls);
                return true;
            }
            if (isset($item['items']) && is_array($item['items'])
                && self::InsertAfterRowContaining($item['items'], $name, $controls)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string,mixed>> */
    public static function GaugeControls(): array
    {
        return [
            ['type' => 'RowLayout', 'items' => [
                ['type'       => 'NumberSpinner', 'name' => 'ScaleLabelDistanceOffset',
                    'caption' => 'Scale label distance offset', 'minimum' => -100, 'maximum' => 100, 'suffix' => 'px']
            ]],
            ['type' => 'RowLayout', 'items' => [
                ['type'       => 'NumberSpinner', 'name' => 'TitleOffsetXPercent',
                    'caption' => 'Title offset X', 'minimum' => -100, 'maximum' => 100, 'suffix' => '%'],
                ['type'       => 'NumberSpinner', 'name' => 'TitleOffsetYPercent',
                    'caption' => 'Title offset Y', 'minimum' => -100, 'maximum' => 100, 'suffix' => '%']
            ]],
            ['type' => 'RowLayout', 'items' => [
                ['type'       => 'NumberSpinner', 'name' => 'ValueOffsetXPercent',
                    'caption' => 'Value offset X', 'minimum' => -100, 'maximum' => 100, 'suffix' => '%'],
                ['type'       => 'NumberSpinner', 'name' => 'ValueOffsetYPercent',
                    'caption' => 'Value offset Y', 'minimum' => -100, 'maximum' => 100, 'suffix' => '%']
            ]]
        ];
    }

    /** @return array{x:float,y:float,anchor:string} Schematic SVG label placement for rectangular bars. */
    public static function PreviewRect(float $x, float $y, float $width, float $height, string $position, int $distance): array
    {
        $distance = max(0, min(100, $distance));
        $centerX = $x + $width / 2;
        $centerY = $y + $height / 2;

        return match ($position) {
            'bottom'           => ['x' => $centerX, 'y' => $y + $height + $distance + 12, 'anchor' => 'middle'],
            'left'             => ['x' => $x - $distance, 'y' => $centerY + 4, 'anchor' => 'end'],
            'right'            => ['x' => $x + $width + $distance, 'y' => $centerY + 4, 'anchor' => 'start'],
            'inside', 'middle' => ['x' => $centerX, 'y' => $centerY + 4, 'anchor' => 'middle'],
            'insideTop'        => ['x' => $centerX, 'y' => $y + $distance + 11, 'anchor' => 'middle'],
            'insideBottom'     => ['x' => $centerX, 'y' => $y + $height - $distance - 3, 'anchor' => 'middle'],
            'insideLeft'       => ['x' => $x + $distance + 2, 'y' => $centerY + 4, 'anchor' => 'start'],
            'insideRight'      => ['x' => $x + $width - $distance - 2, 'y' => $centerY + 4, 'anchor' => 'end'],
            default            => ['x' => $centerX, 'y' => $y - $distance - 2, 'anchor' => 'middle']
        };
    }
}
