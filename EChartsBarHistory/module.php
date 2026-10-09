<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsArchiveQuery;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsUnitAxes;
use SymconECharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsUnitAxes.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarHistory extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarHistory';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];
    private const MAXIMUM_SOURCE_COUNT = 16;
    private const TIME_AXIS_LABEL_FORMATS = ['auto', 'time', 'date', 'date-time'];
    private const BAR_FILL_MODES = ['solid', 'gradient'];
    private const DESIGN_PROPERTY_TYPES = [
        'EChartsTheme'        => 'string',
        'ShowValues'          => 'boolean',
        'ShowGrid'            => 'boolean',
        'EnableZoom'          => 'boolean',
        'RoundedBars'         => 'boolean',
        'BarWidthPercent'     => 'integer',
        'BarFillMode'         => 'string',
        'BarGradientColor'    => 'integer',
        'BarOpacityPercent'   => 'integer',
        'BarCornerRadius'     => 'integer',
        'TitleFontSizePercent'=> 'integer',
        'AxisFontSizePercent' => 'integer',
        'ValueFontSizePercent'=> 'integer',
        'TitleColor'          => 'integer',
        'AxisColor'           => 'integer',
        'ValueColor'          => 'integer',
        'GridColor'           => 'integer'
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECBH_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Range', '24h');
        $this->RegisterPropertyInteger('CustomRangeValue', 7);
        $this->RegisterPropertyString('CustomRangeUnit', 'day');
        $this->RegisterPropertyString('DataMode', 'auto');
        $this->RegisterPropertyInteger('PointBudget', 1000);
        $this->RegisterPropertyString('TimeAxisLabelFormat', 'auto');
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyBoolean('ShowValues', false);
        $this->RegisterPropertyBoolean('ShowGrid', true);
        $this->RegisterPropertyBoolean('EnableZoom', true);
        $this->RegisterPropertyBoolean('RoundedBars', false);
        $this->RegisterPropertyInteger('BarWidthPercent', 70);
        $this->RegisterPropertyString('BarFillMode', 'solid');
        $this->RegisterPropertyInteger('BarGradientColor', -1);
        $this->RegisterPropertyInteger('BarOpacityPercent', 100);
        $this->RegisterPropertyInteger('BarCornerRadius', 6);
        $this->RegisterPropertyInteger('TitleFontSizePercent', 100);
        $this->RegisterPropertyInteger('AxisFontSizePercent', 100);
        $this->RegisterPropertyInteger('ValueFontSizePercent', 100);
        $this->RegisterPropertyInteger('TitleColor', -1);
        $this->RegisterPropertyInteger('AxisColor', -1);
        $this->RegisterPropertyInteger('ValueColor', -1);
        $this->RegisterPropertyInteger('GridColor', -1);
        $this->RegisterIPSViewHTMLPageProperties();
        $this->RegisterEChartsIPSViewTransport();
        $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
        $this->RegisterPropertyBoolean('IPSViewUseTileTimeSettings', true);
        $this->RegisterPropertyString('IPSViewRange', '24h');
        $this->RegisterPropertyInteger('IPSViewCustomRangeValue', 7);
        $this->RegisterPropertyString('IPSViewCustomRangeUnit', 'day');
        $this->RegisterPropertyString('IPSViewTimeAxisLabelFormat', 'auto');
        $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
        $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
        $this->RegisterPropertyInteger(
            'IPSViewBackgroundOpacityPercent',
            EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT
        );
        foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
            $default = match ($name) {
                'EChartsTheme'              => EChartsAsset::THEME_AUTO,
                'BarFillMode'               => 'solid',
                'ShowValues', 'RoundedBars' => false,
                'ShowGrid', 'EnableZoom'    => true,
                'BarWidthPercent'           => 70,
                'BarOpacityPercent', 'TitleFontSizePercent', 'AxisFontSizePercent',
                'ValueFontSizePercent'      => 100,
                'BarCornerRadius'           => 6,
                default                     => -1
            };
            match ($type) {
                'string'  => $this->RegisterPropertyString('IPSView' . $name, $default),
                'boolean' => $this->RegisterPropertyBoolean('IPSView' . $name, $default),
                default   => $this->RegisterPropertyInteger('IPSView' . $name, $default)
            };
        }
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(
            self::IPSVIEW_OUTPUT_IDENT,
            $this->Translate('Historical Bar for IPSView'),
            90
        );
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
        }
    }

    public function GetCompatibleParents(): string
    {
        return json_encode([
            'type'      => 'connect',
            'moduleIDs' => [self::GATEWAY_MODULE_ID]
        ], JSON_THROW_ON_ERROR);
    }

    public function GetConfigurationForm(): string
    {
        $form = $this->LoadConfigurationForm();
        if (isset($form['elements']) && is_array($form['elements'])) {
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['CustomRangeValue', 'CustomRangeUnit'],
                $this->ReadPropertyString('Range') === 'custom'
            );
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['BarGradientColor'],
                $this->ReadPropertyString('BarFillMode') === 'gradient'
            );
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['BarCornerRadius'],
                $this->ReadPropertyBoolean('RoundedBars')
            );
            $form['elements'] = $this->WithIPSViewTimeSettingsByRange(
                $form['elements'],
                $this->BuildIPSViewTimeSettings($form['elements'])
            );
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        }

        return $this->EncodeConfigurationForm($this->WithIPSViewDesignFormState($form, 'ECBH'));
    }

    public function UpdateTimeRangeForm(string $Range): void
    {
        $custom = $Range === 'custom';
        $this->UpdateFormField('CustomRangeValue', 'visible', $custom);
        $this->UpdateFormField('CustomRangeUnit', 'visible', $custom);
    }

    public function UpdateIPSViewTimeRangeForm(bool $IPSViewUseTileTimeSettings, string $IPSViewRange): void
    {
        $independent = !$IPSViewUseTileTimeSettings;
        foreach (['IPSViewRange', 'IPSViewTimeAxisLabelFormat'] as $field) {
            $this->UpdateFormField($field, 'visible', $independent);
        }
        foreach (['IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit'] as $field) {
            $this->UpdateFormField($field, 'visible', $independent && $IPSViewRange === 'custom');
        }
    }

    public function UpdateBarDesignForm(bool $IPSView, bool $RoundedBars, string $BarFillMode): void
    {
        $prefix = $IPSView ? 'IPSView' : '';
        $this->UpdateFormField($prefix . 'BarGradientColor', 'visible', $BarFillMode === 'gradient');
        $this->UpdateFormField($prefix . 'BarCornerRadius', 'visible', $RoundedBars);
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
        foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
            $value = match ($type) {
                'string'  => $this->ReadPropertyString($name),
                'boolean' => $this->ReadPropertyBoolean($name),
                default   => $this->ReadPropertyInteger($name)
            };
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $value);
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function GetBarHistoryData(): string
    {
        return $this->GetBarHistoryDataForOutput(false);
    }

    public function GetBarHistoryDiagnostic(): string
    {
        $error = $this->GetConfigurationError();

        return json_encode([
            'valid'     => $error === null,
            'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderBarHistoryHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderBarHistoryHTMLPage(true);
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

    private function GetBarHistoryDataForOutput(bool $ipsView): string
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
        $axisModel = EChartsUnitAxes::Build($sources);
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        $query = EChartsArchiveQuery::Resolve(
            $this->EffectiveRange($ipsView),
            $this->EffectiveCustomRangeValue($ipsView),
            $this->EffectiveCustomRangeUnit($ipsView),
            $this->ReadPropertyString('DataMode'),
            $this->ReadPropertyInteger('PointBudget'),
            count($sources),
            $this->CurrentTimestamp(),
            date_default_timezone_get()
        );
        if ($query['Mode'] === 'realtime') {
            throw new RuntimeException('Historical bars require archive data.');
        }
        $series = [];
        $truncated = false;
        foreach ($sources as $source) {
            $archive = $this->ReadArchiveSource($source, $query);
            $series[] = [
                'id'                     => 'variable-' . $source['VariableID'],
                'variableID'             => $source['VariableID'],
                'label'                  => $labels[$source['VariableID']],
                'unit'                   => $source['Unit'],
                'decimals'               => $source['Decimals'],
                'axisIndex'              => $axisModel['Indexes'][$source['Unit']],
                'color'                  => $source['Color'],
                'effectiveReducer'       => $archive['EffectiveReducer'],
                'archiveAggregationType' => $archive['ArchiveAggregationType'],
                'points'                 => $archive['Points']
            ];
            $truncated = $truncated || $archive['Truncated'];
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'history',
            'theme'         => $this->EffectiveTheme($ipsView),
            'bar'           => [
                'title'               => $this->ReadPropertyString('Title'),
                'timeAxisLabelFormat' => $this->EffectiveTimeAxisLabelFormat($ipsView),
                'unit'                => $sources[0]['Unit'],
                'decimals'            => $sources[0]['Decimals'],
                'style'               => $this->ReadDesignStyle($ipsView)
            ],
            'axes'          => $axisModel['Axes'],
            'range'         => [
                'key'                 => $this->EffectiveRange($ipsView),
                'startTimestamp'      => $query['StartTimestamp'],
                'endTimestamp'        => $query['EndTimestamp'],
                'dataMode'            => $this->ReadPropertyString('DataMode'),
                'aggregationLevel'    => $query['AggregationLevel'],
                'pointBudget'         => $this->ReadPropertyInteger('PointBudget'),
                'pointLimitPerSeries' => $query['Limit']
            ],
            'series'        => $series,
            'truncated'     => $truncated
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderBarHistoryHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Historical Bar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-history-root', 'echarts-bar-history'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure 1 to 16 valid Historical Bar sources.' => $this->Translate(
                    'Configure 1 to 16 valid Historical Bar sources.'
                ),
                'Connect an active EChartsGateway.'              => $this->Translate('Connect an active EChartsGateway.'),
                'The Historical Bar values could not be loaded.' => $this->Translate(
                    'The Historical Bar values could not be loaded.'
                ),
                'The selected raw range was truncated by the point budget.' => $this->Translate(
                    'The selected raw range was truncated by the point budget.'
                )
            ],
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::CartesianJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_ZOOM_SCRIPT}}'      => EChartsAsset::ZoomJavaScript(),
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
        $this->SynchronizeSourceReference();
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
        $this->SetSummary($this->RangeSummary());
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
        $mode = $this->ReadPropertyString('DataMode');
        if (!$this->IsValidTimeSettings('')
            || !in_array($mode, ['raw', 'auto', ...array_keys(EChartsArchiveQuery::AGGREGATION_LEVELS)], true)
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > 2000
            || !$this->IsValidDesign('')
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The Historical Bar settings are invalid.'
            ];
        }

        if ($this->IsIPSViewHTMLPageEnabled()
            && (!EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
                || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
                || (!$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
                    && !$this->IsValidTimeSettings('IPSView')))
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The IPSView Historical Bar settings are invalid.'
            ];
        }

        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:string,Reducer:string,AxisPosition:string}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Historical Bar sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources)
            || count($sources) < 1 || count($sources) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Configure between 1 and 16 numeric Historical Bar sources.');
        }
        $result = [];
        $seen = [];
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('The Historical Bar source settings are invalid.');
            }
            $variableID = $source['VariableID'] ?? null;
            $label = $source['Label'] ?? '';
            $decimals = $source['Decimals'] ?? 1;
            $color = $source['Color'] ?? -1;
            $reducer = $source['Reducer'] ?? 'auto';
            $unit = $source['Unit'] ?? '';
            $usePresentation = $source['UseVariablePresentation'] ?? true;
            $axisPosition = $source['AxisPosition'] ?? 'auto';
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || isset($seen[$variableID])
                || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || !is_string($unit) || !is_bool($usePresentation)
                || !is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF
                || !is_string($reducer) || !in_array($reducer, self::REDUCERS, true)
                || !is_string($axisPosition) || !in_array($axisPosition, ['auto', 'left', 'right'], true)) {
                throw new InvalidArgumentException('Historical Bar sources must be unique numeric variables with valid settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                $usePresentation
            );
            $seen[$variableID] = true;
            $result[] = [
                'VariableID'   => $variableID,
                'Label'        => trim($label),
                'Unit'         => $presentation['unit'],
                'Decimals'     => $presentation['decimals'],
                'Color'        => $color < 0 ? '' : EChartsAsset::ColorToHex($color),
                'Reducer'      => $reducer,
                'AxisPosition' => $axisPosition
            ];
        }
        EChartsUnitAxes::Build($result);

        return $result;
    }

    /** @param array{VariableID:int,Reducer:string} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        if ($query['Empty']) {
            return [
                'ArchiveAggregationType' => 'none',
                'EffectiveReducer'       => $query['AggregationLevel'] === null
                    ? 'raw'
                    : ($source['Reducer'] === 'auto' ? 'average' : $source['Reducer']),
                'Points'                 => [],
                'Truncated'              => false
            ];
        }

        $payload = [
            'VariableID'     => $source['VariableID'],
            'StartTimestamp' => $query['StartTimestamp'],
            'EndTimestamp'   => $query['EndTimestamp'],
            'Mode'           => $query['Mode'],
            'Reducer'        => $source['Reducer'],
            'Limit'          => $query['Limit']
        ];
        if ($query['AggregationLevel'] !== null) {
            $payload['AggregationLevel'] = $query['AggregationLevel'];
        }
        $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, $payload)
                )),
                $operation
            );
        } catch (Throwable $exception) {
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned an invalid archive response.', 0, $exception);
        }
        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway archive request failed: ' . $errorCode);
        }
        $archive = $response['Payload'];
        if (($archive['VariableID'] ?? null) !== $source['VariableID']
            || !is_string($archive['ArchiveAggregationType'] ?? null)
            || !is_string($archive['EffectiveReducer'] ?? null)
            || !is_array($archive['Points'] ?? null)
            || !is_bool($archive['Truncated'] ?? null)
        ) {
            throw new RuntimeException('The gateway returned invalid Historical Bar data.');
        }
        $points = [];
        $previousTimestamp = 0;
        foreach ($archive['Points'] as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= 0 || $point[0] < $previousTimestamp
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])
            ) {
                throw new RuntimeException('The gateway returned invalid Historical Bar points.');
            }
            $previousTimestamp = $point[0];
            $points[] = [$point[0], (float) $point[1]];
        }
        $archive['Points'] = $points;

        return $archive;
    }

    private function SynchronizeSourceReference(): void
    {
        $previous = json_decode($this->ReadAttributeString('RegisteredSourceVariableIDs'), true);
        $previous = is_array($previous) ? array_values(array_filter($previous, is_int(...))) : [];
        $current = $this->ConfiguredVariableIDs();
        foreach (array_diff($previous, $current) as $variableID) {
            $this->UnregisterReference($variableID);
        }
        foreach (array_diff($current, $previous) as $variableID) {
            if (IPS_VariableExists($variableID)) {
                $this->RegisterReference($variableID);
            }
        }
        $this->WriteAttributeString('RegisteredSourceVariableIDs', json_encode($current, JSON_THROW_ON_ERROR));
    }

    /** @return list<int> */
    private function ConfiguredVariableIDs(): array
    {
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }
        $ids = [];
        foreach ($sources as $source) {
            $id = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($id) && $id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Configure 1 to 16 valid Historical Bar sources.'
            ];
        }
        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }
        try {
            $chart = json_decode($this->GetBarHistoryDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Historical Bar values could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'history',
            'status'        => 'ready',
            'chart'         => $chart,
            'error'         => null
        ];
    }

    private function PublishVisualizationState(): void
    {
        try {
            $this->UpdateVisualizationValue(json_encode(
                $this->BuildVisualizationState(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
            $this->PushEChartsIPSViewState(fn (): array => $this->BuildVisualizationState(true));
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
            || !in_array($this->ReadPropertyString($prefix . 'BarFillMode'), self::BAR_FILL_MODES, true)) {
            return false;
        }
        foreach ([
            'BarWidthPercent'      => [20, 100],
            'BarOpacityPercent'    => [0, 100],
            'BarCornerRadius'      => [0, 24],
            'TitleFontSizePercent' => [50, 200],
            'AxisFontSizePercent'  => [50, 200],
            'ValueFontSizePercent' => [50, 200]
        ] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['BarGradientColor', 'TitleColor', 'AxisColor', 'ValueColor', 'GridColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }

        return true;
    }

    private function IsValidTimeSettings(string $prefix): bool
    {
        return EChartsArchiveQuery::IsValidRange(
            $this->ReadPropertyString($prefix . 'Range'),
            $this->ReadPropertyInteger($prefix . 'CustomRangeValue'),
            $this->ReadPropertyString($prefix . 'CustomRangeUnit')
        ) && in_array(
            $this->ReadPropertyString($prefix . 'TimeAxisLabelFormat'),
            self::TIME_AXIS_LABEL_FORMATS,
            true
        );
    }

    private function EffectiveRange(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyString('IPSViewRange')
            : $this->ReadPropertyString('Range');
    }

    private function EffectiveCustomRangeValue(bool $ipsView): int
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyInteger('IPSViewCustomRangeValue')
            : $this->ReadPropertyInteger('CustomRangeValue');
    }

    private function EffectiveCustomRangeUnit(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyString('IPSViewCustomRangeUnit')
            : $this->ReadPropertyString('CustomRangeUnit');
    }

    private function EffectiveTimeAxisLabelFormat(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyString('IPSViewTimeAxisLabelFormat')
            : $this->ReadPropertyString('TimeAxisLabelFormat');
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('IPSViewEChartsTheme')
            : $this->ReadPropertyString('EChartsTheme');
    }

    /** @return array<string, bool|int|string> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';

        return [
            'showValues'          => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'showGrid'            => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'enableZoom'          => $this->ReadPropertyBoolean($prefix . 'EnableZoom'),
            'roundedBars'         => $this->ReadPropertyBoolean($prefix . 'RoundedBars'),
            'barWidthPercent'     => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'barFillMode'         => $this->ReadPropertyString($prefix . 'BarFillMode'),
            'barGradientColor'    => $this->OptionalDesignColor($prefix . 'BarGradientColor'),
            'barOpacityPercent'   => $this->ReadPropertyInteger($prefix . 'BarOpacityPercent'),
            'barCornerRadius'     => $this->ReadPropertyInteger($prefix . 'BarCornerRadius'),
            'titleFontSizePercent'=> $this->ReadPropertyInteger($prefix . 'TitleFontSizePercent'),
            'axisFontSizePercent' => $this->ReadPropertyInteger($prefix . 'AxisFontSizePercent'),
            'valueFontSizePercent'=> $this->ReadPropertyInteger($prefix . 'ValueFontSizePercent'),
            'titleColor'          => $this->OptionalDesignColor($prefix . 'TitleColor'),
            'axisColor'           => $this->OptionalDesignColor($prefix . 'AxisColor'),
            'valueColor'          => $this->OptionalDesignColor($prefix . 'ValueColor'),
            'gridColor'           => $this->OptionalDesignColor($prefix . 'GridColor')
        ];
    }

    private function OptionalDesignColor(string $property): string
    {
        $color = $this->ReadPropertyInteger($property);

        return $color < 0 ? '' : EChartsAsset::ColorToHex($color);
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-bar-history-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string, mixed>> $elements @return list<array<string, mixed>> */
    private function BuildIPSViewTimeSettings(array $elements): array
    {
        $rangeItems = null;
        $designRows = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'RowLayout'
                && in_array('Range', array_column($element['items'] ?? [], 'name'), true)) {
                $rangeItems = $element['items'];
            }
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $designRows = $element['items'] ?? null;
            }
        }
        if (!is_array($rangeItems) || !is_array($designRows)) {
            throw new RuntimeException('The Historical Bar form sections are missing.');
        }

        $independentTime = !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings');
        $customRange = $independentTime && $this->ReadPropertyString('IPSViewRange') === 'custom';
        $timeAction = 'ECBH_UpdateIPSViewTimeRangeForm($id, $IPSViewUseTileTimeSettings, $IPSViewRange);';
        $ipsViewTimeItems = [];
        foreach ($rangeItems as $item) {
            if (!in_array($item['name'] ?? null, ['Range', 'CustomRangeValue', 'CustomRangeUnit'], true)) {
                continue;
            }
            $item['name'] = 'IPSView' . $item['name'];
            $item['visible'] = $item['name'] === 'IPSViewRange' ? $independentTime : $customRange;
            if ($item['name'] === 'IPSViewRange') {
                $item['caption'] = 'IPSView time range';
                $item['onChange'] = $timeAction;
            }
            $ipsViewTimeItems[] = $item;
        }
        foreach ($designRows as $row) {
            foreach ($row['items'] ?? [] as $item) {
                if (($item['name'] ?? null) !== 'TimeAxisLabelFormat') {
                    continue;
                }
                $item['name'] = 'IPSViewTimeAxisLabelFormat';
                $item['visible'] = $independentTime;
                $ipsViewTimeItems[] = $item;
            }
        }
        if (count($ipsViewTimeItems) !== 4) {
            throw new RuntimeException('The Historical Bar time settings are incomplete.');
        }

        return [
            [
                'type'     => 'CheckBox',
                'name'     => 'IPSViewUseTileTimeSettings',
                'caption'  => 'Use Tile time settings',
                'onChange' => $timeAction
            ],
            [
                'type'    => 'Label',
                'caption' => 'Disable this option to use a separate IPSView time range and time-axis label format.'
            ],
            ['type' => 'RowLayout', 'items' => $ipsViewTimeItems]
        ];
    }

    /** @param list<array<string, mixed>> $elements @return array<string, mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $designRows = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $designRows = $element['items'] ?? null;
                break;
            }
        }
        if (!is_array($designRows)) {
            throw new RuntimeException('The Historical Bar Tile designer section is missing.');
        }

        $ipsViewDesignRows = [];
        $designFieldCount = 0;
        foreach ($designRows as $row) {
            if (($row['type'] ?? null) !== 'RowLayout' || !is_array($row['items'] ?? null)) {
                continue;
            }
            $items = [];
            foreach ($row['items'] as $item) {
                $name = $item['name'] ?? null;
                if (is_string($name) && array_key_exists($name, self::DESIGN_PROPERTY_TYPES)) {
                    $item['name'] = 'IPSView' . $name;
                    if (in_array($name, ['RoundedBars', 'BarFillMode'], true)) {
                        $item['onChange'] = 'ECBH_UpdateBarDesignForm($id, true, '
                            . '$IPSViewRoundedBars, $IPSViewBarFillMode);';
                    }
                    if ($name === 'BarGradientColor') {
                        $item['visible'] = $this->ReadPropertyString('IPSViewBarFillMode') === 'gradient';
                    }
                    if ($name === 'BarCornerRadius') {
                        $item['visible'] = $this->ReadPropertyBoolean('IPSViewRoundedBars');
                    }
                    $items[] = $item;
                    $designFieldCount++;
                }
            }
            if ($items !== []) {
                $ipsViewDesignRows[] = ['type' => 'RowLayout', 'items' => $items];
            }
        }
        if ($designFieldCount !== count(self::DESIGN_PROPERTY_TYPES)) {
            throw new RuntimeException('The Historical Bar designer fields are missing.');
        }

        return [
            'type'     => 'ExpansionPanel',
            'caption'  => 'IPSView design',
            'expanded' => false,
            'width'    => '760px',
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems(
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                ),
                EChartsIPSViewBackground::FormRow(),
                [
                    'type'    => 'CheckBox',
                    'name'    => 'IPSViewUseTileDesign',
                    'caption' => 'Use Tile design'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECBH_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'
                ],
                [
                    'type'     => 'ExpansionPanel',
                    'caption'  => 'Independent IPSView designer',
                    'expanded' => false,
                    'items'    => $ipsViewDesignRows
                ]
            ]
        ];
    }

    private function RangeSummary(): string
    {
        if ($this->ReadPropertyString('Range') !== 'custom') {
            return $this->ReadPropertyString('Range');
        }

        return $this->ReadPropertyInteger('CustomRangeValue')
            . match ($this->ReadPropertyString('CustomRangeUnit')) {
                'minute' => 'min', 'hour' => 'h', 'day' => 'd', 'week' => 'w', default => ''
            };
    }
}
