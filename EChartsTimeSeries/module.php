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
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTimeSeriesDesign;
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
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTimeSeriesDesign.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/TimeSeriesPreview.php';

class EChartsTimeSeries extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewTransport;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const MINIMUM_SOURCE_COUNT = 1;
    private const MAXIMUM_SOURCE_COUNT = 8;
    private const MAXIMUM_ANNOTATION_COUNT = 32;
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewTimeSeries';
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
    private const CUSTOM_RANGE_UNITS = [
        'minute' => 60,
        'hour'   => 3600,
        'day'    => 86400,
        'week'   => 604800
    ];
    private const TIME_AXIS_LABEL_FORMATS = ['auto', 'time', 'date', 'date-time'];
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
        'Title', 'Sources', 'Annotations', 'Range', 'CustomRangeValue', 'CustomRangeUnit',
        'TimeAxisLabelFormat', 'EChartsTheme', 'LegendPosition', 'EnableZoom',
        'LineWidthPercent', 'SmoothLines', 'ShowSymbols', 'SymbolSizePercent',
        'AreaOpacityPercent', 'ShowGrid', 'ShowXAxis', 'ShowYAxis',
        'IPSViewUseTileDesign', 'IPSViewEChartsTheme', 'IPSViewLegendPosition', 'IPSViewEnableZoom',
        'IPSViewLineWidthPercent', 'IPSViewSmoothLines', 'IPSViewShowSymbols', 'IPSViewSymbolSizePercent',
        'IPSViewAreaOpacityPercent', 'IPSViewShowGrid', 'IPSViewShowXAxis', 'IPSViewShowYAxis',
        'IPSViewUseTileTimeSettings', 'IPSViewRange', 'IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit',
        'IPSViewTimeAxisLabelFormat',
        'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'
    ];
    private const DESIGN_PROPERTY_TYPES = [
        'LegendPosition'     => 'string',
        'EnableZoom'         => 'boolean',
        'LineWidthPercent'   => 'integer',
        'SmoothLines'        => 'boolean',
        'ShowSymbols'        => 'boolean',
        'SymbolSizePercent'  => 'integer',
        'AreaOpacityPercent' => 'integer',
        'ShowGrid'           => 'boolean',
        'ShowXAxis'          => 'boolean',
        'ShowYAxis'          => 'boolean'
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECTS_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Annotations', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Range', '24h');
        $this->RegisterPropertyInteger('CustomRangeValue', 7);
        $this->RegisterPropertyString('CustomRangeUnit', 'day');
        $this->RegisterPropertyString('TimeAxisLabelFormat', 'auto');
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
        $this->RegisterPropertyString('IPSViewEChartsTheme', EChartsAsset::THEME_AUTO);
        foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
            $default = match ($name) {
                'LegendPosition'                        => 'top',
                'LineWidthPercent', 'SymbolSizePercent' => 100,
                'AreaOpacityPercent'                    => 22,
                'SmoothLines', 'ShowSymbols'            => false,
                default                                 => true
            };
            match ($type) {
                'string'  => $this->RegisterPropertyString('IPSView' . $name, $default),
                'integer' => $this->RegisterPropertyInteger('IPSView' . $name, $default),
                'boolean' => $this->RegisterPropertyBoolean('IPSView' . $name, $default)
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
            $this->Translate('Time series for IPSView'),
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
            $customRangeVisible = $this->ReadPropertyString('Range') === 'custom';
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['CustomRangeValue', 'CustomRangeUnit'],
                $customRangeVisible
            );
            $form['elements'] = $this->AttachListDesigners($form['elements']);
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->AttachTimeSeriesPreviewActions($form['elements']);
        }
        $form = SVGPreviewHelper::withImage(
            $form,
            'TimeSeriesPreview',
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries(),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('EChartsTheme'),
                $this->ReadTimeSeriesDesign(),
                annotations: $this->PreviewAnnotations(),
                timeAxisLabelFormat: $this->ReadPropertyString('TimeAxisLabelFormat')
            )
        );
        $form = SVGPreviewHelper::withImage(
            $form,
            'IPSViewTimeSeriesPreview',
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries(),
                $this->ReadPropertyString('Title'),
                $this->EffectiveIPSViewTheme(),
                $this->EffectiveIPSViewDesign(),
                $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                EChartsIPSViewBackground::Color($this->ReadPropertyInteger('IPSViewBackgroundColor')),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent'),
                $this->PreviewAnnotations(),
                $this->EffectiveTimeAxisLabelFormat(true)
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

        $customRangeVisible = (string) ($values['Range'] ?? $this->ReadPropertyString('Range')) === 'custom';
        $this->UpdateFormField('CustomRangeValue', 'visible', $customRangeVisible);
        $this->UpdateFormField('CustomRangeUnit', 'visible', $customRangeVisible);
        $useTileTimeSettings = (bool) ($values['IPSViewUseTileTimeSettings']
            ?? $this->ReadPropertyBoolean('IPSViewUseTileTimeSettings'));
        $ipsViewRange = (string) ($values['IPSViewRange'] ?? $this->ReadPropertyString('IPSViewRange'));
        foreach (['IPSViewRange', 'IPSViewTimeAxisLabelFormat'] as $field) {
            $this->UpdateFormField($field, 'visible', !$useTileTimeSettings);
        }
        $this->UpdateFormField(
            'IPSViewCustomRangeValue',
            'visible',
            !$useTileTimeSettings && $ipsViewRange === 'custom'
        );
        $this->UpdateFormField(
            'IPSViewCustomRangeUnit',
            'visible',
            !$useTileTimeSettings && $ipsViewRange === 'custom'
        );

        $this->UpdateFormField('TimeSeriesPreview', 'image', SVGPreviewHelper::dataUri(
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries($values['Sources'] ?? null),
                (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
                (string) ($values['EChartsTheme'] ?? $this->ReadPropertyString('EChartsTheme')),
                $this->TimeSeriesDesignFromFormValues($values),
                annotations: $this->PreviewAnnotations(
                    $values['Annotations'] ?? null,
                    $values['Sources'] ?? null
                ),
                timeAxisLabelFormat: (string) ($values['TimeAxisLabelFormat']
                    ?? $this->ReadPropertyString('TimeAxisLabelFormat'))
            )
        ));

        $useTileDesign = (bool) ($values['IPSViewUseTileDesign'] ?? true);
        $this->UpdateFormField('IPSViewTimeSeriesPreview', 'image', SVGPreviewHelper::dataUri(
            EChartsTimeSeriesPreview::CreateSvg(
                $this->PreviewSeries($values['Sources'] ?? null),
                (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
                $useTileDesign
                    ? (string) ($values['EChartsTheme'] ?? $this->ReadPropertyString('EChartsTheme'))
                    : (string) ($values['IPSViewEChartsTheme'] ?? $this->ReadPropertyString('IPSViewEChartsTheme')),
                $useTileDesign
                    ? $this->TimeSeriesDesignFromFormValues($values)
                    : $this->TimeSeriesDesignFromFormValues($values, 'IPSView'),
                (bool) ($values['IPSViewAdaptToBackground']
                    ?? $this->ReadPropertyBoolean('IPSViewAdaptToBackground')),
                EChartsIPSViewBackground::Color((int) ($values['IPSViewBackgroundColor']
                    ?? $this->ReadPropertyInteger('IPSViewBackgroundColor'))),
                (int) ($values['IPSViewBackgroundOpacityPercent']
                    ?? $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')),
                $this->PreviewAnnotations(
                    $values['Annotations'] ?? null,
                    $values['Sources'] ?? null
                ),
                $useTileTimeSettings
                    ? (string) ($values['TimeAxisLabelFormat']
                        ?? $this->ReadPropertyString('TimeAxisLabelFormat'))
                    : (string) ($values['IPSViewTimeAxisLabelFormat']
                        ?? $this->ReadPropertyString('IPSViewTimeAxisLabelFormat'))
            )
        ));
    }

    /** Refreshes both previews after a source-row editor was confirmed. */
    public function UpdateTimeSeriesPreviewSourceFromForm(string $Configuration, string $Action): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        $row = is_array($values) && is_array($values['Sources'] ?? null) ? $values['Sources'] : [];
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        $sources = is_array($sources) && array_is_list($sources) ? $sources : [];
        $variableID = (int) ($row['VariableID'] ?? 0);
        $matchingIndex = null;
        foreach ($sources as $index => $source) {
            if (is_array($source) && (int) ($source['VariableID'] ?? 0) === $variableID) {
                $matchingIndex = $index;
                break;
            }
        }
        if ($Action === 'delete' && $matchingIndex !== null) {
            array_splice($sources, $matchingIndex, 1);
        } elseif ($Action === 'add') {
            $sources[] = $row;
        } elseif ($matchingIndex !== null) {
            $sources[$matchingIndex] = $row;
        }
        $values['Sources'] = $sources;
        $this->UpdateTimeSeriesPreviewFromForm(json_encode($values, JSON_THROW_ON_ERROR));
    }

    /** Refreshes both previews after an annotation-row editor was confirmed. */
    public function UpdateTimeSeriesPreviewAnnotationFromForm(string $Configuration, string $Action): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        $row = is_array($values) && is_array($values['Annotations'] ?? null) ? $values['Annotations'] : [];
        $annotations = json_decode($this->ReadPropertyString('Annotations'), true);
        $annotations = is_array($annotations) && array_is_list($annotations) ? $annotations : [];
        $matchingIndexes = [];
        foreach ($annotations as $index => $annotation) {
            if (!is_array($annotation)
                || (int) ($annotation['VariableID'] ?? 0) !== (int) ($row['VariableID'] ?? 0)
                || (string) ($annotation['Type'] ?? 'line') !== (string) ($row['Type'] ?? 'line')
            ) {
                continue;
            }
            $matchingIndexes[] = $index;
            if ((string) ($annotation['Label'] ?? '') === (string) ($row['Label'] ?? '')) {
                $matchingIndexes = [$index];
                break;
            }
        }
        $matchingIndex = count($matchingIndexes) === 1 ? $matchingIndexes[0] : null;
        if ($Action === 'delete' && $matchingIndex !== null) {
            array_splice($annotations, $matchingIndex, 1);
        } elseif ($Action === 'add') {
            $annotations[] = $row;
        } elseif ($matchingIndex !== null) {
            $annotations[$matchingIndex] = $row;
        } else {
            $annotations[] = $row;
        }
        $values['Annotations'] = $annotations;
        $this->UpdateTimeSeriesPreviewFromForm(json_encode($values, JSON_THROW_ON_ERROR));
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
        IPS_SetProperty($this->InstanceID, 'IPSViewEChartsTheme', $this->ReadPropertyString('EChartsTheme'));
        foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
            $value = match ($type) {
                'string'  => $this->ReadPropertyString($name),
                'integer' => $this->ReadPropertyInteger($name),
                'boolean' => $this->ReadPropertyBoolean($name)
            };
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $value);
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function GetTimeSeriesData(): string
    {
        return $this->GetTimeSeriesDataForOutput(false);
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
        return $this->RenderTimeSeriesHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderTimeSeriesHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    /** @param array<int, mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
            $this->Initialize();
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
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
        $appendMessage = [
            'messageType' => 'append',
            'variableID'  => $SenderID,
            'timestamp'   => $timestamp,
            'value'       => (float) $value
        ];
        $this->UpdateVisualizationValue(json_encode(
            $appendMessage,
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
        ));
        $this->PushEChartsIPSViewMessage($appendMessage);
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

    private function GetTimeSeriesDataForOutput(bool $ipsView): string
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
        $annotations = $this->GetValidatedAnnotations($sources);
        $query = $this->ResolveArchiveQuery(count($sources), $ipsView);
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
                'design'                 => $source['Design'],
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
                'title'               => $this->ReadPropertyString('Title'),
                'enableZoom'          => $this->ReadPropertyBoolean('EnableZoom'),
                'timeAxisLabelFormat' => $this->EffectiveTimeAxisLabelFormat($ipsView),
                'design'              => $this->ReadTimeSeriesDesign()
            ],
            'range'         => [
                'key'                 => $this->EffectiveRange($ipsView),
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
            'annotations'   => $annotations,
            'truncated'     => $truncated
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderTimeSeriesHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Time Series',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-timeseries-root', 'echarts-timeseries'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
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
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::TimeSeriesJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
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

        $sources = $this->GetValidatedSources();
        $this->SetSummary(count($sources) . ' sources · ' . $this->RangeSummary());
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        $this->ScheduleArchiveRefresh();
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $sources = $this->GetValidatedSources();
            $this->GetValidatedAnnotations($sources);
        } catch (UnexpectedValueException $exception) {
            return [
                'Status'  => in_array($exception->getCode(), [201, 202], true)
                    ? $exception->getCode()
                    : self::STATUS_CONFIGURATION_INVALID,
                'Message' => $exception->getMessage()
            ];
        }

        if (!$this->IsValidTimeSettings()
            || !in_array(
                $this->ReadPropertyString('DataMode'),
                ['realtime', 'raw', 'auto', ...array_keys(self::AGGREGATION_LEVELS)],
                true
            )
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > 8000
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))
            || !$this->IsValidTimeSeriesDesign()
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The time series settings are invalid.'
            ];
        }

        if ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString('IPSViewEChartsTheme'))
                || !$this->IsValidTimeSeriesDesign('IPSView')
            ) {
                return [
                    'Status'  => self::STATUS_CONFIGURATION_INVALID,
                    'Message' => 'The IPSView time series settings are invalid.'
                ];
            }
        }

        if ($this->IsIPSViewHTMLPageEnabled()
            && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            && !$this->IsValidTimeSettings('IPSView')
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The IPSView time settings are invalid.'
            ];
        }

        return null;
    }

    private function IsValidTimeSettings(string $prefix = ''): bool
    {
        $range = $this->ReadPropertyString($prefix . 'Range');
        $customRangeIsValid = $range !== 'custom'
            || ($this->ReadPropertyInteger($prefix . 'CustomRangeValue') >= 1
                && $this->ReadPropertyInteger($prefix . 'CustomRangeValue') <= 1000
                && array_key_exists(
                    $this->ReadPropertyString($prefix . 'CustomRangeUnit'),
                    self::CUSTOM_RANGE_UNITS
                ));

        return (array_key_exists($range, self::RANGE_SECONDS) || $range === 'custom')
            && $customRangeIsValid
            && in_array(
                $this->ReadPropertyString($prefix . 'TimeAxisLabelFormat'),
                self::TIME_AXIS_LABEL_FORMATS,
                true
            );
    }

    /** @return list<array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:string,Style:string,Reducer:string,AxisPosition:string,AxisRange:?array{minimum:float,maximum:float},Design:array<string,mixed>}> */
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
            $axisRangeMode = $source['AxisRangeMode'] ?? 'auto';
            $axisMinimum = $source['AxisMinimum'] ?? 0.0;
            $axisMaximum = $source['AxisMaximum'] ?? 100.0;
            $usePresentation = $source['UseVariablePresentation'] ?? true;
            $useIndividualDesign = $source['UseIndividualDesign'] ?? false;
            $normalizedColor = self::NormalizeSourceColor($color);
            if (!is_string($label) || !is_string($unit) || !is_int($decimals)
                || $normalizedColor === null || !is_string($style) || !is_string($reducer) || !is_string($axisPosition)
                || !is_string($axisRangeMode)
                || !is_bool($usePresentation) || !is_bool($useIndividualDesign) || $decimals < 0 || $decimals > 6
                || !in_array($style, self::STYLES, true) || !in_array($reducer, self::REDUCERS, true)
                || !in_array($axisPosition, self::AXIS_POSITIONS, true)
                || !in_array($axisRangeMode, EChartsTimeSeriesDesign::AXIS_RANGE_MODES, true)
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
            $axisRange = null;
            if ($axisRangeMode !== 'auto') {
                if ((!is_int($axisMinimum) && !is_float($axisMinimum))
                    || (!is_int($axisMaximum) && !is_float($axisMaximum))
                    || !is_finite((float) $axisMinimum)
                    || !is_finite((float) $axisMaximum)
                    || (float) $axisMinimum >= (float) $axisMaximum
                ) {
                    throw new UnexpectedValueException(
                        'The value axis minimum must be smaller than its maximum.',
                        self::STATUS_CONFIGURATION_INVALID
                    );
                }
                if ($axisRangeMode === 'presentation') {
                    $axisPresentation = EChartsVariablePresentation::Resolve(
                        $variableID,
                        (float) $axisMinimum,
                        (float) $axisMaximum,
                        '',
                        0,
                        true
                    );
                    $axisRange = [
                        'minimum' => $axisPresentation['minimum'],
                        'maximum' => $axisPresentation['maximum']
                    ];
                } else {
                    $axisRange = [
                        'minimum' => (float) $axisMinimum,
                        'maximum' => (float) $axisMaximum
                    ];
                }
            }
            try {
                $sourceDesign = $useIndividualDesign
                    ? EChartsTimeSeriesDesign::StyleFromSource($source)
                    : [];
            } catch (InvalidArgumentException $exception) {
                throw new UnexpectedValueException($exception->getMessage(), self::STATUS_CONFIGURATION_INVALID, $exception);
            }
            $variableIDs[$variableID] = true;
            $result[] = [
                'VariableID'   => $variableID,
                'Label'        => trim($label),
                'Unit'         => $effectiveUnit,
                'Decimals'     => $presentation['decimals'],
                'Color'        => $normalizedColor,
                'Style'        => $style,
                'Reducer'      => $reducer,
                'AxisPosition' => $axisPosition,
                'AxisRange'    => $axisRange,
                'Design'       => $sourceDesign
            ];
        }

        $this->BuildAxisModel($result);

        return $result;
    }

    /**
     * @param list<array{Unit:string,AxisPosition:string,AxisRange?:?array{minimum:float,maximum:float}}> $sources
     * @return array{Axes:list<array{unit:string,position:string,positionIndex:int,minimum?:float,maximum?:float}>,Indexes:array<string,int>}
     */
    private function BuildAxisModel(array $sources): array
    {
        $groups = [];
        foreach ($sources as $source) {
            $unit = $source['Unit'];
            $requestedPosition = $source['AxisPosition'];
            if (!array_key_exists($unit, $groups)) {
                $groups[$unit] = ['unit' => $unit, 'position' => 'auto', 'range' => null];
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
            $requestedRange = $source['AxisRange'] ?? null;
            if ($requestedRange !== null) {
                $groupRange = $groups[$unit]['range'];
                if ($groupRange !== null
                    && (!$this->AxisValuesEqual($groupRange['minimum'], $requestedRange['minimum'])
                        || !$this->AxisValuesEqual($groupRange['maximum'], $requestedRange['maximum']))
                ) {
                    throw new UnexpectedValueException(
                        'Sources with the same unit must use the same explicit axis range.',
                        self::STATUS_CONFIGURATION_INVALID
                    );
                }
                $groups[$unit]['range'] = $requestedRange;
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
            $axis = [
                'unit'          => $group['unit'],
                'position'      => $position,
                'positionIndex' => $positionIndexes[$position]++
            ];
            if ($group['range'] !== null) {
                $axis['minimum'] = $group['range']['minimum'];
                $axis['maximum'] = $group['range']['maximum'];
            }
            $axes[] = $axis;
        }

        return ['Axes' => $axes, 'Indexes' => $indexes];
    }

    /**
     * @param list<array{VariableID:int}> $sources
     * @return list<array<string,mixed>>
     */
    private function GetValidatedAnnotations(array $sources): array
    {
        try {
            $annotations = json_decode($this->ReadPropertyString('Annotations'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException(
                'Time series annotations must contain valid JSON.',
                self::STATUS_CONFIGURATION_INVALID,
                $exception
            );
        }

        return $this->NormalizeAnnotations($annotations, $sources);
    }

    /**
     * @param list<array<string,mixed>> $sources
     * @return list<array<string,mixed>>
     */
    private function NormalizeAnnotations(mixed $annotations, array $sources): array
    {
        if (!is_array($annotations) || !array_is_list($annotations)
            || count($annotations) > self::MAXIMUM_ANNOTATION_COUNT
        ) {
            throw new UnexpectedValueException(
                'Configure no more than 32 time series annotations.',
                self::STATUS_CONFIGURATION_INVALID
            );
        }

        $seriesIndexes = [];
        foreach ($sources as $seriesIndex => $source) {
            $variableID = $source['VariableID'] ?? null;
            if (is_int($variableID) && $variableID > 0) {
                $seriesIndexes[$variableID] = $seriesIndex;
            }
        }

        $result = [];
        foreach ($annotations as $annotation) {
            if (!is_array($annotation)) {
                throw new UnexpectedValueException(
                    'Time series annotation settings are invalid.',
                    self::STATUS_CONFIGURATION_INVALID
                );
            }
            $variableID = $annotation['VariableID'] ?? null;
            $type = $annotation['Type'] ?? 'line';
            $label = $annotation['Label'] ?? '';
            $value = $annotation['Value'] ?? null;
            $maximum = $annotation['Maximum'] ?? null;
            $color = self::NormalizeSourceColor($annotation['Color'] ?? '');
            $lineType = $annotation['LineType'] ?? 'solid';
            $lineWidthPercent = $annotation['LineWidthPercent'] ?? 100;
            $opacityPercent = $annotation['OpacityPercent'] ?? 18;
            if (!is_int($variableID) || !array_key_exists($variableID, $seriesIndexes)
                || !is_string($type) || !in_array($type, EChartsTimeSeriesDesign::ANNOTATION_TYPES, true)
                || !is_string($label) || strlen($label) > 120
                || (!is_int($value) && !is_float($value)) || !is_finite((float) $value)
                || $color === null
                || !is_string($lineType)
                || !in_array($lineType, EChartsTimeSeriesDesign::ANNOTATION_LINE_TYPES, true)
                || !is_int($lineWidthPercent) || $lineWidthPercent < 50 || $lineWidthPercent > 200
                || !is_int($opacityPercent) || $opacityPercent < 0 || $opacityPercent > 100
            ) {
                throw new UnexpectedValueException(
                    'Time series annotation settings are invalid.',
                    self::STATUS_CONFIGURATION_INVALID
                );
            }
            if ($type === 'area'
                && ((!is_int($maximum) && !is_float($maximum))
                    || !is_finite((float) $maximum)
                    || (float) $value >= (float) $maximum)
            ) {
                throw new UnexpectedValueException(
                    'A time series value range requires a minimum smaller than its maximum.',
                    self::STATUS_CONFIGURATION_INVALID
                );
            }

            $normalized = [
                'type'             => $type,
                'variableID'       => $variableID,
                'seriesIndex'      => $seriesIndexes[$variableID],
                'label'            => trim($label),
                'value'            => (float) $value,
                'color'            => $color,
                'lineType'         => $lineType,
                'lineWidthPercent' => $lineWidthPercent,
                'opacityPercent'   => $opacityPercent
            ];
            if ($type === 'area') {
                $normalized['maximum'] = (float) $maximum;
            }
            $result[] = $normalized;
        }

        return $result;
    }

    private function AxisValuesEqual(float $left, float $right): bool
    {
        return abs($left - $right) <= 1e-9 * max(1.0, abs($left), abs($right));
    }

    /** @return array{DurationSeconds:int,StartTimestamp:int,EndTimestamp:int,Mode:string,AggregationLevel:int|null,Limit:int} */
    private function ResolveArchiveQuery(int $sourceCount, bool $ipsView = false): array
    {
        $duration = $this->RangeDurationSeconds($ipsView);
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

    private function RangeDurationSeconds(bool $ipsView = false): int
    {
        $range = $this->EffectiveRange($ipsView);
        if ($range !== 'custom') {
            return self::RANGE_SECONDS[$range];
        }

        return $this->EffectiveCustomRangeValue($ipsView)
            * self::CUSTOM_RANGE_UNITS[$this->EffectiveCustomRangeUnit($ipsView)];
    }

    private function RangeSummary(): string
    {
        if ($this->ReadPropertyString('Range') !== 'custom') {
            return $this->ReadPropertyString('Range');
        }

        $unit = match ($this->ReadPropertyString('CustomRangeUnit')) {
            'minute' => 'min',
            'hour'   => 'h',
            'day'    => 'd',
            'week'   => 'w'
        };

        return $this->ReadPropertyInteger('CustomRangeValue') . $unit;
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
    private function BuildVisualizationState(bool $ipsView = false): array
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
            $chart = json_decode($this->GetTimeSeriesDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView) {
                $chart['theme'] = $this->EffectiveIPSViewTheme();
                $chart['chart']['enableZoom'] = $this->EffectiveIPSViewEnableZoom();
                $chart['chart']['design'] = $this->EffectiveIPSViewDesign();
            }
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
            $sourceCount = max(1, count($this->GetValidatedSources()));
            $levels = [$this->ResolveArchiveQuery($sourceCount)['AggregationLevel']];
            if ($this->IsIPSViewHTMLPageEnabled()
                && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ) {
                $levels[] = $this->ResolveArchiveQuery($sourceCount, true)['AggregationLevel'];
            }
        } catch (Throwable) {
            $this->SetTimerInterval('ArchiveRefresh', 0);
            return;
        }
        $now = $this->CurrentTimestamp();
        $nextBoundaries = array_map(
            fn (int $level): int => $this->NextAggregationWindowStart($level, $now) + 2,
            array_values(array_unique(array_filter($levels, is_int(...))))
        );
        if ($nextBoundaries === []) {
            $this->SetTimerInterval('ArchiveRefresh', 0);
            return;
        }
        $nextBoundary = min($nextBoundaries);
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
    private function ReadTimeSeriesDesign(string $prefix = ''): array
    {
        return [
            'legendPosition'     => $this->ReadPropertyString($prefix . 'LegendPosition'),
            'lineWidthPercent'   => $this->ReadPropertyInteger($prefix . 'LineWidthPercent'),
            'smoothLines'        => $this->ReadPropertyBoolean($prefix . 'SmoothLines'),
            'showSymbols'        => $this->ReadPropertyBoolean($prefix . 'ShowSymbols'),
            'symbolSizePercent'  => $this->ReadPropertyInteger($prefix . 'SymbolSizePercent'),
            'areaOpacityPercent' => $this->ReadPropertyInteger($prefix . 'AreaOpacityPercent'),
            'showGrid'           => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'showXAxis'          => $this->ReadPropertyBoolean($prefix . 'ShowXAxis'),
            'showYAxis'          => $this->ReadPropertyBoolean($prefix . 'ShowYAxis')
        ];
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function TimeSeriesDesignFromFormValues(array $values, string $prefix = ''): array
    {
        $design = $this->ReadTimeSeriesDesign($prefix);
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
            if (array_key_exists($prefix . $property, $values)) {
                $design[$key] = $values[$prefix . $property];
            }
        }

        return $design;
    }

    /** @return list<array{variableID:int,label:string,color:string,style:string,design:array<string,mixed>}> */
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
            $color = self::NormalizeSourceColor($source['Color'] ?? '');
            $style = is_string($source['Style'] ?? null) && in_array($source['Style'], self::STYLES, true)
                ? $source['Style']
                : 'line';
            $sourceDesign = [];
            if (($source['UseIndividualDesign'] ?? false) === true) {
                try {
                    $sourceDesign = EChartsTimeSeriesDesign::StyleFromSource($source);
                } catch (InvalidArgumentException) {
                    $sourceDesign = [];
                }
            }
            $result[] = [
                'variableID' => is_int($variableID) ? $variableID : 0,
                'label'      => $label !== '' ? $label : 'Series ' . ($index + 1),
                'color'      => $color ?? '',
                'style'      => $style,
                'design'     => $sourceDesign
            ];
        }

        return $result;
    }

    /** @return list<array<string,mixed>> */
    private function PreviewAnnotations(mixed $formAnnotations = null, mixed $formSources = null): array
    {
        $annotations = $formAnnotations ?? $this->ReadPropertyString('Annotations');
        $sources = $formSources ?? $this->ReadPropertyString('Sources');
        if (is_string($annotations)) {
            $annotations = json_decode($annotations, true);
        }
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
        }
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }

        try {
            return array_values(array_filter(
                $this->NormalizeAnnotations($annotations, $sources),
                static fn (array $annotation): bool => $annotation['seriesIndex'] < 4
            ));
        } catch (UnexpectedValueException) {
            return [];
        }
    }

    private static function NormalizeSourceColor(mixed $color): ?string
    {
        if (is_int($color)) {
            if ($color === -1) {
                return '';
            }
            if ($color >= 0 && $color <= 0xFFFFFF) {
                return sprintf('#%06X', $color);
            }

            return null;
        }
        if (!is_string($color)) {
            return null;
        }
        $color = trim($color);
        if ($color === '') {
            return '';
        }

        return preg_match('/^#[0-9A-F]{6}$/i', $color) === 1 ? strtoupper($color) : null;
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function AttachListDesigners(array $items): array
    {
        foreach ($items as &$item) {
            if (($item['type'] ?? '') === 'List' && ($item['name'] ?? '') === 'Sources') {
                $item['form'] = EChartsTimeSeriesDesign::SourceEditorForm();
                $item['columns'] = array_merge(
                    is_array($item['columns'] ?? null) ? $item['columns'] : [],
                    EChartsTimeSeriesDesign::AxisRangeColumns(),
                    EChartsTimeSeriesDesign::SourceDesignColumns()
                );
                $item['onAdd'] = $this->TimeSeriesPreviewFormAction('add');
                $item['onEdit'] = $this->TimeSeriesPreviewFormAction('edit');
                $item['onDelete'] = $this->TimeSeriesPreviewFormAction('delete');
                continue;
            }
            if (($item['type'] ?? '') === 'List' && ($item['name'] ?? '') === 'Annotations') {
                $item['form'] = EChartsTimeSeriesDesign::AnnotationEditorForm();
                $item['onAdd'] = $this->TimeSeriesPreviewFormAction('add', 'annotation');
                $item['onEdit'] = $this->TimeSeriesPreviewFormAction('edit', 'annotation');
                $item['onDelete'] = $this->TimeSeriesPreviewFormAction('delete', 'annotation');
                continue;
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->AttachListDesigners($item['items']);
            }
        }
        unset($item);

        return $items;
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

    /** @param list<array<string,mixed>> $items @param list<string> $names @return list<array<string,mixed>> */
    private function SetFormFieldVisibility(array $items, array $names, bool $visible): array
    {
        foreach ($items as &$item) {
            if (isset($item['name']) && in_array($item['name'], $names, true)) {
                $item['visible'] = $visible;
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->SetFormFieldVisibility($item['items'], $names, $visible);
            }
        }
        unset($item);

        return $items;
    }

    private function TimeSeriesPreviewFormAction(string $sourceAction = '', string $list = 'source'): string
    {
        $pairs = array_map(
            static fn (string $name): string => "'" . $name . "' => $" . $name,
            self::DESIGN_FORM_FIELDS
        );

        if ($sourceAction !== '') {
            $function = $list === 'annotation'
                ? 'ECTS_UpdateTimeSeriesPreviewAnnotationFromForm'
                : 'ECTS_UpdateTimeSeriesPreviewSourceFromForm';

            return $function . '($id, json_encode([' . implode(', ', $pairs)
                . ']), ' . var_export($sourceAction, true) . ');';
        }

        return 'ECTS_UpdateTimeSeriesPreviewFromForm($id, json_encode([' . implode(', ', $pairs) . ']));';
    }

    private function EffectiveIPSViewTheme(): string
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');
    }

    private function EffectiveIPSViewEnableZoom(): bool
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyBoolean('EnableZoom')
            : $this->ReadPropertyBoolean('IPSViewEnableZoom');
    }

    /** @return array<string,mixed> */
    private function EffectiveIPSViewDesign(): array
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadTimeSeriesDesign()
            : $this->ReadTimeSeriesDesign('IPSView');
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

    private function IsValidTimeSeriesDesign(string $prefix = ''): bool
    {
        return in_array($this->ReadPropertyString($prefix . 'LegendPosition'), self::LEGEND_POSITIONS, true)
            && $this->ReadPropertyInteger($prefix . 'LineWidthPercent') >= 50
            && $this->ReadPropertyInteger($prefix . 'LineWidthPercent') <= 200
            && $this->ReadPropertyInteger($prefix . 'SymbolSizePercent') >= 50
            && $this->ReadPropertyInteger($prefix . 'SymbolSizePercent') <= 200
            && $this->ReadPropertyInteger($prefix . 'AreaOpacityPercent') >= 0
            && $this->ReadPropertyInteger($prefix . 'AreaOpacityPercent') <= 100;
    }

    private function IPSViewThemeCSS(): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($this->EffectiveIPSViewTheme());

        return EChartsIPSViewBackground::ThemeCSS(
            $palette,
            '#echarts-timeseries-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string,mixed>> $elements @return array<string,mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $tileDesigner = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer'
            ) {
                $tileDesigner = $element;
                break;
            }
        }
        if (!is_array($tileDesigner)) {
            throw new RuntimeException('The Tile designer form section is missing.');
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
                ...$this->IPSViewTimeSettingsFormItems(),
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
                    'onClick' => 'ECTS_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'
                ],
                [
                    'type'     => 'ExpansionPanel',
                    'caption'  => 'Independent IPSView designer',
                    'expanded' => false,
                    'items'    => $this->PrefixIPSViewDesignerItems($tileDesigner['items'] ?? [])
                ]
            ]
        ];
    }

    /** @return list<array<string,mixed>> */
    private function IPSViewTimeSettingsFormItems(): array
    {
        $independent = !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings');
        $custom = $independent && $this->ReadPropertyString('IPSViewRange') === 'custom';

        return [
            [
                'type'    => 'CheckBox',
                'name'    => 'IPSViewUseTileTimeSettings',
                'caption' => 'Use Tile time settings'
            ],
            [
                'type'    => 'Label',
                'caption' => 'Disable this option to use a separate IPSView time range and time-axis label format.'
            ],
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Select',
                        'name'    => 'IPSViewRange',
                        'caption' => 'IPSView time range',
                        'width'   => '190px',
                        'visible' => $independent,
                        'options' => [
                            ['caption' => '1 hour', 'value' => '1h'],
                            ['caption' => '6 hours', 'value' => '6h'],
                            ['caption' => '24 hours', 'value' => '24h'],
                            ['caption' => '7 days', 'value' => '7d'],
                            ['caption' => '30 days', 'value' => '30d'],
                            ['caption' => 'Custom', 'value' => 'custom']
                        ]
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'IPSViewCustomRangeValue',
                        'caption' => 'Range value',
                        'minimum' => 1,
                        'maximum' => 1000,
                        'width'   => '130px',
                        'visible' => $custom
                    ],
                    [
                        'type'    => 'Select',
                        'name'    => 'IPSViewCustomRangeUnit',
                        'caption' => 'Range unit',
                        'width'   => '150px',
                        'visible' => $custom,
                        'options' => [
                            ['caption' => 'Minutes', 'value' => 'minute'],
                            ['caption' => 'Hours', 'value' => 'hour'],
                            ['caption' => 'Days', 'value' => 'day'],
                            ['caption' => 'Weeks', 'value' => 'week']
                        ]
                    ],
                    [
                        'type'    => 'Select',
                        'name'    => 'IPSViewTimeAxisLabelFormat',
                        'caption' => 'Time axis labels',
                        'width'   => '190px',
                        'visible' => $independent,
                        'options' => [
                            ['caption' => 'Automatic', 'value' => 'auto'],
                            ['caption' => 'Time', 'value' => 'time'],
                            ['caption' => 'Date', 'value' => 'date'],
                            ['caption' => 'Date and time', 'value' => 'date-time']
                        ]
                    ]
                ]
            ]
        ];
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function PrefixIPSViewDesignerItems(array $items): array
    {
        $designNames = ['EChartsTheme', ...array_keys(self::DESIGN_PROPERTY_TYPES)];
        foreach ($items as &$item) {
            if (($item['name'] ?? null) === 'TimeSeriesPreview') {
                $item['name'] = 'IPSViewTimeSeriesPreview';
            } elseif (isset($item['name']) && in_array($item['name'], $designNames, true)) {
                $item['name'] = 'IPSView' . $item['name'];
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->PrefixIPSViewDesignerItems($item['items']);
            }
        }
        unset($item);

        return $items;
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
