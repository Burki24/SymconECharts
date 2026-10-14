<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

/** Explicit numeric status rules and worst-visible-descendant aggregation for Tree. */
final class TreeHealth
{
    /**
     * @return array<int,array{direction:string,warning:float,critical:float}>
     */
    public static function Parse(mixed $input): array
    {
        if (self::Issues($input) !== []) {
            throw new InvalidArgumentException('The Tree status rules are invalid.');
        }
        if (is_string($input)) {
            $input = json_decode($input, true, 64, JSON_THROW_ON_ERROR);
        }
        $rules = [];
        foreach ($input as $row) {
            $id = $row['VariableID'];
            $rules[$id] = [
                'direction' => $row['Direction'],
                'warning'   => (float) $row['WarningAt'],
                'critical'  => (float) $row['CriticalAt']
            ];
        }
        return $rules;
    }

    /** @return list<array{row:int,code:string,id?:int,firstRow?:int}> */
    public static function Issues(mixed $input): array
    {
        if (is_string($input)) {
            try {
                $input = json_decode($input, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [['row' => 0, 'code' => 'invalid']];
            }
        }
        if (!is_array($input) || !array_is_list($input)) {
            return [['row' => 0, 'code' => 'invalid']];
        }
        if (count($input) > 3000) {
            return [['row' => 0, 'code' => 'too_many']];
        }
        $issues = [];
        $firstRows = [];
        foreach ($input as $index => $row) {
            $number = $index + 1;
            $id = is_array($row) ? ($row['VariableID'] ?? null) : null;
            $direction = is_array($row) ? ($row['Direction'] ?? null) : null;
            $warning = is_array($row) ? ($row['WarningAt'] ?? null) : null;
            $critical = is_array($row) ? ($row['CriticalAt'] ?? null) : null;
            if (!is_int($id) || $id < 1 || !in_array($direction, ['high', 'low'], true)
                || (!is_int($warning) && !is_float($warning))
                || (!is_int($critical) && !is_float($critical))
                || !is_finite((float) $warning) || !is_finite((float) $critical)) {
                $issues[] = ['row' => $number, 'code' => 'invalid'];
                continue;
            }
            if (isset($firstRows[$id])) {
                $issues[] = ['row' => $number, 'code' => 'duplicate', 'id' => $id, 'firstRow' => $firstRows[$id]];
            } else {
                $firstRows[$id] = $number;
            }
            if (($direction === 'high' && $critical < $warning)
                || ($direction === 'low' && $critical > $warning)) {
                $issues[] = ['row' => $number, 'code' => 'threshold', 'id' => $id];
            }
        }
        return $issues;
    }

    /**
     * @param array<string,mixed> $root
     * @param array<int,array{direction:string,warning:float,critical:float}> $rules
     * @param callable(int):mixed $readValue
     * @return array<string,mixed>
     */
    public static function Annotate(array $root, array $rules, callable $readValue): array
    {
        if ($rules === []) {
            return $root;
        }
        [$result] = self::AnnotateNode($root, $rules, $readValue);
        return $result;
    }

    /**
     * @param array<string,mixed> $node
     * @param array<int,array{direction:string,warning:float,critical:float}> $rules
     * @param callable(int):mixed $readValue
     * @return array{array<string,mixed>,int}
     */
    private static function AnnotateNode(array $node, array $rules, callable $readValue): array
    {
        $severity = -1;
        if (($node['kind'] ?? null) === 'variable') {
            $id = filter_var(substr((string) ($node['id'] ?? ''), 7), FILTER_VALIDATE_INT);
            if (is_int($id) && isset($rules[$id]) && $node['id'] === 'object-' . $id) {
                $value = $readValue($id);
                if (!is_bool($value) && !is_int($value) && !is_float($value)) {
                    throw new RuntimeException('A configured Tree status value is not numeric.');
                }
                $number = (float) $value;
                if (!is_finite($number)) {
                    throw new RuntimeException('A configured Tree status value is not finite.');
                }
                $rule = $rules[$id];
                $critical = $rule['direction'] === 'high'
                    ? $number >= $rule['critical'] : $number <= $rule['critical'];
                $warning = $rule['direction'] === 'high'
                    ? $number >= $rule['warning'] : $number <= $rule['warning'];
                $severity = $critical ? 2 : ($warning ? 1 : 0);
            }
        }
        foreach ($node['children'] ?? [] as $index => $child) {
            [$node['children'][$index], $childSeverity] = self::AnnotateNode($child, $rules, $readValue);
            $severity = max($severity, $childSeverity);
        }
        if ($severity >= 0) {
            $node['health'] = ['ok', 'warning', 'critical'][$severity];
        }
        return [$node, $severity];
    }
}
