<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\VariableSearchHelper;
use InvalidArgumentException;

require_once __DIR__ . '/helper/VariableSearchHelper.php';
require_once __DIR__ . '/EChartsVariablePresentation.php';

/** Bounded discovery and append-only, ID-based source drafts. No persistent writes. */
final class EChartsSourceImport
{
    /** @return list<array<string,mixed>> */
    public static function Search(array $options, array $sources): array
    {
        $root = $options['RootID'] ?? null;
        $recursive = $options['Recursive'] ?? null;
        $hidden = $options['IncludeHidden'] ?? null;
        $name = $options['NameFilter'] ?? null;
        $unit = $options['UnitFilter'] ?? null;
        if (!is_int($root) || $root < 0 || !is_bool($recursive) || !is_bool($hidden)
            || !is_string($name) || !is_string($unit) || mb_strlen(trim($unit)) > 120) {
            throw new InvalidArgumentException('The source search settings are invalid.');
        }
        if ($root > 0 && (!IPS_ObjectExists($root)
            || !in_array(IPS_GetObject($root)['ObjectType'] ?? null, [0, 1], true))) {
            throw new InvalidArgumentException('Select a category or instance as the search root.');
        }
        $result = VariableSearchHelper::Search($root, $name, [1, 2], $hidden, $recursive ? 100 : 1, 5000, 500);
        if ($result['truncated']) {
            throw new InvalidArgumentException('Search limit reached (5000 objects or 500 matches). Narrow the branch or name filter.');
        }
        $existing = array_fill_keys(array_column($sources, 'VariableID'), true);
        $rows = [];
        foreach ($result['rows'] as $row) {
            $presentation = EChartsVariablePresentation::Resolve($row['VariableID'], 0, 100, '', 2, true, true);
            if (trim($unit) !== '' && $presentation['unit'] !== trim($unit)) {
                continue;
            }
            $rows[] = $row + ['Selected' => false, 'Unit' => $presentation['unit'],
                'AlreadyPresent'         => isset($existing[$row['VariableID']])];
        }
        if (strlen(json_encode($rows, JSON_THROW_ON_ERROR)) > 250000) {
            throw new InvalidArgumentException('Search results are too large. Narrow the branch or name filter.');
        }
        return $rows;
    }

    /** Keeps every existing row, field and position; selection is revalidated against a fresh search. */
    public static function Append(array $sources, array $submitted, array $fresh, int $maximumSources): array
    {
        if (!array_is_list($sources) || !array_is_list($submitted) || count($submitted) > 500
            || $maximumSources < 1 || $maximumSources > 100) {
            throw new InvalidArgumentException('The source import list is invalid.');
        }
        $selected = VariableSearchHelper::ResolveSelectedVariableIDs($submitted, $fresh);
        if ($selected === []) {
            throw new InvalidArgumentException('Select at least one matching variable.');
        }
        $existing = array_fill_keys(array_column($sources, 'VariableID'), true);
        foreach ($selected as $id) {
            if (!isset($existing[$id])) {
                $sources[] = ['VariableID' => $id, 'Label' => '', 'Color' => -1, 'Minimum' => 0, 'Maximum' => 100];
                $existing[$id] = true;
            }
        }
        if (count($sources) > $maximumSources) {
            throw new InvalidArgumentException('The selection exceeds the source limit. Select fewer variables.');
        }
        if (strlen(json_encode($sources, JSON_THROW_ON_ERROR)) > 65536) {
            throw new InvalidArgumentException('The merged source list exceeds 64 KiB. Select fewer variables.');
        }
        return $sources;
    }
}
