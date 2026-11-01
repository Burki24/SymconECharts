<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';
require_once __DIR__ . '/../EChartsRadarHistory/module.php';
require_once __DIR__ . '/../EChartsProgress/module.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveImport;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePlan;
use SymconECharts\EChartsJSLiveRendering;
use SymconECharts\EChartsJSLiveTargetSnapshot;

function jsliveTargetAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function jsliveTargetModule(int $id): IPSModuleStrict
{
    return $GLOBALS['symconTestModuleObjects'][$id];
}
function ECGS_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECPI_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECTS_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECBH_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECRH_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECPG_UpdateJSLiveImportColorKey(int $id, string $draft): void
{
    jsliveTargetModule($id)->UpdateJSLiveImportColorKey($draft);
}
function ECGS_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}
function ECPI_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}
function ECTS_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}
function ECBH_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}
function ECRH_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}
function ECPG_PreviewJSLiveImportDesign(int $id, string $draft, bool $ipsView): string
{
    return jsliveTargetModule($id)->PreviewJSLiveImportDesign($draft, $ipsView);
}

$GLOBALS['symconTestLinkOutputObjects'] = true;
(new ReflectionProperty(IPSModuleStrict::class, 'nextVariableID'))->setValue(null, 45000);
$gateway = new EChartsGateway();
$gateway->Create();
$gateway->ApplyChanges();
IPSModuleStrict::$ParentResponder = static fn (string $request): string => $gateway->ForwardData($request);
$GLOBALS['symconTestInstances'][6000] = ['InstanceID' => 6000, 'ConnectionID' => 0, 'InstanceStatus' => 102,
    'ModuleInfo'                                      => ['ModuleID' => '{43192F0B-135B-4CE7-A0A7-1475603F3060}']];
$families = [];
$count = 0;
foreach (glob(__DIR__ . '/fixtures/jslive-packages/*.json') as $file) {
    [$json, $package, $metadata] = jsliveFamilyFixture($file);
    $report = EChartsJSLiveImport::Analyze($json, [], static fn (int $id, ?int $archive): array => $metadata[$id], $package->sourceInstallation->id);
    $model = EChartsJSLiveModel::Compile($package->charts[0], $report['charts'][0], $package->assets);
    $name = EChartsJSLiveRendering::Target($model);
    $moduleInfo = EChartsJSLivePlan::Modules()[$name];
    $id = 5700 + ++$count;
    $GLOBALS['symconTestInstances'][$id] = ['InstanceID' => $id, 'ConnectionID' => 5000, 'InstanceStatus' => 102,
        'ModuleInfo'                                     => ['ModuleID' => $moduleInfo['moduleId']]];
    $device = new $name();
    $device->InstanceID = $id;
    $device->Create();
    $snapshot = EChartsJSLiveTargetSnapshot::Read($id);
    $snapshot['creationGuard'] = $device->GetJSLiveCreationGuard();
    jsliveTargetAssert(EChartsJSLiveTargetSnapshot::IsUnchangedCreation($snapshot), 'Creation defaults were not sealed for ' . $name);
    $object = IPS_GetObject($id) + ['ObjectType' => 1, 'ObjectID' => $id];
    $object['ObjectInfo'] = 'SymconECharts:JSLive:1:' . str_repeat('a', 64) . ':' . sprintf('%032x', $count);
    $GLOBALS['symconTestTreeObjects'][$id] = $object;
    $sourceMapping = [];
    foreach ($model['sources'] as $key => &$source) {
        $sourceID = 16000 + count($GLOBALS['symconTestVariables']);
        $type = $source['variableType'];
        $GLOBALS['symconTestVariables'][$sourceID] = ['VariableType' => $type, 'VariableUpdated' => 1793448000,
            'Value'                                                  => $type === 0 ? true : ($type === 1 ? 21 : 21.5), 'Name' => 'Synthetic imported source'];
        $GLOBALS['symconTestArchiveVariables'][$sourceID] = ['AggregationType' => $source['archive']['aggregationType'] ?? 0,
            'CounterIgnoreZeros'                                               => $source['archive']['counterSettings']['ignoreZeros'] ?? false, 'LoggedValues' => [], 'AggregatedValues' => []];
        $sourceMapping[$source['variableID']] = $sourceID;
        $source['variableID'] = $sourceID;
        $source['archiveID'] = 6000;
        foreach ($model['bindings'] as &$binding) {
            if ($binding['sourceKey'] === $key) {
                $binding['variableID'] = $sourceID;
            }
        }
        unset($binding);
    }
    unset($source);
    $rebind = static function (array $row) use (&$rebind, $sourceMapping): array
    {
        foreach ($row as $key => $value) {
            if (in_array($key, ['Variable', 'Reference'], true) && is_int($value) && $value > 0) {
                $row[$key] = $sourceMapping[$value];
            }
            elseif (is_array($value)) {
                $row[$key] = $rebind($value);
            }
        }
        return $row;
    };
    $model['settings'] = $rebind($model['settings']);
    foreach ($model['bindings'] as &$binding) {
        $binding['settings'] = $rebind($binding['settings']);
    }
    unset($binding, $rebind);
    $device->SetTestProperty('JSLiveImportModel', EChartsImportJson::Canonical($model));
    $device->SetTestProperty('Title', $model['settings']['title_text'] ?? '');
    $device->SetTestProperty('EnableIPSView', true);
    foreach (['Period' => 'Period', 'Relativ' => 'Relative', 'Now' => 'Now', 'StartDate' => 'StartDate'] as $source => $target) {
        if (isset($model['time']['selection'][$source])) {
            $device->SetTestProperty('JSLive' . $target, $model['time']['selection'][$source]['value']);
        }
    }
    $sourceKeys = array_flip(array_column($model['sources'], 'variableID'));
    $before = EChartsImportJson::Hash([array_intersect_key($GLOBALS['symconTestVariables'], $sourceKeys), $GLOBALS['symconTestArchiveVariables']]);
    $device->ApplyChanges();
    $creationGuard = $device->GetJSLiveCreationGuard();
    $propertiesBeforeReload = $device->GetTestProperties();
    $outputGuardsBeforeReload = $device->GetJSLiveOutputGuards();
    $device->ReloadTestModule();
    jsliveTargetAssert($device->GetJSLiveCreationGuard() === $creationGuard
        && !isset($device->TestCreateAttributeWrites['JSLiveCreationGuard']), 'Module reload rewrote the persisted creation proof for ' . $name);
    $device->ApplyChanges();
    jsliveTargetAssert($device->GetTestProperties() === $propertiesBeforeReload
        && $device->GetJSLiveOutputGuards() === $outputGuardsBeforeReload, 'Module reload changed imported settings or output ownership.');
    $guards = json_decode($device->GetJSLiveOutputGuards(), true, 16, JSON_THROW_ON_ERROR);
    $children = IPS_GetChildrenIDs($id);
    jsliveTargetAssert(
        count($children) === 1 && isset($guards[$children[0]]) && hash_equals($guards[$children[0]], EChartsImportJson::Hash(EChartsJSLiveTargetSnapshot::Output($children[0]))),
        'Output ownership was not sealed by the real module class.'
    );
    $state = json_decode($device->GetJSLiveImportData(), true, 64, JSON_THROW_ON_ERROR);
    jsliveTargetAssert($device->GetTestStatus() === 102 && $state['status'] === 'ready' && isset($state['chart']['data']), basename($file) . ': ' . EChartsImportJson::Canonical($state));
    jsliveTargetAssert(count($device->GetTestReferences()) >= count($model['sources']), 'Imported references were not registered.');
    foreach ([false, true] as $ipsView) {
        $html = $device->GetJSLiveImportHTML($ipsView);
        jsliveTargetAssert(str_starts_with($html, '<!doctype html>') && strlen($html) <= 1048576, 'Native module output invalid.');
    }
    $form = json_decode($device->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
    $properties = $device->GetTestProperties();
    $flat = flattenDesignerSections($form['elements']);
    $onChange = array_values(array_filter($flat, static fn (array $f): bool => ($f['name'] ?? '') === 'JSLiveBackgroundColor'))[0]['onChange'];
    extract($properties, EXTR_OVERWRITE);
    eval($onChange);
    foreach (array_filter($form['actions'], static fn (array $f): bool => isset($f['download'])) as $action) {
        ob_start();
        eval($action['onClick']);
        $download = ob_get_clean();
        jsliveTargetAssert(str_starts_with($download, 'data:text/html;base64,'), 'Native target draft download callback failed.');
    }
    jsliveTargetAssert($properties === $device->GetTestProperties(), 'Read-only design callbacks persisted draft settings.');
    jsliveTargetAssert($before === EChartsImportJson::Hash([array_intersect_key($GLOBALS['symconTestVariables'], $sourceKeys), $GLOBALS['symconTestArchiveVariables']]), 'Rendering changed source or archive data.');
    $families[$name] = true;
    // Large generated output snapshots are no longer needed after this device's assertions.
    unset($device, $form, $html, $state, $model, $report, $package, $json, $metadata, $properties, $flat);
    unset($GLOBALS['symconTestModuleObjects'][$id]);
    gc_collect_cycles();
}
jsliveTargetAssert(count($families) === 6 && $count === 40, 'All native target families and variants must be covered.');
echo "JSLive real module classes: 40 variants, six families, creation seals, Gateway snapshots, native outputs, references and full-arity draft callbacks verified.\n";
