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
use SymconECharts\EChartsPreviewSourceCount;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsStartupState;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\ThemeRiverData;

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
require_once __DIR__ . '/../libs/EChartsPreviewSymbol.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsStartupState.php';
require_once __DIR__ . '/../libs/EChartsPreviewSourceCount.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/ThemeRiverData.php';
require_once __DIR__ . '/ThemeRiverPreview.php';

class EChartsThemeRiver extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use SymconECharts\ThemeRiverPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewThemeRiver';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const MAXIMUM_MODEL_BYTES = 900000;
    private const MODEL_TOO_LARGE_MESSAGE = 'The ThemeRiver model is too large. Choose a shorter range.';
    private const LOAD_BUDGET_MESSAGE = 'The ThemeRiver archive load budget was exceeded.';
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => 'auto', 'ShowTooltip' => true, 'ShowLegend' => true, 'ShowLabels' => false,
        'BandOpacityPercent'   => 85, 'BorderWidth' => 0, 'BorderColor' => -1,
        'ShowAxisLabels'       => true, 'ShowAxisTicks' => true,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100,
        'LabelFontSizePercent' => 100, 'LegendFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'LabelColor' => -1, 'LegendColor' => -1,
        'BandHeightPercent'    => 84, 'BorderType' => 'solid', 'ShadowBlur' => 0, 'ShadowColor' => -1,
        'ShadowOffsetX'        => 0, 'ShadowOffsetY' => 0, 'EmphasisEnabled' => true, 'EmphasisFocus' => 'self', 'BlurOpacityPercent' => 10,
        'ShowAxisLine'         => true, 'AxisLineWidth' => 1, 'AxisLineType' => 'solid', 'AxisLabelMargin' => 12, 'TimeLabelFormat' => 'auto',
        'ShowGrid'             => false, 'GridLineWidth' => 1, 'GridLineType' => 'dashed', 'GridColor' => -1, 'GridOpacityPercent' => 20,
        'LegendPosition'       => 'bottom', 'LegendAlignment' => 'left', 'LegendIcon' => 'roundRect',
        'LegendItemWidth'      => 25, 'LegendItemHeight' => 14, 'LegendItemGap' => 10, 'FontFamily' => 'Arial, sans-serif',
        'TitleBold'            => true, 'AxisLabelBold' => false, 'LabelBold' => false, 'LegendBold' => false,
        'TitleItalic'          => false, 'AxisLabelItalic' => false, 'LabelItalic' => false, 'LegendItalic' => false
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
            $this->RegisterTimer('ArchiveRefresh', 0, 'ECRV_RefreshArchive($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECRV');
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Metric', 'average');
            $this->RegisterPropertyBoolean('EnableBandSelection', false);
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Range', '7d');
            $this->RegisterPropertyInteger('CustomRangeValue', 7);
            $this->RegisterPropertyString('CustomRangeUnit', 'day');
            $this->RegisterPropertyString('AbsoluteStart', '{}');
            $this->RegisterPropertyString('AbsoluteEnd', '{}');
            $this->RegisterPropertyString('Aggregation', 'hour');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('ThemeRiver for IPSView'), 90);
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
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['CustomRangeValue', 'CustomRangeUnit'],
                $this->ReadPropertyString('Range') === 'custom'
            );
            $form['elements'] = $this->SetFormFieldVisibility(
                $form['elements'],
                ['AbsoluteStart', 'AbsoluteEnd', 'AbsoluteRangeHint', 'AbsoluteTimeZone'],
                $this->ReadPropertyString('Range') === 'absolute'
            );
            foreach ($form['elements'] as &$element) {
                if (($element['name'] ?? '') === 'AbsoluteTimeZone') {
                    $element['caption'] = sprintf($this->Translate('Symcon time zone: %s'), date_default_timezone_get());
                }
            }
            unset($element);
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECRV');
            $form = $this->WithIPSViewDesignFormState($form, 'ECRV');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
                ['caption' => 'Bands and display', 'start' => 'ShowTooltip'],
                ['caption' => 'Shadows and highlighting', 'start' => 'ShadowBlur'],
                ['caption' => 'Time axis', 'start' => 'ShowAxisLabels'],
                ['caption' => 'Legend', 'start' => 'LegendPosition'],
                ['caption' => 'Typography and colors', 'start' => 'FontFamily']
            ]);
            $fields = ['Title', 'Sources', 'Metric', 'EnableBandSelection', 'Range', 'CustomRangeValue', 'CustomRangeUnit', 'AbsoluteStart', 'AbsoluteEnd', 'Aggregation',
                'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
                'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
                ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
            foreach (['', 'IPSView'] as $prefix) {
                foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                    'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                    $fields[] = $prefix . $name;
                }
            }
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECRV', $fields));
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

    public function GetThemeRiverData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetThemeRiverDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetThemeRiverDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();
            return $debugFlowResult = json_encode(
                ['valid'        => $error === null,
                    'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            );
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
            return $debugFlowResult = $this->RenderThemeRiverHTMLPage(false);
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
            return $debugFlowResult = $this->RenderThemeRiverHTMLPage(true);
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
        return $this->ThemeRiverPreviewSvg($values, $ipsView);
    }

    private function GetThemeRiverDataForOutput(bool $ipsView): string
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
        $query = $this->ResolveTimeQuery();
        $series = [];
        $descriptors = [];
        $loadedAggregates = 0;
        foreach ($sources as $source) {
            $archive = $this->ReadArchiveSource($source, $query);
            try {
                $loadedAggregates = ThemeRiverData::CountLoadedAggregates($loadedAggregates, count($archive['Points']));
            } catch (InvalidArgumentException $exception) {
                $this->WriteAttributeString('LastError', 'ARCHIVE_BUDGET_EXCEEDED');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw $exception;
            }
            $series[] = $archive['Points'];
            $descriptors[] = ['id' => 'variable-' . $source['VariableID'], 'label' => $source['Label'],
                'initiallyVisible' => $source['InitiallyVisible'],
                'unit'             => $source['Unit'], 'decimals' => $source['Decimals'],
                'color'            => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color'])];
        }
        try {
            $data = ThemeRiverData::Join($series, $query['Intervals']);
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $range = ['key'    => $this->ReadPropertyString('Range'), 'startTimestamp' => $query['StartTimestamp'],
            'endTimestamp' => $query['EndTimestamp'], 'aggregationLevel' => $query['AggregationLevel'],
            'timezone'     => date_default_timezone_get(), 'loadedAggregates' => $loadedAggregates,
            'loadBudget'   => ThemeRiverData::MAXIMUM_LOADED_AGGREGATES];
        if ($range['key'] === 'absolute') {
            $range += ['requestedStartTimestamp' => $query['RequestedStartTimestamp'],
                'requestedEndTimestamp'          => $query['RequestedEndTimestamp']];
        }
        $json = json_encode([
            'schemaVersion' => 1, 'family' => 'themeRiver', 'theme' => $this->EffectiveTheme($ipsView),
            'themeRiver'    => ['title' => $this->ReadPropertyString('Title'), 'unit' => $sources[0]['Unit'],
                'metric'                => $this->ReadPropertyString('Metric'),
                'enableBandSelection'   => $this->ReadPropertyBoolean('EnableBandSelection'),
                'aggregation'           => $this->ReadPropertyString('Aggregation'), 'sources' => $descriptors,
                ...$data, 'style'       => $this->ReadDesignStyle($ipsView)],
            'range' => $range
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if (strlen($json) > self::MAXIMUM_MODEL_BYTES) {
            $this->WriteAttributeString('LastError', 'MODEL_TOO_LARGE');
            $this->DebugEChartsSetStatus(self::STATUS_CONFIGURATION_INVALID);
            throw new RuntimeException(self::MODEL_TOO_LARGE_MESSAGE);
        }
        return $json;
    }

    private function RenderThemeRiverHTMLPage(bool $ipsView): string
    {
        $startup = EChartsStartupState::Startup($this->BuildVisualizationState($ipsView));
        $configuration = [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts ThemeRiver',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-theme-river-root', 'echarts-theme-river'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $startup['state'],
            'translations' => [
                'All'                                                                                                    => $this->Translate('All'),
                'Invert'                                                                                                 => $this->Translate('Invert'),
                'No bands selected.'                                                                                     => $this->Translate('No bands selected.'),
                'All selected interval means are zero.'                                                                  => $this->Translate('All selected interval means are zero.'),
                'Consumption is zero in all completed intervals.'                                                        => $this->Translate('Consumption is zero in all completed intervals.'),
                'Consumption is zero in all selected bands.'                                                             => $this->Translate('Consumption is zero in all selected bands.'),
                'Completed interval consumption'                                                                         => $this->Translate('Completed interval consumption'),
                'ThemeRiver consumption requires counter archive variables.'                                             => $this->Translate('ThemeRiver consumption requires counter archive variables.'),
                'The ThemeRiver metric is invalid.'                                                                      => $this->Translate('The ThemeRiver metric is invalid.'),
                'The ThemeRiver archive values are invalid. Only nonnegative, finite interval consumption is supported.' => $this->Translate('The ThemeRiver archive values are invalid. Only nonnegative, finite interval consumption is supported.'),
                self::MODEL_TOO_LARGE_MESSAGE                                                                            => $this->Translate(self::MODEL_TOO_LARGE_MESSAGE),
                self::LOAD_BUDGET_MESSAGE                                                                                => $this->Translate(self::LOAD_BUDGET_MESSAGE),
                'Too many archive intervals. Choose a shorter range or a longer interval.'                               => $this->Translate('Too many archive intervals. Choose a shorter range or a longer interval.'),
                'The archive interval settings are invalid.'                                                             => $this->Translate('The archive interval settings are invalid.'),
                'Configure valid ThemeRiver archive variables.'                                                          => $this->Translate('Configure valid ThemeRiver archive variables.'),
                'Connect an active EChartsGateway.'                                                                      => $this->Translate('Connect an active EChartsGateway.'),
                'The ThemeRiver values could not be loaded.'                                                             => $this->Translate('The ThemeRiver values could not be loaded.'),
                'The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.'      => $this->Translate('The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.'),
                'ThemeRiver requires standard archive variables, not counters.'                                          => $this->Translate('ThemeRiver requires standard archive variables, not counters.'),
                'No common completed archive intervals.'                                                                 => $this->Translate('No common completed archive intervals.'),
                'Intervals with missing archive values are left blank.'                                                  => $this->Translate('Intervals with missing archive values are left blank.'),
                'All completed interval means are zero.'                                                                 => $this->Translate('All completed interval means are zero.'),
                'Data could not be refreshed. Showing the last available values.'                                        => $this->Translate('Data could not be refreshed. Showing the last available values.'),
                'Apache ECharts could not be initialized.'                                                               => $this->Translate('Apache ECharts could not be initialized.'),
                'Completed interval means'                                                                               => $this->Translate('Completed interval means'),
            ],
            'options' => [
                'echartsVersion'            => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground'         => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'          => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'compressedStateGzipBase64' => $startup['compressedStateGzipBase64']
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::ThemeRiverJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::StartupJavaScript()
                    . "\n" . EChartsAsset::SeriesSelectionJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ];
        $html = $this->RenderVisualizationHTMLPage($ipsView, $configuration);
        if (strlen($html) > 1048576) {
            $this->WriteAttributeString('LastError', 'MODEL_TOO_LARGE');
            $this->DebugEChartsSetStatus(self::STATUS_CONFIGURATION_INVALID);
            $configuration['state'] = $this->ErrorState(self::MODEL_TOO_LARGE_MESSAGE);
            $configuration['options']['compressedStateGzipBase64'] = null;
            return $this->RenderVisualizationHTMLPage($ipsView, $configuration);
        }
        return $html;
    }

    private function Initialize(): void
    {
        $this->DebugEChartsSetStatus(IS_INACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 0);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $this->SynchronizeSourceReferences();
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
        $this->SetSummary($this->ReadPropertyString('Range') . ' / ' . $this->ReadPropertyString('Aggregation'));
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            ThemeRiverData::ArchiveContract($this->ReadPropertyString('Metric'));
        } catch (InvalidArgumentException $exception) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $exception->getMessage()];
        }
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (mb_strlen($this->ReadPropertyString('Title')) > 200
            || !in_array($this->ReadPropertyString('Range'), ['24h', '7d', '30d', 'custom', 'absolute'], true)
            || !in_array($this->ReadPropertyString('Aggregation'), ['hour', 'day'], true)
            || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The ThemeRiver settings are invalid.'];
        }
        try {
            $this->ResolveTimeQuery();
        } catch (InvalidArgumentException $exception) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $this->ReadPropertyString('Range') === 'absolute'
                ? $this->Translate($exception->getMessage()) : $exception->getMessage()];
        }
        return null;
    }

    /** Pure planning shared by saved settings and read-only preview drafts. */
    private function ResolveTimeQuery(array $values = []): array
    {
        $range = $values['Range'] ?? $this->ReadPropertyString('Range');
        $interval = $values['Aggregation'] ?? $this->ReadPropertyString('Aggregation');
        $timezone = date_default_timezone_get();
        if ($range === 'absolute') {
            $start = EChartsArchiveQuery::ParseLocalDateTime(
                array_key_exists('AbsoluteStart', $values) ? $values['AbsoluteStart'] : $this->ReadPropertyString('AbsoluteStart'),
                $timezone
            );
            $end = EChartsArchiveQuery::ParseLocalDateTime(
                array_key_exists('AbsoluteEnd', $values) ? $values['AbsoluteEnd'] : $this->ReadPropertyString('AbsoluteEnd'),
                $timezone
            );
            return EChartsArchiveQuery::AbsoluteIntervals($start, $end, $interval, $this->CurrentTimestamp(), $timezone)
                + ['RequestedStartTimestamp' => $start, 'RequestedEndTimestamp' => $end];
        }
        return ThemeRiverData::Window(
            $range,
            $interval,
            $this->CurrentTimestamp(),
            $timezone,
            (int) ($values['CustomRangeValue'] ?? $this->ReadPropertyInteger('CustomRangeValue')),
            $values['CustomRangeUnit'] ?? $this->ReadPropertyString('CustomRangeUnit')
        );
    }

    /** @return list<array{VariableID:int,Label:string,Color:int,Unit:string,Decimals:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $json = $this->ReadPropertyString('Sources');
            if (strlen($json) > 65536) {
                throw new InvalidArgumentException('ThemeRiver sources are too large.');
            }
            $raw = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('ThemeRiver sources must contain valid JSON.', 0, $exception);
        }
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > ThemeRiverData::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Configure 1 to 16 unique numeric ThemeRiver variables.');
        }
        $sources = [];
        $units = [];
        $seen = [];
        foreach ($raw as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            $label = is_array($entry) ? ($entry['Label'] ?? '') : null;
            $initiallyVisible = is_array($entry) ? ($entry['InitiallyVisible'] ?? true) : null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || mb_strlen(trim($label)) > 120 || !is_bool($initiallyVisible)) {
                throw new InvalidArgumentException('ThemeRiver sources need distinct numeric variable IDs and valid labels.');
            }
            $color = $entry['Color'] ?? -1;
            if (!is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException('ThemeRiver source colors are invalid.');
            }
            $presentation = EChartsVariablePresentation::Resolve($id, 0.0, 100.0, '', 2, true);
            $sources[] = ['VariableID' => $id, 'Label' => trim($label),
                'InitiallyVisible'     => $initiallyVisible,
                'Color'                => $color, 'Unit' => $presentation['unit'], 'Decimals' => $presentation['decimals']];
            $units[$presentation['unit']] = true;
            $seen[$id] = true;
        }
        if (count($units) > 1) {
            throw new InvalidArgumentException('ThemeRiver variables must use the same unit.');
        }
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        foreach ($sources as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
        }
        unset($source);
        return $sources;
    }

    /** @param array{VariableID:int} $source @param array<string,mixed> $query @return array<string,mixed> */
    private function ReadArchiveSource(array $source, array $query): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if ($query['Empty']) {
                return $debugFlowResult = ['Points' => [], 'Truncated' => false];
            }
            $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
            $contract = ThemeRiverData::ArchiveContract($this->ReadPropertyString('Metric'));
            $payload = ['VariableID' => $source['VariableID'],
                'StartTimestamp'     => $query['StartTimestamp'], 'EndTimestamp' => $query['EndTimestamp'],
                'Mode'               => 'aggregated', 'Reducer' => $contract['reducer'],
                'Limit'              => $query['Limit'], 'AggregationLevel' => $query['AggregationLevel']];
            $this->WriteAttributeString('LastError', '');
            try {
                $response = EChartsDataProtocol::DecodeResponse(
                    $this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                        self::DATA_ID_TO_PARENT,
                        EChartsDataProtocol::CreateRequest($operation, $payload)
                    )),
                    $operation
                );
            } catch (Throwable $exception) {
                $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_RESPONSE');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway returned an invalid ThemeRiver archive response.', 0, $exception);
            }
            if (!$response['Success']) {
                $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
                $this->WriteAttributeString('LastError', $code);
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway archive request failed: ' . $code);
            }
            $archive = $response['Payload'];
            if (in_array($archive['ArchiveAggregationType'] ?? null, ['standard', 'counter'], true)
                && $archive['ArchiveAggregationType'] !== $contract['archiveType']) {
                $this->WriteAttributeString('LastError', 'ARCHIVE_TYPE_MISMATCH');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException($this->ArchiveTypeError());
            }
            if (is_array($archive['Points'] ?? null) && count($archive['Points']) > $query['Limit']) {
                $this->WriteAttributeString('LastError', 'ARCHIVE_BUDGET_EXCEEDED');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException(self::LOAD_BUDGET_MESSAGE);
            }
            if (($archive['VariableID'] ?? null) !== $source['VariableID']
                || !is_array($archive['Points'] ?? null) || ($archive['Truncated'] ?? null) !== false
                || ($archive['StartTimestamp'] ?? null) !== $query['StartTimestamp']
                || ($archive['EndTimestamp'] ?? null) !== $query['EndTimestamp']
                || !in_array($archive['VariableType'] ?? null, [1, 2], true)
                || ($archive['EffectiveReducer'] ?? null) !== $contract['reducer']
                || ($archive['ArchiveAggregationType'] ?? null) !== $contract['archiveType']) {
                $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway returned invalid ThemeRiver archive data.');
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

    private function SynchronizeSourceReferences(): void
    {
        $previous = json_decode($this->ReadAttributeString('RegisteredSourceVariableIDs'), true);
        $previous = is_array($previous) ? array_values(array_filter($previous, is_int(...))) : [];
        $raw = json_decode($this->ReadPropertyString('Sources'), true);
        $current = [];
        foreach (is_array($raw) ? $raw : [] as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            if (is_int($id) && $id > 0 && !in_array($id, $current, true)) {
                $current[] = $id;
            }
        }
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

    private function ArchiveTypeError(): string
    {
        return $this->ReadPropertyString('Metric') === 'consumption'
            ? 'ThemeRiver consumption requires counter archive variables.'
            : 'ThemeRiver requires standard archive variables, not counters.';
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->DebugEChartsSetStatus($configurationError['Status']);
            if ($this->ReadPropertyString('Range') === 'absolute' && $configurationError['Status'] === self::STATUS_CONFIGURATION_INVALID) {
                return $this->ErrorState($configurationError['Message']);
            }
            return $this->ErrorState(in_array($configurationError['Message'], [
                'Too many archive intervals. Choose a shorter range or a longer interval.', 'The archive interval settings are invalid.',
                'The ThemeRiver metric is invalid.'
            ], true) ? $configurationError['Message'] : 'Configure valid ThemeRiver archive variables.');
        }
        if (!$this->HasActiveParent()) {
            $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetThemeRiverDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState(match ($this->ReadAttributeString('LastError')) {
                'MODEL_TOO_LARGE'                                => self::MODEL_TOO_LARGE_MESSAGE,
                'ARCHIVE_BUDGET_EXCEEDED'                        => self::LOAD_BUDGET_MESSAGE,
                'REDUCER_NOT_SUPPORTED', 'ARCHIVE_TYPE_MISMATCH' => $this->ArchiveTypeError(),
                'INVALID_ARCHIVE_DATA'                           => $this->ReadPropertyString('Metric') === 'consumption'
                    ? 'The ThemeRiver archive values are invalid. Only nonnegative, finite interval consumption is supported.'
                    : 'The ThemeRiver archive values are invalid. Only nonnegative, finite interval means are supported.',
                default                   => 'The ThemeRiver values could not be loaded.'
            });
        }
        return ['schemaVersion' => 1, 'family' => 'themeRiver', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'themeRiver', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The ThemeRiver values could not be loaded.');
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
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['BorderType' => ['solid', 'dashed', 'dotted'], 'AxisLineType' => ['solid', 'dashed', 'dotted'],
            'GridLineType'     => ['solid', 'dashed', 'dotted'], 'EmphasisFocus' => ['self', 'series', 'none'],
            'TimeLabelFormat'  => ['auto', 'date', 'dateTime'], 'LegendPosition' => ['top', 'bottom'],
            'LegendAlignment'  => ['left', 'center', 'right'], 'LegendIcon' => ['roundRect', 'rect', 'circle', 'diamond'],
            'FontFamily'       => ['Arial, sans-serif', 'Verdana, sans-serif', 'Georgia, serif', 'monospace']] as $name => $allowed) {
            if (!in_array($this->ReadPropertyString($prefix . $name), $allowed, true)) {
                return false;
            }
        }
        foreach (['BandOpacityPercent' => [0, 100], 'BorderWidth' => [0, 5],
            'TitleFontSizePercent'     => [50, 200], 'AxisFontSizePercent' => [50, 200],
            'LabelFontSizePercent'     => [50, 200], 'LegendFontSizePercent' => [50, 200],
            'BandHeightPercent'        => [20, 100], 'ShadowBlur' => [0, 20], 'ShadowOffsetX' => [-20, 20], 'ShadowOffsetY' => [-20, 20],
            'BlurOpacityPercent'       => [0, 100], 'AxisLineWidth' => [0, 5], 'AxisLabelMargin' => [0, 30],
            'GridLineWidth'            => [0, 5], 'GridOpacityPercent' => [0, 100],
            'LegendItemWidth'          => [6, 40], 'LegendItemHeight' => [6, 30], 'LegendItemGap' => [0, 30]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'BorderColor', 'LabelColor', 'LegendColor', 'ShadowColor', 'GridColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
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
            '#echarts-theme-river-root',
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
            throw new RuntimeException('The ThemeRiver Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECRV_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }
}
