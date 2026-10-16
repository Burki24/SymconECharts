<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Builds a bounded, user-grouped treemap from current numeric Symcon values. */
final class TreemapModel
{
    /**
     * @param list<array{VariableID:int,Group:string,Subgroup:string,Label:string}> $sources
     * @param array<int,array{Value:float,Timestamp:int,Name:string}> $values
     * @return array{data:list<array<string,mixed>>,positiveCount:int,total:float}
     */
    public static function Build(array $sources, array $values): array
    {
        if (count($sources) < 1 || count($sources) > 100) {
            throw new InvalidArgumentException('Configure 1 to 100 Treemap variables.');
        }
        $groups = [];
        $seen = [];
        $positiveCount = 0;
        $total = 0.0;
        foreach ($sources as $source) {
            $id = $source['VariableID'] ?? null;
            $groupName = trim($source['Group'] ?? '');
            $subgroupName = trim($source['Subgroup'] ?? '');
            if (!is_int($id) || $id <= 0 || isset($seen[$id])
                || $groupName === '' || mb_strlen($groupName) > 80 || mb_strlen($subgroupName) > 80
                || !isset($values[$id])) {
                throw new InvalidArgumentException('Treemap groups and variable IDs must be valid and unique.');
            }
            $seen[$id] = true;
            $value = $values[$id]['Value'];
            if (!is_finite($value) || $value < 0) {
                throw new InvalidArgumentException('Treemap weights must be finite and non-negative.');
            }
            $name = trim($source['Label']) !== '' ? trim($source['Label']) : $values[$id]['Name'];
            if ($name === '' || mb_strlen($name) > 120) {
                throw new InvalidArgumentException('Treemap labels must contain 1 to 120 characters.');
            }
            if ($value > 0) {
                ++$positiveCount;
            }
            $total += $value;
            if (!is_finite($total)) {
                throw new InvalidArgumentException('The Treemap total exceeds the numeric range.');
            }
            $leaf = [
                'id'        => 'variable-' . $id,
                'name'      => $name,
                'value'     => $value,
                'timestamp' => $values[$id]['Timestamp']
            ];
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'id'    => 'group-' . sha1($groupName), 'name' => $groupName,
                    'value' => 0.0, 'children' => [], 'subgroupPositions' => []
                ];
            }
            $groups[$groupName]['value'] += $value;
            if ($subgroupName === '') {
                $groups[$groupName]['children'][] = $leaf;
                continue;
            }
            if (!isset($groups[$groupName]['subgroupPositions'][$subgroupName])) {
                $groups[$groupName]['subgroupPositions'][$subgroupName] = count($groups[$groupName]['children']);
                $groups[$groupName]['children'][] = [
                    'id'   => 'subgroup-' . sha1($groupName . "\0" . $subgroupName),
                    'name' => $subgroupName, 'value' => 0.0, 'children' => []
                ];
            }
            $subgroupIndex = $groups[$groupName]['subgroupPositions'][$subgroupName];
            $groups[$groupName]['children'][$subgroupIndex]['value'] += $value;
            $groups[$groupName]['children'][$subgroupIndex]['children'][] = $leaf;
        }
        $data = [];
        foreach ($groups as $group) {
            unset($group['subgroupPositions']);
            $data[] = $group;
        }
        return ['data' => $data, 'positiveCount' => $positiveCount, 'total' => $total];
    }
}
