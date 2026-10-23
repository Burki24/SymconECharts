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
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\SankeyModel;
use SymconECharts\SankeyPreview;

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
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/SankeyModel.php';
require_once __DIR__ . '/SankeyPreview.php';

class EChartsSankey extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm {
        UpdateBarPreviewFromForm as private UpdateSharedBarPreviewFromForm;
    }
    use SankeyPreview;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewSankey';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'                => EChartsAsset::THEME_AUTO,
        'Orientation'                 => 'horizontal', 'NodeAlign' => 'justify',
        'NodeWidth'                   => 20, 'NodeGap' => 12, 'Draggable' => true,
        'LayoutIterations'            => 32, 'HoverFocus' => 'adjacency',
        'HighlightEdgeOpacityPercent' => 85, 'BlurEdgeOpacityPercent' => 5,
        'ShowTooltip'                 => true, 'ShowLabels' => true, 'ShowValues' => false,
        'LabelPosition'               => 'auto', 'LabelDistance' => 5, 'LabelMaxWidth' => 0,
        'LabelOverflow'               => 'truncate', 'LabelBold' => false, 'HideOverlappingLabels' => false,
        'ShowEdgeValues'              => false, 'EdgeLabelFontSizePercent' => 100, 'EdgeLabelColor' => -1,
        'NodeColors'                  => '[]', 'NodeOpacityPercent' => 100,
        'NodeBorderWidth'             => 0, 'NodeBorderRadius' => 0, 'NodeBorderColor' => -1,
        'NodeShadowBlur'              => 0, 'NodeShadowColor' => -1,
        'EdgeColorMode'               => 'gradient', 'EdgeOpacityPercent' => 35, 'EdgeCurvenessPercent' => 50,
        'TitleFontSizePercent'        => 100, 'LabelFontSizePercent' => 100,
        'TitleColor'                  => -1, 'LabelColor' => -1, 'NodeColor' => -1, 'EdgeColor' => -1
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
            $this->RegisterTransientRefresh('ECSA');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Nodes', '[]');
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Unit', '');
            $this->RegisterPropertyString('UnitMode', 'manual');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Sankey for IPSView'), 90);
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
            $form['elements'] = $this->SetFormFieldVisibility($form['elements'], ['Unit'], $this->ReadPropertyString('UnitMode') !== 'automatic');
            $connectionFields = $this->ConnectionFields(json_decode($this->ReadPropertyString('Nodes'), true));
            foreach ($form['elements'] as &$element) {
                if (($element['name'] ?? '') === 'Sources') {
                    $element = $connectionFields;
                }
            }
            unset($element);
            $form['elements'] = $this->WithNodeColorOptions($form['elements'], $this->NodeOptions(json_decode($this->ReadPropertyString('Nodes'), true)));
            $diagnostic = $this->GetConfigurationError()['Message'] ?? $this->ReadAttributeString('LastError');
            $form['elements'][] = ['type' => 'Label', 'name' => 'ConfigurationDiagnostics',
                'caption'                 => $diagnostic !== '' ? $diagnostic : $this->Translate('Sankey configuration is valid.')];
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECSA');
            $form = $this->WithIPSViewDesignFormState($form, 'ECSA');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Layout and interaction', 'start' => 'Orientation'],
                ['caption' => 'Labels and typography', 'start' => 'ShowLabels'],
                ['caption' => 'Node appearance', 'start' => 'NodeColor'],
                ['caption' => 'Flow appearance', 'start' => 'EdgeColorMode']
            ]);
            $fields = [
                'Title', 'Nodes', 'Sources', 'Unit', 'UnitMode', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
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
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECSA', $fields));
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function UpdateUnitModeForm(string $Mode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateFormField('Unit', 'visible', $Mode !== 'automatic');
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

    public function GetSankeyData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetSankeyDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetSankeyDiagnostic(): string
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
            return $debugFlowResult = $this->RenderSankeyHTMLPage(false);
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
            return $debugFlowResult = $this->RenderSankeyHTMLPage(true);
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

    /** Updates draft diagnostics, node selectors and both previews without saving. */
    public function UpdateBarPreviewFromForm(string $Configuration): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $values = json_decode($Configuration, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($values)) {
                    throw new InvalidArgumentException($this->Translate('The Sankey configuration is not valid JSON.'));
                }
                if (isset($values['Nodes']) && is_array($values['Nodes'])) {
                    $this->UpdateNodeOptions(json_encode($values['Nodes'], JSON_THROW_ON_ERROR));
                }
                $this->GetValidatedConfiguration($values);
                foreach (['NodeColors', 'IPSViewNodeColors'] as $name) {
                    if (array_key_exists($name, $values)
                        && ($name === 'NodeColors' || !($values['IPSViewUseTileDesign'] ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')))) {
                        $this->ParseNodeColors($values[$name]);
                    }
                }
                $message = $this->Translate('Sankey configuration is valid.');
            } catch (Throwable $exception) {
                $message = $exception instanceof JsonException
                    ? $this->Translate('The Sankey configuration is not valid JSON.') : $exception->getMessage();
            }
            $this->UpdateFormField('ConfigurationDiagnostics', 'caption', $message);
            $this->UpdateSharedBarPreviewFromForm($Configuration);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    /** Updates connection selectors after editing unsaved node rows. */
    public function UpdateNodeOptions(string $JSONNodes): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            try {
                $nodes = json_decode($JSONNodes, true, 64, JSON_THROW_ON_ERROR);
                $options = $this->NodeOptions($nodes);
                foreach (['NodeColors', 'IPSViewNodeColors'] as $name) {
                    $field = $this->WithNodeColorOptions([$this->NodeColorField()], $options)[0];
                    $this->UpdateFormField($name, 'columns', json_encode($field['columns'], JSON_THROW_ON_ERROR));
                    $this->UpdateFormField($name, 'form', json_encode($field['form'], JSON_THROW_ON_ERROR));
                }
                $field = $this->ConnectionFields($nodes);
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
        return $this->SankeyPreviewSvg($values, $ipsView);
    }

    private function NodeOptions(mixed $nodes): array
    {
        $options = [['caption' => $this->Translate('Select node'), 'value' => '']];
        $seen = [];
        foreach (is_array($nodes) ? array_slice($nodes, 0, 100) : [] as $node) {
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

    private function NodeColorField(): array
    {
        foreach ($this->LoadConfigurationForm()['elements'] as $element) {
            foreach ($element['items'] ?? [] as $field) {
                if (($field['name'] ?? '') === 'NodeColors') {
                    return $field;
                }
            }
        }
        throw new RuntimeException('The Sankey node color list is missing.');
    }

    private function WithNodeColorOptions(array $fields, array $options): array
    {
        foreach ($fields as &$field) {
            if (($field['name'] ?? '') === 'NodeColors') {
                $field['columns'][0]['edit']['options'] = $options;
                $field['form'][0]['options'] = $options;
            }
            if (isset($field['items'])) {
                $field['items'] = $this->WithNodeColorOptions($field['items'], $options);
            }
        }
        unset($field);
        return $fields;
    }

    private function ConnectionFields(mixed $nodes): array
    {
        $options = $this->NodeOptions($nodes);
        foreach ($this->LoadConfigurationForm()['elements'] as $field) {
            if (($field['name'] ?? '') !== 'Sources') {
                continue;
            }
            foreach ($field['columns'] as &$column) {
                if (in_array($column['name'], ['Source', 'Target'], true)) {
                    $column['edit']['options'] = $options;
                }
            }
            unset($column);
            foreach ($field['form'] as &$item) {
                if (in_array($item['name'], ['Source', 'Target'], true)) {
                    $item['options'] = $options;
                }
            }
            unset($item);
            return $field;
        }
        throw new RuntimeException('The Sankey connection list is missing.');
    }

    private function GetSankeyDataForOutput(bool $ipsView): string
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
        foreach (array_unique(array_column($sources, 'VariableID')) as $id) {
            $values[$id] = $this->ReadCurrentSource($id);
        }
        try {
            $model = SankeyModel::Build($configuration['nodes'], $sources, $values, fn (string $text): string => $this->Translate($text));
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->DebugEChartsSetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'sankey',
            'theme'         => $this->EffectiveTheme($ipsView),
            'sankey'        => [
                'title' => $this->ReadPropertyString('Title'),
                'unit'  => $configuration['unit'],
                'style' => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderSankeyHTMLPage(bool $ipsView): string
    {
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Sankey',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-sankey-root', 'echarts-sankey'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Sankey nodes and connections.'                   => $this->Translate('Configure valid Sankey nodes and connections.'),
                'The Sankey design is invalid.'                                   => $this->Translate('The Sankey design is invalid.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Sankey values could not be loaded.'                          => $this->Translate('The Sankey values could not be loaded.'),
                'Sankey values must be finite and non-negative.'                  => $this->Translate('Sankey values must be finite and non-negative.'),
                'No positive flows'                                               => $this->Translate('No positive flows'),
                'Apache ECharts could not be initialized.'                        => $this->Translate('Apache ECharts could not be initialized.'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                ...$this->ResponsiveVisualizationLayoutOptions(!$ipsView),
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::SankeyJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
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
        try {
            $this->ParseNodeColors($this->ReadPropertyString('NodeColors'));
            if (!$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $this->ParseNodeColors($this->ReadPropertyString('IPSViewNodeColors'));
            }
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => $this->Translate('The Sankey design is invalid.')];
        }
        return null;
    }

    private function GetValidatedConfiguration(array $values = []): array
    {
        $nodes = $this->DecodeRows($values['Nodes'] ?? $this->ReadPropertyString('Nodes'));
        $sources = $this->DecodeRows($values['Sources'] ?? $this->ReadPropertyString('Sources'));
        $configuration = SankeyModel::Validate($nodes, $sources, fn (string $text): string => $this->Translate($text));
        foreach ($configuration['sources'] as $index => $source) {
            $id = $source['VariableID'];
            if (!IPS_VariableExists($id) || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException(sprintf($this->Translate('Connection %d: select an existing Integer or Float variable.'), $index + 1));
            }
        }
        $mode = $values['UnitMode'] ?? $this->ReadPropertyString('UnitMode');
        if (!is_string($mode)) {
            throw new InvalidArgumentException($this->Translate('The Sankey unit mode is invalid.'));
        }
        $configuration['unit'] = SankeyModel::ResolveUnit(
            $configuration['sources'],
            $mode,
            (string) ($values['Unit'] ?? $this->ReadPropertyString('Unit')),
            static fn (int $id): string => EChartsVariablePresentation::Resolve($id, 0.0, 100.0, '', 2, true, true)['unit'],
            fn (string $text): string => $this->Translate($text)
        );
        return $configuration;
    }

    private function GetValidatedSources(): array
    {
        return $this->GetValidatedConfiguration()['sources'];
    }

    private function DecodeRows(mixed $raw): array
    {
        if (is_string($raw) && strlen($raw) > 65536) {
            throw new InvalidArgumentException($this->Translate('The Sankey configuration is too large.'));
        }
        try {
            $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        } catch (JsonException $exception) {
            throw new InvalidArgumentException($this->Translate('The Sankey configuration is not valid JSON.'), 0, $exception);
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException($this->Translate('Sankey nodes and connections must be lists.'));
        }
        return $rows;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['Orientation' => ['horizontal', 'vertical'], 'NodeAlign' => ['justify', 'left', 'right'],
            'LabelPosition'     => ['auto', 'inside', 'left', 'right', 'top', 'bottom'],
            'LabelOverflow'     => ['truncate', 'break', 'breakAll'], 'HoverFocus' => ['none', 'adjacency', 'trajectory'],
            'EdgeColorMode'     => ['source', 'target', 'gradient', 'fixed']] as $name => $allowed) {
            if (!in_array($this->ReadPropertyString($prefix . $name), $allowed, true)) {
                return false;
            }
        }
        foreach (['NodeWidth'             => [4, 40], 'NodeGap' => [0, 40], 'EdgeOpacityPercent' => [0, 100],
            'LayoutIterations'            => [0, 64], 'LabelDistance' => [0, 30], 'LabelMaxWidth' => [0, 400],
            'NodeBorderWidth'             => [0, 8], 'NodeBorderRadius' => [0, 20], 'NodeOpacityPercent' => [0, 100],
            'NodeShadowBlur'              => [0, 20], 'EdgeLabelFontSizePercent' => [50, 200],
            'HighlightEdgeOpacityPercent' => [0, 100], 'BlurEdgeOpacityPercent' => [0, 100],
            'EdgeCurvenessPercent'        => [0, 100], 'TitleFontSizePercent' => [50, 200],
            'LabelFontSizePercent'        => [50, 200]] as $name => [$minimum, $maximum]) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < $minimum || $value > $maximum) {
                return false;
            }
        }
        foreach (['TitleColor', 'LabelColor', 'NodeColor', 'EdgeColor', 'NodeBorderColor', 'NodeShadowColor', 'EdgeLabelColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,string> Keyed by stable node key, never by its visible label. */
    private function ParseNodeColors(mixed $raw): array
    {
        $rows = $this->DecodeRows($raw);
        if (count($rows) > 100) {
            throw new InvalidArgumentException($this->Translate('Use at most 100 unique node colors.'));
        }
        $colors = [];
        foreach ($rows as $index => $row) {
            $key = is_array($row) ? ($row['Key'] ?? '') : '';
            $color = is_array($row) ? ($row['Color'] ?? null) : null;
            if (!is_string($key) || !preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key)
                || array_key_exists($key, $colors) || !is_int($color) || $color < 0 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException(sprintf($this->Translate('Node color %d: choose a unique node and a color.'), $index + 1));
            }
            $colors[$key] = EChartsAsset::ColorToHex($color);
        }
        return $colors;
    }

    private function ReadDesignStyle(bool $ipsView): array
    {
        $prefix = $ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign') ? 'IPSView' : '';
        $style = $this->ReadAnimationStyle($prefix);
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if ($name === 'EChartsTheme') {
                continue;
            }
            $key = lcfirst($name);
            if ($name === 'NodeColors') {
                $style[$key] = (object) $this->ParseNodeColors($this->ReadPropertyString($prefix . $name));
                continue;
            }
            $style[$key] = match (gettype($default)) {
                'boolean' => $this->ReadPropertyBoolean($prefix . $name),
                'integer' => $this->ReadPropertyInteger($prefix . $name),
                default   => $this->ReadPropertyString($prefix . $name)
            };
            if (str_ends_with($name, 'Color')) {
                $style[$key] = $style[$key] < 0 ? '' : EChartsAsset::ColorToHex($style[$key]);
            }
        }
        return $style;
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
                ? 'The Sankey design is invalid.' : ($this->ReadPropertyString('UnitMode') === 'manual'
                    ? 'Configure valid Sankey nodes and connections.' : $error['Message']));
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetSankeyDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Sankey values must be finite and non-negative.');
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState('The Sankey values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'sankey', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'sankey', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Sankey values could not be loaded.');
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
            '#echarts-sankey-root',
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
            throw new RuntimeException('The Sankey Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECSA_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
