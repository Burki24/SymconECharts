<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\SVGPreviewHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsTimeSeriesPreview;
use SymconECharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/TimeSeriesPreview.php';

class EChartsTimeSeries extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const MINIMUM_SOURCE_COUNT = 1;
    private const MAXIMUM_SOURCE_COUNT = 8;
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const RANGE_SECONDS = [
        '1h'  => 3600,
        '6h'  => 21600,
        '24h' => 86400,
        '7d'  => 604800,
        '30d' => 2592000
    ];
    private const AGGREGATION_LEVELS = [
        'minute'          => 6,
        'five-minutes'    => 5,
        'fifteen-minutes' => 8,
        'hour'            => 0,
        'day'             => 1
    ];
    private const AGGREGATION_SECONDS = [6 => 60, 5 => 300, 8 => 900, 0 => 3600, 1 => 86400];
    private const REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];
    private const STYLES = ['line', 'area'];
    private const AXIS_POSITIONS = ['auto', 'left', 'right'];
    private const LEGEND_POSITIONS = ['top', 'bottom', 'hidden'];
    private const DESIGN_FORM_FIELDS = [
        'Title', 'Sources', 'EChartsTheme', 'LegendPosition', 'EnableZoom',
        'LineWidthPercent', 'SmoothLines', 'ShowSymbols', 'SymbolSizePercent',
        'AreaOpacityPercent', 'ShowGrid', 'ShowXAxis', 'ShowYAxis'
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECTS_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Range', '24h');
        $this->RegisterPropertyString('DataMode', 'auto');
        $this->RegisterPropertyInteger('PointBudget', 2000);
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyString('LegendPosition', 'top');
        $this->RegisterPropertyBoolean('EnableZoom', true);
        $this->RegisterPropertyInteger('LineWidthPercent', 100);
        $this->RegisterPropertyBoolean('SmoothLines', false);
        $this->RegisterPropertyBoolean('ShowSymbols', false);
        $this->RegisterPropertyInteger('SymbolSizePercent', 100);
        $this->RegisterPropertyInteger('AreaOpacityPercent', 22);
        $this->RegisterPropertyBoolean('ShowGrid', true);
        $this->RegisterPropertyBoolean('ShowXAxis', true);
        $this->RegisterPropertyBoolean('ShowYAxis', true);
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
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
            $form['elements'] = $this->AttachTimeSeriesPreviewActions($form['elements']);
        }
        $form = SVGPreviewHelper::withImage(
            $form,
            'TimeSeriesPreview',
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries(),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('EChartsTheme'),
                $this->ReadTimeSeriesDesign()
            )
        );

        return $this->EncodeConfigurationForm($form);
    }

    /** Refreshes the SVG preview from the values currently edited in the configuration form. */
    public function UpdateTimeSeriesPreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        if (!is_array($values)) {
            $values = [];
        }

        $this->UpdateFormField('TimeSeriesPreview', 'image', SVGPreviewHelper::dataUri(
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries($values['Sources'] ?? null),
                (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
                (string) ($values['EChartsTheme'] ?? $this->ReadPropertyString('EChartsTheme')),
                $this->TimeSeriesDesignFromFormValues($values)
            )
        ));
    }

    public function GetTimeSeriesData(): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->SetStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $sources = $this->GetValidatedSources();
        $query = $this->ResolveArchiveQuery(count($sources));
        $axisModel = $this->BuildAxisModel($sources);
        $axes = $axisModel['Axes'];
        $axisIndexes = $axisModel['Indexes'];
        $series = [];
        $truncated = false;
        foreach ($sources as $source) {
            $archive = $query['Mode'] === 'realtime'
                ? $this->ReadRealtimeSource($source['VariableID'], $query['EndTimestamp'])
                : $this->ReadArchiveSource($source, $query);
            $truncated = $truncated || $archive['Truncated'];
            $series[] = [
                'id'                     => 'variable-' . $source['VariableID'],
                'variableID'             => $source['VariableID'],
                'label'                  => $source['Label'] !== ''
                    ? $source['Label']
                    : IPS_GetName($source['VariableID']),
                'unit'                   => $source['Unit'],
                'decimals'               => $source['Decimals'],
                'color'                  => $source['Color'],
                'style'                  => $source['Style'],
                'axisIndex'              => $axisIndexes[$source['Unit']],
                'effectiveReducer'       => $archive['EffectiveReducer'],
                'archiveAggregationType' => $archive['ArchiveAggregationType'],
                'points'                 => $archive['Points'],
                'truncated'              => $archive['Truncated']
            ];
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'time-series',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'chart'         => [
                'title'      => $this->ReadPropertyString('Title'),
                'enableZoom' => $this->ReadPropertyBoolean('EnableZoom'),
                'design'     => $this->ReadTimeSeriesDesign()
            ],
            'range'         => [
                'key'                 => $this->ReadPropertyString('Range'),
                'durationSeconds'     => $query['DurationSeconds'],
                'startTimestamp'      => $query['StartTimestamp'],
                'endTimestamp'        => $query['EndTimestamp'],
                'dataMode'            => $this->ReadPropertyString('DataMode'),
                'aggregationLevel'    => $query['AggregationLevel'],
                'pointBudget'         => $this->ReadPropertyInteger('PointBudget'),
                'pointLimitPerSeries' => $query['Limit']
            ],
            'axes'          => $axes,
            'series'        => $series,
            'truncated'     => $truncated
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetTimeSeriesDiagnostic(): string
    {
        try {
            return $this->GetTimeSeriesData();
        } catch (Throwable $exception) {
            $this->SendDebug('GetTimeSeriesDiagnostic', $exception::class, 0);
            $error = $this->GetConfigurationError();
            $status = $error['Status']
                ?? ($this->HasActiveParent() ? self::STATUS_GATEWAY_FAILED : self::STATUS_PARENT_MISSING);

            return json_encode([
                'success' => false,
                'status'  => $status,
                'error'   => $this->Translate($exception->getMessage())
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    public function GetVisualizationTile(): string
    {
        $hiddenTileTitle = false;
        if (function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage(false, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Time Series',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-timeseries-root', 'echarts-timeseries'),
            'state'              => $this->BuildVisualizationState(),
            'translations'       => [
                'The selected raw range was truncated by the point budget.' => $this->Translate(
                    'The selected raw range was truncated by the point budget.'
                ),
                'The time series could not be loaded.' => $this->Translate(
                    'The time series could not be loaded.'
                )
            ],
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'       => EChartsAsset::TimeSeriesJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}' => EChartsAsset::ThemeJavaScript()
            ]
        ]);
    }

    public function ReceiveData(string $JSONString): string
    {
        try {
            $message = $this->DecodeDataFlowMessage($JSONString, self::DATA_ID_FROM_PARENT);
            EChartsDataProtocol::DecodeRequest($message);
        } catch (Throwable $exception) {
            $this->SendDebug('ReceiveData', $exception::class, 0);
        }

        return '';
    }

    /** @param array<int, mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
            $this->Initialize();
            $this->PublishVisualizationState();
            return;
        }
        if ($Message !== VM_UPDATE || !in_array($SenderID, $this->ConfiguredVariableIDs(), true)) {
            return;
        }
        if (!in_array($this->ReadPropertyString('DataMode'), ['raw', 'realtime'], true)) {
            return;
        }

        $value = GetValue($SenderID);
        if (!is_int($value) && !is_float($value)) {
            return;
        }
        $variable = IPS_GetVariable($SenderID);
        $timestamp = $variable['VariableUpdated'] ?? $TimeStamp;
        if (!is_int($timestamp) || $timestamp <= 0) {
            $timestamp = $TimeStamp;
        }
        $this->UpdateVisualizationValue(json_encode([
            'messageType' => 'append',
            'variableID'  => $SenderID,
            'timestamp'   => $timestamp,
            'value'       => (float) $value
        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    public function RefreshArchive(): void
    {
        $this->PublishVisualizationState();
        $this->ScheduleArchiveRefresh();
    }

    protected function CurrentTimestamp(): int
    {
        return time();
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

        $sources = $this->GetValidatedSources();
        $this->SetSummary(count($sources) . ' sources · ' . $this->ReadPropertyString('Range'));
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        $this->ScheduleArchiveRefresh();
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (UnexpectedValueException $exception) {
            return [
                'Status'  => in_array($exception->getCode(), [201, 202], true)
                    ? $exception->getCode()
                    : self::STATUS_CONFIGURATION_INVALID,
                'Message' => $exception->getMessage()
            ];
        }

        if (!array_key_exists($this->ReadPropertyString('Range'), self::RANGE_SECONDS)
            || !in_array(
                $this->ReadPropertyString('DataMode'),
                ['realtime', 'raw', 'auto', ...array_keys(self::AGGREGATION_LEVELS)],
                true
            )
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > 8000
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))
            || !in_array($this->ReadPropertyString('LegendPosition'), self::LEGEND_POSITIONS, true)
            || $this->ReadPropertyInteger('LineWidthPercent') < 50
            || $this->ReadPropertyInteger('LineWidthPercent') > 200
            || $this->ReadPropertyInteger('SymbolSizePercent') < 50
            || $this->ReadPropertyInteger('SymbolSizePercent') > 200
            || $this->ReadPropertyInteger('AreaOpacityPercent') < 0
            || $this->ReadPropertyInteger('AreaOpacityPercent') > 100
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The time series settings are invalid.'
            ];
        }

        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:string,Style:string,Reducer:string,AxisPosition:string}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('Time series sources must contain valid JSON.', 201, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources)
            || count($sources) < self::MINIMUM_SOURCE_COUNT
            || count($sources) > self::MAXIMUM_SOURCE_COUNT
        ) {
            throw new UnexpectedValueException('Configure between 1 and 8 time series sources.', 201);
        }

        $result = [];
        $variableIDs = [];
        foreach ($sources as $index => $source) {
            if (!is_array($source)) {
                throw new UnexpectedValueException('Time series source ' . ($index + 1) . ' is invalid.', 201);
            }
            $variableID = $source['VariableID'] ?? null;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || isset($variableIDs[$variableID])
                || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)
            ) {
                throw new UnexpectedValueException('Time series sources must be unique numeric variables.', 201);
            }
            $label = $source['Label'] ?? '';
            $unit = $source['Unit'] ?? '';
            $decimals = $source['Decimals'] ?? null;
            $color = $source['Color'] ?? '';
            $style = $source['Style'] ?? 'line';
            $reducer = $source['Reducer'] ?? 'auto';
            $axisPosition = $source['AxisPosition'] ?? 'auto';
            $usePresentation = $source['UseVariablePresentation'] ?? true;
            if (!is_string($label) || !is_string($unit) || !is_int($decimals)
                || !is_string($color) || !is_string($style) || !is_string($reducer) || !is_string($axisPosition)
                || !is_bool($usePresentation) || $decimals < 0 || $decimals > 6
                || ($color !== '' && preg_match('/^#[0-9A-F]{6}$/i', $color) !== 1)
                || !in_array($style, self::STYLES, true) || !in_array($reducer, self::REDUCERS, true)
                || !in_array($axisPosition, self::AXIS_POSITIONS, true)
            ) {
                throw new UnexpectedValueException('Time series source settings are invalid.', 202);
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                $usePresentation
            );
            $effectiveUnit = $presentation['unit'];
            $variableIDs[$variableID] = true;
            $result[] = [
                'VariableID'   => $variableID,
                'Label'        => trim($label),
                'Unit'         => $effectiveUnit,
                'Decimals'     => $presentation['decimals'],
                'Color'        => strtoupper($color),
                'Style'        => $style,
                'Reducer'      => $reducer,
                'AxisPosition' => $axisPosition
            ];
        }

        $this->BuildAxisModel($result);

        return $result;
    }

    /**
     * @param list<array{Unit:string,AxisPosition:string}> $sources
     * @return array{Axes:list<array{unit:string,position:string,positionIndex:int}>,Indexes:array<string,int>}
     */
    private function BuildAxisModel(array $sources): array
    {
        $groups = [];
        foreach ($sources as $source) {
            $unit = $source['Unit'];
            $requestedPosition = $source['AxisPosition'];
            if (!array_key_exists($unit, $groups)) {
                $groups[$unit] = ['unit' => $unit, 'position' => 'auto'];
            }
            $groupPosition = $groups[$unit]['position'];
            if ($requestedPosition !== 'auto' && $groupPosition !== 'auto' && $groupPosition !== $requestedPosition) {
                throw new UnexpectedValueException(
                    'Sources with the same unit must use the same axis side.',
                    self::STATUS_CONFIGURATION_INVALID
                );
            }
            if ($requestedPosition !== 'auto') {
                $groups[$unit]['position'] = $requestedPosition;
            }
        }

        $counts = ['left' => 0, 'right' => 0];
        foreach ($groups as $group) {
            if ($group['position'] !== 'auto') {
                ++$counts[$group['position']];
            }
        }
        foreach ($groups as &$group) {
            if ($group['position'] === 'auto') {
                $group['position'] = $counts['left'] <= $counts['right'] ? 'left' : 'right';
                ++$counts[$group['position']];
            }
        }
        unset($group);

        $axes = [];
        $indexes = [];
        $positionIndexes = ['left' => 0, 'right' => 0];
        foreach ($groups as $unit => $group) {
            $position = $group['position'];
            $indexes[$unit] = count($axes);
            $axes[] = [
                'unit'          => $group['unit'],
                'position'      => $position,
                'positionIndex' => $positionIndexes[$position]++
            ];
        }

        return ['Axes' => $axes, 'Indexes' => $indexes];
    }

    /** @return array{DurationSeconds:int,StartTimestamp:int,EndTimestamp:int,Mode:string,AggregationLevel:int|null,Limit:int} */
    private function ResolveArchiveQuery(int $sourceCount): array
    {
        $duration = self::RANGE_SECONDS[$this->ReadPropertyString('Range')];
        $mode = $this->ReadPropertyString('DataMode');
        $limit = max(1, min(2000, intdiv($this->ReadPropertyInteger('PointBudget'), $sourceCount)));
        $level = null;
        if (!in_array($mode, ['raw', 'realtime'], true)) {
            if ($mode === 'auto') {
                foreach (self::AGGREGATION_SECONDS as $candidate => $seconds) {
                    $level = $candidate;
                    if ((int) ceil($duration / $seconds) <= $limit) {
                        break;
                    }
                }
            } else {
                $level = self::AGGREGATION_LEVELS[$mode];
            }
        }

        $end = $this->CurrentTimestamp();
        if ($level !== null) {
            $end = $this->AggregationWindowStart($level, $end) - 1;
        }

        return [
            'DurationSeconds'  => $duration,
            'StartTimestamp'   => max(1, $end - $duration + ($level === null ? 0 : 1)),
            'EndTimestamp'     => $end,
            'Mode'             => $mode === 'realtime' ? 'realtime' : ($level === null ? 'raw' : 'aggregated'),
            'AggregationLevel' => $level,
            'Limit'            => $limit
        ];
    }

    /** @param array{VariableID:int,Reducer:string} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
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
            $this->SendDebug('ReadArchiveSource', $exception::class, 0);
            throw new RuntimeException('The gateway returned an invalid archive response.');
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
            throw new RuntimeException('The gateway returned invalid time series data.');
        }

        $points = [];
        $previousTimestamp = 0;
        foreach ($archive['Points'] as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= 0 || $point[0] < $previousTimestamp
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])
            ) {
                throw new RuntimeException('The gateway returned invalid time series points.');
            }
            $previousTimestamp = $point[0];
            $points[] = [$point[0], (float) $point[1]];
        }
        $archive['Points'] = $points;

        return $archive;
    }

    /** @return array{ArchiveAggregationType:string,EffectiveReducer:string,Points:list<array{int,float}>,Truncated:bool} */
    private function ReadRealtimeSource(int $variableID, int $observationTimestamp): array
    {
        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, ['VariableID' => $variableID])
                )),
                $operation
            );
        } catch (Throwable $exception) {
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            $this->SendDebug('ReadRealtimeSource', $exception::class, 0);
            throw new RuntimeException('The gateway returned an invalid current-value response.');
        }
        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway current-value request failed: ' . $errorCode);
        }

        $payload = $response['Payload'];
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if (($payload['VariableID'] ?? null) !== $variableID
            || (!is_int($value) && !is_float($value)) || !is_finite((float) $value)
            || !is_int($timestamp) || $timestamp <= 0
        ) {
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        return [
            'ArchiveAggregationType' => 'none',
            'EffectiveReducer'       => 'realtime',
            'Points'                 => [[$observationTimestamp, (float) $value]],
            'Truncated'              => false
        ];
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'time-series',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Configure 1 to 8 unique numeric sources.'
            ];
        }
        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'time-series',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }
        try {
            $chart = json_decode($this->GetTimeSeriesData(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return [
                'schemaVersion' => 1,
                'family'        => 'time-series',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The time series could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'time-series',
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
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
    }

    private function ScheduleArchiveRefresh(): void
    {
        $mode = $this->ReadPropertyString('DataMode');
        if (in_array($mode, ['raw', 'realtime'], true)
            || !in_array($mode, ['auto', ...array_keys(self::AGGREGATION_LEVELS)], true)
        ) {
            $this->SetTimerInterval('ArchiveRefresh', 0);
            return;
        }
        try {
            $level = $this->ResolveArchiveQuery(max(1, count($this->GetValidatedSources())))['AggregationLevel'];
        } catch (Throwable) {
            $this->SetTimerInterval('ArchiveRefresh', 0);
            return;
        }
        $now = $this->CurrentTimestamp();
        $nextBoundary = $this->NextAggregationWindowStart($level, $now) + 2;
        $this->SetTimerInterval('ArchiveRefresh', max(1000, ($nextBoundary - $now) * 1000));
    }

    private function AggregationWindowStart(int $level, int $timestamp): int
    {
        $local = (new DateTimeImmutable('@' . $timestamp))->setTimezone(
            new DateTimeZone(date_default_timezone_get())
        );
        if ($level === 1) {
            return $local->setTime(0, 0)->getTimestamp();
        }
        if ($level === 0) {
            return $local->setTime((int) $local->format('G'), 0)->getTimestamp();
        }

        $minutes = intdiv(self::AGGREGATION_SECONDS[$level] ?? 60, 60);
        $minute = intdiv((int) $local->format('i'), $minutes) * $minutes;
        return $local->setTime((int) $local->format('G'), $minute)->getTimestamp();
    }

    private function NextAggregationWindowStart(int $level, int $timestamp): int
    {
        $start = (new DateTimeImmutable('@' . $this->AggregationWindowStart($level, $timestamp)))->setTimezone(
            new DateTimeZone(date_default_timezone_get())
        );
        if ($level === 1) {
            return $start->modify('+1 day')->getTimestamp();
        }

        return $start->modify('+' . (self::AGGREGATION_SECONDS[$level] ?? 60) . ' seconds')->getTimestamp();
    }

    private function SynchronizeSourceReferences(): void
    {
        $previous = $this->DecodeVariableIDList($this->ReadAttributeString('RegisteredSourceVariableIDs'));
        $current = array_values(array_filter(
            $this->ConfiguredVariableIDs(),
            static fn (int $variableID): bool => IPS_VariableExists($variableID)
        ));
        foreach (array_diff($previous, $current) as $variableID) {
            $this->UnregisterReference($variableID);
            $this->UnregisterMessage($variableID, VM_UPDATE);
        }
        foreach (array_diff($current, $previous) as $variableID) {
            $this->RegisterReference($variableID);
        }
        foreach ($current as $variableID) {
            $this->RegisterMessage($variableID, VM_UPDATE);
        }
        $this->WriteAttributeString('RegisteredSourceVariableIDs', json_encode($current, JSON_THROW_ON_ERROR));
    }

    /** @return array{legendPosition:string,lineWidthPercent:int,smoothLines:bool,showSymbols:bool,symbolSizePercent:int,areaOpacityPercent:int,showGrid:bool,showXAxis:bool,showYAxis:bool} */
    private function ReadTimeSeriesDesign(): array
    {
        return [
            'legendPosition'     => $this->ReadPropertyString('LegendPosition'),
            'lineWidthPercent'   => $this->ReadPropertyInteger('LineWidthPercent'),
            'smoothLines'        => $this->ReadPropertyBoolean('SmoothLines'),
            'showSymbols'        => $this->ReadPropertyBoolean('ShowSymbols'),
            'symbolSizePercent'  => $this->ReadPropertyInteger('SymbolSizePercent'),
            'areaOpacityPercent' => $this->ReadPropertyInteger('AreaOpacityPercent'),
            'showGrid'           => $this->ReadPropertyBoolean('ShowGrid'),
            'showXAxis'          => $this->ReadPropertyBoolean('ShowXAxis'),
            'showYAxis'          => $this->ReadPropertyBoolean('ShowYAxis')
        ];
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function TimeSeriesDesignFromFormValues(array $values): array
    {
        $design = $this->ReadTimeSeriesDesign();
        foreach ([
            'LegendPosition'     => 'legendPosition',
            'LineWidthPercent'   => 'lineWidthPercent',
            'SmoothLines'        => 'smoothLines',
            'ShowSymbols'        => 'showSymbols',
            'SymbolSizePercent'  => 'symbolSizePercent',
            'AreaOpacityPercent' => 'areaOpacityPercent',
            'ShowGrid'           => 'showGrid',
            'ShowXAxis'          => 'showXAxis',
            'ShowYAxis'          => 'showYAxis'
        ] as $property => $key) {
            if (array_key_exists($property, $values)) {
                $design[$key] = $values[$property];
            }
        }

        return $design;
    }

    /** @return list<array{label:string,color:string,style:string}> */
    private function PreviewSeries(mixed $formSources = null): array
    {
        $sources = $formSources;
        if ($sources === null) {
            $sources = $this->ReadPropertyString('Sources');
        }
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
        }
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }

        $result = [];
        foreach (array_slice($sources, 0, 4) as $index => $source) {
            if (!is_array($source)) {
                continue;
            }
            $variableID = $source['VariableID'] ?? 0;
            $label = trim(is_string($source['Label'] ?? null) ? $source['Label'] : '');
            if ($label === '' && is_int($variableID) && $variableID > 0 && IPS_VariableExists($variableID)) {
                $label = IPS_GetName($variableID);
            }
            $color = is_string($source['Color'] ?? null) ? $source['Color'] : '';
            $style = is_string($source['Style'] ?? null) && in_array($source['Style'], self::STYLES, true)
                ? $source['Style']
                : 'line';
            $result[] = [
                'label' => $label !== '' ? $label : 'Series ' . ($index + 1),
                'color' => preg_match('/^#[0-9A-F]{6}$/i', $color) === 1 ? strtoupper($color) : '',
                'style' => $style
            ];
        }

        return $result;
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function AttachTimeSeriesPreviewActions(array $items): array
    {
        $action = $this->TimeSeriesPreviewFormAction();
        foreach ($items as &$item) {
            if (isset($item['name']) && in_array($item['name'], self::DESIGN_FORM_FIELDS, true)) {
                $item['onChange'] = $action;
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->AttachTimeSeriesPreviewActions($item['items']);
            }
        }
        unset($item);

        return $items;
    }

    private function TimeSeriesPreviewFormAction(): string
    {
        $pairs = array_map(
            static fn (string $name): string => "'" . $name . "' => $" . $name,
            self::DESIGN_FORM_FIELDS
        );

        return 'ECTS_UpdateTimeSeriesPreviewFromForm($id, json_encode([' . implode(', ', $pairs) . ']));';
    }

    /** @return list<int> */
    private function ConfiguredVariableIDs(): array
    {
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }
        $result = [];
        foreach ($sources as $source) {
            $variableID = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $result, true)) {
                $result[] = $variableID;
            }
        }
        sort($result);
        return $result;
    }

    /** @return list<int> */
    private function DecodeVariableIDList(string $json): array
    {
        $values = json_decode($json, true);
        if (!is_array($values) || !array_is_list($values)) {
            return [];
        }
        $result = array_values(array_unique(array_filter($values, static fn (mixed $value): bool => is_int($value) && $value > 0)));
        sort($result);
        return $result;
    }
}
