<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\DebugFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use eCharts\EChartsAnimationDesign;
use eCharts\EChartsAsset;
use eCharts\EChartsBarPreviewForm;
use eCharts\EChartsCurrentSources;
use eCharts\EChartsDebugFlow;
use eCharts\EChartsDesignerSections;
use eCharts\EChartsGaugeDesign;
use eCharts\EChartsGaugeWindPreview;
use eCharts\EChartsIPSViewBackground;
use eCharts\EChartsIPSViewDesignForm;
use eCharts\EChartsIPSViewTransport;
use eCharts\EChartsSvgImage;
use eCharts\EChartsTransientRefresh;
use eCharts\WindCompassCatalog;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDebugFlow.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsGaugeDesign.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/WindCompassCatalog.php';
require_once __DIR__ . '/WindPreview.php';

class EChartsGaugeWind extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use DebugFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use EChartsCurrentSources;
    use EChartsDebugFlow;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewWind';
    private const STATUS_GATEWAY_FAILED = 204;
    private const DIRECTION_INVALID = 'Select a numeric wind direction variable.';
    private const SPEED_INVALID = 'Select a different numeric wind speed variable or leave it empty.';
    private const SETTINGS_INVALID = 'Check the wind gauge settings.';
    private const COMPASS_INVALID = 'Select a supported SVG compass rose or clear the file.';
    private const PRESET_INVALID = 'Select an available compass rose preset.';
    private const BACKGROUND_INVALID = 'Select a valid chart background color.';
    private const NO_GATEWAY = 'Connect an active EChartsGateway.';
    private const NEGATIVE_SPEED = 'Wind speed must be non-negative.';
    private const LOAD_ERROR = 'The wind values could not be loaded.';
    private const MAX_TITLE_LENGTH = 200;
    private const MAX_UNIT_LENGTH = 32;
    private const MAX_SPEED_DECIMALS = 6;
    private const COLORS = ['DialColor', 'RingColor', 'TickColor', 'CardinalColor', 'PointerColor',
        'HubColor', 'RotorColor', 'TitleColor', 'ValueColor'];
    private const SWITCHES = ['RotateCompass', 'ShowRotor', 'ShowDegreeValue'];
    private const ANIMATION_FIELDS = ['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate',
        'AnimationEasing', 'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'];
    private const DESIGN_FIELDS = ['EChartsTheme', 'CompassPreset', 'CompassSvg', ...self::SWITCHES, ...self::COLORS, ...self::ANIMATION_FIELDS];

    /** Last validated upload per designer, reused across outputs in the same module call. */
    private array $compassImages = [];

    public function Create(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::Create();
            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterTransientRefresh('ECWI');
            $this->RegisterPropertyInteger('DirectionVariableID', 0);
            $this->RegisterPropertyInteger('SpeedVariableID', 0);
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('SpeedUnit', 'km/h');
            $this->RegisterPropertyInteger('SpeedDecimals', 2);
            foreach (['', 'IPSView'] as $prefix) {
                $this->RegisterPropertyString($prefix . 'EChartsTheme', 'auto');
                $this->RegisterPropertyString($prefix . 'CompassSvg', '');
                $this->RegisterPropertyString($prefix . 'CompassPreset', 'auto');
                foreach (self::SWITCHES as $name) {
                    $this->RegisterPropertyBoolean($prefix . $name, false);
                }
                foreach (self::COLORS as $name) {
                    $this->RegisterPropertyInteger($prefix . $name, -1);
                }
                $this->RegisterAnimationProperties($prefix, true, 350, 500);
            }
            $this->RegisterChartBackgroundProperties();
            $this->RegisterIPSViewHTMLPageProperties();
            $this->RegisterEChartsIPSViewTransport();
            $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
            $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
            $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
            $this->RegisterPropertyInteger('IPSViewBackgroundOpacityPercent', EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT);
            $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
            $this->RegisterAttributeString('LastError', '');
        }, true);
    }

    public function ApplyChanges(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::ApplyChanges();
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Wind gauge for IPSView'), 90);
            $this->Initialize();
        }, true);
    }

    public function GetCompatibleParents(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, static fn (): string => json_encode(
            ['type' => 'connect', 'moduleIDs' => [self::GATEWAY_MODULE_ID]],
            JSON_THROW_ON_ERROR
        ));
    }

    public function GetConfigurationForm(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): string
        {
            $form = $this->LoadConfigurationForm();
            foreach ($form['elements'] as $panelIndex => $panel) {
                if (($panel['caption'] ?? '') !== 'Tile designer') {
                    continue;
                }
                foreach ($panel['items'] as $index => $field) {
                    if (($field['name'] ?? '') === 'CompassPreset') {
                        array_push($form['elements'][$panelIndex]['items'][$index]['options'], ...WindCompassCatalog::Options());
                        break;
                    }
                }
            }
            $form['elements'][] = EChartsGaugeDesign::IPSViewDesignerForm(
                $form['elements'],
                $this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                self::DESIGN_FIELDS,
                ''
            );
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECWI');
            $form = $this->WithIPSViewDesignFormState($form, 'ECWI');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
                ['caption' => 'Compass', 'start' => 'RotateCompass'],
                ['caption' => 'Colors', 'start' => 'DialColor']
            ]);
            $fields = ['DirectionVariableID', 'SpeedVariableID', 'Title', 'SpeedUnit', 'SpeedDecimals',
                'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
                'IPSViewBackgroundOpacityPercent', ...self::DESIGN_FIELDS,
                ...array_map(static fn (string $name): string => 'IPSView' . $name, self::DESIGN_FIELDS)];
            return $this->EncodeChartDesignerForm(__DIR__, $this->WithBarPreviewForm($form, 'ECWI', $fields, true));
        });
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Ident, $Value): void
        {
            if (!$this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
                throw new InvalidArgumentException('Unknown action: ' . $Ident);
            }
        }, true, ['Aktion' => $Ident]);
    }

    public function GetWindData(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => json_encode(
            $this->ChartForOutput(false),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        ));
    }

    public function GetVisualizationTile(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->RenderWindPage(false));
    }

    public function GetIPSViewHTML(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->RenderWindPage(true));
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        ));
    }

    public function RetryTransient(): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): void
        {
            $this->PublishVisualizationState();
        }, true);
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($SenderID, $Message): void
        {
            if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
                $this->Initialize();
            } elseif ($Message === VM_UPDATE && in_array($SenderID, $this->ConfiguredVariableIDs(), true)) {
                $this->PublishVisualizationState();
            }
        }, true, ['Quelle' => $SenderID, 'Nachricht' => $Message]);
    }

    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        // Form callbacks also accept interim values outside a spinner's configured range.
        $values['SpeedDecimals'] = max(0, min(self::MAX_SPEED_DECIMALS, $this->BarPreviewValue($values, 'SpeedDecimals', 'integer')));
        $values['Title'] = mb_substr($this->BarPreviewValue($values, 'Title', 'string'), 0, self::MAX_TITLE_LENGTH);
        $values['SpeedUnit'] = mb_substr($this->BarPreviewValue($values, 'SpeedUnit', 'string'), 0, self::MAX_UNIT_LENGTH);
        $directionID = $this->BarPreviewValue($values, 'DirectionVariableID', 'integer');
        $speedID = $this->BarPreviewValue($values, 'SpeedVariableID', 'integer');
        $direction = self::IsNumericVariable($directionID) ? (float) GetValue($directionID) : 45.0;
        $speed = self::IsNumericVariable($speedID) ? (float) GetValue($speedID) : null;
        try {
            $image = $this->CompassImage($values, $ipsView);
        } catch (DomainException $exception) {
            return EChartsGaugeDesign::CreateErrorSvg($this->Translate($exception->getMessage()));
        }
        $chart = $this->WindChart(
            $ipsView,
            $values,
            is_finite($direction) ? $direction : 45.0,
            $speed !== null && is_finite($speed) && $speed >= 0 ? $speed : null,
            $image
        );
        $chart['style'] = EChartsIPSViewBackground::WithPreviewStyle(
            $chart['style'],
            $ipsView && (bool) $values['IPSViewAdaptToBackground'],
            (int) $values['IPSViewBackgroundColor'],
            (int) $values['IPSViewBackgroundOpacityPercent'],
            $this->EffectiveChartBackgroundColor($ipsView, $values)
        );
        return EChartsGaugeWindPreview::CreateSvg($chart);
    }

    private function Initialize(): void
    {
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            $this->DebugEChartsSetStatus(IS_INACTIVE);
            return;
        }
        $this->SynchronizeSourceReferences();
        $this->PublishVisualizationState();
        $this->RegenerateIPSViewHTMLPages();
    }

    /** @return list<int> */
    private function ConfiguredVariableIDs(): array
    {
        return array_values(array_unique(array_filter(
            [$this->ReadPropertyInteger('DirectionVariableID'), $this->ReadPropertyInteger('SpeedVariableID')],
            static fn (int $id): bool => $id > 0
        )));
    }

    private static function IsNumericVariable(int $id): bool
    {
        return $id > 0 && IPS_VariableExists($id) && in_array(IPS_GetVariable($id)['VariableType'], [1, 2], true);
    }

    private function IsValidDesign(string $prefix): bool
    {
        foreach (self::COLORS as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }
        return EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            && $this->AnimationIsValid($prefix);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        $directionID = $this->ReadPropertyInteger('DirectionVariableID');
        $speedID = $this->ReadPropertyInteger('SpeedVariableID');
        if (!$this->ChartBackgroundColorsAreValid()) {
            return ['Status' => 202, 'Message' => self::BACKGROUND_INVALID];
        }
        if (!self::IsNumericVariable($directionID)) {
            return ['Status' => 201, 'Message' => self::DIRECTION_INVALID];
        }
        if ($speedID !== 0 && ($speedID === $directionID || !self::IsNumericVariable($speedID))) {
            return ['Status' => 201, 'Message' => self::SPEED_INVALID];
        }
        $decimals = $this->ReadPropertyInteger('SpeedDecimals');
        if ($decimals < 0 || $decimals > self::MAX_SPEED_DECIMALS || mb_strlen($this->ReadPropertyString('Title')) > self::MAX_TITLE_LENGTH
            || mb_strlen($this->ReadPropertyString('SpeedUnit')) > self::MAX_UNIT_LENGTH
            || !$this->IsValidDesign('') || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => 202, 'Message' => self::SETTINGS_INVALID];
        }
        return null;
    }

    private function ChartForOutput(bool $ipsView): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            throw new DomainException($error['Message'], $error['Status']);
        }
        $image = $this->CompassImage([], false);
        if (!$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            $ipsViewImage = $this->CompassImage([], true);
            if ($ipsView) {
                $image = $ipsViewImage;
            }
        }
        if (!$this->HasActiveParent()) {
            throw new DomainException(self::NO_GATEWAY, 203);
        }
        $directionID = $this->ReadPropertyInteger('DirectionVariableID');
        $speedID = $this->ReadPropertyInteger('SpeedVariableID');
        $direction = $this->ReadCurrentSource($directionID);
        $speed = $speedID === 0 ? null : $this->ReadCurrentSource($speedID);
        if ($speed !== null && $speed['Value'] < 0) {
            throw new DomainException(self::NEGATIVE_SPEED, 205);
        }
        $chart = $this->WindChart($ipsView, [], $direction['Value'], $speed['Value'] ?? null, $image);
        $chart['directionVariableID'] = $directionID;
        $chart['directionTimestamp'] = $direction['Timestamp'];
        $chart['speedVariableID'] = $speedID;
        $chart['speedTimestamp'] = $speed['Timestamp'] ?? null;
        return $this->WithChartBackground($chart, $ipsView);
    }

    private function CompassImage(array $values, bool $ipsView): string
    {
        $preset = $this->BarPreviewValue($values, 'CompassPreset', 'string', $ipsView);
        if ($preset === 'standard') {
            return '';
        }
        $file = $preset === 'auto' ? $this->BarPreviewValue($values, 'CompassSvg', 'string', $ipsView) : '';
        if ($preset === 'auto' && $file === '') {
            return '';
        }
        $cached = $this->compassImages[(int) $ipsView] ?? null;
        if ($cached !== null && $cached['preset'] === $preset && $cached['file'] === $file) {
            return $cached['image'];
        }
        try {
            $image = EChartsSvgImage::ImportCompass($preset === 'auto' ? $file : WindCompassCatalog::Read($preset))['dataUri'];
        } catch (InvalidArgumentException $exception) {
            throw new DomainException($preset === 'auto' ? self::COMPASS_INVALID : self::PRESET_INVALID, 202, $exception);
        }
        $this->compassImages[(int) $ipsView] = ['preset' => $preset, 'file' => $file, 'image' => $image];
        return $image;
    }

    private function WindChart(bool $ipsView, array $values, float $direction, ?float $speed, string $image): array
    {
        $prefix = $ipsView && !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')) ? 'IPSView' : '';
        $style = $this->AnimationStyleFromFormValues($values, $prefix);
        $style['compassImage'] = $image;
        foreach (self::SWITCHES as $name) {
            $style[lcfirst($name)] = $this->BarPreviewValue($values, $name, 'boolean', $ipsView);
        }
        foreach (self::COLORS as $name) {
            $style[lcfirst($name)] = EChartsIPSViewBackground::Color($this->BarPreviewValue($values, $name, 'integer', $ipsView));
        }
        $bearing = fmod($direction, 360.0);
        if ($bearing < 0) {
            $bearing += 360.0;
        }
        return [
            'schemaVersion' => 1, 'family' => 'gaugeWind',
            'theme'         => $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView),
            'title'         => $this->BarPreviewValue($values, 'Title', 'string'),
            // Adding a negative epsilon to 360 can round back up to 360.
            'direction'     => $bearing === 0.0 || $bearing === 360.0 ? 0.0 : $bearing,
            'speed'         => $speed,
            'speedUnit'     => $this->BarPreviewValue($values, 'SpeedUnit', 'string'),
            'speedDecimals' => $this->BarPreviewValue($values, 'SpeedDecimals', 'integer'),
            'windLabel'     => $this->Translate('Wind'),
            'language'      => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'labels'        => array_map($this->Translate(...), ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW']),
            'style'         => $style
        ];
    }

    private function BuildVisualizationState(bool $ipsView): array
    {
        $state = ['schemaVersion' => 1, 'family' => 'gaugeWind', 'status' => 'error', 'chart' => null, 'error' => null];
        try {
            $state['chart'] = $this->ChartForOutput($ipsView);
            $state['status'] = 'ready';
            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);
        } catch (DomainException $exception) {
            $state['error'] = $exception->getMessage();
            $this->WriteAttributeString('LastError', $state['error']);
            $this->DebugEChartsSetStatus($exception->getCode());
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            $state['error'] = self::LOAD_ERROR;
            $this->WriteAttributeString('LastError', self::LOAD_ERROR);
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
        }
        return $state;
    }

    private function PublishVisualizationState(): void
    {
        $state = $this->BuildVisualizationState(false);
        $this->UpdateVisualizationValue(json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $states = [$state];
        $this->PushEChartsIPSViewState(function () use (&$states): array
        {
            $state = $this->BuildVisualizationState(true);
            $states[] = $state;
            return $state;
        });
        $this->ScheduleTransientRefresh($states, self::LOAD_ERROR);
    }

    private function RenderWindPage(bool $ipsView): string
    {
        $theme = $this->BarPreviewValue([], 'EChartsTheme', 'string', $ipsView);
        return $this->RenderChartPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Wind Gauge',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n" . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'ipsViewStyle'       => $ipsView ? EChartsIPSViewBackground::ThemeCSS(
                EChartsAsset::ThemePreviewPalette($theme),
                '#echarts-gauge-root',
                $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            ) : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => $this->IPSViewTranslationsFor([self::DIRECTION_INVALID, self::SPEED_INVALID,
                self::SETTINGS_INVALID, self::COMPASS_INVALID, self::PRESET_INVALID, self::BACKGROUND_INVALID, self::NO_GATEWAY, self::NEGATIVE_SPEED, self::LOAD_ERROR,
                'Data could not be refreshed. Showing the last available values.']),
            'options' => [
                'echartsVersion' => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::RendererScript('gauge', $ipsView),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }
}
