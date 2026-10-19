<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Builds bounded, user-grouped hierarchy data from current numeric Symcon values. */
final class TreemapModel
{
    public const MAXIMUM_SOURCES = 100;
    public const MAXIMUM_DEPTH = 8;
    public const MAXIMUM_NODES = 800;
    public const MAXIMUM_MODEL_BYTES = 200000;

    /** Explicit paths are opt-in; old Group/Subgroup names remain literal. @return list<string> */
    public static function Path(array $source): array
    {
        if (array_key_exists('Path', $source)) {
            $path = $source['Path'];
        } else {
            $explicit = $source['GroupPath'] ?? '';
            if (array_key_exists('GroupPath', $source) && !is_string($source['GroupPath'])) {
                throw new InvalidArgumentException('The hierarchy group path must be text.');
            }
            if (trim($explicit) !== '') {
                if (strlen($explicit) > 4096) {
                    throw new InvalidArgumentException('The hierarchy group path is too long.');
                }
                $path = [];
                $segment = '';
                $escaped = false;
                foreach (str_split($explicit) as $character) {
                    if ($escaped) {
                        if ($character !== '/' && $character !== '\\') {
                            throw new InvalidArgumentException('Escape only slash or backslash in hierarchy paths.');
                        }
                        $segment .= $character;
                        $escaped = false;
                    } elseif ($character === '\\') {
                        $escaped = true;
                    } elseif ($character === '/') {
                        $path[] = trim($segment);
                        $segment = '';
                    } else {
                        $segment .= $character;
                    }
                }
                if ($escaped) {
                    throw new InvalidArgumentException('The hierarchy group path ends with an incomplete escape.');
                }
                $path[] = trim($segment);
            } else {
                if (!is_string($source['Group'] ?? null) || !is_string($source['Subgroup'] ?? '')) {
                    throw new InvalidArgumentException('Hierarchy group and subgroup must be text.');
                }
                $path = [trim($source['Group'])];
                if (trim($source['Subgroup'] ?? '') !== '') {
                    $path[] = trim($source['Subgroup']);
                }
            }
        }
        if (!is_array($path) || !array_is_list($path) || count($path) < 1 || count($path) >= self::MAXIMUM_DEPTH) {
            throw new InvalidArgumentException('Use 1 to 7 hierarchy groups; the variable is the eighth level.');
        }
        foreach ($path as &$segment) {
            if (!is_string($segment) || trim($segment) === '' || mb_strlen(trim($segment)) > 80) {
                throw new InvalidArgumentException('Hierarchy group names need 1 to 80 characters.');
            }
            $segment = trim($segment);
        }
        unset($segment);
        return $path;
    }

    /**
     * @param list<array<string,mixed>> $sources
     * @param array<int,array{Value:float,Timestamp:int,Name:string}> $values
     * @return array{data:list<array<string,mixed>>,positiveCount:int,total:float,depth:int,nodeCount:int}
     */
    public static function Build(array $sources, array $values, string $family = 'Treemap'): array
    {
        if (!in_array($family, ['Treemap', 'Sunburst'], true)) {
            throw new InvalidArgumentException('Unsupported hierarchy family.');
        }
        if (!array_is_list($sources) || count($sources) < 1 || count($sources) > self::MAXIMUM_SOURCES) {
            throw new InvalidArgumentException('Configure 1 to 100 ' . $family . ' variables.');
        }
        $groups = [];
        $positions = [];
        $seen = [];
        $positiveCount = 0;
        $total = 0.0;
        $depth = 0;
        $nodeCount = 0;
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Hierarchy sources must be objects.');
            }
            $id = $source['VariableID'] ?? null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id])
                || !isset($values[$id])) {
                throw new InvalidArgumentException($family . ' groups and variable IDs must be valid and unique.');
            }
            $seen[$id] = true;
            $path = self::Path($source);
            $depth = max($depth, count($path) + 1);
            $value = $values[$id]['Value'] ?? null;
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0) {
                throw new InvalidArgumentException($family . ' weights must be finite and non-negative.');
            }
            $value = (float) $value;
            $label = $source['Label'] ?? '';
            if (!is_string($label)) {
                throw new InvalidArgumentException($family . ' labels must be text.');
            }
            $name = trim($label) !== '' ? trim($label) : ($values[$id]['Name'] ?? null);
            if (!is_string($name) || $name === '' || mb_strlen($name) > 120) {
                throw new InvalidArgumentException($family . ' labels must contain 1 to 120 characters.');
            }
            if ($value > 0) {
                ++$positiveCount;
            }
            $total += $value;
            if (!is_finite($total)) {
                throw new InvalidArgumentException('The ' . $family . ' total exceeds the numeric range.');
            }
            $leaf = [
                'id'        => 'variable-' . $id,
                'name'      => $name,
                'value'     => $value,
                'timestamp' => $values[$id]['Timestamp']
            ];
            $siblings = &$groups;
            $prefix = [];
            foreach ($path as $level => $groupName) {
                $prefix[] = $groupName;
                $key = json_encode($prefix, JSON_THROW_ON_ERROR);
                if (!array_key_exists($key, $positions)) {
                    $positions[$key] = count($siblings);
                    $groupID = match ($level) {
                        0       => 'group-' . sha1($groupName),
                        1       => 'subgroup-' . sha1($path[0] . "\0" . $groupName),
                        default => 'path-' . sha1($key)
                    };
                    $siblings[] = ['id' => $groupID, 'name' => $groupName, 'value' => 0.0, 'children' => []];
                    ++$nodeCount;
                }
                $node = &$siblings[$positions[$key]];
                $node['value'] += $value;
                $siblings = &$node['children'];
                unset($node);
            }
            $siblings[] = $leaf;
            unset($siblings);
            ++$nodeCount;
            if ($nodeCount > self::MAXIMUM_NODES) {
                throw new InvalidArgumentException('The hierarchy exceeds 800 nodes.');
            }
        }
        $result = ['data' => $groups, 'positiveCount' => $positiveCount, 'total' => $total,
            'depth'       => $depth, 'nodeCount' => $nodeCount];
        $encoded = json_encode($result, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (strlen($encoded) > self::MAXIMUM_MODEL_BYTES) {
            throw new InvalidArgumentException('The hierarchy exceeds the 200000-byte model budget.');
        }
        return $result;
    }
}
