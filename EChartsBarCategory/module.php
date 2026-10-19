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
use SymconECharts\EChartsBarTypography;
use SymconECharts\EChartsCurrentSources;
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsLabelDesign;
use SymconECharts\EChartsNormalization;
use SymconECharts\EChartsPreviewSourceCount;
use SymconECharts\EChartsSvgImage;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsBarPreview.php';
require_once __DIR__ . '/../libs/EChartsBarPreviewForm.php';
require_once __DIR__ . '/../libs/EChartsPreviewSourceCount.php';
require_once __DIR__ . '/../libs/EChartsBarTypography.php';
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsNormalization.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsSvgImage.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarCategory extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

    use ConfigurationFormHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use EChartsBarTypography;
    use DataFlowHelper;
    use EChartsCurrentSources;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use EChartsTransientRefresh;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const MINIMUM_SOURCE_COUNT = 1;
    private const MAXIMUM_SOURCE_COUNT = 100;
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarCategory';
    private const MODES = ['simple', 'grouped', 'stacked'];
    private const ORIENTATIONS = ['vertical', 'horizontal'];
    private const SORT_ORDERS = ['configured', 'ascending', 'descending'];
    private const BAR_FILL_MODES = ['solid', 'svg', 'gradient'];
    private const TEXT_FONTS = [
        'TitleFontSizePercent' => 'Title size', 'LegendFontSizePercent' => 'Legend size',
        'AxisFontSizePercent'  => 'Axis label size', 'ValueFontSizePercent' => 'Value label size'
    ];
    private const TEXT_COLORS = [
        'TitleColor' => 'Title color', 'LegendColor' => 'Legend color',
        'AxisColor'  => 'Axis color', 'ValueColor' => 'Value label color', 'GridColor' => 'Grid color'
    ];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->RegisterTransientRefresh('ECBC');

            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyBoolean('NormalizeValues', false);
            $this->RegisterPropertyString('Title', '');
            $this->RegisterDesignProperties();
            $this->RegisterIPSViewHTMLPageProperties();
            $this->RegisterEChartsIPSViewTransport();
            $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
            $this->RegisterPropertyBoolean('IPSViewAdaptToBackground', false);
            $this->RegisterPropertyInteger('IPSViewBackgroundColor', EChartsIPSViewBackground::DEFAULT_COLOR);
            $this->RegisterPropertyInteger(
                'IPSViewBackgroundOpacityPercent',
                EChartsIPSViewBackground::DEFAULT_OPACITY_PERCENT
            );
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
            $this->MaintainIPSViewHTMLVariable(
                self::IPSVIEW_OUTPUT_IDENT,
                $this->Translate('Category Bar for IPSView'),
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
            $form = $this->LoadConfigurationForm();
            if (isset($form['elements']) && is_array($form['elements'])) {
                $form['elements'] = $this->WithAnimationFormControls($form['elements']);
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarSVG', 'BarSVGSizePercent'],
                    $this->ReadPropertyString('BarFillMode') === 'svg'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['IPSViewBarSVG', 'IPSViewBarSVGSizePercent'],
                    $this->ReadPropertyString('IPSViewBarFillMode') === 'svg'
                );
                $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
                $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECBC');
                $this->InsertIPSViewHTMLPageFormItems(
                    $form['elements'],
                    'Configure optional IPSView HTML output.',
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                );
            }

            $form = $this->WithBarTypographyForm($form, self::TEXT_FONTS, self::TEXT_COLORS);
            EChartsLabelDesign::InsertAfterRowContaining($form['elements'], 'ShowValues', [
                ['type' => 'RowLayout', 'items' => EChartsLabelDesign::CartesianControls()]
            ]);
            EChartsLabelDesign::InsertAfterRowContaining($form['elements'], 'IPSViewShowValues', [
                ['type' => 'RowLayout', 'items' => EChartsLabelDesign::CartesianControls('IPSView')]
            ]);
            foreach (['', 'IPSView'] as $prefix) {
                EChartsLabelDesign::InsertAfterRowContaining($form['elements'], $prefix . 'BarFillMode', $this->GradientFormRows($prefix));
            }
            $form = $this->WithIPSViewDesignFormState($form, 'ECBC');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Bar layout and order', 'start' => 'BarMode'],
                ['caption' => 'Labels and geometry', 'start' => 'ShowValues'],
                ['caption' => 'Bar fill and pattern', 'start' => 'BarFillMode'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'NormalizeValues', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                    'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys($this->DesignPropertyNames()),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys($this->DesignPropertyNames()))
            );

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECBC', $fields));
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function UpdateBarDesignForm(bool $IPSView, string $BarFillMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $prefix = $IPSView ? 'IPSView' : '';
            foreach (['ColorMinimum', 'ColorMaximum', 'LowColor', 'MiddleColor', 'HighColor'] as $name) {
                $this->UpdateFormField($prefix . $name, 'visible', $BarFillMode === 'gradient');
            }
            $this->UpdateFormField($prefix . 'BarSVG', 'visible', $BarFillMode === 'svg');
            $this->UpdateFormField($prefix . 'BarSVGSizePercent', 'visible', $BarFillMode === 'svg');
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
            foreach ($this->DesignPropertyNames() as $name => $type) {
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

    public function GetBarData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $configurationError = $this->GetConfigurationError();
            if ($configurationError !== null) {
                $this->WriteAttributeString('LastError', $configurationError['Message']);
                $this->DebugEChartsSetStatus($configurationError['Status']);
                throw new RuntimeException($configurationError['Message']);
            }
            if (!$this->HasActiveParent()) {
                $this->DebugEChartsSetStatus(self::STATUS_PARENT_MISSING);
                throw new RuntimeException('No active EChartsGateway is connected.');
            }

            $sources = $this->GetValidatedSources();
            $normalized = $this->ReadPropertyBoolean('NormalizeValues');
            $pairCounts = [];
            foreach ($sources as $source) {
                $series = $source['Series'] !== '' ? $source['Series'] : IPS_GetName($source['VariableID']);
                $pair = json_encode([$source['Category'], $series], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $pairCounts[$pair] = ($pairCounts[$pair] ?? 0) + 1;
            }
            $items = [];
            foreach ($sources as $index => $source) {
                $current = $this->ReadCurrentSource($source['VariableID']);
                $variableName = IPS_GetName($source['VariableID']);
                $series = $source['Series'] !== '' ? $source['Series'] : $variableName;
                $pair = json_encode([$source['Category'], $series], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $duplicateLabel = $pairCounts[$pair] > 1;
                $items[] = [
                    'id'         => 'variable-' . $source['VariableID'],
                    'variableID' => $source['VariableID'],
                    'category'   => $source['Category'],
                    'seriesKey'  => $duplicateLabel ? 'variable-' . $source['VariableID'] : 'series-' . $series,
                    'series'     => $series,
                    'value'      => $normalized ? EChartsNormalization::Value((float) $current['Value'], $source) : $current['Value'],
                    ...($normalized ? [
                        ...EChartsNormalization::Metadata((float) $current['Value'], $source),
                        'unit' => $source['Unit'], 'decimals' => $source['Decimals']
                    ] : []),
                    'timestamp'  => $current['Timestamp'],
                    'color'      => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color']),
                    'order'      => $index
                ];
            }
            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);

            return $debugFlowResult = json_encode([
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'category',
                'theme'         => $this->ReadPropertyString('EChartsTheme'),
                'bar'           => [
                    'title'       => $this->ReadPropertyString('Title'),
                    'mode'        => $this->ReadPropertyString('BarMode'),
                    'orientation' => $this->ReadPropertyString('Orientation'),
                    'sortOrder'   => $this->ReadPropertyString('SortOrder'),
                    'unit'        => $normalized ? '' : $sources[0]['Unit'],
                    'normalized'  => $normalized,
                    'decimals'    => max(array_column($sources, 'Decimals')),
                    'style'       => $this->ReadDesignStyle()
                ],
                'items'         => $items
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetBarDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $configurationError = $this->GetConfigurationError();

            return $debugFlowResult = json_encode([
                'valid'       => $configurationError === null,
                'sourceCount' => count($this->ConfiguredVariableIDs()),
                'lastError'   => $configurationError['Message'] ?? $this->ReadAttributeString('LastError')
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
            return $debugFlowResult = $this->RenderBarHTMLPage(false);
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
            return $debugFlowResult = $this->RenderBarHTMLPage(true);
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

    /** Retries a failed current-value read without resetting the visualization. */
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

    /** @param array<int, mixed> $Data */
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
        $data = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Category', true, self::MAXIMUM_SOURCE_COUNT);
        $items = $data['items'];
        $normalized = (bool) ($values['NormalizeValues'] ?? $this->ReadPropertyBoolean('NormalizeValues'));
        if ($normalized) {
            foreach ($items as &$item) {
                try {
                    $item = [...$item, ...EChartsNormalization::Metadata((float) $item['value'], $item)];
                    $item['value'] = EChartsNormalization::Value((float) $item['value'], $item);
                } catch (InvalidArgumentException) {
                    return EChartsBarPreview::Open(
                        $palette,
                        $this->Translate('Invalid normalization range.'),
                        false,
                        $ipsView,
                        $values,
                        $this->Translate('Example data'),
                        $this->Translate('Chart preview')
                    ) . '</svg>';
                }
            }
            unset($item);
        }
        $previewDecimals = max(array_column($data['items'], 'decimals'));
        $previewLanguage = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        $mode = $this->BarPreviewValue($values, 'BarMode', 'string', $ipsView);
        $orientation = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView);
        $sort = $this->BarPreviewValue($values, 'SortOrder', 'string', $ipsView);
        if ($data['sample'] && $mode !== 'simple') {
            foreach ($items as &$item) {
                $item['Series'] = $item['label'];
                $item['Category'] = $this->Translate('Example category');
                $item['label'] = $this->Translate('Example category');
            }
            unset($item);
        }
        if ($mode === 'simple' && ($sort === 'ascending' || $sort === 'descending')) {
            usort($items, static fn (array $a, array $b): int => $sort === 'ascending'
                ? $a['value'] <=> $b['value'] : $b['value'] <=> $a['value']);
        } elseif ($mode !== 'simple' && ($sort === 'ascending' || $sort === 'descending')) {
            $categoryTotals = [];
            foreach ($items as $item) {
                $category = trim((string) ($item['Category'] ?? ''));
                $categoryTotals[$category] = ($categoryTotals[$category] ?? 0.0) + $item['value'];
            }
            usort($items, static function (array $a, array $b) use ($categoryTotals, $sort): int
            {
                $left = $categoryTotals[trim((string) ($a['Category'] ?? ''))];
                $right = $categoryTotals[trim((string) ($b['Category'] ?? ''))];

                return $sort === 'ascending' ? $left <=> $right : $right <=> $left;
            });
        }
        $horizontal = $orientation === 'horizontal';
        $barRatio = max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView))) / 100;
        $previewAxisSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100)));
        $previewValueSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView) / 100)));
        $previewValueSpan = $previewValueSize * 1.8;
        if (!$horizontal && $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView)) {
            foreach ($items as $item) {
                $previewValueSpan = max($previewValueSpan, mb_strlen(EChartsBarPreview::FormatValue(
                    $item['originalValue'] ?? $item['value'],
                    $normalized ? $item['decimals'] : $previewDecimals,
                    $item['unit'],
                    $previewLanguage
                )) * $previewValueSize * 0.65);
            }
        }
        $previewSpan = max(
            ($horizontal ? 40 : 80) * $barRatio,
            $previewAxisSize * ($horizontal ? 1.5 : 2.4) + 4,
            $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView) ? $previewValueSpan + 4 : 0
        );
        $items = array_slice($items, 0, max(1, (int) floor(($horizontal ? 210 : 568) / $previewSpan)));
        $svg = EChartsBarPreview::Open(
            $palette,
            (string) ($values['Title'] ?? $this->ReadPropertyString('Title')),
            $data['sample'],
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Chart preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $legendColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LegendColor', 'integer', $ipsView), $palette['text']);
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $valueColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['track']);
        $legendSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'LegendFontSizePercent', 'integer', $ipsView) / 100)));
        $axisSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100)));
        $valueSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView) / 100)));
        $svg .= '<text x="696" y="55" text-anchor="end" fill="' . EChartsBarPreview::E($palette['muted'])
            . '" font-size="12">' . EChartsBarPreview::E(ucfirst($mode)) . '</text>';
        if ($normalized) {
            $svg .= '<text x="24" y="55" fill="' . EChartsBarPreview::E($palette['muted']) . '" font-size="11">'
                . EChartsBarPreview::E($this->Translate('Normalized 0–100 per source')) . '</text>';
        }
        if ($mode !== 'simple') {
            foreach (array_slice($items, 0, 6) as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$index % count($palette['seriesColors'])];
                $series = trim(is_string($item['Series'] ?? null) ? $item['Series'] : '');
                if ($series === '') {
                    $series = isset($item['VariableID']) ? IPS_GetName((int) $item['VariableID']) : $item['label'];
                }
                $legendX = 80 + ($index % 3) * 195;
                $legendY = 58 + intdiv($index, 3) * 18;
                $svg .= '<rect x="' . $legendX . '" y="' . ($legendY - 9) . '" width="12" height="12" fill="'
                    . EChartsBarPreview::E($color) . '"/>';
                $svg .= '<text x="' . ($legendX + 17) . '" y="' . $legendY . '" fill="'
                    . EChartsBarPreview::E($legendColor) . '" font-size="' . $legendSize . '">'
                    . EChartsBarPreview::E(mb_strimwidth($series, 0, 20, '…')) . '</text>';
            }
        }
        $showGrid = $this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $valueLabelPosition = $this->BarPreviewValue($values, 'ValueLabelPosition', 'string', $ipsView);
        $valueLabelDistance = $this->BarPreviewValue($values, 'ValueLabelDistance', 'integer', $ipsView);
        $barWidth = max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView)));
        $rounded = $this->BarPreviewValue($values, 'RoundedBars', 'boolean', $ipsView) ? 5 : 0;
        $fillMode = $this->BarPreviewValue($values, 'BarFillMode', 'string', $ipsView);
        if ($fillMode === 'svg') {
            $patternSize = EChartsBarPreview::PatternSize(
                $this->BarPreviewValue($values, 'BarSVGSizePercent', 'integer', $ipsView)
            );
            $svg .= '<defs>';
            foreach ($items as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$index % count($palette['seriesColors'])];
                $svg .= '<pattern id="bar-category-preview-' . $index . '" width="' . EChartsBarPreview::N($patternSize)
                    . '" height="' . EChartsBarPreview::N($patternSize) . '" patternUnits="userSpaceOnUse">'
                    . '<rect width="100%" height="100%" fill="' . EChartsBarPreview::E($color) . '"/>'
                    . '<path d="M 0 ' . EChartsBarPreview::N($patternSize) . ' L ' . EChartsBarPreview::N($patternSize)
                    . ' 0" stroke="' . EChartsBarPreview::E($palette['text'])
                    . '" stroke-opacity="0.5"/></pattern>';
            }
            $svg .= '</defs><text x="696" y="71" text-anchor="end" fill="'
                . EChartsBarPreview::E($palette['muted']) . '" font-size="10">'
                . EChartsBarPreview::E($this->Translate('SVG pattern (schematic)')) . '</text>';
        }
        $groups = [];
        foreach ($items as $index => $item) {
            $category = trim(is_string($item['Category'] ?? null) ? $item['Category'] : '');
            $key = $mode === 'simple' ? (string) $index : ($category !== '' ? $category : $this->Translate('Shared group'));
            $groups[$key][] = ['item' => $item, 'index' => $index];
        }
        if ($mode !== 'simple' && ($sort === 'ascending' || $sort === 'descending')) {
            uasort($groups, static function (array $left, array $right) use ($sort): int
            {
                $leftTotal = array_sum(array_map(static fn (array $entry): float => $entry['item']['value'], $left));
                $rightTotal = array_sum(array_map(static fn (array $entry): float => $entry['item']['value'], $right));

                return $sort === 'ascending' ? $leftTotal <=> $rightTotal : $rightTotal <=> $leftTotal;
            });
        }
        $positiveMax = $normalized ? 100.0 : 1.0;
        $negativeMax = 0.0;
        foreach ($groups as $group) {
            $positive = array_map(static fn (array $entry): float => max(0.0, $entry['item']['value']), $group);
            $negative = array_map(static fn (array $entry): float => max(0.0, -$entry['item']['value']), $group);
            $positiveMax = max($positiveMax, $mode === 'stacked' ? array_sum($positive) : max($positive));
            $negativeMax = max($negativeMax, $mode === 'stacked' ? array_sum($negative) : max($negative));
        }
        $range = $positiveMax + $negativeMax;
        $rampMinimum = $this->BarPreviewValue($values, 'ColorMinimum', 'float', $ipsView);
        $rampMaximum = $this->BarPreviewValue($values, 'ColorMaximum', 'float', $ipsView);
        $rampColors = array_map(fn (string $name): int => $this->BarPreviewValue($values, $name, 'integer', $ipsView), ['LowColor', 'MiddleColor', 'HighColor']);
        $ramp = static fn (float $value): string => EChartsBarPreview::RampColor($value, $rampMinimum, $rampMaximum, $rampColors);
        if ($fillMode === 'gradient') {
            $points = [-$negativeMax];
            foreach ([$rampMinimum, ($rampMinimum + $rampMaximum) / 2, $rampMaximum] as $point) {
                if (is_finite($point) && $point > -$negativeMax && $point < $positiveMax) {
                    $points[] = $point;
                }
            }
            $points[] = $positiveMax;
            sort($points);
            $svg .= '<defs><linearGradient id="bar-category-value-gradient" gradientUnits="userSpaceOnUse" '
                . ($orientation === 'horizontal' ? 'x1="100" y1="0" x2="668" y2="0"' : 'x1="0" y1="295" x2="0" y2="87"') . '>';
            foreach ($points as $point) {
                $svg .= '<stop offset="' . EChartsBarPreview::N(($point + $negativeMax) / $range) . '" stop-color="' . $ramp($point) . '"/>';
            }
            $svg .= '</linearGradient></defs>';
            if ($mode === 'stacked' && $valueLabelPosition === 'auto') {
                $valueLabelPosition = 'inside';
            }
        }
        $zero = $orientation === 'horizontal'
            ? 100 + 568 * $negativeMax / $range
            : 87 + 208 * $positiveMax / $range;
        if ($showGrid) {
            for ($step = 0; $step <= 4; ++$step) {
                $position = $orientation === 'horizontal' ? 100 + $step * 142 : 295 - $step * 52;
                $svg .= $orientation === 'horizontal'
                    ? '<line x1="' . $position . '" y1="75" x2="' . $position . '" y2="295" stroke="' . EChartsBarPreview::E($gridColor) . '"/>'
                    : '<line x1="100" y1="' . $position . '" x2="668" y2="' . $position . '" stroke="' . EChartsBarPreview::E($gridColor) . '"/>';
            }
        }
        $svg .= $orientation === 'horizontal'
            ? '<line x1="' . EChartsBarPreview::N($zero) . '" y1="75" x2="' . EChartsBarPreview::N($zero)
                . '" y2="295" stroke="' . EChartsBarPreview::E($palette['border']) . '"/>'
            : '<line x1="100" y1="' . EChartsBarPreview::N($zero) . '" x2="668" y2="'
                . EChartsBarPreview::N($zero) . '" stroke="' . EChartsBarPreview::E($palette['border']) . '"/>';
        $groupCount = count($groups);
        $span = min(
            ($horizontal ? 210 : 568) / $groupCount,
            $previewSpan * ($mode === 'grouped' ? max(array_map('count', $groups)) : 1)
        );
        $svg .= '<g data-preview-category-span="' . EChartsBarPreview::N($span) . '">';
        $groupIndex = 0;
        foreach ($groups as $key => $group) {
            $label = $mode === 'simple' ? $group[0]['item']['label'] : $key;
            $center = ($orientation === 'horizontal' ? 79 : 100) + ($groupIndex + 0.5) * $span;
            $svg .= $orientation === 'horizontal'
                ? '<text x="92" y="' . EChartsBarPreview::N($center + 4) . '" text-anchor="end" fill="' . EChartsBarPreview::E($axisColor)
                    . '" font-size="' . $axisSize . '"><title>' . EChartsBarPreview::E($label) . '</title>' . EChartsBarPreview::E(mb_strimwidth($label, 0, 13, '…')) . '</text>'
                : '<text x="' . EChartsBarPreview::N($center) . '" y="315" text-anchor="middle" fill="' . EChartsBarPreview::E($axisColor)
                    . '" font-size="' . $axisSize . '"><title>' . EChartsBarPreview::E($label) . '</title>' . EChartsBarPreview::E(mb_strimwidth($label, 0, max(1, min(12, (int) floor(($span - 8) / ($axisSize * 0.65)))), '…')) . '</text>';
            $positiveOffset = 0.0;
            $negativeOffset = 0.0;
            foreach ($group as $entryIndex => $entry) {
                $item = $entry['item'];
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$entry['index'] % count($palette['seriesColors'])];
                $fill = $fillMode === 'svg' ? 'url(#bar-category-preview-' . $entry['index'] . ')' : $color;
                if ($fillMode === 'gradient') {
                    $fill = 'url(#bar-category-value-gradient)';
                }
                $size = ($orientation === 'horizontal' ? 568 : 208) * abs($item['value']) / $range;
                $subCount = $mode === 'stacked' ? 1 : count($group);
                $thickness = max(1, $span * $barWidth / 100 / ($subCount + 0.3 * ($subCount - 1)));
                $cross = $center - $span * $barWidth / 200 + ($mode === 'stacked' ? 0 : $entryIndex * $thickness * 1.3);
                $offset = $mode === 'stacked'
                    ? ($item['value'] >= 0 ? $positiveOffset : $negativeOffset) : 0;
                if ($orientation === 'horizontal') {
                    $x = $item['value'] >= 0 ? $zero + $offset : $zero - $offset - $size;
                    $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($cross)
                        . '" width="' . EChartsBarPreview::N($size) . '" height="' . EChartsBarPreview::N($thickness)
                        . '" rx="' . $rounded . '" fill="' . EChartsBarPreview::E($fill) . '"/>';
                    $valueX = $item['value'] >= 0 ? $x + $size + 6 : $x - 6;
                    $valueY = $cross + $thickness / 2 + 4;
                } else {
                    $top = $item['value'] >= 0 ? $zero - $offset - $size : $zero + $offset;
                    $svg .= '<rect x="' . EChartsBarPreview::N($cross) . '" y="' . EChartsBarPreview::N($top)
                        . '" width="' . EChartsBarPreview::N($thickness) . '" height="' . EChartsBarPreview::N($size)
                        . '" rx="' . $rounded . '" fill="' . EChartsBarPreview::E($fill) . '"/>';
                    $valueX = $cross + $thickness / 2;
                    $valueY = $item['value'] >= 0 ? $top - 5 : $top + $size + 13;
                }
                $valueAnchor = 'middle';
                if ($showValues && ($valueLabelPosition !== 'auto' || $valueLabelDistance !== 5)) {
                    $position = $valueLabelPosition !== 'auto' ? $valueLabelPosition
                        : ($orientation === 'horizontal' ? ($item['value'] >= 0 ? 'right' : 'left')
                            : ($item['value'] >= 0 ? 'top' : 'bottom'));
                    $placement = $orientation === 'horizontal'
                        ? EChartsLabelDesign::PreviewRect($x, $cross, $size, $thickness, $position, $valueLabelDistance)
                        : EChartsLabelDesign::PreviewRect($cross, $top, $thickness, $size, $position, $valueLabelDistance);
                    $valueX = $placement['x'];
                    $valueY = $placement['y'];
                    $valueAnchor = $placement['anchor'];
                }
                if ($showValues && ($mode !== 'grouped' || $thickness >= ($horizontal ? $valueSize * 1.2 : $previewValueSpan))) {
                    $textColor = $valueColor;
                    if ($fillMode === 'gradient' && str_starts_with($valueLabelPosition, 'inside')
                        && $this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView) < 0) {
                        $midpoint = ($mode === 'stacked' ? ($item['value'] >= 0 ? $positiveOffset : -$negativeOffset) * $range / ($orientation === 'horizontal' ? 568 : 208) : 0) + $item['value'] / 2;
                        $textColor = $this->GradientPreviewTextColor($ramp($midpoint), $palette['text'], $palette['background']);
                    }
                    $svg .= '<text x="' . EChartsBarPreview::N($valueX) . '" y="' . EChartsBarPreview::N($valueY)
                        . '" text-anchor="' . $valueAnchor . '" fill="' . EChartsBarPreview::E($textColor) . '" font-size="' . $valueSize . '">'
                        . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                            $item['originalValue'] ?? $item['value'],
                            $normalized ? $item['decimals'] : $previewDecimals,
                            $item['unit'],
                            $previewLanguage
                        )) . '</text>';
                }
                if ($item['value'] >= 0) {
                    $positiveOffset += $size;
                } else {
                    $negativeOffset += $size;
                }
            }
            ++$groupIndex;
        }

        return EChartsPreviewSourceCount::Append(
            $svg . '</g></svg>',
            count($items),
            count($data['items']),
            $this->Translate('Preview shows %d of %d sources'),
            $palette['muted'],
            696,
            351
        );
    }

    private function RenderBarHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'scriptAsset'        => 'app.min.js',
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Category Bar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-category-root', 'echarts-bar-category'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure valid Category Bar sources.'         => $this->Translate(
                    'Configure valid Category Bar sources.'
                ),
                'Connect an active EChartsGateway.'            => $this->Translate('Connect an active EChartsGateway.'),
                'The Category Bar values could not be loaded.' => $this->Translate(
                    'The Category Bar values could not be loaded.'
                ),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                ),
                'Normalized 0–100 per source' => $this->Translate('Normalized 0–100 per source'),
                'Normalized'                  => $this->Translate('Normalized'),
                'Range'                       => $this->Translate('Range')
            ],
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::BarCategoryJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::CategoryNavigationJavaScript(),
                '{{ECHARTS_PATTERN_SCRIPT}}'   => EChartsAsset::PatternJavaScript(),
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
        $this->SetSummary('');
        $this->SynchronizeSourceReferences();
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->DebugEChartsSetStatus($configurationError['Status']);

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

    /** @return array{Status: int, Message: string}|null */
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
            )
        ) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Category Bar design is invalid.'];
        }

        return null;
    }

    /** @return list<array{VariableID:int, Category:string, Series:string, Unit:string, Decimals:int, Color:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The Category Bar sources are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources)
            || count($sources) < self::MINIMUM_SOURCE_COUNT
            || count($sources) > self::MAXIMUM_SOURCE_COUNT
        ) {
            throw new InvalidArgumentException('Configure 1 to 100 unique numeric sources with one common unit.');
        }

        $validated = [];
        $variableIDs = [];
        $commonUnit = null;
        $normalized = $this->ReadPropertyBoolean('NormalizeValues');
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Category Bar source must be an object.');
            }
            $variableID = $source['VariableID'] ?? null;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || in_array($variableID, $variableIDs, true)
            ) {
                throw new InvalidArgumentException('Configure 1 to 100 unique numeric sources with one common unit.');
            }
            $variable = IPS_GetVariable($variableID);
            if (!is_array($variable) || !in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Category Bar sources must be numeric variables.');
            }
            $decimals = $source['Decimals'] ?? 1;
            $color = $source['Color'] ?? -1;
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF
            ) {
                throw new InvalidArgumentException('A Category Bar source contains invalid display settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                is_string($source['Unit'] ?? null) ? $source['Unit'] : '',
                $decimals,
                (bool) ($source['UseVariablePresentation'] ?? true)
            );
            if ($commonUnit === null) {
                $commonUnit = $presentation['unit'];
            } elseif (!$normalized && $presentation['unit'] !== $commonUnit) {
                throw new InvalidArgumentException('All Category Bar sources must use the same unit.');
            }
            $variableIDs[] = $variableID;
            $hasExplicitCategory = array_key_exists('Category', $source)
                && is_string($source['Category']);
            $legacyLabel = trim(is_string($source['Label'] ?? null) ? $source['Label'] : '');
            $category = $hasExplicitCategory ? trim($source['Category']) : $legacyLabel;
            $series = trim(is_string($source['Series'] ?? null) ? $source['Series'] : '');
            if ($series === '' && $hasExplicitCategory) {
                $series = $legacyLabel;
            }
            $validated[] = [
                'VariableID' => $variableID,
                'Category'   => $category,
                'Series'     => $series,
                'Unit'       => $presentation['unit'],
                'Decimals'   => $presentation['decimals'],
                'Color'      => $color,
                ...($normalized ? array_combine(['Minimum', 'Maximum'], EChartsNormalization::Range($source)) : [])
            ];
        }

        return $validated;
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'category',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Configure valid Category Bar sources.'
            ];
        }
        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'category',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }
        try {
            $chart = json_decode($this->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['bar']['mode'] = $this->ReadPropertyString('IPSViewBarMode');
                $chart['bar']['orientation'] = $this->ReadPropertyString('IPSViewOrientation');
                $chart['bar']['sortOrder'] = $this->ReadPropertyString('IPSViewSortOrder');
                $chart['bar']['style'] = $this->ReadDesignStyle('IPSView');
            }
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);

            return [
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'category',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Category Bar values could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'category',
            'status'        => 'ready',
            'chart'         => $chart,
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
                $this->ScheduleTransientRefresh($states, 'The Category Bar values could not be loaded.');
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

    private function GradientPreviewTextColor(string $background, string $first, string $second): string
    {
        // Match echarts-design.js: choose the theme candidate with the higher WCAG contrast.
        $luminance = static function (string $hex): float
        {
            $channels = array_map(static function (int $offset) use ($hex): float
            {
                $value = hexdec(substr($hex, $offset, 2)) / 255;
                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            }, [1, 3, 5]);
            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };
        $base = $luminance($background);
        $contrast = static fn (float $candidate): float => (max($base, $candidate) + 0.05) / (min($base, $candidate) + 0.05);
        return $contrast($luminance($first)) >= $contrast($luminance($second)) ? $first : $second;
    }

    /** @return list<array<string,mixed>> */
    private function GradientFormRows(string $prefix): array
    {
        $visible = $this->ReadPropertyString($prefix . 'BarFillMode') === 'gradient';
        $ranges = [];
        foreach (['ColorMinimum' => 'Color range minimum', 'ColorMaximum' => 'Color range maximum'] as $name => $caption) {
            $ranges[] = ['type' => 'NumberSpinner', 'name' => $prefix . $name, 'caption' => $caption,
                'minimum'       => -1.0e12, 'maximum' => 1.0e12, 'digits' => 6, 'width' => '230px', 'visible' => $visible];
        }
        $colors = [];
        foreach (['LowColor' => 'Low value color', 'MiddleColor' => 'Middle value color', 'HighColor' => 'High value color'] as $name => $caption) {
            $colors[] = ['type' => 'SelectColor', 'name' => $prefix . $name, 'caption' => $caption,
                'width'         => '180px', 'visible' => $visible];
        }
        return [['type' => 'RowLayout', 'items' => $ranges], ['type' => 'RowLayout', 'items' => $colors]];
    }

    private function RegisterDesignProperties(string $prefix = ''): void
    {
        $this->RegisterPropertyString($prefix . 'BarMode', 'simple');
        $this->RegisterPropertyString($prefix . 'Orientation', 'vertical');
        $this->RegisterPropertyString($prefix . 'SortOrder', 'configured');
        $this->RegisterPropertyString($prefix . 'EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyString($prefix . 'ValueLabelPosition', 'auto');
        $this->RegisterPropertyInteger($prefix . 'ValueLabelDistance', 5);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyBoolean($prefix . 'RoundedBars', false);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 70);
        $this->RegisterPropertyString($prefix . 'BarFillMode', 'solid');
        $this->RegisterPropertyString($prefix . 'BarSVG', '');
        $this->RegisterPropertyInteger($prefix . 'BarSVGSizePercent', 100);
        $this->RegisterPropertyFloat($prefix . 'ColorMinimum', 0.0);
        $this->RegisterPropertyFloat($prefix . 'ColorMaximum', 100.0);
        $this->RegisterPropertyInteger($prefix . 'LowColor', 0xFF0000);
        $this->RegisterPropertyInteger($prefix . 'MiddleColor', 0xFFFF00);
        $this->RegisterPropertyInteger($prefix . 'HighColor', 0x00FF00);
        $this->RegisterBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix);
        $this->RegisterAnimationProperties($prefix, true, 350, 500);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'BarMode'                 => 'string',
            'Orientation'             => 'string',
            'SortOrder'               => 'string',
            'EChartsTheme'            => 'string',
            'ShowValues'              => 'boolean',
            'ValueLabelPosition'      => 'string',
            'ValueLabelDistance'      => 'integer',
            'ShowGrid'                => 'boolean',
            'RoundedBars'             => 'boolean',
            'BarWidthPercent'         => 'integer',
            'BarFillMode'             => 'string',
            'BarSVG'                  => 'string',
            'BarSVGSizePercent'       => 'integer',
            'ColorMinimum'            => 'float',
            'ColorMaximum'            => 'float',
            'LowColor'                => 'integer',
            'MiddleColor'             => 'integer',
            'HighColor'               => 'integer',
            ...array_fill_keys(array_keys(self::TEXT_FONTS), 'integer'),
            ...array_fill_keys(array_keys(self::TEXT_COLORS), 'integer'),
            'AnimationEnabled'        => 'boolean',
            'AnimationDuration'       => 'integer',
            'AnimationDurationUpdate' => 'integer',
            'AnimationEasing'         => 'string',
            'AnimationEasingUpdate'   => 'string',
            'AnimationDelay'          => 'integer',
            'AnimationDelayUpdate'    => 'integer'
        ];
    }

    private function IsValidDesign(string $prefix): bool
    {
        $minimum = $this->ReadPropertyFloat($prefix . 'ColorMinimum');
        $maximum = $this->ReadPropertyFloat($prefix . 'ColorMaximum');
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum < -1.0e12 || $maximum > 1.0e12 || $minimum >= $maximum) {
            return false;
        }
        foreach (['LowColor', 'MiddleColor', 'HighColor'] as $name) {
            if ($this->ReadPropertyInteger($prefix . $name) < 0 || $this->ReadPropertyInteger($prefix . $name) > 0xFFFFFF) {
                return false;
            }
        }
        return in_array($this->ReadPropertyString($prefix . 'BarMode'), self::MODES, true)
            && in_array($this->ReadPropertyString($prefix . 'Orientation'), self::ORIENTATIONS, true)
            && in_array($this->ReadPropertyString($prefix . 'SortOrder'), self::SORT_ORDERS, true)
            && EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            && EChartsLabelDesign::IsValidPosition($this->ReadPropertyString($prefix . 'ValueLabelPosition'), EChartsLabelDesign::CARTESIAN_POSITIONS)
            && EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . 'ValueLabelDistance'))
            && $this->ReadPropertyInteger($prefix . 'BarWidthPercent') >= 20
            && $this->ReadPropertyInteger($prefix . 'BarWidthPercent') <= 100
            && in_array($this->ReadPropertyString($prefix . 'BarFillMode'), self::BAR_FILL_MODES, true)
            && $this->ReadPropertyInteger($prefix . 'BarSVGSizePercent') >= 25
            && $this->ReadPropertyInteger($prefix . 'BarSVGSizePercent') <= 400
            && $this->BarTypographyIsValid(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix)
            && $this->AnimationIsValid($prefix)
            && $this->IsValidBarSVG($prefix);
    }

    private function IsValidBarSVG(string $prefix): bool
    {
        if ($this->ReadPropertyString($prefix . 'BarFillMode') !== 'svg') {
            return true;
        }
        try {
            EChartsSvgImage::Import($this->ReadPropertyString($prefix . 'BarSVG'));
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /** @return array<string, bool|int|string|float> */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        $style = [
            ...$this->ReadAnimationStyle($prefix),
            'showValues'         => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'valueLabelPosition' => $this->ReadPropertyString($prefix . 'ValueLabelPosition'),
            'valueLabelDistance' => $this->ReadPropertyInteger($prefix . 'ValueLabelDistance'),
            'showGrid'           => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'roundedBars'        => $this->ReadPropertyBoolean($prefix . 'RoundedBars'),
            'barWidthPercent'    => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'barFillMode'        => $this->ReadPropertyString($prefix . 'BarFillMode'),
            'colorMinimum'       => $this->ReadPropertyFloat($prefix . 'ColorMinimum'),
            'colorMaximum'       => $this->ReadPropertyFloat($prefix . 'ColorMaximum'),
            'lowColor'           => EChartsAsset::ColorToHex($this->ReadPropertyInteger($prefix . 'LowColor')),
            'middleColor'        => EChartsAsset::ColorToHex($this->ReadPropertyInteger($prefix . 'MiddleColor')),
            'highColor'          => EChartsAsset::ColorToHex($this->ReadPropertyInteger($prefix . 'HighColor')),
            ...$this->ReadBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix)
        ];
        if ($style['barFillMode'] === 'svg') {
            $image = EChartsSvgImage::Import($this->ReadPropertyString($prefix . 'BarSVG'));
            $style['barPatternImage'] = $image['dataUri'];
            $style['barPatternAspectRatio'] = $image['width'] / $image['height'];
            $style['barSVGSizePercent'] = $this->ReadPropertyInteger($prefix . 'BarSVGSizePercent');
        }

        return $style;
    }

    private function EffectiveIPSViewTheme(): string
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveIPSViewTheme()),
            '#echarts-bar-category-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }
}
