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
use SymconECharts\EChartsGaugeDesign;
use SymconECharts\EChartsGaugeSinglePreview;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSvgPath;
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
require_once __DIR__ . '/../libs/EChartsSvgPath.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/GaugePreview.php';

class EChartsGaugeSingle extends IPSModuleStrict
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

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_DESIGN_INVALID = 205;
    private const STATUS_THEME_INVALID = 206;

    private const PRESET_SIMPLE = 'simple';
    private const SUPPORTED_PRESETS = ['basic', self::PRESET_SIMPLE, 'progress', 'speed'];
    private const SUPPORTED_ANCHOR_SHAPES = ['preset', 'circle', 'ring', 'custom', 'hidden'];
    private const SUPPORTED_ANCHOR_COLOR_MODES = ['theme', 'custom'];
    private const SUPPORTED_PLATE_SHAPES = ['hidden', 'circle', 'arc'];
    private const SUPPORTED_PLATE_COLOR_MODES = ['theme', 'custom'];
    private const SUPPORTED_PLATE_FILL_MODES = ['solid', 'linear', 'radial'];
    private const SUPPORTED_PLATE_GRADIENT_DIRECTIONS = [
        'top-bottom',
        'left-right',
        'diagonal-down',
        'diagonal-up'
    ];
    private const SUPPORTED_ARC_MODES = ['preset', 'full', 'three-quarter', 'half', 'quarter', 'custom'];
    private const SUPPORTED_COLOR_MODES = ['theme', 'custom'];
    private const SUPPORTED_VISIBILITY_MODES = ['preset', 'show', 'hide'];
    private const SUPPORTED_GAUGE_DIRECTIONS = ['clockwise', 'counterclockwise'];
    private const SUPPORTED_SCALE_LABEL_ROTATIONS = ['horizontal', 'tangential', 'radial'];
    private const SUPPORTED_TITLE_POSITIONS = ['top', 'bottom'];
    private const ANGLE_STEP = 22.5;
    private const DESIGN_SCALE_DEFAULT = 100;
    private const DESIGN_SCALE_MINIMUM = 50;
    private const DESIGN_SCALE_MAXIMUM = 150;
    private const DESIGN_SCALE_PROPERTIES = [
        'scaleFontSizePercent'      => 'ScaleFontSizePercent',
        'valueFontSizePercent'      => 'ValueFontSizePercent',
        'unitFontSizePercent'       => 'UnitFontSizePercent',
        'titleFontSizePercent'      => 'TitleFontSizePercent',
        'ringWidthPercent'          => 'RingWidthPercent',
        'pointerWidthPercent'       => 'PointerWidthPercent',
        'pointerLengthPercent'      => 'PointerLengthPercent',
        'anchorSizePercent'         => 'AnchorSizePercent',
        'anchorBorderWidthPercent'  => 'AnchorBorderWidthPercent',
        'plateSizePercent'          => 'PlateSizePercent',
        'plateBorderWidthPercent'   => 'PlateBorderWidthPercent',
        'minorTickLengthPercent'    => 'MinorTickLengthPercent',
        'majorTickLengthPercent'    => 'MajorTickLengthPercent',
        'progressWidthPercent'      => 'ProgressWidthPercent',
        'minorTickDistancePercent'  => 'MinorTickDistancePercent',
        'majorTickDistancePercent'  => 'MajorTickDistancePercent',
        'scaleLabelDistancePercent' => 'ScaleLabelDistancePercent',
        'gaugeRadiusPercent'        => 'GaugeRadiusPercent',
        'detailBorderWidthPercent'  => 'DetailBorderWidthPercent',
        'detailCornerRadiusPercent' => 'DetailCornerRadiusPercent'
    ];
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewGauge';
    private const IPSVIEW_PROPERTY_PREFIX = 'IPSView';
    private const IPSVIEW_STRING_DESIGN_DEFAULTS = [
        'TitlePosition'           => 'bottom',
        'GaugePreset'             => self::PRESET_SIMPLE,
        'EChartsTheme'            => EChartsAsset::THEME_AUTO,
        'PointerShape'            => 'preset',
        'CustomPointerSVG'        => '',
        'CustomPointerPivotMode'  => 'svg',
        'AnchorShape'             => 'preset',
        'CustomAnchorSVG'         => '',
        'AnchorColorMode'         => 'theme',
        'PlateShape'              => 'hidden',
        'PlateColorMode'          => 'theme',
        'PlateFillMode'           => 'solid',
        'PlateGradientDirection'  => 'top-bottom',
        'PlateBackgroundSVG'      => '',
        'PlateBackgroundFit'      => 'cover',
        'GaugeArcMode'            => 'preset',
        'GaugeColorMode'          => 'theme',
        'ScaleZones'              => '[]',
        'ScaleLabelRotation'      => 'horizontal',
        'GaugeDirection'          => 'clockwise',
        'PointerVisibility'       => 'preset',
        'ProgressVisibility'      => 'preset',
        'RingVisibility'          => 'preset',
        'MinorTicksVisibility'    => 'preset',
        'MajorTicksVisibility'    => 'preset',
        'ScaleLabelsVisibility'   => 'preset',
        'ValueVisibility'         => 'preset',
        'UnitVisibility'          => 'preset',
        'TitleVisibility'         => 'preset',
        'DetailBoxVisibility'     => 'preset',
        'DetailColorMode'         => 'theme'
    ];
    private const IPSVIEW_FLOAT_DESIGN_DEFAULTS = [
        'CustomPointerPivotXPercent' => 50.0,
        'CustomPointerPivotYPercent' => 100.0,
        'PlateBackgroundRotation'    => 0.0,
        'GaugeStartPosition'         => 270.0,
        'GaugeEndPosition'           => 90.0
    ];
    private const IPSVIEW_BOOLEAN_DESIGN_DEFAULTS = [
        'PlateGradientMiddleEnabled' => false,
        'PlateBackgroundEnabled'     => false,
        'PlateTransparent'           => false,
        'PlateShadow'                => false,
        'ScaleZonesEnabled'          => false,
        'DetailShadow'               => false,
        'PointerShadow'              => false,
        'ProgressShadow'             => false,
        'RingShadow'                 => false,
        'AnchorShadow'               => false
    ];
    private const IPSVIEW_INTEGER_DESIGN_DEFAULTS = [
        'AnchorColor'                    => 0x55CBB5,
        'AnchorBorderColor'              => 0xF4F5F7,
        'PlateGradientMiddleColor'       => 0x45474C,
        'PlateGradientEndColor'          => 0x111317,
        'PlateGradientCenterXPercent'    => 50,
        'PlateGradientCenterYPercent'    => 50,
        'PlateGradientRadiusPercent'     => 75,
        'PlateBackgroundSizePercent'     => 100,
        'PlateBackgroundOffsetXPercent'  => 0,
        'PlateBackgroundOffsetYPercent'  => 0,
        'PlateBackgroundOpacityPercent'  => 100,
        'PlateColor'                     => 0x25272B,
        'PlateBorderColor'               => 0xA5A9B0,
        'PointerColor'                   => 0x55CBB5,
        'ProgressColor'                  => 0x55CBB5,
        'RingColor'                      => 0x45474C,
        'ScaleColor'                     => 0xA7A9AE,
        'ValueColor'                     => 0xF4F5F7,
        'TitleColor'                     => 0xA7A9AE,
        'MajorSplitCount'                => 0,
        'MinorSplitCount'                => 0,
        'GaugeOffsetXPercent'            => 0,
        'GaugeOffsetYPercent'            => 0,
        'ValueOffsetXPercent'            => 0,
        'ValueOffsetYPercent'            => 0,
        'TitleOffsetXPercent'            => 0,
        'TitleOffsetYPercent'            => 0,
        'DetailBackgroundColor'          => 0x25272B,
        'DetailBorderColor'              => 0xA5A9B0
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyBoolean('UseVariablePresentation', false);
        $this->RegisterPropertyFloat('Minimum', 0.0);
        $this->RegisterPropertyFloat('Maximum', 100.0);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('TitlePosition', 'bottom');
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
        $this->RegisterPropertyString('GaugePreset', self::PRESET_SIMPLE);
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyString('PointerShape', 'preset');
        $this->RegisterPropertyString('CustomPointerSVG', '');
        $this->RegisterPropertyString('CustomPointerPivotMode', 'svg');
        $this->RegisterPropertyFloat('CustomPointerPivotXPercent', 50.0);
        $this->RegisterPropertyFloat('CustomPointerPivotYPercent', 100.0);
        $this->RegisterPropertyString('AnchorShape', 'preset');
        $this->RegisterPropertyString('CustomAnchorSVG', '');
        $this->RegisterPropertyString('AnchorColorMode', 'theme');
        $this->RegisterPropertyInteger('AnchorColor', 0x55CBB5);
        $this->RegisterPropertyInteger('AnchorBorderColor', 0xF4F5F7);
        $this->RegisterPropertyString('PlateShape', 'hidden');
        $this->RegisterPropertyString('PlateColorMode', 'theme');
        $this->RegisterPropertyString('PlateFillMode', 'solid');
        $this->RegisterPropertyBoolean('PlateGradientMiddleEnabled', false);
        $this->RegisterPropertyInteger('PlateGradientMiddleColor', 0x45474C);
        $this->RegisterPropertyInteger('PlateGradientEndColor', 0x111317);
        $this->RegisterPropertyString('PlateGradientDirection', 'top-bottom');
        $this->RegisterPropertyInteger('PlateGradientCenterXPercent', 50);
        $this->RegisterPropertyInteger('PlateGradientCenterYPercent', 50);
        $this->RegisterPropertyInteger('PlateGradientRadiusPercent', 75);
        $this->RegisterPropertyBoolean('PlateBackgroundEnabled', false);
        $this->RegisterPropertyString('PlateBackgroundSVG', '');
        $this->RegisterPropertyString('PlateBackgroundFit', 'cover');
        $this->RegisterPropertyInteger('PlateBackgroundSizePercent', 100);
        $this->RegisterPropertyInteger('PlateBackgroundOffsetXPercent', 0);
        $this->RegisterPropertyInteger('PlateBackgroundOffsetYPercent', 0);
        $this->RegisterPropertyInteger('PlateBackgroundOpacityPercent', 100);
        $this->RegisterPropertyFloat('PlateBackgroundRotation', 0.0);
        $this->RegisterPropertyBoolean('PlateTransparent', false);
        $this->RegisterPropertyBoolean('PlateShadow', false);
        $this->RegisterPropertyInteger('PlateColor', 0x25272B);
        $this->RegisterPropertyInteger('PlateBorderColor', 0xA5A9B0);
        $this->RegisterPropertyString('GaugeArcMode', 'preset');
        $this->RegisterPropertyFloat('GaugeStartPosition', 270.0);
        $this->RegisterPropertyFloat('GaugeEndPosition', 90.0);
        $this->RegisterPropertyString('GaugeColorMode', 'theme');
        $this->RegisterPropertyInteger('PointerColor', 0x55CBB5);
        $this->RegisterPropertyInteger('ProgressColor', 0x55CBB5);
        $this->RegisterPropertyInteger('RingColor', 0x45474C);
        $this->RegisterPropertyInteger('ScaleColor', 0xA7A9AE);
        $this->RegisterPropertyInteger('ValueColor', 0xF4F5F7);
        $this->RegisterPropertyInteger('TitleColor', 0xA7A9AE);
        $this->RegisterPropertyBoolean('ScaleZonesEnabled', false);
        $this->RegisterPropertyString('ScaleZones', '[]');
        $this->RegisterPropertyInteger('MajorSplitCount', 0);
        $this->RegisterPropertyInteger('MinorSplitCount', 0);
        $this->RegisterPropertyString('ScaleLabelRotation', 'horizontal');
        $this->RegisterPropertyString('GaugeDirection', 'clockwise');
        $this->RegisterPropertyInteger('GaugeOffsetXPercent', 0);
        $this->RegisterPropertyInteger('GaugeOffsetYPercent', 0);
        $this->RegisterPropertyInteger('ValueOffsetXPercent', 0);
        $this->RegisterPropertyInteger('ValueOffsetYPercent', 0);
        $this->RegisterPropertyInteger('TitleOffsetXPercent', 0);
        $this->RegisterPropertyInteger('TitleOffsetYPercent', 0);
        foreach ([
            'PointerVisibility',
            'ProgressVisibility',
            'RingVisibility',
            'MinorTicksVisibility',
            'MajorTicksVisibility',
            'ScaleLabelsVisibility',
            'ValueVisibility',
            'UnitVisibility',
            'TitleVisibility',
            'DetailBoxVisibility'
        ] as $propertyName) {
            $this->RegisterPropertyString($propertyName, 'preset');
        }
        $this->RegisterPropertyString('DetailColorMode', 'theme');
        $this->RegisterPropertyInteger('DetailBackgroundColor', 0x25272B);
        $this->RegisterPropertyInteger('DetailBorderColor', 0xA5A9B0);
        $this->RegisterPropertyBoolean('DetailShadow', false);
        $this->RegisterPropertyBoolean('PointerShadow', false);
        $this->RegisterPropertyBoolean('ProgressShadow', false);
        $this->RegisterPropertyBoolean('RingShadow', false);
        $this->RegisterPropertyBoolean('AnchorShadow', false);
        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $this->RegisterPropertyInteger($propertyName, self::DESIGN_SCALE_DEFAULT);
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
        foreach (self::IPSVIEW_STRING_DESIGN_DEFAULTS as $propertyName => $default) {
            $this->RegisterPropertyString(self::IPSVIEW_PROPERTY_PREFIX . $propertyName, $default);
        }
        foreach (self::IPSVIEW_FLOAT_DESIGN_DEFAULTS as $propertyName => $default) {
            $this->RegisterPropertyFloat(self::IPSVIEW_PROPERTY_PREFIX . $propertyName, $default);
        }
        foreach (self::IPSVIEW_BOOLEAN_DESIGN_DEFAULTS as $propertyName => $default) {
            $this->RegisterPropertyBoolean(self::IPSVIEW_PROPERTY_PREFIX . $propertyName, $default);
        }
        foreach (self::IPSVIEW_INTEGER_DESIGN_DEFAULTS as $propertyName => $default) {
            $this->RegisterPropertyInteger(self::IPSVIEW_PROPERTY_PREFIX . $propertyName, $default);
        }
        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $this->RegisterPropertyInteger(
                self::IPSVIEW_PROPERTY_PREFIX . $propertyName,
                self::DESIGN_SCALE_DEFAULT
            );
        }
        $this->RegisterAttributeInteger('RegisteredSourceVariableID', 0);
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(
            self::IPSVIEW_OUTPUT_IDENT,
            $this->Translate('Gauge for IPSView'),
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
            $form['elements'] = $this->WithGaugePreviewActions(
                $form['elements'],
                $this->GaugePreviewAction()
            );
            $form['elements'] = $this->WithGaugePreviewCallbacks(
                $form['elements'],
                $this->GaugePreviewFormAction()
            );
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        }
        $gaugeConfiguration = $this->ReadEffectiveGaugeConfiguration();
        $form = SVGPreviewHelper::withImage(
            $form,
            'GaugePreview',
            $this->BuildGaugePreviewSvg(
                $this->ReadPropertyInteger('SourceVariableID'),
                $gaugeConfiguration['minimum'],
                $gaugeConfiguration['maximum'],
                $this->ReadPropertyString('Title'),
                $gaugeConfiguration['unit'],
                $gaugeConfiguration['decimals'],
                $this->ReadPropertyString('GaugePreset'),
                $this->ReadPropertyString('EChartsTheme'),
                $this->ReadGaugeStyle()
            )
        );
        $form = SVGPreviewHelper::withImage(
            $form,
            'IPSViewGaugePreview',
            $this->BuildIPSViewGaugePreviewSvg()
        );

        return $this->EncodeConfigurationForm($this->WithIPSViewDesignFormState($form, 'ECGS'));
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
        foreach (self::GaugeDesignPropertyNames() as $propertyName) {
            $sourceName = $propertyName;
            $targetName = self::IPSVIEW_PROPERTY_PREFIX . $propertyName;
            if (array_key_exists($propertyName, self::IPSVIEW_STRING_DESIGN_DEFAULTS)) {
                IPS_SetProperty($this->InstanceID, $targetName, $this->ReadPropertyString($sourceName));
            } elseif (array_key_exists($propertyName, self::IPSVIEW_FLOAT_DESIGN_DEFAULTS)) {
                IPS_SetProperty($this->InstanceID, $targetName, $this->ReadPropertyFloat($sourceName));
            } elseif (array_key_exists($propertyName, self::IPSVIEW_BOOLEAN_DESIGN_DEFAULTS)) {
                IPS_SetProperty($this->InstanceID, $targetName, $this->ReadPropertyBoolean($sourceName));
            } else {
                IPS_SetProperty($this->InstanceID, $targetName, $this->ReadPropertyInteger($sourceName));
            }
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function UpdateIPSViewGaugePreviewFromForm(string $Configuration): void
    {
        try {
            $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $values = [];
        }
        if (!is_array($values)) {
            $values = [];
        }

        $gaugeConfiguration = $this->ResolveGaugeConfiguration(
            (int) ($values['SourceVariableID'] ?? 0),
            self::FiniteFloat($values['Minimum'] ?? 0.0, 0.0),
            self::FiniteFloat($values['Maximum'] ?? 100.0, 100.0),
            (string) ($values['Unit'] ?? ''),
            (int) ($values['Decimals'] ?? 1),
            (bool) ($values['UseVariablePresentation'] ?? false)
        );
        $useTileDesign = (bool) ($values['IPSViewUseTileDesign'] ?? true);
        $designValues = $useTileDesign
            ? $values
            : $this->UnprefixIPSViewDesignValues($values);
        $this->UpdateFormField(
            'IPSViewGaugePreview',
            'image',
            SVGPreviewHelper::dataUri($this->BuildGaugePreviewSvg(
                (int) ($values['SourceVariableID'] ?? 0),
                $gaugeConfiguration['minimum'],
                $gaugeConfiguration['maximum'],
                (string) ($values['Title'] ?? ''),
                $gaugeConfiguration['unit'],
                $gaugeConfiguration['decimals'],
                (string) ($designValues['GaugePreset'] ?? self::PRESET_SIMPLE),
                (string) ($designValues['EChartsTheme'] ?? EChartsAsset::THEME_AUTO),
                EChartsIPSViewBackground::WithPreviewStyle(
                    $this->GaugeStyleFromFormValues(
                        $designValues,
                        $gaugeConfiguration['minimum'],
                        $gaugeConfiguration['maximum']
                    ),
                    (bool) ($values['IPSViewAdaptToBackground']
                        ?? $this->ReadPropertyBoolean('IPSViewAdaptToBackground')),
                    (int) ($values['IPSViewBackgroundColor']
                        ?? $this->ReadPropertyInteger('IPSViewBackgroundColor')),
                    (int) ($values['IPSViewBackgroundOpacityPercent']
                        ?? $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent'))
                )
            ))
        );
    }

    /**
     * Refreshes the preview from one snapshot of all currently edited form values.
     */
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

        $gaugeConfiguration = $this->ResolveGaugeConfiguration(
            (int) ($values['SourceVariableID'] ?? 0),
            self::FiniteFloat($values['Minimum'] ?? 0.0, 0.0),
            self::FiniteFloat($values['Maximum'] ?? 100.0, 100.0),
            (string) ($values['Unit'] ?? ''),
            (int) ($values['Decimals'] ?? 1),
            (bool) ($values['UseVariablePresentation'] ?? false)
        );
        $minimum = $gaugeConfiguration['minimum'];
        $maximum = $gaugeConfiguration['maximum'];
        $this->UpdateFormField(
            'GaugePreview',
            'image',
            SVGPreviewHelper::dataUri($this->BuildGaugePreviewSvg(
                (int) ($values['SourceVariableID'] ?? 0),
                $minimum,
                $maximum,
                (string) ($values['Title'] ?? ''),
                $gaugeConfiguration['unit'],
                $gaugeConfiguration['decimals'],
                (string) ($values['GaugePreset'] ?? self::PRESET_SIMPLE),
                (string) ($values['EChartsTheme'] ?? EChartsAsset::THEME_AUTO),
                $this->GaugeStyleFromFormValues($values, $minimum, $maximum)
            ))
        );
    }

    /**
     * Refreshes the configuration-form preview with values that are not persisted yet.
     */
    public function UpdateGaugePreview(
        int $VariableID,
        float $Minimum,
        float $Maximum,
        string $Title,
        string $Unit,
        int $Decimals,
        string $GaugePreset = self::PRESET_SIMPLE,
        string $EChartsTheme = EChartsAsset::THEME_AUTO,
        int $ScaleFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $ValueFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $UnitFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $TitleFontSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $RingWidthPercent = self::DESIGN_SCALE_DEFAULT,
        int $PointerWidthPercent = self::DESIGN_SCALE_DEFAULT,
        int $MinorTickLengthPercent = self::DESIGN_SCALE_DEFAULT,
        int $MajorTickLengthPercent = self::DESIGN_SCALE_DEFAULT,
        string $PointerShape = 'preset',
        string $GaugeArcMode = 'preset',
        float $GaugeStartPosition = 270.0,
        float $GaugeEndPosition = 90.0,
        string $GaugeColorMode = 'theme',
        int $PointerColor = 0x55CBB5,
        int $ProgressColor = 0x55CBB5,
        int $RingColor = 0x45474C,
        int $ScaleColor = 0xA7A9AE,
        int $ValueColor = 0xF4F5F7,
        int $TitleColor = 0xA7A9AE,
        string $CustomPointerSVG = '',
        int $PointerLengthPercent = self::DESIGN_SCALE_DEFAULT,
        string $CustomPointerPivotMode = 'svg',
        float $CustomPointerPivotXPercent = 50.0,
        float $CustomPointerPivotYPercent = 100.0,
        string $AnchorShape = 'preset',
        string $CustomAnchorSVG = '',
        string $AnchorColorMode = 'theme',
        int $AnchorColor = 0x55CBB5,
        int $AnchorBorderColor = 0xF4F5F7,
        int $AnchorSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $AnchorBorderWidthPercent = self::DESIGN_SCALE_DEFAULT,
        string $PlateShape = 'hidden',
        string $PlateColorMode = 'theme',
        bool $PlateTransparent = false,
        bool $PlateShadow = false,
        int $PlateColor = 0x25272B,
        int $PlateBorderColor = 0xA5A9B0,
        int $PlateSizePercent = self::DESIGN_SCALE_DEFAULT,
        int $PlateBorderWidthPercent = self::DESIGN_SCALE_DEFAULT,
        string $PlateFillMode = 'solid',
        bool $PlateGradientMiddleEnabled = false,
        int $PlateGradientMiddleColor = 0x45474C,
        int $PlateGradientEndColor = 0x111317,
        string $PlateGradientDirection = 'top-bottom',
        int $PlateGradientCenterXPercent = 50,
        int $PlateGradientCenterYPercent = 50,
        int $PlateGradientRadiusPercent = 75,
        bool $PlateBackgroundEnabled = false,
        string $PlateBackgroundSVG = '',
        string $PlateBackgroundFit = 'cover',
        int $PlateBackgroundSizePercent = 100,
        int $PlateBackgroundOffsetXPercent = 0,
        int $PlateBackgroundOffsetYPercent = 0,
        int $PlateBackgroundOpacityPercent = 100,
        float $PlateBackgroundRotation = 0.0
    ): void {
        $this->UpdateFormField(
            'GaugePreview',
            'image',
            SVGPreviewHelper::dataUri($this->BuildGaugePreviewSvg(
                $VariableID,
                $Minimum,
                $Maximum,
                $Title,
                $Unit,
                $Decimals,
                $GaugePreset,
                $EChartsTheme,
                [
                    'scaleFontSizePercent'        => $ScaleFontSizePercent,
                    'valueFontSizePercent'        => $ValueFontSizePercent,
                    'unitFontSizePercent'         => $UnitFontSizePercent,
                    'titleFontSizePercent'        => $TitleFontSizePercent,
                    'ringWidthPercent'            => $RingWidthPercent,
                    'pointerWidthPercent'         => $PointerWidthPercent,
                    'pointerLengthPercent'        => $PointerLengthPercent,
                    'anchorSizePercent'           => $AnchorSizePercent,
                    'anchorBorderWidthPercent'    => $AnchorBorderWidthPercent,
                    'plateSizePercent'            => $PlateSizePercent,
                    'plateBorderWidthPercent'     => $PlateBorderWidthPercent,
                    'minorTickLengthPercent'      => $MinorTickLengthPercent,
                    'majorTickLengthPercent'      => $MajorTickLengthPercent,
                    'pointerShape'                => $PointerShape,
                    'arcMode'                     => $GaugeArcMode,
                    'startPosition'               => $GaugeStartPosition,
                    'endPosition'                 => $GaugeEndPosition,
                    'colorMode'                   => $GaugeColorMode,
                    'pointerColor'                => self::ColorToHex($PointerColor),
                    'progressColor'               => self::ColorToHex($ProgressColor),
                    'ringColor'                   => self::ColorToHex($RingColor),
                    'scaleColor'                  => self::ColorToHex($ScaleColor),
                    'valueColor'                  => self::ColorToHex($ValueColor),
                    'titleColor'                  => self::ColorToHex($TitleColor),
                    'anchorShape'                 => $AnchorShape,
                    'anchorColorMode'             => $AnchorColorMode,
                    'anchorColor'                 => self::ColorToHex($AnchorColor),
                    'anchorBorderColor'           => self::ColorToHex($AnchorBorderColor),
                    'plateShape'                  => $PlateShape,
                    'plateColorMode'              => $PlateColorMode,
                    'plateFillMode'               => $PlateFillMode,
                    'plateGradientMiddleEnabled'  => $PlateGradientMiddleEnabled,
                    'plateGradientMiddleColor'    => self::ColorToHex($PlateGradientMiddleColor),
                    'plateGradientEndColor'       => self::ColorToHex($PlateGradientEndColor),
                    'plateGradientDirection'      => $PlateGradientDirection,
                    'plateGradientCenterXPercent' => $PlateGradientCenterXPercent,
                    'plateGradientCenterYPercent' => $PlateGradientCenterYPercent,
                    'plateGradientRadiusPercent'  => $PlateGradientRadiusPercent,
                    'plateTransparent'            => $PlateTransparent,
                    'plateShadow'                 => $PlateShadow,
                    'plateColor'                  => self::ColorToHex($PlateColor),
                    'plateBorderColor'            => self::ColorToHex($PlateBorderColor),
                    ...$this->ResolveCustomPointerStyle(
                        $PointerShape,
                        $CustomPointerSVG,
                        $CustomPointerPivotMode,
                        $CustomPointerPivotXPercent,
                        $CustomPointerPivotYPercent
                    ),
                    ...$this->ResolveCustomAnchorStyle($AnchorShape, $CustomAnchorSVG),
                    ...$this->ResolvePlateBackgroundStyle(
                        $PlateBackgroundEnabled,
                        $PlateBackgroundSVG,
                        $PlateBackgroundFit,
                        $PlateBackgroundSizePercent,
                        $PlateBackgroundOffsetXPercent,
                        $PlateBackgroundOffsetYPercent,
                        $PlateBackgroundOpacityPercent,
                        $PlateBackgroundRotation
                    )
                ]
            ))
        );
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
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, [
            'VariableID' => $this->ReadPropertyInteger('SourceVariableID')
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
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if ((!is_int($value) && !is_float($value)) || !is_int($timestamp)) {
            $this->WriteAttributeString('LastError', 'INVALID_CURRENT_VALUE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        $gaugeConfiguration = $this->ReadEffectiveGaugeConfiguration();

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'source'        => [
                'variableID' => $this->ReadPropertyInteger('SourceVariableID'),
                'timestamp'  => $timestamp
            ],
            'gauge'         => [
                'title'    => $this->ReadPropertyString('Title'),
                'minimum'  => $gaugeConfiguration['minimum'],
                'maximum'  => $gaugeConfiguration['maximum'],
                'unit'     => $gaugeConfiguration['unit'],
                'decimals' => $gaugeConfiguration['decimals'],
                'preset'   => $this->ReadPropertyString('GaugePreset'),
                'style'    => $this->ReadGaugeStyle()
            ],
            'value'         => (float) $value
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

        if ($Message === VM_UPDATE && $SenderID === $this->ReadPropertyInteger('SourceVariableID')) {
            $this->PublishVisualizationState();
        }
    }

    private function RenderGaugeHTMLPage(bool $ipsView): string
    {
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Gauge Single',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => $this->IPSViewTranslationsFor([
                'Configure a numeric source variable.',
                'Configure a valid Gauge range.',
                'Configure a valid Gauge design.',
                'Configure a valid ECharts theme.',
                'Connect an active EChartsGateway.',
                'The Gauge value could not be loaded.',
                'Apache ECharts could not be initialized.'
            ]),
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
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

    private function GaugePreviewAction(): string
    {
        $parameters = [
            '$id',
            '$SourceVariableID',
            '$Minimum',
            '$Maximum',
            '$Title',
            '$Unit',
            '$Decimals',
            '$GaugePreset',
            '$EChartsTheme',
            '$ScaleFontSizePercent',
            '$ValueFontSizePercent',
            '$UnitFontSizePercent',
            '$TitleFontSizePercent',
            '$RingWidthPercent',
            '$PointerWidthPercent',
            '$MinorTickLengthPercent',
            '$MajorTickLengthPercent',
            '$PointerShape',
            '$GaugeArcMode',
            '$GaugeStartPosition',
            '$GaugeEndPosition',
            '$GaugeColorMode',
            '$PointerColor',
            '$ProgressColor',
            '$RingColor',
            '$ScaleColor',
            '$ValueColor',
            '$TitleColor',
            '$CustomPointerSVG',
            '$PointerLengthPercent',
            '$CustomPointerPivotMode',
            '$CustomPointerPivotXPercent',
            '$CustomPointerPivotYPercent',
            '$AnchorShape',
            '$CustomAnchorSVG',
            '$AnchorColorMode',
            '$AnchorColor',
            '$AnchorBorderColor',
            '$AnchorSizePercent',
            '$AnchorBorderWidthPercent',
            '$PlateShape',
            '$PlateColorMode',
            '$PlateTransparent',
            '$PlateShadow',
            '$PlateColor',
            '$PlateBorderColor',
            '$PlateSizePercent',
            '$PlateBorderWidthPercent',
            '$PlateFillMode',
            '$PlateGradientMiddleEnabled',
            '$PlateGradientMiddleColor',
            '$PlateGradientEndColor',
            '$PlateGradientDirection',
            '$PlateGradientCenterXPercent',
            '$PlateGradientCenterYPercent',
            '$PlateGradientRadiusPercent',
            '$PlateBackgroundEnabled',
            '$PlateBackgroundSVG',
            '$PlateBackgroundFit',
            '$PlateBackgroundSizePercent',
            '$PlateBackgroundOffsetXPercent',
            '$PlateBackgroundOffsetYPercent',
            '$PlateBackgroundOpacityPercent',
            '$PlateBackgroundRotation'
        ];

        return 'ECGS_UpdateGaugePreview(' . implode(', ', $parameters) . ');';
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function WithGaugePreviewActions(array $items, string $action): array
    {
        foreach ($items as &$item) {
            if (isset($item['onChange'])
                && is_string($item['onChange'])
                && str_starts_with($item['onChange'], 'ECGS_UpdateGaugePreview(')) {
                $item['onChange'] = $action;
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->WithGaugePreviewActions($item['items'], $action);
            }
        }
        unset($item);

        return $items;
    }

    private function GaugePreviewFormAction(): string
    {
        $fieldNames = [
            'SourceVariableID', 'UseVariablePresentation', 'Minimum', 'Maximum', 'Title', 'TitlePosition',
            'Unit', 'Decimals',
            'GaugePreset', 'EChartsTheme', 'PointerShape', 'CustomPointerSVG',
            'CustomPointerPivotMode', 'CustomPointerPivotXPercent', 'CustomPointerPivotYPercent',
            'AnchorShape', 'CustomAnchorSVG', 'AnchorColorMode', 'AnchorColor', 'AnchorBorderColor',
            'PlateShape', 'PlateColorMode', 'PlateFillMode', 'PlateGradientMiddleEnabled',
            'PlateGradientMiddleColor', 'PlateGradientEndColor', 'PlateGradientDirection',
            'PlateGradientCenterXPercent', 'PlateGradientCenterYPercent', 'PlateGradientRadiusPercent',
            'PlateBackgroundEnabled', 'PlateBackgroundSVG', 'PlateBackgroundFit',
            'PlateBackgroundSizePercent', 'PlateBackgroundOffsetXPercent',
            'PlateBackgroundOffsetYPercent', 'PlateBackgroundOpacityPercent', 'PlateBackgroundRotation',
            'PlateTransparent', 'PlateShadow', 'PlateColor', 'PlateBorderColor',
            'GaugeArcMode', 'GaugeStartPosition', 'GaugeEndPosition', 'GaugeColorMode',
            'PointerColor', 'ProgressColor', 'RingColor', 'ScaleColor', 'ValueColor', 'TitleColor',
            'ScaleZonesEnabled', 'ScaleZones', 'MajorSplitCount', 'MinorSplitCount',
            'ScaleLabelRotation', 'GaugeDirection', 'GaugeOffsetXPercent', 'GaugeOffsetYPercent',
            'ValueOffsetXPercent', 'ValueOffsetYPercent', 'TitleOffsetXPercent', 'TitleOffsetYPercent',
            'PointerVisibility', 'ProgressVisibility', 'RingVisibility', 'MinorTicksVisibility',
            'MajorTicksVisibility', 'ScaleLabelsVisibility', 'ValueVisibility', 'UnitVisibility',
            'TitleVisibility', 'DetailBoxVisibility', 'DetailColorMode',
            'DetailBackgroundColor', 'DetailBorderColor', 'DetailShadow',
            'PointerShadow', 'ProgressShadow', 'RingShadow', 'AnchorShadow',
            ...array_values(self::DESIGN_SCALE_PROPERTIES)
        ];
        $pairs = array_map(
            static fn (string $name): string => "'{$name}' => \${$name}",
            array_values(array_unique($fieldNames))
        );

        return 'ECGS_UpdateGaugePreviewFromForm($id, json_encode([' . implode(', ', $pairs) . ']));';
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function WithGaugePreviewCallbacks(array $items, string $action): array
    {
        $inputTypes = [
            'CheckBox', 'NumberSpinner', 'Select', 'SelectColor', 'SelectFile',
            'SelectVariable', 'ValidationTextBox'
        ];
        foreach ($items as &$item) {
            if (isset($item['name'], $item['type'])
                && is_string($item['name'])
                && in_array($item['type'], $inputTypes, true)) {
                $item['onChange'] = $action;
            }
            if (($item['type'] ?? null) === 'List'
                && in_array(($item['name'] ?? null), ['ScaleZones', 'IPSViewScaleZones'], true)) {
                foreach (['onAdd', 'onDelete', 'onEdit', 'onChangeOrder'] as $event) {
                    $item[$event] = $action;
                }
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->WithGaugePreviewCallbacks($item['items'], $action);
            }
        }
        unset($item);

        return $items;
    }

    /** @param list<array<string, mixed>> $elements */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $tileDesigner = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $tileDesigner = $element;
                break;
            }
        }
        if (!is_array($tileDesigner)) {
            throw new RuntimeException('The Tile designer form section is missing.');
        }

        $designerItems = $this->PrefixIPSViewDesignerItems($tileDesigner['items'] ?? []);
        array_unshift(
            $designerItems,
            [
                'type'    => 'Select',
                'name'    => 'IPSViewTitlePosition',
                'caption' => 'Title position',
                'options' => [
                    ['caption' => 'Top', 'value' => 'top'],
                    ['caption' => 'Bottom', 'value' => 'bottom']
                ]
            ]
        );
        array_splice($designerItems, max(0, count($designerItems) - 1), 0, [[
            'type'    => 'Button',
            'caption' => 'Update IPSView preview',
            'onClick' => $this->IPSViewGaugePreviewFormAction()
        ]]);

        return [
            'type'     => 'ExpansionPanel',
            'caption'  => 'IPSView design',
            'expanded' => false,
            'width'    => '700px',
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems(
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                ),
                EChartsIPSViewBackground::FormRow($this->IPSViewGaugePreviewFormAction()),
                [
                    'type'     => 'CheckBox',
                    'name'     => 'IPSViewUseTileDesign',
                    'caption'  => 'Use Tile design',
                    'onChange' => $this->IPSViewGaugePreviewFormAction()
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECGS_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'
                ],
                [
                    'type'     => 'ExpansionPanel',
                    'caption'  => 'Independent IPSView designer',
                    'expanded' => false,
                    'items'    => $designerItems
                ]
            ]
        ];
    }

    /** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
    private function PrefixIPSViewDesignerItems(array $items): array
    {
        $designNames = self::GaugeDesignPropertyNames();
        foreach ($items as &$item) {
            if (($item['name'] ?? null) === 'GaugePreview') {
                $item['name'] = 'IPSViewGaugePreview';
            } elseif (isset($item['name']) && in_array($item['name'], $designNames, true)) {
                $item['name'] = self::IPSVIEW_PROPERTY_PREFIX . $item['name'];
            }
            unset($item['onChange'], $item['onAdd'], $item['onDelete'], $item['onEdit'], $item['onChangeOrder']);
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->PrefixIPSViewDesignerItems($item['items']);
            }
        }
        unset($item);

        return $items;
    }

    private function IPSViewGaugePreviewFormAction(): string
    {
        $fieldNames = [
            'SourceVariableID', 'UseVariablePresentation', 'Minimum', 'Maximum', 'Title', 'Unit', 'Decimals',
            'IPSViewUseTileDesign',
            'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent',
            ...self::GaugeDesignPropertyNames(),
            ...array_map(
                static fn (string $name): string => self::IPSVIEW_PROPERTY_PREFIX . $name,
                self::GaugeDesignPropertyNames()
            )
        ];
        $pairs = array_map(
            static fn (string $name): string => "'{$name}' => \${$name}",
            array_values(array_unique($fieldNames))
        );

        return 'ECGS_UpdateIPSViewGaugePreviewFromForm($id, json_encode([' . implode(', ', $pairs) . ']));';
    }

    /** @return list<string> */
    private static function GaugeDesignPropertyNames(): array
    {
        return array_values(array_unique([
            ...array_keys(self::IPSVIEW_STRING_DESIGN_DEFAULTS),
            ...array_keys(self::IPSVIEW_FLOAT_DESIGN_DEFAULTS),
            ...array_keys(self::IPSVIEW_BOOLEAN_DESIGN_DEFAULTS),
            ...array_keys(self::IPSVIEW_INTEGER_DESIGN_DEFAULTS),
            ...array_values(self::DESIGN_SCALE_PROPERTIES)
        ]));
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function UnprefixIPSViewDesignValues(array $values): array
    {
        $result = [];
        foreach (self::GaugeDesignPropertyNames() as $name) {
            $prefixedName = self::IPSVIEW_PROPERTY_PREFIX . $name;
            if (array_key_exists($prefixedName, $values)) {
                $result[$name] = $values[$prefixedName];
            }
        }

        return $result;
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->SetSummary('');
        $this->SynchronizeSourceReference();

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

        $this->WriteAttributeString('LastError', '');
        $this->SetSummary(IPS_GetName($this->ReadPropertyInteger('SourceVariableID')));
        $this->SetStatus(IS_ACTIVE);
    }

    private function BuildGaugePreviewSvg(
        int $variableID,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $preset,
        string $theme,
        array $style
    ): string {
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Minimum must be lower than maximum.')
            );
        }
        if (!in_array($preset, self::SUPPORTED_PRESETS, true)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select a supported Gauge preset.')
            );
        }
        if (!EChartsAsset::IsSupportedTheme($theme)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select a supported ECharts theme.')
            );
        }
        if (!in_array((string) ($style['pointerShape'] ?? 'preset'), EChartsGaugeDesign::POINTER_SHAPES, true)
            || !in_array((string) ($style['anchorShape'] ?? 'preset'), self::SUPPORTED_ANCHOR_SHAPES, true)
            || !in_array((string) ($style['anchorColorMode'] ?? 'theme'), self::SUPPORTED_ANCHOR_COLOR_MODES, true)
            || !in_array((string) ($style['plateShape'] ?? 'hidden'), self::SUPPORTED_PLATE_SHAPES, true)
            || !in_array((string) ($style['plateColorMode'] ?? 'theme'), self::SUPPORTED_PLATE_COLOR_MODES, true)
            || !in_array((string) ($style['plateFillMode'] ?? 'solid'), self::SUPPORTED_PLATE_FILL_MODES, true)
            || !in_array(
                (string) ($style['plateGradientDirection'] ?? 'top-bottom'),
                self::SUPPORTED_PLATE_GRADIENT_DIRECTIONS,
                true
            )
            || !in_array(
                (string) ($style['plateBackgroundFit'] ?? 'cover'),
                EChartsGaugeDesign::PLATE_BACKGROUND_FITS,
                true
            )
            || !in_array((string) ($style['arcMode'] ?? 'preset'), self::SUPPORTED_ARC_MODES, true)
            || !in_array((string) ($style['colorMode'] ?? 'theme'), self::SUPPORTED_COLOR_MODES, true)
            || !in_array((string) ($style['titlePosition'] ?? 'bottom'), self::SUPPORTED_TITLE_POSITIONS, true)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select supported Gauge design options.')
            );
        }
        if (($style['pointerShape'] ?? 'preset') === 'custom'
            && ((string) ($style['pointerPath'] ?? '') === '' || (string) ($style['pointerViewBox'] ?? '') === '')) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate((string) ($style['pointerError'] ?? 'Select a supported path-only SVG pointer.'))
            );
        }
        if (($style['anchorShape'] ?? 'preset') === 'custom'
            && ((string) ($style['anchorPath'] ?? '') === '' || (string) ($style['anchorViewBox'] ?? '') === '')) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate((string) ($style['anchorError'] ?? 'Select a supported path-only SVG anchor.'))
            );
        }
        if ((bool) ($style['plateBackgroundEnabled'] ?? false)
            && (string) ($style['plateBackgroundImage'] ?? '') === '') {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate((string) ($style['plateBackgroundError'] ?? 'Select a supported SVG plate background.'))
            );
        }
        foreach (['plateGradientCenterXPercent', 'plateGradientCenterYPercent'] as $fieldName) {
            $position = (int) ($style[$fieldName] ?? 50);
            if ($position < 0 || $position > 100) {
                return EChartsGaugeSinglePreview::CreateErrorSvg(
                    $this->Translate('Select supported Gauge design options.')
                );
            }
        }
        $gradientRadius = (int) ($style['plateGradientRadiusPercent'] ?? 75);
        if ($gradientRadius < 25 || $gradientRadius > 150) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select supported Gauge design options.')
            );
        }
        $backgroundRanges = [
            'plateBackgroundSizePercent'     => [25, 200],
            'plateBackgroundOffsetXPercent'  => [-100, 100],
            'plateBackgroundOffsetYPercent'  => [-100, 100],
            'plateBackgroundOpacityPercent'  => [0, 100],
            'plateBackgroundRotation'        => [-180, 180]
        ];
        foreach ($backgroundRanges as $fieldName => [$minimumValue, $maximumValue]) {
            $value = (float) ($style[$fieldName] ?? ($fieldName === 'plateBackgroundSizePercent' ? 100 : 0));
            if (!is_finite($value) || $value < $minimumValue || $value > $maximumValue) {
                return EChartsGaugeSinglePreview::CreateErrorSvg(
                    $this->Translate('Select supported Gauge design options.')
                );
            }
        }
        $startPosition = (float) ($style['startPosition'] ?? 270.0);
        $endPosition = (float) ($style['endPosition'] ?? 90.0);
        if (!self::IsAnglePosition($startPosition)
            || !self::IsAnglePosition($endPosition)
            || (($style['arcMode'] ?? 'preset') === 'custom' && abs($startPosition - $endPosition) < 0.000001)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select distinct start and end positions in 22.5 degree steps.')
            );
        }

        $value = $this->ResolveGaugePreviewValue($variableID, $minimum, $maximum);
        $language = $this->NormalizeHelperTranslationLanguage(
            $this->ResolveHelperTranslationLanguage()
        );

        return EChartsGaugeSinglePreview::CreateSvg(
            $value,
            $minimum,
            $maximum,
            $title,
            $unit,
            $decimals,
            $language,
            $preset,
            $theme,
            $style
        );
    }

    private function ResolveGaugePreviewValue(int $variableID, float $minimum, float $maximum): float
    {
        if ($variableID > 0 && IPS_VariableExists($variableID)) {
            try {
                $variable = IPS_GetVariable($variableID);
                if (in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                    $value = GetValue($variableID);
                    if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
                        return (float) $value;
                    }
                }
            } catch (Throwable $exception) {
                $this->SendDebug('ResolveGaugePreviewValue', $exception::class, 0);
            }
        }

        return $minimum + (($maximum - $minimum) / 2);
    }

    /** @return array{minimum: float, maximum: float, unit: string, decimals: int} */
    private function ReadEffectiveGaugeConfiguration(): array
    {
        return $this->ResolveGaugeConfiguration(
            $this->ReadPropertyInteger('SourceVariableID'),
            $this->ReadPropertyFloat('Minimum'),
            $this->ReadPropertyFloat('Maximum'),
            $this->ReadPropertyString('Unit'),
            $this->ReadPropertyInteger('Decimals'),
            $this->ReadPropertyBoolean('UseVariablePresentation')
        );
    }

    /** @return array{minimum: float, maximum: float, unit: string, decimals: int} */
    private function ResolveGaugeConfiguration(
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
     * Returns the first invalid configuration field and its module status.
     *
     * @return array{Status: int, Message: string}|null
     */
    private function GetConfigurationError(): ?array
    {
        if (!EChartsIPSViewBackground::IsValid(
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        )) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The IPSView background configuration is invalid.'
            ];
        }

        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($variableID <= 0 || !IPS_VariableExists($variableID)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'Select an existing numeric source variable.'
            ];
        }

        $variable = IPS_GetVariable($variableID);
        if (!in_array($variable['VariableType'] ?? null, [1, 2], true)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'The selected source variable must be numeric.'
            ];
        }

        $gaugeConfiguration = $this->ReadEffectiveGaugeConfiguration();
        if ($gaugeConfiguration['minimum'] >= $gaugeConfiguration['maximum']) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge range minimum must be lower than maximum.'
            ];
        }

        $decimals = $gaugeConfiguration['decimals'];
        if ($decimals < 0 || $decimals > 6) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge decimals must be between 0 and 6.'
            ];
        }

        if (!in_array($this->ReadPropertyString('GaugePreset'), self::SUPPORTED_PRESETS, true)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Gauge preset is not supported.'
            ];
        }

        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))) {
            return [
                'Status'  => self::STATUS_THEME_INVALID,
                'Message' => 'The selected ECharts theme is not supported.'
            ];
        }

        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $value = $this->ReadPropertyInteger($propertyName);
            if ($value < self::DESIGN_SCALE_MINIMUM || $value > self::DESIGN_SCALE_MAXIMUM) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Gauge design scale values must be between 50 and 150 percent.'
                ];
            }
        }

        if (!in_array($this->ReadPropertyString('PointerShape'), EChartsGaugeDesign::POINTER_SHAPES, true)
            || !in_array(
                $this->ReadPropertyString('CustomPointerPivotMode'),
                EChartsGaugeDesign::POINTER_PIVOT_MODES,
                true
            )
            || !in_array($this->ReadPropertyString('AnchorShape'), self::SUPPORTED_ANCHOR_SHAPES, true)
            || !in_array($this->ReadPropertyString('AnchorColorMode'), self::SUPPORTED_ANCHOR_COLOR_MODES, true)
            || !in_array($this->ReadPropertyString('PlateShape'), self::SUPPORTED_PLATE_SHAPES, true)
            || !in_array($this->ReadPropertyString('PlateColorMode'), self::SUPPORTED_PLATE_COLOR_MODES, true)
            || !in_array($this->ReadPropertyString('PlateFillMode'), self::SUPPORTED_PLATE_FILL_MODES, true)
            || !in_array(
                $this->ReadPropertyString('PlateGradientDirection'),
                self::SUPPORTED_PLATE_GRADIENT_DIRECTIONS,
                true
            )
            || !in_array(
                $this->ReadPropertyString('PlateBackgroundFit'),
                EChartsGaugeDesign::PLATE_BACKGROUND_FITS,
                true
            )
            || !in_array($this->ReadPropertyString('GaugeArcMode'), self::SUPPORTED_ARC_MODES, true)
            || !in_array($this->ReadPropertyString('GaugeColorMode'), self::SUPPORTED_COLOR_MODES, true)
            || !in_array($this->ReadPropertyString('DetailColorMode'), self::SUPPORTED_COLOR_MODES, true)
            || !in_array($this->ReadPropertyString('GaugeDirection'), self::SUPPORTED_GAUGE_DIRECTIONS, true)
            || !in_array($this->ReadPropertyString('TitlePosition'), self::SUPPORTED_TITLE_POSITIONS, true)
            || !in_array(
                $this->ReadPropertyString('ScaleLabelRotation'),
                self::SUPPORTED_SCALE_LABEL_ROTATIONS,
                true
            )) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Gauge design option is not supported.'
            ];
        }

        foreach ([
            'PointerVisibility', 'ProgressVisibility', 'RingVisibility',
            'MinorTicksVisibility', 'MajorTicksVisibility', 'ScaleLabelsVisibility',
            'ValueVisibility', 'UnitVisibility', 'TitleVisibility', 'DetailBoxVisibility'
        ] as $propertyName) {
            if (!in_array($this->ReadPropertyString($propertyName), self::SUPPORTED_VISIBILITY_MODES, true)) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'The selected Gauge visibility option is not supported.'
                ];
            }
        }
        foreach ([
            'MajorSplitCount'     => [0, 24], 'MinorSplitCount' => [0, 10],
            'GaugeOffsetXPercent' => [-50, 50], 'GaugeOffsetYPercent' => [-50, 50],
            'ValueOffsetXPercent' => [-100, 100], 'ValueOffsetYPercent' => [-100, 100],
            'TitleOffsetXPercent' => [-100, 100], 'TitleOffsetYPercent' => [-100, 100]
        ] as $propertyName => [$minimumValue, $maximumValue]) {
            $value = $this->ReadPropertyInteger($propertyName);
            if ($value < $minimumValue || $value > $maximumValue) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Gauge layout values are outside their supported ranges.'
                ];
            }
        }
        if ($this->ReadPropertyInteger('MajorSplitCount') === 1) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Gauge major sections must be zero for the preset or between 2 and 24.'
            ];
        }
        if ($this->ReadPropertyBoolean('ScaleZonesEnabled')
            && !self::ScaleZonesAreValid(
                $this->ReadPropertyString('ScaleZones'),
                $gaugeConfiguration['minimum'],
                $gaugeConfiguration['maximum']
            )) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Gauge value ranges must contain up to eight ascending end values inside the Gauge range.'
            ];
        }

        if ($this->ReadPropertyString('PointerShape') === 'custom') {
            foreach (['CustomPointerPivotXPercent', 'CustomPointerPivotYPercent'] as $propertyName) {
                $pivot = $this->ReadPropertyFloat($propertyName);
                if (!is_finite($pivot) || $pivot < 0.0 || $pivot > 100.0) {
                    return [
                        'Status'  => self::STATUS_DESIGN_INVALID,
                        'Message' => 'Custom SVG pointer pivot values must be between 0 and 100 percent.'
                    ];
                }
            }
            try {
                EChartsGaugeDesign::ImportPointer(
                    $this->ReadPropertyString('CustomPointerSVG'),
                    $this->ReadPropertyString('CustomPointerPivotMode'),
                    $this->ReadPropertyFloat('CustomPointerPivotXPercent'),
                    $this->ReadPropertyFloat('CustomPointerPivotYPercent')
                );
            } catch (InvalidArgumentException $exception) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => $exception->getMessage()
                ];
            }
        }

        if ($this->ReadPropertyString('AnchorShape') === 'custom') {
            try {
                EChartsSvgPath::Import($this->ReadPropertyString('CustomAnchorSVG'));
            } catch (InvalidArgumentException $exception) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => $exception->getMessage()
                ];
            }
        }

        foreach (['PlateGradientCenterXPercent', 'PlateGradientCenterYPercent'] as $propertyName) {
            $position = $this->ReadPropertyInteger($propertyName);
            if ($position < 0 || $position > 100) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Plate gradient center values must be between 0 and 100 percent.'
                ];
            }
        }
        $gradientRadius = $this->ReadPropertyInteger('PlateGradientRadiusPercent');
        if ($gradientRadius < 25 || $gradientRadius > 150) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Plate gradient radius must be between 25 and 150 percent.'
            ];
        }
        foreach ([
            'PlateBackgroundSizePercent'    => [25, 200],
            'PlateBackgroundOffsetXPercent' => [-100, 100],
            'PlateBackgroundOffsetYPercent' => [-100, 100],
            'PlateBackgroundOpacityPercent' => [0, 100]
        ] as $propertyName => [$minimumValue, $maximumValue]) {
            $value = $this->ReadPropertyInteger($propertyName);
            if ($value < $minimumValue || $value > $maximumValue) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Plate SVG background values are outside their supported ranges.'
                ];
            }
        }
        $backgroundRotation = $this->ReadPropertyFloat('PlateBackgroundRotation');
        if (!is_finite($backgroundRotation) || $backgroundRotation < -180.0 || $backgroundRotation > 180.0) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Plate SVG background rotation must be between -180 and 180 degrees.'
            ];
        }
        if ($this->ReadPropertyBoolean('PlateBackgroundEnabled')) {
            try {
                EChartsGaugeDesign::ImportPlateBackground(
                    $this->ReadPropertyString('PlateBackgroundSVG'),
                    $this->ReadPropertyString('PlateBackgroundFit'),
                    $this->ReadPropertyInteger('PlateBackgroundSizePercent'),
                    $this->ReadPropertyInteger('PlateBackgroundOffsetXPercent'),
                    $this->ReadPropertyInteger('PlateBackgroundOffsetYPercent'),
                    $this->ReadPropertyInteger('PlateBackgroundOpacityPercent'),
                    $this->ReadPropertyFloat('PlateBackgroundRotation')
                );
            } catch (InvalidArgumentException $exception) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => $exception->getMessage()
                ];
            }
        }

        if (!self::IsAnglePosition($this->ReadPropertyFloat('GaugeStartPosition'))
            || !self::IsAnglePosition($this->ReadPropertyFloat('GaugeEndPosition'))
            || ($this->ReadPropertyString('GaugeArcMode') === 'custom'
                && abs($this->ReadPropertyFloat('GaugeStartPosition') - $this->ReadPropertyFloat('GaugeEndPosition')) < 0.000001)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'Gauge positions must be distinct 22.5 degree steps between 0 and 337.5 degrees.'
            ];
        }

        foreach ([
            'PointerColor',
            'ProgressColor',
            'RingColor',
            'ScaleColor',
            'ValueColor',
            'TitleColor',
            'AnchorColor',
            'AnchorBorderColor',
            'PlateColor',
            'PlateBorderColor',
            'PlateGradientMiddleColor',
            'PlateGradientEndColor',
            'DetailBackgroundColor',
            'DetailBorderColor'
        ] as $propertyName) {
            $color = $this->ReadPropertyInteger($propertyName);
            if ($color < 0 || $color > 0xFFFFFF) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Gauge colors must be valid RGB colors.'
                ];
            }
        }

        if ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            $values = $this->ReadIPSViewDesignValues();
            if (!in_array($values['GaugePreset'], self::SUPPORTED_PRESETS, true)) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'The selected IPSView Gauge preset is not supported.'
                ];
            }
            if (!EChartsAsset::IsSupportedTheme((string) $values['EChartsTheme'])) {
                return [
                    'Status'  => self::STATUS_THEME_INVALID,
                    'Message' => 'The selected IPSView ECharts theme is not supported.'
                ];
            }
            foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
                $value = (int) $values[$propertyName];
                if ($value < self::DESIGN_SCALE_MINIMUM || $value > self::DESIGN_SCALE_MAXIMUM) {
                    return [
                        'Status'  => self::STATUS_DESIGN_INVALID,
                        'Message' => 'IPSView Gauge design scale values must be between 50 and 150 percent.'
                    ];
                }
            }
            if (!in_array($values['PointerShape'], EChartsGaugeDesign::POINTER_SHAPES, true)
                || !in_array($values['AnchorShape'], self::SUPPORTED_ANCHOR_SHAPES, true)
                || !in_array($values['PlateShape'], self::SUPPORTED_PLATE_SHAPES, true)
                || !in_array($values['GaugeArcMode'], self::SUPPORTED_ARC_MODES, true)
                || !in_array($values['GaugeColorMode'], self::SUPPORTED_COLOR_MODES, true)
                || !in_array($values['TitlePosition'], self::SUPPORTED_TITLE_POSITIONS, true)) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'The selected IPSView Gauge design option is not supported.'
                ];
            }
            try {
                $this->GaugeStyleFromFormValues(
                    $values,
                    $gaugeConfiguration['minimum'],
                    $gaugeConfiguration['maximum']
                );
            } catch (InvalidArgumentException $exception) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => $exception->getMessage()
                ];
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function ReadGaugeStyle(): array
    {
        $style = [];
        foreach (self::DESIGN_SCALE_PROPERTIES as $fieldName => $propertyName) {
            $style[$fieldName] = $this->ReadPropertyInteger($propertyName);
        }

        $style['pointerShape'] = $this->ReadPropertyString('PointerShape');
        $style['titlePosition'] = $this->ReadPropertyString('TitlePosition');
        $style['anchorShape'] = $this->ReadPropertyString('AnchorShape');
        $style['anchorColorMode'] = $this->ReadPropertyString('AnchorColorMode');
        $style['plateShape'] = $this->ReadPropertyString('PlateShape');
        $style['plateColorMode'] = $this->ReadPropertyString('PlateColorMode');
        $style['plateFillMode'] = $this->ReadPropertyString('PlateFillMode');
        $style['plateGradientMiddleEnabled'] = $this->ReadPropertyBoolean('PlateGradientMiddleEnabled');
        $style['plateGradientDirection'] = $this->ReadPropertyString('PlateGradientDirection');
        $style['plateGradientCenterXPercent'] = $this->ReadPropertyInteger('PlateGradientCenterXPercent');
        $style['plateGradientCenterYPercent'] = $this->ReadPropertyInteger('PlateGradientCenterYPercent');
        $style['plateGradientRadiusPercent'] = $this->ReadPropertyInteger('PlateGradientRadiusPercent');
        $style['plateTransparent'] = $this->ReadPropertyBoolean('PlateTransparent');
        $style['plateShadow'] = $this->ReadPropertyBoolean('PlateShadow');
        $style['arcMode'] = $this->ReadPropertyString('GaugeArcMode');
        $style['startPosition'] = $this->ReadPropertyFloat('GaugeStartPosition');
        $style['endPosition'] = $this->ReadPropertyFloat('GaugeEndPosition');
        $style['colorMode'] = $this->ReadPropertyString('GaugeColorMode');
        $style['scaleZonesEnabled'] = $this->ReadPropertyBoolean('ScaleZonesEnabled');
        $gaugeConfiguration = $this->ReadEffectiveGaugeConfiguration();
        $style['scaleZones'] = self::ResolveScaleZones(
            $this->ReadPropertyString('ScaleZones'),
            $gaugeConfiguration['minimum'],
            $gaugeConfiguration['maximum']
        );
        foreach ([
            'majorSplitCount'     => 'MajorSplitCount',
            'minorSplitCount'     => 'MinorSplitCount',
            'gaugeOffsetXPercent' => 'GaugeOffsetXPercent',
            'gaugeOffsetYPercent' => 'GaugeOffsetYPercent',
            'valueOffsetXPercent' => 'ValueOffsetXPercent',
            'valueOffsetYPercent' => 'ValueOffsetYPercent',
            'titleOffsetXPercent' => 'TitleOffsetXPercent',
            'titleOffsetYPercent' => 'TitleOffsetYPercent'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = $this->ReadPropertyInteger($propertyName);
        }
        foreach ([
            'scaleLabelRotation'   => 'ScaleLabelRotation',
            'gaugeDirection'       => 'GaugeDirection',
            'pointerVisibility'    => 'PointerVisibility',
            'progressVisibility'   => 'ProgressVisibility',
            'ringVisibility'       => 'RingVisibility',
            'minorTicksVisibility' => 'MinorTicksVisibility',
            'majorTicksVisibility' => 'MajorTicksVisibility',
            'scaleLabelsVisibility'=> 'ScaleLabelsVisibility',
            'valueVisibility'      => 'ValueVisibility',
            'unitVisibility'       => 'UnitVisibility',
            'titleVisibility'      => 'TitleVisibility',
            'detailBoxVisibility'  => 'DetailBoxVisibility',
            'detailColorMode'      => 'DetailColorMode'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = $this->ReadPropertyString($propertyName);
        }
        foreach (['detailShadow', 'pointerShadow', 'progressShadow', 'ringShadow', 'anchorShadow'] as $fieldName) {
            $style[$fieldName] = $this->ReadPropertyBoolean(ucfirst($fieldName));
        }
        foreach ([
            'pointerColor'             => 'PointerColor',
            'progressColor'            => 'ProgressColor',
            'ringColor'                => 'RingColor',
            'scaleColor'               => 'ScaleColor',
            'valueColor'               => 'ValueColor',
            'titleColor'               => 'TitleColor',
            'anchorColor'              => 'AnchorColor',
            'anchorBorderColor'        => 'AnchorBorderColor',
            'plateColor'               => 'PlateColor',
            'plateBorderColor'         => 'PlateBorderColor',
            'plateGradientMiddleColor' => 'PlateGradientMiddleColor',
            'plateGradientEndColor'    => 'PlateGradientEndColor',
            'detailBackgroundColor'    => 'DetailBackgroundColor',
            'detailBorderColor'        => 'DetailBorderColor'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = self::ColorToHex($this->ReadPropertyInteger($propertyName));
        }
        $style = array_merge(
            $style,
            $this->ResolveCustomPointerStyle(
                $style['pointerShape'],
                $this->ReadPropertyString('CustomPointerSVG'),
                $this->ReadPropertyString('CustomPointerPivotMode'),
                $this->ReadPropertyFloat('CustomPointerPivotXPercent'),
                $this->ReadPropertyFloat('CustomPointerPivotYPercent')
            ),
            $this->ResolveCustomAnchorStyle(
                $style['anchorShape'],
                $this->ReadPropertyString('CustomAnchorSVG')
            ),
            $this->ResolvePlateBackgroundStyle(
                $this->ReadPropertyBoolean('PlateBackgroundEnabled'),
                $this->ReadPropertyString('PlateBackgroundSVG'),
                $this->ReadPropertyString('PlateBackgroundFit'),
                $this->ReadPropertyInteger('PlateBackgroundSizePercent'),
                $this->ReadPropertyInteger('PlateBackgroundOffsetXPercent'),
                $this->ReadPropertyInteger('PlateBackgroundOffsetYPercent'),
                $this->ReadPropertyInteger('PlateBackgroundOpacityPercent'),
                $this->ReadPropertyFloat('PlateBackgroundRotation')
            )
        );

        return $style;
    }

    /** @return array<string, mixed> */
    private function ReadIPSViewDesignValues(): array
    {
        $values = [];
        foreach (self::IPSVIEW_STRING_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyString(self::IPSVIEW_PROPERTY_PREFIX . $name);
        }
        foreach (self::IPSVIEW_FLOAT_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyFloat(self::IPSVIEW_PROPERTY_PREFIX . $name);
        }
        foreach (self::IPSVIEW_BOOLEAN_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyBoolean(self::IPSVIEW_PROPERTY_PREFIX . $name);
        }
        foreach (self::IPSVIEW_INTEGER_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyInteger(self::IPSVIEW_PROPERTY_PREFIX . $name);
        }
        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $values[$propertyName] = $this->ReadPropertyInteger(self::IPSVIEW_PROPERTY_PREFIX . $propertyName);
        }

        return $values;
    }

    /** @return array<string, mixed> */
    private function EffectiveIPSViewDesignValues(): array
    {
        if (!$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            return $this->ReadIPSViewDesignValues();
        }

        $values = [];
        foreach (self::IPSVIEW_STRING_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyString($name);
        }
        foreach (self::IPSVIEW_FLOAT_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyFloat($name);
        }
        foreach (self::IPSVIEW_BOOLEAN_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyBoolean($name);
        }
        foreach (self::IPSVIEW_INTEGER_DESIGN_DEFAULTS as $name => $_default) {
            $values[$name] = $this->ReadPropertyInteger($name);
        }
        foreach (self::DESIGN_SCALE_PROPERTIES as $propertyName) {
            $values[$propertyName] = $this->ReadPropertyInteger($propertyName);
        }

        return $values;
    }

    private function BuildIPSViewGaugePreviewSvg(): string
    {
        $configuration = $this->ReadEffectiveGaugeConfiguration();
        $values = $this->EffectiveIPSViewDesignValues();

        return $this->BuildGaugePreviewSvg(
            $this->ReadPropertyInteger('SourceVariableID'),
            $configuration['minimum'],
            $configuration['maximum'],
            $this->ReadPropertyString('Title'),
            $configuration['unit'],
            $configuration['decimals'],
            (string) $values['GaugePreset'],
            (string) $values['EChartsTheme'],
            EChartsIPSViewBackground::WithPreviewStyle(
                $this->GaugeStyleFromFormValues($values, $configuration['minimum'], $configuration['maximum']),
                $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
        );
    }

    /** @param array<string, mixed> $values */
    private function GaugeStyleFromFormValues(array $values, float $minimum, float $maximum): array
    {
        $style = $this->ReadGaugeStyle();
        foreach (self::DESIGN_SCALE_PROPERTIES as $fieldName => $propertyName) {
            $style[$fieldName] = (int) ($values[$propertyName] ?? $style[$fieldName]);
        }
        $stringFields = [
            'pointerShape'           => 'PointerShape', 'titlePosition' => 'TitlePosition',
            'anchorShape'            => 'AnchorShape',
            'anchorColorMode'        => 'AnchorColorMode', 'plateShape' => 'PlateShape',
            'plateColorMode'         => 'PlateColorMode', 'plateFillMode' => 'PlateFillMode',
            'plateGradientDirection' => 'PlateGradientDirection', 'plateBackgroundFit' => 'PlateBackgroundFit',
            'arcMode'                => 'GaugeArcMode', 'colorMode' => 'GaugeColorMode',
            'scaleLabelRotation'     => 'ScaleLabelRotation', 'gaugeDirection' => 'GaugeDirection',
            'pointerVisibility'      => 'PointerVisibility', 'progressVisibility' => 'ProgressVisibility',
            'ringVisibility'         => 'RingVisibility', 'minorTicksVisibility' => 'MinorTicksVisibility',
            'majorTicksVisibility'   => 'MajorTicksVisibility', 'scaleLabelsVisibility' => 'ScaleLabelsVisibility',
            'valueVisibility'        => 'ValueVisibility', 'unitVisibility' => 'UnitVisibility',
            'titleVisibility'        => 'TitleVisibility', 'detailBoxVisibility' => 'DetailBoxVisibility',
            'detailColorMode'        => 'DetailColorMode'
        ];
        $stringDefaults = [
            'pointerShape'           => 'preset', 'titlePosition' => 'bottom',
            'anchorShape'            => 'preset', 'anchorColorMode' => 'theme',
            'plateShape'             => 'hidden', 'plateColorMode' => 'theme', 'plateFillMode' => 'solid',
            'plateGradientDirection' => 'top-bottom', 'plateBackgroundFit' => 'cover',
            'arcMode'                => 'preset', 'colorMode' => 'theme', 'scaleLabelRotation' => 'horizontal',
            'gaugeDirection'         => 'clockwise', 'detailColorMode' => 'theme'
        ];
        foreach ($stringFields as $fieldName => $propertyName) {
            $style[$fieldName] = (string) (
                $values[$propertyName]
                ?? $style[$fieldName]
                ?? $stringDefaults[$fieldName]
                ?? 'preset'
            );
        }
        foreach ([
            'majorSplitCount'             => 'MajorSplitCount', 'minorSplitCount' => 'MinorSplitCount',
            'gaugeOffsetXPercent'         => 'GaugeOffsetXPercent', 'gaugeOffsetYPercent' => 'GaugeOffsetYPercent',
            'valueOffsetXPercent'         => 'ValueOffsetXPercent', 'valueOffsetYPercent' => 'ValueOffsetYPercent',
            'titleOffsetXPercent'         => 'TitleOffsetXPercent', 'titleOffsetYPercent' => 'TitleOffsetYPercent',
            'plateGradientCenterXPercent' => 'PlateGradientCenterXPercent',
            'plateGradientCenterYPercent' => 'PlateGradientCenterYPercent',
            'plateGradientRadiusPercent'  => 'PlateGradientRadiusPercent'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = (int) ($values[$propertyName] ?? $style[$fieldName]);
        }
        foreach ([
            'plateGradientMiddleEnabled' => 'PlateGradientMiddleEnabled',
            'plateTransparent'           => 'PlateTransparent', 'plateShadow' => 'PlateShadow',
            'scaleZonesEnabled'          => 'ScaleZonesEnabled', 'detailShadow' => 'DetailShadow',
            'pointerShadow'              => 'PointerShadow', 'progressShadow' => 'ProgressShadow',
            'ringShadow'                 => 'RingShadow', 'anchorShadow' => 'AnchorShadow'
        ] as $fieldName => $propertyName) {
            $style[$fieldName] = (bool) ($values[$propertyName] ?? $style[$fieldName]);
        }
        $style['startPosition'] = self::FiniteFloat($values['GaugeStartPosition'] ?? $style['startPosition'], 270.0);
        $style['endPosition'] = self::FiniteFloat($values['GaugeEndPosition'] ?? $style['endPosition'], 90.0);
        foreach ([
            'pointerColor'             => 'PointerColor', 'progressColor' => 'ProgressColor', 'ringColor' => 'RingColor',
            'scaleColor'               => 'ScaleColor', 'valueColor' => 'ValueColor', 'titleColor' => 'TitleColor',
            'anchorColor'              => 'AnchorColor', 'anchorBorderColor' => 'AnchorBorderColor',
            'plateColor'               => 'PlateColor', 'plateBorderColor' => 'PlateBorderColor',
            'plateGradientMiddleColor' => 'PlateGradientMiddleColor',
            'plateGradientEndColor'    => 'PlateGradientEndColor',
            'detailBackgroundColor'    => 'DetailBackgroundColor', 'detailBorderColor' => 'DetailBorderColor'
        ] as $fieldName => $propertyName) {
            if (array_key_exists($propertyName, $values)) {
                $style[$fieldName] = self::ColorToHex((int) $values[$propertyName]);
            }
        }
        $zoneValues = $values['ScaleZones'] ?? '[]';
        $zones = is_array($zoneValues)
            ? json_encode($zoneValues, JSON_THROW_ON_ERROR)
            : (string) $zoneValues;
        $style['scaleZones'] = self::ResolveScaleZones($zones, $minimum, $maximum);
        $style = array_merge(
            $style,
            $this->ResolveCustomPointerStyle(
                $style['pointerShape'],
                (string) ($values['CustomPointerSVG'] ?? ''),
                (string) ($values['CustomPointerPivotMode'] ?? 'svg'),
                self::FiniteFloat($values['CustomPointerPivotXPercent'] ?? 50.0, 50.0),
                self::FiniteFloat($values['CustomPointerPivotYPercent'] ?? 100.0, 100.0)
            ),
            $this->ResolveCustomAnchorStyle($style['anchorShape'], (string) ($values['CustomAnchorSVG'] ?? '')),
            $this->ResolvePlateBackgroundStyle(
                (bool) ($values['PlateBackgroundEnabled'] ?? false),
                (string) ($values['PlateBackgroundSVG'] ?? ''),
                (string) ($values['PlateBackgroundFit'] ?? 'cover'),
                (int) ($values['PlateBackgroundSizePercent'] ?? 100),
                (int) ($values['PlateBackgroundOffsetXPercent'] ?? 0),
                (int) ($values['PlateBackgroundOffsetYPercent'] ?? 0),
                (int) ($values['PlateBackgroundOpacityPercent'] ?? 100),
                self::FiniteFloat($values['PlateBackgroundRotation'] ?? 0.0, 0.0)
            )
        );

        return $style;
    }

    /** @return list<array{0: float, 1: string}> */
    private static function ResolveScaleZones(string $json, float $minimum, float $maximum): array
    {
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            return [];
        }
        try {
            $rows = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($rows) || count($rows) > 8) {
            return [];
        }
        $zones = [];
        $lastRatio = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                return [];
            }
            $end = self::FiniteFloat($row['EndValue'] ?? NAN, NAN);
            $color = (int) ($row['Color'] ?? -1);
            $ratio = ($end - $minimum) / ($maximum - $minimum);
            if (!is_finite($end) || $ratio <= $lastRatio || $ratio > 1.0 || $color < 0 || $color > 0xFFFFFF) {
                return [];
            }
            $zones[] = [$ratio, self::ColorToHex($color)];
            $lastRatio = $ratio;
        }

        return $zones;
    }

    private static function ScaleZonesAreValid(string $json, float $minimum, float $maximum): bool
    {
        try {
            $rows = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }
        if (!is_array($rows) || $rows === [] || count($rows) > 8) {
            return false;
        }

        return count(self::ResolveScaleZones($json, $minimum, $maximum)) === count($rows);
    }

    private static function FiniteFloat(mixed $value, float $fallback): float
    {
        $number = (float) $value;

        return is_finite($number) ? $number : $fallback;
    }

    /**
     * @return array{
     *     pointerPath?: string,
     *     pointerViewBox?: string,
     *     pointerPivotX?: float,
     *     pointerPivotY?: float,
     *     pointerShowAnchor?: bool,
     *     pointerError?: string
     * }
     */
    private function ResolveCustomPointerStyle(
        string $pointerShape,
        string $fileData,
        string $pivotMode = 'svg',
        float $pivotXPercent = 50.0,
        float $pivotYPercent = 100.0
    ): array {
        if ($pointerShape !== 'custom') {
            return [];
        }

        try {
            return EChartsGaugeDesign::ImportPointer(
                $fileData,
                $pivotMode,
                $pivotXPercent,
                $pivotYPercent
            );
        } catch (InvalidArgumentException $exception) {
            $this->SendDebug('ResolveCustomPointerStyle', $exception->getMessage(), 0);

            return [
                'pointerPath'    => '',
                'pointerViewBox' => '',
                'pointerError'   => $exception->getMessage()
            ];
        }
    }

    /**
     * @return array{anchorPath?: string, anchorViewBox?: string, anchorError?: string}
     */
    private function ResolveCustomAnchorStyle(string $anchorShape, string $fileData): array
    {
        if ($anchorShape !== 'custom') {
            return [];
        }

        try {
            $anchor = EChartsSvgPath::Import($fileData);

            return ['anchorPath' => $anchor['path'], 'anchorViewBox' => $anchor['viewBox']];
        } catch (InvalidArgumentException $exception) {
            $this->SendDebug('ResolveCustomAnchorStyle', $exception->getMessage(), 0);

            return [
                'anchorPath'    => '',
                'anchorViewBox' => '',
                'anchorError'   => $exception->getMessage()
            ];
        }
    }

    /**
     * @return array{
     *     plateBackgroundEnabled?: bool,
     *     plateBackgroundFit?: string,
     *     plateBackgroundSizePercent?: int,
     *     plateBackgroundOffsetXPercent?: int,
     *     plateBackgroundOffsetYPercent?: int,
     *     plateBackgroundOpacityPercent?: int,
     *     plateBackgroundRotation?: float,
     *     plateBackgroundImage?: string,
     *     plateBackgroundAspectRatio?: float,
     *     plateBackgroundError?: string
     * }
     */
    private function ResolvePlateBackgroundStyle(
        bool $enabled,
        string $fileData,
        string $fit,
        int $sizePercent,
        int $offsetXPercent,
        int $offsetYPercent,
        int $opacityPercent,
        float $rotation
    ): array {
        if (!$enabled) {
            return [];
        }

        try {
            return EChartsGaugeDesign::ImportPlateBackground(
                $fileData,
                $fit,
                $sizePercent,
                $offsetXPercent,
                $offsetYPercent,
                $opacityPercent,
                $rotation
            );
        } catch (InvalidArgumentException $exception) {
            $this->SendDebug('ResolvePlateBackgroundStyle', $exception->getMessage(), 0);

            return [
                'plateBackgroundEnabled'        => true,
                'plateBackgroundFit'            => $fit,
                'plateBackgroundSizePercent'    => $sizePercent,
                'plateBackgroundOffsetXPercent' => $offsetXPercent,
                'plateBackgroundOffsetYPercent' => $offsetYPercent,
                'plateBackgroundOpacityPercent' => $opacityPercent,
                'plateBackgroundRotation'       => $rotation,
                'plateBackgroundImage'          => '',
                'plateBackgroundAspectRatio'    => 1.0,
                'plateBackgroundError'          => $exception->getMessage()
            ];
        }
    }

    private static function IsAnglePosition(float $position): bool
    {
        return is_finite($position)
            && $position >= 0.0
            && $position < 360.0
            && abs(($position / self::ANGLE_STEP) - round($position / self::ANGLE_STEP)) < 0.000001;
    }

    private static function ColorToHex(int $color): string
    {
        return EChartsGaugeDesign::ColorToHex($color);
    }

    private function SynchronizeSourceReference(): void
    {
        $previousVariableID = $this->ReadAttributeInteger('RegisteredSourceVariableID');
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        $variableExists = $variableID > 0 && IPS_VariableExists($variableID);

        if ($previousVariableID > 0 && ($previousVariableID !== $variableID || !$variableExists)) {
            $this->UnregisterReference($previousVariableID);
            $this->UnregisterMessage($previousVariableID, VM_UPDATE);
        }

        if ($variableExists) {
            if ($previousVariableID !== $variableID) {
                $this->RegisterReference($variableID);
            }
            $this->RegisterMessage($variableID, VM_UPDATE);
            $this->WriteAttributeInteger('RegisteredSourceVariableID', $variableID);
        } else {
            $this->WriteAttributeInteger('RegisteredSourceVariableID', 0);
        }
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => match ($configurationError['Status']) {
                    self::STATUS_SOURCE_INVALID => 'Configure a numeric source variable.',
                    self::STATUS_DESIGN_INVALID => 'Configure a valid Gauge design.',
                    self::STATUS_THEME_INVALID  => 'Configure a valid ECharts theme.',
                    default                     => 'Configure a valid Gauge range.'
                }
            ];
        }

        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }

        try {
            $chart = json_decode($this->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $configuration = $this->ReadEffectiveGaugeConfiguration();
                $values = $this->ReadIPSViewDesignValues();
                $chart['theme'] = (string) $values['EChartsTheme'];
                $chart['gauge']['preset'] = (string) $values['GaugePreset'];
                $chart['gauge']['style'] = $this->GaugeStyleFromFormValues(
                    $values,
                    $configuration['minimum'],
                    $configuration['maximum']
                );
            }
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Gauge value could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'single',
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
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION
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

    private function IPSViewThemeCSS(): string
    {
        $values = $this->EffectiveIPSViewDesignValues();
        $palette = EChartsAsset::ThemePreviewPalette((string) $values['EChartsTheme']);

        return EChartsIPSViewBackground::ThemeCSS(
            $palette,
            '#echarts-gauge-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }
}
