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
use SymconECharts\EChartsCurrentComparisonSources;
use SymconECharts\EChartsCurrentSources;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\PictorialBarIcon;
use SymconECharts\PictorialBarSvg;

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
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsCurrentComparisonSources.php';
require_once __DIR__ . '/PictorialBarPreview.php';
require_once __DIR__ . '/PictorialBarIcon.php';
require_once __DIR__ . '/PictorialBarSvg.php';

class EChartsPictorialBar extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use EChartsCurrentSources;
    use SymconECharts\PictorialBarPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewPictorialBar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'            => 'auto',
        'Orientation'             => 'horizontal',
        'SortOrder'               => 'configured',
        'Symbol'                  => 'rect',
        'SymbolIcon'              => '',
        'CustomSymbolSVG'         => '',
        'SymbolRepeat'            => true,
        'SymbolSize'              => 12,
        'SymbolGap'               => 2,
        'BarWidth'                => 40,
        'ScaleMaximum'            => 0,
        'ShowTooltip'             => true,
        'ShowLabels'              => true,
        'ShowValues'              => true,
        'ShowGrid'                => true,
        'TitleFontSizePercent'    => 100,
        'AxisFontSizePercent'     => 100,
        'ValueFontSizePercent'    => 100,
        'TitleColor'              => -1,
        'AxisColor'               => -1,
        'ValueColor'              => -1,
        'GridColor'               => -1,
        'TitleBold'               => true,
        'OpacityPercent'          => 90,
        'BorderWidth'             => 0,
        'BorderColor'             => -1,
        'FontFamily'              => 'Arial, sans-serif',
        'ValuePosition'           => 'outside',
        'ValueDistance'           => 5,
        'ValueBold'               => false,
        'HideOverlap'             => true,
        'CategoryLabelRotate'     => 0,
        'LabelColumnWidthPercent' => 0,
        'ShowAxisValues'          => true,
        'ShowAxisTicks'           => false,
        'GridWidth'               => 1,
        'GridType'                => 'solid',
        'GridOpacityPercent'      => 25,
        'ColorMode'               => 'category',
        'ColorMinimum'            => 0.0,
        'ColorMaximum'            => 100.0,
        'LowColor'                => 0xEE6666,
        'MiddleColor'             => 0xFAC858,
        'HighColor'               => 0x91CC75,
        'BorderType'              => 'solid',
        'ShadowBlur'              => 0,
        'ShadowColor'             => -1,
        'ShadowOffsetX'           => 0,
        'ShadowOffsetY'           => 0,
        'EmphasisDisabled'        => false,
        'Focus'                   => 'self',
        'BlurOpacityPercent'      => 10
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTransientRefresh('ECPB');
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        foreach (['', 'IPSView'] as $prefix) {
            foreach (self::DESIGN_DEFAULTS as $name => $default) {
                match (gettype($default)) {
                    'boolean' => $this->RegisterPropertyBoolean($prefix . $name, $default),
                    'integer' => $this->RegisterPropertyInteger($prefix . $name, $default),
                    'double'  => $this->RegisterPropertyFloat($prefix . $name, $default),
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
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('PictorialBar for IPSView'), 90);
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
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECPB');
        $form = $this->WithIPSViewDesignFormState($form, 'ECPB');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
            ['caption' => 'Layout and symbols', 'start' => 'Orientation'],
            ['caption' => 'Axes and values', 'start' => 'ShowGrid'],
            ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent'],
            ['caption' => 'Borders and opacity', 'start' => 'BorderWidth'],
            ['caption' => 'Labels and typography', 'start' => 'FontFamily'],
            ['caption' => 'Axis and grid details', 'start' => 'ShowAxisValues'],
            ['caption' => 'Value-dependent colors', 'start' => 'ColorMode'],
            ['caption' => 'Shadows and highlighting', 'start' => 'ShadowBlur']
        ]);
        $fields = ['Title', 'Sources',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
            'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECPB', $fields));
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
                'double'  => $this->ReadPropertyFloat($name),
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

    public function GetPictorialBarData(): string
    {
        return $this->GetPictorialBarDataForOutput(false);
    }

    public function GetPictorialBarDiagnostic(): string
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
        return $this->RenderPictorialBarHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderPictorialBarHTMLPage(true);
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

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        return $this->PictorialBarPreviewSvg($values, $ipsView);
    }

    /** @return array{name:string,path:string,width:int,height:int} */
    protected function ReadSymconIcon(string $name): array
    {
        return PictorialBarIcon::Resolve($name);
    }

    private function GetPictorialBarDataForOutput(bool $ipsView): string
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
        // A new transport failure must not inherit a previous domain error.
        $this->WriteAttributeString('LastError', '');
        $data = [];
        foreach ($sources as $index => $source) {
            $current = $this->ReadCurrentSource($source['VariableID']);
            if ($current['Value'] < 0) {
                $this->WriteAttributeString('LastError', 'PictorialBar requires non-negative values.');
                $this->SetStatus(self::STATUS_VALUE_INVALID);
                throw new DomainException('PictorialBar requires non-negative values.');
            }
            $data[] = ['id' => 'variable-' . $source['VariableID'], 'variableID' => $source['VariableID'],
                'label'     => $source['Label'], 'unit' => $source['Unit'], 'decimals' => $source['Decimals'],
                'value'     => $current['Value'], 'timestamp' => $current['Timestamp'], 'order' => $index,
                'color'     => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color'])];
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode(
            ['schemaVersion'       => 1, 'family' => 'pictorialBar', 'theme' => $this->EffectiveTheme($ipsView),
                'pictorialBar'     => ['title' => $this->ReadPropertyString('Title'), 'unit' => $sources[0]['Unit'],
                    'data'                     => $data, 'style' => $this->ReadDesignStyle($ipsView)]],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    private function RenderPictorialBarHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts PictorialBar',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-pictorial-bar-root', 'echarts-pictorial-bar'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Select a Symcon icon from the icon picker.'                                          => $this->Translate('Select a Symcon icon from the icon picker.'),
                'The installed Symcon icon library could not be read.'                                => $this->Translate('The installed Symcon icon library could not be read.'),
                'The selected Symcon icon is unavailable or unsupported.'                             => $this->Translate('The selected Symcon icon is unavailable or unsupported.'),
                'The PictorialBar settings are invalid.'                                              => $this->Translate('The PictorialBar settings are invalid.'),
                'The custom SVG shape is invalid or unsupported. Use a path-only SVG with a viewBox.' => $this->Translate('The custom SVG shape is invalid or unsupported. Use a path-only SVG with a viewBox.'),
                'Configure valid PictorialBar variables.'                                             => $this->Translate('Configure valid PictorialBar variables.'),
                'Connect an active EChartsGateway.'                                                   => $this->Translate('Connect an active EChartsGateway.'),
                'The PictorialBar values could not be loaded.'                                        => $this->Translate('The PictorialBar values could not be loaded.'),
                'PictorialBar requires non-negative values.'                                          => $this->Translate('PictorialBar requires non-negative values.'),
                'All values are zero.'                                                                => $this->Translate('All values are zero.'),
                'Data could not be refreshed. Showing the last available values.'                     => $this->Translate('Data could not be refreshed. Showing the last available values.')
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::PictorialBarJavaScript(),
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
        $this->SetSummary(count($this->ConfiguredVariableIDs()) . ' ' . $this->Translate('Categories'));
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
        foreach ($this->ReadPropertyBoolean('IPSViewUseTileDesign') ? [''] : ['', 'IPSView'] as $prefix) {
            if ($this->ReadPropertyString($prefix . 'Symbol') === 'icon') {
                try {
                    $this->ReadSymconIcon($this->ReadPropertyString($prefix . 'SymbolIcon'));
                } catch (Throwable $exception) {
                    return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $exception->getMessage()];
                }
            }
            if ($this->ReadPropertyString($prefix . 'Symbol') === 'svg') {
                try {
                    PictorialBarSvg::Import($this->ReadPropertyString($prefix . 'CustomSymbolSVG'));
                } catch (InvalidArgumentException $exception) {
                    return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $exception->getMessage()];
                }
            }
        }
        if (mb_strlen($this->ReadPropertyString('Title')) > 200
            || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The PictorialBar settings are invalid.'];
        }
        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Color:int,Unit:string,Decimals:int}> */
    private function GetValidatedSources(?string $draft = null): array
    {
        return EChartsCurrentComparisonSources::Resolve($draft ?? $this->ReadPropertyString('Sources'), 'PictorialBar', 100);
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        if (($error = $this->GetConfigurationError()) !== null) {
            return $this->ErrorState($error['Status'] === self::STATUS_CONFIGURATION_INVALID ? $error['Message'] : 'Configure valid PictorialBar variables.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetPictorialBarDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState($this->ReadAttributeString('LastError') === 'PictorialBar requires non-negative values.'
                ? 'PictorialBar requires non-negative values.' : 'The PictorialBar values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'pictorialBar', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'pictorialBar', 'status' => 'error', 'chart' => null, 'error' => $message];
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
            $this->ScheduleTransientRefresh($states, 'The PictorialBar values could not be loaded.');
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
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme')) || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'Orientation'), ['vertical', 'horizontal'], true)
            || !in_array($this->ReadPropertyString($prefix . 'SortOrder'), ['configured', 'ascending', 'descending'], true)
            || !in_array($this->ReadPropertyString($prefix . 'FontFamily'), ['Arial, sans-serif', 'Verdana, sans-serif', 'Georgia, serif', 'monospace'], true)
            || !in_array($this->ReadPropertyString($prefix . 'ValuePosition'), ['outside', 'inside', 'insideStart', 'insideEnd'], true)
            || !in_array($this->ReadPropertyString($prefix . 'ColorMode'), ['category', 'value', 'gradient'], true)
            || !in_array($this->ReadPropertyString($prefix . 'GridType'), ['solid', 'dashed', 'dotted'], true)
            || !in_array($this->ReadPropertyString($prefix . 'BorderType'), ['solid', 'dashed', 'dotted'], true)
            || !in_array($this->ReadPropertyString($prefix . 'Focus'), ['none', 'self'], true)
            || !in_array($this->ReadPropertyString($prefix . 'Symbol'), ['rect', 'roundRect', 'circle', 'diamond', 'triangle', 'icon', 'svg'], true)) {
            return false;
        }
        foreach (['SymbolSize'        => [4, 40], 'SymbolGap' => [0, 20], 'BarWidth' => [4, 80], 'ScaleMaximum' => [0, 1000000000],
            'TitleFontSizePercent'    => [50, 200], 'AxisFontSizePercent' => [50, 200], 'ValueFontSizePercent' => [50, 200],
            'BorderWidth'             => [0, 5], 'OpacityPercent' => [0, 100], 'ValueDistance' => [0, 30],
            'CategoryLabelRotate'     => [-90, 90], 'GridWidth' => [0, 5], 'GridOpacityPercent' => [0, 100],
            'LabelColumnWidthPercent' => [0, 80],
            'ShadowBlur'              => [0, 20], 'ShadowOffsetX' => [-20, 20], 'ShadowOffsetY' => [-20, 20],
            'BlurOpacityPercent'      => [0, 100]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'ValueColor', 'GridColor', 'BorderColor', 'ShadowColor', 'LowColor', 'MiddleColor', 'HighColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < (in_array($name, ['LowColor', 'MiddleColor', 'HighColor'], true) ? 0 : -1) || $color > 0xFFFFFF) {
                return false;
            }
        }
        $minimum = $this->ReadPropertyFloat($prefix . 'ColorMinimum');
        $maximum = $this->ReadPropertyFloat($prefix . 'ColorMaximum');
        return is_finite($minimum) && is_finite($maximum) && $minimum >= 0 && $maximum <= 1.0e12 && $minimum < $maximum;
    }

    /** @return array<string,mixed> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = [...$this->ReadAnimationStyle($prefix)];
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if (in_array($name, ['EChartsTheme', 'CustomSymbolSVG'], true)) {
                continue;
            }
            $value = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($prefix . $name),
                'integer' => $this->ReadPropertyInteger($prefix . $name),
                'double'  => $this->ReadPropertyFloat($prefix . $name),
                default   => $this->ReadPropertyString($prefix . $name)
            };
            $style[lcfirst($name)] = str_ends_with($name, 'Color')
                ? ($value < 0 ? '' : EChartsAsset::ColorToHex($value)) : $value;
        }
        if (in_array($style['symbol'], ['icon', 'svg'], true)) {
            $icon = $style['symbol'] === 'icon' ? $this->ReadSymconIcon($style['symbolIcon'])
                : PictorialBarSvg::Import($this->ReadPropertyString($prefix . 'CustomSymbolSVG'));
            $style['symbolPath'] = $icon['path'];
            $style['symbolWidth'] = $icon['width'];
            $style['symbolHeight'] = $icon['height'];
            if ($style['symbol'] === 'svg') {
                $style['symbolMinimumX'] = $icon['minimumX'];
                $style['symbolMinimumY'] = $icon['minimumY'];
            }
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
            '#echarts-pictorial-bar-root',
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
                    } elseif (($item['type'] ?? null) === 'Label') {
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
            throw new RuntimeException('The PictorialBar Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECPB_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }
}
