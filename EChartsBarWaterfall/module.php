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
use SymconECharts\EChartsPreviewSourceCount;
use SymconECharts\EChartsSourceIdentity;
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
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarWaterfall extends IPSModuleStrict
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarWaterfall';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const TEXT_FONTS = [
        'TitleFontSizePercent' => 'Title size', 'AxisFontSizePercent' => 'Axis label size',
        'ValueFontSizePercent' => 'Value label size'
    ];
    private const TEXT_COLORS = [
        'TitleColor' => 'Title color', 'AxisColor' => 'Axis color',
        'ValueColor' => 'Value label color', 'GridColor' => 'Grid color'
    ];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->RegisterTransientRefresh('ECBW');

            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('TotalLabel', '');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Waterfall for IPSView'), 90);
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
                $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
                $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECBW');
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
            $form = $this->WithIPSViewDesignFormState($form, 'ECBW');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Labels and geometry', 'start' => 'ShowValues'],
                ['caption' => 'Step colors', 'start' => 'StartColor'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'TotalLabel', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                    'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys($this->DesignPropertyNames()),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys($this->DesignPropertyNames()))
            );

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECBW', $fields));
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

    public function GetWaterfallData(): string
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
            $labels = EChartsSourceIdentity::LabelsForSources($sources);
            $steps = [];
            $total = 0.0;
            foreach ($sources as $index => $source) {
                $current = $this->ReadCurrentSource($source['VariableID']);
                $value = $current['Value'];
                $before = $total;
                $total = $index === 0 ? $value : $total + $value;
                if (!is_finite($total)) {
                    $this->WriteAttributeString('LastError', 'The Waterfall total is not finite.');
                    $this->DebugEChartsSetStatus(self::STATUS_GATEWAY_FAILED);
                    throw new RuntimeException('The Waterfall total is not finite.');
                }
                $steps[] = [
                    'id'        => 'variable-' . $source['VariableID'],
                    'label'     => $labels[$source['VariableID']],
                    'kind'      => $index === 0 ? 'start' : 'change',
                    'change'    => $value,
                    'before'    => $before,
                    'after'     => $total,
                    'timestamp' => $current['Timestamp']
                ];
            }
            $steps[] = [
                'id'     => 'total',
                'label'  => trim($this->ReadPropertyString('TotalLabel')) !== ''
                    ? trim($this->ReadPropertyString('TotalLabel')) : $this->Translate('Total'),
                'kind'   => 'total',
                'change' => $total,
                'before' => 0.0,
                'after'  => $total
            ];

            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);

            return $debugFlowResult = json_encode([
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'waterfall',
                'theme'         => $this->ReadPropertyString('EChartsTheme'),
                'bar'           => [
                    'title'    => $this->ReadPropertyString('Title'),
                    'unit'     => $sources[0]['Unit'],
                    'decimals' => max(array_column($sources, 'Decimals')),
                    'style'    => $this->ReadDesignStyle()
                ],
                'steps'         => $steps
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
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
            return $debugFlowResult = $this->RenderWaterfallHTMLPage(false);
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
            return $debugFlowResult = $this->RenderWaterfallHTMLPage(true);
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
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $data = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values);
        $items = array_slice($data['items'], 0, 5);
        $previewDecimals = max(array_column($data['items'], 'decimals'));
        $previewUnit = $data['items'][0]['unit'];
        $previewLanguage = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        if ($data['sample']) {
            $items[0]['value'] = 18.0;
            $items[1]['value'] = -5.5;
            $items[2]['value'] = 8.0;
        }
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
        $axisColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'AxisColor', 'integer', $ipsView), $palette['text']);
        $valueColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['text']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['track']);
        $axisSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'AxisFontSizePercent', 'integer', $ipsView) / 100)));
        $valueSize = max(6, min(22, (int) round(11 * $this->BarPreviewValue($values, 'ValueFontSizePercent', 'integer', $ipsView) / 100)));
        $levels = [0.0];
        $total = 0.0;
        foreach ($items as $index => $item) {
            $total = $index === 0 ? $item['value'] : $total + $item['value'];
            $levels[] = $total;
        }
        $minimum = min(0.0, ...$levels);
        $maximum = max(1.0, ...$levels);
        $range = max(1.0, $maximum - $minimum);
        $y = static fn (float $value): float => 285 - ($value - $minimum) / $range * 205;
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            for ($step = 0; $step <= 4; ++$step) {
                $lineY = 80 + $step * 51;
                $svg .= '<line x1="70" y1="' . $lineY . '" x2="680" y2="' . $lineY
                    . '" stroke="' . EChartsBarPreview::E($gridColor) . '"/>';
            }
        }
        $svg .= '<line x1="70" y1="' . EChartsBarPreview::N($y(0)) . '" x2="680" y2="'
            . EChartsBarPreview::N($y(0)) . '" stroke="' . EChartsBarPreview::E($palette['border']) . '"/>';
        $width = 580 / (count($items) + 1);
        $barWidth = $width * max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView))) / 100;
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $valueLabelPosition = $this->BarPreviewValue($values, 'ValueLabelPosition', 'string', $ipsView);
        $valueLabelDistance = $this->BarPreviewValue($values, 'ValueLabelDistance', 'integer', $ipsView);
        $categoryLabelY = $showValues && $minimum < 0 ? 337 : 309;
        foreach ($items as $index => $item) {
            $from = $index === 0 ? 0.0 : $levels[$index];
            $to = $levels[$index + 1];
            $top = min($y($from), $y($to));
            $height = max(2, abs($y($from) - $y($to)));
            $colorName = $index === 0 ? 'StartColor' : ($item['value'] >= 0 ? 'IncreaseColor' : 'DecreaseColor');
            $fallback = $palette['seriesColors'][($index + 1) % count($palette['seriesColors'])];
            $color = EChartsBarPreview::Color($this->BarPreviewValue($values, $colorName, 'integer', $ipsView), $fallback);
            $x = 90 + $index * $width;
            $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($top)
                . '" width="' . EChartsBarPreview::N($barWidth) . '" height="' . EChartsBarPreview::N($height)
                . '" fill="' . EChartsBarPreview::E($color) . '"/>';
            $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="' . $categoryLabelY
                . '" text-anchor="middle" fill="'
                . EChartsBarPreview::E($axisColor) . '" font-size="' . $axisSize . '">'
                . EChartsBarPreview::E(mb_strimwidth($item['label'], 0, 13, '…')) . '</text>';
            if ($showValues) {
                $valueY = $item['value'] >= 0 ? $top - 5 : $top + $height + $valueSize + 2;
                $valueX = $x + $barWidth / 2;
                $anchor = 'middle';
                if ($valueLabelPosition !== 'auto' || $valueLabelDistance !== 5) {
                    $placement = EChartsLabelDesign::PreviewRect(
                        $x,
                        $top,
                        $barWidth,
                        $height,
                        $valueLabelPosition === 'auto' ? ($item['value'] >= 0 ? 'top' : 'bottom') : $valueLabelPosition,
                        $valueLabelDistance
                    );
                    $valueX = $placement['x'];
                    $valueY = $placement['y'];
                    $anchor = $placement['anchor'];
                }
                $svg .= '<text x="' . EChartsBarPreview::N($valueX) . '" y="'
                    . EChartsBarPreview::N($valueY) . '" text-anchor="' . $anchor . '" fill="'
                    . EChartsBarPreview::E($valueColor) . '" font-size="' . $valueSize . '">'
                    . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                        $item['value'],
                        $previewDecimals,
                        $previewUnit,
                        $previewLanguage
                    )) . '</text>';
            }
        }
        $x = 90 + count($items) * $width;
        $top = min($y(0), $y($total));
        $height = max(2, abs($y(0) - $y($total)));
        $color = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'TotalColor', 'integer', $ipsView),
            $palette['accent']
        );
        $svg .= '<rect x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($top)
            . '" width="' . EChartsBarPreview::N($barWidth) . '" height="' . EChartsBarPreview::N($height)
            . '" fill="' . EChartsBarPreview::E($color) . '"/>';
        $label = (string) ($values['TotalLabel'] ?? $this->ReadPropertyString('TotalLabel'));
        $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="' . $categoryLabelY
            . '" text-anchor="middle" fill="'
            . EChartsBarPreview::E($axisColor) . '" font-size="' . $axisSize . '">'
            . EChartsBarPreview::E($label !== '' ? $label : 'Gesamt') . '</text>';
        if ($showValues) {
            $valueY = $total >= 0 ? $top - 5 : $top + $height + $valueSize + 2;
            $valueX = $x + $barWidth / 2;
            $anchor = 'middle';
            if ($valueLabelPosition !== 'auto' || $valueLabelDistance !== 5) {
                $placement = EChartsLabelDesign::PreviewRect(
                    $x,
                    $top,
                    $barWidth,
                    $height,
                    $valueLabelPosition === 'auto' ? ($total >= 0 ? 'top' : 'bottom') : $valueLabelPosition,
                    $valueLabelDistance
                );
                $valueX = $placement['x'];
                $valueY = $placement['y'];
                $anchor = $placement['anchor'];
            }
            $svg .= '<text x="' . EChartsBarPreview::N($valueX) . '" y="'
                . EChartsBarPreview::N($valueY) . '" text-anchor="' . $anchor . '" fill="'
                . EChartsBarPreview::E($valueColor) . '" font-size="' . $valueSize . '">'
                . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                    $total,
                    $previewDecimals,
                    $previewUnit,
                    $previewLanguage
                )) . '</text>';
        }

        return EChartsPreviewSourceCount::Append(
            $svg . '</svg>',
            count($items),
            count($data['items']),
            $this->Translate('Preview shows %d of %d sources'),
            $palette['muted'],
            696,
            351
        );
    }

    private function RenderWaterfallHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Waterfall',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-waterfall-root', 'echarts-bar-waterfall'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure valid Waterfall sources.'                              => $this->Translate('Configure valid Waterfall sources.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Waterfall values could not be loaded.'                       => $this->Translate('The Waterfall values could not be loaded.'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::CartesianJavaScript(),
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

    /** @return array{Status:int, Message:string}|null */
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
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Waterfall design is invalid.'];
        }

        return null;
    }

    /** @return list<array{VariableID:int, Label:string, Unit:string, Decimals:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The Waterfall sources are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 2 || count($sources) > 16) {
            throw new InvalidArgumentException('Configure a start and 1 to 15 signed changes.');
        }
        $validated = [];
        $variableIDs = [];
        $commonUnit = null;
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Waterfall source must be an object.');
            }
            $variableID = $source['VariableID'] ?? null;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || in_array($variableID, $variableIDs, true)
            ) {
                throw new InvalidArgumentException('Waterfall sources must have unique existing variable IDs.');
            }
            $variable = IPS_GetVariable($variableID);
            if (!is_array($variable) || !in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Waterfall sources must be numeric variables.');
            }
            $decimals = $source['Decimals'] ?? 1;
            $unit = $source['Unit'] ?? '';
            $label = $source['Label'] ?? '';
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_string($unit) || !is_string($label)
            ) {
                throw new InvalidArgumentException('A Waterfall source contains invalid display settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                (bool) ($source['UseVariablePresentation'] ?? true)
            );
            if ($commonUnit !== null && $presentation['unit'] !== $commonUnit) {
                throw new InvalidArgumentException('All Waterfall sources must use the same unit.');
            }
            $commonUnit = $presentation['unit'];
            $variableIDs[] = $variableID;
            $validated[] = [
                'VariableID' => $variableID,
                'Label'      => trim($label),
                'Unit'       => $presentation['unit'],
                'Decimals'   => $presentation['decimals']
            ];
        }

        return $validated;
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return $this->ErrorState('Configure valid Waterfall sources.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetWaterfallData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['bar']['style'] = $this->ReadDesignStyle('IPSView');
            }
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);

            return $this->ErrorState('The Waterfall values could not be loaded.');
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'waterfall',
            'status'        => 'ready',
            'chart'         => $chart,
            'error'         => null
        ];
    }

    /** @return array<string, mixed> */
    private function ErrorState(string $message): array
    {
        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'waterfall',
            'status'        => 'error',
            'chart'         => null,
            'error'         => $message
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
                $this->ScheduleTransientRefresh($states, 'The Waterfall values could not be loaded.');
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
        $this->RegisterPropertyString($prefix . 'EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyString($prefix . 'ValueLabelPosition', 'auto');
        $this->RegisterPropertyInteger($prefix . 'ValueLabelDistance', 5);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 60);
        $this->RegisterPropertyInteger($prefix . 'StartColor', -1);
        $this->RegisterPropertyInteger($prefix . 'IncreaseColor', -1);
        $this->RegisterPropertyInteger($prefix . 'DecreaseColor', -1);
        $this->RegisterPropertyInteger($prefix . 'TotalColor', -1);
        $this->RegisterBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix);
        $this->RegisterAnimationProperties($prefix, true, 350, 500);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'EChartsTheme'            => 'string',
            'ShowValues'              => 'boolean',
            'ValueLabelPosition'      => 'string',
            'ValueLabelDistance'      => 'integer',
            'ShowGrid'                => 'boolean',
            'BarWidthPercent'         => 'integer',
            'StartColor'              => 'integer',
            'IncreaseColor'           => 'integer',
            'DecreaseColor'           => 'integer',
            'TotalColor'              => 'integer',
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
        if (!$this->AnimationIsValid($prefix)
            || !EChartsLabelDesign::IsValidPosition($this->ReadPropertyString($prefix . 'ValueLabelPosition'), EChartsLabelDesign::CARTESIAN_POSITIONS)
            || !EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . 'ValueLabelDistance'))
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') > 100
        ) {
            return false;
        }
        if (!$this->BarTypographyIsValid(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix)) {
            return false;
        }
        foreach (['StartColor', 'IncreaseColor', 'DecreaseColor', 'TotalColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            if ($color < -1 || $color > 0xFFFFFF) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        $colors = [];
        foreach (['StartColor', 'IncreaseColor', 'DecreaseColor', 'TotalColor'] as $name) {
            $color = $this->ReadPropertyInteger($prefix . $name);
            $colors[lcfirst($name)] = $color < 0 ? '' : EChartsAsset::ColorToHex($color);
        }

        return [
            ...$this->ReadAnimationStyle($prefix),
            'showValues'         => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'valueLabelPosition' => $this->ReadPropertyString($prefix . 'ValueLabelPosition'),
            'valueLabelDistance' => $this->ReadPropertyInteger($prefix . 'ValueLabelDistance'),
            'showGrid'           => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'barWidthPercent'    => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'colors'             => $colors,
            ...$this->ReadBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix)
        ];
    }

    private function IPSViewThemeCSS(): string
    {
        $theme = $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');

        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($theme),
            '#echarts-bar-waterfall-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }
}
