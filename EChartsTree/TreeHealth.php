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
        if (is_string($input)) {
            try {
                $input = json_decode($input, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new InvalidArgumentException('The Tree status rules are invalid.');
            }
        }
        if (!is_array($input) || !array_is_list($input) || count($input) > 3000) {
            throw new InvalidArgumentException('The Tree status rules are invalid.');
        }
        $rules = [];
        foreach ($input as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('The Tree status rules are invalid.');
            }
            $id = $row['VariableID'] ?? null;
            $direction = $row['Direction'] ?? null;
            $warning = $row['WarningAt'] ?? null;
            $critical = $row['CriticalAt'] ?? null;
            if (!is_int($id) || $id < 1 || isset($rules[$id])
                || !in_array($direction, ['high', 'low'], true)
                || (!is_int($warning) && !is_float($warning))
                || (!is_int($critical) && !is_float($critical))
                || !is_finite((float) $warning) || !is_finite((float) $critical)
                || ($direction === 'high' && $critical < $warning)
                || ($direction === 'low' && $critical > $warning)) {
                throw new InvalidArgumentException('The Tree status rules are invalid.');
            }
            $rules[$id] = [
                'direction' => $direction,
                'warning'   => (float) $warning,
                'critical'  => (float) $critical
            ];
        }
        return $rules;
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
