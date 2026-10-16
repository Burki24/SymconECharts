<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Bounded acyclic flow configuration; display labels never identify nodes or sources. */
final class SankeyModel
{
    /** @return array{nodes:list<array{Key:string,Label:string}>,sources:list<array{Source:string,Target:string,VariableID:int}>} */
    public static function Validate(array $nodes, array $sources, ?callable $translate = null): array
    {
        $t = $translate ?? static fn (string $text): string => $text;
        if (!array_is_list($nodes) || count($nodes) < 2 || count($nodes) > 100
            || !array_is_list($sources) || count($sources) < 1 || count($sources) > 200) {
            throw new InvalidArgumentException($t('Configure 2 to 100 nodes and 1 to 200 connections.'));
        }
        $normalized = [];
        $positions = [];
        foreach ($nodes as $index => $node) {
            $key = is_array($node) && is_string($node['Key'] ?? null) ? trim($node['Key']) : '';
            $label = is_array($node) && is_string($node['Label'] ?? null) ? trim($node['Label']) : '';
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) || isset($positions[$key])
                || $label === '' || mb_strlen($label) > 80) {
                throw new InvalidArgumentException(sprintf($t('Node %d: enter a unique key and a label of 1 to 80 characters.'), $index + 1));
            }
            $positions[$key] = count($normalized);
            $normalized[] = ['Key' => $key, 'Label' => $label];
        }
        $edges = [];
        $pairs = [];
        $incoming = array_fill_keys(array_keys($positions), 0);
        $outgoing = array_fill_keys(array_keys($positions), []);
        foreach ($sources as $index => $edge) {
            $source = is_array($edge) && is_string($edge['Source'] ?? null) ? trim($edge['Source']) : '';
            $target = is_array($edge) && is_string($edge['Target'] ?? null) ? trim($edge['Target']) : '';
            $id = is_array($edge) ? ($edge['VariableID'] ?? null) : null;
            $pair = $source . "\0" . $target;
            if (!isset($positions[$source], $positions[$target]) || $source === $target
                || isset($pairs[$pair]) || !is_int($id) || $id <= 0) {
                throw new InvalidArgumentException(sprintf($t('Connection %d: select distinct existing nodes, a variable and a unique directed pair.'), $index + 1));
            }
            $pairs[$pair] = true;
            ++$incoming[$target];
            $outgoing[$source][] = $target;
            $edges[] = ['Source' => $source, 'Target' => $target, 'VariableID' => $id];
        }
        $queue = array_keys(array_filter($incoming, static fn (int $count): bool => $count === 0));
        $visited = 0;
        for ($cursor = 0; $cursor < count($queue); ++$cursor) {
            ++$visited;
            foreach ($outgoing[$queue[$cursor]] as $target) {
                if (--$incoming[$target] === 0) {
                    $queue[] = $target;
                }
            }
        }
        if ($visited !== count($normalized)) {
            throw new InvalidArgumentException($t('Sankey connections contain a cycle.'));
        }
        return ['nodes' => $normalized, 'sources' => $edges];
    }

    /** @param array<int,array{Value:float,Timestamp:int}> $values @return array{data:list<array<string,mixed>>,links:list<array<string,mixed>>,positiveCount:int} */
    public static function Build(array $nodes, array $sources, array $values, ?callable $translate = null): array
    {
        $configuration = self::Validate($nodes, $sources, $translate);
        $t = $translate ?? static fn (string $text): string => $text;
        $links = [];
        $incoming = [];
        $outgoing = [];
        $sum = 0.0;
        foreach ($configuration['sources'] as $index => $edge) {
            $id = $edge['VariableID'];
            $value = $values[$id]['Value'] ?? null;
            $timestamp = $values[$id]['Timestamp'] ?? null;
            if ((!is_float($value) && !is_int($value)) || !is_finite((float) $value) || $value < 0
                || !is_int($timestamp)) {
                throw new InvalidArgumentException(sprintf($t('Connection %d: the current value must be finite and non-negative.'), $index + 1));
            }
            if ($value == 0) {
                continue;
            }
            $source = $edge['Source'];
            $target = $edge['Target'];
            $outgoing[$source] = ($outgoing[$source] ?? 0.0) + $value;
            $incoming[$target] = ($incoming[$target] ?? 0.0) + $value;
            $sum += $value;
            if (!is_finite($sum) || !is_finite($outgoing[$source]) || !is_finite($incoming[$target])) {
                throw new InvalidArgumentException($t('Sankey flow totals exceed the numeric range.'));
            }
            $links[] = ['source' => $source, 'target' => $target, 'value' => (float) $value,
                'variableID'     => $id, 'timestamp' => $timestamp];
        }
        $data = [];
        foreach ($configuration['nodes'] as $node) {
            $key = $node['Key'];
            if (isset($incoming[$key]) || isset($outgoing[$key])) {
                $data[] = ['name' => $key, 'label' => $node['Label'],
                    'value'       => max($incoming[$key] ?? 0.0, $outgoing[$key] ?? 0.0)];
            }
        }
        return ['data' => $data, 'links' => $links, 'positiveCount' => count($links)];
    }
}
