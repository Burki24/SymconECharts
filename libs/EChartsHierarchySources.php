<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use JsonException;

require_once __DIR__ . '/TreemapModel.php';

/** Shared source validation for manual Treemap/Sunburst hierarchies, not renderer design. */
final class EChartsHierarchySources
{
    public static function Resolve(string $json, string $family = 'Treemap'): array
    {
        if (!in_array($family, ['Treemap', 'Sunburst'], true)) {
            throw new InvalidArgumentException('Unsupported hierarchy family.');
        }
        try {
            if (strlen($json) > 262144) {
                throw new InvalidArgumentException('The hierarchy source configuration is too large.');
            }
            $sources = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The ' . $family . ' variables are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 1 || count($sources) > TreemapModel::MAXIMUM_SOURCES) {
            throw new InvalidArgumentException('Configure 1 to 100 unique numeric ' . $family . ' variables.');
        }
        $result = [];
        $seen = [];
        foreach ($sources as $source) {
            $id = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException($family . ' variables must be unique numeric variables.');
            }
            $label = $source['Label'] ?? '';
            if (!is_string($label) || mb_strlen(trim($label)) > 120) {
                throw new InvalidArgumentException($family . ' labels need at most 120 characters.');
            }
            $path = TreemapModel::Path($source);
            $result[] = ['VariableID' => $id, 'Group' => $path[0], 'Subgroup' => $path[1] ?? '',
                'Label'               => trim($label), 'Path' => $path];
            $seen[$id] = true;
        }
        return $result;
    }

    /** Synthetic weights preserve configured structure without reading live values or archives. */
    public static function Preview(mixed $raw, string $family = 'Treemap'): array
    {
        if (is_string($raw)) {
            try {
                $raw = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            }
            catch (JsonException $exception) {
                throw new InvalidArgumentException('Invalid hierarchy preview sources.', 0, $exception);
            }
        }
        if ($raw === []) {
            return TreemapModel::Build([
                ['VariableID' => 1, 'Group' => 'Group A', 'Subgroup' => 'Subgroup A', 'Label' => 'Value A'],
                ['VariableID' => 2, 'Group' => 'Group B', 'Subgroup' => '', 'Label' => 'Value B']
            ], [1 => ['Value' => 2.0, 'Timestamp' => 0, 'Name' => 'Value A'],
                2 => ['Value' => 1.0, 'Timestamp' => 0, 'Name' => 'Value B']], $family) + ['sourceCount' => 0, 'sample' => true];
        }
        $sources = self::Resolve(json_encode($raw, JSON_THROW_ON_ERROR), $family);
        $values = [];
        foreach ($sources as $source) {
            $values[$source['VariableID']] = ['Value' => 1.0, 'Timestamp' => 0, 'Name' => IPS_GetName($source['VariableID'])];
        }
        return TreemapModel::Build($sources, $values, $family) + ['sourceCount' => count($sources), 'sample' => true];
    }
}
