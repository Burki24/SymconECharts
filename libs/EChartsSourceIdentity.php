<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Resolves display labels without using names or paths as source identities. */
final class EChartsSourceIdentity
{
    /**
     * @param list<array{VariableID:int,Label:string}> $sources
     * @return array<int,string> Labels indexed by the unique source variable ID.
     */
    public static function LabelsForSources(array $sources): array
    {
        $labels = [];
        foreach ($sources as $source) {
            $variableID = $source['VariableID'];
            if ($variableID <= 0 || isset($labels[$variableID])) {
                throw new InvalidArgumentException('Source variable IDs must be positive and unique.');
            }
            $labels[$variableID] = $source['Label'] !== ''
                ? $source['Label']
                : IPS_GetName($variableID);
        }

        return self::UniqueLabels($labels);
    }

    /**
     * Resolves complete draft lists for previews; invalid drafts retain their existing fallback display.
     *
     * @param list<mixed> $sources
     * @return array<int,string>
     */
    public static function LabelsForDraftSources(array $sources): array
    {
        $valid = [];
        $seen = [];
        foreach ($sources as $source) {
            if (!is_array($source) || !is_int($source['VariableID'] ?? null)
                || $source['VariableID'] <= 0 || !IPS_VariableExists($source['VariableID'])
                || isset($seen[$source['VariableID']])
            ) {
                return [];
            }
            $variableID = $source['VariableID'];
            $seen[$variableID] = true;
            $valid[] = [
                'VariableID' => $variableID,
                'Label'      => trim(is_string($source['Label'] ?? null) ? $source['Label'] : '')
            ];
        }

        return self::LabelsForSources($valid);
    }

    /**
     * @param array<int,string> $labelsByVariableID
     * @param list<string> $reservedLabels Names of shared presentation groups, not source IDs.
     * @return array<int,string>
     */
    public static function UniqueLabels(array $labelsByVariableID, array $reservedLabels = []): array
    {
        if ($labelsByVariableID === []) {
            return [];
        }

        $labels = $labelsByVariableID;
        for ($pass = 0; $pass <= count($labelsByVariableID) + count($reservedLabels); ++$pass) {
            $counts = array_count_values(array_merge(array_values($labels), $reservedLabels));
            $collisions = false;
            foreach ($labels as $variableID => $label) {
                if ($counts[$label] <= 1) {
                    continue;
                }
                $labels[$variableID] = $label . ' (#' . $variableID . ')';
                $collisions = true;
            }
            if (!$collisions) {
                return $labels;
            }
        }

        throw new InvalidArgumentException('Source labels could not be distinguished by variable ID.');
    }
}
