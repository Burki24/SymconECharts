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
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\ThemeRiverData;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/ThemeRiverData.php';
require_once __DIR__ . '/ThemeRiverPreview.php';

class EChartsThemeRiver extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use SymconECharts\ThemeRiverPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewThemeRiver';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => 'auto', 'ShowTooltip' => true, 'ShowLegend' => true, 'ShowLabels' => false,
        'BandOpacityPercent'   => 85, 'BorderWidth' => 0, 'BorderColor' => -1,
        'ShowAxisLabels'       => true, 'ShowAxisTicks' => true,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100,
        'LabelFontSizePercent' => 100, 'LegendFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'LabelColor' => -1, 'LegendColor' => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECRV_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterTransientRefresh('ECRV');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Range', '7d');
        $this->RegisterPropertyString('Aggregation', 'hour');
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
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('ThemeRiver for IPSView'), 90);
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
        $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        $form['elements'] = $this->WithAnimationFormControls($form['elements']);
        $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECRV');
        $form = $this->WithIPSViewDesignFormState($form, 'ECRV');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
            ['caption' => 'Bands and display', 'start' => 'ShowTooltip'],
            ['caption' => 'Time axis', 'start' => 'ShowAxisLabels'],
            ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
        ]);
        $fields = ['Title', 'Sources', 'Range', 'Aggregation',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
            'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECRV', $fields));
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if (!$this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
            throw new InvalidArgumentException('Unknown action: ' . $Ident);
        }
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

    public function GetThemeRiverData(): string
    {
        return $this->GetThemeRiverDataForOutput(false);
    }

    public function GetThemeRiverDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode(
            ['valid'        => $error === null,
                'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderThemeRiverHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderThemeRiverHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    public function RefreshArchive(): void
    {
        $this->PublishVisualizationState();
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
        }
    }

    protected function CurrentTimestamp(): int
    {
        return time();
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        return $this->ThemeRiverPreviewSvg($values, $ipsView);
    }

    private function GetThemeRiverDataForOutput(bool $ipsView): string
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
        $sources = $this->GetValidatedSources();
        $query = ThemeRiverData::Window(
            $this->ReadPropertyString('Range'),
            $this->ReadPropertyString('Aggregation'),
            $this->CurrentTimestamp(),
            date_default_timezone_get()
        );
        $series = [];
        $descriptors = [];
        foreach ($sources as $source) {
            $series[] = $this->ReadArchiveSource($source, $query)['Points'];
            $descriptors[] = ['id' => 'variable-' . $source['VariableID'], 'label' => $source['Label'],
                'unit'             => $source['Unit'], 'decimals' => $source['Decimals'],
                'color'            => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color'])];
        }
        try {
            $data = ThemeRiverData::Join($series, $query['Intervals']);
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1, 'family' => 'themeRiver', 'theme' => $this->EffectiveTheme($ipsView),
            'themeRiver'    => ['title' => $this->ReadPropertyString('Title'), 'unit' => $sources[0]['Unit'],
                'aggregation'           => $this->ReadPropertyString('Aggregation'), 'sources' => $descriptors,
                ...$data, 'style'       => $this->ReadDesignStyle($ipsView)],
            'range' => ['key'  => $this->ReadPropertyString('Range'), 'startTimestamp' => $query['StartTimestamp'],
                'endTimestamp' => $query['EndTimestamp'], 'aggregationLevel' => $query['AggregationLevel'],
                'timezone'     => date_default_timezone_get()]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderThemeRiverHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts ThemeRiver',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-theme-river-root', 'echarts-theme-river'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid ThemeRiver archive variables.'                                                     => $this->Translate('Configure valid ThemeRiver archive variables.'),
                'Connect an active EChartsGateway.'                                                                 => $this->Translate('Connect an active EChartsGateway.'),
                'The ThemeRiver values could not be loaded.'                                                        => $this->Translate('The ThemeRiver values could not be loaded.'),
                'The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.' => $this->Translate('The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.'),
                'ThemeRiver requires standard archive variables, not counters.'                                     => $this->Translate('ThemeRiver requires standard archive variables, not counters.'),
                'No common completed archive intervals.'                                                            => $this->Translate('No common completed archive intervals.'),
                'Intervals with missing archive values are left blank.'                                             => $this->Translate('Intervals with missing archive values are left blank.'),
                'All completed interval means are zero.'                                                            => $this->Translate('All completed interval means are zero.'),
                'Data could not be refreshed. Showing the last available values.'                                   => $this->Translate('Data could not be refreshed. Showing the last available values.'),
                'Apache ECharts could not be initialized.'                                                          => $this->Translate('Apache ECharts could not be initialized.'),
                'Completed interval means'                                                                          => $this->Translate('Completed interval means'),
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::ThemeRiverJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 0);
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
        $this->SetSummary($this->ReadPropertyString('Range') . ' / ' . $this->ReadPropertyString('Aggregation'));
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (mb_strlen($this->ReadPropertyString('Title')) > 200
            || !in_array($this->ReadPropertyString('Range'), ['24h', '7d', '30d'], true)
            || !in_array($this->ReadPropertyString('Aggregation'), ['hour', 'day'], true)
            || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The ThemeRiver settings are invalid.'];
        }
        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Color:int,Unit:string,Decimals:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $json = $this->ReadPropertyString('Sources');
            if (strlen($json) > 65536) {
                throw new InvalidArgumentException('ThemeRiver sources are too large.');
            }
            $raw = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('ThemeRiver sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > 8) {
            throw new InvalidArgumentException('Configure 1 to 8 unique numeric ThemeRiver variables.');
        }
        $sources = [];
        $units = [];
        $seen = [];
        foreach ($raw as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            $label = is_array($entry) ? ($entry['Label'] ?? '') : null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || mb_strlen(trim($label)) > 120) {
                throw new InvalidArgumentException('ThemeRiver sources need distinct numeric variable IDs and valid labels.');
            }
            $color = $entry['Color'] ?? -1;
            if (!is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException('ThemeRiver source colors are invalid.');
            }
            $presentation = EChartsVariablePresentation::Resolve($id, 0.0, 100.0, '', 2, true);
            $sources[] = ['VariableID' => $id, 'Label' => trim($label),
                'Color'                => $color, 'Unit' => $presentation['unit'], 'Decimals' => $presentation['decimals']];
            $units[$presentation['unit']] = true;
            $seen[$id] = true;
        }
        if (count($units) > 1) {
            throw new InvalidArgumentException('ThemeRiver variables must use the same unit.');
        }
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        foreach ($sources as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
        }
        unset($source);
        return $sources;
    }

    /** @param array{VariableID:int} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        if ($query['Empty']) {
            return ['Points' => [], 'Truncated' => false];
        }
        $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
        $payload = ['VariableID' => $source['VariableID'],
            'StartTimestamp'     => $query['StartTimestamp'], 'EndTimestamp' => $query['EndTimestamp'],
            'Mode'               => 'aggregated', 'Reducer' => 'average',
            'Limit'              => $query['Limit'], 'AggregationLevel' => $query['AggregationLevel']];
        $this->WriteAttributeString('LastError', '');
        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, $payload)
                )),
                $operation
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_RESPONSE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned an invalid ThemeRiver archive response.', 0, $exception);
        }
        if (!$response['Success']) {
            $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $code);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway archive request failed: ' . $code);
        }
        $archive = $response['Payload'];
        if (($archive['VariableID'] ?? null) !== $source['VariableID']
            || !is_array($archive['Points'] ?? null) || ($archive['Truncated'] ?? null) !== false
            || ($archive['StartTimestamp'] ?? null) !== $query['StartTimestamp']
            || ($archive['EndTimestamp'] ?? null) !== $query['EndTimestamp']
            || !in_array($archive['VariableType'] ?? null, [1, 2], true)
            || ($archive['EffectiveReducer'] ?? null) !== 'average'
            || ($archive['ArchiveAggregationType'] ?? null) !== 'standard') {
            $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid ThemeRiver archive data.');
        }
        return $archive;
    }

    private function SynchronizeSourceReferences(): void
    {
        $previous = json_decode($this->ReadAttributeString('RegisteredSourceVariableIDs'), true);
        $previous = is_array($previous) ? array_values(array_filter($previous, is_int(...))) : [];
        $raw = json_decode($this->ReadPropertyString('Sources'), true);
        $current = [];
        foreach (is_array($raw) ? $raw : [] as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            if (is_int($id) && $id > 0 && !in_array($id, $current, true)) {
                $current[] = $id;
            }
        }
        foreach (array_diff($previous, $current) as $id) {
            $this->UnregisterReference($id);
        }
        foreach (array_diff($current, $previous) as $id) {
            if (IPS_VariableExists($id)) {
                $this->RegisterReference($id);
            }
        }
        $this->WriteAttributeString('RegisteredSourceVariableIDs', json_encode($current, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            return $this->ErrorState('Configure valid ThemeRiver archive variables.');
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetThemeRiverDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState(match ($this->ReadAttributeString('LastError')) {
                'REDUCER_NOT_SUPPORTED' => 'ThemeRiver requires standard archive variables, not counters.',
                'INVALID_ARCHIVE_DATA'  => 'The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.',
                default                 => 'The ThemeRiver values could not be loaded.'
            });
        }
        return ['schemaVersion' => 1, 'family' => 'themeRiver', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'themeRiver', 'status' => 'error', 'chart' => null, 'error' => $message];
    }

    private function PublishVisualizationState(): void
    {
        try {
            $state = $this->BuildVisualizationState();
            $states = [$state];
            $this->UpdateVisualizationValue(json_encode(
                $state,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
            $this->PushEChartsIPSViewState(function () use (&$states): array
            {
                $state = $this->BuildVisualizationState(true);
                $states[] = $state;
                return $state;
            });
            $this->ScheduleTransientRefresh($states, 'The ThemeRiver values could not be loaded.');
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

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['BandOpacityPercent' => [0, 100], 'BorderWidth' => [0, 5],
            'TitleFontSizePercent'     => [50, 200], 'AxisFontSizePercent' => [50, 200],
            'LabelFontSizePercent'     => [50, 200], 'LegendFontSizePercent' => [50, 200]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'BorderColor', 'LabelColor', 'LegendColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = [...$this->ReadAnimationStyle($prefix)];
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if ($name === 'EChartsTheme') {
                continue;
            }
            $value = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($prefix . $name),
                'integer' => $this->ReadPropertyInteger($prefix . $name),
                default   => $this->ReadPropertyString($prefix . $name)
            };
            $style[lcfirst($name)] = str_ends_with($name, 'Color')
                ? ($value < 0 ? '' : EChartsAsset::ColorToHex($value)) : $value;
        }
        return $style;
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $this->ReadPropertyString($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? 'IPSViewEChartsTheme' : 'EChartsTheme');
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-theme-river-root',
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
            throw new RuntimeException('The ThemeRiver Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECRV_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }
}
