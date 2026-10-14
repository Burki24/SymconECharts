<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Projects explicitly chosen Symcon objects and undirected connections. */
final class GraphModel
{
    /** @param list<array<string,mixed>> $nodeRows @param list<array<string,mixed>> $edgeRows */
    public static function ValidateRows(array $nodeRows, array $edgeRows): void
    {
        if (count($nodeRows) < 2 || count($nodeRows) > 80 || count($edgeRows) < 1 || count($edgeRows) > 200) {
            throw new InvalidArgumentException('Graph requires 2–80 nodes and 1–200 connections.');
        }
        $known = [];
        foreach ($nodeRows as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('Graph node rows must be objects.');
            }
            $id = $row['ObjectID'] ?? null;
            $label = $row['Label'] ?? '';
            if (!is_int($id) || $id < 1 || isset($known[$id]) || !is_string($label)
                || mb_strlen($label, 'UTF-8') > 100) {
                throw new InvalidArgumentException('Graph nodes need distinct valid object IDs and short labels.');
            }
            $known[$id] = true;
        }
        $pairs = [];
        foreach ($edgeRows as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('Graph connection rows must be objects.');
            }
            $source = $row['SourceObjectID'] ?? null;
            $target = $row['TargetObjectID'] ?? null;
            if (!is_int($source) || !is_int($target) || !isset($known[$source], $known[$target])
                || $source === $target) {
                throw new InvalidArgumentException('Every connection needs two different configured nodes.');
            }
            $pair = min($source, $target) . ':' . max($source, $target);
            if (isset($pairs[$pair])) {
                throw new InvalidArgumentException('Duplicate Graph connections are not allowed.');
            }
            $pairs[$pair] = true;
        }
    }

    /**
     * @param list<array<string,mixed>> $nodeRows
     * @param list<array<string,mixed>> $edgeRows
     * @param callable(int):array<string,mixed> $getObject
     * @param callable(int):string $getFormattedValue
     * @return array{nodes:list<array<string,mixed>>,links:list<array<string,string>>}
     */
    public static function Build(array $nodeRows, array $edgeRows, callable $getObject, callable $getFormattedValue): array
    {
        self::ValidateRows($nodeRows, $edgeRows);
        $nodes = [];
        foreach ($nodeRows as $row) {
            $id = $row['ObjectID'];
            $label = $row['Label'] ?? '';
            $object = $getObject($id);
            $type = $object['ObjectType'] ?? null;
            if (!in_array($type, [0, 1, 2], true) || !is_string($object['ObjectName'] ?? null)) {
                throw new InvalidArgumentException('Only categories, instances and variables can be Graph nodes.');
            }
            $nodes[] = [
                'id'           => 'object-' . $id,
                'name'         => trim($label) !== '' ? trim($label) : $object['ObjectName'],
                'kind'         => match ($type) {
                    0 => 'category', 1 => 'instance', 2 => 'variable'
                },
                'displayValue' => $type === 2 ? $getFormattedValue($id) : ''
            ];
        }
        $links = [];
        foreach ($edgeRows as $row) {
            $source = $row['SourceObjectID'];
            $target = $row['TargetObjectID'];
            $links[] = ['source' => 'object-' . $source, 'target' => 'object-' . $target];
        }
        return ['nodes' => $nodes, 'links' => $links];
    }
}
