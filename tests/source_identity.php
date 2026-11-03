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

assertSourceIdentity(
    EChartsSourceIdentity::LabelsForSources([
        ['VariableID' => 4711, 'Label' => ''],
        ['VariableID' => 4717, 'Label' => '']
    ]) === [4711 => 'Temperatur', 4717 => 'Temperatur'],
    'Equal Symcon names must stay visible without exposing internal IDs.'
);
assertSourceIdentity(
    EChartsSourceIdentity::LabelsForDraftSources([
        ['VariableID' => 4711, 'Label' => 'Sensor'],
        ['VariableID' => 4717, 'Label' => 'Sensor']
    ]) === [4711 => 'Sensor', 4717 => 'Sensor'],
    'A complete form draft must keep configured names without exposing IDs.'
);
assertSourceIdentity(
    EChartsSourceIdentity::LabelsForDraftSources([
        ['VariableID' => 4711, 'Label' => 'Sensor'],
        ['VariableID' => 0, 'Label' => 'Sensor']
    ]) === [],
    'An incomplete draft must not invent an index- or path-based source identity.'
);

echo "Source variable identity and display names verified.\n";
