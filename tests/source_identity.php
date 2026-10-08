<?php

declare(strict_types=1);

use SymconECharts\EChartsSourceIdentity;

require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';

function IPS_VariableExists(int $variableID): bool
{
    return in_array($variableID, [4711, 4717, 4718], true);
}

function IPS_GetName(int $variableID): string
{
    return match ($variableID) {
        4711, 4717 => 'Temperatur',
        4718       => 'Luftfeuchtigkeit',
        default    => ''
    };
}

function assertSourceIdentity(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertSourceIdentity(EChartsSourceIdentity::UniqueLabels([]) === [], 'An empty source list must stay empty.');
assertSourceIdentity(
    EChartsSourceIdentity::UniqueLabels([4711 => 'Temperatur']) === [4711 => 'Temperatur'],
    'A unique source label must remain unchanged.'
);
assertSourceIdentity(
    EChartsSourceIdentity::UniqueLabels([4711 => 'Temperatur', 4717 => 'Temperatur']) === [
        4711 => 'Temperatur (#4711)',
        4717 => 'Temperatur (#4717)'
    ],
    'Equal source names must be distinguished only by their variable IDs.'
);
assertSourceIdentity(
    EChartsSourceIdentity::UniqueLabels([
        4711 => 'Temperatur',
        4717 => 'Temperatur',
        4718 => 'Temperatur (#4711)'
    ]) === [
        4711 => 'Temperatur (#4711) (#4711)',
        4717 => 'Temperatur (#4717)',
        4718 => 'Temperatur (#4711) (#4718)'
    ],
    'A literal label containing an ID suffix must not hide another source.'
);
assertSourceIdentity(
    EChartsSourceIdentity::UniqueLabels([4711 => 'Temperatur (#4711)'], ['Temperatur (#4711)']) === [
        4711 => 'Temperatur (#4711) (#4711)'
    ],
    'A source-specific label must remain distinct from a shared presentation-group label.'
);
assertSourceIdentity(
    EChartsSourceIdentity::LabelsForSources([
        ['VariableID' => 4711, 'Label' => ''],
        ['VariableID' => 4717, 'Label' => '']
    ]) === [4711 => 'Temperatur (#4711)', 4717 => 'Temperatur (#4717)'],
    'Equal names inherited from Symcon must be resolved by variable ID.'
);
assertSourceIdentity(
    EChartsSourceIdentity::LabelsForDraftSources([
        ['VariableID' => 4711, 'Label' => 'Sensor'],
        ['VariableID' => 4717, 'Label' => 'Sensor']
    ]) === [4711 => 'Sensor (#4711)', 4717 => 'Sensor (#4717)'],
    'A complete form draft must preview the same ID-based labels as the saved chart.'
);
assertSourceIdentity(
    EChartsSourceIdentity::LabelsForDraftSources([
        ['VariableID' => 4711, 'Label' => 'Sensor'],
        ['VariableID' => 0, 'Label' => 'Sensor']
    ]) === [],
    'An incomplete draft must not invent an index- or path-based source identity.'
);

echo "Source variable identity and label collision handling verified.\n";
