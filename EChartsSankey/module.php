<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAnimationDesign;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsBarPreview;
use SymconECharts\EChartsBarPreviewForm;
use SymconECharts\EChartsCurrentSources;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\SankeyModel;
use SymconECharts\SankeyPreview;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/SankeyModel.php';
require_once __DIR__ . '/SankeyPreview.php';

class EChartsSankey extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm {
        UpdateBarPreviewFromForm as private UpdateSharedBarPreviewFromForm;
    }
    use SankeyPreview;
    use EChartsCurrentSources;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use EChartsTransientRefresh;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewSankey';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO,
        'Orientation'          => 'horizontal', 'NodeAlign' => 'justify',
        'NodeWidth'            => 20, 'NodeGap' => 12, 'Draggable' => true,
        'ShowTooltip'          => true, 'ShowLabels' => true, 'ShowValues' => false,
        'EdgeColorMode'        => 'gradient', 'EdgeOpacityPercent' => 35, 'EdgeCurvenessPercent' => 50,
        'TitleFontSizePercent' => 100, 'LabelFontSizePercent' => 100,
        'TitleColor'           => -1, 'LabelColor' => -1, 'NodeColor' => -1, 'EdgeColor' => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTransientRefresh('ECSA');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Nodes', '[]');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Unit', '');
        foreach (['', 'IPSView'] as $prefix) {
            foreach (self::DESIGN_DEFAULTS as $name => $default) {
                match (gettype($default)) {
                    'boolean' => $this->RegisterPropertyBoolean($prefix . $name, $default),
                    'integer' => $this->RegisterPropertyInteger($prefix . $name, $default),
                    default   => $this->RegisterPropertyString($prefix . $name, $default)
                };
            }
            $this->RegisterAnimationProperties($prefix, true, 350, 500);
        }
        $this->RegisterIPSViewHTMLPageProperties();
        $this->RegisterEChartsIPSViewTransport();
        $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
        $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
        $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
        $this->RegisterPropertyInteger('IPSViewBackgroundOpacityPercent', EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT);
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Sankey for IPSView'), 90);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
        }
    }

    public function GetCompatibleParents(): string
    {
        return json_encode(['type' => 'connect', 'moduleIDs' => [self::GATEWAY_MODULE_ID]], JSON_THROW_ON_ERROR);
    }

    public function GetConfigurationForm(): string
    {
        $form = $this->LoadConfigurationForm();
        $connectionFields = $this->ConnectionFields(json_decode($this->ReadPropertyString('Nodes'), true));
        foreach ($form['elements'] as &$element) {
            if (($element['name'] ?? '') === 'Sources') {
                $element = $connectionFields;
            }
        }
        unset($element);
        $diagnostic = $this->GetConfigurationError()['Message'] ?? $this->ReadAttributeString('LastError');
        $form['elements'][] = ['type' => 'Label', 'name' => 'ConfigurationDiagnostics',
            'caption'                 => $diagnostic !== '' ? $diagnostic : $this->Translate('Sankey configuration is valid.')];
        $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        $form['elements'] = $this->WithAnimationFormControls($form['elements']);
        $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECSA');
        $form = $this->WithIPSViewDesignFormState($form, 'ECSA');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Layout and interaction', 'start' => 'Orientation'],
            ['caption' => 'Labels and typography', 'start' => 'ShowLabels'],
            ['caption' => 'Node and flow colors', 'start' => 'NodeColor']
        ]);
        $fields = [
            'Title', 'Nodes', 'Sources', 'Unit', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
            'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent',
            ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))
        ];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECSA', $fields));
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
            return;
        }
        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function CopyTileDesignToIPSView(): void
    {
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            $value = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($name),
                'integer' => $this->ReadPropertyInteger($name),
                default   => $this->ReadPropertyString($name)
            };
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $value);
        }
        foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
            'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
            $value = match ($name) {
                'AnimationEnabled'                         => $this->ReadPropertyBoolean($name),
                'AnimationEasing', 'AnimationEasingUpdate' => $this->ReadPropertyString($name),
                default                                    => $this->ReadPropertyInteger($name)
            };
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $value);
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function GetSankeyData(): string
    {
        return $this->GetSankeyDataForOutput(false);
    }

    public function GetSankeyDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode([
            'valid'       => $error === null,
            'sourceCount' => count($this->ConfiguredVariableIDs()),
            'lastError'   => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderSankeyHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderSankeyHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    public function RetryTransient(): void
    {
        $this->PublishVisualizationState();
    }

    /** @param array<int,mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
            $this->Initialize();
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
            return;
        }
        if ($Message === VM_UPDATE && in_array($SenderID, $this->ConfiguredVariableIDs(), true)) {
            $this->PublishVisualizationState();
        }
    }

    /** Updates draft diagnostics, node selectors and both previews without saving. */
    public function UpdateBarPreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($values)) {
                throw new InvalidArgumentException($this->Translate('The Sankey configuration is not valid JSON.'));
            }
            if (isset($values['Nodes']) && is_array($values['Nodes'])) {
                $this->UpdateNodeOptions(json_encode($values['Nodes'], JSON_THROW_ON_ERROR));
            }
            $this->GetValidatedConfiguration($values);
            $message = $this->Translate('Sankey configuration is valid.');
        } catch (Throwable $exception) {
            $message = $exception instanceof JsonException
                ? $this->Translate('The Sankey configuration is not valid JSON.') : $exception->getMessage();
        }
        $this->UpdateFormField('ConfigurationDiagnostics', 'caption', $message);
        $this->UpdateSharedBarPreviewFromForm($Configuration);
    }

    /** Updates connection selectors after editing unsaved node rows. */
    public function UpdateNodeOptions(string $JSONNodes): void
    {
        try {
            $nodes = json_decode($JSONNodes, true, 64, JSON_THROW_ON_ERROR);
            $field = $this->ConnectionFields($nodes);
            $this->UpdateFormField('Sources', 'columns', json_encode($field['columns'], JSON_THROW_ON_ERROR));
            $this->UpdateFormField('Sources', 'form', json_encode($field['form'], JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Incomplete list edits keep their last usable selector options.
        }
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        return $this->SankeyPreviewSvg($values, $ipsView);
    }

    private function ConnectionFields(mixed $nodes): array
    {
        $options = [['caption' => $this->Translate('Select node'), 'value' => '']];
        $seen = [];
        foreach (is_array($nodes) ? array_slice($nodes, 0, 100) : [] as $node) {
            if (!is_array($node) || !is_string($node['Key'] ?? null) || !is_string($node['Label'] ?? null)) {
                continue;
            }
            $key = trim($node['Key']);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[] = ['caption' => trim($node['Label']) . ' (' . $key . ')', 'value' => $key];
        }
        foreach ($this->LoadConfigurationForm()['elements'] as $field) {
            if (($field['name'] ?? '') !== 'Sources') {
                continue;
            }
            foreach ($field['columns'] as &$column) {
                if (in_array($column['name'], ['Source', 'Target'], true)) {
                    $column['edit']['options'] = $options;
                }
            }
            unset($column);
            foreach ($field['form'] as &$item) {
                if (in_array($item['name'], ['Source', 'Target'], true)) {
                    $item['options'] = $options;
                }
            }
            unset($item);
            return $field;
        }
        throw new RuntimeException('The Sankey connection list is missing.');
    }

    private function GetSankeyDataForOutput(bool $ipsView): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->SetStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }
        $configuration = $this->GetValidatedConfiguration();
        $sources = $configuration['sources'];
        $values = [];
        foreach (array_unique(array_column($sources, 'VariableID')) as $id) {
            $values[$id] = $this->ReadCurrentSource($id);
        }
        try {
            $model = SankeyModel::Build($configuration['nodes'], $sources, $values, fn (string $text): string => $this->Translate($text));
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'sankey',
            'theme'         => $this->EffectiveTheme($ipsView),
            'sankey'        => [
                'title' => $this->ReadPropertyString('Title'),
                'unit'  => trim($this->ReadPropertyString('Unit')),
                'style' => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderSankeyHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Sankey',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-sankey-root', 'echarts-sankey'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Sankey nodes and connections.'                   => $this->Translate('Configure valid Sankey nodes and connections.'),
                'The Sankey design is invalid.'                                   => $this->Translate('The Sankey design is invalid.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Sankey values could not be loaded.'                          => $this->Translate('The Sankey values could not be loaded.'),
                'Sankey values must be finite and non-negative.'                  => $this->Translate('Sankey values must be finite and non-negative.'),
                'No positive flows'                                               => $this->Translate('No positive flows'),
                'Apache ECharts could not be initialized.'                        => $this->Translate('Apache ECharts could not be initialized.'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::SankeyJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $this->SynchronizeSourceReferences();
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->SetStatus($error['Status']);
            return;
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => $this->Translate('The Sankey design is invalid.')];
        }
        return null;
    }

    private function GetValidatedConfiguration(array $values = []): array
    {
        $nodes = $this->DecodeRows($values['Nodes'] ?? $this->ReadPropertyString('Nodes'));
        $sources = $this->DecodeRows($values['Sources'] ?? $this->ReadPropertyString('Sources'));
        $configuration = SankeyModel::Validate($nodes, $sources, fn (string $text): string => $this->Translate($text));
        foreach ($configuration['sources'] as $index => $source) {
            $id = $source['VariableID'];
            if (!IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException(sprintf($this->Translate('Connection %d: select an existing Integer or Float variable.'), $index + 1));
            }
        }
        if (mb_strlen(trim((string) ($values['Unit'] ?? $this->ReadPropertyString('Unit')))) > 24) {
            throw new InvalidArgumentException($this->Translate('The Sankey unit must not exceed 24 characters.'));
        }
        return $configuration;
    }

    private function GetValidatedSources(): array
    {
        return $this->GetValidatedConfiguration()['sources'];
    }

    private function DecodeRows(mixed $raw): array
    {
        if (is_string($raw) && strlen($raw) > 65536) {
            throw new InvalidArgumentException($this->Translate('The Sankey configuration is too large.'));
        }
        try {
            $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        } catch (JsonException $exception) {
            throw new InvalidArgumentException($this->Translate('The Sankey configuration is not valid JSON.'), 0, $exception);
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException($this->Translate('Sankey nodes and connections must be lists.'));
        }
        return $rows;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['Orientation' => ['horizontal', 'vertical'], 'NodeAlign' => ['justify', 'left', 'right'],
            'EdgeColorMode'     => ['source', 'target', 'gradient', 'fixed']] as $name => $allowed) {
            if (!in_array($this->ReadPropertyString($prefix . $name), $allowed, true)) {
                return false;
            }
        }
        foreach (['NodeWidth'      => [4, 40], 'NodeGap' => [0, 40], 'EdgeOpacityPercent' => [0, 100],
            'EdgeCurvenessPercent' => [0, 100], 'TitleFontSizePercent' => [50, 200],
            'LabelFontSizePercent' => [50, 200]] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['TitleColor', 'LabelColor', 'NodeColor', 'EdgeColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = $this->ReadAnimationStyle($prefix);
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if ($name === 'EChartsTheme') {
                continue;
            }
            $key = lcfirst($name);
            $style[$key] = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($prefix . $name),
                'integer' => $this->ReadPropertyInteger($prefix . $name),
                default   => $this->ReadPropertyString($prefix . $name)
            };
            if (str_ends_with($name, 'Color')) {
                $style[$key] = $style[$key] < 0 ? '' : EChartsAsset::ColorToHex($style[$key]);
            }
        }
        return $style;
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $this->ReadPropertyString($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? 'IPSViewEChartsTheme' : 'EChartsTheme');
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return $this->ErrorState($error['Status'] === self::STATUS_DESIGN_INVALID
                ? 'The Sankey design is invalid.' : 'Configure valid Sankey nodes and connections.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetSankeyDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Sankey values must be finite and non-negative.');
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState('The Sankey values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'sankey', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'sankey', 'status' => 'error', 'chart' => null, 'error' => $message];
    }

    private function PublishVisualizationState(): void
    {
        try {
            $state = $this->BuildVisualizationState();
            $states = [$state];
            $this->UpdateVisualizationValue(json_encode(
                $state,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
            $this->PushEChartsIPSViewState(function () use (&$states): array
            {
                $state = $this->BuildVisualizationState(true);
                $states[] = $state;
                return $state;
            });
            $this->ScheduleTransientRefresh($states, 'The Sankey values could not be loaded.');
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
    }

    private function PublishIPSViewHTML(): void
    {
        if (!$this->IsIPSViewHTMLPageEnabled()) {
            return;
        }
        try {
            $this->UpdateIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->GetIPSViewHTML());
        } catch (Throwable $exception) {
            $this->SendDebug('PublishIPSViewHTML', $exception::class, 0);
        }
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-sankey-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string,mixed>> $elements @return array<string,mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $rows = [];
        foreach ($elements as $element) {
            if (($element['type'] ?? null) !== 'ExpansionPanel' || ($element['caption'] ?? null) !== 'Tile designer') {
                continue;
            }
            foreach ($element['items'] as $row) {
                if (is_string($row['name'] ?? null) && array_key_exists($row['name'], self::DESIGN_DEFAULTS)) {
                    $row['name'] = 'IPSView' . $row['name'];
                    $rows[] = $row;
                    continue;
                }
                $items = [];
                foreach ($row['items'] ?? [] as $item) {
                    if (is_string($item['name'] ?? null) && (array_key_exists($item['name'], self::DESIGN_DEFAULTS)
                        || str_starts_with($item['name'], 'Animation'))) {
                        $item['name'] = 'IPSView' . $item['name'];
                        $items[] = $item;
                    }
                }
                if ($items !== []) {
                    $rows[] = ['type' => 'RowLayout', 'items' => $items];
                }
            }
            break;
        }
        if ($rows === []) {
            throw new RuntimeException('The Sankey Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECSA_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
