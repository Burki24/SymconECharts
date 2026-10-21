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
use SymconECharts\HeatmapColorScales;
use SymconECharts\HeatmapWindow;

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
require_once __DIR__ . '/HeatmapColorScales.php';
require_once __DIR__ . '/HeatmapWindow.php';

class EChartsHeatmap extends IPSModuleStrict
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewHeatmap';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const RANGES = HeatmapWindow::RANGES;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO,
        'ColorScalePreset'     => HeatmapColorScales::CUSTOM,
        'LowColor'             => -1,
        'CustomMiddleColor1'   => -1,
        'CustomMiddleColor2'   => -1,
        'CustomMiddleColor3'   => -1,
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
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterTimer('ArchiveRefresh', 0, 'ECHM_RefreshArchive($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECHM');
            $this->RegisterPropertyInteger('SourceVariableID', 0);
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Range', '1m');
            $this->RegisterPropertyInteger('CustomRangeValue', 7);
            $this->RegisterPropertyString('CustomRangeUnit', 'day');
            $this->RegisterPropertyString('AbsoluteStart', '{}');
            $this->RegisterPropertyString('AbsoluteEnd', '{}');
            $this->RegisterPropertyString('Metric', 'average');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Heatmap for IPSView'), 90);
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
            $range = $this->ReadPropertyString('Range');
            $form['elements'] = $this->SetFormFieldVisibility($form['elements'], ['CustomRangeValue', 'CustomRangeUnit'], $range === 'custom');
            $form['elements'] = $this->SetFormFieldVisibility($form['elements'], ['AbsoluteStart', 'AbsoluteEnd', 'AbsoluteRangeHint', 'AbsoluteTimeZone'], $range === 'absolute');
            foreach ($form['elements'] as &$element) {
                if (($element['name'] ?? '') === 'AbsoluteTimeZone') {
                    $element['caption'] = sprintf($this->Translate('Symcon time zone: %s'), date_default_timezone_get());
                }
            }
            unset($element);
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECHM');
            $form = $this->WithIPSViewDesignFormState($form, 'ECHM');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Color scale', 'start' => 'ColorScalePreset'],
                ['caption' => 'Labels and typography', 'start' => 'ShowValues']
            ]);
            $fields = [
                'Title', 'SourceVariableID', 'Range', 'CustomRangeValue', 'CustomRangeUnit', 'AbsoluteStart', 'AbsoluteEnd', 'Metric', 'UseVariablePresentation', 'Unit', 'Decimals',
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

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECHM', $fields));
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
            foreach (['AbsoluteStart', 'AbsoluteEnd', 'AbsoluteRangeHint', 'AbsoluteTimeZone'] as $name) {
                $this->UpdateFormField($name, 'visible', $Range === 'absolute');
            }
            foreach (['CustomRangeValue', 'CustomRangeUnit'] as $name) {
                $this->UpdateFormField($name, 'visible', $Range === 'custom');
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

    public function CopyTileDesignToIPSView(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
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
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function GetHeatmapData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetHeatmapDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetHeatmapDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();
            return $debugFlowResult = json_encode([
                'valid'     => $error === null,
                'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
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
            return $debugFlowResult = $this->RenderHeatmapHTMLPage(false);
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
            return $debugFlowResult = $this->RenderHeatmapHTMLPage(true);
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
                fn (array $payload): array => $this->BuildVisualizationState(
                    ($payload['Output'] ?? 'ipsview') !== 'tile',
                    (int) ($payload['WindowOffset'] ?? 0)
                ),
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

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $low = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LowColor', 'integer', $ipsView), $palette['track']);
        $high = EChartsBarPreview::Color($this->BarPreviewValue($values, 'HighColor', 'integer', $ipsView), $palette['accent']);
        $preset = $this->BarPreviewValue($values, 'ColorScalePreset', 'string', $ipsView);
        $scaleColors = HeatmapColorScales::Colors($preset);
        $scaleRange = HeatmapColorScales::Range($preset);
        $classScale = $scaleColors !== [] && $scaleRange !== null;
        if (!$classScale) {
            $scaleColors = [$low];
            if ($preset === HeatmapColorScales::CUSTOM) {
                foreach ([1, 2, 3] as $index) {
                    $configured = $this->BarPreviewValue($values, 'CustomMiddleColor' . $index, 'integer', $ipsView);
                    if ($configured >= 0 && $configured <= 0xFFFFFF) {
                        $scaleColors[] = EChartsAsset::ColorToHex($configured);
                    }
                }
            }
            $scaleColors[] = $high;
        }
        $titleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']);
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $valueColor = $this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView);
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
        try {
            $query = $this->ResolveHeatmapWindow(0, $values);
        } catch (Throwable $exception) {
            return $svg . '<text x="24" y="100" font-size="11" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E($this->Translate($exception->getMessage())) . '</text></svg>';
        }
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $showScale = $this->BarPreviewValue($values, 'ShowColorScale', 'boolean', $ipsView);
        $sourceID = (int) ($values['SourceVariableID'] ?? $this->ReadPropertyInteger('SourceVariableID'));
        $sourceName = $sourceID > 0 && IPS_VariableExists($sourceID) ? IPS_GetName($sourceID) : $this->Translate('No source selected');
        $range = (string) ($values['Range'] ?? $this->ReadPropertyString('Range'));
        $rangeLabel = $this->Translate(match ($range) {
            '1w'     => '1 week', '1m' => '1 month', '3m' => '3 months',
            '6m'     => '6 months', '12m' => '12 months', 'ytd' => 'Since January 1',
            'custom' => 'Custom', 'absolute' => 'Fixed historical period',
            default  => $range
        });
        $svg = str_replace('<svg ', '<svg data-range="' . $range . '" data-start="' . $query['StartTimestamp']
            . '" data-end="' . $query['EndTimestamp'] . '" ', $svg);
        $metric = (string) ($values['Metric'] ?? $this->ReadPropertyString('Metric'));
        $metricLabel = $this->Translate(self::MetricLabel($metric));
        $svg .= '<text x="54" y="53" fill="' . EChartsBarPreview::E($palette['muted'])
            . '" font-size="11">' . EChartsBarPreview::E($sourceName . ' · ' . $rangeLabel . ' · ' . $metricLabel) . '</text>';
        $extended = isset($query['RequestedStartTimestamp']);
        if ($extended) {
            $svg .= '<text x="54" y="72" fill="' . EChartsBarPreview::E($palette['muted']) . '" font-size="9">'
                . EChartsBarPreview::E($query['RequestedStartLabel'] . ' – ' . $query['RequestedEndLabel'] . ' · '
                    . date_default_timezone_get() . ' · ' . $this->Translate('Until (exclusive)')) . '</text>';
        }
        $plotTop = $extended ? 88 : 64;
        $rowHeight = $extended ? 30 : 34;
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $datedDays = $query['Days'];
        foreach ($weekdays as $day => $name) {
            $label = $datedDays === [] ? $this->Translate($name)
                : $this->Translate($weekdays[$datedDays[$day]['weekday']]) . ' '
                    . substr($datedDays[$day]['date'], 8, 2) . '.' . substr($datedDays[$day]['date'], 5, 2) . '.';
            $svg .= '<text x="74" y="' . ($plotTop + 18 + $day * $rowHeight) . '" text-anchor="end" fill="'
                . EChartsBarPreview::E($axisColor) . '" font-size="12">'
                . EChartsBarPreview::E($label) . '</text>';
            for ($hour = 0; $hour < 24; $hour++) {
                $ratio = (($hour * 11 + $day * 17) % 100) / 100;
                $color = self::ScaleColor($scaleColors, $ratio, $classScale);
                $x = 82 + $hour * 24;
                $y = $plotTop + $day * $rowHeight;
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="22" height="' . ($rowHeight - 4) . '" rx="2" fill="'
                    . EChartsBarPreview::E($color) . '"/>';
                if ($showValues && $hour % 3 === 0) {
                    $sample = $classScale
                        ? $scaleRange['min'] + ($scaleRange['max'] - $scaleRange['min']) * $ratio
                        : $ratio * 100;
                    $svg .= '<text x="' . ($x + 11) . '" y="' . ($y + 19)
                        . '" text-anchor="middle" fill="' . EChartsBarPreview::E($valueColor < 0 || $valueColor > 0xFFFFFF
                            ? self::ReadableCellTextColor($color) : EChartsAsset::ColorToHex($valueColor))
                        . '" font-size="10">' . round($sample, 1) . '</text>';
                }
            }
        }
        foreach ([0, 6, 12, 18, 23] as $hour) {
            $svg .= '<text x="' . (93 + $hour * 24) . '" y="312" text-anchor="middle" fill="'
                . EChartsBarPreview::E($axisColor) . '" font-size="12">' . $hour . '</text>';
        }
        if ($showScale) {
            if ($classScale) {
                foreach ($scaleColors as $index => $color) {
                    $svg .= '<rect x="' . (260 + $index * 200 / count($scaleColors))
                        . '" y="330" width="' . (200 / count($scaleColors))
                        . '" height="8" fill="' . EChartsBarPreview::E($color) . '"/>';
                }
                $svg .= '<text x="250" y="338" text-anchor="end" fill="' . EChartsBarPreview::E($axisColor)
                    . '" font-size="10">' . $scaleRange['min'] . ' °C</text>';
                $svg .= '<text x="470" y="338" fill="' . EChartsBarPreview::E($axisColor)
                    . '" font-size="10">' . $scaleRange['max'] . ' °C</text>';
            } else {
                $svg .= '<defs><linearGradient id="scale">';
                foreach ($scaleColors as $index => $color) {
                    $svg .= '<stop offset="' . round($index * 100 / (count($scaleColors) - 1), 2)
                        . '%" stop-color="' . EChartsBarPreview::E($color) . '"/>';
                }
                $svg .= '</linearGradient></defs><rect x="260" y="330" width="200" height="8" fill="url(#scale)"/>';
            }
        }

        return $svg . '</svg>';
    }

    protected function CurrentTimestamp(): int
    {
        return time();
    }

    private function GetHeatmapDataForOutput(bool $ipsView, int $windowOffset = 0): string
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
        $now = $this->CurrentTimestamp();
        $settings = $this->HeatmapPeriodSettings();
        $query = $this->ResolveHeatmapWindow($windowOffset);
        $archive = $this->ReadArchive($variableID, $query);
        try {
            $cells = HeatmapAggregation::ByWeekdayAndHour($archive['Points'], $timezone, $this->ReadPropertyString('Metric'));
        } catch (Throwable $exception) {
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid Heatmap values.', 0, $exception);
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $style = $this->ReadDesignStyle($ipsView);
        $viewKey = hash('sha256', json_encode([$variableID, $range, $this->ReadPropertyString('Metric'), $timezone,
            $range === 'absolute' ? [$query['BaseStartTimestamp'], $query['BaseEndTimestamp']]
                : ($range === 'custom' ? $query['DurationSeconds'] : null),
            $presentation, $style, $this->EffectiveTheme($ipsView), $this->ReadPropertyString('Title')], JSON_THROW_ON_ERROR));
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
                'metric'         => $this->ReadPropertyString('Metric'),
                'windowOffset'   => $windowOffset,
                'startDate'      => $query['StartDate'],
                'endDate'        => $query['EndDate'],
                'days'           => $range === '1w' ? $query['Days'] : [],
                'maxOffset'      => HeatmapWindow::MaximumOffset($range, $now, $timezone, $settings),
                'startTimestamp' => $query['StartTimestamp'],
                'endTimestamp'   => $query['EndTimestamp'],
                'style'          => $style,
                'viewKey'        => $viewKey,
                ...(isset($query['RequestedStartTimestamp']) ? [
                    'requestedStartTimestamp' => $query['RequestedStartTimestamp'], 'requestedEndTimestamp' => $query['RequestedEndTimestamp'],
                    'requestedStartLabel'     => $query['RequestedStartLabel'], 'requestedEndLabel' => $query['RequestedEndLabel'],
                    'durationSeconds'         => $query['DurationSeconds']
                ] : [])
            ],
            'cells'     => $cells,
            'truncated' => $archive['Truncated']
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** Pure draft/stored settings, shared by validation, navigation and both previews. */
    private function HeatmapPeriodSettings(array $values = []): array
    {
        $settings = [];
        foreach (['CustomRangeUnit', 'AbsoluteStart', 'AbsoluteEnd'] as $name) {
            $settings[$name] = $values[$name] ?? $this->ReadPropertyString($name);
        }
        $settings['CustomRangeValue'] = $values['CustomRangeValue'] ?? $this->ReadPropertyInteger('CustomRangeValue');
        return $settings;
    }

    private function ResolveHeatmapWindow(int $offset = 0, array $values = []): array
    {
        $range = $values['Range'] ?? $this->ReadPropertyString('Range');
        if (!is_string($range)) {
            throw new InvalidArgumentException('The Heatmap window is invalid.');
        }
        return HeatmapWindow::Resolve($range, $offset, $this->CurrentTimestamp(), date_default_timezone_get(), $this->HeatmapPeriodSettings($values));
    }

    /** @param array<string,mixed> $query @return array{Points:list<array{0:int,1:float}>,Truncated:bool} */
    private function ReadArchive(int $variableID, array $query): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__, ['Variable' => $variableID]);
        try {
            if ($query['Empty']) {
                return $debugFlowResult = ['Points' => [], 'Truncated' => false];
            }
            $points = [];
            foreach (HeatmapWindow::ArchiveChunks($query['StartTimestamp'], $query['EndTimestamp']) as $chunk) {
                $archive = $this->ReadArchiveChunk($variableID, $chunk, $query['Limit']);
                if ($archive['Truncated']) {
                    $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                    throw new RuntimeException('The gateway truncated a Heatmap archive interval.');
                }
                array_push($points, ...$archive['Points']);
            }

            return $debugFlowResult = ['Points' => $points, 'Truncated' => false];
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    /** @param array{StartTimestamp:int,EndTimestamp:int} $chunk @return array{Points:list<array{0:int,1:float}>,Truncated:bool} */
    private function ReadArchiveChunk(int $variableID, array $chunk, int $limit): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__, ['Variable' => $variableID]);
        try {
            $payload = [
                'VariableID'       => $variableID,
                'StartTimestamp'   => $chunk['StartTimestamp'],
                'EndTimestamp'     => $chunk['EndTimestamp'],
                'Mode'             => 'aggregated',
                'AggregationLevel' => 0,
                'Reducer'          => $this->ReadPropertyString('Metric') === 'consumption' ? 'sum' : $this->ReadPropertyString('Metric'),
                'Limit'            => $limit
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
                throw new RuntimeException('The gateway returned an invalid archive response.', 0, $exception);
            }
            if (!$response['Success']) {
                $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
                $this->WriteAttributeString('LastError', $code);
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway archive request failed: ' . $code);
            }
            $archive = $response['Payload'];
            if (($archive['VariableID'] ?? null) !== $variableID
                || ($archive['VariableType'] ?? null) !== IPS_GetVariable($variableID)['VariableType']
                || ($archive['StartTimestamp'] ?? null) !== $chunk['StartTimestamp']
                || ($archive['EndTimestamp'] ?? null) !== $chunk['EndTimestamp']
                || ($archive['EffectiveReducer'] ?? null) !== $payload['Reducer']
                || ($archive['ArchiveAggregationType'] ?? null) !== ($this->ReadPropertyString('Metric') === 'consumption' ? 'counter' : 'standard')
                || !is_array($archive['Points'] ?? null) || !array_is_list($archive['Points'])
                || count($archive['Points']) > $limit || ($archive['Truncated'] ?? null) !== false) {
                throw new RuntimeException('The gateway returned invalid Heatmap data.');
            }
            $points = [];
            $previousTimestamp = 0;
            foreach ($archive['Points'] as $point) {
                if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                    || !is_int($point[0]) || $point[0] <= $previousTimestamp
                    || $point[0] < $chunk['StartTimestamp'] || $point[0] > $chunk['EndTimestamp']
                    || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])
                    || ($payload['Reducer'] === 'sum' && $point[1] < 0)) {
                    throw new RuntimeException('The gateway returned invalid Heatmap points.');
                }
                $previousTimestamp = $point[0];
                $points[] = [$point[0], (float) $point[1]];
            }

            return $debugFlowResult = ['Points' => $points, 'Truncated' => $archive['Truncated']];
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
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
                'The selected range was truncated.'                   => $this->Translate('The selected range was truncated.'),
                'Heatmap'                                             => $this->Translate('Heatmap'),
                '24 hours by seven weekdays.'                         => $this->Translate('24 hours by seven weekdays.'),
                'Hourly intervals'                                    => $this->Translate('Hourly intervals'),
                'Average of hourly means'                             => $this->Translate('Average of hourly means'),
                'Minimum of hourly minima'                            => $this->Translate('Minimum of hourly minima'),
                'Maximum of hourly maxima'                            => $this->Translate('Maximum of hourly maxima'),
                'Sum of hourly consumption'                           => $this->Translate('Sum of hourly consumption'),
                'Minimum of {count} hourly intervals'                 => $this->Translate('Minimum of {count} hourly intervals'),
                'Maximum of {count} hourly intervals'                 => $this->Translate('Maximum of {count} hourly intervals'),
                'Consumption total of {count} hourly intervals'       => $this->Translate('Consumption total of {count} hourly intervals'),
                '1 month'                                             => $this->Translate('1 month'),
                '3 months'                                            => $this->Translate('3 months'),
                '6 months'                                            => $this->Translate('6 months'),
                '12 months'                                           => $this->Translate('12 months'),
                'Since January 1'                                     => $this->Translate('Since January 1'),
                'No completed archive hours in the selected range.'   => $this->Translate(
                    'No completed archive hours in the selected range.'
                ),
                'Previous period'                     => $this->Translate('Previous period'),
                'Next period'                         => $this->Translate('Next period'),
                'Time range'                          => $this->Translate('Time range'),
                'Today'                               => $this->Translate('Today'),
                'Selected period'                     => $this->Translate('Selected period'),
                'Custom'                              => $this->Translate('Custom'),
                'Fixed historical period'             => $this->Translate('Fixed historical period'),
                'Until (exclusive)'                   => $this->Translate('Until (exclusive)'),
                'From (inclusive)'                    => $this->Translate('From (inclusive)'),
                '1 week'                              => $this->Translate('1 week'),
                '1-month average'                     => $this->Translate('1-month average'),
                '3-month average'                     => $this->Translate('3-month average'),
                '6-month average'                     => $this->Translate('6-month average'),
                '12-month average'                    => $this->Translate('12-month average'),
                'Since January 1 average'             => $this->Translate('Since January 1 average'),
                'Average of {count} hourly intervals' => $this->Translate('Average of {count} hourly intervals'),
                'Mon'                                 => $this->Translate('Mon'), 'Tue' => $this->Translate('Tue'),
                'Wed'                                 => $this->Translate('Wed'), 'Thu' => $this->Translate('Thu'),
                'Fri'                                 => $this->Translate('Fri'), 'Sat' => $this->Translate('Sat'),
                'Sun'                                 => $this->Translate('Sun')
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'heatmapStatePath'  => $this->EChartsIPSViewTransportOptions()['statePath'],
                'heatmapMaxOffset'  => EChartsDataProtocol::MAX_STATE_WINDOW_OFFSET
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
        $this->DebugEChartsSetStatus(IS_INACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 0);
        $this->SetTimerInterval('TransientRetry', 0);
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
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($variableID <= 0 || !IPS_VariableExists($variableID)
            || !in_array(IPS_GetVariable($variableID)['VariableType'] ?? null, [1, 2], true)) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Configure a numeric archive variable.'];
        }
        try {
            $this->ResolveHeatmapWindow();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!in_array($this->ReadPropertyString('Range'), self::RANGES, true)
            || !in_array($this->ReadPropertyString('Metric'), ['average', 'minimum', 'maximum', 'consumption'], true)
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
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !HeatmapColorScales::IsSupported($this->ReadPropertyString($prefix . 'ColorScalePreset'))
            || ($this->ReadPropertyString('Metric') === 'consumption'
                && $this->ReadPropertyString($prefix . 'ColorScalePreset') !== HeatmapColorScales::CUSTOM)) {
            return false;
        }
        foreach (['LowColor', 'CustomMiddleColor1', 'CustomMiddleColor2', 'CustomMiddleColor3',
            'HighColor', 'TitleColor', 'AxisColor', 'ValueColor', 'GridColor'] as $name) {
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
    private function BuildVisualizationState(bool $ipsView = false, int $windowOffset = 0): array
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
            $chart = json_decode($this->GetHeatmapDataForOutput($ipsView, $windowOffset), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'error',
                'chart'             => null, 'error' => 'The Heatmap values could not be loaded.'];
        }

        return ['schemaVersion' => 1, 'family' => 'heatmap', 'status' => 'ready',
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

    private function EffectiveTheme(bool $ipsView): string
    {
        return $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('IPSViewEChartsTheme')
            : $this->ReadPropertyString('EChartsTheme');
    }

    private static function MetricLabel(string $metric): string
    {
        return match ($metric) {
            'minimum'     => 'Minimum of hourly minima', 'maximum' => 'Maximum of hourly maxima',
            'consumption' => 'Sum of hourly consumption', default => 'Average of hourly means'
        };
    }

    /** @return array<string,mixed> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';

        return [
            ...$this->ReadAnimationStyle($prefix),
            'colorScalePreset'     => $this->ReadPropertyString($prefix . 'ColorScalePreset'),
            'colorScaleColors'     => HeatmapColorScales::Colors($this->ReadPropertyString($prefix . 'ColorScalePreset')),
            'colorScaleRange'      => HeatmapColorScales::Range($this->ReadPropertyString($prefix . 'ColorScalePreset')),
            'customMiddleColors'   => $this->CustomMiddleColors($prefix),
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

    /** @return list<string> */
    private function CustomMiddleColors(string $prefix): array
    {
        $colors = [];
        foreach ([1, 2, 3] as $index) {
            $color = $this->OptionalDesignColor($prefix . 'CustomMiddleColor' . $index);
            if ($color !== '') {
                $colors[] = $color;
            }
        }

        return $colors;
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

    /** @param list<string> $colors */
    private static function ScaleColor(array $colors, float $ratio, bool $classScale): string
    {
        if ($classScale) {
            return $colors[min(count($colors) - 1, (int) floor(max(0.0, min(1.0, $ratio)) * count($colors)))];
        }
        $position = max(0.0, min(1.0, $ratio)) * (count($colors) - 1);
        $index = min(count($colors) - 2, (int) floor($position));
        return self::InterpolateColor($colors[$index], $colors[$index + 1], $position - $index);
    }

    private static function ReadableCellTextColor(string $background): string
    {
        $channels = sscanf($background, '#%02x%02x%02x');
        $linear = array_map(static function (int $channel): float
        {
            $normalized = $channel / 255;
            return $normalized <= 0.04045
                ? $normalized / 12.92
                : (($normalized + 0.055) / 1.055) ** 2.4;
        }, $channels);
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
        $darkLuminance = ((17 / 255 + 0.055) / 1.055) ** 2.4;
        return 1.05 / ($luminance + 0.05) >= ($luminance + 0.05) / ($darkLuminance + 0.05)
            ? '#FFFFFF' : '#111111';
    }
}
