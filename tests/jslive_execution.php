<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';
require_once __DIR__ . '/../libs/EChartsJSLiveCreationForm.php';
require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveArchive;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePage;
use SymconECharts\EChartsJSLivePlan;
use SymconECharts\EChartsJSLiveRendering;
use SymconECharts\EChartsJSLiveTargetSnapshot;

const VARIABLE_PRESENTATION_WEB_CONTENT = '{00000000-0000-4000-8000-000000000001}';
set_error_handler(static function (int $severity, string $message): never
{ throw new RuntimeException($message); });
$GLOBALS['nativeImport'] = ['objects' => [], 'instances' => [], 'variables' => [], 'profiles' => [], 'templates' => [], 'outputGuards' => [],
    'configuration'                   => [], 'guard' => [], 'archives' => [], 'created' => [], 'mutations' => [], 'locked' => false, 'next' => 20000, 'children' => []];
function nativeObject(int $id, string $name, int $type = 1, int $parent = 0): array
{
    return ['ObjectID' => $id, 'ObjectName' => $name, 'ObjectType' => $type, 'ParentID' => $parent, 'ObjectIdent' => '', 'ObjectInfo' => '',
        'ObjectIcon'   => '', 'ObjectPosition' => 0, 'ObjectIsHidden' => false, 'ObjectIsDisabled' => false, 'ObjectHasChildren' => false];
}
function nativeInstance(string $module, int $connection = 0): array
{
    return ['ModuleInfo' => ['ModuleID' => $module], 'ConnectionID' => $connection, 'InstanceStatus' => 102];
}
function IPS_InstanceExists(int $id): bool
{
    return isset($GLOBALS['nativeImport']['instances'][$id]);
}
function IPS_VariableExists(int $id): bool
{
    return isset($GLOBALS['nativeImport']['variables'][$id]);
}
function IPS_GetObject(int $id): array
{
    return $GLOBALS['nativeImport']['objects'][$id] ?? throw new RuntimeException('Missing object');
}
function IPS_GetInstance(int $id): array
{
    return $GLOBALS['nativeImport']['instances'][$id] ?? throw new RuntimeException('Missing instance');
}
function IPS_GetConfiguration(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['configuration'][$id]);
}
function IPS_GetVariable(int $id): array
{
    return $GLOBALS['nativeImport']['variables'][$id]['metadata'];
}
function IPS_GetVariablePresentation(int $id): array
{
    return $GLOBALS['nativeImport']['variables'][$id]['presentation'];
}
function IPS_GetVariableProfile(string $name): array
{
    return $GLOBALS['nativeImport']['profiles'][$name];
}
function IPS_VariableProfileExists(string $name): bool
{
    return isset($GLOBALS['nativeImport']['profiles'][$name]);
}
function IPS_GetTemplate(string $id): array
{
    return $GLOBALS['nativeImport']['templates'][$id];
}
function GetValue(int $id): mixed
{
    return $GLOBALS['nativeImport']['variables'][$id]['value'];
}
function IPS_GetChildrenIDs(int $id): array
{
    return $GLOBALS['nativeImport']['children'][$id] ?? [];
}
function IPS_GetInstanceListByModuleID(string $module): array
{
    return array_keys(array_filter($GLOBALS['nativeImport']['instances'], static fn (array $i): bool => $i['ModuleInfo']['ModuleID'] === $module));
}
function SymconJSLive_GetMigrationInstallationID(int $id): string
{
    return '22222222-2222-4222-8222-222222222222';
}
function AC_GetLoggingStatus(int $archive, int $id): bool
{
    return $GLOBALS['nativeImport']['archives'][$id]['logging'];
}
function AC_GetAggregationType(int $archive, int $id): int
{
    return $GLOBALS['nativeImport']['archives'][$id]['aggregationType'];
}
function AC_GetCounterIgnoreZeros(int $archive, int $id): bool
{
    return $GLOBALS['nativeImport']['archives'][$id]['counterSettings']['ignoreZeros'];
}
function AC_GetLoggedValues(int $archive, int $id, int $from, int $until, int $limit): array
{
    $value = GetValue($id);
    return [['TimeStamp' => $until, 'Value' => $value], ['TimeStamp' => intdiv($from + $until, 2), 'Value' => $value], ['TimeStamp' => $from, 'Value' => $value]];
}
function AC_GetAggregatedValues(int $archive, int $id, int $level, int $from, int $until, int $limit): array
{
    return array_map(static fn (array $r): array => ['TimeStamp' => $r['TimeStamp'], 'Avg' => $r['Value']], AC_GetLoggedValues($archive, $id, $from, $until, $limit));
}
function IPS_SemaphoreEnter(string $name, int $timeout): bool
{
    if ($GLOBALS['nativeImport']['locked']) {
        return false;
    }
    return $GLOBALS['nativeImport']['locked'] = true;
}
function IPS_SemaphoreLeave(string $name): bool
{
    $GLOBALS['nativeImport']['locked'] = false;
    return true;
}
function nativeMutation(int $id, string $action): void
{
    if (!$GLOBALS['nativeImport']['locked'] || !isset($GLOBALS['nativeImport']['created'][$id])) {
        throw new RuntimeException('Attempt to modify a pre-existing object or write without lock');
    }
    $GLOBALS['nativeImport']['mutations'][] = [$id, $action];
}
function IPS_CreateInstance(string $module): int
{
    if (!$GLOBALS['nativeImport']['locked']) {
        throw new RuntimeException('Create without lock');
    }
    $id = ++$GLOBALS['nativeImport']['next'];
    $GLOBALS['nativeImport']['instances'][$id] = nativeInstance($module);
    $GLOBALS['nativeImport']['objects'][$id] = nativeObject($id, 'New target');
    $GLOBALS['nativeImport']['configuration'][$id] = ['Title' => '', 'JSLiveImportModel' => '', 'EnableIPSView' => false];
    $GLOBALS['nativeImport']['created'][$id] = true;
    $GLOBALS['nativeImport']['guard'][$id] = EChartsJSLiveTargetSnapshot::CreationHash(EChartsJSLiveTargetSnapshot::Read($id));
    return $id;
}
function IPS_SetInfo(int $id, string $value): bool
{
    nativeMutation($id, 'info');
    $GLOBALS['nativeImport']['objects'][$id]['ObjectInfo'] = $value;
    return true;
}
function IPS_SetName(int $id, string $value): bool
{
    nativeMutation($id, 'name');
    $GLOBALS['nativeImport']['objects'][$id]['ObjectName'] = $value;
    return true;
}
function IPS_SetParent(int $id, int $value): bool
{
    nativeMutation($id, 'parent');
    $GLOBALS['nativeImport']['objects'][$id]['ParentID'] = $value;
    if ($value > 0) {
        $GLOBALS['nativeImport']['objects'][$value]['ObjectHasChildren'] = true;
    } return true;
}
function IPS_ConnectInstance(int $id, int $value): bool
{
    nativeMutation($id, 'connection');
    $GLOBALS['nativeImport']['instances'][$id]['ConnectionID'] = $value;
    return true;
}
function IPS_DisconnectInstance(int $id): bool
{
    nativeMutation($id, 'disconnect');
    $GLOBALS['nativeImport']['instances'][$id]['ConnectionID'] = 0;
    return true;
}
function IPS_SetConfiguration(int $id, string $value): bool
{
    nativeMutation($id, 'configuration');
    $GLOBALS['nativeImport']['configuration'][$id] = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
    return true;
}
function nativeState(int $id): array
{
    $c = $GLOBALS['nativeImport']['configuration'][$id];
    $model = json_decode($c['JSLiveImportModel'], true, 64, JSON_THROW_ON_ERROR);
    $model = EChartsJSLiveModel::Read($c['JSLiveImportModel'], $model['family']);
    return EChartsJSLiveRendering::State($model, EChartsJSLiveArchive::Read(...), time(), EChartsJSLivePlan::Design($c));
}
function nativeHTML(int $id, bool $ipsView): string
{
    return EChartsJSLivePage::Render(nativeState($id), $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58]);
}
function IPS_ApplyChanges(int $id): bool
{
    nativeMutation($id, 'apply');
    if ($GLOBALS['nativeImport']['configuration'][$id]['EnableIPSView']) {
        $child = $GLOBALS['nativeImport']['children'][$id][0] ?? ($id + 10000);
        $GLOBALS['nativeImport']['children'][$id] = [$child];
        $GLOBALS['nativeImport']['objects'][$child] = nativeObject($child, 'Chart for IPSView', 2, $id);
        $GLOBALS['nativeImport']['objects'][$child]['ObjectIdent'] = 'WebContent';
        $GLOBALS['nativeImport']['variables'][$child] = ['metadata' => ['VariableType' => 3, 'VariableAction' => 0, 'VariableCustomAction' => 0],
            'presentation'                                          => ['PRESENTATION' => VARIABLE_PRESENTATION_WEB_CONTENT], 'value' => nativeHTML($id, true)];
        $GLOBALS['nativeImport']['objects'][$child]['ObjectInfo'] = $GLOBALS['nativeImport']['objects'][$id]['ObjectInfo'] . ':output:WebContent';
        $GLOBALS['nativeImport']['outputGuards'][$id][$child] = EChartsImportJson::Hash(EChartsJSLiveTargetSnapshot::Output($child));
    }
    return true;
}

// Native exported function signatures are exercised by the production kernel adapter, not bypassed by a journal fake.
function ECGS_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECPI_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECTS_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECBH_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECRH_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECPG_GetJSLiveCreationGuard(int $id): string
{
    return $GLOBALS['nativeImport']['guard'][$id];
}
function ECGS_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECPI_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECTS_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECBH_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECRH_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECPG_GetJSLiveOutputGuards(int $id): string
{
    return EChartsImportJson::Canonical($GLOBALS['nativeImport']['outputGuards'][$id] ?? []);
}
function ECGS_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECPI_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECTS_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECBH_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECRH_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECPG_GetJSLiveImportData(int $id): string
{
    return EChartsImportJson::Canonical(nativeState($id));
}
function ECGS_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}
function ECPI_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}
function ECTS_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}
function ECBH_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}
function ECRH_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}
function ECPG_GetJSLiveImportHTML(int $id, bool $ipsView): string
{
    return nativeHTML($id, $ipsView);
}

final class NativeImportGateway
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsJSLiveCreationForm;
    public int $InstanceID = 16000;
    public array $fields = [];
    public array $debug = [];
    private array $attributes = [];
    public function __construct()
    {
        $this->RegisterJSLiveImportJournal();
    }
    protected function RegisterAttributeString(string $name, string $default): void
    {
        $this->attributes[$name] ??= $default;
    }
    protected function ReadAttributeString(string $name): string
    {
        return $this->attributes[$name];
    }
    protected function WriteAttributeString(string $name, string $value): bool
    {
        $this->attributes[$name] = $value;
        return true;
    }
    protected function SendDebug(string $message, string $data, int $format): bool
    {
        $this->debug[] = $data;
        return true;
    }
    protected function UpdateFormField(string $name, string $field, mixed $value): void
    {
        $this->fields[$name][$field] = $value;
    }
    protected function Translate(string $value): string
    {
        return $value;
    }
}
function nativeAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function nativeThrows(callable $work, string $code): void
{
    try {
        $work();
    } catch (Throwable $e) {
        nativeAssert(str_starts_with($e->getMessage(), $code), $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected ' . $code);
}
function ECGW_JSLiveImportFromForm(int $id, string $file, string $mapping, int $parent, string $action, string $confirmation, int $chart, bool $ipsView): string
{
    if (func_num_args() !== 8 || $id !== 16000) {
        throw new RuntimeException('Wrong native import callback signature');
    }
    ++$GLOBALS['nativeFormCalls'];
    return $GLOBALS['nativeGateway']->JSLiveImportFromForm($file, $mapping, $parent, $action, $confirmation, $chart, $ipsView);
}
$kernel = &$GLOBALS['nativeImport'];
$kernel['instances'][16000] = nativeInstance('{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}');
$kernel['objects'][16000] = nativeObject(16000, 'Existing gateway');
$kernel['configuration'][16000] = [];
$kernel['objects'][15000] = nativeObject(15000, 'Target category', 0);
$kernel['instances'][17000] = nativeInstance('{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}');
$kernel['objects'][17000] = nativeObject(17000, 'Existing JSLive gateway');
$kernel['instances'][2] = nativeInstance('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
$kernel['objects'][2] = nativeObject(2, 'Archive');
$gateway = new NativeImportGateway();
$count = 0;
$GLOBALS['nativeGateway'] = $gateway;
$GLOBALS['nativeFormCalls'] = 0;
foreach (glob(__DIR__ . '/fixtures/jslive-packages/*.json') as $file) {
    [$json, $package] = jsliveFamilyFixture($file);
    $original = $package->charts[0];
    $originalID = $original->origin->instanceId;
    $kernel['instances'][$originalID] = nativeInstance($original->origin->moduleId, 17000);
    $kernel['objects'][$originalID] = nativeObject($originalID, $original->origin->instanceName);
    $kernel['configuration'][$originalID] = EChartsJSLivePackageArray($original->original->configuration);
    $kernel['configuration'][17000] = EChartsJSLivePackageArray($original->original->globalConfiguration);
    foreach ($original->sources as $source) {
        $id = $source->variableId;
        $type = $source->variableType;
        $p = EChartsJSLivePackageArray($source->presentation->original);
        $kernel['variables'][$id] = ['metadata' => $p['variable'] + ['VariableUpdated' => 1793448000, 'VariableAction' => 0, 'VariableCustomAction' => 0],
            'presentation'                      => $p['presentation'], 'value' => $type === 0 ? true : ($type === 1 ? 21 : 21.5)];
        $kernel['objects'][$id] = nativeObject($id, $source->label, 2);
        $kernel['objects'][$id]['ObjectIcon'] = $p['objectIcon'];
        $kernel['profiles'] = array_replace($kernel['profiles'], $p['profiles']);
        $kernel['templates'] = array_replace($kernel['templates'], $p['templates']);
        $kernel['archives'][$id] = EChartsJSLivePackageArray($source->archive);
    }
    foreach ($original->original->runtimeState as $control) {
        $id = $control->variableId;
        $kernel['variables'][$id] = ['metadata' => ['VariableType' => is_bool($control->value) ? 0 : 1, 'VariableAction' => 0, 'VariableCustomAction' => 0],
            'presentation'                      => [], 'value' => $control->value];
        $kernel['objects'][$id] = nativeObject($id, 'Existing control', 2, $originalID);
    }
    $before = EChartsImportJson::Hash([$kernel['configuration'][$originalID], $kernel['configuration'][16000], $kernel['configuration'][17000],
        array_intersect_key($kernel['variables'], array_flip(array_column($original->sources, 'variableId'))), $kernel['profiles'], $kernel['archives']]);
    $plan = json_decode($gateway->PrepareJSLiveImport($json, '{}', 15000), true, 64, JSON_THROW_ON_ERROR);
    $row = array_values($plan['charts'])[0];
    nativeAssert(!$row['blocked'], basename($file) . ': ' . EChartsImportJson::Canonical($row['issues']));
    $confirmation = ['confirmedNewInstances' => true, 'planHash' => $plan['planHash'], 'charts' => [['chartKey' => $row['chartKey'], 'accept' => $row['differences']]]];
    $unconfirmed = $confirmation;
    $unconfirmed['confirmedNewInstances'] = false;
    nativeThrows(fn () => $gateway->ExecuteJSLiveImport($json, '{}', 15000, EChartsImportJson::Canonical($unconfirmed)), 'IMPORT_BLOCKED:');
    $result = json_decode($gateway->ExecuteJSLiveImport($json, '{}', 15000, EChartsImportJson::Canonical($confirmation)), true, 64, JSON_THROW_ON_ERROR);
    $result = $result['charts'][$row['chartKey']];
    nativeAssert($result['status'] === 'complete', basename($file) . ': ' . EChartsImportJson::Canonical($result));
    $mutations = count($kernel['mutations']);
    $again = json_decode($gateway->ExecuteJSLiveImport($json, '{}', 15000, EChartsImportJson::Canonical($confirmation)), true, 64, JSON_THROW_ON_ERROR);
    nativeAssert($again['charts'][$row['chartKey']]['targetID'] === $result['targetID'] && count($kernel['mutations']) === $mutations, 'Completed retry must not reapply configuration.');
    $after = EChartsImportJson::Hash([$kernel['configuration'][$originalID], $kernel['configuration'][16000], $kernel['configuration'][17000],
        array_intersect_key($kernel['variables'], array_flip(array_column($original->sources, 'variableId'))), $kernel['profiles'], $kernel['archives']]);
    nativeAssert($before === $after, 'Existing source, native formatting, profile, original/gateway configuration or archive changed.');
    nativeAssert(!$kernel['locked'], 'Semaphore leaked.');
    ++$count;
}
nativeAssert($count === 40 && count($kernel['created']) === 40, 'Exactly 40 new target instances expected.');
// Execute actual form scripts against the complete required native signature; no callbacks are merely regex-counted.
$lastNativeResult = $result;
$lastNativeRow = $row;
$lastNativeConfirmation = $confirmation;
$form = json_decode(file_get_contents(__DIR__ . '/../EChartsGateway/form.json'), true, 64, JSON_THROW_ON_ERROR);
$JSLiveImportFile = base64_encode($json);
$JSLiveImportSources = [];
$JSLiveImportArchives = [];
$JSLiveReadyParent = 15000;
$JSLiveReadyChart = 1;
$JSLiveReadySeparateFrom = '';
$id = 16000;
$mutations = count($kernel['mutations']);
foreach ($form['actions'][1]['items'] as $item) {
    $script = $item['onClick'] ?? '';
    if (!str_contains($script, 'ECGW_JSLiveImportFromForm(')) {
        continue;
    }
    if (str_contains($script, "'execute'")) {
        $JSLiveReadyHash = $gateway->fields['JSLiveReadyHash']['value'];
        $JSLiveReadyKey = $gateway->fields['JSLiveReadyKey']['value'];
        $JSLiveReadyDifferences = json_decode($gateway->fields['JSLiveReadyDifferences']['values'], true, 64, JSON_THROW_ON_ERROR);
        foreach ($JSLiveReadyDifferences as &$difference) {
            $difference['Accept'] = true;
        } unset($difference);
        $JSLiveReadyConfirmed = true;
    }
    ob_start();
    eval($script);
    $output = ob_get_clean();
    if (isset($item['download'])) {
        nativeAssert(str_starts_with($output, 'data:'), 'Native report/preview form callback did not return a download.');
    }
    else {
        nativeAssert($output === '', 'Successful native form action must not display an opaque success popup.');
    }
}
nativeAssert($GLOBALS['nativeFormCalls'] === 5 && count($kernel['created']) === 40 && count($kernel['mutations']) === $mutations, 'Full native form flow must reuse the completed target without writes.');
$error = $gateway->JSLiveImportFromForm('invalid base64', '{}', 15000, 'prepare', '{}', 0, false);
nativeAssert(str_starts_with($error, 'PACKAGE_INVALID:') && $gateway->fields['JSLiveReadyHash']['value'] === ''
    && str_contains($gateway->fields['JSLiveReadySummary']['caption'], 'PACKAGE_INVALID'), 'Invalid upload must clear stale confirmation and show a visible error.');
nativeAssert((new ReflectionMethod(NativeImportGateway::class, 'JSLiveImportFromForm'))->getNumberOfRequiredParameters() === 7, 'Native callback arguments must not depend on PHP defaults.');

// The output guard participates in drift detection, even if the parent configuration remains unchanged.
$lastTarget = $lastNativeResult['targetID'];
$child = $kernel['children'][$lastTarget][0];
$outputGuard = $kernel['outputGuards'][$lastTarget];
unset($kernel['outputGuards'][$lastTarget]);
$unproven = json_decode($gateway->ExecuteJSLiveImport($json, '{}', 15000, EChartsImportJson::Canonical($lastNativeConfirmation)), true, 64, JSON_THROW_ON_ERROR);
nativeAssert($unproven['charts'][$lastNativeRow['chartKey']]['error'] === 'RECOVERY_REQUIRED'
    && count($kernel['mutations']) === $mutations, 'An unproven output after interruption must stop recovery without writes.');
$kernel['outputGuards'][$lastTarget] = $outputGuard;
$kernel['objects'][$child]['ObjectName'] = 'Owner renamed output';
$preserve = $kernel['objects'][$child];
$changed = json_decode($gateway->ExecuteJSLiveImport($json, '{}', 15000, EChartsImportJson::Canonical($lastNativeConfirmation)), true, 64, JSON_THROW_ON_ERROR);
nativeAssert($changed['charts'][$lastNativeRow['chartKey']]['error'] === 'TARGET_CHANGED' && $kernel['objects'][$child] === $preserve
    && count($kernel['mutations']) === $mutations, 'A changed output must never be overwritten on import retry.');
nativeAssert(!str_contains(implode('\n', $gateway->debug), 'contentBase64') && !str_contains(implode('\n', $gateway->debug), 'JSLiveImportModel'), 'Private import contents leaked into debug.');
echo "JSLive confirmed public API and native kernel adapter: 40 new targets, both HTML outputs, repeat without writes, unchanged originals/sources/profiles/archives verified.\n";

function EChartsJSLivePackageArray(mixed $value): mixed
{
    return \SymconECharts\EChartsJSLivePackage::ArrayValue($value);
}
