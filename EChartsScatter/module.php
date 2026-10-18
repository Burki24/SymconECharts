<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDebugFlow.php';

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
use SymconECharts\EChartsLabelDesign;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\ScatterPairing;

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
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/ScatterPairing.php';

class EChartsScatter extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewScatter';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const ROLES = ['x', 'y', 'size'];
    private const REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];
    private const DESIGN_PROPERTY_TYPES = [
        'EChartsTheme'            => 'string',
        'ShowGrid'                => 'boolean',
        'EnableZoom'              => 'boolean',
        'EnableBrush'             => 'boolean',
        'ShowValues'              => 'boolean',
        'AdaptiveValueLabels'     => 'boolean',
        'ValueLabelContent'       => 'string',
        'ValueLabelPosition'      => 'string',
        'ValueLabelDistance'      => 'integer',
        'PointSize'               => 'integer',
        'BubbleMaxSize'           => 'integer',
        'ColorMapEnabled'         => 'boolean',
        'ColorMapDimension'       => 'string',
        'ColorMapRangeMode'       => 'string',
        'ColorMapMinimum'         => 'float',
        'ColorMapMaximum'         => 'float',
        'ColorMapLowColor'        => 'integer',
        'ColorMapHighColor'       => 'integer',
        'ColorMapShowLegend'      => 'boolean',
        'XAxisRangeMode'          => 'string',
        'XAxisMinimum'            => 'float',
        'XAxisMaximum'            => 'float',
        'YAxisRangeMode'          => 'string',
        'YAxisMinimum'            => 'float',
        'YAxisMaximum'            => 'float',
        'PointColor'              => 'integer',
        'TitleColor'              => 'integer',
        'AxisColor'               => 'integer',
        'GridColor'               => 'integer',
        'ValueColor'              => 'integer',
        'TitleFontSizePercent'    => 'integer',
        'AxisFontSizePercent'     => 'integer',
        'ValueFontSizePercent'    => 'integer',
        'AnimationEnabled'        => 'boolean',
        'AnimationDuration'       => 'integer',
        'AnimationDurationUpdate' => 'integer',
        'AnimationEasing'         => 'string',
        'AnimationEasingUpdate'   => 'string',
        'AnimationDelay'          => 'integer',
        'AnimationDelayUpdate'    => 'integer'
    ];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterTimer('ArchiveRefresh', 0, 'ECSC_RefreshArchive($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECSC');
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Range', '24h');
            $this->RegisterPropertyInteger('CustomRangeValue', 7);
            $this->RegisterPropertyString('CustomRangeUnit', 'day');
            $this->RegisterPropertyString('DataMode', 'auto');
            $this->RegisterPropertyInteger('PointBudget', 1000);
            $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
            $this->RegisterPropertyBoolean('ShowGrid', true);
            $this->RegisterPropertyBoolean('EnableZoom', true);
            $this->RegisterPropertyBoolean('EnableBrush', false);
            $this->RegisterPropertyBoolean('ShowValues', false);
            $this->RegisterPropertyBoolean('AdaptiveValueLabels', false);
            $this->RegisterPropertyString('ValueLabelContent', 'y');
            $this->RegisterPropertyString('ValueLabelPosition', 'auto');
            $this->RegisterPropertyInteger('ValueLabelDistance', 5);
            $this->RegisterPropertyInteger('PointSize', 12);
            $this->RegisterPropertyInteger('BubbleMaxSize', 48);
            $this->RegisterPropertyBoolean('ColorMapEnabled', false);
            $this->RegisterPropertyString('ColorMapDimension', 'y');
            $this->RegisterPropertyString('ColorMapRangeMode', 'data');
            $this->RegisterPropertyFloat('ColorMapMinimum', 0.0);
            $this->RegisterPropertyFloat('ColorMapMaximum', 100.0);
            $this->RegisterPropertyInteger('ColorMapLowColor', -1);
            $this->RegisterPropertyInteger('ColorMapHighColor', -1);
            $this->RegisterPropertyBoolean('ColorMapShowLegend', false);
            foreach (['X', 'Y'] as $axis) {
                $this->RegisterPropertyString($axis . 'AxisRangeMode', 'data');
                $this->RegisterPropertyFloat($axis . 'AxisMinimum', 0.0);
                $this->RegisterPropertyFloat($axis . 'AxisMaximum', 100.0);
            }
            foreach (['PointColor', 'TitleColor', 'AxisColor', 'GridColor', 'ValueColor'] as $name) {
                $this->RegisterPropertyInteger($name, -1);
            }
            foreach (['TitleFontSizePercent', 'AxisFontSizePercent', 'ValueFontSizePercent'] as $name) {
                $this->RegisterPropertyInteger($name, 100);
            }
            $this->RegisterAnimationProperties('', true, 350, 500);
            $this->RegisterIPSViewHTMLPageProperties();
            $this->RegisterEChartsIPSViewTransport();
            $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
            $this->RegisterPropertyBoolean('IPSViewUseTileTimeSettings', true);
            $this->RegisterPropertyString('IPSViewRange', '24h');
            $this->RegisterPropertyInteger('IPSViewCustomRangeValue', 7);
            $this->RegisterPropertyString('IPSViewCustomRangeUnit', 'day');
            $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
            $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
            $this->RegisterPropertyInteger('IPSViewBackgroundOpacityPercent', EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT);
            foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
                $default = match ($name) {
                    'EChartsTheme'                                                                              => EChartsAsset::THEME_AUTO,
                    'ValueLabelPosition'                                                                        => 'auto',
                    'ValueLabelContent'                                                                         => 'y',
                    'XAxisRangeMode', 'YAxisRangeMode', 'ColorMapRangeMode'                                     => 'data',
                    'ColorMapDimension'                                                                         => 'y',
                    'AnimationEasing', 'AnimationEasingUpdate'                                                  => 'cubicInOut',
                    'ShowGrid', 'EnableZoom', 'AnimationEnabled'                                                => true,
                    'ShowValues', 'AdaptiveValueLabels', 'EnableBrush', 'ColorMapEnabled', 'ColorMapShowLegend' => false,
                    'PointSize'                                                                                 => 12, 'BubbleMaxSize' => 48,
                    'ValueLabelDistance'                                                                        => 5,
                    'XAxisMinimum', 'YAxisMinimum', 'ColorMapMinimum'                                           => 0.0,
                    'XAxisMaximum', 'YAxisMaximum', 'ColorMapMaximum'                                           => 100.0,
                    'TitleFontSizePercent', 'AxisFontSizePercent', 'ValueFontSizePercent'                       => 100,
                    'AnimationDuration'                                                                         => 350, 'AnimationDurationUpdate' => 500,
                    'AnimationDelay', 'AnimationDelayUpdate'                                                    => 0,
                    default                                                                                     => -1
                };
                match ($type) {
                    'string'  => $this->RegisterPropertyString('IPSView' . $name, $default),
                    'boolean' => $this->RegisterPropertyBoolean('IPSView' . $name, $default),
                    'float'   => $this->RegisterPropertyFloat('IPSView' . $name, $default),
                    default   => $this->RegisterPropertyInteger('IPSView' . $name, $default)
                };
            }
            $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
            $this->RegisterAttributeString('LastError', '');
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function ApplyChanges(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::ApplyChanges();
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Scatter for IPSView'), 90);
            $this->Initialize();
            if (IPS_GetKernelRunlevel() === KR_READY) {
                $this->PublishVisualizationState();
                $this->PublishIPSViewHTML();
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function GetCompatibleParents(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = json_encode(['type' => 'connect', 'moduleIDs' => [self::GATEWAY_MODULE_ID]], JSON_THROW_ON_ERROR);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetConfigurationForm(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $form = $this->LoadConfigurationForm();
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            EChartsLabelDesign::InsertAfterRowContaining($form['elements'], 'ValueLabelContent', [
                ['type' => 'RowLayout', 'items' => EChartsLabelDesign::CartesianControls()]
            ]);
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['CustomRangeValue', 'CustomRangeUnit'],
                $this->ReadPropertyString('Range') === 'custom'
            );
            foreach (['X', 'Y'] as $axis) {
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    [$axis . 'AxisMinimum', $axis . 'AxisMaximum'],
                    $this->ReadPropertyString($axis . 'AxisRangeMode') === 'manual'
                );
            }
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['ColorMapMinimum', 'ColorMapMaximum'],
                $this->ReadPropertyString('ColorMapRangeMode') === 'manual'
            );
            $form['elements'] = $this->WithIPSViewTimeSettingsByRange(
                $form['elements'],
                $this->BuildIPSViewTimeSettings($form['elements'])
            );
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            foreach (['X', 'Y'] as $axis) {
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['IPSView' . $axis . 'AxisMinimum', 'IPSView' . $axis . 'AxisMaximum'],
                    $this->ReadPropertyString('IPSView' . $axis . 'AxisRangeMode') === 'manual'
                );
            }
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['IPSViewColorMapMinimum', 'IPSViewColorMapMaximum'],
                $this->ReadPropertyString('IPSViewColorMapRangeMode') === 'manual'
            );
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECSC');
            $form = $this->WithIPSViewDesignFormState($form, 'ECSC');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Points and display', 'start' => 'EChartsTheme'],
                ['caption' => 'Axis ranges', 'start' => 'XAxisRangeMode'],
                ['caption' => 'Value-based colors', 'start' => 'ColorMapEnabled'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'Range', 'CustomRangeValue', 'CustomRangeUnit', 'DataMode', 'PointBudget',
                    'IPSViewUseTileDesign', 'IPSViewUseTileTimeSettings', 'IPSViewRange',
                    'IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit',
                    'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys(self::DESIGN_PROPERTY_TYPES),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_PROPERTY_TYPES))
            );

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECSC', $fields));
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function UpdateTimeRangeForm(string $Range): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            foreach (['CustomRangeValue', 'CustomRangeUnit'] as $field) {
                $this->UpdateFormField($field, 'visible', $Range === 'custom');
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateIPSViewTimeRangeForm(bool $IPSViewUseTileTimeSettings, string $IPSViewRange): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateFormField('IPSViewRange', 'visible', !$IPSViewUseTileTimeSettings);
            foreach (['IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit'] as $field) {
                $this->UpdateFormField($field, 'visible', !$IPSViewUseTileTimeSettings && $IPSViewRange === 'custom');
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateAxisRangeForm(bool $IPSView, string $XAxisRangeMode, string $YAxisRangeMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $prefix = $IPSView ? 'IPSView' : '';
            foreach (['X' => $XAxisRangeMode, 'Y' => $YAxisRangeMode] as $axis => $mode) {
                foreach (['Minimum', 'Maximum'] as $bound) {
                    $this->UpdateFormField($prefix . $axis . 'Axis' . $bound, 'visible', $mode === 'manual');
                }
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateColorMapRangeForm(bool $IPSView, string $ColorMapRangeMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $prefix = $IPSView ? 'IPSView' : '';
            foreach (['Minimum', 'Maximum'] as $bound) {
                $this->UpdateFormField($prefix . 'ColorMap' . $bound, 'visible', $ColorMapRangeMode === 'manual');
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__, ['Aktion' => $Ident]);
        try {
            if (!$this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
                throw new InvalidArgumentException('Unknown action: ' . $Ident);
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function CopyTileDesignToIPSView(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
                $value = match ($type) {
                    'string'  => $this->ReadPropertyString($name),
                    'boolean' => $this->ReadPropertyBoolean($name),
                    'float'   => $this->ReadPropertyFloat($name),
                    default   => $this->ReadPropertyInteger($name)
                };
                IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $value);
            }
            IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
            IPS_ApplyChanges($this->InstanceID);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function GetScatterData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetScatterDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetScatterDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();

            return $debugFlowResult = json_encode(['valid' => $error === null, 'lastError' => $error['Message']
                ?? $this->ReadAttributeString('LastError')], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetVisualizationTile(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->RenderScatterHTMLPage(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetIPSViewHTML(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->RenderScatterHTMLPage(true);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function ReceiveData(string $JSONString): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->HandleEChartsIPSViewRequest(
                $JSONString,
                self::DATA_ID_FROM_PARENT,
                fn (): array => $this->BuildVisualizationState(true)
            );
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function RefreshArchive(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->PublishVisualizationState();
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function RetryTransient(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->PublishVisualizationState();
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /** @param array<int,mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__, ['Quelle' => $SenderID, 'Nachricht' => $Message]);
        try {
            if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
                $this->Initialize();
                $this->PublishVisualizationState();
                $this->PublishIPSViewHTML();
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    protected function CurrentTimestamp(): int
    {
        return time();
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $sources = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false);
        $byRole = [];
        foreach ($sources['items'] as $source) {
            if (in_array($source['Role'] ?? null, self::ROLES, true)) {
                $byRole[$source['Role']] = $source;
            }
        }
        $xLabel = ($byRole['x']['label'] ?? 'X') . (empty($byRole['x']['unit']) ? '' : ' (' . $byRole['x']['unit'] . ')');
        $yLabel = ($byRole['y']['label'] ?? 'Y') . (empty($byRole['y']['unit']) ? '' : ' (' . $byRole['y']['unit'] . ')');
        $themeColor = $palette['seriesColors'][0] ?? $palette['accent'];
        $pointColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'PointColor', 'integer', $ipsView), $themeColor);
        $colorMapEnabled = $this->BarPreviewValue($values, 'ColorMapEnabled', 'boolean', $ipsView);
        $colorMapDimension = $this->BarPreviewValue($values, 'ColorMapDimension', 'string', $ipsView);
        $colorMapLow = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'ColorMapLowColor', 'integer', $ipsView),
            $palette['seriesColors'][0] ?? $palette['accent']
        );
        $colorMapHigh = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'ColorMapHighColor', 'integer', $ipsView),
            $palette['seriesColors'][3] ?? $palette['accent']
        );
        $colorMapSamples = [];
        for ($sampleIndex = 0; $sampleIndex < 13; $sampleIndex++) {
            $colorMapSamples[] = match ($colorMapDimension) {
                'x'     => 1.8 + $sampleIndex,
                'size'  => (float) $sampleIndex,
                default => 92 - $sampleIndex * 2.7 + sin($sampleIndex * 0.8) * 6
            };
        }
        $colorMapMinimum = min($colorMapSamples);
        $colorMapMaximum = max($colorMapSamples);
        if ($this->BarPreviewValue($values, 'ColorMapRangeMode', 'string', $ipsView) === 'manual') {
            $configuredMinimum = $this->BarPreviewValue($values, 'ColorMapMinimum', 'float', $ipsView);
            $configuredMaximum = $this->BarPreviewValue($values, 'ColorMapMaximum', 'float', $ipsView);
            if ($configuredMinimum < $configuredMaximum) {
                $colorMapMinimum = $configuredMinimum;
                $colorMapMaximum = $configuredMaximum;
            }
        }
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['muted']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['track']);
        $titleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']);
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Chart preview'),
            $titleColor,
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg .= '<style>text{font-family:Arial,sans-serif}</style>';
        if ($this->BarPreviewValue($values, 'EnableBrush', 'boolean', $ipsView)) {
            $svg .= '<rect x="640" y="37" width="18" height="14" fill="none" stroke="'
                . EChartsBarPreview::E($palette['accent']) . '" stroke-width="1.5" stroke-dasharray="3 2"/>';
            $svg .= '<path d="M 665 39 L 665 49 M 660 44 L 670 44" stroke="'
                . EChartsBarPreview::E($palette['muted']) . '" stroke-width="1.5"/>';
        }
        if ($colorMapEnabled && $this->BarPreviewValue($values, 'ColorMapShowLegend', 'boolean', $ipsView)) {
            $colorMapRole = $byRole[$colorMapDimension] ?? [];
            $colorMapDecimals = (int) ($colorMapRole['decimals'] ?? 1);
            $colorMapUnit = (string) ($colorMapRole['unit'] ?? '');
            $colorMapLanguage = $this->ResolveHelperTranslationLanguage();
            $svg .= '<defs><linearGradient id="scatter-preview-color-map"><stop offset="0%" stop-color="'
                . EChartsBarPreview::E($colorMapLow) . '"/><stop offset="100%" stop-color="'
                . EChartsBarPreview::E($colorMapHigh) . '"/></linearGradient></defs>';
            $svg .= '<text x="242" y="54" text-anchor="end" font-size="10" fill="'
                . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                    $colorMapMinimum,
                    $colorMapDecimals,
                    $colorMapUnit,
                    $colorMapLanguage
                )) . '</text>';
            $svg .= '<rect x="250" y="44" width="220" height="8" fill="url(#scatter-preview-color-map)"/>';
            $svg .= '<text x="478" y="54" font-size="10" fill="'
                . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                    $colorMapMaximum,
                    $colorMapDecimals,
                    $colorMapUnit,
                    $colorMapLanguage
                )) . '</text>';
        }
        $left = 105;
        $right = 670;
        $top = 70;
        $bottom = 286;
        $xRange = $this->PreviewAxisBounds($values, $ipsView, 'X', 1.0, 15.0);
        $yRange = $this->PreviewAxisBounds($values, $ipsView, 'Y', 50.0, 100.0);
        $axisFontSize = max(8, min(20, (int) round(11 * $this->BarPreviewValue(
            $values,
            'AxisFontSizePercent',
            'integer',
            $ipsView
        ) / 100)));
        for ($i = 0; $i <= 4; $i++) {
            $x = $left + ($right - $left) * $i / 4;
            $y = $bottom - ($bottom - $top) * $i / 4;
            if ($i > 0 && $i < 4 && $this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
                $svg .= '<path d="M ' . EChartsBarPreview::N($x) . ' ' . $top . ' V ' . $bottom
                    . ' M ' . $left . ' ' . EChartsBarPreview::N($y) . ' H ' . $right
                    . '" stroke="' . EChartsBarPreview::E($gridColor) . '" opacity="0.65"/>';
            }
            $svg .= '<text x="' . EChartsBarPreview::N($x) . '" y="305" text-anchor="middle" font-size="'
                . $axisFontSize . '" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E(EChartsBarPreview::N($xRange[0] + ($xRange[1] - $xRange[0]) * $i / 4))
                . '</text>';
            $svg .= '<text x="92" y="' . EChartsBarPreview::N($y + 4) . '" text-anchor="end" font-size="'
                . $axisFontSize . '" fill="' . EChartsBarPreview::E($axisColor) . '">'
                . EChartsBarPreview::E(EChartsBarPreview::N($yRange[0] + ($yRange[1] - $yRange[0]) * $i / 4))
                . '</text>';
        }
        $svg .= '<path d="M ' . $left . ' ' . $top . ' V ' . $bottom . ' H ' . $right
            . '" fill="none" stroke="' . EChartsBarPreview::E($axisColor) . '"/>';
        if ($this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView)) {
            $sliderColor = EChartsBarPreview::E($palette['accent']);
            $svg .= '<path d="M ' . $left . ' 320 H ' . $right . ' M 692 ' . $top . ' V ' . $bottom
                . '" fill="none" stroke="' . EChartsBarPreview::E($gridColor)
                . '" stroke-width="7" stroke-linecap="round"/>';
            $svg .= '<path d="M ' . $left . ' 320 H ' . $right . ' M 692 ' . $top . ' V ' . $bottom
                . '" fill="none" stroke="' . $sliderColor
                . '" stroke-width="3" stroke-linecap="round"/>';
        }
        $svg .= '<defs><clipPath id="scatter-preview-plot"><rect x="' . $left . '" y="' . $top
            . '" width="' . ($right - $left) . '" height="' . ($bottom - $top) . '"/></clipPath></defs>';
        $size = max(4, min(60, $this->BarPreviewValue($values, 'PointSize', 'integer', $ipsView)));
        $maxBubble = max(8, min(100, $this->BarPreviewValue($values, 'BubbleMaxSize', 'integer', $ipsView)));
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $adaptiveLabels = $this->BarPreviewValue($values, 'AdaptiveValueLabels', 'boolean', $ipsView);
        $valueColor = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView),
            $palette['text']
        );
        $valueFontSize = max(8, min(20, (int) round(10 * $this->BarPreviewValue(
            $values,
            'ValueFontSizePercent',
            'integer',
            $ipsView
        ) / 100)));
        $position = $this->BarPreviewValue($values, 'ValueLabelPosition', 'string', $ipsView);
        $distance = $this->BarPreviewValue($values, 'ValueLabelDistance', 'integer', $ipsView);
        $content = $this->BarPreviewValue($values, 'ValueLabelContent', 'string', $ipsView);
        for ($i = 0; $i < 13; $i++) {
            $xValue = 1.8 + $i;
            $yValue = 92 - $i * 2.7 + sin($i * 0.8) * 6;
            if ($xValue < $xRange[0] || $xValue > $xRange[1]
                || $yValue < $yRange[0] || $yValue > $yRange[1]) {
                continue;
            }
            $x = $left + ($xValue - $xRange[0]) / ($xRange[1] - $xRange[0]) * ($right - $left);
            $y = $bottom - ($yValue - $yRange[0]) / ($yRange[1] - $yRange[0]) * ($bottom - $top);
            $radius = isset($byRole['size']) ? ($size + ($maxBubble - $size) * $i / 12) / 2 : $size / 2;
            $sampleColor = $colorMapEnabled ? self::PreviewInterpolatedColor(
                $colorMapLow,
                $colorMapHigh,
                ($colorMapSamples[$i] - $colorMapMinimum) / max(0.000001, $colorMapMaximum - $colorMapMinimum)
            ) : $pointColor;
            $svg .= '<circle cx="' . EChartsBarPreview::N($x) . '" cy="' . EChartsBarPreview::N($y)
                . '" r="' . EChartsBarPreview::N($radius) . '" clip-path="url(#scatter-preview-plot)" fill="'
                . EChartsBarPreview::E($sampleColor) . '" fill-opacity="0.8"/>';
            if ($showValues && $i % ($adaptiveLabels ? 6 : 4) === 1) {
                $label = EChartsLabelDesign::PreviewRect(
                    $x - $radius,
                    $y - $radius,
                    $radius * 2,
                    $radius * 2,
                    $position,
                    $distance
                );
                $formattedX = EChartsBarPreview::FormatValue(
                    $xValue,
                    (int) ($byRole['x']['decimals'] ?? 0),
                    (string) ($byRole['x']['unit'] ?? ''),
                    $this->ResolveHelperTranslationLanguage()
                );
                $formattedY = EChartsBarPreview::FormatValue(
                    $yValue,
                    (int) ($byRole['y']['decimals'] ?? 0),
                    (string) ($byRole['y']['unit'] ?? ''),
                    $this->ResolveHelperTranslationLanguage()
                );
                $formatted = match ($content) {
                    'x'     => $formattedX,
                    'both'  => $formattedX . ' / ' . $formattedY,
                    default => $formattedY
                };
                $svg .= '<text x="' . EChartsBarPreview::N($label['x']) . '" y="'
                    . EChartsBarPreview::N($label['y']) . '" text-anchor="' . $label['anchor']
                    . '" font-size="' . $valueFontSize . '" fill="' . EChartsBarPreview::E($valueColor)
                    . '">' . EChartsBarPreview::E($formatted) . '</text>';
            }
        }
        $svg .= '<text x="387" y="340" text-anchor="middle" font-size="' . ($axisFontSize + 2)
            . '" fill="' . EChartsBarPreview::E($axisColor) . '">'
            . EChartsBarPreview::E($xLabel) . '</text>';
        $svg .= '<text x="26" y="178" transform="rotate(-90 26 178)" text-anchor="middle" font-size="'
            . ($axisFontSize + 2) . '" fill="' . EChartsBarPreview::E($axisColor) . '">'
            . EChartsBarPreview::E($yLabel) . '</text>';

        return $svg . '</svg>';
    }

    private static function PreviewInterpolatedColor(string $low, string $high, float $fraction): string
    {
        $fraction = max(0.0, min(1.0, $fraction));
        $channels = [];
        foreach ([1, 3, 5] as $offset) {
            $start = hexdec(substr($low, $offset, 2));
            $end = hexdec(substr($high, $offset, 2));
            $channels[] = (int) round($start + ($end - $start) * $fraction);
        }

        return sprintf('#%02X%02X%02X', ...$channels);
    }

    /** @param array<string,mixed> $values @return array{float,float} */
    private function PreviewAxisBounds(array $values, bool $ipsView, string $axis, float $minimum, float $maximum): array
    {
        $mode = $this->BarPreviewValue($values, $axis . 'AxisRangeMode', 'string', $ipsView);
        if ($mode === 'manual') {
            $configuredMinimum = $this->BarPreviewValue($values, $axis . 'AxisMinimum', 'float', $ipsView);
            $configuredMaximum = $this->BarPreviewValue($values, $axis . 'AxisMaximum', 'float', $ipsView);
            if (is_finite($configuredMinimum) && is_finite($configuredMaximum)
                && $configuredMinimum < $configuredMaximum) {
                return [$configuredMinimum, $configuredMaximum];
            }
        }

        return $mode === 'zero' ? [min(0.0, $minimum), max(0.0, $maximum)] : [$minimum, $maximum];
    }

    private function GetScatterDataForOutput(bool $ipsView): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->DebugEChartsSetStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }
        $sources = $this->GetValidatedSources();
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
        if ($query['Mode'] !== 'aggregated') {
            throw new RuntimeException('Scatter pairing requires completed archive intervals.');
        }
        $archive = [];
        $metadata = [];
        $truncated = false;
        foreach ($sources as $role => $source) {
            $response = $this->ReadArchiveSource($source, $query);
            $archive[$role] = $response['Points'];
            $metadata[$role] = [
                'variableID' => $source['VariableID'], 'label' => $source['Label'],
                'unit'       => $source['Unit'], 'decimals' => $source['Decimals']
            ];
            $truncated = $truncated || $response['Truncated'];
        }
        $points = ScatterPairing::Pair($archive['x'], $archive['y'], $archive['size'] ?? null);
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'scatter',
            'variant'       => isset($sources['size']) ? 'bubble' : 'scatter',
            'theme'         => $this->EffectiveTheme($ipsView),
            'scatter'       => ['title' => $this->ReadPropertyString('Title'), 'style' => $this->ReadDesignStyle($ipsView)],
            'roles'         => $metadata,
            'range'         => [
                'key'              => $this->EffectiveRange($ipsView),
                'startTimestamp'   => $query['StartTimestamp'],
                'endTimestamp'     => $query['EndTimestamp'],
                'aggregationLevel' => $query['AggregationLevel'],
                'pointBudget'      => $this->ReadPropertyInteger('PointBudget')
            ],
            'points'    => $points,
            'truncated' => $truncated
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderScatterHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Scatter',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-scatter-root', 'echarts-scatter'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure X and Y archive sources.'                                         => $this->Translate('Configure X and Y archive sources.'),
                'Connect an active EChartsGateway.'                                          => $this->Translate('Connect an active EChartsGateway.'),
                'The Scatter values could not be loaded.'                                    => $this->Translate('The Scatter values could not be loaded.'),
                'Select points'                                                              => $this->Translate('Select points'),
                'Clear selection'                                                            => $this->Translate('Clear selection'),
                'Selected points'                                                            => $this->Translate('Selected points'),
                'Scatter data could not be refreshed. Showing the last available values.'    => $this->Translate(
                    'Scatter data could not be refreshed. Showing the last available values.'
                ),
                'No common completed archive intervals.'                => $this->Translate('No common completed archive intervals.'),
                'The selected range was truncated by the point budget.' => $this->Translate(
                    'The selected range was truncated by the point budget.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::ScatterJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{ECHARTS_ZOOM_SCRIPT}}'      => EChartsAsset::ZoomJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->DebugEChartsSetStatus(IS_INACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 0);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $this->SynchronizeSourceReference();
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->DebugEChartsSetStatus($error['Status']);
            return;
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
            return;
        }
        $this->SetSummary($this->ReadPropertyString('Range'));
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $sources = $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!EChartsArchiveQuery::IsValidRange(
            $this->ReadPropertyString('Range'),
            $this->ReadPropertyInteger('CustomRangeValue'),
            $this->ReadPropertyString('CustomRangeUnit')
        ) || !in_array(
            $this->ReadPropertyString('DataMode'),
            ['auto', ...array_keys(EChartsArchiveQuery::AGGREGATION_LEVELS)],
            true
        )
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > 2000
            || !$this->IsValidDesign('')) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The Scatter settings are invalid.'];
        }
        if (($this->ReadPropertyBoolean('ColorMapEnabled')
            && $this->ReadPropertyString('ColorMapDimension') === 'size')
            || ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
                && $this->ReadPropertyBoolean('IPSViewColorMapEnabled')
                && $this->ReadPropertyString('IPSViewColorMapDimension') === 'size')) {
            if (!isset($sources['size'])) {
                return ['Status' => self::STATUS_CONFIGURATION_INVALID,
                    'Message'    => 'A bubble-size source is required for size-based colors.'];
            }
        }
        if ($this->IsIPSViewHTMLPageEnabled()
            && (!EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
                || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
                || (!$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
                    && !EChartsArchiveQuery::IsValidRange(
                        $this->ReadPropertyString('IPSViewRange'),
                        $this->ReadPropertyInteger('IPSViewCustomRangeValue'),
                        $this->ReadPropertyString('IPSViewCustomRangeUnit')
                    )))) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The IPSView Scatter settings are invalid.'];
        }

        return null;
    }

    /** @return array<string,array{VariableID:int,Label:string,Unit:string,Decimals:int,Reducer:string}> */
    private function GetValidatedSources(): array
    {
        try {
            $raw = json_decode($this->ReadPropertyString('Sources'), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Scatter sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 2 || count($raw) > 3) {
            throw new InvalidArgumentException('Configure one X and one Y source, optionally one size source.');
        }
        $byRole = [];
        $seen = [];
        foreach ($raw as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Scatter source settings are invalid.');
            }
            $variableID = $source['VariableID'] ?? null;
            $role = $source['Role'] ?? null;
            $label = $source['Label'] ?? '';
            $unit = $source['Unit'] ?? '';
            $decimals = $source['Decimals'] ?? 1;
            $reducer = $source['Reducer'] ?? 'auto';
            $usePresentation = $source['UseVariablePresentation'] ?? true;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || isset($seen[$variableID]) || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)
                || !is_string($role) || !in_array($role, self::ROLES, true) || isset($byRole[$role])
                || !is_string($label) || !is_string($unit) || !is_int($decimals)
                || $decimals < 0 || $decimals > 6 || !is_bool($usePresentation)
                || !is_string($reducer) || !in_array($reducer, self::REDUCERS, true)) {
                throw new InvalidArgumentException('Scatter roles need distinct numeric variable IDs and valid settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                $usePresentation
            );
            $byRole[$role] = [
                'VariableID' => $variableID, 'Label' => trim($label),
                'Unit'       => $presentation['unit'], 'Decimals' => $presentation['decimals'], 'Reducer' => $reducer
            ];
            $seen[$variableID] = true;
        }
        if (!isset($byRole['x'], $byRole['y'])) {
            throw new InvalidArgumentException('Scatter needs exactly one X and one Y source.');
        }
        $labels = EChartsSourceIdentity::LabelsForSources(array_values($byRole));
        foreach ($byRole as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
        }
        unset($source);

        return $byRole;
    }

    /** @param array{VariableID:int,Reducer:string} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if ($query['Empty']) {
                return $debugFlowResult = ['Points' => [], 'Truncated' => false];
            }
            $payload = [
                'VariableID'       => $source['VariableID'],
                'StartTimestamp'   => $query['StartTimestamp'],
                'EndTimestamp'     => $query['EndTimestamp'],
                'Mode'             => 'aggregated',
                'Reducer'          => $source['Reducer'],
                'Limit'            => $query['Limit'],
                'AggregationLevel' => $query['AggregationLevel']
            ];
            $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
            try {
                $response = EChartsDataProtocol::DecodeResponse(
                    $this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                        self::DATA_ID_TO_PARENT,
                        EChartsDataProtocol::CreateRequest($operation, $payload)
                    )),
                    $operation
                );
            } catch (Throwable $exception) {
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway returned an invalid Scatter archive response.', 0, $exception);
            }
            if (!$response['Success']) {
                $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
                $this->WriteAttributeString('LastError', $code);
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway archive request failed: ' . $code);
            }
            $archive = $response['Payload'];
            if (($archive['VariableID'] ?? null) !== $source['VariableID']
                || !is_array($archive['Points'] ?? null) || !is_bool($archive['Truncated'] ?? null)) {
                throw new RuntimeException('The gateway returned invalid Scatter archive data.');
            }

            return $debugFlowResult = $archive;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    private function SynchronizeSourceReference(): void
    {
        $previous = json_decode($this->ReadAttributeString('RegisteredSourceVariableIDs'), true);
        $previous = is_array($previous) ? array_values(array_filter($previous, is_int(...))) : [];
        $raw = json_decode($this->ReadPropertyString('Sources'), true);
        $current = [];
        foreach (is_array($raw) ? $raw : [] as $source) {
            $id = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($id) && $id > 0 && !in_array($id, $current, true)) {
                $current[] = $id;
            }
        }
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

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        if ($this->GetConfigurationError() !== null) {
            return ['schemaVersion' => 1, 'family' => 'scatter', 'status' => 'error',
                'chart'             => null, 'error' => 'Configure X and Y archive sources.'];
        }
        if (!$this->HasActiveParent()) {
            return ['schemaVersion' => 1, 'family' => 'scatter', 'status' => 'error',
                'chart'             => null, 'error' => 'Connect an active EChartsGateway.'];
        }
        try {
            $chart = json_decode($this->GetScatterDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return ['schemaVersion' => 1, 'family' => 'scatter', 'status' => 'error',
                'chart'             => null, 'error' => 'The Scatter values could not be loaded.'];
        }

        return ['schemaVersion' => 1, 'family' => 'scatter', 'status' => 'ready',
            'chart'             => $chart, 'error' => null];
    }

    private function PublishVisualizationState(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $state = $this->BuildVisualizationState();
                $states = [$state];
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'Diagrammzustand an die native Kachel übergeben; Darstellung im Browser ist noch nicht bestätigt.');
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
                $this->ScheduleTransientRefresh($states, 'The Scatter values could not be loaded.');
            } catch (Throwable $exception) {
                $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    private function PublishIPSViewHTML(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if (!$this->IsIPSViewHTMLPageEnabled()) {
                return;
            }
            try {
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'HTML-Ausgabe an die IPSView-Statusvariable übergeben; Dokumentinhalt bleibt geschützt.', ['Ziel' => self::IPSVIEW_OUTPUT_IDENT]);
                $this->UpdateIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->GetIPSViewHTML());
            } catch (Throwable $exception) {
                $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!$this->AnimationIsValid($prefix)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'ValueLabelContent'), ['x', 'y', 'both'], true)
            || !EChartsLabelDesign::IsValidPosition(
                $this->ReadPropertyString($prefix . 'ValueLabelPosition'),
                EChartsLabelDesign::CARTESIAN_POSITIONS
            )
            || !EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . 'ValueLabelDistance'))
            || $this->ReadPropertyInteger($prefix . 'PointSize') < 4
            || $this->ReadPropertyInteger($prefix . 'PointSize') > 60
            || $this->ReadPropertyInteger($prefix . 'BubbleMaxSize') < 8
            || $this->ReadPropertyInteger($prefix . 'BubbleMaxSize') > 100
            || $this->ReadPropertyInteger($prefix . 'BubbleMaxSize') < $this->ReadPropertyInteger($prefix . 'PointSize')) {
            return false;
        }
        $colorRangeMinimum = $this->ReadPropertyFloat($prefix . 'ColorMapMinimum');
        $colorRangeMaximum = $this->ReadPropertyFloat($prefix . 'ColorMapMaximum');
        if (!in_array($this->ReadPropertyString($prefix . 'ColorMapDimension'), self::ROLES, true)
            || !in_array($this->ReadPropertyString($prefix . 'ColorMapRangeMode'), ['data', 'manual'], true)
            || !is_finite($colorRangeMinimum) || !is_finite($colorRangeMaximum)
            || ($this->ReadPropertyString($prefix . 'ColorMapRangeMode') === 'manual'
                && $colorRangeMinimum >= $colorRangeMaximum)) {
            return false;
        }
        foreach (['X', 'Y'] as $axis) {
            $mode = $this->ReadPropertyString($prefix . $axis . 'AxisRangeMode');
            $minimum = $this->ReadPropertyFloat($prefix . $axis . 'AxisMinimum');
            $maximum = $this->ReadPropertyFloat($prefix . $axis . 'AxisMaximum');
            if (!in_array($mode, ['data', 'zero', 'manual'], true)
                || !is_finite($minimum) || !is_finite($maximum)
                || ($mode === 'manual' && $minimum >= $maximum)) {
                return false;
            }
        }
        foreach (['TitleFontSizePercent', 'AxisFontSizePercent', 'ValueFontSizePercent'] as $name) {
            if ($this->ReadPropertyInteger($prefix . $name) < 50
                || $this->ReadPropertyInteger($prefix . $name) > 150) {
                return false;
            }
        }
        foreach (['PointColor', 'TitleColor', 'AxisColor', 'GridColor', 'ValueColor',
            'ColorMapLowColor', 'ColorMapHighColor'] as $name) {
            if ($this->ReadPropertyInteger($prefix . $name) < -1
                || $this->ReadPropertyInteger($prefix . $name) > 0xFFFFFF) {
                return false;
            }
        }

        return true;
    }

    private function EffectiveRange(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyString('IPSViewRange') : $this->ReadPropertyString('Range');
    }

    private function EffectiveCustomRangeValue(bool $ipsView): int
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyInteger('IPSViewCustomRangeValue') : $this->ReadPropertyInteger('CustomRangeValue');
    }

    private function EffectiveCustomRangeUnit(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
            ? $this->ReadPropertyString('IPSViewCustomRangeUnit') : $this->ReadPropertyString('CustomRangeUnit');
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('IPSViewEChartsTheme') : $this->ReadPropertyString('EChartsTheme');
    }

    /** @return array<string,bool|float|int|string> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = $this->ReadAnimationStyle($prefix);
        foreach (['ShowGrid', 'EnableZoom', 'EnableBrush', 'ShowValues', 'AdaptiveValueLabels',
            'ColorMapEnabled', 'ColorMapShowLegend'] as $name) {
            $style[lcfirst($name)] = $this->ReadPropertyBoolean($prefix . $name);
        }
        $style['valueLabelPosition'] = $this->ReadPropertyString($prefix . 'ValueLabelPosition');
        $style['valueLabelContent'] = $this->ReadPropertyString($prefix . 'ValueLabelContent');
        $style['colorMapDimension'] = $this->ReadPropertyString($prefix . 'ColorMapDimension');
        $style['colorMapRangeMode'] = $this->ReadPropertyString($prefix . 'ColorMapRangeMode');
        $style['colorMapMinimum'] = $this->ReadPropertyFloat($prefix . 'ColorMapMinimum');
        $style['colorMapMaximum'] = $this->ReadPropertyFloat($prefix . 'ColorMapMaximum');
        foreach (['X', 'Y'] as $axis) {
            $style[lcfirst($axis) . 'AxisRangeMode'] = $this->ReadPropertyString($prefix . $axis . 'AxisRangeMode');
            foreach (['Minimum', 'Maximum'] as $bound) {
                $style[lcfirst($axis) . 'Axis' . $bound] = $this->ReadPropertyFloat($prefix . $axis . 'Axis' . $bound);
            }
        }
        foreach (['ValueLabelDistance', 'PointSize', 'BubbleMaxSize', 'TitleFontSizePercent',
            'AxisFontSizePercent', 'ValueFontSizePercent'] as $name) {
            $style[lcfirst($name)] = $this->ReadPropertyInteger($prefix . $name);
        }
        foreach (['PointColor', 'TitleColor', 'AxisColor', 'GridColor', 'ValueColor',
            'ColorMapLowColor', 'ColorMapHighColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            $style[lcfirst($name)] = $color < 0 ? '' : EChartsAsset::ColorToHex($color);
        }

        return $style;
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-scatter-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string,mixed>> $elements @return list<array<string,mixed>> */
    private function BuildIPSViewTimeSettings(array $elements): array
    {
        $rangeRow = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? '') === 'RowLayout'
                && in_array('Range', array_column($element['items'] ?? [], 'name'), true)) {
                $rangeRow = $element['items'];
                break;
            }
        }
        if (!is_array($rangeRow)) {
            throw new RuntimeException('The Scatter time range form section is missing.');
        }
        $independent = !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings');
        $timeItems = [];
        foreach ($rangeRow as $item) {
            if (!in_array($item['name'] ?? null, ['Range', 'CustomRangeValue', 'CustomRangeUnit'], true)) {
                continue;
            }
            $item['name'] = 'IPSView' . $item['name'];
            $item['visible'] = $item['name'] === 'IPSViewRange'
                ? $independent : ($independent && $this->ReadPropertyString('IPSViewRange') === 'custom');
            unset($item['onChange']);
            $timeItems[] = $item;
        }
        $action = 'ECSC_UpdateIPSViewTimeRangeForm($id, $IPSViewUseTileTimeSettings, $IPSViewRange);';
        $timeItems[0]['caption'] = 'IPSView time range';
        $timeItems[0]['onChange'] = $action;

        return [
            ['type'       => 'CheckBox', 'name' => 'IPSViewUseTileTimeSettings',
                'caption' => 'Use Tile time settings', 'onChange' => $action],
            ['type' => 'Label', 'caption' => 'Disable to use an independent IPSView time range.'],
            ['type' => 'RowLayout', 'items' => $timeItems]
        ];
    }

    /** @param list<array<string,mixed>> $elements @return array<string,mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $designRows = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? '') === 'ExpansionPanel'
                && ($element['caption'] ?? '') === 'Tile designer') {
                $designRows = $element['items'] ?? null;
                break;
            }
        }
        if (!is_array($designRows)) {
            throw new RuntimeException('The Scatter Tile designer section is missing.');
        }
        $ipsViewRows = [];
        foreach ($designRows as $row) {
            if (($row['type'] ?? '') !== 'RowLayout') {
                continue;
            }
            $items = [];
            foreach ($row['items'] ?? [] as $item) {
                $name = $item['name'] ?? null;
                if (!is_string($name) || !array_key_exists($name, self::DESIGN_PROPERTY_TYPES)) {
                    continue;
                }
                $item['name'] = 'IPSView' . $name;
                unset($item['onChange']);
                if (in_array($name, ['XAxisRangeMode', 'YAxisRangeMode'], true)) {
                    $item['onChange'] = 'ECSC_UpdateAxisRangeForm($id, true, '
                        . '$IPSViewXAxisRangeMode, $IPSViewYAxisRangeMode);';
                } elseif ($name === 'ColorMapRangeMode') {
                    $item['onChange'] = 'ECSC_UpdateColorMapRangeForm($id, true, $IPSViewColorMapRangeMode);';
                }
                $items[] = $item;
            }
            if ($items !== []) {
                $ipsViewRows[] = ['type' => 'RowLayout', 'items' => $items];
            }
        }

        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false, 'items' => [
            ...$this->IPSViewHTMLPageFormItems(
                'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
            ),
            EChartsIPSViewBackground::FormRow(),
            ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
            ['type' => 'Label', 'caption' => 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.'],
            ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                'onClick' => 'ECSC_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
            ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                'expanded' => false, 'items' => $ipsViewRows]
        ]];
    }
}
