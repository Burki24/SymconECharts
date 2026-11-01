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
function jsliveTargetTick(IPSModuleStrict $device): void
{
    $_IPS = ['TARGET' => $device->InstanceID];
    eval($device->GetTestTimerScript('JSLiveImportRefresh'));
}
function ECGS_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
}
function ECPI_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
}
function ECTS_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
}
function ECBH_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
}
function ECRH_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
}
function ECPG_RefreshJSLiveImport(int $id): void
{
    jsliveTargetModule($id)->RefreshJSLiveImport();
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
    // Reload while the gateway is not ready. Only the registered timer may recover; no second ApplyChanges.
    $referencesBeforeRetry = $device->GetTestReferences();
    $healthyResponder = IPSModuleStrict::$ParentResponder;
    $readsBeforeInitialization = 0;
    IPSModuleStrict::$ParentResponder = static function (string $request) use ($healthyResponder, &$readsBeforeInitialization): string
    {
        ++$readsBeforeInitialization;
        return $healthyResponder($request);
    };
    $device->SetTestParentActive(false);
    $device->ReloadTestModule();
    $device->ApplyChanges();
    $cadence = max(1, min(300, (int) ($model['global']['RefreshTime'] ?? 3))) * 1000;
    jsliveTargetAssert(
        $device->GetTestStatus() === 298 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence,
        'A valid imported model lost its retry timer during gateway startup: ' . $name
    );
    for ($attempt = 0; $attempt < 2; ++$attempt) {
        jsliveTargetTick($device);
        jsliveTargetAssert($device->GetTestStatus() === 298 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence
            && json_decode($device->GetJSLiveImportData(), true)['status'] === 'error', 'Failed initialization was incorrectly reported as ready.');
    }
    foreach ([false, true] as $ipsView) {
        jsliveTargetAssert(str_starts_with($device->GetJSLiveImportHTML($ipsView), '<!doctype html>'), 'Pending import output is invalid.');
    }
    jsliveTargetAssert($readsBeforeInitialization === 0, 'A pending import read live/archive values through a not-yet-ready gateway.');
    IPSModuleStrict::$ParentResponder = $healthyResponder;
    $device->SetTestParentActive(true);
    jsliveTargetTick($device);
    jsliveTargetAssert($device->GetTestStatus() === 102 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence
        && $device->GetTestReferences() === $referencesBeforeRetry && $device->GetTestProperties() === $propertiesBeforeReload
        && IPS_GetChildrenIDs($id) === $children && $device->GetJSLiveOutputGuards() === $outputGuardsBeforeReload
        && $device->GetJSLiveCreationGuard() === $creationGuard, 'Timer recovery changed configuration, references, outputs or ownership.');

    // A later data-flow failure must also recover through the same timer at the unchanged cadence.
    $healthyResponder = IPSModuleStrict::$ParentResponder;
    IPSModuleStrict::$ParentResponder = static fn (string $request): string => throw new RuntimeException('Synthetic parent read outage');
    jsliveTargetTick($device);
    jsliveTargetAssert($device->GetTestStatus() === 298 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence, 'A failed read disabled regular recovery.');
    IPSModuleStrict::$ParentResponder = $healthyResponder;
    jsliveTargetTick($device);
    jsliveTargetAssert($device->GetTestStatus() === 102, 'The restored data flow did not recover on the next timer callback.');

    if (!isset($families[$name])) {
        // Kernel startup remains deferred until IPS_KERNELSTARTED; invalid models never acquire a polling loop.
        $GLOBALS['symconTestKernelRunlevel'] = KR_READY - 1;
        $device->ReloadTestModule();
        $device->ApplyChanges();
        jsliveTargetAssert($device->GetTestTimerInterval('JSLiveImportRefresh') === 0, 'Import timer started before the kernel was ready.');
        unset($GLOBALS['symconTestKernelRunlevel']);
        $device->MessageSink(1793448000, 0, IPS_KERNELSTARTED, []);
        jsliveTargetAssert($device->GetTestStatus() === 102 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence, 'Kernel startup did not restore the import timer.');
        $device->SetTestProperty('JSLiveImportModel', 'invalid model');
        $device->ApplyChanges();
        $device->RefreshJSLiveImport();
        jsliveTargetAssert($device->GetTestStatus() === 298 && $device->GetTestTimerInterval('JSLiveImportRefresh') === 0
            && json_decode($device->GetJSLiveImportData(), true)['status'] === 'error', 'Invalid models must stay blocked without timer reads.');
        $device->SetTestProperty('JSLiveImportModel', $propertiesBeforeReload['JSLiveImportModel']);
        $device->ApplyChanges();
        $device->SetTestProperty('JSLiveImportModel', '');
        $device->RefreshJSLiveImport();
        jsliveTargetAssert($device->GetTestTimerInterval('JSLiveImportRefresh') === 0, 'Removed import model kept its timer running.');
        $device->SetTestProperty('JSLiveImportModel', $propertiesBeforeReload['JSLiveImportModel']);
        $device->ApplyChanges();

        $sourceID = array_values($model['sources'])[0]['variableID'];
        $sourceType = $GLOBALS['symconTestVariables'][$sourceID]['VariableType'];
        $GLOBALS['symconTestVariables'][$sourceID]['VariableType'] = 3;
        $device->ApplyChanges();
        jsliveTargetTick($device);
        jsliveTargetAssert($device->GetTestStatus() === 298 && $device->GetTestTimerInterval('JSLiveImportRefresh') === $cadence
            && json_decode($device->GetJSLiveImportData(), true)['status'] === 'error', 'Invalid source types were silently accepted during recovery.');
        $GLOBALS['symconTestVariables'][$sourceID]['VariableType'] = $sourceType;
        jsliveTargetTick($device);
        jsliveTargetAssert($device->GetTestStatus() === 102, 'Corrected source metadata required a second ApplyChanges.');
    }
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
echo "JSLive real module classes: 40 variants, six families, reload/startup timer recovery, creation seals, native outputs, references and full-arity draft callbacks verified.\n";
