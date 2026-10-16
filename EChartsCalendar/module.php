<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\CalendarData;
use SymconECharts\CalendarPreview;
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
require_once __DIR__ . '/CalendarData.php';
require_once __DIR__ . '/CalendarPreview.php';

class EChartsCalendar extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use CalendarPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewCalendar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => 'auto', 'Orientation' => 'auto', 'ShowTooltip' => true,
        'ShowMonthLabels'      => true, 'ShowWeekdayLabels' => true, 'FirstWeekday' => 1,
        'ShowDayNumbers'       => false, 'ShowValues' => false, 'ShowColorScale' => true,
        'CellBorderWidth'      => 1, 'CellBorderColor' => -1, 'EmptyCellColor' => -1,
        'LowColor'             => 0x5070DD, 'HighColor' => 0xFB628B,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100, 'ValueFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'ValueColor' => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECCL_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterTransientRefresh('ECCL');
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyInteger('Year', 0);
        $this->RegisterPropertyBoolean('UseVariablePresentation', true);
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
        $this->RegisterPropertyString('Title', '');
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
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Calendar for IPSView'), 90);
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
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECCL');
        $form = $this->WithIPSViewDesignFormState($form, 'ECCL');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
            ['caption' => 'Calendar layout', 'start' => 'Orientation'],
            ['caption' => 'Color scale and cells', 'start' => 'LowColor'],
            ['caption' => 'Labels and typography', 'start' => 'ShowDayNumbers']
        ]);
        $fields = ['Title', 'SourceVariableID', 'Year', 'UseVariablePresentation', 'Unit', 'Decimals',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
            'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECCL', $fields));
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

    public function GetCalendarData(): string
    {
        return $this->GetCalendarDataForOutput(false);
    }

    public function GetCalendarDiagnostic(): string
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
        return $this->RenderCalendarHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderCalendarHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (array $payload): array => $this->BuildVisualizationState(($payload['Output'] ?? 'ipsview') !== 'tile', (int) ($payload['WindowOffset'] ?? 0)),
            true
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

    /** Historical design preview: no archive reads and no state changes. */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        return $this->CalendarPreviewSvg($values, $ipsView);
    }

    private function GetCalendarDataForOutput(bool $ipsView, int $windowOffset = 0): string
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
        $id = $this->ReadPropertyInteger('SourceVariableID');
        $window = CalendarData::Resolve(
            $this->ReadPropertyInteger('Year'),
            $windowOffset,
            $this->CurrentTimestamp(),
            date_default_timezone_get()
        );
        $archive = $this->ReadArchiveSource(['VariableID' => $id], $window);
        try {
            $cells = CalendarData::Cells($archive['Points'], $window);
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw $exception;
        }
        $presentation = EChartsVariablePresentation::Resolve(
            $id,
            0.0,
            100.0,
            $this->ReadPropertyString('Unit'),
            $this->ReadPropertyInteger('Decimals'),
            $this->ReadPropertyBoolean('UseVariablePresentation')
        );
        $labels = EChartsSourceIdentity::LabelsForSources([['VariableID' => $id, 'Label' => '']]);
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode(['schemaVersion' => 1, 'family' => 'calendar', 'theme' => $this->EffectiveTheme($ipsView),
            'calendar'                      => ['title' => $this->ReadPropertyString('Title'), 'sourceLabel' => $labels[$id],
                'unit'                                  => $presentation['unit'], 'decimals' => $presentation['decimals'],
                'timezone'                              => $window['Timezone'], 'year' => $window['Year'], 'baseYear' => $window['BaseYear'],
                'windowOffset'                          => $windowOffset, 'maxOffset' => $window['MaximumOffset'],
                'startTimestamp'                        => $window['StartTimestamp'], 'endTimestamp' => $window['EndTimestamp'],
                'data'                                  => $cells, 'style' => $this->ReadDesignStyle($ipsView)]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderCalendarHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Calendar',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-calendar-root', 'echarts-calendar'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure a numeric Calendar archive variable.'                          => $this->Translate('Configure a numeric Calendar archive variable.'),
                'Connect an active EChartsGateway.'                                       => $this->Translate('Connect an active EChartsGateway.'),
                'The Calendar values could not be loaded.'                                => $this->Translate('The Calendar values could not be loaded.'),
                'No completed daily means in this year.'                                  => $this->Translate('No completed daily means in this year.'),
                'Previous year'                                                           => $this->Translate('Previous year'), 'Next year' => $this->Translate('Next year'),
                'Selected year'                                                           => $this->Translate('Selected year'), 'Daily mean' => $this->Translate('Daily mean'),
                'Calendar requires a standard archive variable, not a counter.'           => $this->Translate('Calendar requires a standard archive variable, not a counter.'),
                'Mon'                                                                     => $this->Translate('Mon'), 'Tue' => $this->Translate('Tue'), 'Wed' => $this->Translate('Wed'),
                'Thu'                                                                     => $this->Translate('Thu'), 'Fri' => $this->Translate('Fri'), 'Sat' => $this->Translate('Sat'), 'Sun' => $this->Translate('Sun'),
                'Jan'                                                                     => $this->Translate('Jan'), 'Feb' => $this->Translate('Feb'), 'Mar' => $this->Translate('Mar'),
                'Apr'                                                                     => $this->Translate('Apr'), 'May' => $this->Translate('May'), 'Jun' => $this->Translate('Jun'),
                'Jul'                                                                     => $this->Translate('Jul'), 'Aug' => $this->Translate('Aug'), 'Sep' => $this->Translate('Sep'),
                'Oct'                                                                     => $this->Translate('Oct'), 'Nov' => $this->Translate('Nov'), 'Dec' => $this->Translate('Dec'),
                'Data could not be refreshed. Showing the last available values.'         => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'calendarStatePath' => $this->EChartsIPSViewTransportOptions()['statePath'],
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::CalendarJavaScript(),
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
        $this->SetSummary($this->ReadPropertyInteger('Year') === 0 ? $this->Translate('Current year') : (string) $this->ReadPropertyInteger('Year'));
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        $id = $this->ReadPropertyInteger('SourceVariableID');
        if ($id <= 0 || !IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $this->Translate('Configure a numeric Calendar archive variable.')];
        }
        try {
            CalendarData::Resolve($this->ReadPropertyInteger('Year'), 0, $this->CurrentTimestamp(), date_default_timezone_get());
        } catch (Throwable) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $this->Translate('Choose the current year (0) or a year from 1971 through this year.')];
        }
        if ($this->ReadPropertyInteger('Decimals') < 0 || $this->ReadPropertyInteger('Decimals') > 6
            || mb_strlen($this->ReadPropertyString('Unit')) > 24 || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $this->Translate('The Calendar settings are invalid.')];
        }
        return null;
    }

    /** @param array{VariableID:int} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        if ($query['Empty']) {
            return ['Points' => [], 'Truncated' => false];
        }
        $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
        $this->WriteAttributeString('LastError', '');
        $payload = ['VariableID' => $source['VariableID'],
            'StartTimestamp'     => $query['StartTimestamp'], 'EndTimestamp' => $query['EndTimestamp'],
            'Mode'               => 'aggregated', 'Reducer' => 'average',
            'Limit'              => 366, 'AggregationLevel' => 1];
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
            throw new RuntimeException('The gateway returned an invalid Calendar archive response.', 0, $exception);
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
            || ($archive['EffectiveReducer'] ?? null) !== 'average' || ($archive['ArchiveAggregationType'] ?? null) !== 'standard') {
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid Calendar archive data.');
        }
        return $archive;
    }

    private function SynchronizeSourceReferences(): void
    {
        $previous = json_decode($this->ReadAttributeString('RegisteredSourceVariableIDs'), true);
        $previous = is_array($previous) ? array_values(array_filter($previous, is_int(...))) : [];
        $id = $this->ReadPropertyInteger('SourceVariableID');
        $current = $id > 0 && IPS_VariableExists($id) ? [$id] : [];
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
    private function BuildVisualizationState(bool $ipsView = false, int $windowOffset = 0): array
    {
        if ($this->GetConfigurationError() !== null) {
            return $this->ErrorState('Configure a numeric Calendar archive variable.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetCalendarDataForOutput($ipsView, $windowOffset), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState($this->ReadAttributeString('LastError') === 'REDUCER_NOT_SUPPORTED'
                ? 'Calendar requires a standard archive variable, not a counter.' : 'The Calendar values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'calendar', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'calendar', 'status' => 'error', 'chart' => null, 'error' => $message];
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
            $this->ScheduleTransientRefresh($states, 'The Calendar values could not be loaded.');
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
            || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'Orientation'), ['auto', 'horizontal', 'vertical'], true)) {
            return false;
        }
        foreach (['CellBorderWidth' => [0, 4], 'FirstWeekday' => [0, 6], 'TitleFontSizePercent' => [50, 200],
            'AxisFontSizePercent'   => [50, 200], 'ValueFontSizePercent' => [50, 200]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['CellBorderColor', 'EmptyCellColor', 'LowColor', 'HighColor', 'TitleColor', 'AxisColor', 'ValueColor'] as $name) {
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
            '#echarts-calendar-root',
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
            throw new RuntimeException('The Calendar Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECCL_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }
}
