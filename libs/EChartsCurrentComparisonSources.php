<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use JsonException;

require_once __DIR__ . '/EChartsSourceIdentity.php';
require_once __DIR__ . '/EChartsVariablePresentation.php';
require_once __DIR__ . '/EChartsNormalization.php';

/** ID-based current comparisons with an explicit family-specific source budget. */
final class EChartsCurrentComparisonSources
{
    public static function Resolve(string $json, string $family, int $maximumSources = 16, bool $normalize = false): array
    {
        if ($maximumSources < 1 || $maximumSources > 100) {
            throw new InvalidArgumentException('Invalid current-comparison source budget.');
        }
        try {
            if (strlen($json) > 65536) {
                throw new InvalidArgumentException($family . ' sources are too large.');
            }
            $raw = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException($family . ' sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > $maximumSources) {
            throw new InvalidArgumentException('Configure 1 to ' . $maximumSources . ' unique numeric ' . $family . ' variables.');
        }
        $sources = [];
        $units = [];
        $seen = [];
        foreach ($raw as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            $label = is_array($entry) ? ($entry['Label'] ?? '') : null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || mb_strlen(trim($label)) > 120) {
                throw new InvalidArgumentException($family . ' sources need distinct numeric variable IDs and valid labels.');
            }
            $color = $entry['Color'] ?? -1;
            if (!is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException($family . ' source colors are invalid.');
            }
            $presentation = EChartsVariablePresentation::Resolve($id, 0.0, 100.0, '', 2, true);
            $sources[] = ['VariableID' => $id, 'Label' => trim($label),
                'Color'                => $color, 'Unit' => $presentation['unit'], 'Decimals' => $presentation['decimals']];
            if ($normalize) {
                [$minimum, $maximum] = EChartsNormalization::Range($entry);
                $sources[array_key_last($sources)] += ['Minimum' => $minimum, 'Maximum' => $maximum];
            }
            $units[$presentation['unit']] = true;
            $seen[$id] = true;
        }
        if (!$normalize && count($units) > 1) {
            throw new InvalidArgumentException($family . ' variables must use the same unit.');
        }
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        foreach ($sources as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
        }
        unset($source);
        return $sources;
    }
}
