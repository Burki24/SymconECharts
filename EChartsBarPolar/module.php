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
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\EChartsVariablePresentation;
use SymconECharts\PolarData;

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
require_once __DIR__ . '/../libs/EChartsNormalization.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsLabelDesign.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';
require_once __DIR__ . '/PolarData.php';

class EChartsBarPolar extends IPSModuleStrict
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
    private const MAXIMUM_MODEL_BYTES = 200000;
    private const MODEL_TOO_LARGE_MESSAGE = 'The Polar model is too large. Reduce source labels or units.';

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarPolar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const POLAR_MODES = ['radial', 'tangential'];
    private const SORT_ORDERS = ['configured', 'ascending', 'descending'];
    private const VALUE_LABEL_POSITIONS = EChartsLabelDesign::POLAR_POSITIONS;
    private const TEXT_FONTS = ['TitleFontSizePercent' => 'Title size'];
    private const TEXT_COLORS = [
        'TitleColor'      => 'Title color', 'CategoryLabelColor' => 'Category color',
        'ScaleLabelColor' => 'Scale color', 'ValueColor' => 'Outside value color',
        'GridColor'       => 'Grid color'
    ];
    private const VALUE_AXIS_RANGE_MODES = ['auto', 'manual'];
    private const BAR_FILL_MODES = ['solid', 'gradient'];
    private const BAR_HIGHLIGHT_MODES = ['standard', 'outline', 'focus', 'off'];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();
            $this->RegisterTransientRefresh('ECBP');

            $this->SetVisualizationType(1);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterPropertyString('Sources', '[]');
            $this->RegisterPropertyBoolean('EnableSourceSelection', false);
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Polar for IPSView'), 90);
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
            foreach (['', 'IPSView'] as $prefix) {
                EChartsLabelDesign::AppendOptions($form['elements'], $prefix . 'ValueLabelPosition', [
                    ['caption' => 'At start', 'value' => 'start'],
                    ['caption' => 'At end', 'value' => 'end']
                ]);
                EChartsLabelDesign::InsertAfter($form['elements'], $prefix . 'ValueLabelPosition', [
                    ['type'       => 'NumberSpinner', 'name' => $prefix . 'ValueLabelDistance',
                        'caption' => 'Value label distance (-1 = automatic)',
                        'minimum' => -1, 'maximum' => 100, 'suffix' => 'px']
                ]);
            }
            if (isset($form['elements']) && is_array($form['elements'])) {
                $form['elements'] = $this->WithAnimationFormControls($form['elements']);
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['ValueAxisMinimum', 'ValueAxisMaximum'],
                    $this->ReadPropertyString('ValueAxisRangeMode') === 'manual'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['IPSViewValueAxisMinimum', 'IPSViewValueAxisMaximum'],
                    $this->ReadPropertyString('IPSViewValueAxisRangeMode') === 'manual'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarGradientColor'],
                    $this->ReadPropertyString('BarFillMode') === 'gradient'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['IPSViewBarGradientColor'],
                    $this->ReadPropertyString('IPSViewBarFillMode') === 'gradient'
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['BarHighlightColor'],
                    in_array($this->ReadPropertyString('BarHighlightMode'), ['outline', 'focus'], true)
                );
                $form['elements'] = $this->SetFormFieldVisibility(
                    $form['elements'],
                    ['IPSViewBarHighlightColor'],
                    in_array($this->ReadPropertyString('IPSViewBarHighlightMode'), ['outline', 'focus'], true)
                );
                $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
                $this->InsertIPSViewHTMLPageFormItems(
                    $form['elements'],
                    'Configure optional IPSView HTML output.',
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                );
            }

            $form = $this->WithBarTypographyForm($form, self::TEXT_FONTS, self::TEXT_COLORS);
            $form = $this->WithIPSViewDesignFormState($form, 'ECBP');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Polar layout and order', 'start' => 'PolarMode'],
                ['caption' => 'Labels and visibility', 'start' => 'ShowCategoryLabels'],
                ['caption' => 'Bar geometry', 'start' => 'BarWidthPercent'],
                ['caption' => 'Bar appearance and effects', 'start' => 'BarFillMode'],
                ['caption' => 'Scale and angular range', 'start' => 'ValueAxisRangeMode'],
                ['caption' => 'Typography and colors', 'start' => 'TitleFontSizePercent']
            ]);
            $fields = array_merge(
                ['Title', 'Sources', 'NormalizeValues', 'EnableSourceSelection', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                    'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
                array_keys($this->DesignPropertyNames()),
                array_map(static fn (string $name): string => 'IPSView' . $name, array_keys($this->DesignPropertyNames()))
            );

            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECBP', $fields));
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function UpdateValueScaleForm(string $ValueAxisRangeMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateValueScaleFields('', $ValueAxisRangeMode);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateIPSViewValueScaleForm(string $ValueAxisRangeMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateValueScaleFields('IPSView', $ValueAxisRangeMode);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateBarFillForm(bool $IPSView, string $BarFillMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateFormField(($IPSView ? 'IPSView' : '') . 'BarGradientColor', 'visible', $BarFillMode === 'gradient');
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function UpdateBarHighlightForm(bool $IPSView, string $BarHighlightMode): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->UpdateFormField(
                ($IPSView ? 'IPSView' : '') . 'BarHighlightColor',
                'visible',
                in_array($BarHighlightMode, ['outline', 'focus'], true)
            );
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

    public function GetPolarData(): string
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
            $items = [];
            foreach ($sources as $index => $source) {
                $current = $this->ReadCurrentSource($source['VariableID']);
                $items[] = [
                    'id'               => 'variable-' . $source['VariableID'],
                    'initiallyVisible' => $source['InitiallyVisible'],
                    'label'            => $labels[$source['VariableID']],
                    'value'            => $this->ReadPropertyBoolean('NormalizeValues')
                        ? EChartsNormalization::Value($current['Value'], $source) : $current['Value'],
                    ...($this->ReadPropertyBoolean('NormalizeValues')
                        ? EChartsNormalization::Metadata($current['Value'], $source) : []),
                    'unit'      => $source['Unit'],
                    'decimals'  => $source['Decimals'],
                    'color'     => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color']),
                    'order'     => $index,
                    'timestamp' => $current['Timestamp']
                ];
            }

            $this->WriteAttributeString('LastError', '');
            $this->DebugEChartsSetStatus(IS_ACTIVE);

            $json = json_encode([
                'schemaVersion' => 1,
                'family'        => 'bar',
                'variant'       => 'polar',
                'theme'         => $this->ReadPropertyString('EChartsTheme'),
                'polar'         => [
                    'enableSourceSelection' => $this->ReadPropertyBoolean('EnableSourceSelection'),
                    'normalized'            => $this->ReadPropertyBoolean('NormalizeValues'),
                    'title'                 => $this->ReadPropertyString('Title'),
                    'unit'                  => $this->ReadPropertyBoolean('NormalizeValues') ? '%' : $sources[0]['Unit'],
                    'decimals'              => max(array_column($sources, 'Decimals')),
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

    public function GetVisualizationTile(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->RenderPolarHTMLPage(false);
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
            return $debugFlowResult = $this->RenderPolarHTMLPage(true);
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
        $data = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values, 'Label', true, PolarData::MAXIMUM_SOURCE_COUNT);
        $items = $data['items'];
        foreach ($items as $index => &$item) {
            $item['order'] = $index;
        }
        unset($item);
        $normalized = (bool) ($values['NormalizeValues'] ?? $this->ReadPropertyBoolean('NormalizeValues'));
        if ($normalized) {
            foreach ($items as &$item) {
                try {
                    $item['originalValue'] = $item['value'];
                    $item['value'] = EChartsNormalization::Value($item['value'], $item);
                } catch (InvalidArgumentException) {
                    return EChartsBarPreview::Open($palette, $this->Translate('Normalization requires finite Min < Max for every source.'), false, $ipsView, $values, '', '') . '</svg>';
                }
            }
            unset($item);
        }
        try {
            [$autoMinimum, $autoMaximum] = PolarData::Bounds(array_column($items, 'value'));
        } catch (InvalidArgumentException) {
            return EChartsBarPreview::Open($palette, $this->Translate('The Polar values could not be loaded.'), false, $ipsView, $values, '', '') . '</svg>';
        }
        $sourceSelection = (bool) ($values['EnableSourceSelection'] ?? $this->ReadPropertyBoolean('EnableSourceSelection'));
        if ($sourceSelection) {
            $items = array_values(array_filter($items, static fn (array $item): bool => ($item['InitiallyVisible'] ?? true) === true));
        }
        $visibleCount = count($items);
        $previewLanguage = $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage());
        $mode = $this->BarPreviewValue($values, 'PolarMode', 'string', $ipsView);
        $sort = $this->BarPreviewValue($values, 'SortOrder', 'string', $ipsView);
        if ($sort === 'ascending' || $sort === 'descending') {
            usort($items, static fn (array $a, array $b): int => $sort === 'ascending'
                ? $a['value'] <=> $b['value'] : $b['value'] <=> $a['value']);
        }
        $items = array_slice($items, 0, 6);
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
        $categoryColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'CategoryLabelColor', 'integer', $ipsView), $palette['text']);
        if ($normalized) {
            $svg .= '<text x="24" y="350" font-size="11" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E($this->Translate('Normalized comparison (0–100)')) . '</text>';
        }
        $scaleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'ScaleLabelColor', 'integer', $ipsView), $palette['muted']);
        $outsideValueColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'ValueColor', 'integer', $ipsView), $palette['muted']);
        $gridColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GridColor', 'integer', $ipsView), $palette['track']);
        $cx = 350;
        $cy = 190;
        $inner = 145 * max(0, min(70, $this->BarPreviewValue($values, 'InnerRadiusPercent', 'integer', $ipsView))) / 100;
        $outer = 145 * max(20, min(100, $this->BarPreviewValue($values, 'OuterRadiusPercent', 'integer', $ipsView))) / 100;
        $outer = max($inner + 25, $outer);
        $start = (float) $this->BarPreviewValue($values, 'StartAngle', 'integer', $ipsView);
        $span = max(30, min(360, $this->BarPreviewValue($values, 'AngularSpan', 'integer', $ipsView)));
        $direction = $this->BarPreviewValue($values, 'Clockwise', 'boolean', $ipsView) ? -1 : 1;
        $manual = !$normalized && $this->BarPreviewValue($values, 'ValueAxisRangeMode', 'string', $ipsView) === 'manual';
        $minimum = $manual ? (float) $this->BarPreviewValue($values, 'ValueAxisMinimum', 'float', $ipsView) : ($normalized ? 0.0 : $autoMinimum);
        $maximum = $manual ? (float) $this->BarPreviewValue($values, 'ValueAxisMaximum', 'float', $ipsView)
            : ($normalized ? 100.0 : $autoMaximum);
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            return $svg . '</svg>';
        }
        $svg = preg_replace('/<svg\b/', '<svg data-polar-min="' . EChartsBarPreview::N($minimum)
            . '" data-polar-max="' . EChartsBarPreview::N($maximum)
            . '" data-polar-source-count="' . count($data['items']) . '" data-polar-visible-count="' . $visibleCount . '"', $svg, 1) ?? $svg;
        $zeroFraction = $manual ? 0.0 : PolarData::Fraction(0.0, $minimum, $maximum);
        if ($items === []) {
            $svg .= '<text x="350" y="190" text-anchor="middle" fill="' . EChartsBarPreview::E($categoryColor)
                . '" font-size="14">' . EChartsBarPreview::E($this->Translate('No sources selected')) . '</text>';
        }
        if ($this->BarPreviewValue($values, 'ShowGrid', 'boolean', $ipsView)) {
            $scaleSize = max(8, min(24, $this->BarPreviewValue($values, 'ScaleLabelFontSize', 'integer', $ipsView)));
            $svg .= '<text x="24" y="69" fill="' . EChartsBarPreview::E($scaleColor) . '" font-size="'
                . $scaleSize . '">' . EChartsBarPreview::E(EChartsBarPreview::N($minimum) . '–'
                    . EChartsBarPreview::N($maximum)) . '</text>';
            foreach ([0.25, 0.5, 0.75, 1.0] as $fraction) {
                $radius = $inner + ($outer - $inner) * $fraction;
                if ($span === 360) {
                    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . EChartsBarPreview::N($radius)
                        . '" fill="none" stroke="' . EChartsBarPreview::E($gridColor) . '"/>';
                } else {
                    $svg .= '<path data-preview-grid-arc="true" d="'
                        . self::BarPreviewGridArc($cx, $cy, $radius, $start, $start + $direction * $span)
                        . '" fill="none" stroke="' . EChartsBarPreview::E($gridColor) . '"/>';
                }
            }
        }
        $opacity = max(0, min(100, $this->BarPreviewValue($values, 'BarOpacityPercent', 'integer', $ipsView))) / 100;
        $width = max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView))) / 100;
        $showLabels = $this->BarPreviewValue($values, 'ShowCategoryLabels', 'boolean', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $labelSize = max(8, min(24, $this->BarPreviewValue($values, 'CategoryLabelFontSize', 'integer', $ipsView)));
        $valueSize = max(8, min(24, $this->BarPreviewValue($values, 'ValueLabelFontSize', 'integer', $ipsView)));
        $fillMode = $this->BarPreviewValue($values, 'BarFillMode', 'string', $ipsView);
        $gradientEnd = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'BarGradientColor', 'integer', $ipsView),
            $palette['background']
        );
        $outlineWidth = max(0, min(8, $this->BarPreviewValue($values, 'BarOutlineWidth', 'integer', $ipsView)));
        $outlineColor = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'BarOutlineColor', 'integer', $ipsView),
            $palette['border']
        );
        $showBackground = $this->BarPreviewValue($values, 'ShowBarBackground', 'boolean', $ipsView);
        $backgroundColor = EChartsBarPreview::Color(
            $this->BarPreviewValue($values, 'BarBackgroundColor', 'integer', $ipsView),
            $palette['track']
        );
        $backgroundOpacity = max(0, min(
            100,
            $this->BarPreviewValue($values, 'BarBackgroundOpacityPercent', 'integer', $ipsView)
        )) / 100;
        if ($fillMode === 'gradient') {
            $svg .= '<defs>';
            foreach ($items as $index => $item) {
                $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])];
                $svg .= '<linearGradient id="bar-polar-preview-' . $index . '" x1="0" x2="1" y1="0" y2="1">'
                    . '<stop offset="0" stop-color="' . EChartsBarPreview::E($color) . '"/>'
                    . '<stop offset="1" stop-color="' . EChartsBarPreview::E($gradientEnd) . '"/></linearGradient>';
            }
            $svg .= '</defs>';
        }
        foreach ($items as $index => $item) {
            $fraction = PolarData::Fraction($item['value'], $minimum, $maximum);
            $color = $item['color'] !== '' ? $item['color'] : $palette['seriesColors'][$item['order'] % count($palette['seriesColors'])];
            if ($mode === 'tangential') {
                $ring = ($outer - $inner) / count($items);
                $r0 = $inner + $index * $ring + $ring * (1 - $width) / 2;
                $r1 = $r0 + $ring * $width;
                $a0 = $start + $direction * $span * $zeroFraction;
                $a1 = $start + $direction * $span * $fraction;
                if ($showBackground) {
                    $svg .= '<path d="' . self::BarPreviewWedge($cx, $cy, $r0, $r1, $start, $start + $direction * $span)
                        . '" fill="' . EChartsBarPreview::E($backgroundColor)
                        . '" fill-opacity="' . EChartsBarPreview::N($backgroundOpacity) . '"/>';
                }
            } else {
                $slot = $span / count($items);
                $gap = $slot * (1 - $width) / 2;
                $a0 = $start + $direction * ($index * $slot + $gap);
                $a1 = $start + $direction * (($index + 1) * $slot - $gap);
                $r0 = $inner + ($outer - $inner) * $zeroFraction;
                $r1 = $inner + ($outer - $inner) * $fraction;
                [$r0, $r1] = [min($r0, $r1), max($r0, $r1)];
                if ($showBackground) {
                    $svg .= '<path d="' . self::BarPreviewWedge($cx, $cy, $inner, $outer, $a0, $a1)
                        . '" fill="' . EChartsBarPreview::E($backgroundColor)
                        . '" fill-opacity="' . EChartsBarPreview::N($backgroundOpacity) . '"/>';
                }
            }
            if (abs($fraction - $zeroFraction) > 0) {
                $fill = $fillMode === 'gradient' ? 'url(#bar-polar-preview-' . $index . ')' : $color;
                $svg .= '<path data-polar-id="variable-' . (int) ($item['VariableID'] ?? 0)
                    . '" data-polar-fraction="' . sprintf('%.8F', $fraction) . '" d="' . self::BarPreviewWedge($cx, $cy, $r0, $r1, $a0, $a1)
                    . '" fill="' . EChartsBarPreview::E($fill) . '" fill-opacity="'
                    . EChartsBarPreview::N($opacity) . '" stroke="' . EChartsBarPreview::E($outlineColor)
                    . '" stroke-width="' . $outlineWidth . '"/>';
            }
            $labelY = 83 + $index * 33;
            if ($showLabels) {
                $svg .= '<circle cx="545" cy="' . ($labelY - 4) . '" r="5" fill="' . EChartsBarPreview::E($color) . '"/>';
                $svg .= '<text x="558" y="' . $labelY . '" fill="' . EChartsBarPreview::E($categoryColor)
                    . '" font-size="' . $labelSize . '">'
                    . EChartsBarPreview::E(mb_strimwidth($item['label'], 0, 18, '…')) . '</text>';
            }
            if ($showValues) {
                $svg .= '<text x="675" y="' . $labelY . '" text-anchor="end" fill="'
                    . EChartsBarPreview::E($outsideValueColor) . '" font-size="' . $valueSize . '">'
                    . EChartsBarPreview::E(EChartsBarPreview::FormatValue(
                        $normalized ? $item['originalValue'] : $item['value'],
                        $item['decimals'],
                        $item['unit'],
                        $previewLanguage
                    )) . '</text>';
            }
        }

        $svg .= self::BarPreviewAngleGuide($cx, $cy, $start, $direction, $scaleColor);

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

    private static function BarPreviewAngleGuide(float $cx, float $cy, float $start, int $direction, string $color): string
    {
        $svg = '';
        foreach ([0 => '0°/360°', 90 => '90°', 180 => '180°', 270 => '270°'] as $angle => $label) {
            $absoluteAngle = $start + $direction * $angle;
            [$tickX0, $tickY0] = self::BarPreviewPoint($cx, $cy, 148, $absoluteAngle);
            [$tickX1, $tickY1] = self::BarPreviewPoint($cx, $cy, 153, $absoluteAngle);
            [$labelX, $labelY] = self::BarPreviewPoint($cx, $cy, 159, $absoluteAngle);
            $svg .= '<path d="M ' . EChartsBarPreview::N($tickX0) . ' ' . EChartsBarPreview::N($tickY0)
                . ' L ' . EChartsBarPreview::N($tickX1) . ' ' . EChartsBarPreview::N($tickY1)
                . '" stroke="' . EChartsBarPreview::E($color) . '" stroke-opacity="0.65"/>';
            $svg .= '<text data-preview-angle="' . $angle . '" x="' . EChartsBarPreview::N($labelX)
                . '" y="' . EChartsBarPreview::N(max(46, min(348, $labelY + 4)))
                . '" text-anchor="middle" fill="' . EChartsBarPreview::E($color)
                . '" font-size="11">' . EChartsBarPreview::E($label) . '</text>';
        }

        return $svg;
    }

    private static function BarPreviewWedge(float $cx, float $cy, float $inner, float $outer, float $start, float $end): string
    {
        $end = $start + max(-359.99, min(359.99, $end - $start));
        [$x0, $y0] = self::BarPreviewPoint($cx, $cy, $outer, $start);
        [$x1, $y1] = self::BarPreviewPoint($cx, $cy, $outer, $end);
        [$x2, $y2] = self::BarPreviewPoint($cx, $cy, $inner, $end);
        [$x3, $y3] = self::BarPreviewPoint($cx, $cy, $inner, $start);
        $large = abs($end - $start) > 180 ? 1 : 0;
        $sweep = $end <= $start ? 1 : 0;

        return 'M ' . EChartsBarPreview::N($x0) . ' ' . EChartsBarPreview::N($y0)
            . ' A ' . EChartsBarPreview::N($outer) . ' ' . EChartsBarPreview::N($outer)
            . ' 0 ' . $large . ' ' . $sweep . ' ' . EChartsBarPreview::N($x1) . ' ' . EChartsBarPreview::N($y1)
            . ' L ' . EChartsBarPreview::N($x2) . ' ' . EChartsBarPreview::N($y2)
            . ' A ' . EChartsBarPreview::N($inner) . ' ' . EChartsBarPreview::N($inner)
            . ' 0 ' . $large . ' ' . (1 - $sweep) . ' ' . EChartsBarPreview::N($x3) . ' ' . EChartsBarPreview::N($y3) . ' Z';
    }

    private static function BarPreviewGridArc(float $cx, float $cy, float $radius, float $start, float $end): string
    {
        [$x0, $y0] = self::BarPreviewPoint($cx, $cy, $radius, $start);
        [$x1, $y1] = self::BarPreviewPoint($cx, $cy, $radius, $end);
        $large = abs($end - $start) > 180 ? 1 : 0;
        $sweep = $end <= $start ? 1 : 0;

        return 'M ' . EChartsBarPreview::N($x0) . ' ' . EChartsBarPreview::N($y0)
            . ' A ' . EChartsBarPreview::N($radius) . ' ' . EChartsBarPreview::N($radius)
            . ' 0 ' . $large . ' ' . $sweep . ' ' . EChartsBarPreview::N($x1) . ' ' . EChartsBarPreview::N($y1);
    }

    /** @return array{float,float} */
    private static function BarPreviewPoint(float $cx, float $cy, float $radius, float $angle): array
    {
        return [
            $cx + $radius * cos(deg2rad($angle)),
            $cy - $radius * sin(deg2rad($angle))
        ];
    }

    private function UpdateValueScaleFields(string $prefix, string $rangeMode): void
    {
        $manual = $rangeMode === 'manual';
        $this->UpdateFormField($prefix . 'ValueAxisMinimum', 'visible', $manual);
        $this->UpdateFormField($prefix . 'ValueAxisMaximum', 'visible', $manual);
    }

    private function RenderPolarHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        $configuration = [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Polar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-polar-root', 'echarts-bar-polar'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Sources'                                                         => $this->Translate('Sources'),
                'All'                                                             => $this->Translate('All'),
                'Inverse'                                                         => $this->Translate('Inverse'),
                'Previous page'                                                   => $this->Translate('Previous page'),
                'Next page'                                                       => $this->Translate('Next page'),
                'Category navigation'                                             => $this->Translate('Category navigation'),
                'No sources selected'                                             => $this->Translate('No sources selected'),
                self::MODEL_TOO_LARGE_MESSAGE                                     => $this->Translate(self::MODEL_TOO_LARGE_MESSAGE),
                'Normalized'                                                      => $this->Translate('Normalized'),
                'Range'                                                           => $this->Translate('Range'),
                'Configure valid Polar sources.'                                  => $this->Translate('Configure valid Polar sources.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Polar values could not be loaded.'                           => $this->Translate('The Polar values could not be loaded.'),
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::PolarJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::SeriesSelectionJavaScript()
                    . "\n" . EChartsAsset::CategoryNavigationJavaScript(),
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
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Polar design is invalid.'];
        }

        return null;
    }

    /** @return list<array{VariableID:int, Label:string, Unit:string, Decimals:int, Color:int}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The Polar sources are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 1 || count($sources) > PolarData::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Configure 1 to 100 numeric Polar sources.');
        }
        $validated = [];
        $variableIDs = [];
        $commonUnit = null;
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Polar source must be an object.');
            }
            $variableID = $source['VariableID'] ?? null;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || in_array($variableID, $variableIDs, true)
            ) {
                throw new InvalidArgumentException('Polar sources must have unique existing variable IDs.');
            }
            $variable = IPS_GetVariable($variableID);
            if (!is_array($variable) || !in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Polar sources must be numeric variables.');
            }
            $decimals = $source['Decimals'] ?? 1;
            $unit = $source['Unit'] ?? '';
            $label = $source['Label'] ?? '';
            $color = $source['Color'] ?? -1;
            $initiallyVisible = $source['InitiallyVisible'] ?? true;
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_string($unit) || !is_string($label)
                || !is_bool($initiallyVisible)
                || !is_int($color) || $color < -1 || $color > 0xFFFFFF
            ) {
                throw new InvalidArgumentException('A Polar source contains invalid display settings.');
            }
            $presentation = EChartsVariablePresentation::Resolve(
                $variableID,
                0.0,
                100.0,
                $unit,
                $decimals,
                (bool) ($source['UseVariablePresentation'] ?? true)
            );
            if (!$this->ReadPropertyBoolean('NormalizeValues') && $commonUnit !== null && $presentation['unit'] !== $commonUnit) {
                throw new InvalidArgumentException('All Polar sources must use the same unit.');
            }
            $commonUnit = $presentation['unit'];
            $variableIDs[] = $variableID;
            $validated[] = [
                ...($this->ReadPropertyBoolean('NormalizeValues')
                    ? array_combine(['Minimum', 'Maximum'], EChartsNormalization::Range($source)) : []),
                'VariableID'       => $variableID,
                'Label'            => trim($label),
                'Unit'             => $presentation['unit'],
                'Decimals'         => $presentation['decimals'],
                'Color'            => $color,
                'InitiallyVisible' => $initiallyVisible
            ];
        }

        return $validated;
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return $this->ErrorState('Configure valid Polar sources.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetPolarData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['polar']['style'] = $this->ReadDesignStyle('IPSView');
            }
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);

            return $this->ErrorState($this->ReadAttributeString('LastError') === 'MODEL_TOO_LARGE'
                ? self::MODEL_TOO_LARGE_MESSAGE : 'The Polar values could not be loaded.');
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'polar',
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
            'variant'       => 'polar',
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
                $this->ScheduleTransientRefresh($states, 'The Polar values could not be loaded.');
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
        $this->RegisterPropertyString($prefix . 'PolarMode', 'radial');
        $this->RegisterPropertyString($prefix . 'SortOrder', 'configured');
        $this->RegisterPropertyBoolean($prefix . 'ShowCategoryLabels', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyString($prefix . 'ValueLabelPosition', 'middle');
        $this->RegisterPropertyInteger($prefix . 'ValueLabelDistance', -1);
        $this->RegisterPropertyInteger($prefix . 'CategoryLabelFontSize', 10);
        $this->RegisterPropertyInteger($prefix . 'ValueLabelFontSize', 10);
        $this->RegisterPropertyInteger($prefix . 'ScaleLabelFontSize', 10);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 60);
        $this->RegisterPropertyString($prefix . 'BarFillMode', 'solid');
        $this->RegisterPropertyInteger($prefix . 'BarGradientColor', -1);
        $this->RegisterPropertyInteger($prefix . 'BarOpacityPercent', 100);
        $this->RegisterPropertyInteger($prefix . 'BarOutlineWidth', 0);
        $this->RegisterPropertyInteger($prefix . 'BarOutlineColor', -1);
        $this->RegisterPropertyInteger($prefix . 'BarShadowBlur', 0);
        $this->RegisterPropertyInteger($prefix . 'BarShadowColor', -1);
        $this->RegisterPropertyInteger($prefix . 'BarShadowOpacityPercent', 35);
        $this->RegisterAnimationProperties($prefix, true, 350, 500);
        $this->RegisterPropertyString($prefix . 'BarHighlightMode', 'standard');
        $this->RegisterPropertyInteger($prefix . 'BarHighlightColor', -1);
        $this->RegisterPropertyInteger($prefix . 'InnerRadiusPercent', 12);
        $this->RegisterPropertyInteger($prefix . 'OuterRadiusPercent', 76);
        $this->RegisterPropertyInteger($prefix . 'StartAngle', 90);
        $this->RegisterPropertyInteger($prefix . 'AngularSpan', 360);
        $this->RegisterPropertyBoolean($prefix . 'Clockwise', true);
        $this->RegisterPropertyBoolean($prefix . 'RoundCaps', false);
        $this->RegisterPropertyString($prefix . 'ValueAxisRangeMode', 'auto');
        $this->RegisterPropertyFloat($prefix . 'ValueAxisMinimum', 0.0);
        $this->RegisterPropertyFloat($prefix . 'ValueAxisMaximum', 100.0);
        $this->RegisterPropertyBoolean($prefix . 'ShowBarBackground', false);
        $this->RegisterPropertyInteger($prefix . 'BarBackgroundColor', -1);
        $this->RegisterPropertyInteger($prefix . 'BarBackgroundOpacityPercent', 25);
        $this->RegisterBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'EChartsTheme'                => 'string',
            'PolarMode'                   => 'string',
            'SortOrder'                   => 'string',
            'ShowCategoryLabels'          => 'boolean',
            'ShowValues'                  => 'boolean',
            'ValueLabelPosition'          => 'string',
            'ValueLabelDistance'          => 'integer',
            'CategoryLabelFontSize'       => 'integer',
            'ValueLabelFontSize'          => 'integer',
            'ScaleLabelFontSize'          => 'integer',
            'ShowGrid'                    => 'boolean',
            'BarWidthPercent'             => 'integer',
            'BarFillMode'                 => 'string',
            'BarGradientColor'            => 'integer',
            'BarOpacityPercent'           => 'integer',
            'BarOutlineWidth'             => 'integer',
            'BarOutlineColor'             => 'integer',
            'BarShadowBlur'               => 'integer',
            'BarShadowColor'              => 'integer',
            'BarShadowOpacityPercent'     => 'integer',
            'AnimationEnabled'            => 'boolean',
            'AnimationDuration'           => 'integer',
            'AnimationDurationUpdate'     => 'integer',
            'AnimationEasing'             => 'string',
            'AnimationEasingUpdate'       => 'string',
            'AnimationDelay'              => 'integer',
            'AnimationDelayUpdate'        => 'integer',
            'BarHighlightMode'            => 'string',
            'BarHighlightColor'           => 'integer',
            'InnerRadiusPercent'          => 'integer',
            'OuterRadiusPercent'          => 'integer',
            'StartAngle'                  => 'integer',
            'AngularSpan'                 => 'integer',
            'Clockwise'                   => 'boolean',
            'RoundCaps'                   => 'boolean',
            'ValueAxisRangeMode'          => 'string',
            'ValueAxisMinimum'            => 'float',
            'ValueAxisMaximum'            => 'float',
            'ShowBarBackground'           => 'boolean',
            'BarBackgroundColor'          => 'integer',
            'BarBackgroundOpacityPercent' => 'integer',
            ...array_fill_keys(array_keys(self::TEXT_FONTS), 'integer'),
            ...array_fill_keys(array_keys(self::TEXT_COLORS), 'integer')
        ];
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'PolarMode'), self::POLAR_MODES, true)
            || !in_array($this->ReadPropertyString($prefix . 'SortOrder'), self::SORT_ORDERS, true)
            || !in_array($this->ReadPropertyString($prefix . 'ValueLabelPosition'), self::VALUE_LABEL_POSITIONS, true)
            || !EChartsLabelDesign::IsValidDistance($this->ReadPropertyInteger($prefix . 'ValueLabelDistance'), true)
            || $this->ReadPropertyInteger($prefix . 'CategoryLabelFontSize') < 8
            || $this->ReadPropertyInteger($prefix . 'CategoryLabelFontSize') > 24
            || $this->ReadPropertyInteger($prefix . 'ValueLabelFontSize') < 8
            || $this->ReadPropertyInteger($prefix . 'ValueLabelFontSize') > 24
            || $this->ReadPropertyInteger($prefix . 'ScaleLabelFontSize') < 8
            || $this->ReadPropertyInteger($prefix . 'ScaleLabelFontSize') > 24
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') > 100
            || !in_array($this->ReadPropertyString($prefix . 'BarFillMode'), self::BAR_FILL_MODES, true)
            || $this->ReadPropertyInteger($prefix . 'BarGradientColor') < -1
            || $this->ReadPropertyInteger($prefix . 'BarGradientColor') > 0xFFFFFF
            || $this->ReadPropertyInteger($prefix . 'BarOpacityPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'BarOpacityPercent') > 100
            || $this->ReadPropertyInteger($prefix . 'BarOutlineWidth') < 0
            || $this->ReadPropertyInteger($prefix . 'BarOutlineWidth') > 8
            || $this->ReadPropertyInteger($prefix . 'BarOutlineColor') < -1
            || $this->ReadPropertyInteger($prefix . 'BarOutlineColor') > 0xFFFFFF
            || $this->ReadPropertyInteger($prefix . 'BarShadowBlur') < 0
            || $this->ReadPropertyInteger($prefix . 'BarShadowBlur') > 20
            || $this->ReadPropertyInteger($prefix . 'BarShadowColor') < -1
            || $this->ReadPropertyInteger($prefix . 'BarShadowColor') > 0xFFFFFF
            || $this->ReadPropertyInteger($prefix . 'BarShadowOpacityPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'BarShadowOpacityPercent') > 100
            || !$this->AnimationIsValid($prefix)
            || !in_array($this->ReadPropertyString($prefix . 'BarHighlightMode'), self::BAR_HIGHLIGHT_MODES, true)
            || $this->ReadPropertyInteger($prefix . 'BarHighlightColor') < -1
            || $this->ReadPropertyInteger($prefix . 'BarHighlightColor') > 0xFFFFFF
            || $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent') > 95
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent')
                - $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'StartAngle') < 0
            || $this->ReadPropertyInteger($prefix . 'StartAngle') > 360
            || $this->ReadPropertyInteger($prefix . 'AngularSpan') < 30
            || $this->ReadPropertyInteger($prefix . 'AngularSpan') > 360
        ) {
            return false;
        }

        if (
            !in_array($this->ReadPropertyString($prefix . 'ValueAxisRangeMode'), self::VALUE_AXIS_RANGE_MODES, true)
            || !is_finite($this->ReadPropertyFloat($prefix . 'ValueAxisMinimum'))
            || !is_finite($this->ReadPropertyFloat($prefix . 'ValueAxisMaximum'))
            || ($this->ReadPropertyString($prefix . 'ValueAxisRangeMode') === 'manual'
                && $this->ReadPropertyFloat($prefix . 'ValueAxisMinimum')
                    >= $this->ReadPropertyFloat($prefix . 'ValueAxisMaximum'))
            || $this->ReadPropertyInteger($prefix . 'BarBackgroundColor') < -1
            || $this->ReadPropertyInteger($prefix . 'BarBackgroundColor') > 0xFFFFFF
            || $this->ReadPropertyInteger($prefix . 'BarBackgroundOpacityPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'BarBackgroundOpacityPercent') > 100
        ) {
            return false;
        }

        return $this->BarTypographyIsValid(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix);
    }

    /** @return array<string, mixed> */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        $backgroundColor = $this->ReadPropertyInteger($prefix . 'BarBackgroundColor');
        $gradientColor = $this->ReadPropertyInteger($prefix . 'BarGradientColor');
        $outlineColor = $this->ReadPropertyInteger($prefix . 'BarOutlineColor');
        $shadowColor = $this->ReadPropertyInteger($prefix . 'BarShadowColor');
        $highlightColor = $this->ReadPropertyInteger($prefix . 'BarHighlightColor');
        $style = [
            'mode'                        => $this->ReadPropertyString($prefix . 'PolarMode'),
            'sortOrder'                   => $this->ReadPropertyString($prefix . 'SortOrder'),
            'showCategoryLabels'          => $this->ReadPropertyBoolean($prefix . 'ShowCategoryLabels'),
            'showValues'                  => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'valueLabelPosition'          => $this->ReadPropertyString($prefix . 'ValueLabelPosition'),
            'valueLabelDistance'          => $this->ReadPropertyInteger($prefix . 'ValueLabelDistance'),
            'categoryLabelFontSize'       => $this->ReadPropertyInteger($prefix . 'CategoryLabelFontSize'),
            'valueLabelFontSize'          => $this->ReadPropertyInteger($prefix . 'ValueLabelFontSize'),
            'scaleLabelFontSize'          => $this->ReadPropertyInteger($prefix . 'ScaleLabelFontSize'),
            'showGrid'                    => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'barWidthPercent'             => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'barFillMode'                 => $this->ReadPropertyString($prefix . 'BarFillMode'),
            'barGradientColor'            => $gradientColor < 0 ? '' : EChartsAsset::ColorToHex($gradientColor),
            'barOpacityPercent'           => $this->ReadPropertyInteger($prefix . 'BarOpacityPercent'),
            'barOutlineWidth'             => $this->ReadPropertyInteger($prefix . 'BarOutlineWidth'),
            'barOutlineColor'             => $outlineColor < 0 ? '' : EChartsAsset::ColorToHex($outlineColor),
            'barShadowBlur'               => $this->ReadPropertyInteger($prefix . 'BarShadowBlur'),
            'barShadowColor'              => $shadowColor < 0 ? '' : EChartsAsset::ColorToHex($shadowColor),
            'barShadowOpacityPercent'     => $this->ReadPropertyInteger($prefix . 'BarShadowOpacityPercent'),
            ...$this->ReadAnimationStyle($prefix),
            'barHighlightMode'            => $this->ReadPropertyString($prefix . 'BarHighlightMode'),
            'barHighlightColor'           => $highlightColor < 0 ? '' : EChartsAsset::ColorToHex($highlightColor),
            'innerRadiusPercent'          => $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent'),
            'outerRadiusPercent'          => $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent'),
            'startAngle'                  => $this->ReadPropertyInteger($prefix . 'StartAngle'),
            'angularSpan'                 => $this->ReadPropertyInteger($prefix . 'AngularSpan'),
            'clockwise'                   => $this->ReadPropertyBoolean($prefix . 'Clockwise'),
            'roundCaps'                   => $this->ReadPropertyBoolean($prefix . 'RoundCaps'),
            'valueAxisRangeMode'          => $this->ReadPropertyString($prefix . 'ValueAxisRangeMode'),
            'valueAxisMinimum'            => $this->ReadPropertyFloat($prefix . 'ValueAxisMinimum'),
            'valueAxisMaximum'            => $this->ReadPropertyFloat($prefix . 'ValueAxisMaximum'),
            'showBarBackground'           => $this->ReadPropertyBoolean($prefix . 'ShowBarBackground'),
            'barBackgroundColor'          => $backgroundColor < 0 ? '' : EChartsAsset::ColorToHex($backgroundColor),
            'barBackgroundOpacityPercent' => $this->ReadPropertyInteger($prefix . 'BarBackgroundOpacityPercent'),
            ...$this->ReadBarTypography(array_keys(self::TEXT_FONTS), array_keys(self::TEXT_COLORS), $prefix)
        ];

        return $style;
    }

    private function IPSViewThemeCSS(): string
    {
        $theme = $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');

        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($theme),
            '#echarts-bar-polar-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }
}

