<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

/** Read-only, bounded projection of Symcon objects into ECharts Tree nodes. */
final class TreeHierarchy
{
    private const FILTER_SCAN_LIMIT = 30000;

    /**
     * @param callable(int):array<string,mixed> $getObject
     * @param callable(int):list<int> $getChildren
     * @param callable(int):string $getValue
     * @return array{root:array<string,mixed>,count:int,truncated:bool,variableIDs:list<int>}
     */
    public static function Build(
        int $rootID,
        int $maxDepth,
        int $maxNodes,
        bool $includeHidden,
        callable $getObject,
        callable $getChildren,
        callable $getValue,
        string $variableNameFilter = ''
    ): array {
        $variableNameFilter = trim($variableNameFilter);
        if ($rootID < 0 || $maxDepth < 1 || $maxDepth > 6 || $maxNodes < 2 || $maxNodes > 3000
            || mb_strlen($variableNameFilter, 'UTF-8') > 120) {
            throw new InvalidArgumentException('The Tree selection or limits are invalid.');
        }
        $root = $getObject($rootID);
        if (!in_array($root['ObjectType'] ?? null, [0, 1], true)) {
            throw new InvalidArgumentException('Select a category or instance as the Tree root.');
        }
        if ($variableNameFilter !== '') {
            return self::BuildFiltered(
                $rootID,
                $root,
                $maxDepth,
                $maxNodes,
                $includeHidden,
                $getObject,
                $getChildren,
                $getValue,
                $variableNameFilter
            );
        }

        $seen = [];
        $count = 0;
        $truncated = false;
        $variableIDs = [];
        $visit = static function (int $id, int $depth) use (
            &$visit,
            &$seen,
            &$count,
            &$truncated,
            &$variableIDs,
            $maxDepth,
            $maxNodes,
            $includeHidden,
            $getObject,
            $getChildren,
            $getValue
        ): ?array {
            if (isset($seen[$id])) {
                return null;
            }
            $object = $getObject($id);
            $kind = match ($object['ObjectType'] ?? null) {
                0 => 'category', 1 => 'instance', 2 => 'variable', default => null
            };
            if ($kind === null || ($depth > 0 && !$includeHidden && ($object['ObjectIsHidden'] ?? false))) {
                return null;
            }
            if ($count >= $maxNodes) {
                $truncated = true;
                return null;
            }
            $seen[$id] = true;
            ++$count;
            $node = [
                'id'       => 'object-' . $id,
                'name'     => mb_substr((string) ($object['ObjectName'] ?? ''), 0, 120),
                'kind'     => $kind,
                'children' => []
            ];
            if ($kind === 'variable') {
                $variableIDs[] = $id;
                $node['displayValue'] = mb_substr($getValue($id), 0, 100);
            } elseif ($depth < $maxDepth) {
                foreach ($getChildren($id) as $childID) {
                    $child = $visit($childID, $depth + 1);
                    if ($child !== null) {
                        $node['children'][] = $child;
                    }
                    if ($truncated) {
                        break;
                    }
                }
            }
            return $node;
        };

        return [
            'root'        => $visit($rootID, 0),
            'count'       => $count,
            'truncated'   => $truncated,
            'variableIDs' => $variableIDs
        ];
    }

    /**
     * @param array<string,mixed> $rootObject
     * @param callable(int):array<string,mixed> $getObject
     * @param callable(int):list<int> $getChildren
     * @param callable(int):string $getValue
     * @return array{root:array<string,mixed>,count:int,truncated:bool,variableIDs:list<int>}
     */
    private static function BuildFiltered(
        int $rootID,
        array $rootObject,
        int $maxDepth,
        int $maxNodes,
        bool $includeHidden,
        callable $getObject,
        callable $getChildren,
        callable $getValue,
        string $variableNameFilter
    ): array {
        $seen = [];
        $scanned = 0;
        $find = static function (int $id, int $depth) use (
            &$find,
            &$seen,
            &$scanned,
            $rootID,
            $rootObject,
            $maxDepth,
            $includeHidden,
            $getObject,
            $getChildren,
            $variableNameFilter
        ): ?array {
            if (isset($seen[$id])) {
                return null;
            }
            $seen[$id] = true;
            if (++$scanned > self::FILTER_SCAN_LIMIT) {
                throw new RuntimeException('The Tree name filter exceeded its scan limit. Select a narrower root or depth.');
            }
            $object = $id === $rootID ? $rootObject : $getObject($id);
            $kind = match ($object['ObjectType'] ?? null) {
                0 => 'category', 1 => 'instance', 2 => 'variable', default => null
            };
            if ($kind === null || ($depth > 0 && !$includeHidden && ($object['ObjectIsHidden'] ?? false))) {
                return null;
            }
            $name = mb_substr((string) ($object['ObjectName'] ?? ''), 0, 120);
            if ($kind === 'variable') {
                if (mb_stripos($name, $variableNameFilter, 0, 'UTF-8') === false) {
                    return null;
                }
                return ['id' => 'object-' . $id, 'name' => $name, 'kind' => $kind, 'children' => []];
            }
            $children = [];
            if ($depth < $maxDepth) {
                foreach ($getChildren($id) as $childID) {
                    $child = $find($childID, $depth + 1);
                    if ($child !== null) {
                        $children[] = $child;
                    }
                }
            }
            if ($depth > 0 && $children === []) {
                return null;
            }
            return ['id' => 'object-' . $id, 'name' => $name, 'kind' => $kind, 'children' => $children];
        };

        $candidate = $find($rootID, 0);
        $count = 0;
        $truncated = false;
        $variableIDs = [];
        $limit = static function (array $node) use (&$limit, &$count, &$truncated, &$variableIDs, $maxNodes, $getValue): array
        {
            ++$count;
            if ($node['kind'] === 'variable') {
                $id = (int) substr($node['id'], 7);
                $variableIDs[] = $id;
                $node['displayValue'] = mb_substr($getValue($id), 0, 100);
            }
            $children = $node['children'];
            $node['children'] = [];
            foreach ($children as $child) {
                if ($count >= $maxNodes) {
                    $truncated = true;
                    break;
                }
                $node['children'][] = $limit($child);
            }
            return $node;
        };

        return [
            'root'        => $limit($candidate),
            'count'       => $count,
            'truncated'   => $truncated,
            'variableIDs' => $variableIDs
        ];
    }
}
