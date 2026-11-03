<?php

declare(strict_types=1);

use SymconECharts\EChartsHierarchySources;

require_once __DIR__ . '/../libs/EChartsHierarchySources.php';

// Synthetic metadata only; a preview must never read numeric values or archives.
function IPS_VariableExists(int $id): bool
{
    return in_array($id, [81001, 81002], true);
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2];
}
function IPS_GetName(int $id): string
{
    return 'Equal label';
}
function GetValue(int $id): never
{
    throw new LogicException('Hierarchy previews must not read source values.');
}

foreach (['Treemap', 'Sunburst'] as $family) {
    $rows = [['VariableID' => 81001, 'Group' => 'House / literal', 'Subgroup' => 'Power / literal', 'Label' => ''],
        ['VariableID' => 81002, 'GroupPath' => 'House / Floor / Room / Power / Rack / Circuit / Device', 'Label' => 'Equal label']];
    $json = json_encode($rows, JSON_THROW_ON_ERROR);
    $resolved = EChartsHierarchySources::Resolve($json, $family);
    if ($resolved[0]['Path'] !== ['House / literal', 'Power / literal']
        || count($resolved[1]['Path']) !== 7 || $resolved[0]['VariableID'] === $resolved[1]['VariableID']) {
        throw new RuntimeException('Shared hierarchy validation must preserve literal legacy names and ID-based deep sources.');
    }
    $preview = EChartsHierarchySources::Preview($rows, $family);
    if ($preview['sourceCount'] !== 2 || $preview['depth'] !== 8 || $preview['sample'] !== true
        || $preview['total'] !== 2.0 || $json !== json_encode($rows, JSON_THROW_ON_ERROR)) {
        throw new RuntimeException('Synthetic preview data must retain all validated paths without source values or mutations.');
    }
    if (EChartsHierarchySources::Preview([], $family)['sourceCount'] !== 0) {
        throw new RuntimeException('Empty configuration must be explicitly identified as example structure.');
    }
    foreach ([null, 'invalid', [['VariableID' => 81001, 'GroupPath' => 'House//Room']],
        [['VariableID' => 81001, 'GroupPath' => 'A/B/C/D/E/F/G/H']],
        [['VariableID' => 81001, 'Group' => 'House', 'Label' => []]],
        [$rows[0], $rows[0]], [['VariableID' => 81003, 'Group' => 'House']]] as $invalid) {
        try {
            EChartsHierarchySources::Resolve(json_encode($invalid, JSON_THROW_ON_ERROR), $family);
            throw new LogicException('Invalid hierarchy sources must be rejected, not converted to an example tree.');
        } catch (InvalidArgumentException) {
        }
    }
    $fallback = $rows[0] + ['GroupPath' => '   '];
    if (EChartsHierarchySources::Resolve(json_encode([$fallback]), $family)[0]['Path'] !== $resolved[0]['Path']) {
        throw new RuntimeException('An empty additive GroupPath field must preserve old source rows.');
    }
}

echo "Shared hierarchy sources and read-only structure previews verified.\n";
