<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePackage;
use SymconECharts\EChartsJSLivePlan;

function radarDashAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** Keep the complete producer contract consistent while varying only the list field. */
function radarDashPackage(mixed $dash): array
{
    [$json, $package, $metadata, $reader] = jsliveFamilyFixture(__DIR__ . '/fixtures/jslive-packages/case-11.json');
    $chart = $package->charts[0];
    radarDashAssert($chart->origin->family === 'radar-history', 'The regression needs a real exported Radar fixture.');
    $rows = EChartsImportJson::Decode($chart->original->configuration->Datasets);
    $rows[0]->Dash = $dash;
    $chart->original->configuration->Datasets = EChartsImportJson::Canonical($rows);
    $chart->appearance->settings = $chart->original->configuration;
    foreach ($chart->bindings as $binding) {
        if (str_starts_with($binding->originalPointer, '/Datasets/0')) {
            $binding->settings->Dash = $dash;
            $binding->appearance->Dash = $dash;
        }
    }
    $json = EChartsImportJson::Canonical($package);
    EChartsJSLivePackage::Read($json);
    return [$json, $package, $metadata, $reader];
}

function radarDashPlan(mixed $dash): array
{
    [$json, $package, $metadata, $reader] = radarDashPackage($dash);
    $before = EChartsImportJson::Canonical($package);
    $plan = EChartsJSLivePlan::Prepare(
        $json,
        [],
        ['gatewayID' => 16000, 'parentID' => 15000, 'fingerprint' => str_repeat('c', 64)],
        static fn (int $id, ?int $archive): array => $metadata[$id],
        $reader,
        $package->sourceInstallation->id,
        1793448000
    );
    radarDashAssert($before === EChartsImportJson::Canonical($package), 'Planning must not change the export.');
    return array_values($plan['charts'])[0];
}

foreach (['', [], [['Order' => 2, 'Length' => 7], ['Order' => 1, 'Length' => 0]]] as $dash) {
    $row = radarDashPlan($dash);
    radarDashAssert(!$row['blocked'], 'A supported Radar dash list was blocked: ' . EChartsImportJson::Canonical($row['issues']));
    radarDashAssert($row['target']['module'] === 'EChartsRadarHistory', 'The complete plan must keep the historical Radar model.');
    $model = EChartsJSLiveModel::Read($row['target']['configuration']['JSLiveImportModel'], 'radar-history');
    radarDashAssert(EChartsImportJson::Canonical($model['settings']['Datasets'][0]['Dash']) === EChartsImportJson::Canonical($dash), 'The frozen model must preserve order, empty arrays, lengths and zero.');
    radarDashAssert(EChartsImportJson::Canonical($model['bindings'][0]['settings']['Dash']) === EChartsImportJson::Canonical($dash), 'Bindings must preserve the same original dash settings.');
}

foreach ([
    true,
    7,
    [4],
    [['Order' => false, 'Length' => 3]],
    [['Order' => 1, 'Length' => '3']],
    [['Order' => 1, 'Length' => 3, 'Unknown' => 0]],
    (object) ['unexpected' => []],
    array_fill(0, 101, ['Order' => 1, 'Length' => 2])
] as $dash) {
    $row = radarDashPlan($dash);
    radarDashAssert($row['blocked'], 'Unknown fields, invalid types, non-lists and oversized dash lists must stay blocked.');
    radarDashAssert(in_array($row['issues'][0]['code'], ['FORMAT_UNSUPPORTED', 'LIMIT_EXCEEDED'], true), 'A rejected dash must retain an explicit validation error.');
}

echo "JSLive Radar dash lists: full plans, frozen models, preserved values and invalid/oversized-list rejection verified.\n";
