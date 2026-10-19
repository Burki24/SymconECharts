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
use SymconECharts\EChartsLabelDesign;
use SymconECharts\EChartsNormalization;
use SymconECharts\EChartsPreviewSourceCount;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\PieData;

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
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsNormalization.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsPreviewSourceCount.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/PieData.php';

class EChartsPie extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewPie';
    private const MAXIMUM_MODEL_BYTES = 200000;
    private const MODEL_TOO_LARGE_MESSAGE = 'The Pie model is too large. Reduce source labels or units.';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const MODES = ['pie', 'donut', 'nightingale'];
    private const SORT_ORDERS = ['configured', 'ascending', 'descending'];
    private const LABEL_POSITIONS = ['inside', 'outside'];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->RegisterTransientRefresh('ECPI');
            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyBoolean('NormalizeValues', false);
            $this->RegisterPropertyBoolean('EnableSourceSelection', false);
            $this->RegisterPropertyString('Title', '');
            $this->RegisterDesignProperties();
            $this->RegisterIPSViewHTMLPageProperties();
            $this->RegisterEChartsIPSViewTransport();
            $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
            $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
            $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
            $this->RegisterPropertyInteger('IPSViewBackgroundOpacityPercent', EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT);
            $this->RegisterDesignProperties('IPSView');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Pie for IPSView'), 90);
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
            foreach (['', 'IPSView'] as $prefix) {
                EChartsLabelDesign::InsertAfterRowContaining($form['elements'], $prefix . 'LabelPosition', [
                    ['type' => 'RowLayout', 'items' => [
                        ['type'       => 'Select', 'name' => $prefix . 'OutsideLabelAlignment',
                            'caption' => 'Outside label alignment', 'options' => [
                                ['caption' => 'Natural', 'value' => 'none'],
                                ['caption' => 'Leader line', 'value' => 'labelLine'],
                                ['caption' => 'Container edge', 'value' => 'edge']
                            ]],
                        ['type'       => 'NumberSpinner', 'name' => $prefix . 'LabelEdgeDistance',
                            'caption' => 'Label edge distance', 'minimum' => 0, 'maximum' => 100, 'suffix' => 'px']
                    ]],
                    ['type' => 'RowLayout', 'items' => [
                        ['type'       => 'NumberSpinner', 'name' => $prefix . 'LeaderLineLength',
                            'caption' => 'First leader length', 'minimum' => 0, 'maximum' => 100, 'suffix' => 'px'],
                        ['type'       => 'NumberSpinner', 'name' => $prefix . 'LeaderLineLength2',
                            'caption' => 'Second leader length', 'minimum' => 0, 'maximum' => 100, 'suffix' => 'px'],
                        ['type'       => 'NumberSpinner', 'name' => $prefix . 'LabelLineGap',
                            'caption' => 'Text gap to leader', 'minimum' => 0, 'maximum' => 100, 'suffix' => 'px']
                    ]]
                ]);
            }
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECPI');
            $this->InsertIPSViewHTMLPageFormItems(
                $form['elements'],
                'Configure optional IPSView HTML output.',
                'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
            );
            $form = $this->WithIPSViewDesignFormState($form, 'ECPI');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Geometry and order', 'start' => 'PieMode'],
                ['caption' => 'Labels and visibility', 'start' => 'ShowLegend'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'NormalizeValues', 'EnableSourceSelection', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                    'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys($this->DesignPropertyNames()),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys($this->DesignPropertyNames()))
            );

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECPI', $fields));
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
            foreach ($this->DesignPropertyNames() as $name => $type) {
                $value = match ($type) {
                    'string'  => $this->ReadPropertyString($name),
                    'boolean' => $this->ReadPropertyBoolean($name),
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

    public function GetPieData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
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
            $labels = EChartsSourceIdentity::LabelsForSources(array_map(
                static fn (array $source): array => ['VariableID' => $source['VariableID'], 'Label' => $source['Label']],
                $sources
            ));
            $items = [];
            foreach ($sources as $index => $source) {
                $current = $this->ReadCurrentSource($source['VariableID']);
                if (!$this->ReadPropertyBoolean('NormalizeValues') && $current['Value'] < 0) {
                    $this->WriteAttributeString('LastError', 'Pie charts require non-negative values.');
                    $this->DebugEChartsSetStatus(self::STATUS_VALUE_INVALID);
                    throw new DomainException('Pie charts require non-negative values.');
                }
                $items[] = [
                    'id'               => 'variable-' . $source['VariableID'],
                    'initiallyVisible' => $source['InitiallyVisible'],
                    'name'             => $labels[$source['VariableID']],
                    'value'            => $this->ReadPropertyBoolean('NormalizeValues')
                        ? EChartsNormalization::Value($current['Value'], $source) : $current['Value'],
                    ...($this->ReadPropertyBoolean('NormalizeValues')
                        ? EChartsNormalization::Metadata($current['Value'], $source) : []),
                    'timestamp' => $current['Timestamp'],
                    'unit'      => $source['Unit'],
                    'decimals'  => $source['Decimals'],
                    'color'     => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color']),
                    'order'     => $index
                ];
            }
            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);

            $json = json_encode([
                'schemaVersion' => 1,
                'family'        => 'pie',
                'theme'         => $this->ReadPropertyString('EChartsTheme'),
                'pie'           => [
                    'enableSourceSelection' => $this->ReadPropertyBoolean('EnableSourceSelection'),
                    'normalized'            => $this->ReadPropertyBoolean('NormalizeValues'),
                    'title'                 => $this->ReadPropertyString('Title'),
                    'style'                 => $this->ReadDesignStyle()
                ],
                'items'         => $items
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            if (strlen($json) > self::MAXIMUM_MODEL_BYTES) {
                $this->WriteAttributeString('LastError', 'MODEL_TOO_LARGE');
                $this->DebugEChartsSetStatus(self::STATUS_DESIGN_INVALID);
                throw new RuntimeException(self::MODEL_TOO_LARGE_MESSAGE);
            }
            return $debugFlowResult = $json;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetPieDiagnostic(): string
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
            return $debugFlowResult = $this->RenderPieHTMLPage(false);
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
            return $debugFlowResult = $this->RenderPieHTMLPage(true);
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

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $sourceData = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', true, PieData::MAXIMUM_SOURCE_COUNT);
        $items = $sourceData['items'];
        $normalized = (bool) ($values['NormalizeValues'] ?? $this->ReadPropertyBoolean('NormalizeValues'));
        if ($normalized) {
            foreach ($items as &$item) {
                try {
                    $item['value'] = EChartsNormalization::Value($item['value'], $item);
                } catch (InvalidArgumentException) {
                    return EChartsBarPreview::Open($palette, $this->Translate('Normalization requires finite Min < Max for every source.'), false, $ipsView, $values, '', '') . '</svg>';
                }
            }
            unset($item);
            $sourceData['items'] = $items;
        }
        $selectionEnabled = (bool) ($values['EnableSourceSelection'] ?? $this->ReadPropertyBoolean('EnableSourceSelection'));
        $hasNegative = count(array_filter($items, static fn (array $item): bool => !is_finite($item['value']) || $item['value'] < 0)) > 0;
        $allShares = $hasNegative ? array_fill(0, count($items), 0.0) : PieData::Shares(array_column($items, 'value'));
        foreach ($items as $index => &$item) {
            $item['order'] = $index;
            $item['allShare'] = $allShares[$index];
        }
        unset($item);
        $items = array_values(array_filter($items, static fn (array $item): bool => !$selectionEnabled || ($item['InitiallyVisible'] ?? true) !== false));
        $selectedCount = count($items);
        $selectionShares = $hasNegative ? array_fill(0, $selectedCount, 0.0) : PieData::Shares(array_column($items, 'value'));
        foreach ($items as $index => &$item) {
            $item['share'] = $selectionShares[$index];
        }
        unset($item);
        $maximum = $items === [] ? 0.0 : max(array_column($items, 'value'));
        $mode = $this->BarPreviewValue($values, 'PieMode', 'string', $ipsView);
        $sort = $this->BarPreviewValue($values, 'SortOrder', 'string', $ipsView);
        if ($sort === 'ascending' || $sort === 'descending') {
            usort($items, static fn (array $a, array $b): int => $sort === 'ascending'
                ? $a['value'] <=> $b['value'] : $b['value'] <=> $a['value']);
        }
        $items = array_slice($items, 0, 6);
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            $sourceData['sample'],
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Pie preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $svg = str_replace('<svg xmlns', '<svg data-pie-visible-count="' . $selectedCount
            . '" data-pie-source-count="' . count($sourceData['items']) . '" xmlns', $svg);
        $modeCaption = match ($mode) {
            'donut'       => 'Donut',
            'nightingale' => 'Nightingale rose',
            default       => 'Pie'
        };
        $svg .= '<text x="696" y="58" text-anchor="end" fill="' . EChartsBarPreview::E($palette['muted'])
            . '" font-size="12">' . EChartsBarPreview::E($this->Translate($modeCaption)) . '</text>';
        $positive = $hasNegative ? [] : array_values(array_filter($items, static fn (array $item): bool => $item['value'] > 0));
        $sum = $hasNegative ? 0.0 : array_sum($selectionShares);
        $outer = max(80, min(125, 125 * $this->BarPreviewValue($values, 'OuterRadiusPercent', 'integer', $ipsView) / 100));
        $inner = $mode === 'pie' ? 0 : $outer * max(0, min(70, $this->BarPreviewValue($values, 'InnerRadiusPercent', 'integer', $ipsView))) / 100;
        $showLabels = $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView);
        $showPercent = $this->BarPreviewValue($values, 'ShowPercent', 'boolean', $ipsView);
        $labelPosition = $this->BarPreviewValue($values, 'LabelPosition', 'string', $ipsView);
        $alignment = $this->BarPreviewValue($values, 'OutsideLabelAlignment', 'string', $ipsView);
        $leader1 = max(0, min(100, $this->BarPreviewValue($values, 'LeaderLineLength', 'integer', $ipsView)));
        $leader2 = max(0, min(100, $this->BarPreviewValue($values, 'LeaderLineLength2', 'integer', $ipsView)));
        $lineGap = max(0, min(100, $this->BarPreviewValue($values, 'LabelLineGap', 'integer', $ipsView)));
        $edgeDistance = max(0, min(100, $this->BarPreviewValue($values, 'LabelEdgeDistance', 'integer', $ipsView)));
        $labelSize = max(7, min(20, (int) round(11 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $previewLanguage = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        if ($normalized) {
            $svg .= '<text x="24" y="350" font-size="11" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E($this->Translate('Share of normalized weights')) . '</text>';
        }
        if ($selectionEnabled) {
            $svg .= '<text x="24" y="325" font-size="11" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E(sprintf($this->Translate('Initially visible: %d of %d sources'), $selectedCount, count($sourceData['items']))) . '</text>';
        }
        $legendColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $palette['text']);
        $legendSize = max(7, min(20, (int) round(11 * $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView) / 100)));
        $start = -M_PI / 2;
        $sectorItems = $mode === 'nightingale' && $sum > 0 ? $items : $positive;
        foreach ($sectorItems as $index => $item) {
            $angle = $mode === 'nightingale' ? 2 * M_PI / $selectedCount : 2 * M_PI * $item['share'] / 100;
            $end = $start + $angle;
            $radius = $mode === 'nightingale' ? $inner + ($outer - $inner) * ($item['value'] / $maximum) : $outer;
            $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])];
            if ($radius > $inner) {
                $svg .= '<path data-pie-id="' . EChartsBarPreview::E(isset($item['VariableID']) ? 'variable-' . $item['VariableID'] : 'sample-' . $item['order'])
                    . '" data-pie-share="' . json_encode($item['share'], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
                    . '" data-pie-all-share="' . json_encode($item['allShare'], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
                    . '" d="' . $this->PreviewSector(310, 190, $inner, $radius, $start, $end)
                    . '" fill="' . EChartsBarPreview::E($color) . '"/>';
            }
            if ($showLabels && $item['value'] > 0) {
                $mid = $start + $angle / 2;
                $labelRadius = $labelPosition === 'inside' ? ($inner + $radius) / 2 : $radius + 15;
                $x = 310 + $labelRadius * cos($mid);
                $y = 190 + $labelRadius * sin($mid);
                $anchor = $labelPosition === 'inside' ? 'middle' : (cos($mid) >= 0 ? 'start' : 'end');
                if ($labelPosition !== 'inside'
                    && ($alignment !== 'edge' || $leader1 !== 15 || $leader2 !== 15
                        || $lineGap !== 5 || $edgeDistance !== 8)) {
                    $side = cos($mid) >= 0 ? 1 : -1;
                    $x1 = 310 + ($radius + $leader1) * cos($mid);
                    $y1 = 190 + ($radius + $leader1) * sin($mid);
                    $x2 = $alignment === 'edge' ? 310 + $side * (150 - $edgeDistance - $lineGap)
                        : $x1 + $side * $leader2;
                    $svg .= '<path d="M ' . EChartsBarPreview::N(310 + $radius * cos($mid)) . ' '
                        . EChartsBarPreview::N(190 + $radius * sin($mid)) . ' L '
                        . EChartsBarPreview::N($x1) . ' ' . EChartsBarPreview::N($y1) . ' L '
                        . EChartsBarPreview::N($x2) . ' ' . EChartsBarPreview::N($y1)
                        . '" fill="none" stroke="' . EChartsBarPreview::E($labelColor) . '"/>';
                    $x = $x2 + $side * $lineGap;
                    $y = $y1;
                    $anchor = $side > 0 ? 'start' : 'end';
                }
                $text = mb_strimwidth((string) $item['label'], 0, 14, '…');
                if ($showPercent) {
                    $text .= ' ' . EChartsBarPreview::FormatValue(
                        $item['share'],
                        1,
                        '%',
                        $previewLanguage
                    );
                }
                $svg .= '<text x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                    . '" text-anchor="' . $anchor . '" fill="' . EChartsBarPreview::E($labelColor) . '" font-size="'
                    . $labelSize . '">' . EChartsBarPreview::E($text) . '</text>';
            }
            $start = $end;
        }
        if ($hasNegative) {
            $svg .= '<text x="310" y="195" text-anchor="middle" fill="' . EChartsBarPreview::E($palette['text'])
                . '" font-size="14">' . EChartsBarPreview::E($this->Translate('Pie charts require non-negative values.')) . '</text>';
        } elseif ($selectedCount === 0) {
            $svg .= '<text x="310" y="195" text-anchor="middle" fill="' . EChartsBarPreview::E($palette['muted'])
                . '" font-size="14">' . EChartsBarPreview::E($this->Translate('No sources selected')) . '</text>';
        } elseif ($sum === 0.0) {
            $svg .= '<text x="310" y="195" text-anchor="middle" fill="' . EChartsBarPreview::E($palette['muted'])
                . '" font-size="14">' . EChartsBarPreview::E($this->Translate('No positive values')) . '</text>';
        }
        if ($this->BarPreviewValue($values, 'ShowLegend', 'boolean', $ipsView)) {
            foreach ($items as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])];
                $y = 105 + $index * 30;
                $svg .= '<rect x="480" y="' . $y . '" width="12" height="12" fill="' . EChartsBarPreview::E($color) . '"/>';
                $svg .= '<text x="500" y="' . ($y + 11) . '" fill="' . EChartsBarPreview::E($legendColor)
                    . '" font-size="' . $legendSize . '">' . EChartsBarPreview::E(mb_strimwidth($item['label'], 0, 25, '…')) . '</text>';
            }
        }

        return EChartsPreviewSourceCount::Append(
            $svg . '</svg>',
            count($items),
            count($sourceData['items']),
            $this->Translate('Preview shows %d of %d sources'),
            $palette['muted'],
            696,
            351
        );
    }

    private function PreviewSector(float $cx, float $cy, float $inner, float $outer, float $start, float $end): string
    {
        return EChartsBarPreview::Sector($cx, $cy, $inner, $outer, $start, $end);
    }

    private function RenderPieHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        $configuration = [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Pie',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-pie-root', 'echarts-pie'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                self::MODEL_TOO_LARGE_MESSAGE                                     => $this->Translate(self::MODEL_TOO_LARGE_MESSAGE),
                'All'                                                             => $this->Translate('All'),
                'Invert'                                                          => $this->Translate('Invert'),
                'Share of visible selection'                                      => $this->Translate('Share of visible selection'),
                'Share of full model'                                             => $this->Translate('Share of full model'),
                'Share of selected normalized weights'                            => $this->Translate('Share of selected normalized weights'),
                'Share of all normalized weights'                                 => $this->Translate('Share of all normalized weights'),
                'No sources selected.'                                            => $this->Translate('No sources selected.'),
                'No positive values in the selected sources.'                     => $this->Translate('No positive values in the selected sources.'),
                'Normalized'                                                      => $this->Translate('Normalized'),
                'Range'                                                           => $this->Translate('Range'),
                'Share of normalized weights'                                     => $this->Translate('Share of normalized weights'),
                'Configure valid Pie sources.'                                    => $this->Translate('Configure valid Pie sources.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Pie values could not be loaded.'                             => $this->Translate('The Pie values could not be loaded.'),
                'Pie charts require non-negative values.'                         => $this->Translate('Pie charts require non-negative values.'),
                'No positive values'                                              => $this->Translate('No positive values'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::PieJavaScript(),
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
        $this->SetSummary('');
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
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Pie design is invalid.'];
        }
        return null;
    }

    /** @return list<array{VariableID:int,Label:string,Unit:string,Decimals:int,Color:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The Pie sources are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 1 || count($sources) > PieData::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Configure 1 to 100 unique numeric Pie sources with one common unit.');
        }
        $validated = [];
        $ids = [];
        $unit = null;
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Pie source must be an object.');
            }
            $id = $source['VariableID'] ?? null;
            if (!is_int($id) || $id <= 0 || in_array($id, $ids, true) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Pie sources must be unique numeric variables.');
            }
            $decimals = $source['Decimals'] ?? 1;
            $color = $source['Color'] ?? -1;
            $initiallyVisible = $source['InitiallyVisible'] ?? true;
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_bool($initiallyVisible)
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF) {
                throw new InvalidArgumentException('A Pie source contains invalid display settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $id,
                0.0,
                100.0,
                is_string($source['Unit'] ?? null) ? $source['Unit'] : '',
                $decimals,
                (bool) ($source['UseVariablePresentation'] ?? true)
            );
            if (!$this->ReadPropertyBoolean('NormalizeValues') && $unit !== null && $unit !== $presentation['unit']) {
                throw new InvalidArgumentException('All Pie sources must use the same unit.');
            }
            $unit = $presentation['unit'];
            $ids[] = $id;
            $validated[] = [
                ...($this->ReadPropertyBoolean('NormalizeValues')
                    ? array_combine(['Minimum', 'Maximum'], EChartsNormalization::Range($source)) : []),
                'VariableID'       => $id,
                'Label'            => trim(is_string($source['Label'] ?? null) ? $source['Label'] : ''),
                'Unit'             => $presentation['unit'],
                'Decimals'         => $presentation['decimals'],
                'Color'            => $color,
                'InitiallyVisible' => $initiallyVisible
            ];
        }
        return $validated;
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            return $this->ErrorState('Configure valid Pie sources.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetPieData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['pie']['style'] = $this->ReadDesignStyle('IPSView');
            }
        } catch (DomainException) {
            return $this->ErrorState('Pie charts require non-negative values.');
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState($this->ReadAttributeString('LastError') === 'MODEL_TOO_LARGE'
                ? self::MODEL_TOO_LARGE_MESSAGE : 'The Pie values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'pie', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'pie', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Pie values could not be loaded.');
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

    private function RegisterDesignProperties(string $prefix = ''): void
    {
        $this->RegisterPropertyString($prefix . 'PieMode', 'pie');
        $this->RegisterPropertyString($prefix . 'SortOrder', 'configured');
        $this->RegisterPropertyString($prefix . 'EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyInteger($prefix . 'InnerRadiusPercent', 35);
        $this->RegisterPropertyInteger($prefix . 'OuterRadiusPercent', 70);
        $this->RegisterPropertyBoolean($prefix . 'ShowLegend', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowLabels', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowPercent', true);
        $this->RegisterPropertyString($prefix . 'LabelPosition', 'outside');
        $this->RegisterPropertyString($prefix . 'OutsideLabelAlignment', 'edge');
        $this->RegisterPropertyInteger($prefix . 'LeaderLineLength', 15);
        $this->RegisterPropertyInteger($prefix . 'LeaderLineLength2', 15);
        $this->RegisterPropertyInteger($prefix . 'LabelLineGap', 5);
        $this->RegisterPropertyInteger($prefix . 'LabelEdgeDistance', 8);
        foreach (['TitleFontSizePercent', 'LegendFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $this->RegisterPropertyInteger($prefix . $name, 100);
        }
        foreach (['TitleColor', 'LegendColor', 'LabelColor'] as $name) {
            $this->RegisterPropertyInteger($prefix . $name, -1);
        }
        $this->RegisterAnimationProperties($prefix, true, 350, 500);
    }

    /** @return array<string,string> */
    private function DesignPropertyNames(): array
    {
        return [
            'PieMode'                 => 'string', 'SortOrder' => 'string', 'EChartsTheme' => 'string',
            'InnerRadiusPercent'      => 'integer', 'OuterRadiusPercent' => 'integer',
            'ShowLegend'              => 'boolean', 'ShowLabels' => 'boolean', 'ShowPercent' => 'boolean',
            'LabelPosition'           => 'string',
            'OutsideLabelAlignment'   => 'string',
            'LeaderLineLength'        => 'integer', 'LeaderLineLength2' => 'integer',
            'LabelLineGap'            => 'integer', 'LabelEdgeDistance' => 'integer',
            'TitleFontSizePercent'    => 'integer', 'LegendFontSizePercent' => 'integer',
            'LabelFontSizePercent'    => 'integer',
            'TitleColor'              => 'integer', 'LegendColor' => 'integer', 'LabelColor' => 'integer',
            'AnimationEnabled'        => 'boolean', 'AnimationDuration' => 'integer',
            'AnimationDurationUpdate' => 'integer', 'AnimationEasing' => 'string',
            'AnimationEasingUpdate'   => 'string', 'AnimationDelay' => 'integer',
            'AnimationDelayUpdate'    => 'integer'
        ];
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!in_array($this->ReadPropertyString($prefix . 'PieMode'), self::MODES, true)
            || !in_array($this->ReadPropertyString($prefix . 'SortOrder'), self::SORT_ORDERS, true)
            || !in_array($this->ReadPropertyString($prefix . 'LabelPosition'), self::LABEL_POSITIONS, true)
            || !in_array($this->ReadPropertyString($prefix . 'OutsideLabelAlignment'), ['none', 'labelLine', 'edge'], true)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') > 70
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent') < 40
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent') > 100
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['TitleFontSizePercent', 'LegendFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $size = $this->ReadPropertyInteger($prefix . $name);
            if ($size < 50 || $size > 200) {
                return false;
            }
        }
        foreach (['LeaderLineLength', 'LeaderLineLength2', 'LabelLineGap', 'LabelEdgeDistance'] as $name) {
            if (!EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . $name))) {
                return false;
            }
        }
        foreach (['TitleColor', 'LegendColor', 'LabelColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        $style = [
            ...$this->ReadAnimationStyle($prefix),
            'mode'                  => $this->ReadPropertyString($prefix . 'PieMode'),
            'sortOrder'             => $this->ReadPropertyString($prefix . 'SortOrder'),
            'innerRadiusPercent'    => $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent'),
            'outerRadiusPercent'    => $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent'),
            'showLegend'            => $this->ReadPropertyBoolean($prefix . 'ShowLegend'),
            'showLabels'            => $this->ReadPropertyBoolean($prefix . 'ShowLabels'),
            'showPercent'           => $this->ReadPropertyBoolean($prefix . 'ShowPercent'),
            'labelPosition'         => $this->ReadPropertyString($prefix . 'LabelPosition'),
            'outsideLabelAlignment' => $this->ReadPropertyString($prefix . 'OutsideLabelAlignment'),
            'leaderLineLength'      => $this->ReadPropertyInteger($prefix . 'LeaderLineLength'),
            'leaderLineLength2'     => $this->ReadPropertyInteger($prefix . 'LeaderLineLength2'),
            'labelLineGap'          => $this->ReadPropertyInteger($prefix . 'LabelLineGap'),
            'labelEdgeDistance'     => $this->ReadPropertyInteger($prefix . 'LabelEdgeDistance')
        ];
        foreach (['TitleFontSizePercent', 'LegendFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $style[lcfirst($name)] = $this->ReadPropertyInteger($prefix . $name);
        }
        foreach (['TitleColor', 'LegendColor', 'LabelColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            $style[lcfirst($name)] = $value < 0 ? '' : EChartsAsset::ColorToHex($value);
        }
        return $style;
    }

    private function IPSViewThemeCSS(): string
    {
        $theme = $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme') : $this->ReadPropertyString('IPSViewEChartsTheme');
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($theme),
            '#echarts-pie-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }
}
