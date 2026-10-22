<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Scatter-specific grouping; names group assignments, variable IDs identify measurements. */
final class ScatterGroups
{
    public const MAX_GROUPS = 16;
    public const MAX_SOURCES = 48;

    public static function Split(array $rows): array
    {
        if (!array_is_list($rows) || count($rows) < 2 || count($rows) > self::MAX_SOURCES) {
            throw new InvalidArgumentException('Configure up to 16 complete X/Y comparison groups, optionally with size.');
        }
        $groups = [];
        foreach ($rows as $row) {
            $name = is_array($row) ? ($row['Group'] ?? '') : null;
            if (!is_string($name) || mb_strlen(trim($name)) > 80) {
                throw new InvalidArgumentException('Comparison group names must contain at most 80 characters.');
            }
            $name = trim($name);
            // Prefix prevents numeric labels from changing PHP key types.
            $key = 'group:' . $name;
            $groups[$key] ??= ['label' => $name, 'rows' => []];
            $groups[$key]['rows'][] = $row;
        }
        if (count($groups) > self::MAX_GROUPS) {
            throw new InvalidArgumentException('Configure up to 16 complete X/Y comparison groups, optionally with size.');
        }
        return array_values($groups);
    }

    public static function ID(array $roles): string
    {
        $ids = [];
        foreach (['x', 'y', 'size'] as $role) {
            $ids[$role] = isset($roles[$role])
                ? ['variableID' => $roles[$role]['VariableID'], 'reducer' => $roles[$role]['Reducer']]
                : null;
        }
        return 'comparison-' . hash('sha256', json_encode($ids, JSON_THROW_ON_ERROR));
    }
}
