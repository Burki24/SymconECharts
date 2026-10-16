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
use SymconECharts\RadarModel;
use SymconECharts\RadarPreview;

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
require_once __DIR__ . '/RadarModel.php';
require_once __DIR__ . '/RadarPreview.php';

class EChartsRadar extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm {
        UpdateBarPreviewFromForm as private UpdateSharedBarPreviewFromForm;
    }
    use RadarPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewRadar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO, 'Shape' => 'polygon', 'StartAngle' => 90, 'Clockwise' => false,
        'ShowLegend'           => true, 'ShowTooltip' => true, 'ShowAxisNames' => true, 'ShowAxisValues' => false,
        'ShowSplitArea'        => true, 'SplitNumber' => 5, 'AxisNameGap' => 15,
        'LineWidth'            => 2, 'LineOpacityPercent' => 100, 'AreaOpacityPercent' => 25, 'SymbolSize' => 4,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'GridColor' => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTransientRefresh('ECRA');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Axes', '[]');
        $this->RegisterPropertyString('Sources', '[]');
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
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Radar for IPSView'), 90);
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
        $connectionFields = $this->ConnectionFields(json_decode($this->ReadPropertyString('Axes'), true));
        foreach ($form['elements'] as &$element) {
            if (($element['name'] ?? '') === 'Sources') {
                $element = $connectionFields;
            }
        }
        unset($element);
        $diagnostic = $this->GetConfigurationError()['Message'] ?? $this->ReadAttributeString('LastError');
        $form['elements'][] = ['type' => 'Label', 'name' => 'ConfigurationDiagnostics',
            'caption'                 => $diagnostic !== '' ? $diagnostic : $this->Translate('Radar configuration is valid.')];
        $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        $form['elements'] = $this->WithAnimationFormControls($form['elements']);
        $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECRA');
        $form = $this->WithIPSViewDesignFormState($form, 'ECRA');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Layout and interaction', 'start' => 'Shape'],
            ['caption' => 'Lines and areas', 'start' => 'LineWidth'],
            ['caption' => 'Labels and typography', 'start' => 'TitleFontSizePercent']
        ]);
        $fields = [
            'Title', 'Axes', 'Sources', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
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
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECRA', $fields));
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

    public function GetRadarData(): string
    {
        return $this->GetRadarDataForOutput(false);
    }

    public function GetRadarDiagnostic(): string
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
        return $this->RenderRadarHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderRadarHTMLPage(true);
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

    /** Updates draft diagnostics, axis selectors and both previews without saving. */
    public function UpdateBarPreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($values)) {
                throw new InvalidArgumentException($this->Translate('The Radar configuration is not valid JSON.'));
            }
            if (isset($values['Axes']) && is_array($values['Axes'])) {
                $this->UpdateAxisOptions(json_encode($values['Axes'], JSON_THROW_ON_ERROR));
            }
            $this->GetValidatedConfiguration($values);
            $message = $this->Translate('Radar configuration is valid.');
        } catch (Throwable $exception) {
            $message = $exception instanceof JsonException
                ? $this->Translate('The Radar configuration is not valid JSON.') : $exception->getMessage();
        }
        $this->UpdateFormField('ConfigurationDiagnostics', 'caption', $message);
        $this->UpdateSharedBarPreviewFromForm($Configuration);
    }

    /** Updates assignment selectors after editing unsaved axes. */
    public function UpdateAxisOptions(string $JSONAxes): void
    {
        try {
            $field = $this->ConnectionFields(json_decode($JSONAxes, true, 64, JSON_THROW_ON_ERROR));
            $this->UpdateFormField('Sources', 'columns', json_encode($field['columns'], JSON_THROW_ON_ERROR));
            $this->UpdateFormField('Sources', 'form', json_encode($field['form'], JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Incomplete list edits keep their last usable selector options.
        }
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        return $this->RadarPreviewSvg($values, $ipsView);
    }

    private function AxisOptions(mixed $axes): array
    {
        $options = [['caption' => $this->Translate('Select axis'), 'value' => '']];
        $seen = [];
        foreach (is_array($axes) ? array_slice($axes, 0, 10) : [] as $node) {
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
        return $options;
    }

    private function ConnectionFields(mixed $axes): array
    {
        $options = $this->AxisOptions($axes);
        foreach ($this->LoadConfigurationForm()['elements'] as $field) {
            if (($field['name'] ?? '') !== 'Sources') {
                continue;
            }
            foreach ($field['columns'] as &$column) {
                if (in_array($column['name'], ['Axis'], true)) {
                    $column['edit']['options'] = $options;
                }
            }
            unset($column);
            foreach ($field['form'] as &$item) {
                if (in_array($item['name'], ['Axis'], true)) {
                    $item['options'] = $options;
                }
            }
            unset($item);
            return $field;
        }
        throw new RuntimeException('The Radar connection list is missing.');
    }

    private function GetRadarDataForOutput(bool $ipsView): string
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
            $model = RadarModel::Build($configuration['axes'], $sources, $values, fn (string $text): string => $this->Translate($text));
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'radar',
            'theme'         => $this->EffectiveTheme($ipsView),
            'radar'         => [
                'title' => $this->ReadPropertyString('Title'),
                'style' => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderRadarHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Radar',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-radar-root', 'echarts-radar'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Radar axes and comparison values.'                                                           => $this->Translate('Configure valid Radar axes and comparison values.'),
                'The Radar design is invalid.'                                                                                => $this->Translate('The Radar design is invalid.'),
                'Connect an active EChartsGateway.'                                                                           => $this->Translate('Connect an active EChartsGateway.'),
                'The Radar values could not be loaded.'                                                                       => $this->Translate('The Radar values could not be loaded.'),
                'Radar values must be finite numbers with timestamps.'                                                        => $this->Translate('Radar values must be finite numbers with timestamps.'),
                'Some values exceed their axis range and are visually limited.'                                               => $this->Translate('Some values exceed their axis range and are visually limited.'),
                'Apache ECharts could not be initialized.'                                                                    => $this->Translate('Apache ECharts could not be initialized.'),
                'Data could not be refreshed. Showing the last available values.'                                             => $this->Translate(
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::RadarJavaScript(),
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
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => $this->Translate('The Radar design is invalid.')];
        }
        return null;
    }

    private function GetValidatedConfiguration(array $values = []): array
    {
        $axes = $this->DecodeRows($values['Axes'] ?? $this->ReadPropertyString('Axes'));
        $sources = $this->DecodeRows($values['Sources'] ?? $this->ReadPropertyString('Sources'));
        $configuration = RadarModel::Validate($axes, $sources, fn (string $text): string => $this->Translate($text));
        foreach ($configuration['sources'] as $index => $source) {
            $id = $source['VariableID'];
            if (!IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException(sprintf($this->Translate('Value %d: select an existing Integer or Float variable.'), $index + 1));
            }
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
            throw new InvalidArgumentException($this->Translate('The Radar configuration is too large.'));
        }
        try {
            $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        } catch (JsonException $exception) {
            throw new InvalidArgumentException($this->Translate('The Radar configuration is not valid JSON.'), 0, $exception);
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException($this->Translate('Radar axes and values must be lists.'));
        }
        return $rows;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'Shape'), ['polygon', 'circle'], true)) {
            return false;
        }
        foreach (['StartAngle' => [0, 360], 'SplitNumber' => [1, 10], 'AxisNameGap' => [0, 40],
            'LineWidth'        => [0, 8], 'LineOpacityPercent' => [0, 100], 'AreaOpacityPercent' => [0, 100],
            'SymbolSize'       => [0, 16], 'TitleFontSizePercent' => [50, 200], 'AxisFontSizePercent' => [50, 200]] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'GridColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
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
                ? 'The Radar design is invalid.' : 'Configure valid Radar axes and comparison values.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetRadarDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Radar values must be finite numbers with timestamps.');
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState('The Radar values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'radar', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'radar', 'status' => 'error', 'chart' => null, 'error' => $message];
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
            $this->ScheduleTransientRefresh($states, 'The Radar values could not be loaded.');
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
            '#echarts-radar-root',
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
            throw new RuntimeException('The Radar Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECRA_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}

