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
use SymconECharts\EChartsGaugeChronographPreview;
use SymconECharts\EChartsGaugeDesign;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
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
require_once __DIR__ . '/../libs/EChartsGaugeDesign.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/GaugePreview.php';

class EChartsGaugeChronograph extends IPSModuleStrict
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

    private const MINIMUM_SOURCE_COUNT = 2;
    private const MAXIMUM_SOURCE_COUNT = 5;

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_DESIGN_INVALID = 205;

    private const PRESET_CHRONOGRAPH = 'chronograph';
    private const SUPPORTED_PRESETS = [
        self::PRESET_CHRONOGRAPH
    ];
    private const FIXED_PRESET = self::PRESET_CHRONOGRAPH;
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewGauge';
    private const DESIGN_SCALE_PROPERTIES = [
        'RingWidthPercent', 'ScaleFontSizePercent', 'ValueFontSizePercent', 'TitleFontSizePercent',
        'PointerWidthPercent', 'PointerLengthPercent', 'AnchorSizePercent', 'AnchorBorderWidthPercent',
        'PlateSizePercent', 'PlateBorderWidthPercent'
    ];
    private const DESIGN_STRING_DEFAULTS = [
        'PointerShape'    => 'preset',
        'AnchorShape'     => 'preset',
        'GaugeColorMode'  => 'theme',
        'PlateDesignMode' => 'preset'
    ];
    private const DESIGN_INTEGER_DEFAULTS = [
        'MajorSplitCount'   => 0,
        'MinorSplitCount'   => 0,
        'PointerColor'      => 0x55CBB5,
        'ProgressColor'     => 0x55CBB5,
        'AnchorColor'       => 0x55CBB5,
        'AnchorBorderColor' => 0xF4F5F7,
        'RingColor'         => 0x45474C,
        'ScaleColor'        => 0xA7A9AE,
        'ValueColor'        => 0xF4F5F7,
        'TitleColor'        => 0xA7A9AE,
        'PlateColor'        => 0x25272B,
        'PlateBorderColor'  => 0xA5A9B0
    ];
    private const DESIGN_ASSET_STRING_DEFAULTS = [
        'CustomPointerSVG'       => '',
        'CustomPointerPivotMode' => 'svg',
        'CustomAnchorSVG'        => '',
        'PlateBackgroundSVG'     => '',
        'PlateBackgroundFit'     => 'cover'
    ];
    private const DESIGN_ASSET_FLOAT_DEFAULTS = [
        'CustomPointerPivotXPercent' => 50.0,
        'CustomPointerPivotYPercent' => 100.0,
        'PlateBackgroundRotation'    => 0.0
    ];
    private const DESIGN_ASSET_BOOLEAN_DEFAULTS = [
        'PlateBackgroundEnabled' => false
    ];
    private const DESIGN_ASSET_INTEGER_DEFAULTS = [
        'PlateBackgroundSizePercent'    => 100,
        'PlateBackgroundOffsetXPercent' => 0,
        'PlateBackgroundOffsetYPercent' => 0,
        'PlateBackgroundOpacityPercent' => 100
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('GaugePreset', self::PRESET_CHRONOGRAPH);
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $this->RegisterPropertyInteger($name, 100);
        }
        foreach (self::DESIGN_STRING_DEFAULTS as $name => $default) {
            $this->RegisterPropertyString($name, $default);
        }
        foreach (self::DESIGN_INTEGER_DEFAULTS as $name => $default) {
            $this->RegisterPropertyInteger($name, $default);
        }
        foreach (self::DESIGN_ASSET_STRING_DEFAULTS as $name => $default) {
            $this->RegisterPropertyString($name, $default);
        }
        foreach (self::DESIGN_ASSET_FLOAT_DEFAULTS as $name => $default) {
            $this->RegisterPropertyFloat($name, $default);
        }
        foreach (self::DESIGN_ASSET_BOOLEAN_DEFAULTS as $name => $default) {
            $this->RegisterPropertyBoolean($name, $default);
        }
        foreach (self::DESIGN_ASSET_INTEGER_DEFAULTS as $name => $default) {
            $this->RegisterPropertyInteger($name, $default);
        }
        $this->RegisterIPSViewHTMLPageProperties();
        $this->RegisterEChartsIPSViewTransport();
        $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
        $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
        $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
        $this->RegisterPropertyInteger(
            'IPSViewBackgroundOpacityPercent',
            EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT
        );
        $this->RegisterPropertyString('IPSViewGaugePreset', self::PRESET_CHRONOGRAPH);
        $this->RegisterPropertyString('IPSViewEChartsTheme', EChartsAsset::THEME_AUTO);
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $this->RegisterPropertyInteger('IPSView' . $name, 100);
        }
        foreach (self::DESIGN_STRING_DEFAULTS as $name => $default) {
            $this->RegisterPropertyString('IPSView' . $name, $default);
        }
        foreach (self::DESIGN_INTEGER_DEFAULTS as $name => $default) {
            $this->RegisterPropertyInteger('IPSView' . $name, $default);
        }
        foreach (self::DESIGN_ASSET_STRING_DEFAULTS as $name => $default) {
            $this->RegisterPropertyString('IPSView' . $name, $default);
        }
        foreach (self::DESIGN_ASSET_FLOAT_DEFAULTS as $name => $default) {
            $this->RegisterPropertyFloat('IPSView' . $name, $default);
        }
        foreach (self::DESIGN_ASSET_BOOLEAN_DEFAULTS as $name => $default) {
            $this->RegisterPropertyBoolean('IPSView' . $name, $default);
        }
        foreach (self::DESIGN_ASSET_INTEGER_DEFAULTS as $name => $default) {
            $this->RegisterPropertyInteger('IPSView' . $name, $default);
        }
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Gauge for IPSView'), 90);
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
            $form['elements'] = EChartsGaugeDesign::WithSourceEditor(
                $form['elements'],
                $this->GaugePreviewFormAction('add'),
                $this->GaugePreviewFormAction('edit'),
                $this->GaugePreviewFormAction('delete')
            );
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = EChartsGaugeDesign::WithoutPresetSelectors($form['elements']);
            $form['elements'] = $this->AttachGaugePreviewActions($form['elements']);
        }
        $form = SVGPreviewHelper::withImage(
            $form,
            'GaugePreview',
            EChartsGaugeChronographPreview::CreateSvg(
                $this->PreviewItems(),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('EChartsTheme'),
                self::FIXED_PRESET,
                $this->ReadGaugeStyle()
            )
        );
        $form = SVGPreviewHelper::withImage(
            $form,
            'IPSViewGaugePreview',
            EChartsGaugeChronographPreview::CreateSvg(
                $this->PreviewItems(!$this->ReadPropertyBoolean('IPSViewUseTileDesign')),
                $this->ReadPropertyString('Title'),
                $this->EffectiveIPSViewTheme(),
                $this->ReadPropertyBoolean('IPSViewUseTileDesign')
                    ? self::FIXED_PRESET
                    : self::FIXED_PRESET,
                EChartsIPSViewBackground::WithPreviewStyle(
                    $this->ReadPropertyBoolean('IPSViewUseTileDesign')
                        ? $this->ReadGaugeStyle()
                        : $this->ReadGaugeStyle('IPSView'),
                    $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                    $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                    $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
                )
            )
        );

        return $this->EncodeConfigurationForm($this->WithIPSViewDesignFormState($form, 'ECGC'));
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
        IPS_SetProperty($this->InstanceID, 'IPSViewGaugePreset', self::FIXED_PRESET);
        IPS_SetProperty($this->InstanceID, 'IPSViewEChartsTheme', $this->ReadPropertyString('EChartsTheme'));
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyInteger($name));
        }
        foreach (self::DESIGN_STRING_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyString($name));
        }
        foreach (self::DESIGN_INTEGER_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyInteger($name));
        }
        foreach (self::DESIGN_ASSET_STRING_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyString($name));
        }
        foreach (self::DESIGN_ASSET_FLOAT_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyFloat($name));
        }
        foreach (self::DESIGN_ASSET_BOOLEAN_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyBoolean($name));
        }
        foreach (self::DESIGN_ASSET_INTEGER_DEFAULTS as $name => $_default) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyInteger($name));
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    /** Refreshes both SVG previews from one snapshot of the currently edited form values. */
    public function UpdateGaugePreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        if (!is_array($values)) {
            $values = [];
        }

        $items = $this->PreviewItemsFromForm($values['Sources'] ?? null);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $tileStyle = $this->GaugeStyleFromFormValues($values);
        $this->UpdateFormField('GaugePreview', 'image', SVGPreviewHelper::dataUri(
            EChartsGaugeChronographPreview::CreateSvg(
                $items,
                $title,
                (string) ($values['EChartsTheme'] ?? $this->ReadPropertyString('EChartsTheme')),
                self::FIXED_PRESET,
                $tileStyle
            )
        ));

        $useTileDesign = (bool) ($values['IPSViewUseTileDesign'] ?? true);
        $ipsViewItems = $this->PreviewItemsFromForm($values['Sources'] ?? null, !$useTileDesign);
        $this->UpdateFormField('IPSViewGaugePreview', 'image', SVGPreviewHelper::dataUri(
            EChartsGaugeChronographPreview::CreateSvg(
                $ipsViewItems,
                $title,
                $useTileDesign
                    ? (string) ($values['EChartsTheme'] ?? $this->ReadPropertyString('EChartsTheme'))
                    : (string) ($values['IPSViewEChartsTheme'] ?? $this->ReadPropertyString('IPSViewEChartsTheme')),
                $useTileDesign
                    ? self::FIXED_PRESET
                    : self::FIXED_PRESET,
                EChartsIPSViewBackground::WithPreviewStyle(
                    $useTileDesign ? $tileStyle : $this->GaugeStyleFromFormValues($values, 'IPSView'),
                    (bool) ($values['IPSViewAdaptToBackground']
                        ?? $this->ReadPropertyBoolean('IPSViewAdaptToBackground')),
                    (int) ($values['IPSViewBackgroundColor']
                        ?? $this->ReadPropertyInteger('IPSViewBackgroundColor')),
                    (int) ($values['IPSViewBackgroundOpacityPercent']
                        ?? $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent'))
                )
            )
        ));
    }

    public function UpdateGaugePreviewSourceFromForm(string $Configuration, string $Action): void
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
        $this->UpdateGaugePreviewFromForm(json_encode($values, JSON_THROW_ON_ERROR));
    }

    public function GetGaugeData(): string
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            throw new RuntimeException($configurationError['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $items = [];
        foreach ($this->GetValidatedSources() as $source) {
            $current = $this->ReadCurrentSource($source['VariableID']);
            $label = $source['Label'] !== '' ? $source['Label'] : IPS_GetName($source['VariableID']);
            $items[] = [
                'id'     => 'variable-' . $source['VariableID'],
                'source' => [
                    'variableID' => $source['VariableID'],
                    'timestamp'  => $current['Timestamp']
                ],
                'gauge'  => [
                    'label'    => $label,
                    'minimum'  => $source['Minimum'],
                    'maximum'  => $source['Maximum'],
                    'unit'     => $source['Unit'],
                    'decimals' => $source['Decimals']
                ],
                'value'  => $current['Value'],
                'style'  => $source['Style']
            ];
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'chronograph',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'gauge'         => [
                'title'  => $this->ReadPropertyString('Title'),
                'preset' => self::FIXED_PRESET,
                'style'  => $this->ReadGaugeStyle()
            ],
            'items'         => $items
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderGaugeHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderGaugeHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    /**
     * Revalidates the module after the Symcon kernel becomes ready.
     *
     * @param array<int, mixed> $Data
     */
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

    private function RenderGaugeHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Gauge Chronograph',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure 2 to 5 unique numeric sources.' => $this->Translate(
                    'Configure 2 to 5 unique numeric sources.'
                ),
                'Configure a valid Chronograph Gauge design.' => $this->Translate(
                    'Configure a valid Chronograph Gauge design.'
                ),
                'Connect an active EChartsGateway.' => $this->Translate(
                    'Connect an active EChartsGateway.'
                ),
                'The Chronograph Gauge values could not be loaded.' => $this->Translate(
                    'The Chronograph Gauge values could not be loaded.'
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::JavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
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

        $this->SetSummary('');
        $this->SynchronizeSourceReferences();

        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            return;
        }

        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return;
        }

        $sources = $this->GetValidatedSources();
        $this->WriteAttributeString('LastError', '');
        $this->SetSummary(count($sources) . ' sources');
        $this->SetStatus(IS_ACTIVE);
    }

    /**
     * Returns the first invalid configuration field and its module status.
     *
     * @return array{Status: int, Message: string}|null
     */
    private function GetConfigurationError(): ?array
    {
        try {
            $sources = $this->GetValidatedSources();
        } catch (UnexpectedValueException $exception) {
            $status = $exception->getCode();
            if (!in_array($status, [
                self::STATUS_SOURCE_INVALID, self::STATUS_RANGE_INVALID, self::STATUS_DESIGN_INVALID
            ], true)) {
                $status = self::STATUS_SOURCE_INVALID;
            }

            return [
                'Status'  => $status,
                'Message' => $exception->getMessage()
            ];
        }

        if (!EChartsIPSViewBackground::IsValid(
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        )) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The IPSView background configuration is invalid.'
            ];
        }

        if (!in_array(self::FIXED_PRESET, self::SUPPORTED_PRESETS, true)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Chronograph Gauge design is not supported.'
            ];
        }
        $designError = $this->ValidateGaugeDesign();
        if ($designError !== null) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => $designError
            ];
        }

        if ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            if (!in_array(self::FIXED_PRESET, self::SUPPORTED_PRESETS, true)
                || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('IPSViewEChartsTheme'))) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'The selected IPSView Chronograph Gauge design is not supported.'
                ];
            }
            $designError = $this->ValidateGaugeDesign('IPSView');
            if ($designError !== null) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'IPSView ' . $designError
                ];
            }
        }

        return null;
    }

    private function ValidateGaugeDesign(string $prefix = ''): ?string
    {
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 150) {
                return 'Chronograph Gauge design scale values must be between 50 and 150 percent.';
            }
        }

        if (!in_array($this->ReadPropertyString($prefix . 'PointerShape'), EChartsGaugeDesign::POINTER_SHAPES, true)
            || !in_array($this->ReadPropertyString($prefix . 'AnchorShape'), ['preset', 'circle', 'ring', 'custom', 'none'], true)
            || !in_array($this->ReadPropertyString($prefix . 'GaugeColorMode'), ['theme', 'custom'], true)
            || !in_array($this->ReadPropertyString($prefix . 'PlateDesignMode'), ['preset', 'custom', 'hidden'], true)
            || !in_array(
                $this->ReadPropertyString($prefix . 'CustomPointerPivotMode'),
                EChartsGaugeDesign::POINTER_PIVOT_MODES,
                true
            )
            || !in_array(
                $this->ReadPropertyString($prefix . 'PlateBackgroundFit'),
                EChartsGaugeDesign::PLATE_BACKGROUND_FITS,
                true
            )) {
            return 'The selected Chronograph Gauge element design is not supported.';
        }

        $majorSplitCount = $this->ReadPropertyInteger($prefix . 'MajorSplitCount');
        $minorSplitCount = $this->ReadPropertyInteger($prefix . 'MinorSplitCount');
        if (($majorSplitCount !== 0 && ($majorSplitCount < 2 || $majorSplitCount > 24))
            || $minorSplitCount < 0 || $minorSplitCount > 10) {
            return 'Chronograph Gauge scale divisions are invalid.';
        }
        foreach (array_keys(array_filter(
            self::DESIGN_INTEGER_DEFAULTS,
            static fn (int $default, string $name): bool => str_ends_with($name, 'Color'),
            ARRAY_FILTER_USE_BOTH
        )) as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < 0 || $color > 0xFFFFFF) {
                return 'Chronograph Gauge colors must be valid RGB colors.';
            }
        }

        if ($this->ReadPropertyString($prefix . 'PointerShape') === 'custom') {
            try {
                EChartsGaugeDesign::ImportPointer(
                    $this->ReadPropertyString($prefix . 'CustomPointerSVG'),
                    $this->ReadPropertyString($prefix . 'CustomPointerPivotMode'),
                    $this->ReadPropertyFloat($prefix . 'CustomPointerPivotXPercent'),
                    $this->ReadPropertyFloat($prefix . 'CustomPointerPivotYPercent')
                );
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }
        }

        if ($this->ReadPropertyString($prefix . 'AnchorShape') === 'custom') {
            try {
                EChartsGaugeDesign::ImportAnchor($this->ReadPropertyString($prefix . 'CustomAnchorSVG'));
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }
        }

        if ($this->ReadPropertyBoolean($prefix . 'PlateBackgroundEnabled')) {
            try {
                EChartsGaugeDesign::ImportPlateBackground(
                    $this->ReadPropertyString($prefix . 'PlateBackgroundSVG'),
                    $this->ReadPropertyString($prefix . 'PlateBackgroundFit'),
                    $this->ReadPropertyInteger($prefix . 'PlateBackgroundSizePercent'),
                    $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetXPercent'),
                    $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetYPercent'),
                    $this->ReadPropertyInteger($prefix . 'PlateBackgroundOpacityPercent'),
                    $this->ReadPropertyFloat($prefix . 'PlateBackgroundRotation')
                );
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }
        }

        return null;
    }

    /**
     * @return list<array{
     *     VariableID: int,
     *     Label: string,
     *     Minimum: float,
     *     Maximum: float,
     *     Unit: string,
     *     Decimals: int,
     *     UseVariablePresentation: bool
     * }>
     */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode(
                $this->ReadPropertyString('Sources'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new UnexpectedValueException(
                'Gauge sources must contain valid JSON.',
                self::STATUS_SOURCE_INVALID,
                $exception
            );
        }

        if (!is_array($sources) || !array_is_list($sources)) {
            throw new UnexpectedValueException(
                'Gauge sources must be a JSON list.',
                self::STATUS_SOURCE_INVALID
            );
        }

        $sourceCount = count($sources);
        if ($sourceCount < self::MINIMUM_SOURCE_COUNT || $sourceCount > self::MAXIMUM_SOURCE_COUNT) {
            throw new UnexpectedValueException(
                'Configure between 2 and 5 Gauge sources.',
                self::STATUS_SOURCE_INVALID
            );
        }

        $validatedSources = [];
        $variableIDs = [];
        foreach ($sources as $index => $source) {
            $sourceNumber = $index + 1;
            if (!is_array($source)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' is invalid.',
                    self::STATUS_SOURCE_INVALID
                );
            }

            $variableID = $source['VariableID'] ?? null;
            $label = $source['Label'] ?? '';
            $minimum = $source['Minimum'] ?? null;
            $maximum = $source['Maximum'] ?? null;
            $unit = $source['Unit'] ?? '';
            $decimals = $source['Decimals'] ?? null;
            $useVariablePresentation = $source['UseVariablePresentation'] ?? false;
            $useIndividualDesign = $source['UseIndividualDesign'] ?? false;
            $ipsViewUseTileDesign = $source['IPSViewUseTileDesign'] ?? true;

            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' must reference an existing numeric variable.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if (isset($variableIDs[$variableID])) {
                throw new UnexpectedValueException(
                    'Gauge source variables must be unique.',
                    self::STATUS_SOURCE_INVALID
                );
            }

            $variable = IPS_GetVariable($variableID);
            if (!in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' must reference a numeric variable.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if (!is_string($label) || !is_string($unit) || !is_bool($useVariablePresentation)
                || !is_bool($useIndividualDesign) || !is_bool($ipsViewUseTileDesign)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' contains invalid text fields.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if ((!is_int($minimum) && !is_float($minimum))
                || (!is_int($maximum) && !is_float($maximum))
                || $minimum >= $maximum
            ) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' has an invalid range.',
                    self::STATUS_RANGE_INVALID
                );
            }
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' decimals must be between 0 and 6.',
                    self::STATUS_RANGE_INVALID
                );
            }

            $effectiveConfiguration = $this->ResolveSourceConfiguration(
                $variableID,
                (float) $minimum,
                (float) $maximum,
                $unit,
                $decimals,
                $useVariablePresentation
            );

            $individualStyle = [];
            $individualIPSViewStyle = [];
            if ($useIndividualDesign) {
                try {
                    $individualStyle = EChartsGaugeDesign::StyleFromSource($source);
                    $individualIPSViewStyle = $ipsViewUseTileDesign
                        ? $individualStyle
                        : EChartsGaugeDesign::StyleFromSource($source, 'IPSView');
                } catch (InvalidArgumentException $exception) {
                    throw new UnexpectedValueException(
                        'Gauge source ' . $sourceNumber . ' contains an invalid individual design: '
                            . $exception->getMessage(),
                        self::STATUS_DESIGN_INVALID,
                        $exception
                    );
                }
            }

            $variableIDs[$variableID] = true;
            $validatedSources[] = [
                'VariableID'               => $variableID,
                'Label'                    => trim($label),
                'Minimum'                  => $effectiveConfiguration['minimum'],
                'Maximum'                  => $effectiveConfiguration['maximum'],
                'Unit'                     => $effectiveConfiguration['unit'],
                'Decimals'                 => $effectiveConfiguration['decimals'],
                'UseVariablePresentation'  => $useVariablePresentation,
                'UseIndividualDesign'      => $useIndividualDesign,
                'IPSViewUseTileDesign'     => $ipsViewUseTileDesign,
                'Style'                    => $individualStyle,
                'IPSViewStyle'             => $individualIPSViewStyle
            ];
        }

        return $validatedSources;
    }

    /** @return array{minimum: float, maximum: float, unit: string, decimals: int} */
    private function ResolveSourceConfiguration(
        int $variableID,
        float $minimum,
        float $maximum,
        string $unit,
        int $decimals,
        bool $useVariablePresentation
    ): array {
        return EChartsVariablePresentation::Resolve(
            $variableID,
            $minimum,
            $maximum,
            $unit,
            $decimals,
            $useVariablePresentation
        );
    }

    /**
     * @return array{Value: float, Timestamp: int}
     */
    private function ReadCurrentSource(int $variableID): array
    {
        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, [
            'VariableID' => $variableID
        ]);

        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(self::DATA_ID_TO_PARENT, $request)),
                $operation
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_GATEWAY_RESPONSE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            $this->SendDebug('GetGaugeData', $exception::class, 0);
            throw new RuntimeException('The gateway returned an invalid response.');
        }

        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway request failed: ' . $errorCode);
        }

        $payload = $response['Payload'];
        $responseVariableID = $payload['VariableID'] ?? null;
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if ($responseVariableID !== $variableID
            || (!is_int($value) && !is_float($value))
            || !is_int($timestamp)
        ) {
            $this->WriteAttributeString('LastError', 'INVALID_CURRENT_VALUE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        return [
            'Value'     => (float) $value,
            'Timestamp' => $timestamp
        ];
    }

    private function SynchronizeSourceReferences(): void
    {
        $previousVariableIDs = $this->DecodeVariableIDList(
            $this->ReadAttributeString('RegisteredSourceVariableIDs')
        );
        $variableIDs = $this->ConfiguredVariableIDs();
        $registeredVariableIDs = array_values(array_filter(
            $variableIDs,
            static fn (int $variableID): bool => IPS_VariableExists($variableID)
        ));

        foreach (array_diff($previousVariableIDs, $registeredVariableIDs) as $variableID) {
            $this->UnregisterReference($variableID);
            $this->UnregisterMessage($variableID, VM_UPDATE);
        }
        foreach (array_diff($registeredVariableIDs, $previousVariableIDs) as $variableID) {
            $this->RegisterReference($variableID);
        }
        foreach ($registeredVariableIDs as $variableID) {
            // Register idempotently so existing instances gain live updates after a module update.
            $this->RegisterMessage($variableID, VM_UPDATE);
        }

        $this->WriteAttributeString(
            'RegisteredSourceVariableIDs',
            json_encode($registeredVariableIDs, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Returns the unique, positive variable IDs that can be recovered from the
     * current configuration, even if another source field is invalid.
     *
     * @return list<int>
     */
    private function ConfiguredVariableIDs(): array
    {
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }

        $variableIDs = [];
        foreach ($sources as $source) {
            $variableID = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $variableIDs, true)) {
                $variableIDs[] = $variableID;
            }
        }

        sort($variableIDs);

        return $variableIDs;
    }

    /**
     * @return list<int>
     */
    private function DecodeVariableIDList(string $json): array
    {
        $variableIDs = json_decode($json, true);
        if (!is_array($variableIDs) || !array_is_list($variableIDs)) {
            return [];
        }

        $result = [];
        foreach ($variableIDs as $variableID) {
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $result, true)) {
                $result[] = $variableID;
            }
        }

        sort($result);

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function PreviewItems(bool $ipsView = false): array
    {
        try {
            $sources = $this->GetValidatedSources();
        } catch (Throwable) {
            return [
                ['label' => 'Temperature', 'minimum' => 0.0, 'maximum' => 40.0, 'unit' => '°C', 'decimals' => 1, 'value' => 21.5],
                ['label' => 'Humidity', 'minimum' => 0.0, 'maximum' => 100.0, 'unit' => '%', 'decimals' => 0, 'value' => 54.0]
            ];
        }

        return array_map(static function (array $source) use ($ipsView): array
        {
            $value = GetValue($source['VariableID']);

            return [
                'label'    => $source['Label'] !== '' ? $source['Label'] : IPS_GetName($source['VariableID']),
                'minimum'  => $source['Minimum'],
                'maximum'  => $source['Maximum'],
                'unit'     => $source['Unit'],
                'decimals' => $source['Decimals'],
                'value'    => is_int($value) || is_float($value) ? (float) $value : $source['Minimum'],
                'style'    => $ipsView ? $source['IPSViewStyle'] : $source['Style']
            ];
        }, array_slice($sources, 0, self::MAXIMUM_SOURCE_COUNT));
    }

    /** @return list<array<string, mixed>> */
    private function PreviewItemsFromForm(mixed $sources, bool $ipsView = false): array
    {
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
        }
        if (!is_array($sources) || !array_is_list($sources)) {
            return $this->PreviewItems($ipsView);
        }

        $items = [];
        foreach (array_slice($sources, 0, self::MAXIMUM_SOURCE_COUNT) as $source) {
            if (!is_array($source)) {
                continue;
            }
            $variableID = (int) ($source['VariableID'] ?? 0);
            $minimum = (float) ($source['Minimum'] ?? 0.0);
            $maximum = (float) ($source['Maximum'] ?? 100.0);
            $value = $variableID > 0 && IPS_VariableExists($variableID) ? GetValue($variableID) : $minimum;
            $style = [];
            if (($source['UseIndividualDesign'] ?? false) === true) {
                try {
                    $style = $ipsView && (($source['IPSViewUseTileDesign'] ?? true) === false)
                        ? EChartsGaugeDesign::StyleFromSource($source, 'IPSView')
                        : EChartsGaugeDesign::StyleFromSource($source);
                } catch (InvalidArgumentException) {
                    $style = [];
                }
            }
            $items[] = [
                'label'    => trim((string) ($source['Label'] ?? '')) ?: ($variableID > 0 ? IPS_GetName($variableID) : 'Gauge'),
                'minimum'  => $minimum,
                'maximum'  => $maximum > $minimum ? $maximum : $minimum + 1.0,
                'unit'     => (string) ($source['Unit'] ?? ''),
                'decimals' => max(0, min(6, (int) ($source['Decimals'] ?? 1))),
                'value'    => is_int($value) || is_float($value) ? (float) $value : $minimum,
                'style'    => $style
            ];
        }

        return $items !== [] ? $items : $this->PreviewItems($ipsView);
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'chronograph',
                'status'        => 'error',
                'chart'         => null,
                'error'         => $configurationError['Status'] === self::STATUS_DESIGN_INVALID
                    ? 'Configure a valid Chronograph Gauge design.'
                    : 'Configure 2 to 5 unique numeric sources.'
            ];
        }
        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'chronograph',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }

        try {
            $chart = json_decode($this->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['gauge']['preset'] = self::FIXED_PRESET;
                $chart['gauge']['style'] = $this->ReadGaugeStyle('IPSView');
                foreach ($this->GetValidatedSources() as $index => $source) {
                    $chart['items'][$index]['style'] = $source['IPSViewStyle'];
                }
            }
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'chronograph',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Chronograph Gauge values could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'chronograph',
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
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
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

    private function EffectiveIPSViewTheme(): string
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');
    }

    /** @return array<string, mixed> */
    private function ReadGaugeStyle(string $prefix = ''): array
    {
        $style = [];
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $style[lcfirst($name)] = $this->ReadPropertyInteger($prefix . $name);
        }
        foreach (self::DESIGN_STRING_DEFAULTS as $name => $_default) {
            $fieldName = match ($name) {
                'GaugeColorMode'  => 'colorMode',
                'PlateDesignMode' => 'plateMode',
                default           => lcfirst($name)
            };
            $style[$fieldName] = $this->ReadPropertyString($prefix . $name);
        }
        foreach (self::DESIGN_INTEGER_DEFAULTS as $name => $_default) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            $style[lcfirst($name)] = str_ends_with($name, 'Color') ? self::ColorToHex($value) : $value;
        }

        return array_merge($style, $this->ResolveGaugeAssetStyle(
            $this->ReadPropertyString($prefix . 'PointerShape'),
            $this->ReadPropertyString($prefix . 'CustomPointerSVG'),
            $this->ReadPropertyString($prefix . 'CustomPointerPivotMode'),
            $this->ReadPropertyFloat($prefix . 'CustomPointerPivotXPercent'),
            $this->ReadPropertyFloat($prefix . 'CustomPointerPivotYPercent'),
            $this->ReadPropertyString($prefix . 'AnchorShape'),
            $this->ReadPropertyString($prefix . 'CustomAnchorSVG'),
            $this->ReadPropertyBoolean($prefix . 'PlateBackgroundEnabled'),
            $this->ReadPropertyString($prefix . 'PlateBackgroundSVG'),
            $this->ReadPropertyString($prefix . 'PlateBackgroundFit'),
            $this->ReadPropertyInteger($prefix . 'PlateBackgroundSizePercent'),
            $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetXPercent'),
            $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetYPercent'),
            $this->ReadPropertyInteger($prefix . 'PlateBackgroundOpacityPercent'),
            $this->ReadPropertyFloat($prefix . 'PlateBackgroundRotation')
        ));
    }

    private static function ColorToHex(int $color): string
    {
        return EChartsGaugeDesign::ColorToHex($color);
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function GaugeStyleFromFormValues(array $values, string $prefix = ''): array
    {
        $style = [];
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $style[lcfirst($name)] = (int) ($values[$prefix . $name]
                ?? $this->ReadPropertyInteger($prefix . $name));
        }
        foreach (self::DESIGN_STRING_DEFAULTS as $name => $_default) {
            $fieldName = match ($name) {
                'GaugeColorMode'  => 'colorMode',
                'PlateDesignMode' => 'plateMode',
                default           => lcfirst($name)
            };
            $style[$fieldName] = (string) ($values[$prefix . $name]
                ?? $this->ReadPropertyString($prefix . $name));
        }
        foreach (self::DESIGN_INTEGER_DEFAULTS as $name => $_default) {
            $value = (int) ($values[$prefix . $name] ?? $this->ReadPropertyInteger($prefix . $name));
            $style[lcfirst($name)] = str_ends_with($name, 'Color') ? self::ColorToHex($value) : $value;
        }

        return array_merge($style, $this->ResolveGaugeAssetStyle(
            (string) ($values[$prefix . 'PointerShape'] ?? $this->ReadPropertyString($prefix . 'PointerShape')),
            (string) ($values[$prefix . 'CustomPointerSVG'] ?? $this->ReadPropertyString($prefix . 'CustomPointerSVG')),
            (string) ($values[$prefix . 'CustomPointerPivotMode'] ?? $this->ReadPropertyString($prefix . 'CustomPointerPivotMode')),
            (float) ($values[$prefix . 'CustomPointerPivotXPercent'] ?? $this->ReadPropertyFloat($prefix . 'CustomPointerPivotXPercent')),
            (float) ($values[$prefix . 'CustomPointerPivotYPercent'] ?? $this->ReadPropertyFloat($prefix . 'CustomPointerPivotYPercent')),
            (string) ($values[$prefix . 'AnchorShape'] ?? $this->ReadPropertyString($prefix . 'AnchorShape')),
            (string) ($values[$prefix . 'CustomAnchorSVG'] ?? $this->ReadPropertyString($prefix . 'CustomAnchorSVG')),
            (bool) ($values[$prefix . 'PlateBackgroundEnabled'] ?? $this->ReadPropertyBoolean($prefix . 'PlateBackgroundEnabled')),
            (string) ($values[$prefix . 'PlateBackgroundSVG'] ?? $this->ReadPropertyString($prefix . 'PlateBackgroundSVG')),
            (string) ($values[$prefix . 'PlateBackgroundFit'] ?? $this->ReadPropertyString($prefix . 'PlateBackgroundFit')),
            (int) ($values[$prefix . 'PlateBackgroundSizePercent'] ?? $this->ReadPropertyInteger($prefix . 'PlateBackgroundSizePercent')),
            (int) ($values[$prefix . 'PlateBackgroundOffsetXPercent'] ?? $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetXPercent')),
            (int) ($values[$prefix . 'PlateBackgroundOffsetYPercent'] ?? $this->ReadPropertyInteger($prefix . 'PlateBackgroundOffsetYPercent')),
            (int) ($values[$prefix . 'PlateBackgroundOpacityPercent'] ?? $this->ReadPropertyInteger($prefix . 'PlateBackgroundOpacityPercent')),
            (float) ($values[$prefix . 'PlateBackgroundRotation'] ?? $this->ReadPropertyFloat($prefix . 'PlateBackgroundRotation'))
        ));
    }

    /** @return array<string, mixed> */
    private function ResolveGaugeAssetStyle(
        string $pointerShape,
        string $pointerFileData,
        string $pointerPivotMode,
        float $pointerPivotXPercent,
        float $pointerPivotYPercent,
        string $anchorShape,
        string $anchorFileData,
        bool $plateBackgroundEnabled,
        string $plateBackgroundFileData,
        string $plateBackgroundFit,
        int $plateBackgroundSizePercent,
        int $plateBackgroundOffsetXPercent,
        int $plateBackgroundOffsetYPercent,
        int $plateBackgroundOpacityPercent,
        float $plateBackgroundRotation
    ): array {
        $style = [];
        if ($pointerShape === 'custom') {
            try {
                $style = EChartsGaugeDesign::ImportPointer(
                    $pointerFileData,
                    $pointerPivotMode,
                    $pointerPivotXPercent,
                    $pointerPivotYPercent
                );
            } catch (InvalidArgumentException $exception) {
                $this->SendDebug('ResolveCustomPointerStyle', $exception->getMessage(), 0);
                $style = [
                    'pointerPath'    => '',
                    'pointerViewBox' => '',
                    'pointerError'   => $exception->getMessage()
                ];
            }
        }
        if ($anchorShape === 'custom') {
            try {
                $style = array_merge($style, EChartsGaugeDesign::ImportAnchor($anchorFileData));
            } catch (InvalidArgumentException $exception) {
                $this->SendDebug('ResolveCustomAnchorStyle', $exception->getMessage(), 0);
                $style = [
                    ...$style,
                    'anchorPath'    => '',
                    'anchorViewBox' => '',
                    'anchorError'   => $exception->getMessage()
                ];
            }
        }
        if (!$plateBackgroundEnabled) {
            return $style;
        }

        try {
            return array_merge($style, EChartsGaugeDesign::ImportPlateBackground(
                $plateBackgroundFileData,
                $plateBackgroundFit,
                $plateBackgroundSizePercent,
                $plateBackgroundOffsetXPercent,
                $plateBackgroundOffsetYPercent,
                $plateBackgroundOpacityPercent,
                $plateBackgroundRotation
            ));
        } catch (InvalidArgumentException $exception) {
            $this->SendDebug('ResolvePlateBackgroundStyle', $exception->getMessage(), 0);

            return array_merge($style, [
                'plateBackgroundEnabled'        => true,
                'plateBackgroundFit'            => $plateBackgroundFit,
                'plateBackgroundSizePercent'    => $plateBackgroundSizePercent,
                'plateBackgroundOffsetXPercent' => $plateBackgroundOffsetXPercent,
                'plateBackgroundOffsetYPercent' => $plateBackgroundOffsetYPercent,
                'plateBackgroundOpacityPercent' => $plateBackgroundOpacityPercent,
                'plateBackgroundRotation'       => $plateBackgroundRotation,
                'plateBackgroundImage'          => '',
                'plateBackgroundAspectRatio'    => 1.0,
                'plateBackgroundError'          => $exception->getMessage()
            ]);
        }
    }

    /** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
    private function AttachGaugePreviewActions(array $items): array
    {
        return EChartsGaugeDesign::WithPreviewActions(
            $items,
            $this->GaugePreviewFieldNames(),
            $this->GaugePreviewFormAction()
        );
    }

    private function GaugePreviewFormAction(string $sourceAction = ''): string
    {
        return EChartsGaugeDesign::PreviewFormAction('ECGC', $this->GaugePreviewFieldNames(), $sourceAction);
    }

    /** @return list<string> */
    private function GaugePreviewFieldNames(): array
    {
        return EChartsGaugeDesign::PreviewFieldNames([
            'Sources', 'Title', 'EChartsTheme', 'IPSViewUseTileDesign',
            'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent',
            'IPSViewEChartsTheme'
        ], $this->GaugeDesignFieldNames());
    }

    /** @return list<string> */
    private function GaugeDesignFieldNames(): array
    {
        return [
            ...self::DESIGN_SCALE_PROPERTIES,
            ...array_keys(self::DESIGN_STRING_DEFAULTS), ...array_keys(self::DESIGN_INTEGER_DEFAULTS),
            ...array_keys(self::DESIGN_ASSET_STRING_DEFAULTS), ...array_keys(self::DESIGN_ASSET_FLOAT_DEFAULTS),
            ...array_keys(self::DESIGN_ASSET_BOOLEAN_DEFAULTS), ...array_keys(self::DESIGN_ASSET_INTEGER_DEFAULTS)
        ];
    }

    private function IPSViewThemeCSS(): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($this->EffectiveIPSViewTheme());

        return EChartsIPSViewBackground::ThemeCSS(
            $palette,
            '#echarts-gauge-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string, mixed>> $elements @return array<string, mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        return EChartsGaugeDesign::IPSViewDesignerForm(
            $elements,
            $this->IPSViewHTMLPageFormItems(
                'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
            ),
            ['GaugePreset', 'EChartsTheme', ...$this->GaugeDesignFieldNames()],
            $this->GaugePreviewFormAction(),
            'ECGC_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'
        );
    }
}
