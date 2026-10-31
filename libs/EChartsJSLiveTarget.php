<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/EChartsJSLiveRendering.php';
require_once __DIR__ . '/EChartsJSLiveTargetSnapshot.php';
require_once __DIR__ . '/EChartsJSLiveLocale.php';
require_once __DIR__ . '/EChartsJSLivePreview.php';

/** Opt-in import lifecycle. The empty default leaves every existing configuration/output path unchanged. */
trait EChartsJSLiveTarget
{
    use EChartsJSLiveTranslations;
    private const JSLIVE_MODULES = [
        'EChartsGaugeSingle'  => ['ECGS', 'gauge'], 'EChartsPie' => ['ECPI', 'pie'],
        'EChartsTimeSeries'   => ['ECTS', 'cartesian-history'], 'EChartsBarHistory' => ['ECBH', 'cartesian-history'],
        'EChartsRadarHistory' => ['ECRH', 'radar-history'], 'EChartsProgress' => ['ECPG', 'progress']
    ];

    public function GetJSLiveCreationGuard(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->ReadAttributeString('JSLiveCreationGuard'), false);
    }

    public function GetJSLiveOutputGuards(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->ReadAttributeString('JSLiveOutputGuards'), false);
    }

    public function GetJSLiveImportHTML(bool $IPSView): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->RenderJSLiveImportPage($IPSView), false);
    }

    public function PreviewJSLiveImportDesign(string $Draft, bool $IPSView): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Draft, $IPSView): string
        {
            [$model, $configuration] = $this->JSLiveDesignDraft($Draft);
            $prefix = $IPSView && !$configuration['IPSViewUseTileDesign'] ? 'IPSView' : '';
            $state = EChartsJSLiveRendering::State($model, function (array $payload): array
            {
                $operation = EChartsJSLiveArchive::OPERATION;
                $response = EChartsDataProtocol::DecodeResponse($this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, $payload)
                )), $operation);
                if (!$response['Success']) {
                    throw new RuntimeException('SNAPSHOT_UNAVAILABLE: source/archive snapshot could not be loaded.');
                }
                return $response['Payload'];
            }, time(), $this->JSLiveDraftDesign($configuration, $prefix));
            return 'data:text/html;base64,' . base64_encode(EChartsJSLivePage::Render(
                $state,
                $IPSView,
                $this->ResponsiveVisualizationLayoutOptions(!$IPSView),
                null,
                $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage())
            ));
        });
    }

    public function UpdateJSLiveImportColorKey(string $Draft): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Draft): void
        {
            [$model, $configuration] = $this->JSLiveDesignDraft($Draft);
            foreach (['', 'IPSView'] as $prefix) {
                $effective = $prefix === 'IPSView' && $configuration['IPSViewUseTileDesign'] ? '' : $prefix;
                $this->UpdateFormField($prefix . 'JSLiveColorKey', 'image', EChartsJSLivePreview::ColorKey(
                    $model,
                    $this->JSLiveDraftDesign($configuration, $effective),
                    $this->JSLiveText('Schematic color key: Chart background')
                ));
            }
            foreach (['BackgroundColor', 'FontSizePercent', 'ShowTitle', 'ShowLegend', 'ShowTooltip', 'AnimationEnabled'] as $key) {
                $this->UpdateFormField('IPSViewJSLive' . $key, 'enabled', !$configuration['IPSViewUseTileDesign']);
            }
        }, true);
    }

    public function RefreshJSLiveImport(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            if (!$this->HasJSLiveImport()) {
                $this->SetTimerInterval('JSLiveImportRefresh', 0);
                return;
            }
            $state = $this->BuildJSLiveImportState();
            $this->WriteAttributeString('LastError', $state['status'] === 'ready' ? '' : 'IMPORTED_SOURCE_OR_ARCHIVE_UNAVAILABLE');
            $this->DebugEChartsSetStatus($state['status'] === 'ready' ? IS_ACTIVE : 298);
            $this->UpdateVisualizationValue(json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
            $this->PushEChartsIPSViewState(fn (): array => $this->BuildJSLiveImportState(true));
        }, true);
    }

    public function GetJSLiveImportData(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => EChartsImportJson::Canonical($this->BuildJSLiveImportState()), false);
    }

    protected function RegisterJSLiveTarget(): void
    {
        $this->RegisterPropertyString('JSLiveImportModel', '');
        $this->RegisterPropertyInteger('JSLivePeriod', 5);
        $this->RegisterPropertyBoolean('JSLiveRelative', true);
        $this->RegisterPropertyBoolean('JSLiveNow', true);
        $this->RegisterPropertyInteger('JSLiveStartDate', 0);
        foreach (['', 'IPSView'] as $prefix) {
            $this->RegisterPropertyInteger($prefix . 'JSLiveBackgroundColor', -1);
            $this->RegisterPropertyInteger($prefix . 'JSLiveFontSizePercent', 100);
            foreach (['ShowTitle', 'ShowLegend', 'ShowTooltip', 'AnimationEnabled'] as $key) {
                $this->RegisterPropertyBoolean($prefix . 'JSLive' . $key, true);
            }
        }
        $this->RegisterAttributeString('JSLiveSourceReferences', '[]');
        $this->RegisterAttributeString('JSLiveCreationGuard', '');
        $this->RegisterAttributeString('JSLiveOutputGuards', '{}');
        $prefix = $this->JSLiveModule()[0];
        $this->RegisterTimer('JSLiveImportRefresh', 0, $prefix . '_RefreshJSLiveImport($_IPS["TARGET"]);');
    }

    protected function HasJSLiveImport(): bool
    {
        return $this->ReadPropertyString('JSLiveImportModel') !== '';
    }

    protected function SealJSLiveCreationDefaults(): void
    {
        if ($this->ReadAttributeString('JSLiveCreationGuard') === '') {
            $this->WriteAttributeString('JSLiveCreationGuard', EChartsJSLiveTargetSnapshot::CreationHash(EChartsJSLiveTargetSnapshot::Read($this->InstanceID)));
        }
    }

    protected function JSLiveModel(): array
    {
        $model = EChartsJSLiveModel::Read($this->ReadPropertyString('JSLiveImportModel'), $this->JSLiveModule()[1]);
        if ($model['time'] !== null) {
            $model['time']['selection']['Period']['value'] = $this->ReadPropertyInteger('JSLivePeriod');
            $model['time']['selection']['Relativ']['value'] = $this->ReadPropertyBoolean('JSLiveRelative');
            if ($model['family'] === 'cartesian-history') {
                $model['time']['selection']['Now']['value'] = $this->ReadPropertyBoolean('JSLiveNow');
                $model['time']['selection']['StartDate']['value'] = $this->ReadPropertyInteger('JSLiveStartDate');
            }
        }
        return $model;
    }

    protected function InitializeJSLiveImport(): void
    {
        $this->SetTimerInterval('JSLiveImportRefresh', 0);
        try {
            $this->SealJSLiveOutputs();
            $model = $this->JSLiveModel();
            $ids = array_values(array_unique(array_column($model['sources'], 'variableID')));
            $previous = json_decode($this->ReadAttributeString('JSLiveSourceReferences'), true, 16, JSON_THROW_ON_ERROR);
            foreach (array_diff($previous, $ids) as $id) {
                $this->UnregisterReference($id);
                $this->UnregisterMessage($id, VM_UPDATE);
            }
            foreach ($model['sources'] as $source) {
                $id = $source['variableID'];
                if (!IPS_VariableExists($id) || IPS_GetVariable($id)['VariableType'] !== $source['variableType']) {
                    throw new RuntimeException('SOURCE_CHANGED');
                }
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
            $this->WriteAttributeString('JSLiveSourceReferences', json_encode($ids, JSON_THROW_ON_ERROR));
            if (!$this->HasActiveParent()) {
                throw new RuntimeException('PARENT_MISSING');
            }
            $this->SetSummary($this->JSLiveText('Imported JSLive chart'));
            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);
            if (IPS_GetKernelRunlevel() === KR_READY) {
                $refresh = max(1, min(300, (int) ($model['global']['RefreshTime'] ?? 3)));
                $this->SetTimerInterval('JSLiveImportRefresh', $refresh * 1000);
            }
        } catch (Throwable) {
            $this->WriteAttributeString('LastError', 'IMPORTED_CONFIGURATION_INVALID');
            $this->DebugEChartsSetStatus(298);
        }
    }

    protected function BuildJSLiveImportState(bool $ipsView = false): array
    {
        $family = $this->JSLiveModule()[1];
        try {
            $model = $this->JSLiveModel();
            $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
            $design = ['title'    => $this->ReadPropertyString('Title'), 'background' => $this->ReadPropertyInteger($prefix . 'JSLiveBackgroundColor'),
                'fontSizePercent' => $this->ReadPropertyInteger($prefix . 'JSLiveFontSizePercent')];
            foreach (['ShowTitle', 'ShowLegend', 'ShowTooltip', 'AnimationEnabled'] as $key) {
                $design[lcfirst($key)] = $this->ReadPropertyBoolean($prefix . 'JSLive' . $key);
            }
            return EChartsJSLiveRendering::State($model, function (array $payload): array
            {
                $operation = EChartsJSLiveArchive::OPERATION;
                $response = EChartsDataProtocol::DecodeResponse($this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, $payload)
                )), $operation);
                if (!$response['Success']) {
                    throw new RuntimeException('SNAPSHOT_UNAVAILABLE');
                }
                return $response['Payload'];
            }, method_exists($this, 'CurrentTimestamp') ? $this->CurrentTimestamp() : time(), $design);
        } catch (Throwable) {
            return ['schemaVersion' => 1, 'chart' => ['family' => $family], 'status' => 'error',
                'error'             => $this->JSLiveText('The imported source/archive model could not be loaded. Check source types, archive rules and budgets.')];
        }
    }

    protected function RenderJSLiveImportPage(bool $ipsView): string
    {
        return EChartsJSLivePage::Render(
            $this->BuildJSLiveImportState($ipsView),
            $ipsView,
            $this->ResponsiveVisualizationLayoutOptions(!$ipsView),
            $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
            $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage())
        );
    }

    protected function HandleJSLiveImportMessage(int $sender, int $message): void
    {
        if ($sender === 0 && $message === IPS_KERNELSTARTED) {
            $this->InitializeJSLiveImport();
            $this->RefreshJSLiveImport();
        }
        // Current values and running archive windows refresh at the bounded configured cadence;
        // VM_UPDATE subscriptions identify sources without re-reading all archives for each individual event.
    }

    protected function JSLiveConfigurationForm(): string
    {
        $form = $this->LoadConfigurationForm();
        $form['status'] = [['code' => 102, 'icon' => 'active', 'caption' => $this->JSLiveText('Imported JSLive chart')],
            ['code' => 298, 'icon' => 'error', 'caption' => $this->JSLiveText('Imported model unavailable. Check sources, archive rules and budgets.')]];
        try {
            $model = $this->JSLiveModel();
        }
        catch (Throwable) {
            $form['elements'] = [['type' => 'Label', 'caption' => $this->JSLiveText('Imported model unavailable. Check sources, archive rules and budgets.')]];
            $form['actions'] = [];
            return $this->EncodeConfigurationForm($form);
        }
        $form['elements'] = [['type' => 'Label', 'caption' => $this->JSLiveText('Imported JSLive chart. Original sources, archive rules and appearance are retained in a frozen native model.')],
            ['type' => 'ValidationTextBox', 'name' => 'Title', 'caption' => $this->JSLiveText('Title')]];
        if ($model['time'] !== null) {
            $names = ['Decade', 'Year', 'Quarter', 'Month', 'Week', 'Day', 'Hour', 'Minute'];
            $options = [];
            foreach ($names as $index => $name) {
                $options[] = ['caption' => $this->JSLiveText($name), 'value' => $index];
            }
            $items = [['type' => 'Select', 'name' => 'JSLivePeriod', 'caption' => $this->JSLiveText('Period'), 'options' => $options],
                ['type' => 'CheckBox', 'name' => 'JSLiveRelative', 'caption' => $this->JSLiveText('Relative period')]];
            if ($model['family'] === 'cartesian-history') {
                $items[] = ['type' => 'CheckBox', 'name' => 'JSLiveNow', 'caption' => $this->JSLiveText('Use current date')];
                $items[] = ['type' => 'NumberSpinner', 'name' => 'JSLiveStartDate', 'caption' => $this->JSLiveText('Historical anchor (Unix seconds)'), 'minimum' => 0];
            }
            $form['elements'][] = ['type' => 'ExpansionPanel', 'caption' => $this->JSLiveText('Archive period'), 'expanded' => false, 'items' => $items];
        }
        foreach (['' => 'Tile designer', 'IPSView' => 'IPSView designer'] as $prefix => $caption) {
            $items = [];
            if ($prefix !== '') {
                $items[] = ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => $this->JSLiveText('Use tile design for IPSView')];
            }
            $items[] = ['type' => 'SelectColor', 'allowTransparent' => true, 'name' => $prefix . 'JSLiveBackgroundColor', 'caption' => $this->JSLiveText('Chart background')];
            $items[] = ['type' => 'NumberSpinner', 'name' => $prefix . 'JSLiveFontSizePercent', 'caption' => $this->JSLiveText('Font size'), 'minimum' => 50, 'maximum' => 200, 'suffix' => '%'];
            foreach (['ShowTitle' => 'Show chart title', 'ShowLegend' => 'Show legend', 'ShowTooltip' => 'Show tooltip', 'AnimationEnabled' => 'Animation'] as $key => $label) {
                $items[] = ['type' => 'CheckBox', 'name' => $prefix . 'JSLive' . $key, 'caption' => $this->JSLiveText($label)];
            }
            $items[] = ['type' => 'Label', 'caption' => $this->JSLiveText('Schematic color key; compare the real chart in the native HTML download. Source colors and fonts remain in the frozen import model.')];
            $items[] = ['type' => 'Image', 'name' => $prefix . 'JSLiveColorKey', 'width' => '100%', 'center' => true,
                'image'        => EChartsJSLivePreview::ColorKey($model, $this->JSLiveDraftDesign(
                    $this->JSLiveDraftValues(),
                    $prefix === 'IPSView' && $this->ReadPropertyBoolean('IPSViewUseTileDesign') ? '' : $prefix
                ), $this->JSLiveText('Schematic color key: Chart background'))];
            if ($prefix === 'IPSView' && $this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                foreach ($items as &$item) {
                    if (str_starts_with($item['name'] ?? '', 'IPSViewJSLive')) {
                        $item['enabled'] = false;
                    }
                }
                unset($item);
            }
            $form['elements'][] = ['type' => 'ExpansionPanel', 'caption' => $this->JSLiveText($caption), 'expanded' => false, 'items' => $items];
        }
        $form['elements'] = array_merge($form['elements'], $this->IPSViewHTMLPageFormItems());
        $rows = [];
        foreach ($model['sources'] as $source) {
            $rows[] = ['VariableID' => $source['variableID'], 'Label' => $source['label']];
        }
        $form['actions'] = [['type' => 'List', 'caption' => $this->JSLiveText('Existing source references'), 'rowCount' => 6, 'add' => false, 'delete' => false, 'values' => $rows,
            'columns'               => [['name' => 'VariableID', 'caption' => 'ID', 'width' => '100px'], ['name' => 'Label', 'caption' => $this->JSLiveText('Name'), 'width' => 'auto']]],
            ['type' => 'Label', 'caption' => $this->JSLiveText('The original JSLive chart remains available as a comparison and fallback. No source or archive setting is changed here.')]];
        $names = [];
        $collect = static function (array $items) use (&$collect, &$names): void
        {
            foreach ($items as $item) {
                if (isset($item['name']) && in_array($item['type'], ['ValidationTextBox', 'CheckBox', 'SelectColor', 'Select', 'NumberSpinner'], true)) {
                    $names[] = $item['name'];
                }
                if (isset($item['items'])) {
                    $collect($item['items']);
                }
            }
        };
        $collect($form['elements']);
        $names = array_values(array_intersect($names, array_keys($this->JSLiveDraftValues())));
        $draft = '$draft = [' . implode(', ', array_map(static fn (string $name): string => var_export($name, true) . ' => $' . $name, $names)) . ']; ';
        $prefix = $this->JSLiveModule()[0];
        $attach = function (array &$items) use (&$attach, $prefix, $draft, $names): void
        {
            foreach ($items as &$item) {
                if (in_array($item['name'] ?? '', $names, true)) {
                    $item['onChange'] = $draft . $prefix . '_UpdateJSLiveImportColorKey($id, json_encode($draft));';
                }
                if (isset($item['items'])) {
                    $attach($item['items']);
                }
            }
        };
        $attach($form['elements']);
        foreach ([false, true] as $ipsView) {
            $form['actions'][] = ['type' => 'Button', 'caption' => $this->JSLiveText($ipsView ? 'Download native IPSView draft preview' : 'Download native tile draft preview'),
                'download'               => $ipsView ? 'echarts-import-ipsview.html' : 'echarts-import-tile.html',
                'onClick'                => $draft . 'echo ' . $prefix . '_PreviewJSLiveImportDesign($id, json_encode($draft), ' . ($ipsView ? 'true' : 'false') . ');'];
        }
        return $this->EncodeConfigurationForm($form);
    }

    private function JSLiveModule(): array
    {
        foreach (self::JSLIVE_MODULES as $class => $definition) {
            if (is_a($this, $class)) {
                return $definition;
            }
        }
        throw new RuntimeException('FORMAT_UNSUPPORTED: unknown imported target family.');
    }

    private function SealJSLiveOutputs(): void
    {
        $marker = IPS_GetObject($this->InstanceID)['ObjectInfo'];
        if (preg_match('/^SymconECharts:JSLive:1:[a-f0-9]{64}:[a-f0-9]{32}$/D', $marker) !== 1) {
            return;
        }
        $guards = json_decode($this->ReadAttributeString('JSLiveOutputGuards'), true, 16, JSON_THROW_ON_ERROR);
        foreach (IPS_GetChildrenIDs($this->InstanceID) as $id) {
            $object = IPS_GetObject($id);
            if ($object['ObjectType'] !== 2 || $object['ObjectIdent'] !== self::IPSVIEW_OUTPUT_IDENT) {
                throw new RuntimeException('TARGET_CHANGED: unexpected child on an owned new target.');
            }
            if (!isset($guards[$id])) {
                if ($object['ObjectInfo'] !== '') {
                    throw new RuntimeException('TARGET_CHANGED: refusing to claim an existing output.');
                }
                if (!IPS_SetInfo($id, $marker . ':output:' . $object['ObjectIdent'])) {
                    throw new RuntimeException('IMPORT_FAILED: output marker could not be saved.');
                }
                $guards[$id] = EChartsImportJson::Hash(EChartsJSLiveTargetSnapshot::Output($id));
                $this->WriteAttributeString('JSLiveOutputGuards', EChartsImportJson::Canonical($guards));
            }
        }
    }

    private function JSLiveDraftValues(): array
    {
        $values = ['Title' => $this->ReadPropertyString('Title'), 'IPSViewUseTileDesign' => $this->ReadPropertyBoolean('IPSViewUseTileDesign'),
            'JSLivePeriod' => $this->ReadPropertyInteger('JSLivePeriod'), 'JSLiveRelative' => $this->ReadPropertyBoolean('JSLiveRelative'),
            'JSLiveNow'    => $this->ReadPropertyBoolean('JSLiveNow'), 'JSLiveStartDate' => $this->ReadPropertyInteger('JSLiveStartDate')];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['BackgroundColor', 'FontSizePercent'] as $key) {
                $values[$prefix . 'JSLive' . $key] = $this->ReadPropertyInteger($prefix . 'JSLive' . $key);
            }
            foreach (['ShowTitle', 'ShowLegend', 'ShowTooltip', 'AnimationEnabled'] as $key) {
                $values[$prefix . 'JSLive' . $key] = $this->ReadPropertyBoolean($prefix . 'JSLive' . $key);
            }
        }
        return $values;
    }

    private function JSLiveDesignDraft(string $draft): array
    {
        if (strlen($draft) > 8192) {
            throw new RuntimeException('LIMIT_EXCEEDED: imported design draft exceeds 8 KiB.');
        }
        $value = EChartsImportJson::Decode($draft);
        if (!$value instanceof \stdClass) {
            throw new RuntimeException('PACKAGE_INVALID: design draft must be an object.');
        }
        $values = $this->JSLiveDraftValues();
        foreach (get_object_vars($value) as $key => $field) {
            if (!array_key_exists($key, $values) || gettype($field) !== gettype($values[$key])) {
                throw new RuntimeException('PACKAGE_INVALID: unknown or mistyped design draft field.');
            }
            $values[$key] = $field;
        }
        $model = $this->JSLiveModel();
        if ($model['time'] !== null) {
            foreach (['Period' => 'Period', 'Relativ' => 'Relative', 'Now' => 'Now', 'StartDate' => 'StartDate'] as $source => $target) {
                if (isset($model['time']['selection'][$source])) {
                    $model['time']['selection'][$source]['value'] = $values['JSLive' . $target];
                }
            }
            EChartsJSLiveTime::Window($model['time'], $model['family'], time());
        }
        foreach (['', 'IPSView'] as $prefix) {
            if ($values[$prefix . 'JSLiveBackgroundColor'] < -1 || $values[$prefix . 'JSLiveBackgroundColor'] > 0xFFFFFF
                || $values[$prefix . 'JSLiveFontSizePercent'] < 50 || $values[$prefix . 'JSLiveFontSizePercent'] > 200) {
                throw new RuntimeException('PACKAGE_INVALID: invalid imported color or font scale.');
            }
        }
        return [$model, $values];
    }

    private function JSLiveDraftDesign(array $configuration, string $prefix): array
    {
        $design = ['title'    => $configuration['Title'], 'background' => $configuration[$prefix . 'JSLiveBackgroundColor'],
            'fontSizePercent' => $configuration[$prefix . 'JSLiveFontSizePercent']];
        foreach (['ShowTitle', 'ShowLegend', 'ShowTooltip', 'AnimationEnabled'] as $key) {
            $design[lcfirst($key)] = $configuration[$prefix . 'JSLive' . $key];
        }
        return $design;
    }
}
