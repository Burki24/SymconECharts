<?php

declare(strict_types=1);

// Real EChartsTree class; only the Kernel boundary is doubled. Planned properties stay separate from active ones.
final class TreeJSONKernelFixture extends EChartsTree
{
    public float $Now = 1793771784;
    public array $Planned = [];
    public int $Applies = 0;
    public bool $FailSave = false;

    public function PlanProperty(string $name, mixed $value): void
    {
        if ($this->FailSave) {
            throw new RuntimeException('private fixture persistence failure');
        }
        $this->Planned[$name] = $value;
    }

    public function CommitProperties(): void
    {
        foreach ($this->Planned as $name => $value) {
            parent::SetTestProperty($name, $value);
        }
        $this->Planned = [];
    }

    public function GetTestProperties(): array
    {
        return array_replace(parent::GetTestProperties(), $this->Planned);
    }

    public function ApplyChanges(): void
    {
        ++$this->Applies;
        parent::ApplyChanges();
    }

    public function Restart(): void
    {
        foreach (['TreeJSONSnapshot', 'TreeJSONSource', 'TreeJSONContext', 'TreeJSONAttempt'] as $name) {
            $this->SetBuffer($name, '');
        }
        $this->ApplyChanges();
    }

    public function TransportChannel(): string
    {
        return $this->ReadAttributeString('IPSViewTransportChannel');
    }

    protected function TreeJSONNow(): float
    {
        return $this->Now;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/EChartsTree/form.json'), true, 64, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAssetDirectory(): string
    {
        return dirname(__DIR__) . '/EChartsTree/visualization';
    }
}

function IPS_HasChanges(int $id): bool
{
    return ($GLOBALS['symconTestModuleObjects'][$id]->Planned ?? []) !== [];
}

function IPS_SemaphoreEnter(string $ident, int $timeout): bool
{
    if ($GLOBALS['treeJSONLocks'][$ident] ?? false) {
        return false;
    }
    $GLOBALS['treeJSONLocks'][$ident] = true;
    return true;
}

function IPS_SemaphoreLeave(string $ident): bool
{
    $GLOBALS['treeJSONLocks'][$ident] = false;
    return true;
}

$module = new TreeJSONKernelFixture();
$module->InstanceID = 58000;
$module->Create();
$module->CaptureDebug = true;
$GLOBALS['symconTestCopyTarget'] = $module;
$document = file_get_contents(__DIR__ . '/../docs/examples/tree-network-1.0.json');
$before = $module->GetTestProperties();
assertGatewayGauge($before['SourceMode'] === 'objects' && $before['JsonDocument'] === '' && $before['JsonVariableID'] === 0
    && $before['UseJsonObjectBindings'] === false, 'The object mode must remain the compatible default.');
$report = json_decode($module->ValidateTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($report['valid'] && $report['totalNodes'] === 6 && $report['maximumDepth'] === 3, 'Read-only public validation must accept the contract example.');
assertGatewayGauge($module->GetTestProperties() === $before && $module->TransportChannel() === '', 'Validation must not persist properties or transport channels.');
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['status'] === 'rejected' && $result['errors'][0]['code'] === 'SOURCE_MODE_MISMATCH', 'The setter must not switch a pre-existing source.');
$module->SetTestProperty('SourceMode', 'json');
$module->SetTestProperty('Title', 'Preserved design');
$before = $module->GetTestProperties();
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['status'] === 'applied' && $result['dataQuality'] === 'ready' && $result['displayConfirmed'] === false
    && $module->GetTestStatus() === IS_ACTIVE, 'The setter must confirm persisted data without falsely confirming an unseen client display.');
$after = $module->GetTestProperties();
unset($after['JsonDocument'], $before['JsonDocument']);
assertGatewayGauge($before === $after && !$module->Planned, 'Only JsonDocument may be changed and applied.');
$applies = $module->Applies;
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['status'] === 'unchanged' && $module->Applies === $applies, 'Canonical replay must not apply configuration again.');
$module->PlanProperty('Title', 'Unapplied title');
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['errors'][0]['code'] === 'CONFIGURATION_PENDING' && $module->Planned['Title'] === 'Unapplied title', 'The setter must not apply another planned property.');
$module->Planned = [];
$module->PlanProperty('SourceMode', 'variable');
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['errors'][0]['code'] === 'CONFIGURATION_PENDING', 'An unapplied source selection is not the active source selection.');
$module->Planned = [];
$GLOBALS['treeJSONLocks']['SymconECharts.TreeJSON.' . $module->InstanceID] = true;
$result = json_decode($module->SetTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['status'] === 'rejected' && $result['errors'][0]['code'] === 'IMPORT_BUSY', 'Competing setters must be explicitly refused without writes.');
$GLOBALS['treeJSONLocks']['SymconECharts.TreeJSON.' . $module->InstanceID] = false;
$bad = json_decode($document, true, 32, JSON_THROW_ON_ERROR);
$bad['nodes'][0]['name'] = 'NEW ROOT';
$module->FailSave = true;
try {
    $module->SetTreeJSON(json_encode($bad, JSON_THROW_ON_ERROR));
    throw new RuntimeException('An uncertain persistence failure was reported as success.');
} catch (RuntimeException $exception) {
    assertGatewayGauge(str_starts_with($exception->getMessage(), 'PERSISTENCE_FAILED:'), 'Persistence failures must be sanitized and explicitly distinguishable.');
} finally {
    $module->FailSave = false;
}

$draft = $module->GetTestProperties();
$formBefore = $module->GetTestProperties();
$snapshotBefore = $module->GetTestBuffer('TreeJSONSnapshot');
$message = $module->TreeJSONFromForm(base64_encode($document), '', json_encode($draft, JSON_THROW_ON_ERROR), true);
assertGatewayGauge(str_starts_with($message, 'MESSAGE:') && $module->GetTestProperties() === $formBefore
    && $module->GetTestBuffer('TreeJSONSnapshot') === $snapshotBefore, 'File takeover must only update the form draft and previews.');
$message = $module->TreeJSONFromForm('', '{bad json}', json_encode($draft, JSON_THROW_ON_ERROR), false);
assertGatewayGauge(str_contains($message, 'JSON_INVALID'), 'Invalid JSON must produce a visible callback message.');
$form = json_decode($module->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
function ECTR_TreeJSONFromForm(int $id, string $file, string $text, string $configuration, bool $takeOver): string
{
    assertGatewayGauge(func_num_args() === 5 && $id === 58000, 'The form callback must pass all four native method arguments plus InstanceID.');
    return $GLOBALS['symconTestModuleObjects'][$id]->TreeJSONFromForm($file, $text, $configuration, $takeOver);
}
function ECTR_UpdateTreeJSONSourceAvailability(int $id, string $mode, bool $root): void
{
    assertGatewayGauge(func_num_args() === 3 && $id === 58000, 'Source availability requires its complete native callback signature.');
    $GLOBALS['symconTestModuleObjects'][$id]->UpdateTreeJSONSourceAvailability($mode, $root);
}
function ECTR_UpdateBarPreviewFromForm(int $id, string $draft): void
{
    assertGatewayGauge(func_num_args() === 2 && $id === 58000, 'Draft previews require both native callback arguments.');
    $GLOBALS['symconTestModuleObjects'][$id]->UpdateBarPreviewFromForm($draft);
}
function ECTR_UpdateTreeRootAvailability(int $id, bool $root): void
{
    assertGatewayGauge(func_num_args() === 2 && $id === 58000, 'The existing root callback signature remains supported.');
    $GLOBALS['symconTestModuleObjects'][$id]->UpdateTreeRootAvailability($root);
}
$id = 58000;
extract($draft);
$TreeJSONUpload = base64_encode($document);
$actions = findGaugeFormElement($form['actions'], 'TreeJSONActions');
ob_start();
eval($actions['items'][0]['onChange']);
$output = ob_get_clean();
assertGatewayGauge(str_starts_with($output, 'MESSAGE:'), 'The actual file callback must echo its result.');
ob_start();
eval($actions['items'][1]['onClick']);
$output = ob_get_clean();
assertGatewayGauge(str_starts_with($output, 'MESSAGE:'), 'The actual text callback must echo its result.');
ob_start();
eval($actions['items'][2]['onClick']);
$output = ob_get_clean();
assertGatewayGauge(str_starts_with($output, 'MESSAGE:'), 'The actual takeover callback must echo its result.');
assertGatewayGauge($module->GetTestProperties() === $formBefore, 'Executing actual native callbacks must preserve the saved configuration.');
eval(findGaugeFormElement($form['elements'], 'SourceMode')['onChange']);
eval(findGaugeFormElement($form['elements'], 'UseSymconRoot')['onChange']);
assertGatewayGauge($module->GetTestProperties() === $formBefore, 'Native source-choice callbacks only change the form.');
$module->UpdateBarPreviewFromForm(json_encode(['SourceMode' => 'json', 'JsonDocument' => '{invalid'], JSON_THROW_ON_ERROR));
foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(simplexml_load_string($svg) !== false && str_contains($svg, 'JSON_INVALID') && !str_contains($svg, 'sample-root'), 'Invalid JSON preview drafts must display an error, never an unmarked example.');
}
foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
    assertGatewayGauge(strlen($html) > 500000 && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
        && str_contains($html, 'SYMC_ECHARTS_TREE') && str_contains($html, 'json:document:network'), 'Both real module outputs must include the shipped runtime and JSON tree under the original byte limit.');
}

$bound = json_decode($document, true, 32, JSON_THROW_ON_ERROR);
$bound['nodes'][] = ['id' => 'sensor-a', 'parentId' => 'network', 'name' => 'Same label', 'kind' => 'variable', 'objectId' => 9002];
$bound['nodes'][] = ['id' => 'sensor-b', 'parentId' => 'network', 'name' => 'Same label', 'kind' => 'variable', 'objectId' => 9002];
$module->SetTestProperty('UseJsonObjectBindings', true);
$GLOBALS['treeJSONObjectHook'] = static function (int $objectID) use ($module): void
{
    if ($objectID === 9002) {
        $module->PlanProperty('Title', 'Concurrent draft');
    }
};
try {
    $result = json_decode($module->SetTreeJSON(json_encode($bound)), true, 32, JSON_THROW_ON_ERROR);
    assertGatewayGauge($result['errors'][0]['code'] === 'CONFIGURATION_PENDING' && $module->Planned['Title'] === 'Concurrent draft', 'A pending edit arriving during validation must not be applied.');
} finally {
    unset($GLOBALS['treeJSONObjectHook']);
    $module->Planned = [];
}
$result = json_decode($module->SetTreeJSON(json_encode($bound)), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge($result['status'] === 'applied' && $module->GetTestReferences() === [9002], 'Repeated bound occurrences must register a single source reference.');
$boundTree = json_decode($module->GetTreeData(), true)['tree'];
assertGatewayGauge($boundTree['root']['children'][1]['displayValue'] === '21.3 °C'
    && $boundTree['root']['children'][2]['displayValue'] === '21.3 °C', 'Bound occurrences must read the existing formatted value.');
assertGatewayGauge($GLOBALS['symconTestTreeValues'][9002] === '21.3 °C', 'Bindings must not write values or presentation metadata.');
$expanded = ['format' => 'de.burki24.symconecharts.tree', 'formatVersion' => '1.0', 'rootId' => 'root', 'nodes' => [
    ['id' => 'root', 'parentId' => null, 'name' => 'Root', 'kind' => 'category']
]];
for ($index = 1; $index < 2600; $index++) {
    $expanded['nodes'][] = ['id' => 'sensor-' . $index, 'parentId' => 'root', 'name' => str_repeat('n', 120), 'kind' => 'variable', 'objectId' => 9002];
}
$GLOBALS['symconTestTreeValues'][9002] = str_repeat('🔋', 100);
try {
    $expandedDocument = json_encode($expanded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    assertGatewayGauge(strlen($expandedDocument) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The snapshot boundary fixture must fit the input budget.');
    $limit = json_decode($module->ValidateTreeJSON($expandedDocument), true, 32, JSON_THROW_ON_ERROR);
    assertGatewayGauge(!$limit['valid'] && $limit['errors'][0]['code'] === 'LIMIT_EXCEEDED', 'Resolved snapshot bytes must be bounded even outside the configured view.');
} finally {
    $GLOBALS['symconTestTreeValues'][9002] = '21.3 °C';
}
$module->SetTestProperty('UseJsonObjectBindings', false);
$savedTitle = $module->GetTestProperties()['Title'];
$module->SetTestProperty('Title', str_repeat('x', 1048576));
$limit = json_decode($module->ValidateTreeJSON($document), true, 32, JSON_THROW_ON_ERROR);
assertGatewayGauge(!$limit['valid'] && $limit['errors'][0]['code'] === 'LIMIT_EXCEEDED', 'Candidate output checks must enforce the original one-MiB budget before activation.');
$module->SetTestProperty('Title', $savedTitle);

// Applying a text draft preserves its bytes; whitespace must not hide an oversized configuration.
$module->SetTestProperty('JsonDocument', $document . str_repeat(' ', SYMCON_OUTPUT_BUFFER_LIMIT - strlen($document)));
$message = $module->TreeJSONFromForm('', $module->GetTestProperties()['JsonDocument'], json_encode($module->GetTestProperties()), false);
assertGatewayGauge(str_contains($message, 'LIMIT_EXCEEDED'), 'Text checking must not validate canonical bytes while leaving an oversized raw draft unchanged.');
$module->ApplyChanges();
try {
    $module->GetTreeData();
    throw new RuntimeException('An oversized stored configuration was accepted after canonicalization.');
} catch (RuntimeException $exception) {
    assertGatewayGauge(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:'), 'Stored text must satisfy the full configuration budget using its actual bytes.');
}
$module->SetTestProperty('JsonDocument', $document);

$GLOBALS['symconTestVariables'][58100] = ['VariableType' => 3, 'VariableUpdated' => 100, 'Value' => $document, 'Name' => 'Same name'];
$GLOBALS['symconTestTreeObjects'][58100] = ['ObjectType' => 2, 'ObjectName' => 'Same name'];
$GLOBALS['symconTestVariables'][58101] = ['VariableType' => 3, 'VariableUpdated' => 100, 'Value' => $document, 'Name' => 'Same name'];
$GLOBALS['symconTestTreeObjects'][58101] = ['ObjectType' => 2, 'ObjectName' => 'Same name'];
$module->SetTestProperty('SourceMode', 'variable');
$module->SetTestProperty('JsonVariableID', 58100);
$module->ApplyChanges();
$tree = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
assertGatewayGauge($tree['count'] === 6 && $tree['root']['id'] === 'json:variable-58100:network' && $tree['source']['quality'] === 'ready', 'String sources must retain their ID, not use names.');
assertGatewayGauge($module->GetTestTimerInterval('TreeRefresh') === 60000, 'The regular refresh interval stays unchanged.');
$rapid = json_decode($document, true, 32, JSON_THROW_ON_ERROR);
$rapid['nodes'][0]['name'] = 'Intermediate';
$GLOBALS['symconTestVariables'][58100]['Value'] = json_encode($rapid);
$module->MessageSink(0, 58100, VM_UPDATE, []);
$rapid['nodes'][0]['name'] = 'Latest';
$GLOBALS['symconTestVariables'][58100]['Value'] = json_encode($rapid);
$module->MessageSink(0, 58100, VM_UPDATE, []);
assertGatewayGauge(json_decode($module->GetTreeData(), true)['tree']['root']['name'] !== 'Intermediate', 'Rapid source updates must not repeatedly process intermediate values.');
$module->Now += 1.1;
$module->RefreshTreeJSON();
assertGatewayGauge(json_decode($module->GetTreeData(), true)['tree']['root']['name'] === 'Latest', 'The latest complete source value must be consumed after the processing interval.');
$GLOBALS['symconTestVariables'][58100]['Value'] = $document;
$module->Now += 1.1;
$module->RefreshTreeJSON();
$tree = json_decode($module->GetTreeData(), true)['tree'];
$accepted = $tree['source']['acceptedAt'];
$GLOBALS['symconTestVariables'][58100]['Value'] = '{invalid';
$module->MessageSink(0, 58100, VM_UPDATE, []);
assertGatewayGauge($module->GetTestTimerInterval('TreeJSONRefresh') >= 1, 'VM_UPDATE must schedule bounded processing.');
$module->Now += 1.1;
$module->RefreshTreeJSON();
$tree = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
assertGatewayGauge($tree['count'] === 6 && $tree['source']['quality'] === 'stale' && $tree['source']['acceptedAt'] === $accepted
    && $module->GetTestStatus() === 204, 'Invalid updates must retain a visibly stale snapshot with its original timestamp.');
$module->Restart();
$state = json_decode($module->GetTreeDiagnostic(), true, 64, JSON_THROW_ON_ERROR);
assertGatewayGauge($state['source']['quality'] === 'error', 'A restart must not fabricate a persistent last-good snapshot.');
$GLOBALS['symconTestVariables'][58100]['Value'] = $document;
$module->Now += 1.1;
$module->RefreshTreeJSON();
$tree = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
assertGatewayGauge($tree['source']['quality'] === 'ready' && $module->GetTestStatus() === IS_ACTIVE, 'A valid subsequent update must recover automatically.');
$GLOBALS['symconTestVariables'][58100]['Value'] = str_repeat(' ', 1048577);
$module->Now += 1.1;
$module->RefreshTreeJSON();
$oversize = json_decode($module->GetTreeData(), true)['tree'];
assertGatewayGauge($oversize['source']['quality'] === 'stale' && $oversize['source']['diagnostic'] === 'LIMIT_EXCEEDED', 'An oversized source update must preserve the last valid snapshot with its true error class.');
$GLOBALS['symconTestVariables'][58100]['Value'] = $document;
$GLOBALS['symconTestVariables'][58100]['VariableType'] = 1;
$module->Now += 1.1;
$module->RefreshTreeJSON();
assertGatewayGauge(json_decode($module->GetTreeData(), true)['tree']['source']['quality'] === 'stale', 'A type-changed string source must remain visibly unavailable.');
$GLOBALS['symconTestVariables'][58100]['VariableType'] = 3;
$module->Now += 1.1;
$module->RefreshTreeJSON();
assertGatewayGauge(json_decode($module->GetTreeData(), true)['tree']['source']['quality'] === 'ready', 'Restoring a string source must recover.');
$module->SetTestProperty('JsonVariableID', 58101);
$GLOBALS['symconTestVariables'][58101]['Value'] = '';
$module->ApplyChanges();
assertGatewayGauge(json_decode($module->GetTreeDiagnostic(), true)['source']['quality'] === 'error', 'A changed source must not inherit the previous source snapshot.');
assertGatewayGauge($GLOBALS['symconTestVariables'][58100]['Value'] === $document && $GLOBALS['symconTestVariables'][58101]['Value'] === '', 'The module must never write its JSON source.');
$module->SetTestProperty('UseJsonObjectBindings', true);
$many = json_decode($document, true);
for ($i = 0; $i <= 100; ++$i) {
    $GLOBALS['symconTestTreeObjects'][59000 + $i] = ['ObjectType' => 1, 'ObjectName' => 'Synthetic local object ' . $i];
    $many['nodes'][] = ['id' => 'bound-' . $i, 'parentId' => 'network', 'name' => 'Bound node', 'kind' => 'instance', 'objectId' => 59000 + $i];
}
$manyReport = json_decode($module->ValidateTreeJSON(json_encode($many)), true);
assertGatewayGauge($manyReport['valid'] && $manyReport['bindingCount'] === 101 && count($manyReport['bindings']) === 100
    && $manyReport['bindingsTruncated'] && $manyReport['warnings'][0]['code'] === 'BINDING_REPORT_TRUNCATED', 'The binding-name report is bounded without reducing the source model.');
unset($GLOBALS['symconTestTreeObjects'][59100]);
$manyReport = json_decode($module->ValidateTreeJSON(json_encode($many)), true);
assertGatewayGauge(!$manyReport['valid'] && $manyReport['errors'][0]['code'] === 'OBJECT_BINDING_INVALID', 'Bindings behind the report/preview limit must still be validated.');
assertGatewayGauge(!str_contains(json_encode($module->DebugMessages), 'NEW ROOT') && !str_contains(json_encode($module->DebugMessages), 'Switch Büro')
    && !str_contains(json_encode($module->DebugMessages), 'private fixture'), 'Debug output must not expose imported content or underlying persistence exceptions.');

echo "Tree JSON real module API, planned/active configuration, pure form actions, string updates, stale states and restart verified.\n";
