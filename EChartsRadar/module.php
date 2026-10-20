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
use SymconECharts\EChartsCurrentSources;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\RadarModel;
use SymconECharts\RadarPreview;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsPreviewSymbol.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/RadarModel.php';
require_once __DIR__ . '/RadarPreview.php';

class EChartsRadar extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm {
        UpdateBarPreviewFromForm as private UpdateSharedBarPreviewFromForm;
    }
    use RadarPreview;
    use EChartsCurrentSources;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewRadar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const MAXIMUM_MODEL_BYTES = 200000;
    private const MODEL_TOO_LARGE_MESSAGE = 'The Radar model is too large. Reduce the title or group metadata.';
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO, 'Shape' => 'polygon', 'StartAngle' => 90, 'Clockwise' => false,
        'ShowLegend'           => true, 'ShowTooltip' => true, 'ShowAxisNames' => true, 'ShowAxisValues' => false,
        'ShowSplitArea'        => true, 'SplitNumber' => 5, 'AxisNameGap' => 15,
        'LineWidth'            => 2, 'LineOpacityPercent' => 100, 'AreaOpacityPercent' => 25, 'SymbolSize' => 4,
        'TitleFontSizePercent' => 100, 'AxisFontSizePercent' => 100,
        'TitleColor'           => -1, 'AxisColor' => -1, 'GridColor' => -1,
        'GroupColors'          => '[]', 'LineType' => 'solid', 'Symbol' => 'circle', 'SymbolRotate' => 0,
        'SymbolBorderWidth'    => 0, 'SymbolBorderColor' => -1, 'SymbolOpacityPercent' => 100,
        'ShowAxisRange'        => true, 'ShowAxisLines' => true, 'AxisLineWidth' => 1, 'AxisLineOpacityPercent' => 50,
        'ShowSplitLines'       => true, 'SplitLineWidth' => 1, 'SplitLineType' => 'solid', 'SplitLineOpacityPercent' => 40,
        'SplitAreaColor1'      => -1, 'SplitAreaColor2' => -1, 'SplitAreaOpacityPercent' => 100,
        'ShowValues'           => false, 'ValueFontSizePercent' => 100, 'ValueColor' => -1,
        'ValueLabelPosition'   => 'top', 'ValueLabelDistance' => 5, 'ValueLabelBold' => false, 'HideOverlappingValueLabels' => true,
        'TitleBold'            => true, 'AxisLabelBold' => false, 'LegendFontSizePercent' => 100, 'LegendColor' => -1,
        'LegendPosition'       => 'bottom', 'HoverFocus' => 'self', 'AreaShadowBlur' => 0, 'AreaShadowColor' => -1
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
            $this->RegisterTransientRefresh('ECRA');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Axes', '[]');
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyBoolean('EnableGroupSelection', false);
            $this->RegisterPropertyString('GroupVisibility', '[]');
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
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Radar for IPSView'), 90);
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
            $connectionFields = $this->ConnectionFields(json_decode($this->ReadPropertyString('Axes'), true));
            foreach ($form['elements'] as &$element) {
                if (($element['name'] ?? '') === 'Sources') {
                    $element = $connectionFields;
                }
            }
            unset($element);
            $diagnostic = $this->GetConfigurationError()['Message'] ?? $this->ReadAttributeString('LastError');
            $form['elements'][] = ['type' => 'Label', 'name' => 'ConfigurationDiagnostics',
                'caption'                 => $diagnostic !== '' ? $diagnostic : $this->Translate('Radar configuration is valid.')];
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithGroupColorOptions($form['elements'], $this->GroupOptions(json_decode($this->ReadPropertyString('Sources'), true)));
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECRA');
            $form = $this->WithIPSViewDesignFormState($form, 'ECRA');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Layout and interaction', 'start' => 'Shape'],
                ['caption' => 'Axes and grid', 'start' => 'ShowAxisNames'],
                ['caption' => 'Lines and areas', 'start' => 'LineWidth'],
                ['caption' => 'Symbols and values', 'start' => 'Symbol'],
                ['caption' => 'Labels and typography', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = [
                'Title', 'Axes', 'Sources', 'EnableGroupSelection', 'GroupVisibility', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent',
                ...array_keys(self::DESIGN_DEFAULTS),
                ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))
            ];
            foreach (['', 'IPSView'] as $prefix) {
                foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                    'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                    $fields[] = $prefix . $name;
                }
            }
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECRA', $fields, true));
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

    public function GetRadarData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetRadarDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetRadarDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();
            return $debugFlowResult = json_encode([
                'valid'       => $error === null,
                'sourceCount' => count($this->ConfiguredVariableIDs()),
                'lastError'   => $error['Message'] ?? $this->ReadAttributeString('LastError')
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
            return $debugFlowResult = $this->RenderRadarHTMLPage(false);
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
            return $debugFlowResult = $this->RenderRadarHTMLPage(true);
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
                return;
            }
            if ($Message === VM_UPDATE && in_array($SenderID, $this->ConfiguredVariableIDs(), true)) {
                $this->PublishVisualizationState();
            }
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /** Updates draft diagnostics, axis selectors and both previews without saving. */
    public function UpdateBarPreviewFromForm(string $Configuration): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($values)) {
                    throw new InvalidArgumentException($this->Translate('The Radar configuration is not valid JSON.'));
                }
                if (isset($values['Axes']) && is_array($values['Axes'])) {
                    $this->UpdateAxisOptions(json_encode($values['Axes'], JSON_THROW_ON_ERROR));
                }
                $this->GetValidatedConfiguration($values);
                foreach (['GroupColors', 'IPSViewGroupColors'] as $name) {
                    if (array_key_exists($name, $values) && ($name === 'GroupColors'
                        || !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')))) {
                        $this->ParseGroupColors($values[$name]);
                    }
                }
                $message = $this->Translate('Radar configuration is valid.');
            } catch (Throwable $exception) {
                $message = $exception instanceof JsonException
                    ? $this->Translate('The Radar configuration is not valid JSON.') : $exception->getMessage();
            }
            $this->UpdateFormField('ConfigurationDiagnostics', 'caption', $message);
            if (isset($values['Sources']) && is_array($values['Sources'])) {
                $options = $this->GroupOptions($values['Sources']);
                foreach (['GroupColors', 'IPSViewGroupColors', 'GroupVisibility'] as $name) {
                    $field = $this->WithGroupColorOptions($this->LoadConfigurationForm()['elements'], $options);
                    $target = $name === 'IPSViewGroupColors' ? 'GroupColors' : $name;
                    $find = static function (array $items) use (&$find, $target): ?array
                    {
                        foreach ($items as $item) {
                            if (($item['name'] ?? '') === $target) {
                                return $item;
                            }
                            if (isset($item['items']) && ($found = $find($item['items'])) !== null) {
                                return $found;
                            }
                        }
                        return null;
                    };
                    $field = $find($field);
                    $this->UpdateFormField($name, 'columns', json_encode($field['columns'], JSON_THROW_ON_ERROR));
                    $this->UpdateFormField($name, 'form', json_encode($field['form'], JSON_THROW_ON_ERROR));
                }
            }
            $this->UpdateSharedBarPreviewFromForm($Configuration);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /** Updates assignment selectors after editing unsaved axes. */
    public function UpdateAxisOptions(string $JSONAxes): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $field = $this->ConnectionFields(json_decode($JSONAxes, true, 64, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('Sources', 'columns', json_encode($field['columns'], JSON_THROW_ON_ERROR));
                $this->UpdateFormField('Sources', 'form', json_encode($field['form'], JSON_THROW_ON_ERROR));
            } catch (Throwable) {
                // Incomplete list edits keep their last usable selector options.
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
        return $this->RadarPreviewSvg($values, $ipsView);
    }

    private function AxisOptions(mixed $axes): array
    {
        $options = [['caption' => $this->Translate('Select axis'), 'value' => '']];
        $seen = [];
        foreach (is_array($axes) ? array_slice($axes, 0, 10) : [] as $node) {
            if (!is_array($node) || !is_string($node['Key'] ?? null) || !is_string($node['Label'] ?? null)) {
                continue;
            }
            $key = trim($node['Key']);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[] = ['caption' => trim($node['Label']) . ' (' . $key . ')', 'value' => $key];
        }
        return $options;
    }

    private function ConnectionFields(mixed $axes): array
    {
        $options = $this->AxisOptions($axes);
        foreach ($this->LoadConfigurationForm()['elements'] as $field) {
            if (($field['name'] ?? '') !== 'Sources') {
                continue;
            }
            foreach ($field['columns'] as &$column) {
                if (in_array($column['name'], ['Axis'], true)) {
                    $column['edit']['options'] = $options;
                }
            }
            unset($column);
            foreach ($field['form'] as &$item) {
                if (in_array($item['name'], ['Axis'], true)) {
                    $item['options'] = $options;
                }
            }
            unset($item);
            return $field;
        }
        throw new RuntimeException('The Radar connection list is missing.');
    }

    private function GetRadarDataForOutput(bool $ipsView): string
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
        $configuration = $this->GetValidatedConfiguration();
        $sources = $configuration['sources'];
        $values = [];
        $this->WriteAttributeString('LastError', '');
        foreach (array_unique(array_column($sources, 'VariableID')) as $id) {
            $values[$id] = $this->ReadCurrentSource($id);
        }
        try {
            $model = RadarModel::Build($configuration['axes'], $sources, $values, fn (string $text): string => $this->Translate($text), $configuration['visibility']);
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->DebugEChartsSetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        $json = json_encode([
            'schemaVersion' => 1,
            'family'        => 'radar',
            'theme'         => $this->EffectiveTheme($ipsView),
            'radar'         => [
                'title'                => $this->ReadPropertyString('Title'),
                'enableGroupSelection' => $this->ReadPropertyBoolean('EnableGroupSelection'),
                'style'                => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if (strlen($json) > self::MAXIMUM_MODEL_BYTES) {
            $this->WriteAttributeString('LastError', 'MODEL_TOO_LARGE');
            $this->DebugEChartsSetStatus(self::STATUS_DESIGN_INVALID);
            throw new RuntimeException(self::MODEL_TOO_LARGE_MESSAGE);
        }
        return $json;
    }

    private function RenderRadarHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        $configuration = [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Radar',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-radar-root', 'echarts-radar'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Radar axes and comparison values.'                                                           => $this->Translate('Configure valid Radar axes and comparison values.'),
                'The Radar design is invalid.'                                                                                => $this->Translate('The Radar design is invalid.'),
                'Connect an active EChartsGateway.'                                                                           => $this->Translate('Connect an active EChartsGateway.'),
                'The Radar values could not be loaded.'                                                                       => $this->Translate('The Radar values could not be loaded.'),
                'Radar values must be finite numbers with timestamps.'                                                        => $this->Translate('Radar values must be finite numbers with timestamps.'),
                'Some values exceed their axis range and are visually limited.'                                               => $this->Translate('Some values exceed their axis range and are visually limited.'),
                'Apache ECharts could not be initialized.'                                                                    => $this->Translate('Apache ECharts could not be initialized.'),
                'All'                                                                                                         => $this->Translate('All'),
                'Inverse'                                                                                                     => $this->Translate('Inverse'),
                'No groups selected'                                                                                          => $this->Translate('No groups selected'),
                self::MODEL_TOO_LARGE_MESSAGE                                                                                 => $this->Translate(self::MODEL_TOO_LARGE_MESSAGE),
                'Data could not be refreshed. Showing the last available values.'                                             => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::RadarJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::SeriesSelectionJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ];
        $html = $this->RenderVisualizationHTMLPage($ipsView, $configuration);
        if (strlen($html) > 1048576) {
            $this->WriteAttributeString('LastError', 'MODEL_TOO_LARGE');
            $this->DebugEChartsSetStatus(self::STATUS_DESIGN_INVALID);
            $configuration['state'] = $this->ErrorState(self::MODEL_TOO_LARGE_MESSAGE);
            return $this->RenderVisualizationHTMLPage($ipsView, $configuration);
        }
        return $html;
    }

    private function Initialize(): void
    {
        $this->DebugEChartsSetStatus(IS_INACTIVE);
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
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => $this->Translate('The Radar design is invalid.')];
        }
        return null;
    }

    private function GetValidatedConfiguration(array $values = []): array
    {
        $axes = $this->DecodeRows($values['Axes'] ?? $this->ReadPropertyString('Axes'));
        $sources = $this->DecodeRows($values['Sources'] ?? $this->ReadPropertyString('Sources'));
        $configuration = RadarModel::Validate($axes, $sources, fn (string $text): string => $this->Translate($text));
        $configuration['visibility'] = $this->ParseGroupVisibility($values['GroupVisibility'] ?? $this->ReadPropertyString('GroupVisibility'));
        foreach ($configuration['sources'] as $index => $source) {
            $id = $source['VariableID'];
            if (!IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException(sprintf($this->Translate('Value %d: select an existing Integer or Float variable.'), $index + 1));
            }
        }
        return $configuration;
    }

    private function GetValidatedSources(): array
    {
        return $this->GetValidatedConfiguration()['sources'];
    }

    private function DecodeRows(mixed $raw): array
    {
        if (is_string($raw) && strlen($raw) > 65536) {
            throw new InvalidArgumentException($this->Translate('The Radar configuration is too large.'));
        }
        try {
            $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        } catch (JsonException $exception) {
            throw new InvalidArgumentException($this->Translate('The Radar configuration is not valid JSON.'), 0, $exception);
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException($this->Translate('Radar axes and values must be lists.'));
        }
        return $rows;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'Shape'), ['polygon', 'circle'], true)) {
            return false;
        }
        foreach (['LineType'     => ['solid', 'dashed', 'dotted'], 'SplitLineType' => ['solid', 'dashed', 'dotted'],
            'Symbol'             => ['circle', 'emptyCircle', 'rect', 'roundRect', 'triangle', 'diamond', 'none'],
            'ValueLabelPosition' => ['top', 'bottom', 'left', 'right', 'inside'],
            'LegendPosition'     => ['top', 'bottom'], 'HoverFocus' => ['self', 'none']] as $name => $allowed) {
            if (!in_array($this->ReadPropertyString($prefix . $name), $allowed, true)) {
                return false;
            }
        }
        try {
            $this->ParseGroupColors($this->ReadPropertyString($prefix . 'GroupColors'));
        } catch (Throwable) {
            return false;
        }
        foreach (['StartAngle'        => [0, 360], 'SplitNumber' => [1, 10], 'AxisNameGap' => [0, 40],
            'LineWidth'               => [0, 8], 'LineOpacityPercent' => [0, 100], 'AreaOpacityPercent' => [0, 100],
            'SymbolSize'              => [0, 16], 'TitleFontSizePercent' => [50, 200], 'AxisFontSizePercent' => [50, 200],
            'SymbolRotate'            => [-180, 180], 'SymbolBorderWidth' => [0, 8], 'SymbolOpacityPercent' => [0, 100],
            'AxisLineWidth'           => [0, 8], 'AxisLineOpacityPercent' => [0, 100], 'SplitLineWidth' => [0, 8],
            'SplitLineOpacityPercent' => [0, 100], 'SplitAreaOpacityPercent' => [0, 100],
            'ValueFontSizePercent'    => [50, 200], 'ValueLabelDistance' => [0, 30],
            'LegendFontSizePercent'   => [50, 200], 'AreaShadowBlur' => [0, 20]] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['TitleColor', 'AxisColor', 'GridColor', 'SymbolBorderColor', 'SplitAreaColor1', 'SplitAreaColor2',
            'ValueColor', 'LegendColor', 'AreaShadowColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = $this->ReadAnimationStyle($prefix);
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if ($name === 'EChartsTheme') {
                continue;
            }
            if ($name === 'GroupColors') {
                $style['groupColors'] = (object) $this->ParseGroupColors($this->ReadPropertyString($prefix . $name));
                continue;
            }
            $key = lcfirst($name);
            $style[$key] = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($prefix . $name),
                'integer' => $this->ReadPropertyInteger($prefix . $name),
                default   => $this->ReadPropertyString($prefix . $name)
            };
            if (str_ends_with($name, 'Color') || in_array($name, ['SplitAreaColor1', 'SplitAreaColor2'], true)) {
                $style[$key] = $style[$key] < 0 ? '' : EChartsAsset::ColorToHex($style[$key]);
            }
        }
        return $style;
    }

    /** Local design contract: at most sixteen unique group names, independent of source validity. */
    private function ParseGroupColors(mixed $raw): array
    {
        $rows = $this->DecodeRows($raw);
        if (count($rows) > 16) {
            throw new InvalidArgumentException($this->Translate('Use at most 16 unique group colors.'));
        }
        $colors = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            $group = is_array($row) && is_string($row['Group'] ?? null) ? trim($row['Group']) : '';
            $color = is_array($row) ? ($row['Color'] ?? null) : null;
            if ($group === '' || mb_strlen($group) > 80 || isset($seen[$group]) || !is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException(sprintf($this->Translate('Group color %d: select a unique group and a valid color.'), $index + 1));
            }
            $seen[$group] = true;
            if ($color >= 0) {
                $colors[$group] = EChartsAsset::ColorToHex($color);
            }
        }
        return $colors;
    }

    /** Optional initial view overrides; stale named groups remain harmless, like group colors. */
    private function ParseGroupVisibility(mixed $raw): array
    {
        if (is_string($raw) && !str_starts_with(ltrim($raw), '[')) {
            throw new InvalidArgumentException($this->Translate('Radar axes and values must be lists.'));
        }
        $rows = $this->DecodeRows($raw);
        if (count($rows) > 16) {
            throw new InvalidArgumentException($this->Translate('Use at most 16 unique group visibility settings.'));
        }
        $visibility = [];
        foreach ($rows as $index => $row) {
            $group = is_array($row) && is_string($row['Group'] ?? null) ? trim($row['Group']) : '';
            $visible = is_array($row) ? ($row['InitiallyVisible'] ?? true) : null;
            if ($group === '' || mb_strlen($group) > 80 || array_key_exists($group, $visibility) || !is_bool($visible)) {
                throw new InvalidArgumentException(sprintf($this->Translate('Group visibility %d: select a unique group and a boolean initial visibility.'), $index + 1));
            }
            $visibility[$group] = $visible;
        }
        return $visibility;
    }

    private function GroupOptions(mixed $sources): array
    {
        $groups = [];
        foreach (is_array($sources) ? array_slice($sources, 0, 160) : [] as $row) {
            if (is_array($row) && is_string($row['Group'] ?? null)) {
                $name = trim($row['Group']);
                if ($name !== '' && mb_strlen($name) <= 80) {
                    $groups[$name] = true;
                }
            }
        }
        $options = [['caption' => $this->Translate('Select group'), 'value' => '']];
        foreach (array_keys($groups) as $name) {
            $options[] = ['caption' => (string) $name, 'value' => (string) $name];
        }
        return $options;
    }

    private function WithGroupColorOptions(array $fields, array $options): array
    {
        foreach ($fields as &$field) {
            if (in_array($field['name'] ?? '', ['GroupColors', 'IPSViewGroupColors', 'GroupVisibility'], true)) {
                $field['columns'][0]['edit']['options'] = $options;
                $field['form'][0]['options'] = $options;
            }
            if (isset($field['items'])) {
                $field['items'] = $this->WithGroupColorOptions($field['items'], $options);
            }
        }
        unset($field);
        return $fields;
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $this->ReadPropertyString($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? 'IPSViewEChartsTheme' : 'EChartsTheme');
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return $this->ErrorState($error['Status'] === self::STATUS_DESIGN_INVALID
                ? 'The Radar design is invalid.' : 'Configure valid Radar axes and comparison values.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetRadarDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Radar values must be finite numbers with timestamps.');
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            if ($this->ReadAttributeString('LastError') === 'MODEL_TOO_LARGE') {
                return $this->ErrorState(self::MODEL_TOO_LARGE_MESSAGE);
            }
            return $this->ErrorState('The Radar values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'radar', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'radar', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Radar values could not be loaded.');
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

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-radar-root',
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
                if (is_string($row['name'] ?? null) && array_key_exists($row['name'], self::DESIGN_DEFAULTS)) {
                    $row['name'] = 'IPSView' . $row['name'];
                    $rows[] = $row;
                    continue;
                }
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
            throw new RuntimeException('The Radar Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECRA_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}

