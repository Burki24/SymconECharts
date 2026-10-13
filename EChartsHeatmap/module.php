<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAnimationDesign;
use SymconECharts\EChartsArchiveQuery;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsBarPreview;
use SymconECharts\EChartsBarPreviewForm;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\HeatmapAggregation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/HeatmapAggregation.php';

class EChartsHeatmap extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewHeatmap';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const RANGES = ['7d', '30d'];
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO,
        'LowColor'             => -1,
        'HighColor'            => -1,
        'ShowColorScale'       => true,
        'ShowValues'           => false,
        'TitleFontSizePercent' => 100,
        'AxisFontSizePercent'  => 100,
        'ValueFontSizePercent' => 100,
        'TitleColor'           => -1,
        'AxisColor'            => -1,
        'ValueColor'           => -1,
        'GridColor'            => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ArchiveRefresh', 0, 'ECHM_RefreshArchive($_IPS["TARGET"]);');
        $this->RegisterTransientRefresh('ECHM');
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Range', '30d');
        $this->RegisterPropertyBoolean('UseVariablePresentation', true);
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
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
        $this->RegisterAttributeInteger('RegisteredSourceVariableID', 0);
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Heatmap for IPSView'), 90);
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
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECHM');
        $form = $this->WithIPSViewDesignFormState($form, 'ECHM');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Color scale', 'start' => 'LowColor'],
            ['caption' => 'Labels and typography', 'start' => 'ShowValues']
        ]);
        $fields = [
            'Title', 'SourceVariableID', 'Range', 'UseVariablePresentation', 'Unit', 'Decimals',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
            'IPSViewBackgroundOpacityPercent',
            ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))
        ];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }

        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECHM', $fields));
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

    public function GetHeatmapData(): string
    {
        return $this->GetHeatmapDataForOutput(false);
    }

    public function GetHeatmapDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode([
            'valid'     => $error === null,
            'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderHeatmapHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderHeatmapHTMLPage(true);
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

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $low = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LowColor', 'integer', $ipsView), $palette['track']);
        $high = EChartsBarPreview::Color($this->BarPreviewValue($values, 'HighColor', 'integer', $ipsView), $palette['accent']);
        $titleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']);
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Heatmap'),
            $titleColor,
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $showScale = $this->BarPreviewValue($values, 'ShowColorScale', 'boolean', $ipsView);
        $sourceID = (int) ($values['SourceVariableID'] ?? $this->ReadPropertyInteger('SourceVariableID'));
        $sourceName = $sourceID > 0 && IPS_VariableExists($sourceID) ? IPS_GetName($sourceID) : $this->Translate('No source selected');
        $range = (string) ($values['Range'] ?? $this->ReadPropertyString('Range'));
        $svg .= '<text x="54" y="53" fill="' . EChartsBarPreview::E($palette['muted'])
            . '" font-size="11">' . EChartsBarPreview::E($sourceName . ' · ' . $range) . '</text>';
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        foreach ($weekdays as $day => $name) {
            $svg .= '<text x="45" y="' . (82 + $day * 34) . '" text-anchor="end" fill="'
                . EChartsBarPreview::E($axisColor) . '" font-size="12">'
                . EChartsBarPreview::E($this->Translate($name)) . '</text>';
            for ($hour = 0; $hour < 24; $hour++) {
                $ratio = (($hour * 11 + $day * 17) % 100) / 100;
                $color = self::InterpolateColor($low, $high, $ratio);
                $x = 54 + $hour * 26;
                $y = 64 + $day * 34;
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="24" height="30" rx="2" fill="'
                    . EChartsBarPreview::E($color) . '"/>';
                if ($showValues && $hour % 3 === 0) {
                    $svg .= '<text x="' . ($x + 12) . '" y="' . ($y + 19)
                        . '" text-anchor="middle" fill="' . EChartsBarPreview::E($palette['text'])
                        . '" font-size="10">' . (int) round($ratio * 100) . '</text>';
                }
            }
        }
        foreach ([0, 6, 12, 18, 23] as $hour) {
            $svg .= '<text x="' . (66 + $hour * 26) . '" y="312" text-anchor="middle" fill="'
                . EChartsBarPreview::E($axisColor) . '" font-size="12">' . $hour . '</text>';
        }
        if ($showScale) {
            $svg .= '<defs><linearGradient id="scale"><stop stop-color="' . EChartsBarPreview::E($low)
                . '"/><stop offset="1" stop-color="' . EChartsBarPreview::E($high)
                . '"/></linearGradient></defs><rect x="260" y="330" width="200" height="8" fill="url(#scale)"/>';
        }

        return $svg . '</svg>';
    }

    protected function CurrentTimestamp(): int
    {
        return time();
    }

    private function GetHeatmapDataForOutput(bool $ipsView): string
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
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        $presentation = EChartsVariablePresentation::Resolve(
            $variableID,
            0.0,
            100.0,
            $this->ReadPropertyString('Unit'),
            $this->ReadPropertyInteger('Decimals'),
            $this->ReadPropertyBoolean('UseVariablePresentation')
        );
        $timezone = date_default_timezone_get();
        $range = $this->ReadPropertyString('Range');
        $query = EChartsArchiveQuery::Resolve(
            $range,
            7,
            'day',
            'hour',
            2000,
            1,
            $this->CurrentTimestamp(),
            $timezone
        );
        $archive = $this->ReadArchive($variableID, $query);
        $cells = HeatmapAggregation::ByWeekdayAndHour($archive['Points'], $timezone);
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'heatmap',
            'theme'         => $this->EffectiveTheme($ipsView),
            'heatmap'       => [
                'title'          => $this->ReadPropertyString('Title'),
                'unit'           => $presentation['unit'],
                'decimals'       => $presentation['decimals'],
                'timezone'       => $timezone,
                'range'          => $range,
                'startTimestamp' => $query['StartTimestamp'],
                'endTimestamp'   => $query['EndTimestamp'],
                'style'          => $this->ReadDesignStyle($ipsView)
            ],
            'cells'     => $cells,
            'truncated' => $archive['Truncated']
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** @param array<string,mixed> $query @return array{Points:list<array{0:int,1:float}>,Truncated:bool} */
    private function ReadArchive(int $variableID, array $query): array
    {
        if ($query['Empty']) {
            return ['Points' => [], 'Truncated' => false];
        }
        $payload = [
            'VariableID'       => $variableID,
            'StartTimestamp'   => $query['StartTimestamp'],
            'EndTimestamp'     => $query['EndTimestamp'],
            'Mode'             => 'aggregated',
            'AggregationLevel' => 0,
            'Reducer'          => 'average',
            'Limit'            => $query['Limit']
        ];
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
            $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $code);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway archive request failed: ' . $code);
        }
        $archive = $response['Payload'];
        if (($archive['VariableID'] ?? null) !== $variableID
            || !is_array($archive['Points'] ?? null) || !is_bool($archive['Truncated'] ?? null)) {
            throw new RuntimeException('The gateway returned invalid Heatmap data.');
        }
        $points = [];
        foreach ($archive['Points'] as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || (!is_int($point[1]) && !is_float($point[1]))) {
                throw new RuntimeException('The gateway returned invalid Heatmap points.');
            }
            $points[] = [$point[0], (float) $point[1]];
        }

        return ['Points' => $points, 'Truncated' => $archive['Truncated']];
    }

    private function RenderHeatmapHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Heatmap',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-heatmap-root', 'echarts-heatmap'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure a numeric archive variable.'                           => $this->Translate('Configure a numeric archive variable.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Heatmap values could not be loaded.'                         => $this->Translate('The Heatmap values could not be loaded.'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                ),
                'The selected range was truncated.'                 => $this->Translate('The selected range was truncated.'),
                'Heatmap'                                           => $this->Translate('Heatmap'),
                '24 hours by seven weekdays.'                       => $this->Translate('24 hours by seven weekdays.'),
                'Hourly intervals'                                  => $this->Translate('Hourly intervals'),
                'No completed archive hours in the selected range.' => $this->Translate(
                    'No completed archive hours in the selected range.'
                ),
                'Mon'                               => $this->Translate('Mon'), 'Tue' => $this->Translate('Tue'),
                'Wed'                               => $this->Translate('Wed'), 'Thu' => $this->Translate('Thu'),
                'Fri'                               => $this->Translate('Fri'), 'Sat' => $this->Translate('Sat'),
                'Sun'                               => $this->Translate('Sun')
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::HeatmapJavaScript(),
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
        $this->SetTimerInterval('TransientRetry', 0);
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
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return;
        }
        $this->SetSummary($this->ReadPropertyString('Range'));
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($variableID <= 0 || !IPS_VariableExists($variableID)
            || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Configure a numeric archive variable.'];
        }
        if (!in_array($this->ReadPropertyString('Range'), self::RANGES, true)
            || $this->ReadPropertyInteger('Decimals') < 0 || $this->ReadPropertyInteger('Decimals') > 6
            || !$this->IsValidDesign('')
            || ($this->IsIPSViewHTMLPageEnabled()
                && (!EChartsIPSViewBackground::IsValid(
                    $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                    $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
                ) || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))))) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The Heatmap settings are invalid.'];
        }

        return null;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!$this->AnimationIsValid($prefix)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))) {
            return false;
        }
        foreach (['LowColor', 'HighColor', 'TitleColor', 'AxisColor', 'ValueColor', 'GridColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        foreach (['TitleFontSizePercent', 'AxisFontSizePercent', 'ValueFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 200) {
                return false;
            }
        }

        return true;
    }

    private function SynchronizeSourceReference(): void
    {
        $previous = $this->ReadAttributeInteger('RegisteredSourceVariableID');
        $current = $this->ReadPropertyInteger('SourceVariableID');
        if ($previous === $current) {
            return;
        }
        if ($previous > 0) {
            $this->UnregisterReference($previous);
        }
        if ($current > 0 && IPS_VariableExists($current)) {
            $this->RegisterReference($current);
        }
        $this->WriteAttributeInteger('RegisteredSourceVariableID', $current);
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'error',
                'chart'             => null, 'error' => $error['Message']];
        }
        if (!$this->HasActiveParent()) {
            return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'error',
                'chart'             => null, 'error' => 'Connect an active EChartsGateway.'];
        }
        try {
            $chart = json_decode($this->GetHeatmapDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'error',
                'chart'             => null, 'error' => 'The Heatmap values could not be loaded.'];
        }

        return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'ready',
            'chart'             => $chart, 'error' => null];
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
            $this->ScheduleTransientRefresh($states, 'The Heatmap values could not be loaded.');
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

    private function EffectiveTheme(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('IPSViewEChartsTheme')
            : $this->ReadPropertyString('EChartsTheme');
    }

    /** @return array<string,bool|int|string> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';

        return [
            ...$this->ReadAnimationStyle($prefix),
            'lowColor'             => $this->OptionalDesignColor($prefix . 'LowColor'),
            'highColor'            => $this->OptionalDesignColor($prefix . 'HighColor'),
            'showColorScale'       => $this->ReadPropertyBoolean($prefix . 'ShowColorScale'),
            'showValues'           => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'titleFontSizePercent' => $this->ReadPropertyInteger($prefix . 'TitleFontSizePercent'),
            'axisFontSizePercent'  => $this->ReadPropertyInteger($prefix . 'AxisFontSizePercent'),
            'valueFontSizePercent' => $this->ReadPropertyInteger($prefix . 'ValueFontSizePercent'),
            'titleColor'           => $this->OptionalDesignColor($prefix . 'TitleColor'),
            'axisColor'            => $this->OptionalDesignColor($prefix . 'AxisColor'),
            'valueColor'           => $this->OptionalDesignColor($prefix . 'ValueColor'),
            'gridColor'            => $this->OptionalDesignColor($prefix . 'GridColor')
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
            '#echarts-heatmap-root',
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
            if (($element['type'] ?? null) === 'ExpansionPanel' && ($element['caption'] ?? null) === 'Tile designer') {
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
        }
        if ($rows === []) {
            throw new RuntimeException('The Heatmap Tile designer section is missing.');
        }

        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECHM_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }

    private static function InterpolateColor(string $low, string $high, float $ratio): string
    {
        $first = sscanf($low, '#%02x%02x%02x');
        $last = sscanf($high, '#%02x%02x%02x');
        return sprintf(
            '#%02x%02x%02x',
            (int) round($first[0] + ($last[0] - $first[0]) * $ratio),
            (int) round($first[1] + ($last[1] - $first[1]) * $ratio),
            (int) round($first[2] + ($last[2] - $first[2]) * $ratio)
        );
    }
}
