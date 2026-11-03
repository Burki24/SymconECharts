<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/../libs/EChartsValueRange.php';

/** Explicit axes and current-value groups; unassigned axes never become measured zeros. */
final class RadarModel
{
    public static function Validate(array $axes, array $sources, ?callable $translate = null, bool $allowIncomplete = false): array
    {
        $t = $translate ?? static fn (string $s): string => $s;
        if (!array_is_list($axes) || count($axes) < 3 || count($axes) > 10
            || !array_is_list($sources) || count($sources) < ($allowIncomplete ? 1 : 3) || count($sources) > 160) {
            throw new InvalidArgumentException($t('Configure 3 to 10 axes and 1 to 16 complete comparison groups.'));
        }
        $normalized = [];
        $keys = [];
        foreach ($axes as $index => $axis) {
            $key = is_array($axis) && is_string($axis['Key'] ?? null) ? trim($axis['Key']) : '';
            $label = is_array($axis) && is_string($axis['Label'] ?? null) ? trim($axis['Label']) : '';
            $unit = is_array($axis) && is_string($axis['Unit'] ?? '') ? trim($axis['Unit'] ?? '') : null;
            $min = is_array($axis) ? ($axis['Minimum'] ?? null) : null;
            $max = is_array($axis) ? ($axis['Maximum'] ?? null) : null;
            $digits = is_array($axis) ? ($axis['Decimals'] ?? 1) : null;
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) || isset($keys[$key])
                || $label === '' || mb_strlen($label) > 80 || $unit === null || mb_strlen($unit) > 24
                || !EChartsValueRange::IsValid($min, $max)
                || !is_int($digits) || $digits < 0 || $digits > 6) {
                throw new InvalidArgumentException(sprintf($t('Axis %d: enter a unique key, label and a finite increasing range.'), $index + 1));
            }
            $inverse = array_key_exists('Inverse', $axis) ? $axis['Inverse'] : false;
            if (!is_bool($inverse)) {
                throw new InvalidArgumentException(sprintf($t('Axis %d: inverse must be a boolean.'), $index + 1));
            }
            $keys[$key] = true;
            $normalized[] = ['Key' => $key, 'Label' => $label, 'Minimum' => (float) $min,
                'Maximum'          => (float) $max, 'Unit' => $unit, 'Decimals' => $digits, 'Inverse' => $inverse];
        }
        $rows = [];
        $groups = [];
        foreach ($sources as $index => $row) {
            $group = is_array($row) && is_string($row['Group'] ?? null) ? trim($row['Group']) : '';
            $axis = is_array($row) && is_string($row['Axis'] ?? null) ? trim($row['Axis']) : '';
            $id = is_array($row) ? ($row['VariableID'] ?? null) : null;
            if ($group === '' || mb_strlen($group) > 80 || !isset($keys[$axis]) || !is_int($id) || $id <= 0
                || isset($groups[$group][$axis])) {
                throw new InvalidArgumentException(sprintf($t('Value %d: select a group, an existing axis and a unique variable assignment.'), $index + 1));
            }
            $groups[$group][$axis] = true;
            $rows[] = ['Group' => $group, 'Axis' => $axis, 'VariableID' => $id];
        }
        if (count($groups) > 16) {
            throw new InvalidArgumentException($t('Configure 3 to 10 axes and 1 to 16 complete comparison groups.'));
        }
        foreach ($groups as $name => $mapped) {
            if (!$allowIncomplete && count($mapped) !== count($keys)) {
                throw new InvalidArgumentException(sprintf($t('Group "%s" needs exactly one variable for every axis.'), $name));
            }
        }
        return ['axes' => $normalized, 'sources' => $rows];
    }

    public static function Build(array $axes, array $sources, array $values, ?callable $translate = null, array $visibility = [], bool $allowIncomplete = false): array
    {
        $configuration = self::Validate($axes, $sources, $translate, $allowIncomplete);
        $t = $translate ?? static fn (string $s): string => $s;
        $mapped = [];
        foreach ($configuration['sources'] as $source) {
            $current = $values[$source['VariableID']] ?? null;
            if (!is_array($current) || (!is_int($current['Value'] ?? null) && !is_float($current['Value'] ?? null))
                || !is_finite((float) $current['Value']) || !is_int($current['Timestamp'] ?? null)) {
                throw new InvalidArgumentException($t('Radar values must be finite numbers with timestamps.'));
            }
            $mapped[$source['Group']][$source['Axis']] = [...$current, 'VariableID' => $source['VariableID']];
        }
        $series = [];
        $clipped = 0;
        $incomplete = 0;
        foreach ($mapped as $group => $items) {
            $raw = [];
            $percent = [];
            $ids = [];
            $times = [];
            $missing = [];
            foreach ($configuration['axes'] as $axis) {
                if (!isset($items[$axis['Key']])) {
                    $raw[] = $percent[] = $ids[] = $times[] = null;
                    $missing[] = $axis['Key'];
                    continue;
                }
                $current = $items[$axis['Key']];
                $value = (float) $current['Value'];
                $bounded = max($axis['Minimum'], min($axis['Maximum'], $value));
                $clipped += $bounded !== $value ? 1 : 0;
                $raw[] = $value;
                // One display projection for native output and SVG; raw values/ranges remain untouched.
                $position = ($bounded - $axis['Minimum']) / ($axis['Maximum'] - $axis['Minimum']) * 100;
                $percent[] = $axis['Inverse'] ? 100.0 - $position : $position;
                $ids[] = $current['VariableID'];
                $times[] = $current['Timestamp'];
            }
            $series[] = ['id'      => 'group-' . hash('sha256', (string) $group), 'name' => (string) $group,
                'initiallyVisible' => $visibility[$group] ?? true,
                'values'           => $raw, 'percentValues' => $percent, 'variableIDs' => $ids, 'timestamps' => $times,
                ...($missing !== [] ? ['missingAxes' => $missing, 'segments' => self::Segments($percent)] : [])];
            $incomplete += $missing !== [] ? 1 : 0;
        }
        return ['axes' => $configuration['axes'], 'data' => $series, 'clippedCount' => $clipped, 'incompleteGroupCount' => $incomplete];
    }

    /** Only adjacent measured axes may connect; never bridge a missing measurement. */
    private static function Segments(array $positions): array
    {
        $edges = [];
        foreach ($positions as $index => $position) {
            $next = ($index + 1) % count($positions);
            if ($position !== null && $positions[$next] !== null) {
                $edges[] = [$index, $next];
            }
        }
        return $edges;
    }
}
