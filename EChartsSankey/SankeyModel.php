<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use Throwable;

/** Bounded acyclic flow configuration; display labels never identify nodes or sources. */
final class SankeyModel
{
    private const BALANCE_RELATIVE_TOLERANCE = 1.0e-12;
    /** Resolve a common raw-value unit without converting or normalizing flows. */
    public static function ResolveUnit(array $sources, string $mode, string $unit, ?callable $readUnit = null, ?callable $translate = null): string
    {
        $t = $translate ?? static fn (string $text): string => $text;
        if (!in_array($mode, ['manual', 'automatic', 'checked'], true)) {
            throw new InvalidArgumentException($t('The Sankey unit mode is invalid.'));
        }
        $unit = trim($unit);
        if ($mode !== 'automatic' && mb_strlen($unit) > 24) {
            throw new InvalidArgumentException($t('The Sankey unit must not exceed 24 characters.'));
        }
        if ($mode === 'manual') {
            return $unit;
        }
        $units = [];
        $expected = $mode === 'checked' ? $unit : null;
        foreach ($sources as $index => $source) {
            $id = $source['VariableID'];
            if (!array_key_exists($id, $units)) {
                try {
                    $resolved = $readUnit !== null ? $readUnit($id) : null;
                    if (!is_string($resolved)) {
                        throw new InvalidArgumentException('Invalid source unit.');
                    }
                    $units[$id] = trim($resolved);
                } catch (Throwable) {
                    throw new InvalidArgumentException(sprintf($t('Connection %d: the source unit could not be read.'), $index + 1));
                }
            }
            $actual = $units[$id];
            if (mb_strlen($actual) > 24) {
                throw new InvalidArgumentException(sprintf($t('Connection %d: the source unit must not exceed 24 characters.'), $index + 1));
            }
            $expected ??= $actual;
            if ($actual !== $expected) {
                throw new InvalidArgumentException(sprintf(
                    $t('Connection %d: source unit "%s" does not match "%s". Values are not converted.'),
                    $index + 1,
                    $actual !== '' ? $actual : $t('(unitless)'),
                    $expected !== '' ? $expected : $t('(unitless)')
                ));
            }
        }
        return $expected ?? '';
    }

    /** @return array{nodes:list<array{Key:string,Label:string,Balance?:bool}>,sources:list<array{Source:string,Target:string,VariableID:int,ReverseNegative?:bool}>} */
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
            if (array_key_exists('Balance', $node) && !is_bool($node['Balance'])) {
                throw new InvalidArgumentException(sprintf($t('Node %d: balance must be enabled or disabled.'), $index + 1));
            }
            $positions[$key] = count($normalized);
            $normalized[] = ['Key' => $key, 'Label' => $label,
                ...(($node['Balance'] ?? false) ? ['Balance' => true] : [])];
        }
        $edges = [];
        $pairs = [];
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
            if (array_key_exists('ReverseNegative', $edge) && !is_bool($edge['ReverseNegative'])) {
                throw new InvalidArgumentException(sprintf($t('Connection %d: negative-flow reversal must be enabled or disabled.'), $index + 1));
            }
            $edges[] = ['Source' => $source, 'Target' => $target, 'VariableID' => $id,
                ...(($edge['ReverseNegative'] ?? false) ? ['ReverseNegative' => true] : [])];
        }
        self::AssertDirections(array_keys($positions), $edges, $t, false);
        return ['nodes' => $normalized, 'sources' => $edges];
    }

    /** @param array<int,array{Value:float,Timestamp:int}> $values @return array{data:list<array<string,mixed>>,links:list<array<string,mixed>>,positiveCount:int} */
    public static function Build(array $nodes, array $sources, array $values, ?callable $translate = null): array
    {
        $configuration = self::Validate($nodes, $sources, $translate);
        $t = $translate ?? static fn (string $text): string => $text;
        $links = [];
        $directions = [];
        $incoming = [];
        $outgoing = [];
        $sum = 0.0;
        foreach ($configuration['sources'] as $index => $edge) {
            $id = $edge['VariableID'];
            $value = $values[$id]['Value'] ?? null;
            $timestamp = $values[$id]['Timestamp'] ?? null;
            $reverse = $edge['ReverseNegative'] ?? false;
            if ((!is_float($value) && !is_int($value)) || !is_finite((float) $value) || ($value < 0 && !$reverse)
                || !is_int($timestamp)) {
                throw new InvalidArgumentException(sprintf($t($reverse
                    ? 'Connection %d: the current value must be finite.'
                    : 'Connection %d: the current value must be finite and non-negative.'), $index + 1));
            }
            $reversed = $value < 0;
            $source = $reversed ? $edge['Target'] : $edge['Source'];
            $target = $reversed ? $edge['Source'] : $edge['Target'];
            $directions[] = ['Source' => $source, 'Target' => $target];
            if ($value == 0) {
                continue;
            }
            $originalValue = (float) $value;
            $value = abs((float) $value);
            $outgoing[$source] = ($outgoing[$source] ?? 0.0) + $value;
            $incoming[$target] = ($incoming[$target] ?? 0.0) + $value;
            $sum += $value;
            if (!is_finite($sum) || !is_finite($outgoing[$source]) || !is_finite($incoming[$target])) {
                throw new InvalidArgumentException($t('Sankey flow totals exceed the numeric range.'));
            }
            $links[] = ['source' => $source, 'target' => $target, 'value' => (float) $value,
                'variableID'     => $id, 'timestamp' => $timestamp,
                ...($reversed ? ['originalValue' => $originalValue, 'configuredSource' => $edge['Source'],
                    'configuredTarget'           => $edge['Target'], 'reversed' => true] : [])];
        }
        self::AssertDirections(array_column($configuration['nodes'], 'Key'), $directions, $t, true);
        $data = [];
        foreach ($configuration['nodes'] as $node) {
            $key = $node['Key'];
            if (isset($incoming[$key]) || isset($outgoing[$key])) {
                $data[] = ['name' => $key, 'label' => $node['Label'],
                    'value'       => max($incoming[$key] ?? 0.0, $outgoing[$key] ?? 0.0)];
            }
        }
        return self::AppendBalances($configuration, $data, $links, $incoming, $outgoing, $directions, $t);
    }

    /** Derive terminals only from measured totals; never feed generated values into another balance. */
    private static function AppendBalances(array $configuration, array $data, array $links, array $incoming, array $outgoing, array $directions, callable $t): array
    {
        $keys = array_column($configuration['nodes'], 'Key');
        $generated = 0;
        foreach ($configuration['nodes'] as $index => $node) {
            if (!($node['Balance'] ?? false)) {
                continue;
            }
            $key = $node['Key'];
            $in = $incoming[$key] ?? 0.0;
            $out = $outgoing[$key] ?? 0.0;
            $difference = $in - $out;
            $scale = max($in, $out);
            if ($scale == 0.0 || abs($difference) / $scale <= self::BALANCE_RELATIVE_TOLERANCE) {
                continue;
            }
            if (count($configuration['nodes']) + $generated + 1 > 100
                || count($configuration['sources']) + $generated + 1 > 200) {
                throw new InvalidArgumentException(sprintf($t('Node %d: calculated balances must fit the total limit of 100 nodes and 200 connections.'), $index + 1));
            }
            // Configured keys start with a letter, so this namespace cannot collide.
            $virtual = '@balance:' . $key;
            $source = $difference > 0 ? $key : $virtual;
            $target = $difference > 0 ? $virtual : $key;
            $metadata = ['calculated' => true, 'balanceFor' => $key,
                'balanceIncoming'     => $in, 'balanceOutgoing' => $out];
            $data[] = ['name' => $virtual,
                'label'       => $t($difference > 0 ? 'Unassigned (calculated)' : 'Unexplained inflow (calculated)'),
                'value'       => abs($difference), ...$metadata];
            $links[] = ['source' => $source, 'target' => $target, 'value' => abs($difference), ...$metadata];
            $keys[] = $virtual;
            $directions[] = ['Source' => $source, 'Target' => $target];
            ++$generated;
        }
        if ($generated > 0) {
            self::AssertDirections($keys, $directions, $t, true);
        }
        return ['data' => $data, 'links' => $links, 'positiveCount' => count($links)];
    }

    /** Shared configured/effective topology check, including invisible zero flows. */
    private static function AssertDirections(array $keys, array $edges, callable $t, bool $effective): void
    {
        $incoming = array_fill_keys($keys, 0);
        $outgoing = array_fill_keys($keys, []);
        $pairs = [];
        foreach ($edges as $index => $edge) {
            $source = $edge['Source'];
            $target = $edge['Target'];
            $pair = $source . "\0" . $target;
            if (isset($pairs[$pair])) {
                throw new InvalidArgumentException(sprintf($t('Connection %d: flow directions create a duplicate directed pair after reversing negative values.'), $index + 1));
            }
            $pairs[$pair] = true;
            ++$incoming[$target];
            $outgoing[$source][] = $target;
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
        if ($visited !== count($keys)) {
            throw new InvalidArgumentException($t($effective
                ? 'Sankey flow directions contain a cycle after reversing negative values.'
                : 'Sankey connections contain a cycle.'));
        }
    }
}
