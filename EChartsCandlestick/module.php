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
use SymconECharts\CandlestickData;
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
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsStartupState;
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
require_once __DIR__ . '/../libs/EChartsStartupState.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/CandlestickData.php';
require_once __DIR__ . '/CandlestickPreview.php';

class EChartsCandlestick extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use SymconECharts\CandlestickPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewCandlestick';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'             => 'auto', 'Orientation' => 'vertical', 'ShowTooltip' => true,
        'ShowGrid'                 => true, 'ShowAxisLabels' => true, 'CandleWidth' => 40, 'BorderWidth' => 2, 'CandleOpacityPercent' => 70,
        'TitleFontSizePercent'     => 100, 'AxisFontSizePercent' => 100, 'TitleColor' => -1, 'AxisColor' => -1, 'BorderColor' => -1,
        'RiseColor'                => 0x3FBE95, 'FallColor' => 0xE66B6B, 'DojiColor' => -1,
        'MinCandleWidth'           => 1, 'BorderType' => 'solid', 'ShadowBlur' => 0, 'ShadowColor' => -1, 'ShadowOffsetX' => 0, 'ShadowOffsetY' => 0,
        'HoverFocus'               => 'self', 'BlurOpacityPercent' => 10, 'AxisLineWidth' => 1, 'ShowAxisTicks' => true,
        'AxisLabelRotate'          => 0, 'AxisLabelMaxWidth' => 0, 'TitleBold' => true, 'AxisLabelBold' => false,
        'SplitNumber'              => 5, 'GridLineType' => 'solid', 'GridLineWidth' => 1, 'GridOpacityPercent' => 25, 'GridColor' => -1,
        'FixedCandleWidth'         => 0, 'RiseHollow' => false, 'FallHollow' => false, 'RiseBorderColor' => -1, 'FallBorderColor' => -1,
        'HoverBorderWidth'         => 2, 'HoverBorderColor' => -1, 'HoverOpacityPercent' => -1,
        'AxisLineColor'            => -1, 'AxisLineType' => 'solid', 'AxisLabelMargin' => 8,
        'ReverseTimeAxis'          => false, 'IncludeZero' => false, 'FontFamily' => 'Arial, sans-serif',
        'TitleItalic'              => false, 'AxisLabelItalic' => false, 'EnableZoom' => false
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
            $this->RegisterTimer('ArchiveRefresh', 0, 'ECCA_RefreshArchive($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECCA');
            $this->RegisterPropertyInteger('VariableID', 0);
            $this->RegisterPropertyBoolean('UseComparisonPanels', false);
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Interval', 'day');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Range', '7d');
            $this->RegisterPropertyInteger('CustomRangeValue', 7);
            $this->RegisterPropertyString('CustomRangeUnit', 'day');
            $this->RegisterPropertyString('AbsoluteStart', '{}');
            $this->RegisterPropertyString('AbsoluteEnd', '{}');
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
            $this->SetBuffer('CandlestickCache', '');
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Candlestick for IPSView'), 90);
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
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECCA');
            $form = $this->WithIPSViewDesignFormState($form, 'ECCA');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
                ['caption' => 'Layout and candles', 'start' => 'Orientation'],
                ['caption' => 'Shadows and highlighting', 'start' => 'ShadowBlur'],
                ['caption' => 'Axes and grid', 'start' => 'ShowGrid'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = ['Title', 'VariableID', 'UseComparisonPanels', 'Sources', 'Range', 'CustomRangeValue', 'CustomRangeUnit', 'AbsoluteStart', 'AbsoluteEnd', 'Interval',
                'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
                'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
                ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
            foreach (['', 'IPSView'] as $prefix) {
                foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                    'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                    $fields[] = $prefix . $name;
                }
            }
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECCA', $fields));
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

    public function GetCandlestickData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetCandlestickDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetCandlestickDiagnostic(): string
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
            return $debugFlowResult = $this->RenderCandlestickHTMLPage(false);
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
            return $debugFlowResult = $this->RenderCandlestickHTMLPage(true);
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
        return $this->CandlestickPreviewSvg($values, $ipsView);
    }

    private function GetCandlestickDataForOutput(bool $ipsView): string
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
        $range = ['key'    => $this->ReadPropertyString('Range'), 'startTimestamp' => $query['StartTimestamp'],
            'endTimestamp' => $query['EndTimestamp'], 'timezone' => date_default_timezone_get()];
        if ($range['key'] === 'absolute') {
            $range += ['requestedStartTimestamp' => $query['RequestedStartTimestamp'],
                'requestedEndTimestamp'          => $query['RequestedEndTimestamp']];
        }
        $key = 'panels:' . implode(',', array_column($sources, 'VariableID')) . ':' . $query['StartTimestamp'] . ':' . $query['EndTimestamp'] . ':' . $this->ReadPropertyString('Interval');
        $cache = json_decode($this->GetBuffer('CandlestickCache') ?: '{}', true);
        try {
            if (($cache['key'] ?? null) === $key && ($cache['expires'] ?? 0) > $this->CurrentTimestamp()) {
                $data = $cache['data'];
            } else {
                $points = $query['Empty'] ? [] : CandlestickData::ReadMany(
                    array_column($sources, 'VariableID'),
                    $query['StartTimestamp'],
                    $query['EndTimestamp'],
                    fn (array $request): array => $this->ReadArchiveSource($request)
                );
                $data = [];
                foreach ($sources as $source) {
                    $data[$source['VariableID']] = CandlestickData::Summarize($points[$source['VariableID']] ?? [], $query);
                }
                $this->SetBuffer('CandlestickCache', json_encode(['key' => $key, 'expires' => $this->CurrentTimestamp() + 60,
                    'data'                                              => $data], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
            }
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
            throw $exception;
        }
        $panels = [];
        foreach ($sources as $source) {
            $items = $data[$source['VariableID']];
            foreach ($items as &$item) {
                $item['unit'] = $source['Unit'];
                $item['decimals'] = $source['Decimals'];
            }
            unset($item);
            $panels[] = ['id' => (string) $source['VariableID'], 'variableID' => $source['VariableID'],
                'sourceLabel' => $source['Label'], 'unit' => $source['Unit'], 'data' => $items];
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        return json_encode(
            ['schemaVersion'     => 1, 'family' => 'candlestick', 'theme' => $this->EffectiveTheme($ipsView),
                'candlestick'    => ['title' => $this->ReadPropertyString('Title'), 'unit' => $sources[0]['Unit'],
                    'sampling'               => 'completed-raw-intervals', 'sourceLabel' => $sources[0]['Label'], 'variableID' => $sources[0]['VariableID'], 'interval' => $this->ReadPropertyString('Interval'),
                    'data'                   => $this->ReadPropertyBoolean('UseComparisonPanels') ? [] : $panels[0]['data'],
                    ...($this->ReadPropertyBoolean('UseComparisonPanels') ? ['panels' => $panels] : []),
                    'style' => $this->ReadDesignStyle($ipsView)],
                'range' => $range],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    private function RenderCandlestickHTMLPage(bool $ipsView): string
    {
        $startup = EChartsStartupState::Startup($this->BuildVisualizationState($ipsView));
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Candlestick',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-candlestick-root', 'echarts-candlestick'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $startup['state'],
            'translations' => [
                'Too many archive intervals. Choose a shorter range or a longer interval.'  => $this->Translate('Too many archive intervals. Choose a shorter range or a longer interval.'),
                'The archive interval settings are invalid.'                                => $this->Translate('The archive interval settings are invalid.'),
                'Configure valid Candlestick archive variables.'                            => $this->Translate('Configure valid Candlestick archive variables.'),
                'Connect an active EChartsGateway.'                                         => $this->Translate('Connect an active EChartsGateway.'),
                'The Candlestick values could not be loaded.'                               => $this->Translate('The Candlestick values could not be loaded.'),
                'The archive is too dense. Choose a shorter time range.'                    => $this->Translate('The archive is too dense. Choose a shorter time range.'),
                'Candlestick requires standard archive variables, not counters.'            => $this->Translate('Candlestick requires standard archive variables, not counters.'),
                'Logged samples'                                                            => $this->Translate('Logged samples'),
                'Minimum'                                                                   => $this->Translate('Minimum'), 'First value' => $this->Translate('First value'), 'Last value' => $this->Translate('Last value'),
                'Maximum'                                                                   => $this->Translate('Maximum'),
                'No logged values in completed intervals.'                                  => $this->Translate('No logged values in completed intervals.'),
                'Actual logged samples in completed intervals'                              => $this->Translate('Actual logged samples in completed intervals'),
                'Data could not be refreshed. Showing the last available values.'           => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground'         => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'          => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'compressedStateGzipBase64' => $startup['compressedStateGzipBase64']
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::CandlestickJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::StartupJavaScript(),
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
        $this->SetSummary($this->ReadPropertyString('Range') . ' / ' . $this->ReadPropertyString('Interval'));
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (mb_strlen($this->ReadPropertyString('Title')) > 200
            || !in_array($this->ReadPropertyString('Range'), ['24h', '7d', '30d', 'custom', 'absolute'], true)
            || !in_array($this->ReadPropertyString('Interval'), EChartsArchiveQuery::RAW_INTERVALS, true)
            || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The Candlestick settings are invalid.'];
        }
        try {
            $this->ResolveTimeQuery();
        } catch (InvalidArgumentException $exception) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $this->ReadPropertyString('Range') === 'absolute'
                ? $this->Translate($exception->getMessage()) : $exception->getMessage()];
        }
        return null;
    }

    /** Pure shared planning for saved settings and read-only preview drafts. */
    private function ResolveTimeQuery(array $values = []): array
    {
        $range = $values['Range'] ?? $this->ReadPropertyString('Range');
        $interval = $values['Interval'] ?? $this->ReadPropertyString('Interval');
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
        return CandlestickData::Window(
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
        $raw = $this->ReadPropertyBoolean('UseComparisonPanels')
            ? (strlen($this->ReadPropertyString('Sources')) <= 65536
                ? json_decode($this->ReadPropertyString('Sources'), true, 32, JSON_THROW_ON_ERROR) : null)
            : [['VariableID' => $this->ReadPropertyInteger('VariableID'), 'Label' => '']];
        return $this->ValidatePanelSources($raw);
    }

    /** Same ID/label contract for saved sources and archive-free preview drafts. */
    private function ValidatePanelSources(mixed $raw): array
    {
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > 4
            || strlen(json_encode($raw, JSON_THROW_ON_ERROR)) > 65536) {
            throw new InvalidArgumentException('Configure one to four unique numeric Candlestick panel variables.');
        }
        $sources = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('The Candlestick panel settings are invalid.');
            }
            $id = $row['VariableID'] ?? null;
            $label = $row['Label'] ?? '';
            if (!is_string($label) || mb_strlen($label) > 120 || !is_int($id)) {
                throw new InvalidArgumentException('The Candlestick panel settings are invalid.');
            }
            if ($id <= 0 || !IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Configure a numeric Candlestick archive variable.');
            }
            $presentation = EChartsVariablePresentation::Resolve($id, 0.0, 100.0, '', 2, true);
            $sources[] = ['VariableID' => $id, 'Label' => trim($label), 'Color' => -1, 'Unit' => $presentation['unit'], 'Decimals' => $presentation['decimals']];
        }
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        foreach ($sources as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
        }
        unset($source);
        return $sources;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function ReadArchiveSource(array $payload): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $operation = EChartsDataProtocol::OPERATION_ARCHIVE_READ;
            $response = EChartsDataProtocol::DecodeResponse(
                $this->DebugEChartsSendToParent($this->EncodeDataFlowMessage(
                    self::DATA_ID_TO_PARENT,
                    EChartsDataProtocol::CreateRequest($operation, $payload)
                )),
                $operation
            );
            if (!$response['Success']) {
                throw new RuntimeException('The gateway archive request failed: ' . ($response['Error']['Code'] ?? 'UNKNOWN'));
            }
            return $debugFlowResult = $response['Payload'];
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
        $raw = $this->ReadPropertyBoolean('UseComparisonPanels') ? (strlen($this->ReadPropertyString('Sources')) <= 65536
            ? json_decode($this->ReadPropertyString('Sources'), true) : null)
            : [['VariableID' => $this->ReadPropertyInteger('VariableID')]];
        $current = [];
        foreach (is_array($raw) ? array_slice($raw, 0, 4) : [] as $row) {
            $id = $row['VariableID'] ?? null;
            if (is_int($id) && $id > 0 && IPS_VariableExists($id) && !in_array($id, $current, true)) {
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

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            if ($this->ReadPropertyString('Range') === 'absolute' && $configurationError['Status'] === self::STATUS_CONFIGURATION_INVALID) {
                return $this->ErrorState($configurationError['Message']);
            }
            return $this->ErrorState(in_array($configurationError['Message'], [
                'Too many archive intervals. Choose a shorter range or a longer interval.', 'The archive interval settings are invalid.'
            ], true) ? $configurationError['Message'] : 'Configure valid Candlestick archive variables.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetCandlestickDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            if (str_contains($this->ReadAttributeString('LastError'), 'too dense')) {
                return $this->ErrorState('The archive is too dense. Choose a shorter time range.');
            }
            return $this->ErrorState($this->ReadAttributeString('LastError') === 'Candlestick requires standard archive variables, not counters.'
                ? 'Candlestick requires standard archive variables, not counters.' : 'The Candlestick values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'candlestick', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'candlestick', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Candlestick values could not be loaded.');
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
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme')) || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'Orientation'), ['vertical', 'horizontal'], true)
            || !in_array($this->ReadPropertyString($prefix . 'BorderType'), ['solid', 'dashed', 'dotted'], true)
            || !in_array($this->ReadPropertyString($prefix . 'GridLineType'), ['solid', 'dashed', 'dotted'], true)
            || !in_array($this->ReadPropertyString($prefix . 'AxisLineType'), ['solid', 'dashed', 'dotted'], true)
            || !in_array($this->ReadPropertyString($prefix . 'FontFamily'), ['Arial, sans-serif', 'Verdana, sans-serif', 'Georgia, serif', 'monospace'], true)
            || !in_array($this->ReadPropertyString($prefix . 'HoverFocus'), ['self', 'none'], true)
            || $this->ReadPropertyInteger($prefix . 'MinCandleWidth') > $this->ReadPropertyInteger($prefix . 'CandleWidth')) {
            return false;
        }
        $fixedWidth = $this->ReadPropertyInteger($prefix . 'FixedCandleWidth');
        if ($fixedWidth !== 0 && ($fixedWidth < $this->ReadPropertyInteger($prefix . 'MinCandleWidth')
            || $fixedWidth > $this->ReadPropertyInteger($prefix . 'CandleWidth'))) {
            return false;
        }
        foreach (['CandleWidth'       => [8, 80], 'BorderWidth' => [0, 8], 'CandleOpacityPercent' => [0, 100],
            'FixedCandleWidth'        => [0, 80], 'HoverBorderWidth' => [0, 8], 'HoverOpacityPercent' => [-1, 100], 'AxisLabelMargin' => [0, 30],
            'TitleFontSizePercent'    => [50, 200], 'AxisFontSizePercent' => [50, 200],
            'MinCandleWidth'          => [1, 80], 'ShadowBlur' => [0, 20], 'ShadowOffsetX' => [-20, 20], 'ShadowOffsetY' => [-20, 20],
            'BlurOpacityPercent'      => [0, 100], 'AxisLineWidth' => [0, 5], 'AxisLabelRotate' => [-90, 90],
            'AxisLabelMaxWidth'       => [0, 300], 'SplitNumber' => [2, 10], 'GridLineWidth' => [0, 5], 'GridOpacityPercent' => [0, 100]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['RiseBorderColor', 'FallBorderColor', 'HoverBorderColor', 'AxisLineColor',
            'RiseColor', 'FallColor', 'DojiColor', 'TitleColor', 'AxisColor', 'BorderColor', 'ShadowColor', 'GridColor'] as $name) {
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
            '#echarts-candlestick-root',
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
            throw new RuntimeException('The Candlestick Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECCA_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }
}
