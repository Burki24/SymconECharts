<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

require_once __DIR__ . '/EChartsNormalization.php';
require_once __DIR__ . '/EChartsBarPreview.php';

/** Projects explicitly chosen Symcon objects and connections, optionally directed. */
final class GraphModel
{
    public const MAXIMUM_NODES = 300;
    public const MAXIMUM_EDGES = 1000;
    public const PREVIEW_NODES = 40;
    public const PREVIEW_EDGES = 80;

    /** @param list<array<string,mixed>> $nodeRows @param list<array<string,mixed>> $edgeRows */
    public static function ValidateRows(array $nodeRows, array $edgeRows, bool $directed = false): void
    {
        if (count($nodeRows) < 2 || count($nodeRows) > self::MAXIMUM_NODES
            || count($edgeRows) < 1 || count($edgeRows) > self::MAXIMUM_EDGES) {
            throw new InvalidArgumentException('Graph requires 2–300 nodes and 1–1000 connections.');
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
            $pair = $directed ? $source . ':' . $target : min($source, $target) . ':' . max($source, $target);
            if (isset($pairs[$pair])) {
                throw new InvalidArgumentException('Duplicate Graph connections are not allowed.');
            }
            $pairs[$pair] = true;
            $mode = $row['WeightMode'] ?? 'none';
            if (!in_array($mode, ['none', 'manual', 'variable'], true)) {
                throw new InvalidArgumentException('Graph weight mode is invalid.');
            }
            if ($mode === 'manual') {
                $weight = $row['Weight'] ?? 1;
                $unit = $row['WeightUnit'] ?? '';
                $decimals = $row['WeightDecimals'] ?? 2;
                if ((!is_int($weight) && !is_float($weight)) || !is_finite((float) $weight) || $weight < 0
                    || !is_string($unit) || mb_strlen($unit) > 24 || !is_int($decimals) || $decimals < 0 || $decimals > 6) {
                    throw new InvalidArgumentException('Manual Graph weights require a finite nonnegative value, a short unit and 0–6 decimals.');
                }
            } elseif ($mode === 'variable' && (!is_int($row['WeightVariableID'] ?? null) || $row['WeightVariableID'] <= 0)) {
                throw new InvalidArgumentException('Graph weights require a valid numeric variable ID.');
            }
        }
    }

    /** @param list<array<string,mixed>> $edges @return list<int> */
    public static function WeightVariableIDs(array $edges): array
    {
        $ids = [];
        foreach ($edges as $edge) {
            if (is_array($edge) && ($edge['WeightMode'] ?? 'none') === 'variable' && is_int($edge['WeightVariableID'] ?? null) && $edge['WeightVariableID'] > 0) {
                $ids[$edge['WeightVariableID']] = true;
            }
        }
        $result = array_keys($ids);
        sort($result);
        return $result;
    }

    /** Validate only active weight scales; dormant defaults never invalidate legacy Graphs. */
    public static function ValidateWeights(array $edges, array $options): void
    {
        foreach ($edges as $edge) {
            if (($edge['WeightMode'] ?? 'none') === 'none') {
                continue;
            }
            if (!EChartsValueRange::IsValid($options['minimum'] ?? null, $options['maximum'] ?? null)
                || $options['minimum'] < 0) {
                throw new InvalidArgumentException('Graph weights require finite fixed limits with 0 <= minimum < maximum.');
            }
            foreach (['minimumWidth', 'maximumWidth'] as $key) {
                $value = $options[$key] ?? null;
                if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 1 || $value > 20) {
                    throw new InvalidArgumentException('Graph weight widths must be between 1 and 20 pixels.');
                }
            }
            if ($options['minimumWidth'] > $options['maximumWidth']) {
                throw new InvalidArgumentException('Minimum Graph width must not exceed maximum width.');
            }
            return;
        }
    }

    /**
     * @param list<array<string,mixed>> $nodeRows
     * @param list<array<string,mixed>> $edgeRows
     * @param callable(int):array<string,mixed> $getObject
     * @param callable(int):string $getFormattedValue
     * @return array{directed:bool,nodes:list<array<string,mixed>>,links:list<array<string,mixed>>}
     */
    public static function Build(array $nodeRows, array $edgeRows, callable $getObject, callable $getFormattedValue, bool $directed = false, array $weightOptions = [], ?callable $readWeight = null): array
    {
        self::ValidateRows($nodeRows, $edgeRows, $directed);
        self::ValidateWeights($edgeRows, $weightOptions);
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
        $pairs = [];
        if ($directed) {
            foreach ($edgeRows as $row) {
                $pairs[$row['SourceObjectID'] . ':' . $row['TargetObjectID']] = true;
            }
        }
        foreach ($edgeRows as $row) {
            $source = $row['SourceObjectID'];
            $target = $row['TargetObjectID'];
            $link = ['source' => 'object-' . $source, 'target' => 'object-' . $target];
            if ($directed) {
                $link['reciprocal'] = isset($pairs[$target . ':' . $source]);
            }
            $mode = $row['WeightMode'] ?? 'none';
            if ($mode !== 'none') {
                $weight = $mode === 'manual' ? [
                    'value'        => (float) ($row['Weight'] ?? 1),
                    'displayValue' => EChartsBarPreview::FormatValue(
                        (float) ($row['Weight'] ?? 1),
                        $row['WeightDecimals'] ?? 2,
                        trim($row['WeightUnit'] ?? ''),
                        $weightOptions['language'] ?? 'de'
                    ),
                    'unit' => trim($row['WeightUnit'] ?? ''), 'timestamp' => null
                ] : ($readWeight !== null ? $readWeight($row['WeightVariableID']) : throw new InvalidArgumentException('Graph weight reader is unavailable.'));
                $value = $weight['value'] ?? null;
                if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0
                    || !is_string($weight['displayValue'] ?? null) || !is_string($weight['unit'] ?? null)
                    || ($mode === 'variable' && (!is_int($weight['timestamp'] ?? null) || $weight['timestamp'] < 0))) {
                    throw new InvalidArgumentException('Graph weight sources must provide finite nonnegative numbers and valid display metadata.');
                }
                $weight['value'] = (float) $value;
                $weight['variableID'] = $mode === 'variable' ? $row['WeightVariableID'] : null;
                $weight['minimum'] = (float) $weightOptions['minimum'];
                $weight['maximum'] = (float) $weightOptions['maximum'];
                $percent = EChartsNormalization::Value(
                    (float) $value,
                    ['Minimum' => $weightOptions['minimum'], 'Maximum' => $weightOptions['maximum']]
                );
                $link['width'] = (float) ($weightOptions['minimumWidth'] + $percent / 100 * ($weightOptions['maximumWidth'] - $weightOptions['minimumWidth']));
                $link['weight'] = $weight;
            }
            $links[] = $link;
        }
        return ['directed' => $directed, 'nodes' => $nodes, 'links' => $links];
    }
}
