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
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarHistory extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];
    private const TIME_AXIS_LABEL_FORMATS = ['auto', 'time', 'date', 'date-time'];

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
        $this->RegisterPropertyBoolean('RoundedBars', false);
        $this->RegisterPropertyInteger('BarWidthPercent', 70);
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
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['CustomRangeValue', 'CustomRangeUnit'],
                $this->ReadPropertyString('Range') === 'custom'
            );
        }

        return $this->EncodeConfigurationForm($form);
    }

    public function UpdateTimeRangeForm(string $Range): void
    {
        $custom = $Range === 'custom';
        $this->UpdateFormField('CustomRangeValue', 'visible', $custom);
        $this->UpdateFormField('CustomRangeUnit', 'visible', $custom);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function GetBarHistoryData(): string
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

        $source = $this->GetValidatedSource();
        $query = EChartsArchiveQuery::Resolve(
            $this->ReadPropertyString('Range'),
            $this->ReadPropertyInteger('CustomRangeValue'),
            $this->ReadPropertyString('CustomRangeUnit'),
            $this->ReadPropertyString('DataMode'),
            $this->ReadPropertyInteger('PointBudget'),
            1,
            $this->CurrentTimestamp(),
            date_default_timezone_get()
        );
        if ($query['Mode'] === 'realtime') {
            throw new RuntimeException('Historical bars require archive data.');
        }
        $archive = $this->ReadArchiveSource($source, $query);
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'history',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'bar'           => [
                'title'               => $this->ReadPropertyString('Title'),
                'timeAxisLabelFormat' => $this->ReadPropertyString('TimeAxisLabelFormat'),
                'unit'                => $source['Unit'],
                'decimals'            => $source['Decimals'],
                'style'               => [
                    'showValues'      => $this->ReadPropertyBoolean('ShowValues'),
                    'showGrid'        => $this->ReadPropertyBoolean('ShowGrid'),
                    'roundedBars'     => $this->ReadPropertyBoolean('RoundedBars'),
                    'barWidthPercent' => $this->ReadPropertyInteger('BarWidthPercent')
                ]
            ],
            'range'         => [
                'key'                 => $this->ReadPropertyString('Range'),
                'startTimestamp'      => $query['StartTimestamp'],
                'endTimestamp'        => $query['EndTimestamp'],
                'dataMode'            => $this->ReadPropertyString('DataMode'),
                'aggregationLevel'    => $query['AggregationLevel'],
                'pointBudget'         => $this->ReadPropertyInteger('PointBudget'),
                'pointLimitPerSeries' => $query['Limit']
            ],
            'series'        => [[
                'id'                     => 'variable-' . $source['VariableID'],
                'variableID'             => $source['VariableID'],
                'label'                  => $source['Label'] !== '' ? $source['Label'] : IPS_GetName($source['VariableID']),
                'color'                  => $source['Color'],
                'effectiveReducer'       => $archive['EffectiveReducer'],
                'archiveAggregationType' => $archive['ArchiveAggregationType'],
                'points'                 => $archive['Points']
            ]],
            'truncated'     => $archive['Truncated']
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
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
        $hiddenTileTitle = false;
        if (function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage(false, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Historical Bar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-history-root', 'echarts-bar-history'),
            'state'              => $this->BuildVisualizationState(),
            'translations'       => [
                'Configure one valid Historical Bar source.' => $this->Translate(
                    'Configure one valid Historical Bar source.'
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
                'tileHeaderVisible' => !$hiddenTileTitle
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'       => EChartsAsset::CartesianJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}' => EChartsAsset::ThemeJavaScript()
            ]
        ]);
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
        }
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
            $this->GetValidatedSource();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        $mode = $this->ReadPropertyString('DataMode');
        if (!EChartsArchiveQuery::IsValidRange(
            $this->ReadPropertyString('Range'),
            $this->ReadPropertyInteger('CustomRangeValue'),
            $this->ReadPropertyString('CustomRangeUnit')
        )
            || !in_array($mode, ['raw', 'auto', ...array_keys(EChartsArchiveQuery::AGGREGATION_LEVELS)], true)
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > 2000
            || !in_array($this->ReadPropertyString('TimeAxisLabelFormat'), self::TIME_AXIS_LABEL_FORMATS, true)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))
            || $this->ReadPropertyInteger('BarWidthPercent') < 20
            || $this->ReadPropertyInteger('BarWidthPercent') > 100
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The Historical Bar settings are invalid.'
            ];
        }

        return null;
    }

    /** @return array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:string,Reducer:string} */
    private function GetValidatedSource(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Historical Bar sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) !== 1 || !is_array($sources[0])) {
            throw new InvalidArgumentException('Configure exactly one numeric Historical Bar source.');
        }
        $source = $sources[0];
        $variableID = $source['VariableID'] ?? null;
        $label = $source['Label'] ?? '';
        $decimals = $source['Decimals'] ?? 1;
        $color = $source['Color'] ?? -1;
        $reducer = $source['Reducer'] ?? 'auto';
        if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
            || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)
            || !is_string($label) || !is_int($decimals) || $decimals < 0 || $decimals > 6
            || !is_int($color) || $color < -1 || $color > 0xFFFFFF
            || !is_string($reducer) || !in_array($reducer, self::REDUCERS, true)
        ) {
            throw new InvalidArgumentException('The Historical Bar source settings are invalid.');
        }
        $presentation = EChartsVariablePresentation::Resolve(
            $variableID,
            0.0,
            100.0,
            is_string($source['Unit'] ?? null) ? $source['Unit'] : '',
            $decimals,
            (bool) ($source['UseVariablePresentation'] ?? true)
        );

        return [
            'VariableID' => $variableID,
            'Label'      => trim($label),
            'Unit'       => $presentation['unit'],
            'Decimals'   => $presentation['decimals'],
            'Color'      => $color < 0 ? '' : EChartsAsset::ColorToHex($color),
            'Reducer'    => $reducer
        ];
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
    private function BuildVisualizationState(): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Configure one valid Historical Bar source.'
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
            $chart = json_decode($this->GetBarHistoryData(), true, 512, JSON_THROW_ON_ERROR);
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
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
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
