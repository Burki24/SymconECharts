<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Read-only, bounded projection of Symcon objects into ECharts Tree nodes. */
final class TreeHierarchy
{
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
        callable $getValue
    ): array {
        if ($rootID < 1 || $maxDepth < 1 || $maxDepth > 6 || $maxNodes < 2 || $maxNodes > 500) {
            throw new InvalidArgumentException('The Tree selection or limits are invalid.');
        }
        $root = $getObject($rootID);
        if (!in_array($root['ObjectType'] ?? null, [0, 1], true)) {
            throw new InvalidArgumentException('Select a category or instance as the Tree root.');
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
}
