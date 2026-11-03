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
use SymconECharts\CalendarPieData;
use SymconECharts\EChartsAnimationDesign;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsBarPreview;
use SymconECharts\EChartsBarPreviewForm;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\StateTimelineData;

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
require_once __DIR__ . '/CalendarPieData.php';
require_once __DIR__ . '/CalendarPiePreview.php';

class EChartsCalendarPie extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use SymconECharts\CalendarPiePreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewCalendarPie';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_CONFIGURATION_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => 'auto', 'ShowTooltip' => true, 'ShowLegend' => true,
        'ShowDayNumbers'       => true, 'ShowValues' => false, 'FirstWeekday' => 1,
        'PieRadiusPercent'     => 70, 'InnerRadiusPercent' => 0, 'StartAngle' => 90, 'Clockwise' => true,
        'SliceBorderWidth'     => 1, 'SliceBorderColor' => -1, 'CellBorderWidth' => 1, 'CellBorderColor' => -1,
        'CellOpacityPercent'   => 100, 'EmptyCellColor' => -1, 'UnknownColor' => 0x777B83,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100, 'ValueFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'ValueColor' => -1
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
            $this->RegisterTimer('ArchiveRefresh', 0, 'ECCP_RefreshArchive($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECCP');
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('DataMode', 'average');
            $this->RegisterPropertyBoolean('NormalizeValues', false);
            $this->RegisterPropertyBoolean('ShowIncompleteDays', false);
            $this->RegisterPropertyString('Month', '');
            $this->RegisterPropertyInteger('StateVariableID', 0);
            $this->RegisterPropertyString('States', '[]');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('CalendarPie for IPSView'), 90);
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
            $this->SetDataModeVisibility($form['elements'], $this->ReadPropertyString('DataMode'));
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECCP');
            $form = $this->WithIPSViewDesignFormState($form, 'ECCP');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Animation', 'start' => 'AnimationEnabled'],
                ['caption' => 'Calendar and circles', 'start' => 'ShowTooltip'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = ['Title', 'Sources', 'DataMode', 'NormalizeValues', 'ShowIncompleteDays', 'Month', 'StateVariableID', 'States',
                'UseVariablePresentation', 'Unit', 'Decimals',
                'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
                'IPSViewBackgroundOpacityPercent', ...array_keys(self::DESIGN_DEFAULTS),
                ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))];
            foreach (['', 'IPSView'] as $prefix) {
                foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                    'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                    $fields[] = $prefix . $name;
                }
            }
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECCP', $fields));
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
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

    public function GetCalendarPieData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetCalendarPieDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetCalendarPieDiagnostic(): string
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
            return $debugFlowResult = $this->RenderCalendarPieHTMLPage(false);
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
            return $debugFlowResult = $this->RenderCalendarPieHTMLPage(true);
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

    public function UpdateDataMode(string $Mode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            foreach (['Sources', 'NormalizeValues', 'NormalizationHint', 'ShowIncompleteDays', 'IncompleteDaysHint', 'UseVariablePresentation', 'Unit', 'Decimals'] as $name) {
                $this->UpdateFormField($name, 'visible', $Mode !== 'states');
            }
            foreach (['StateVariableID', 'States'] as $name) {
                $this->UpdateFormField($name, 'visible', $Mode === 'states');
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateNormalizationForm(bool $NormalizeValues): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            foreach (['UseVariablePresentation', 'Unit', 'Decimals'] as $name) {
                $this->UpdateFormField($name, 'enabled', !$NormalizeValues);
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
        return $this->CalendarPiePreviewSvg($values, $ipsView);
    }

    private function GetCalendarPieDataForOutput(bool $ipsView, int $windowOffset = 0): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->DebugEChartsSetStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }
        try {
            $window = CalendarPieData::Resolve(
                $this->ReadPropertyString('Month'),
                $windowOffset,
                $this->CurrentTimestamp(),
                date_default_timezone_get()
            );
            $mode = $this->ReadPropertyString('DataMode');
            $sources = $this->GetValidatedSources();
            $categories = [];
            if ($mode === 'states') {
                $source = $sources[0];
                $archive = $this->ReadArchiveSource($source, $window);
                $days = CalendarPieData::StateDays(
                    $archive['Points'],
                    $archive['PreviousPoint'],
                    $source['VariableType'] === 0,
                    $source['States'],
                    $window
                );
                foreach ($source['States'] as $value => $definition) {
                    $categories['state-' . $value] = ['id' => 'state-' . $value,
                        'label'                            => $definition['Label'],
                        'color'                            => $definition['Color'] < 0 ? '' : EChartsAsset::ColorToHex($definition['Color'])];
                }
                foreach ($days as &$day) {
                    foreach ($day['slices'] as &$slice) {
                        if ($slice['id'] === 'unknown') {
                            $slice['label'] = $this->Translate('Unknown');
                        }
                        $categories[$slice['id']] ??= ['id' => $slice['id'], 'label' => $slice['label'], 'color' => $slice['color']];
                    }
                    unset($slice);
                }
                unset($day);
                $unit = 'h';
                $decimals = 1;
            } else {
                $points = [];
                foreach ($sources as $source) {
                    $archive = $this->ReadArchiveSource($source, $window);
                    $points[$source['VariableID']] = $archive['Points'];
                    $categories[] = ['id' => 'variable-' . $source['VariableID'], 'label' => $source['Label'], 'color' => $source['Color']];
                }
                $days = CalendarPieData::NumericDays($sources, $points, $window, $this->ReadPropertyBoolean('NormalizeValues'));
                $unit = $sources[0]['Unit'];
                $decimals = max(array_column($sources, 'Decimals'));
            }
            $showIncompleteDays = $mode !== 'states' && $this->ReadPropertyBoolean('ShowIncompleteDays');
            foreach ($days as &$day) {
                $day['showPie'] = CalendarPieData::ShowsPie($day, $mode, $showIncompleteDays);
            }
            unset($day);
            $encoded = $this->EncodeVisualizationHTMLJSON([
                'schemaVersion' => 1, 'family' => 'calendarPie', 'theme' => $this->EffectiveTheme($ipsView),
                'calendarPie'   => ['title' => $this->ReadPropertyString('Title'), 'mode' => $mode,
                    'normalized'            => $mode !== 'states' && $this->ReadPropertyBoolean('NormalizeValues'),
                    'showIncompleteDays'    => $showIncompleteDays,
                    'month'                 => $window['Month'], 'baseMonth' => $window['BaseMonth'], 'windowOffset' => $windowOffset,
                    'maxOffset'             => $window['MaximumOffset'], 'timezone' => $window['Timezone'],
                    'startTimestamp'        => $window['StartTimestamp'], 'endTimestamp' => $window['EndTimestamp'],
                    'unit'                  => $unit, 'decimals' => $decimals, 'categories' => array_values($categories),
                    'days'                  => $days, 'style' => $this->ReadDesignStyle($ipsView)]
            ]);
            if (strlen($encoded) > 250000) {
                $this->WriteAttributeString('LastError', 'ARCHIVE_TRUNCATED');
                throw new RuntimeException('The CalendarPie data exceeds the output limit.');
            }
        } catch (Throwable $exception) {
            if ($this->ReadAttributeString('LastError') === '') {
                $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
            }
            $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        return $encoded;
    }

    private function RenderCalendarPieHTMLPage(bool $ipsView): string
    {
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'EChartsCalendarPie',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-calendar-pie-root', 'echarts-calendar-pie'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => $this->RendererTranslations(),
            'options'      => [
                'echartsVersion'       => EChartsAsset::VERSION, 'echartsThemes' => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground'    => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'calendarPieStatePath' => $this->EChartsIPSViewTransportOptions()['statePath'],
                'ipsViewTransport'     => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::CalendarPieJavaScript(),
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
        $this->SetSummary($this->ReadPropertyString('DataMode'));
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $this->SetTimerInterval('ArchiveRefresh', 60000);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        if (!in_array($this->ReadPropertyString('DataMode'), ['average', 'counter', 'states'], true)) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The CalendarPie settings are invalid.'];
        }
        try {
            CalendarPieData::Resolve($this->ReadPropertyString('Month'), 0, $this->CurrentTimestamp(), date_default_timezone_get());
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => $exception->getMessage()];
        }
        if (mb_strlen($this->ReadPropertyString('Title')) > 200 || !$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )
            || ($this->ReadPropertyString('DataMode') !== 'states' && !$this->ReadPropertyBoolean('NormalizeValues')
                && ($this->ReadPropertyInteger('Decimals') < 0 || $this->ReadPropertyInteger('Decimals') > 6
                    || mb_strlen($this->ReadPropertyString('Unit')) > 24))) {
            return ['Status' => self::STATUS_CONFIGURATION_INVALID, 'Message' => 'The CalendarPie settings are invalid.'];
        }
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        return null;
    }

    /** Active-mode source identities only; inactive source configuration remains untouched. */
    private function GetValidatedSources(): array
    {
        if ($this->ReadPropertyString('DataMode') === 'states') {
            $id = $this->ReadPropertyInteger('StateVariableID');
            if ($id <= 0 || !IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [0, 1], true)) {
                throw new InvalidArgumentException('Configure a Boolean or Integer CalendarPie archive variable.');
            }
            $json = $this->ReadPropertyString('States');
            if (strlen($json) > 32768) {
                throw new InvalidArgumentException('The CalendarPie state definitions are too large.');
            }
            $raw = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
            return [['VariableID' => $id, 'VariableType' => IPS_GetVariable($id)['VariableType'],
                'Label'           => IPS_GetName($id), 'States' => StateTimelineData::Definitions($raw, IPS_GetVariable($id)['VariableType'] === 0)]];
        }
        $json = $this->ReadPropertyString('Sources');
        if (strlen($json) > 65536) {
            throw new InvalidArgumentException('The CalendarPie sources are too large.');
        }
        $raw = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > 5) {
            throw new InvalidArgumentException('Configure one to five numeric CalendarPie archive variables.');
        }
        $sources = [];
        $seen = [];
        $unit = null;
        $normalize = $this->ReadPropertyBoolean('NormalizeValues');
        foreach ($raw as $entry) {
            $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
            $label = is_array($entry) ? ($entry['Label'] ?? '') : null;
            $color = is_array($entry) ? ($entry['Color'] ?? -1) : null;
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)
                || !is_string($label) || mb_strlen(trim($label)) > 120
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException('CalendarPie sources need distinct numeric variable IDs, valid labels and colors.');
            }
            if ($normalize) {
                [$minimum, $maximum] = CalendarPieData::NormalizationRange($entry);
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $id,
                0.0,
                100.0,
                $normalize ? '' : $this->ReadPropertyString('Unit'),
                $normalize ? 1 : $this->ReadPropertyInteger('Decimals'),
                $normalize || $this->ReadPropertyBoolean('UseVariablePresentation')
            );
            if (!$normalize && $unit !== null && $unit !== $presentation['unit']) {
                throw new InvalidArgumentException('All CalendarPie sources must use the same unit.');
            }
            $unit = $presentation['unit'];
            $sources[] = ['VariableID' => $id, 'VariableType' => IPS_GetVariable($id)['VariableType'],
                'Label'                => trim($label), 'Color' => $color < 0 ? '' : EChartsAsset::ColorToHex($color),
                'Unit'                 => $unit, 'Decimals' => $presentation['decimals']];
            if ($normalize) {
                $sources[array_key_last($sources)] += ['Minimum' => $minimum, 'Maximum' => $maximum];
            }
            $seen[$id] = true;
        }
        $labels = EChartsSourceIdentity::LabelsForSources($sources);
        foreach ($sources as &$source) {
            $source['Label'] = $labels[$source['VariableID']];
            if (trim($source['Label']) === '') {
                $source['Label'] = $this->Translate('Unnamed source');
            }
        }
        unset($source);
        return $sources;
    }

    private function ReadArchiveSource(array $source, array $query): array
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $states = $this->ReadPropertyString('DataMode') === 'states';
            if ($query['Empty']) {
                return $debugFlowResult = ['Points' => [], 'PreviousPoint' => null];
            }
            $reducer = $this->ReadPropertyString('DataMode') === 'counter' ? 'sum' : 'average';
            $operation = $states ? EChartsDataProtocol::OPERATION_ARCHIVE_STATES_READ : EChartsDataProtocol::OPERATION_ARCHIVE_READ;
            $payload = ['VariableID' => $source['VariableID'], 'StartTimestamp' => $query['StartTimestamp'],
                'EndTimestamp'       => $query['EndTimestamp'] - ($states ? 0 : 1), 'Limit' => $states ? 2000 : 31];
            if (!$states) {
                $payload += ['Mode' => 'aggregated', 'Reducer' => $reducer, 'AggregationLevel' => 1];
            }
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
                throw new RuntimeException('The gateway returned an invalid CalendarPie archive response.', 0, $exception);
            }
            if (!$response['Success']) {
                $code = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
                $this->WriteAttributeString('LastError', $code);
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway archive request failed: ' . $code);
            }
            $archive = $response['Payload'];
            if (($archive['Truncated'] ?? null) === true) {
                $this->WriteAttributeString('LastError', 'ARCHIVE_TRUNCATED');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('Too many state changes for this month.');
            }
            $invalid = ($archive['VariableID'] ?? null) !== $source['VariableID']
                || !is_array($archive['Points'] ?? null) || ($archive['Truncated'] ?? null) !== false
                || ($archive['StartTimestamp'] ?? null) !== $payload['StartTimestamp']
                || ($archive['EndTimestamp'] ?? null) !== $payload['EndTimestamp']
                || ($archive['VariableType'] ?? null) !== $source['VariableType']
                || ($archive['ArchiveAggregationType'] ?? null) !== ($reducer === 'sum' ? 'counter' : 'standard');
            if ($states) {
                $invalid = $invalid || !array_key_exists('PreviousPoint', $archive)
                    || (!is_array($archive['PreviousPoint']) && $archive['PreviousPoint'] !== null);
            } else {
                $invalid = $invalid || ($archive['EffectiveReducer'] ?? null) !== $reducer;
            }
            if ($invalid) {
                $this->WriteAttributeString('LastError', 'INVALID_ARCHIVE_DATA');
                $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                throw new RuntimeException('The gateway returned invalid CalendarPie archive data.');
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
        $current = [];
        if ($this->ReadPropertyString('DataMode') === 'states') {
            $current = [$this->ReadPropertyInteger('StateVariableID')];
        } elseif (in_array($this->ReadPropertyString('DataMode'), ['average', 'counter'], true)) {
            $raw = json_decode($this->ReadPropertyString('Sources'), true);
            foreach (is_array($raw) ? $raw : [] as $entry) {
                $id = is_array($entry) ? ($entry['VariableID'] ?? null) : null;
                if (is_int($id)) {
                    $current[] = $id;
                }
            }
        }
        $current = array_values(array_unique(array_filter($current, static fn (int $id): bool => $id > 0 && IPS_VariableExists($id))));
        foreach (array_diff($previous, $current) as $id) {
            $this->UnregisterReference($id);
        }
        foreach (array_diff($current, $previous) as $id) {
            $this->RegisterReference($id);
        }
        $this->WriteAttributeString('RegisteredSourceVariableIDs', json_encode($current, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false, int $windowOffset = 0): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->DebugEChartsSetStatus($configurationError['Status']);
            return $this->ErrorState('Configure valid CalendarPie archive variables.');
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetCalendarPieDataForOutput($ipsView, $windowOffset), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState(match ($this->ReadAttributeString('LastError')) {
                'ARCHIVE_TRUNCATED'     => 'Too many state changes for this month.',
                'INVALID_ARCHIVE_DATA'  => 'The CalendarPie archive data is invalid.',
                default                 => 'The CalendarPie values could not be loaded.'
            });
        }
        return ['schemaVersion' => 1, 'family' => 'calendarPie', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'calendarPie', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The CalendarPie values could not be loaded.');
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
        foreach (['FirstWeekday'  => [0, 6], 'PieRadiusPercent' => [20, 90], 'InnerRadiusPercent' => [0, 80],
            'StartAngle'          => [0, 360], 'SliceBorderWidth' => [0, 5], 'CellBorderWidth' => [0, 4],
            'CellOpacityPercent'  => [0, 100], 'TitleFontSizePercent' => [50, 200],
            'AxisFontSizePercent' => [50, 200], 'ValueFontSizePercent' => [50, 200]] as $name => [$min, $max]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $min || $value > $max) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'ValueColor', 'SliceBorderColor', 'CellBorderColor', 'EmptyCellColor', 'UnknownColor'] as $name) {
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
            '#echarts-calendar-pie-root',
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
            throw new RuntimeException('The CalendarPie Tile designer section is missing.');
        }
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECCP_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'        => 'ExpansionPanel', 'caption' => 'Independent IPSView designer',
                    'expanded' => false, 'items' => $rows]
            ]];
    }

    /** @return array<string,string> */
    private function RendererTranslations(): array
    {
        $keys = ['Configure valid CalendarPie archive variables.', 'Connect an active EChartsGateway.',
            'The CalendarPie values could not be loaded.', 'The CalendarPie archive data is invalid.',
            'Too many state changes for this month.', 'Data could not be refreshed. Showing the last available values.',
            'Apache ECharts could not be initialized.', 'Unknown', 'Previous month', 'Next month', 'Selected month',
            'Daily means', 'Daily consumption', 'State duration', 'No positive values', 'Incomplete data',
            'Normalized comparison (0–100)', 'Normalized', 'Range', 'Circle share',
            'Shares refer only to available sources.', 'Incomplete days are marked ◆. Shares refer only to available sources.',
            'Missing data', 'Not completed', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return array_combine($keys, array_map(fn (string $key): string => $this->Translate($key), $keys));
    }

    private function SetDataModeVisibility(array &$elements, string $mode): void
    {
        foreach ($elements as &$element) {
            if (in_array($element['name'] ?? null, ['Sources', 'NormalizeValues', 'NormalizationHint', 'ShowIncompleteDays', 'IncompleteDaysHint', 'UseVariablePresentation', 'Unit', 'Decimals'], true)) {
                $element['visible'] = $mode !== 'states';
                if (in_array($element['name'] ?? null, ['UseVariablePresentation', 'Unit', 'Decimals'], true)) {
                    $element['enabled'] = !$this->ReadPropertyBoolean('NormalizeValues');
                }
            } elseif (in_array($element['name'] ?? null, ['StateVariableID', 'States'], true)) {
                $element['visible'] = $mode === 'states';
            }
            if (is_array($element['items'] ?? null)) {
                $this->SetDataModeVisibility($element['items'], $mode);
            }
        }
        unset($element);
    }
}

