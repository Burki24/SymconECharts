<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/EChartsTreeJSON.php';

/** Tree-local source lifecycle. Sources and object bindings are strictly read only. */
trait EChartsTreeJSONSource
{
    public function ValidateTreeJSON(string $JSON): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($JSON): string
        {
            try {
                $model = $this->ReadTreeJSONDocument($JSON, []);
                $projection = $this->ProjectTreeJSON($model);
                $this->CheckTreeJSONOutputs($projection);
                $this->CheckTreeJSONConfiguration($model['document']);
                return EChartsImportJson::Canonical($this->TreeJSONReport($model, $projection));
            } catch (Throwable $exception) {
                return EChartsImportJson::Canonical($this->TreeJSONRejected($exception));
            }
        }, false);
    }

    public function SetTreeJSON(string $JSON): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($JSON): string
        {
            $lock = 'SymconECharts.TreeJSON.' . $this->InstanceID;
            if (!IPS_SemaphoreEnter($lock, 1000)) {
                return EChartsImportJson::Canonical(['status' => 'rejected'] + $this->TreeJSONRejected(new InvalidArgumentException('IMPORT_BUSY:')));
            }
            try {
                try {
                    if ($this->ReadPropertyString('SourceMode') !== 'json') {
                        throw new InvalidArgumentException('SOURCE_MODE_MISMATCH:');
                    }
                    if (IPS_HasChanges($this->InstanceID)) {
                        throw new InvalidArgumentException('CONFIGURATION_PENDING:');
                    }
                    $expectedConfiguration = $this->TreeJSONActiveConfiguration();
                    $model = $this->ReadTreeJSONDocument($JSON, []);
                    $projection = $this->ProjectTreeJSON($model);
                    $this->CheckTreeJSONOutputs($projection);
                    $this->CheckTreeJSONConfiguration($model['document']);
                    try {
                        $same = $this->ReadTreeJSONDocument($this->ReadPropertyString('JsonDocument'), []);
                    } catch (Throwable) {
                        $same = null;
                    }
                    // Check after every bounded source read, including comparison with the active document.
                    if (IPS_HasChanges($this->InstanceID) || $this->TreeJSONActiveConfiguration() !== $expectedConfiguration) {
                        throw new InvalidArgumentException('CONFIGURATION_PENDING:');
                    }
                    if ($this->ReadPropertyString('SourceMode') !== 'json') {
                        throw new InvalidArgumentException('SOURCE_MODE_MISMATCH:');
                    }
                } catch (Throwable $exception) {
                    return EChartsImportJson::Canonical(['status' => 'rejected'] + $this->TreeJSONRejected($exception));
                }
                $hash = $model['documentHash'];
                if (($same['documentHash'] ?? null) === $hash) {
                    return EChartsImportJson::Canonical(['status' => 'unchanged', 'documentHash' => $hash]);
                }
                $configuration = $expectedConfiguration;
                $configuration['JsonDocument'] = $model['document'];
                try {
                    if (!IPS_SetProperty($this->InstanceID, 'JsonDocument', $model['document'])) {
                        throw new RuntimeException('PERSISTENCE_FAILED');
                    }
                    // Kernel getters expose active settings, not the staged document.
                    if (!IPS_HasChanges($this->InstanceID) || $this->TreeJSONActiveConfiguration() !== $expectedConfiguration) {
                        throw new RuntimeException('PERSISTENCE_FAILED');
                    }
                    if (!IPS_ApplyChanges($this->InstanceID) || IPS_HasChanges($this->InstanceID)
                        || $this->TreeJSONActiveConfiguration() !== $configuration) {
                        throw new RuntimeException('PERSISTENCE_FAILED');
                    }
                } catch (Throwable) {
                    // Persistence may already have occurred. Do not claim rejection without writes or restore stale properties.
                    throw new RuntimeException('PERSISTENCE_FAILED: inspect the active and planned Tree configuration before retrying.');
                }
                return EChartsImportJson::Canonical(['status' => 'applied', 'documentHash' => $hash,
                    'dataQuality'                             => $this->TreeJSONSourceDiagnostic()['quality'], 'displayConfirmed' => false]);
            } finally {
                IPS_SemaphoreLeave($lock);
            }
        }, false);
    }

    /** Explicit form callback: file content is transient, previews and draft takeover never save properties. */
    public function TreeJSONFromForm(string $FileData, string $Document, string $Configuration, bool $TakeOver): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($FileData, $Document, $Configuration, $TakeOver): string
        {
            try {
                if (strlen($Configuration) > 2097152 || strlen($FileData) > EChartsTreeJSON::MAXIMUM_UPLOAD_BYTES) {
                    throw new InvalidArgumentException('LIMIT_EXCEEDED:');
                }
                $draft = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($draft) || ($draft['SourceMode'] ?? '') !== 'json') {
                    throw new InvalidArgumentException('SOURCE_MODE_MISMATCH:');
                }
                if ($TakeOver && $FileData === '') {
                    throw new InvalidArgumentException('SOURCE_UNAVAILABLE:');
                }
                try {
                    $text = $FileData === '' ? $Document : EChartsImportJson::Upload($FileData);
                } catch (Throwable) {
                    throw new InvalidArgumentException('JSON_INVALID:');
                }
                $model = $this->ReadTreeJSONDocument($text, $draft);
                $draft['JsonDocument'] = $FileData === '' ? $text : $model['document'];
                $projection = $this->ProjectTreeJSON($model, $draft);
                $this->CheckTreeJSONOutputs($projection, $draft);
                $this->CheckTreeJSONConfiguration($draft['JsonDocument'], $draft);
                $report = $this->TreeJSONReport($model, $projection);
                $summary = sprintf($this->Translate('Valid Tree JSON: %d nodes, depth %d, %d loaded nodes.'), $report['totalNodes'], $report['maximumDepth'], $report['visibleNodes']);
                foreach ($report['bindings'] as $binding) {
                    $summary .= ' ' . $binding['objectId'] . ': ' . $binding['name'];
                }
                foreach ($report['warnings'] as $warning) {
                    $summary .= ' ' . $warning['message'];
                }
                $this->UpdateFormField('TreeJSONReport', 'caption', $summary);
                $this->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
                if ($TakeOver) {
                    $this->UpdateFormField('JsonDocument', 'value', $model['document']);
                    return 'MESSAGE:' . $this->Translate('Tree JSON added to the draft. Apply the form to save.');
                }
                return 'MESSAGE:' . $summary;
            } catch (Throwable $exception) {
                $problem = $this->TreeJSONProblem($exception);
                $message = $problem['code'] . ' ' . $problem['pointer'] . ': ' . $problem['message'];
                $this->UpdateFormField('TreeJSONReport', 'caption', $message);
                return $message;
            }
        }, false);
    }

    public function UpdateTreeJSONSourceAvailability(string $SourceMode, bool $UseSymconRoot): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($SourceMode, $UseSymconRoot): void
        {
            foreach (['UseSymconRoot', 'RootObjectID', 'IncludeHidden', 'VariableNameFilter'] as $name) {
                $this->UpdateFormField($name, 'enabled', $SourceMode === 'objects' && ($name !== 'RootObjectID' || !$UseSymconRoot));
            }
            foreach (['JsonDocument', 'TreeJSONActions'] as $name) {
                $this->UpdateFormField($name, 'visible', $SourceMode === 'json');
            }
            $this->UpdateFormField('JsonVariableID', 'visible', $SourceMode === 'variable');
            $this->UpdateFormField('UseJsonObjectBindings', 'visible', $SourceMode !== 'objects');
            foreach (['TreeObjectHint', 'TreeVariableFilterHint'] as $name) {
                $this->UpdateFormField($name, 'visible', $SourceMode === 'objects');
            }
        }, true);
    }

    public function RefreshTreeJSON(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            $this->SetTimerInterval('TreeJSONRefresh', 0);
            if ($this->ReadPropertyString('SourceMode') === 'objects') {
                return;
            }
            $remaining = 1000 - (int) (($this->TreeJSONNow() - (float) $this->GetBuffer('TreeJSONAttempt')) * 1000);
            if ($remaining > 0) {
                $this->SetTimerInterval('TreeJSONRefresh', $remaining);
                return;
            }
            $this->PublishVisualizationState();
        }, true);
    }

    protected function TreeJSONNow(): float
    {
        return microtime(true);
    }
    private function RegisterTreeJSONSource(): void
    {
        $this->RegisterPropertyString('SourceMode', 'objects');
        $this->RegisterPropertyString('JsonDocument', '');
        $this->RegisterPropertyInteger('JsonVariableID', 0);
        $this->RegisterPropertyBoolean('UseJsonObjectBindings', false);
        $this->RegisterAttributeString('TreeJSONReferences', '[]');
        $this->RegisterTimer('TreeJSONRefresh', 0, 'ECTR_RefreshTreeJSON($_IPS["TARGET"]);');
    }

    private function WithTreeJSONForm(array $form, array $fields): array
    {
        $mode = $this->ReadPropertyString('SourceMode');
        $availability = 'ECTR_UpdateTreeJSONSourceAvailability($id, $SourceMode, $UseSymconRoot);';
        $controls = [
            ['type' => 'Select', 'name' => 'SourceMode', 'caption' => 'Tree data source', 'onChange' => $availability, 'options' => [
                ['caption' => 'Symcon object tree', 'value' => 'objects'], ['caption' => 'Stored JSON (file, text or script)', 'value' => 'json'],
                ['caption' => 'JSON string variable', 'value' => 'variable']]],
            ['type' => 'ValidationTextBox', 'name' => 'JsonDocument', 'caption' => 'Tree JSON document', 'multiline' => true, 'visible' => $mode === 'json'],
            ['type' => 'SelectVariable', 'name' => 'JsonVariableID', 'caption' => 'JSON string variable', 'validVariableTypes' => [3], 'visible' => $mode === 'variable'],
            ['type' => 'CheckBox', 'name' => 'UseJsonObjectBindings', 'caption' => 'Allow local object bindings in JSON', 'visible' => $mode !== 'objects']
        ];
        $inserted = false;
        $elements = [];
        foreach ($form['elements'] as $element) {
            $name = $element['name'] ?? '';
            if (!$inserted && $name === 'UseSymconRoot') {
                array_push($elements, ...$controls);
                $inserted = true;
            }
            if (in_array($name, ['UseSymconRoot', 'RootObjectID', 'IncludeHidden', 'VariableNameFilter'], true)) {
                $element['enabled'] = $mode === 'objects' && ($name !== 'RootObjectID' || !$this->ReadPropertyBoolean('UseSymconRoot'));
                if ($name === 'UseSymconRoot') {
                    $element['onChange'] = trim(($element['onChange'] ?? '') . ' ' . $availability);
                }
            }
            if (($element['type'] ?? '') === 'Label' && (str_starts_with($element['caption'] ?? '', 'Categories, instances and variables')
                || str_starts_with($element['caption'] ?? '', 'Only matching variable names'))) {
                $element['name'] = str_starts_with($element['caption'], 'Categories') ? 'TreeObjectHint' : 'TreeVariableFilterHint';
                $element['visible'] = $mode === 'objects';
            }
            $elements[] = $element;
        }
        $form['elements'] = $elements;
        $payload = 'json_encode(compact(' . implode(', ', array_map(static fn (string $name): string => var_export($name, true), $fields)) . '))';
        $form['actions'][] = ['type' => 'ExpansionPanel', 'name' => 'TreeJSONActions', 'caption' => 'Check or import Tree JSON', 'expanded' => true, 'visible' => $mode === 'json', 'items' => [
            ['type'        => 'SelectFile', 'name' => 'TreeJSONUpload', 'caption' => 'Select Tree JSON file', 'extensions' => '.json',
                'onChange' => 'echo ECTR_TreeJSONFromForm($id, $TreeJSONUpload, $JsonDocument, ' . $payload . ', false);'],
            ['type'       => 'Button', 'caption' => 'Check JSON text',
                'onClick' => 'echo ECTR_TreeJSONFromForm($id, "", $JsonDocument, ' . $payload . ', false);'],
            ['type'       => 'Button', 'caption' => 'Copy checked file to draft',
                'onClick' => 'echo ECTR_TreeJSONFromForm($id, $TreeJSONUpload, $JsonDocument, ' . $payload . ', true);'],
            ['type' => 'Label', 'name' => 'TreeJSONReport', 'caption' => 'File selection and checking do not save configuration. Apply the form to save the JSON draft.']
        ]];
        return $form;
    }

    private function ReadTreeJSONDocument(string $json, array $draft): array
    {
        $model = EChartsTreeJSON::Read(
            $json,
            (bool) ($draft['UseJsonObjectBindings'] ?? $this->ReadPropertyBoolean('UseJsonObjectBindings')),
            static function (int $id): array
            {
                if (!IPS_ObjectExists($id)) {
                    throw new InvalidArgumentException('OBJECT_BINDING_INVALID:');
                }
                return IPS_GetObject($id);
            },
            static fn (int $id): string => (string) GetValueFormatted($id),
            $this->TreeJSONForbidden($draft)
        );
        // Bindings can expand a small document into a large resolved snapshot,
        // even when the configured view only includes a few nodes.
        $snapshot = $model;
        unset($snapshot['document'], $snapshot['bindings']);
        if (strlen(json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))
            > EChartsTreeJSON::MAXIMUM_BYTES - EChartsTreeJSON::TRANSPORT_RESERVE_BYTES) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED:');
        }
        return $model;
    }

    private function TreeJSONForbidden(array $draft): array
    {
        $ids = ($draft['SourceMode'] ?? $this->ReadPropertyString('SourceMode')) === 'variable'
            ? [(int) ($draft['JsonVariableID'] ?? $this->ReadPropertyInteger('JsonVariableID'))] : [];
        $outputID = $this->IPSViewHTMLVariableID(self::IPSVIEW_OUTPUT_IDENT);
        if ($outputID > 0) {
            $ids[] = $outputID;
        }
        return $ids;
    }

    private function ProjectTreeJSON(array $model, array $draft = []): array
    {
        $mode = $draft['SourceMode'] ?? $this->ReadPropertyString('SourceMode');
        $namespace = $mode === 'variable' ? 'variable-' . (int) ($draft['JsonVariableID'] ?? $this->ReadPropertyInteger('JsonVariableID')) : 'document';
        return EChartsTreeJSON::Project(
            $model,
            (int) ($draft['MaxDepth'] ?? $this->ReadPropertyInteger('MaxDepth')),
            (int) ($draft['MaxNodes'] ?? $this->ReadPropertyInteger('MaxNodes')),
            $namespace
        );
    }

    private function TreeJSONReport(array $model, array $projection): array
    {
        $limited = count($model['bindings']) > 100;
        return ['valid'  => true, 'documentHash' => $model['documentHash'], 'rootId' => $model['rootId'],
            'totalNodes' => $model['totalNodes'], 'maximumDepth' => $model['maximumDepth'], 'visibleNodes' => $projection['count'],
            'truncated'  => $projection['truncated'], 'bindingCount' => count($model['bindings']), 'bindingsTruncated' => $limited,
            'bindings'   => array_slice($model['bindings'], 0, 100), 'errors' => [],
            'warnings'   => $limited ? [['code' => 'BINDING_REPORT_TRUNCATED', 'pointer' => '/nodes',
                'message'                       => $this->Translate('All bindings were checked. Only the first 100 local object names are included in this report.')]] : [], 'additionalMessages' => false];
    }

    private function TreeJSONProblem(Throwable $exception): array
    {
        $problem = EChartsTreeJSON::Problem($exception);
        $problem['message'] = $this->Translate($problem['message']);
        return $problem;
    }

    private function TreeJSONRejected(Throwable $exception): array
    {
        return ['valid' => false, 'errors' => [$this->TreeJSONProblem($exception)], 'warnings' => [], 'additionalMessages' => false];
    }

    private function TreeJSONActiveConfiguration(): array
    {
        return json_decode(IPS_GetConfiguration($this->InstanceID), true, 64, JSON_THROW_ON_ERROR);
    }

    private function CheckTreeJSONConfiguration(string $document, array $draft = []): void
    {
        if (($draft['SourceMode'] ?? $this->ReadPropertyString('SourceMode')) !== 'variable') {
            $configuration = array_replace($this->TreeJSONActiveConfiguration(), array_intersect_key($draft, $this->TreeJSONActiveConfiguration()));
            $configuration['JsonDocument'] = $document;
            if (strlen(json_encode($configuration, JSON_THROW_ON_ERROR)) > EChartsTreeJSON::MAXIMUM_BYTES) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED:');
            }
        }
    }

    /** Candidate checks are read only; neither renderer nor draft validation can commit a snapshot. */
    private function CheckTreeJSONOutputs(array $hierarchy, array $draft = []): void
    {
        $hierarchy['source'] ??= ['mode' => $draft['SourceMode'] ?? $this->ReadPropertyString('SourceMode'), 'quality' => 'ready', 'acceptedAt' => (int) $this->TreeJSONNow(), 'diagnostic' => null];
        // Reserve the metadata needed to deliver a stale snapshot without crossing the same output budget.
        $hierarchy['source']['quality'] = 'stale';
        $hierarchy['source']['diagnostic'] = 'OBJECT_BINDING_INVALID';
        foreach ([false, true] as $ipsView) {
            $chart = $this->TreeChartData($hierarchy, $ipsView);
            if ($draft !== []) {
                $chart['tree']['title'] = (string) ($draft['Title'] ?? $chart['tree']['title']);
            }
            $state = ['schemaVersion' => 1, 'family' => 'tree', 'status' => 'ready', 'chart' => $chart, 'error' => null, 'sourceContext' => str_repeat('0', 64)];
            if (strlen(json_encode($chart, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) > EChartsTreeJSON::MAXIMUM_BYTES - EChartsTreeJSON::TRANSPORT_RESERVE_BYTES
                || strlen(json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) > EChartsTreeJSON::MAXIMUM_BYTES
                || strlen($this->RenderTreeHTMLPage($ipsView, $state, true)) > EChartsTreeJSON::MAXIMUM_BYTES) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED:');
            }
        }
    }

    private function TreeJSONSourceDiagnostic(): array
    {
        $diagnostic = $this->GetBuffer('TreeJSONContext') === $this->TreeJSONContext()
            ? json_decode($this->GetBuffer('TreeJSONSource'), true) : null;
        return is_array($diagnostic) ? $diagnostic : ['mode' => $this->ReadPropertyString('SourceMode'), 'quality' => 'error', 'acceptedAt' => null, 'diagnostic' => 'SOURCE_UNAVAILABLE'];
    }

    private function TreeJSONContext(): string
    {
        $mode = $this->ReadPropertyString('SourceMode');
        return EChartsImportJson::Hash([$mode, $mode === 'variable' ? $this->ReadPropertyInteger('JsonVariableID') : hash('sha256', $this->ReadPropertyString('JsonDocument')),
            $this->ReadPropertyBoolean('UseJsonObjectBindings')]);
    }

    private function InitializeTreeJSONSource(): void
    {
        $this->SetTimerInterval('TreeJSONRefresh', 0);
        $old = json_decode($this->ReadAttributeString('TreeJSONReferences'), true);
        foreach (is_array($old) ? $old : [] as $id) {
            $this->UnregisterReference($id);
            $this->UnregisterMessage($id, VM_UPDATE);
        }
        $this->WriteAttributeString('TreeJSONReferences', '[]');
        $this->SetBuffer('TreeJSONAttempt', '0');
        if ($this->ReadPropertyString('SourceMode') === 'objects' || $this->GetBuffer('TreeJSONContext') !== $this->TreeJSONContext()) {
            foreach (['TreeJSONSnapshot', 'TreeJSONSource', 'TreeJSONContext'] as $name) {
                $this->SetBuffer($name, '');
            }
        }
        $this->SyncTreeJSONReferences([]);
    }

    private function SyncTreeJSONReferences(array $ids): void
    {
        $mode = $this->ReadPropertyString('SourceMode');
        if ($mode === 'variable') {
            $ids[] = $this->ReadPropertyInteger('JsonVariableID');
        } elseif ($mode === 'objects') {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        $old = json_decode($this->ReadAttributeString('TreeJSONReferences'), true);
        foreach (array_diff(is_array($old) ? $old : [], $ids) as $id) {
            $this->UnregisterReference($id);
            $this->UnregisterMessage($id, VM_UPDATE);
        }
        foreach ($ids as $id) {
            if (IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
            }
            if (IPS_VariableExists($id)) {
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }
        $this->WriteAttributeString('TreeJSONReferences', json_encode($ids, JSON_THROW_ON_ERROR));
    }

    private function ReadTreeJSONHierarchy(): array
    {
        $lock = 'SymconECharts.TreeJSON.Read.' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($lock, 1000)) {
            throw new RuntimeException('IMPORT_BUSY:');
        }
        try {
            return $this->ReadTreeJSONHierarchyLocked();
        } finally {
            IPS_SemaphoreLeave($lock);
        }
    }

    private function ReadTreeJSONHierarchyLocked(): array
    {
        $mode = $this->ReadPropertyString('SourceMode');
        $context = $this->TreeJSONContext();
        $cached = json_decode($this->GetBuffer('TreeJSONSnapshot'), true, 64);
        if ($this->GetBuffer('TreeJSONContext') !== $context) {
            $cached = null;
            $this->SetBuffer('TreeJSONSnapshot', '');
            $this->SetBuffer('TreeJSONSource', '');
            $this->SetBuffer('TreeJSONAttempt', '0');
            $this->SetBuffer('TreeJSONContext', $context);
        }
        $now = $this->TreeJSONNow();
        if ($now - (float) $this->GetBuffer('TreeJSONAttempt') >= 1) {
            $this->SetBuffer('TreeJSONAttempt', (string) $now);
            try {
                if ($mode === 'variable') {
                    $id = $this->ReadPropertyInteger('JsonVariableID');
                    if (!IPS_VariableExists($id) || IPS_GetVariable($id)['VariableType'] !== 3) {
                        throw new InvalidArgumentException('SOURCE_UNAVAILABLE:');
                    }
                    $text = GetValue($id);
                    if (!is_string($text)) {
                        throw new InvalidArgumentException('SOURCE_UNAVAILABLE:');
                    }
                } else {
                    $text = $this->ReadPropertyString('JsonDocument');
                }
                $model = $this->ReadTreeJSONDocument($text, []);
                $projection = $this->ProjectTreeJSON($model);
                $source = ['mode' => $mode, 'quality' => 'ready', 'acceptedAt' => (int) $now, 'diagnostic' => null];
                $projection['source'] = $source;
                $this->CheckTreeJSONOutputs($projection);
                $this->CheckTreeJSONConfiguration($text);
                $contentHash = EChartsImportJson::Hash([$model['documentHash'], $model['root']]);
                $accepted = ($cached['hash'] ?? null) === $contentHash ? $cached['acceptedAt'] : (int) $now;
                unset($model['document'], $model['bindings']);
                $cached = ['context' => $context, 'model' => $model, 'hash' => $contentHash, 'acceptedAt' => $accepted];
                $this->SetBuffer('TreeJSONSnapshot', json_encode($cached, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->SetBuffer('TreeJSONSource', json_encode(['mode' => $mode, 'quality' => 'ready', 'acceptedAt' => $accepted, 'diagnostic' => null], JSON_THROW_ON_ERROR));
                $this->SyncTreeJSONReferences($model['objectIDs']);
            } catch (Throwable $exception) {
                $problem = $this->TreeJSONProblem($exception);
                $this->SetBuffer('TreeJSONSource', json_encode(['mode' => $mode, 'quality' => $cached === null ? 'error' : 'stale',
                    'acceptedAt'                                       => $cached['acceptedAt'] ?? null, 'diagnostic' => $problem['code']], JSON_THROW_ON_ERROR));
                $this->WriteAttributeString('LastError', $problem['code'] . ':' . $problem['pointer']);
                $this->DebugEChartsSetStatus(self::STATUS_READ_FAILED);
            }
        }
        $source = $this->TreeJSONSourceDiagnostic();
        if ($cached === null) {
            throw new RuntimeException(($source['diagnostic'] ?? 'SOURCE_UNAVAILABLE') . ':');
        }
        $this->SyncTreeJSONReferences($cached['model']['objectIDs']);
        $projection = $this->ProjectTreeJSON($cached['model']);
        $projection['source'] = $source;
        if ($source['quality'] === 'ready') {
            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);
        }
        return $projection;
    }
}
