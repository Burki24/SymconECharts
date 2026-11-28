<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDebugFlow.php';
require_once __DIR__ . '/../libs/EChartsJSLiveTarget.php';

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use eCharts\EChartsAnimationDesign;
use eCharts\EChartsArchiveQuery;
use eCharts\EChartsArchiveStack;
use eCharts\EChartsAsset;
use eCharts\EChartsBarPreview;
use eCharts\EChartsBarPreviewForm;
use eCharts\EChartsDataProtocol;
use eCharts\EChartsDesignerSections;
use eCharts\EChartsIPSViewBackground;
use eCharts\EChartsIPSViewDesignForm;
use eCharts\EChartsIPSViewTransport;
use eCharts\EChartsLabelDesign;
use eCharts\EChartsPeriodPreview;
use eCharts\EChartsPreviewSourceCount;
use eCharts\EChartsSliderSize;
use eCharts\EChartsSourceIdentity;
use eCharts\EChartsStartupState;
use eCharts\EChartsSvgImage;
use eCharts\EChartsUnitAxes;
use eCharts\EChartsValueAxisForm;
use eCharts\EChartsValueAxisLayout;
use eCharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsCartesianPeriodSelection.php';
require_once __DIR__ . '/../libs/EChartsArchiveStack.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsPreviewSourceCount.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsStartupState.php';
require_once __DIR__ . '/../libs/EChartsSvgImage.php';
require_once __DIR__ . '/../libs/EChartsUnitAxes.php';
require_once __DIR__ . '/../libs/EChartsValueAxisForm.php';
require_once __DIR__ . '/../libs/EChartsValueAxisLayout.php';
require_once __DIR__ . '/../libs/EChartsSliderSize.php';
require_once __DIR__ . '/../libs/EChartsPeriodPreview.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarHistory extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \eCharts\EChartsDebugFlow;
    use \eCharts\EChartsJSLiveTarget;

    use ConfigurationFormHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use \eCharts\EChartsCartesianPeriodSelection;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarHistory';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const REDUCERS = ['auto', 'average', 'sum', 'minimum', 'maximum'];
    private const MAXIMUM_SOURCE_COUNT = 32;
    private const MAXIMUM_POINT_BUDGET = 32000;
    private const MAXIMUM_MODEL_BYTES = 900000;
    private const MODEL_TOO_LARGE_MESSAGE = 'The Historical Bar model is too large. Reduce the point budget or simplify the SVG pattern.';
    private const TIME_AXIS_LABEL_FORMATS = ['auto', 'time', 'date', 'date-time'];
    private const BAR_FILL_MODES = ['solid', 'gradient', 'svg'];
    private const DESIGN_PROPERTY_TYPES = [
        'EChartsTheme'            => 'string',
        'Orientation'             => 'string',
        'ValueAxisSpacing'        => 'integer',
        'ShowValues'              => 'boolean',
        'ValueLabelPosition'      => 'string',
        'ValueLabelDistance'      => 'integer',
        'ShowGrid'                => 'boolean',
        'EnableZoom'              => 'boolean',
        'EnablePeriodSelection'   => 'boolean',
        'ZoomSliderSize'          => 'integer',
        'RoundedBars'             => 'boolean',
        'BarWidthPercent'         => 'integer',
        'BarFillMode'             => 'string',
        'BarGradientColor'        => 'integer',
        'BarSVG'                  => 'string',
        'BarSVGSizePercent'       => 'integer',
        'BarOpacityPercent'       => 'integer',
        'BarCornerRadius'         => 'integer',
        'TitleFontSizePercent'    => 'integer',
        'LegendFontSizePercent'   => 'integer',
        'AxisFontSizePercent'     => 'integer',
        'ValueFontSizePercent'    => 'integer',
        'TitleColor'              => 'integer',
        'LegendColor'             => 'integer',
        'AxisColor'               => 'integer',
        'ValueColor'              => 'integer',
        'GridColor'               => 'integer',
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
            $this->RegisterJSLiveTarget();

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
            $this->RegisterPropertyString('Orientation', 'vertical');
            $this->RegisterPropertyInteger('ValueAxisSpacing', 0);
            $this->RegisterPropertyBoolean('ShowValues', false);
            $this->RegisterPropertyString('ValueLabelPosition', 'auto');
            $this->RegisterPropertyInteger('ValueLabelDistance', 5);
            $this->RegisterPropertyBoolean('ShowGrid', true);
            $this->RegisterPropertyBoolean('EnableZoom', true);
            $this->RegisterPropertyBoolean('EnablePeriodSelection', false);
            $this->RegisterPropertyInteger('ZoomSliderSize', 0);
            $this->RegisterPropertyBoolean('RoundedBars', false);
            $this->RegisterPropertyInteger('BarWidthPercent', 70);
            $this->RegisterPropertyString('BarFillMode', 'solid');
            $this->RegisterPropertyInteger('BarGradientColor', -1);
            $this->RegisterPropertyString('BarSVG', '');
            $this->RegisterPropertyInteger('BarSVGSizePercent', 100);
            $this->RegisterPropertyInteger('BarOpacityPercent', 100);
            $this->RegisterPropertyInteger('BarCornerRadius', 6);
            $this->RegisterPropertyInteger('TitleFontSizePercent', 100);
            $this->RegisterPropertyInteger('LegendFontSizePercent', 100);
            $this->RegisterPropertyInteger('AxisFontSizePercent', 100);
            $this->RegisterPropertyInteger('ValueFontSizePercent', 100);
            $this->RegisterPropertyInteger('TitleColor', -1);
            $this->RegisterPropertyInteger('LegendColor', -1);
            $this->RegisterPropertyInteger('AxisColor', -1);
            $this->RegisterPropertyInteger('ValueColor', -1);
            $this->RegisterPropertyInteger('GridColor', -1);
            $this->RegisterAnimationProperties('', true, 350, 500);
            $this->RegisterChartBackgroundProperties();
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
            foreach (self::DESIGN_PROPERTY_TYPES as $name => $type) {
                $default = match ($name) {
                    'EChartsTheme'                                                         => EChartsAsset::THEME_AUTO,
                    'Orientation'                                                          => 'vertical',
                    'ValueAxisSpacing', 'ZoomSliderSize'                                   => 0,
                    'BarFillMode'                                                          => 'solid',
                    'BarSVG'                                                               => '',
                    'BarSVGSizePercent'                                                    => 100,
                    'EnablePeriodSelection', 'ShowValues', 'RoundedBars'                   => false,
                    'ValueLabelPosition'                                                   => 'auto',
                    'ValueLabelDistance'                                                   => 5,
                    'ShowGrid', 'EnableZoom', 'AnimationEnabled'                           => true,
                    'AnimationDuration'                                                    => 350,
                    'AnimationDurationUpdate'                                              => 500,
                    'AnimationEasing', 'AnimationEasingUpdate'                             => 'cubicInOut',
                    'AnimationDelay', 'AnimationDelayUpdate'                               => 0,
                    'BarWidthPercent'                                                      => 70,
                    'BarOpacityPercent', 'TitleFontSizePercent', 'LegendFontSizePercent', 'AxisFontSizePercent',
                    'ValueFontSizePercent'      => 100,
                    'BarCornerRadius'           => 6,
                    default                     => -1
                };
                match ($type) {
                    'string'  => $this->RegisterPropertyString('IPSView' . $name, $default),
                    'boolean' => $this->RegisterPropertyBoolean('IPSView' . $name, $default),
                    default   => $this->RegisterPropertyInteger('IPSView' . $name, $default)
                };
            }
            $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
            $this->RegisterAttributeString('LastError', '');
            $this->SealJSLiveCreationDefaults();
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
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->MaintainIPSViewHTMLVariable(
                self::IPSVIEW_OUTPUT_IDENT,
                $this->Translate('Historical Bar for IPSView'),
                90
            );
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
            return $debugFlowResult = json_encode([
                'type'      => 'connect',
                'moduleIDs' => [self::GATEWAY_MODULE_ID]
            ], JSON_THROW_ON_ERROR);
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
            if ($this->HasJSLiveImport()) {
                return $this->JSLiveConfigurationForm();
            }
            $form = $this->LoadConfigurationForm();
            if (isset($form['elements']) && is_array($form['elements'])) {
                foreach ($form['elements'] as &$element) {
                    if (($element['name'] ?? '') === 'Sources') {
                        array_push($element['columns'], ...EChartsValueAxisForm::Columns());
                        $element['form'][] = EChartsValueAxisForm::Panel();
                    }
                }
                unset($element);
                $form['elements'] = $this->WithAnimationFormControls($form['elements']);
                EChartsLabelDesign::InsertAfterRowContaining($form['elements'], 'ShowValues', [
                    ['type' => 'RowLayout', 'items' => EChartsLabelDesign::CartesianControls()]
                ]);
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['CustomRangeValue', 'CustomRangeUnit'],
                    $this->ReadPropertyString('Range') === 'custom'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarGradientColor'],
                    $this->ReadPropertyString('BarFillMode') === 'gradient'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarSVG', 'BarSVGSizePercent'],
                    $this->ReadPropertyString('BarFillMode') === 'svg'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarCornerRadius'],
                    $this->ReadPropertyBoolean('RoundedBars')
                );
                $form['elements'] = $this->WithIPSViewTimeSettingsByRange(
                    $form['elements'],
                    $this->BuildIPSViewTimeSettings($form['elements'])
                );
                $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
                $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
                $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECBH');
            }

            $form = $this->WithIPSViewDesignFormState($form, 'ECBH');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Axes and display', 'start' => 'EChartsTheme'],
                ['caption' => 'Bar fill and pattern', 'start' => 'BarFillMode'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'Range', 'CustomRangeValue', 'CustomRangeUnit', 'TimeAxisLabelFormat',
                    'IPSViewUseTileDesign', 'IPSViewUseTileTimeSettings', 'IPSViewRange',
                    'IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit', 'IPSViewTimeAxisLabelFormat',
                    'IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys(self::DESIGN_PROPERTY_TYPES),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_PROPERTY_TYPES))
            );

            return $debugFlowResult = $this->EncodeChartDesignerForm(__DIR__, $this->WithBarPreviewForm($form, 'ECBH', $fields));
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
            $custom = $Range === 'custom';
            $this->UpdateFormField('CustomRangeValue', 'visible', $custom);
            $this->UpdateFormField('CustomRangeUnit', 'visible', $custom);
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
            $independent = !$IPSViewUseTileTimeSettings;
            foreach (['IPSViewRange', 'IPSViewTimeAxisLabelFormat'] as $field) {
                $this->UpdateFormField($field, 'visible', $independent);
            }
            foreach (['IPSViewCustomRangeValue', 'IPSViewCustomRangeUnit'] as $field) {
                $this->UpdateFormField($field, 'visible', $independent && $IPSViewRange === 'custom');
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateBarDesignForm(bool $IPSView, bool $RoundedBars, string $BarFillMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $prefix = $IPSView ? 'IPSView' : '';
            $this->UpdateFormField($prefix . 'BarGradientColor', 'visible', $BarFillMode === 'gradient');
            $this->UpdateFormField($prefix . 'BarSVG', 'visible', $BarFillMode === 'svg');
            $this->UpdateFormField($prefix . 'BarSVGSizePercent', 'visible', $BarFillMode === 'svg');
            $this->UpdateFormField($prefix . 'BarCornerRadius', 'visible', $RoundedBars);
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
            if ($this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
                return;
            }
            throw new InvalidArgumentException('Unknown action: ' . $Ident);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function GetBarHistoryData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetBarHistoryDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetBarHistoryDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();

            return $debugFlowResult = json_encode([
                'valid'     => $error === null,
                'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
            return $debugFlowResult = $this->RenderBarHistoryHTMLPage(false);
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
            return $debugFlowResult = $this->RenderBarHistoryHTMLPage(true);
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
                fn (array $payload): array => $this->CartesianTransportState($payload),
                $this->CartesianPeriodChoices(false) !== [],
                true
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

    /** @param array<int,mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__, ['Quelle' => $SenderID, 'Nachricht' => $Message]);
        try {
            if ($this->HasJSLiveImport()) {
                $this->HandleJSLiveImportMessage($SenderID, $Message);
                return;
            }
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

    protected function UpdateIndependentDesignVisibilityAfterCopy(array $values): void
    {
        $this->UpdateBarDesignForm(true, $values['RoundedBars'], $values['BarFillMode']);
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $data = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', false, self::MAXIMUM_SOURCE_COUNT);
        foreach ($data['items'] as $index => &$item) {
            $item['previewPaletteIndex'] = $index;
        }
        unset($item);
        $items = array_slice(array_values(array_filter(
            $data['items'],
            static fn (array $item): bool => ($item['InitiallyVisible'] ?? true) !== false
        )), 0, 3);
        $axisSources = [];
        foreach ($data['items'] as $item) {
            $position = $item['AxisPosition'] ?? 'auto';
            try {
                $range = EChartsUnitAxes::ResolveRange($item);
            } catch (InvalidArgumentException) {
                $range = null;
            }
            $axisSources[] = [
                'Unit'         => $item['unit'],
                'OwnAxis'      => ($item['OwnAxis'] ?? false) === true,
                'VariableID'   => $item['VariableID'] ?? 0,
                'AxisRange'    => $range,
                'AxisPosition' => is_string($position) && in_array($position, ['auto', 'left', 'right'], true)
                    ? $position : 'auto'
            ];
        }
        try {
            $axisModel = EChartsUnitAxes::Build($axisSources);
        } catch (\InvalidArgumentException) {
            foreach ($axisSources as &$axisSource) {
                $axisSource['AxisPosition'] = 'auto';
                $axisSource['AxisRange'] = null;
            }
            unset($axisSource);
            $axisModel = EChartsUnitAxes::Build($axisSources);
        }
        $visibleAxes = [];
        foreach ($items as $index => $item) {
            $visibleAxes[$axisModel['SeriesIndexes'][$item['previewPaletteIndex']]] ??= $index;
        }
        $previewLanguage = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Chart preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $legendColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $palette['text']);
        $legendSize = max(6, min(24, (int) round(12 * $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView) / 100)));
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['muted']);
        $valueColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['track']);
        $axisSize = max(6, min(24, (int) round(11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100)));
        $valueSize = max(6, min(24, (int) round(10 * $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView) / 100)));
        $range = $ipsView && !($values['IPSViewUseTileTimeSettings']
            ?? $this->ReadPropertyBoolean('IPSViewUseTileTimeSettings'))
            ? (string) ($values['IPSViewRange'] ?? $this->ReadPropertyString('IPSViewRange'))
            : (string) ($values['Range'] ?? $this->ReadPropertyString('Range'));
        $svg .= '<text x="696" y="55" text-anchor="end" fill="' . EChartsBarPreview::E($palette['muted'])
            . '" font-size="11">' . EChartsBarPreview::E($range) . '</text>';
        $horizontal = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView) === 'horizontal';
        $counts = array_count_values(array_map(
            static fn (int $index): string => $axisModel['Axes'][$index]['position'],
            array_keys($visibleAxes)
        ));
        $leftExtra = max(0, ($counts['left'] ?? 0) - 1);
        $rightExtra = max(0, ($counts['right'] ?? 0) - 1);
        $maximumExtra = max($leftExtra, $rightExtra);
        $spacing = $this->BarPreviewValue($values, 'ValueAxisSpacing', 'integer', $ipsView);
        $sliderSize = $this->BarPreviewValue($values, 'EnableZoom', 'boolean', $ipsView)
            ? EChartsSliderSize::Resolve($this->BarPreviewValue($values, 'ZoomSliderSize', 'integer', $ipsView), 11)
            : 0;
        $extraZoomSpace = max(0, $sliderSize - 11);
        // Keep the legend, axis captions and zoom outside the example plot.
        $step = $horizontal
            ? EChartsValueAxisLayout::Spacing($spacing, $axisSize * 2.8, 165 * 0.65, $leftExtra + $rightExtra)
            : EChartsValueAxisLayout::Spacing($spacing, 54, 132, $maximumExtra);
        $plotLeft = $horizontal ? 90 : 65 + $leftExtra * $step;
        $plotRight = $horizontal ? 642 - $extraZoomSpace : 680 - $rightExtra * $step;
        $plotTop = $horizontal ? 110 + $rightExtra * $step : 93 + $maximumExtra * ($axisSize + 2);
        $plotBottom = $horizontal ? 275 - $leftExtra * $step : 265 - $extraZoomSpace;
        $plotWidth = $plotRight - $plotLeft;
        $plotHeight = $plotBottom - $plotTop;
        $showGrid = $this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView);
        if ($showGrid) {
            for ($i = 0; $i <= 4; ++$i) {
                $position = $horizontal ? $plotLeft + $i * $plotWidth / 4 : $plotBottom - $i * $plotHeight / 4;
                $line = $horizontal
                    ? 'x1="' . $position . '" y1="' . $plotTop . '" x2="' . $position . '" y2="' . $plotBottom . '"'
                    : 'x1="' . $plotLeft . '" y1="' . $position . '" x2="' . $plotRight . '" y2="' . $position . '"';
                $svg .= '<line ' . $line . ' stroke="' . EChartsBarPreview::E($gridColor) . '"/>';
            }
        }
        $automaticAxisColor = $this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView) < 0;
        $visibleAxisCounts = ['left' => 0, 'right' => 0];
        foreach ($axisModel['Axes'] as $axisIndex => $axis) {
            if (!isset($visibleAxes[$axisIndex])) {
                continue;
            }
            $sourceIndex = $visibleAxes[$axisIndex];
            $source = $items[$sourceIndex];
            $sourceColor = $source['color'] !== ''
                ? $source['color'] : $palette['seriesColors'][$source['previewPaletteIndex'] % count($palette['seriesColors'])];
            $color = $automaticAxisColor && count($data['items']) > 1 ? $sourceColor : $axisColor;
            $left = $axis['position'] === 'left';
            $positionIndex = $visibleAxisCounts[$axis['position']]++;
            if ($horizontal) {
                $side = $left ? 'bottom' : 'top';
                $axisY = $left ? $plotBottom + $positionIndex * $step : $plotTop - $positionIndex * $step;
                $line = 'x1="90" y1="' . $axisY . '" x2="' . $plotRight . '" y2="' . $axisY . '"';
                $captionX = 86;
                $captionY = $axisY + 4;
                $anchor = 'end';
            } else {
                $side = $axis['position'];
                $axisX = $left ? $plotLeft - $positionIndex * $step : $plotRight + $positionIndex * $step;
                $line = 'x1="' . $axisX . '" y1="' . $plotTop . '" x2="' . $axisX . '" y2="' . $plotBottom . '"';
                $captionX = $axisX + ($left ? 3 : -4);
                $captionY = $plotTop - 5 - $positionIndex * ($axisSize + 2);
                $anchor = $left ? 'start' : 'end';
            }
            $svg .= '<line data-preview-axis="' . $side . '" ' . $line . ' stroke="' . EChartsBarPreview::E($color) . '"/>';
            $caption = mb_strimwidth($axis['unit'], 0, 8, '…');
            if (isset($axis['minimum'], $axis['maximum'])) {
                $caption = trim($caption . ' ' . $axis['minimum'] . ' … ' . $axis['maximum']);
            }
            if ($caption !== '') {
                $svg .= '<text data-preview-axis-unit="' . $side . '" x="' . $captionX
                    . '" y="' . $captionY . '" text-anchor="' . $anchor . '" fill="'
                    . EChartsBarPreview::E($color) . '" font-size="' . $axisSize . '">'
                    . EChartsBarPreview::E($caption) . '</text>';
            }
        }
        $barWidth = max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView)));
        $opacity = max(0, min(100, $this->BarPreviewValue($values, 'BarOpacityPercent', 'integer', $ipsView))) / 100;
        $rounded = $this->BarPreviewValue($values, 'RoundedBars', 'boolean', $ipsView)
            ? max(0, min(24, $this->BarPreviewValue($values, 'BarCornerRadius', 'integer', $ipsView)))
            : 0;
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $valueLabelPosition = $this->BarPreviewValue($values, 'ValueLabelPosition', 'string', $ipsView);
        $valueLabelDistance = $this->BarPreviewValue($values, 'ValueLabelDistance', 'integer', $ipsView);
        $fillMode = $this->BarPreviewValue($values, 'BarFillMode', 'string', $ipsView);
        $gradientEnd = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'BarGradientColor', 'integer', $ipsView),
            $palette['background']
        );
        if ($fillMode === 'gradient' || $fillMode === 'svg') {
            $patternSize = $fillMode === 'svg' ? EChartsBarPreview::PatternSize(
                $this->BarPreviewValue($values, 'BarSVGSizePercent', 'integer', $ipsView)
            ) : 0.0;
            $svg .= '<defs>';
            foreach ($items as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['previewPaletteIndex'] % count($palette['seriesColors'])];
                if ($fillMode === 'gradient') {
                    $direction = $horizontal ? 'x1="0" x2="1" y1="0" y2="0"' : 'x1="0" x2="0" y1="1" y2="0"';
                    $svg .= '<linearGradient id="bar-history-preview-' . $index . '" ' . $direction . '>'
                        . '<stop offset="0" stop-color="' . EChartsBarPreview::E($color) . '"/>'
                        . '<stop offset="1" stop-color="' . EChartsBarPreview::E($gradientEnd) . '"/></linearGradient>';
                } else {
                    $size = EChartsBarPreview::N($patternSize);
                    $svg .= '<pattern id="bar-history-preview-' . $index . '" width="' . $size
                        . '" height="' . $size . '" patternUnits="userSpaceOnUse">'
                        . '<rect width="' . $size . '" height="' . $size . '" fill="'
                        . EChartsBarPreview::E($color) . '"/>'
                        . '<path d="M 0 ' . $size . ' L ' . $size . ' 0" stroke="'
                        . EChartsBarPreview::E($palette['text'])
                        . '" stroke-opacity="0.45"/></pattern>';
                }
            }
            $svg .= '</defs>';
        }
        $slots = [];
        $slotByItem = [];
        foreach ($items as $index => $item) {
            $group = $item['StackGroup'] ?? 0;
            $key = is_int($group) && $group > 0 ? 'stack-' . $group : 'single-' . $index;
            $slots[$key] ??= count($slots);
            $slotByItem[$index] = $slots[$key];
        }
        $bucketSpan = ($horizontal ? $plotHeight : $plotWidth - 15) / 15;
        $slot = $bucketSpan / max(1, count($slots));
        $thickness = $slot * $barWidth / 100;
        $slotCounts = array_count_values($slotByItem);
        $scale = max([1, ...array_values($slotCounts)]);
        $labelRects = [];
        for ($bucket = 0; $bucket < 15; ++$bucket) {
            $offsets = array_fill(0, count($slots), 0.0);
            $totals = array_fill(0, count($slots), 0.0);
            foreach ($items as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['previewPaletteIndex'] % count($palette['seriesColors'])];
                $sample = 50 + 65 * (1 + sin(($bucket + $index * 3) / 2.4));
                $column = $slotByItem[$index];
                $timeOffset = $bucket * $bucketSpan + $column * $slot + ($slot - $thickness) / 2;
                $sampleValue = 5 + $sample / 10;
                $length = $sample / $scale / 180 * ($horizontal ? $plotWidth : $plotHeight);
                $offset = $offsets[$column];
                $axis = $axisModel['Axes'][$axisModel['SeriesIndexes'][$item['previewPaletteIndex']]];
                if (isset($axis['minimum'], $axis['maximum'])) {
                    $minimum = $axis['minimum'];
                    $maximum = $axis['maximum'];
                    $sampleMinimum = $maximum > 0 ? max(0.0, $minimum) : $minimum;
                    $sampleValue = ($sampleMinimum + ($maximum - $sampleMinimum) * ($sample / 200)) / $slotCounts[$column];
                    $start = max($minimum, min($maximum, $totals[$column]));
                    $end = max($minimum, min($maximum, $totals[$column] + $sampleValue));
                    $span = $horizontal ? $plotWidth : $plotHeight;
                    $offset = (min($start, $end) - $minimum) / ($maximum - $minimum) * $span;
                    $length = abs($end - $start) / ($maximum - $minimum) * $span;
                }
                if ($horizontal) {
                    $x = $plotLeft + $offset;
                    $y = $plotTop + $timeOffset;
                    $width = $length;
                    $height = $thickness;
                } else {
                    $x = $plotLeft + 5 + $timeOffset;
                    $y = $plotBottom - $offset - $length;
                    $width = $thickness;
                    $height = $length;
                }
                $offsets[$column] += $length;
                $totals[$column] += $sampleValue;
                if ($bucket === 10 + $index) {
                    $labelRects[$index] = [$x, $y, $width, $height, $sampleValue];
                }
                $fill = $fillMode === 'solid' ? $color : 'url(#bar-history-preview-' . $index . ')';
                $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                    . '" width="' . EChartsBarPreview::N($width) . '" height="' . EChartsBarPreview::N($height)
                    . '" rx="' . $rounded . '" fill="' . EChartsBarPreview::E($fill)
                    . '" fill-opacity="' . EChartsBarPreview::N($opacity) . '"/>';
            }
        }
        foreach ($items as $index => $item) {
            $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['previewPaletteIndex'] % count($palette['seriesColors'])];
            $x = 75 + $index * 200;
            $svg .= '<rect x="' . $x . '" y="60" width="13" height="13" fill="' . EChartsBarPreview::E($color) . '"/>';
            $legend = mb_strimwidth($item['label'], 0, 14, '…')
                . ($item['unit'] !== '' ? ' (' . $item['unit'] . ')' : '');
            $svg .= '<text x="' . ($x + 19) . '" y="71" fill="' . EChartsBarPreview::E($legendColor)
                . '" font-size="' . $legendSize . '">' . EChartsBarPreview::E($legend) . '</text>';
            if ($showValues) {
                [$barX, $barY, $width, $height, $sampleValue] = $labelRects[$index];
                $placement = EChartsLabelDesign::PreviewRect(
                    $barX,
                    $barY,
                    $width,
                    $height,
                    $valueLabelPosition === 'auto' ? ($horizontal ? 'right' : 'top') : $valueLabelPosition,
                    $valueLabelDistance
                );
                $svg .= '<text x="' . EChartsBarPreview::N($placement['x']) . '" y="' . EChartsBarPreview::N($placement['y'])
                    . '" text-anchor="' . $placement['anchor'] . '" fill="' . EChartsBarPreview::E($valueColor)
                    . '" font-size="' . $valueSize . '">' . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                        $sampleValue,
                        $item['decimals'],
                        $item['unit'],
                        $previewLanguage
                    )) . '</text>';
            }
        }
        $rangePosition = $horizontal ? 'x="82" y="' . ($plotTop + $plotHeight / 2) . '" text-anchor="end"'
            : 'x="' . ($plotLeft + 5) . '" y="' . ($plotBottom + 21) . '"';
        $svg .= '<text ' . $rangePosition . ' fill="' . EChartsBarPreview::E($axisColor)
            . '" font-size="' . $axisSize . '">' . EChartsBarPreview::E($range)
            . '</text>';
        if ($fillMode === 'svg') {
            $svg .= '<text x="' . (696 - ($horizontal ? $extraZoomSpace : 0)) . '" y="' . (307 - ($horizontal ? 0 : $extraZoomSpace))
                . '" text-anchor="end" fill="' . EChartsBarPreview::E($palette['muted'])
                . '" font-size="10">' . EChartsBarPreview::E($this->Translate('SVG pattern (schematic)')) . '</text>';
        }
        if ($sliderSize > 0) {
            $sliderX = 707 - $sliderSize;
            $sliderY = 331 - $sliderSize;
            $track = $horizontal ? 'x="' . $sliderX . '" y="' . $plotTop . '" width="' . $sliderSize . '" height="' . $plotHeight . '"'
                : 'x="' . ($plotLeft + 10) . '" y="' . $sliderY . '" width="' . ($plotWidth - 15) . '" height="' . $sliderSize . '"';
            $selection = $horizontal ? 'x="' . $sliderX . '" y="' . ($plotTop + $plotHeight * 0.14) . '" width="' . $sliderSize . '" height="' . ($plotHeight * 0.7) . '"'
                : 'x="' . ($plotLeft + 10 + ($plotWidth - 15) * 0.14) . '" y="' . $sliderY . '" width="' . (($plotWidth - 15) * 0.7) . '" height="' . $sliderSize . '"';
            $svg .= '<rect ' . $track . ' data-preview-zoom="' . ($horizontal ? 'vertical' : 'horizontal')
                . '" rx="3" fill="' . EChartsBarPreview::E($palette['surface'])
                . '" stroke="' . EChartsBarPreview::E($palette['border']) . '"/>';
            $svg .= '<rect ' . $selection . ' rx="2" fill="'
                . EChartsBarPreview::E($palette['accent']) . '" fill-opacity="0.4"/>';
        }

        $periodLabel = $this->CartesianPeriodPreviewLabel($values, $ipsView);
        return EChartsPreviewSourceCount::Append(
            EChartsPeriodPreview::Apply($svg . '</svg>', $periodLabel !== '', $periodLabel, $palette),
            count($items),
            count($data['items']),
            $this->Translate('Preview shows %d of %d sources'),
            $palette['muted'],
            696,
            351
        );
    }

    protected function CurrentTimestamp(): int
    {
        return time();
    }

    private function GetBarHistoryDataForOutput(bool $ipsView, string $period = ''): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->SetViewError($error['Message']);
            $this->SetViewStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetViewStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $sources = $this->GetValidatedSources();
        $range = $period !== '' ? $period : $this->EffectiveRange($ipsView);
        $axisModel = EChartsUnitAxes::Build($sources);
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        $query = EChartsArchiveQuery::Resolve(
            $range,
            $this->EffectiveCustomRangeValue($ipsView),
            $this->EffectiveCustomRangeUnit($ipsView),
            $this->ReadPropertyString('DataMode'),
            $this->ReadPropertyInteger('PointBudget'),
            count($sources),
            $this->CurrentTimestamp(),
            date_default_timezone_get()
        );
        if ($query['Mode'] === 'realtime') {
            throw new RuntimeException('Historical bars require archive data.');
        }
        $series = [];
        $truncated = false;
        foreach ($sources as $index => $source) {
            $archive = $this->ReadArchiveSource($source, $query);
            $series[] = [
                'id'                     => 'variable-' . $source['VariableID'],
                'variableID'             => $source['VariableID'],
                'label'                  => $labels[$source['VariableID']],
                'initiallyVisible'       => $source['InitiallyVisible'],
                ...($source['StackGroup'] > 0 ? ['stackGroup' => $source['StackGroup']] : []),
                'unit'                   => $source['Unit'],
                'decimals'               => $source['Decimals'],
                'axisIndex'              => $axisModel['SeriesIndexes'][$index],
                'color'                  => $source['Color'],
                'effectiveReducer'       => $archive['EffectiveReducer'],
                'archiveAggregationType' => $archive['ArchiveAggregationType'],
                'points'                 => $archive['Points']
            ];
            $truncated = $truncated || $archive['Truncated'];
        }
        $this->SetViewError('');
        $this->SetViewStatus(IS_ACTIVE);

        $json = json_encode([
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'history',
            'theme'         => $this->EffectiveTheme($ipsView),
            'bar'           => [
                'title'               => $this->ReadPropertyString('Title'),
                'timeAxisLabelFormat' => $this->EffectiveTimeAxisLabelFormat($ipsView),
                'unit'                => $sources[0]['Unit'],
                'decimals'            => $sources[0]['Decimals'],
                'style'               => $this->ReadDesignStyle($ipsView)
            ],
            'axes'          => $axisModel['Axes'],
            'range'         => [
                'key'                 => $range,
                'startTimestamp'      => $query['StartTimestamp'],
                'endTimestamp'        => $query['EndTimestamp'],
                'dataMode'            => $this->ReadPropertyString('DataMode'),
                'aggregationLevel'    => $query['AggregationLevel'],
                'pointBudget'         => $this->ReadPropertyInteger('PointBudget'),
                'pointLimitPerSeries' => $query['Limit']
            ],
            'series'        => $series,
            'truncated'     => $truncated
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if (strlen($json) > self::MAXIMUM_MODEL_BYTES) {
            $this->SetViewError(self::MODEL_TOO_LARGE_MESSAGE);
            $this->SetViewStatus(self::STATUS_CONFIGURATION_INVALID);
            throw new RuntimeException(self::MODEL_TOO_LARGE_MESSAGE);
        }
        return $json;
    }

    private function RenderBarHistoryHTMLPage(bool $ipsView): string
    {
        if ($this->HasJSLiveImport()) {
            return $this->RenderJSLiveImportPage($ipsView);
        }
        $startup = EChartsStartupState::Startup($this->BuildVisualizationState($ipsView));
        $configuration = [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Historical Bar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-history-root', 'echarts-bar-history'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $startup['state'],
            'translations'       => [
                'Configure 1 to 32 valid Historical Bar sources.' => $this->Translate(
                    'Configure 1 to 32 valid Historical Bar sources.'
                ),
                'All'                                            => $this->Translate('All'),
                'Invert'                                         => $this->Translate('Invert'),
                self::MODEL_TOO_LARGE_MESSAGE                    => $this->Translate(self::MODEL_TOO_LARGE_MESSAGE),
                'Connect an active EChartsGateway.'              => $this->Translate('Connect an active EChartsGateway.'),
                'The Historical Bar values could not be loaded.' => $this->Translate(
                    'The Historical Bar values could not be loaded.'
                ),
                'Historical Bar data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Historical Bar data could not be refreshed. Showing the last available values.'
                ),
                'The selected raw range was truncated by the point budget.' => $this->Translate(
                    'The selected raw range was truncated by the point budget.'
                )
            ],
            'options'            => [
                'echartsVersion'            => EChartsAsset::VERSION,
                'echartsThemes'             => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground'         => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'          => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'compressedStateGzipBase64' => $startup['compressedStateGzipBase64']
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::RendererScript('cartesian', $ipsView),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::StartupJavaScript()
                    . "\n" . EChartsAsset::SeriesSelectionJavaScript()
                    . "\n" . EChartsAsset::ArchiveStackJavaScript(),
                '{{ECHARTS_ZOOM_SCRIPT}}'      => EChartsAsset::ZoomJavaScript(),
                '{{ECHARTS_PATTERN_SCRIPT}}'   => EChartsAsset::PatternJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ];
        $html = $this->WithEChartsPeriodSelection($this->RenderChartPage($ipsView, $configuration), $ipsView, $this->CartesianPeriodChoices($ipsView));
        if (strlen($html) > 1048576) {
            $this->WriteAttributeString('LastError', self::MODEL_TOO_LARGE_MESSAGE);
            $this->DebugEChartsSetStatus(self::STATUS_CONFIGURATION_INVALID);
            $configuration['state'] = ['schemaVersion' => 1, 'family' => 'bar', 'variant' => 'history',
                'status'                               => 'error', 'chart' => null, 'error' => self::MODEL_TOO_LARGE_MESSAGE];
            $configuration['options']['compressedStateGzipBase64'] = null;
            return $this->RenderChartPage($ipsView, $configuration);
        }
        return $html;
    }

    private function Initialize(): void
    {
        if ($this->HasJSLiveImport()) {
            $this->InitializeJSLiveImport();
            return;
        }
        $this->SetTimerInterval('JSLiveImportRefresh', 0);
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
        $this->SetSummary($this->RangeSummary());
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        if (!$this->ChartBackgroundColorsAreValid()) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'Select a valid chart background color.'];
        }
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        $mode = $this->ReadPropertyString('DataMode');
        if (!$this->IsValidTimeSettings('')
            || !in_array($mode, ['raw', 'auto', ...array_keys(EChartsArchiveQuery::AGGREGATION_LEVELS)], true)
            || $this->ReadPropertyInteger('PointBudget') < 200
            || $this->ReadPropertyInteger('PointBudget') > self::MAXIMUM_POINT_BUDGET
            || !$this->IsValidDesign('')
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The Historical Bar settings are invalid.'
            ];
        }

        if ($this->IsIPSViewHTMLPageEnabled()
            && (!EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
                || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
                || (!$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings')
                    && !$this->IsValidTimeSettings('IPSView')))
        ) {
            return [
                'Status'  => self::STATUS_CONFIGURATION_INVALID,
                'Message' => 'The IPSView Historical Bar settings are invalid.'
            ];
        }

        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:string,Reducer:string,AxisPosition:string,OwnAxis:bool,AxisRange:?array{minimum:float,maximum:float},InitiallyVisible:bool,StackGroup:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Historical Bar sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources)
            || count($sources) < 1 || count($sources) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Configure between 1 and 32 numeric Historical Bar sources.');
        }
        $result = [];
        $seen = [];
        $stackUnits = [];
        $mode = $this->ReadPropertyString('DataMode');
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('The Historical Bar source settings are invalid.');
            }
            $variableID = $source['VariableID'] ?? null;
            $label = $source['Label'] ?? '';
            $decimals = $source['Decimals'] ?? 1;
            $color = $source['Color'] ?? -1;
            $reducer = $source['Reducer'] ?? 'auto';
            $unit = $source['Unit'] ?? '';
            $usePresentation = $source['UseVariablePresentation'] ?? true;
            $axisPosition = $source['AxisPosition'] ?? 'auto';
            $ownAxis = array_key_exists('OwnAxis', $source) ? $source['OwnAxis'] : false;
            $initiallyVisible = $source['InitiallyVisible'] ?? true;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || isset($seen[$variableID])
                || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || !is_string($unit) || !is_bool($usePresentation) || !is_bool($initiallyVisible) || !is_bool($ownAxis)
                || !is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF
                || !is_string($reducer) || !in_array($reducer, self::REDUCERS, true)
                || !is_string($axisPosition) || !in_array($axisPosition, ['auto', 'left', 'right'], true)) {
                throw new InvalidArgumentException('Historical Bar sources must be unique numeric variables with valid settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                $usePresentation
            );
            $seen[$variableID] = true;
            $stackGroup = EChartsArchiveStack::ValidateGroup($source['StackGroup'] ?? 0, $presentation['unit'], $mode, $stackUnits, $ownAxis);
            $result[] = [
                'StackGroup'       => $stackGroup,
                'VariableID'       => $variableID,
                'InitiallyVisible' => $initiallyVisible,
                'Label'            => trim($label),
                'Unit'             => $presentation['unit'],
                'Decimals'         => $presentation['decimals'],
                'Color'            => $color < 0 ? '' : EChartsAsset::ColorToHex($color),
                'Reducer'          => $reducer,
                'AxisPosition'     => $axisPosition,
                'OwnAxis'          => $ownAxis,
                'AxisRange'        => EChartsUnitAxes::ResolveRange($source)
            ];
        }
        if (count(EChartsUnitAxes::Build($result)['Axes']) > 16) {
            throw new InvalidArgumentException('Configure no more than 16 Historical Bar value axes.');
        }

        return $result;
    }

    /** @param array{VariableID:int,Reducer:string,StackGroup:int} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if ($query['Empty']) {
                return $debugFlowResult = [
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
                    $this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                        self::DATA_ID_TO_PARENT,
                        EChartsDataProtocol::CreateRequest($operation, $payload)
                    )),
                    $operation
                );
            } catch (Throwable $exception) {
                $this->SetViewStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway returned an invalid archive response.', 0, $exception);
            }
            if (!$response['Success']) {
                $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
                $this->SetViewError($errorCode);
                $this->SetViewStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway archive request failed: ' . $errorCode);
            }
            $archive = $response['Payload'];
            if (($archive['VariableID'] ?? null) !== $source['VariableID']
                || !is_string($archive['ArchiveAggregationType'] ?? null)
                || !is_string($archive['EffectiveReducer'] ?? null)
                || !is_array($archive['Points'] ?? null)
                || count($archive['Points']) > $query['Limit']
                || !is_bool($archive['Truncated'] ?? null)
            ) {
                throw new RuntimeException('The gateway returned invalid Historical Bar data.');
            }
            $points = [];
            $previousTimestamp = 0;
            foreach ($archive['Points'] as $point) {
                if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                    || !is_int($point[0]) || $point[0] <= 0 || $point[0] < $previousTimestamp
                    || ($source['StackGroup'] > 0 && $point[0] === $previousTimestamp)
                    || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])
                ) {
                    throw new RuntimeException('The gateway returned invalid Historical Bar points.');
                }
                $previousTimestamp = $point[0];
                $points[] = [$point[0], (float) $point[1]];
            }
            $archive['Points'] = $points;

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
    private function BuildVisualizationState(bool $ipsView = false, string $period = ''): array
    {
        if ($this->HasJSLiveImport()) {
            return $this->BuildJSLiveImportState($ipsView);
        }
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Configure 1 to 32 valid Historical Bar sources.'
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
            $chart = json_decode($this->GetBarHistoryDataForOutput($ipsView, $period), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'history',
                'status'        => 'error',
                'chart'         => null,
                'error'         => $exception->getMessage() === self::MODEL_TOO_LARGE_MESSAGE
                    ? self::MODEL_TOO_LARGE_MESSAGE : 'The Historical Bar values could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'history',
            'status'        => 'ready',
            'chart'         => $this->WithChartBackground($chart, $ipsView),
            'error'         => null
        ];
    }

    private function PublishVisualizationState(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'Diagrammzustand an die native Kachel übergeben; Darstellung im Browser ist noch nicht bestätigt.');
                $this->UpdateVisualizationValue(json_encode(
                    $this->BuildVisualizationState(),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ));
                $this->PushEChartsIPSViewState(fn (): array => $this->BuildVisualizationState(true));
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
            || !in_array($this->ReadPropertyString($prefix . 'Orientation'), ['vertical', 'horizontal'], true)
            || !EChartsValueAxisLayout::IsValidSpacing($this->ReadPropertyInteger($prefix . 'ValueAxisSpacing'))
            || !EChartsSliderSize::IsValid($this->ReadPropertyInteger($prefix . 'ZoomSliderSize'))
            || !EChartsLabelDesign::IsValidPosition($this->ReadPropertyString($prefix . 'ValueLabelPosition'), EChartsLabelDesign::CARTESIAN_POSITIONS)
            || !EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . 'ValueLabelDistance'))
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'BarFillMode'), self::BAR_FILL_MODES, true)) {
            return false;
        }
        foreach ([
            'BarWidthPercent'       => [20, 100],
            'BarOpacityPercent'     => [0, 100],
            'BarCornerRadius'       => [0, 24],
            'TitleFontSizePercent'  => [50, 200],
            'LegendFontSizePercent' => [50, 200],
            'AxisFontSizePercent'   => [50, 200],
            'ValueFontSizePercent'  => [50, 200],
            'BarSVGSizePercent'     => [25, 400]
        ] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['BarGradientColor', 'TitleColor', 'LegendColor', 'AxisColor', 'ValueColor', 'GridColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }

        if ($this->ReadPropertyString($prefix . 'BarFillMode') === 'svg') {
            try {
                EChartsSvgImage::Import($this->ReadPropertyString($prefix . 'BarSVG'));
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        return true;
    }

    private function IsValidTimeSettings(string $prefix): bool
    {
        return EChartsArchiveQuery::IsValidRange(
            $this->ReadPropertyString($prefix . 'Range'),
            $this->ReadPropertyInteger($prefix . 'CustomRangeValue'),
            $this->ReadPropertyString($prefix . 'CustomRangeUnit')
        ) && in_array(
            $this->ReadPropertyString($prefix . 'TimeAxisLabelFormat'),
            self::TIME_AXIS_LABEL_FORMATS,
            true
        );
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

    private function EffectiveTheme(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('IPSViewEChartsTheme')
            : $this->ReadPropertyString('EChartsTheme');
    }

    /** @return array<string, bool|int|string|float> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';

        $style = [
            ...$this->ReadAnimationStyle($prefix),
            'orientation'          => $this->ReadPropertyString($prefix . 'Orientation'),
            'valueAxisSpacing'     => $this->ReadPropertyInteger($prefix . 'ValueAxisSpacing'),
            'showValues'           => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'valueLabelPosition'   => $this->ReadPropertyString($prefix . 'ValueLabelPosition'),
            'valueLabelDistance'   => $this->ReadPropertyInteger($prefix . 'ValueLabelDistance'),
            'showGrid'             => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'enableZoom'           => $this->ReadPropertyBoolean($prefix . 'EnableZoom'),
            'zoomSliderSize'       => $this->ReadPropertyInteger($prefix . 'ZoomSliderSize'),
            'roundedBars'          => $this->ReadPropertyBoolean($prefix . 'RoundedBars'),
            'barWidthPercent'      => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'barFillMode'          => $this->ReadPropertyString($prefix . 'BarFillMode'),
            'barGradientColor'     => $this->OptionalDesignColor($prefix . 'BarGradientColor'),
            'barOpacityPercent'    => $this->ReadPropertyInteger($prefix . 'BarOpacityPercent'),
            'barCornerRadius'      => $this->ReadPropertyInteger($prefix . 'BarCornerRadius'),
            'titleFontSizePercent' => $this->ReadPropertyInteger($prefix . 'TitleFontSizePercent'),
            'legendFontSizePercent'=> $this->ReadPropertyInteger($prefix . 'LegendFontSizePercent'),
            'axisFontSizePercent'  => $this->ReadPropertyInteger($prefix . 'AxisFontSizePercent'),
            'valueFontSizePercent' => $this->ReadPropertyInteger($prefix . 'ValueFontSizePercent'),
            'titleColor'           => $this->OptionalDesignColor($prefix . 'TitleColor'),
            'legendColor'          => $this->OptionalDesignColor($prefix . 'LegendColor'),
            'axisColor'            => $this->OptionalDesignColor($prefix . 'AxisColor'),
            'valueColor'           => $this->OptionalDesignColor($prefix . 'ValueColor'),
            'gridColor'            => $this->OptionalDesignColor($prefix . 'GridColor')
        ];
        if ($style['barFillMode'] === 'svg') {
            $image = EChartsSvgImage::Import($this->ReadPropertyString($prefix . 'BarSVG'));
            $style['barPatternImage'] = $image['dataUri'];
            $style['barPatternAspectRatio'] = $image['width'] / $image['height'];
            $style['barSVGSizePercent'] = $this->ReadPropertyInteger($prefix . 'BarSVGSizePercent');
        }

        return $style;
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
            '#echarts-bar-history-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param list<array<string, mixed>> $elements @return list<array<string, mixed>> */
    private function BuildIPSViewTimeSettings(array $elements): array
    {
        $rangeItems = null;
        $designRows = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'RowLayout'
                && in_array('Range', array_column($element['items'] ?? [], 'name'), true)) {
                $rangeItems = $element['items'];
            }
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $designRows = $element['items'] ?? null;
            }
        }
        if (!is_array($rangeItems) || !is_array($designRows)) {
            throw new RuntimeException('The Historical Bar form sections are missing.');
        }

        $independentTime = !$this->ReadPropertyBoolean('IPSViewUseTileTimeSettings');
        $customRange = $independentTime && $this->ReadPropertyString('IPSViewRange') === 'custom';
        $timeAction = 'ECBH_UpdateIPSViewTimeRangeForm($id, $IPSViewUseTileTimeSettings, $IPSViewRange);';
        $ipsViewTimeItems = [];
        foreach ($rangeItems as $item) {
            if (!in_array($item['name'] ?? null, ['Range', 'CustomRangeValue', 'CustomRangeUnit'], true)) {
                continue;
            }
            $item['name'] = 'IPSView' . $item['name'];
            $item['visible'] = $item['name'] === 'IPSViewRange' ? $independentTime : $customRange;
            if ($item['name'] === 'IPSViewRange') {
                $item['caption'] = 'IPSView time range';
                $item['onChange'] = $timeAction;
            }
            $ipsViewTimeItems[] = $item;
        }
        foreach ($designRows as $row) {
            foreach ($row['items'] ?? [] as $item) {
                if (($item['name'] ?? null) !== 'TimeAxisLabelFormat') {
                    continue;
                }
                $item['name'] = 'IPSViewTimeAxisLabelFormat';
                $item['visible'] = $independentTime;
                $ipsViewTimeItems[] = $item;
            }
        }
        if (count($ipsViewTimeItems) !== 4) {
            throw new RuntimeException('The Historical Bar time settings are incomplete.');
        }

        return [
            [
                'type'     => 'CheckBox',
                'name'     => 'IPSViewUseTileTimeSettings',
                'caption'  => 'Use Tile time settings',
                'onChange' => $timeAction
            ],
            [
                'type'    => 'Label',
                'caption' => 'Disable this option to use a separate IPSView time range and time-axis label format.'
            ],
            ['type' => 'RowLayout', 'items' => $ipsViewTimeItems]
        ];
    }

    /** @param list<array<string, mixed>> $elements @return array<string, mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $designRows = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $designRows = $element['items'] ?? null;
                break;
            }
        }
        if (!is_array($designRows)) {
            throw new RuntimeException('The Historical Bar Tile designer section is missing.');
        }

        $ipsViewDesignRows = [];
        $designFieldCount = 0;
        foreach ($designRows as $row) {
            if (($row['type'] ?? null) !== 'RowLayout' || !is_array($row['items'] ?? null)) {
                continue;
            }
            $items = [];
            foreach ($row['items'] as $item) {
                $name = $item['name'] ?? null;
                if (is_string($name) && array_key_exists($name, self::DESIGN_PROPERTY_TYPES)) {
                    $item['name'] = 'IPSView' . $name;
                    if (in_array($name, ['RoundedBars', 'BarFillMode'], true)) {
                        $item['onChange'] = 'ECBH_UpdateBarDesignForm($id, true, '
                            . '$IPSViewRoundedBars, $IPSViewBarFillMode);';
                    }
                    if ($name === 'BarGradientColor') {
                        $item['visible'] = $this->ReadPropertyString('IPSViewBarFillMode') === 'gradient';
                    }
                    if (in_array($name, ['BarSVG', 'BarSVGSizePercent'], true)) {
                        $item['visible'] = $this->ReadPropertyString('IPSViewBarFillMode') === 'svg';
                    }
                    if ($name === 'BarCornerRadius') {
                        $item['visible'] = $this->ReadPropertyBoolean('IPSViewRoundedBars');
                    }
                    $items[] = $item;
                    $designFieldCount++;
                }
            }
            if ($items !== []) {
                $ipsViewDesignRows[] = ['type' => 'RowLayout', 'items' => $items];
            }
        }
        if ($designFieldCount !== count(self::DESIGN_PROPERTY_TYPES)) {
            throw new RuntimeException('The Historical Bar designer fields are missing.');
        }

        return [
            'type'     => 'ExpansionPanel',
            'caption'  => 'IPSView design',
            'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems(
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                ),
                EChartsIPSViewBackground::FormRow(),
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
                    'caption' => 'Copy Tile design to IPSView and edit independently'
                ],
                [
                    'type'     => 'ExpansionPanel',
                    'caption'  => 'Independent IPSView designer',
                    'expanded' => false,
                    'items'    => $ipsViewDesignRows
                ]
            ]
        ];
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
