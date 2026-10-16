<?php

declare(strict_types=1);

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
use SymconECharts\TreemapModel;

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
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/TreemapModel.php';

class EChartsTreemap extends IPSModuleStrict
{
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewTreemap';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'                 => EChartsAsset::THEME_AUTO,
        'ShowLabels'                   => true,
        'ShowValues'                   => false,
        'TitleFontSizePercent'         => 100,
        'LabelFontSizePercent'         => 100,
        'GroupLabelFontSizePercent'    => 0,
        'SubgroupLabelFontSizePercent' => 0,
        'TitleColor'                   => -1,
        'LabelColor'                   => -1,
        'GroupLabelColor'              => -1,
        'SubgroupLabelColor'           => -1,
        'GroupColors'                  => '[]',
        'Level1BorderWidth'            => 3,
        'Level1GapWidth'               => 3,
        'Level2BorderWidth'            => 2,
        'Level2GapWidth'               => 1,
        'Level3BorderWidth'            => 1,
        'GroupGapWidth'                => 0,
        'FrameColor'                   => -1,
        'AllowPanZoom'                 => true,
        'ClickToZoom'                  => true,
        'ShowBreadcrumb'               => true
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTransientRefresh('ECTM');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Sources', '[]');
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
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Treemap for IPSView'), 90);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
        }
    }

    public function GetCompatibleParents(): string
    {
        return json_encode(['type' => 'connect', 'moduleIDs' => [self::GATEWAY_MODULE_ID]], JSON_THROW_ON_ERROR);
    }

    public function GetConfigurationForm(): string
    {
        $form = $this->LoadConfigurationForm();
        $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        $form['elements'] = $this->WithAnimationFormControls($form['elements']);
        $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECTM');
        $form = $this->WithIPSViewDesignFormState($form, 'ECTM');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Layout and labels', 'start' => 'ShowLabels'],
            ['caption' => 'Typography and colors', 'start' => 'TitleColor'],
            ['caption' => 'Frames and navigation', 'start' => 'GroupGapWidth']
        ]);
        $fields = [
            'Title', 'Sources', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
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
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECTM', $fields));
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
            return;
        }
        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function CopyTileDesignToIPSView(): void
    {
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
    }

    public function GetTreemapData(): string
    {
        return $this->GetTreemapDataForOutput(false);
    }

    public function GetTreemapDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode([
            'valid'       => $error === null,
            'sourceCount' => count($this->ConfiguredVariableIDs()),
            'lastError'   => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderTreemapHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderTreemapHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    public function RetryTransient(): void
    {
        $this->PublishVisualizationState();
    }

    /** @param array<int,mixed> $Data */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($SenderID === 0 && $Message === IPS_KERNELSTARTED) {
            $this->Initialize();
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
            return;
        }
        if ($Message === VM_UPDATE && in_array($SenderID, $this->ConfiguredVariableIDs(), true)) {
            $this->PublishVisualizationState();
        }
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Treemap preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $rows = $values['Sources'] ?? json_decode($this->ReadPropertyString('Sources'), true);
        $groups = [];
        $samples = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $group = trim((string) ($row['Group'] ?? ''));
            if ($group !== '') {
                $groups[$group] = ($groups[$group] ?? 0) + 1;
                $samples[$group] ??= [
                    'subgroup' => trim((string) ($row['Subgroup'] ?? '')),
                    'leaf'     => trim((string) ($row['Label'] ?? '')) ?: 'Value'
                ];
            }
        }
        if ($groups === []) {
            $groups = ['Group A' => 3, 'Group B' => 2, 'Group C' => 1];
        }
        $groups = array_slice($groups, 0, 6, true);
        $total = array_sum($groups);
        $x = 32;
        $widthAvailable = 656;
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $groupLabelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'GroupLabelColor', 'integer', $ipsView), $labelColor);
        $groupColorsProperty = $ipsView && !($values['IPSViewUseTileDesign']
            ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')) ? 'IPSViewGroupColors' : 'GroupColors';
        $configuredColors = $this->ParseGroupColors(
            $values[$groupColorsProperty] ?? $this->ReadPropertyString($groupColorsProperty)
        ) ?? [];
        $showLabels = $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $labelPercent = $this->BarPreviewValue($values, 'GroupLabelFontSizePercent', 'integer', $ipsView)
            ?: $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView);
        $labelSize = max(8, min(22, (int) round(13 * $labelPercent / 100)));
        $valueSize = max(8, min(22, (int) round(13 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        $subgroupPercent = $this->BarPreviewValue($values, 'SubgroupLabelFontSizePercent', 'integer', $ipsView)
            ?: $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView);
        $subgroupSize = max(8, min(22, (int) round(13 * $subgroupPercent / 100)));
        $subgroupColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'SubgroupLabelColor', 'integer', $ipsView), $labelColor);
        $frameColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'FrameColor', 'integer', $ipsView), $palette['background']);
        $level1Border = max(0, min(12, $this->BarPreviewValue($values, 'Level1BorderWidth', 'integer', $ipsView)));
        $groupGap = max(0, min(12, $this->BarPreviewValue($values, 'GroupGapWidth', 'integer', $ipsView)));
        $level1Gap = max(0, min(12, $this->BarPreviewValue($values, 'Level1GapWidth', 'integer', $ipsView)));
        $level2Border = max(0, min(12, $this->BarPreviewValue($values, 'Level2BorderWidth', 'integer', $ipsView)));
        $level2Gap = max(0, min(12, $this->BarPreviewValue($values, 'Level2GapWidth', 'integer', $ipsView)));
        $level3Border = max(0, min(12, $this->BarPreviewValue($values, 'Level3BorderWidth', 'integer', $ipsView)));
        $position = 0;
        foreach ($groups as $name => $group) {
            $width = $position === count($groups) - 1 ? 688 - $x : $widthAvailable * $group / $total;
            $color = $configuredColors[$name] ?? $palette['seriesColors'][$position % count($palette['seriesColors'])];
            $plotX = $x + $groupGap / 2;
            $plotWidth = max(1.0, $width - $groupGap);
            $svg .= '<rect x="' . EChartsBarPreview::N($plotX) . '" y="78" width="' . EChartsBarPreview::N($plotWidth)
                . '" height="240" fill="' . EChartsBarPreview::E($color) . '" stroke="' . EChartsBarPreview::E($frameColor)
                . '" stroke-width="' . $level1Border . '"/>';
            if ($width > 125) {
                $sample = $samples[$name] ?? ['subgroup' => '', 'leaf' => 'Value'];
                $innerX = $plotX + 10 + $level1Gap / 2;
                $innerWidth = max(1.0, $plotWidth - 20 - $level1Gap);
                $svg .= '<rect x="' . EChartsBarPreview::N($innerX) . '" y="116" width="'
                    . EChartsBarPreview::N($innerWidth) . '" height="98" fill="' . EChartsBarPreview::E($color)
                    . '" stroke="' . EChartsBarPreview::E($frameColor) . '" stroke-width="' . $level2Border . '"/>';
                if ($sample['subgroup'] !== '') {
                    $leafX = $innerX + 8 + $level2Gap / 2;
                    $svg .= '<rect x="' . EChartsBarPreview::N($leafX) . '" y="145" width="'
                        . EChartsBarPreview::N(max(1.0, $innerWidth - 16 - $level2Gap))
                        . '" height="57" fill="' . EChartsBarPreview::E($color)
                        . '" stroke="' . EChartsBarPreview::E($frameColor) . '" stroke-width="' . $level3Border . '"/>';
                }
            }
            if ($showLabels && $width > 85) {
                $svg .= '<text x="' . EChartsBarPreview::N($plotX + 8) . '" y="103" font-size="' . $labelSize
                    . '" fill="' . EChartsBarPreview::E($groupLabelColor) . '">'
                    . EChartsBarPreview::E(mb_strimwidth((string) $name, 0, max(4, (int) ($width / 9)), '…')) . '</text>';
                if ($width > 125) {
                    if ($sample['subgroup'] !== '') {
                        $svg .= '<text x="' . EChartsBarPreview::N($innerX + 4) . '" y="135" font-size="' . $subgroupSize
                            . '" fill="' . EChartsBarPreview::E($subgroupColor) . '">'
                            . EChartsBarPreview::E(mb_strimwidth($sample['subgroup'], 0, max(4, (int) ($width / 9)), '…')) . '</text>';
                    }
                    $svg .= '<text x="' . EChartsBarPreview::N($innerX + 6) . '" y="164" font-size="' . $valueSize
                        . '" fill="' . EChartsBarPreview::E($labelColor) . '">'
                        . EChartsBarPreview::E(mb_strimwidth($sample['leaf'], 0, max(4, (int) ($width / 9)), '…'))
                        . ($showValues ? ' 12.4' : '') . '</text>';
                }
            }
            $x += $width;
            ++$position;
        }
        if ($this->BarPreviewValue($values, 'ShowBreadcrumb', 'boolean', $ipsView)) {
            $svg .= '<text x="32" y="341" font-size="11" fill="' . EChartsBarPreview::E($palette['muted'])
                . '">' . EChartsBarPreview::E($this->Translate('Navigation path')) . '</text>';
        }
        return $svg . '</svg>';
    }

    private function GetTreemapDataForOutput(bool $ipsView): string
    {
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->SetStatus($error['Status']);
            throw new RuntimeException($error['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }
        $sources = $this->GetValidatedSources();
        $values = [];
        foreach ($sources as $source) {
            $id = $source['VariableID'];
            $values[$id] = [...$this->ReadCurrentSource($id), 'Name' => IPS_GetName($id)];
        }
        try {
            $model = TreemapModel::Build($sources, $values);
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'treemap',
            'theme'         => $this->EffectiveTheme($ipsView),
            'treemap'       => [
                'title' => $this->ReadPropertyString('Title'),
                'style' => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderTreemapHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Treemap',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-treemap-root', 'echarts-treemap'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Treemap variables.'                              => $this->Translate('Configure valid Treemap variables.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Treemap values could not be loaded.'                         => $this->Translate('The Treemap values could not be loaded.'),
                'Treemap values must be non-negative.'                            => $this->Translate('Treemap values must be non-negative.'),
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::TreemapJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $this->SynchronizeSourceReferences();
        $error = $this->GetConfigurationError();
        if ($error !== null) {
            $this->WriteAttributeString('LastError', $error['Message']);
            $this->SetStatus($error['Status']);
            return;
        }
        if (!$this->HasActiveParent()) {
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            return;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
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
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Treemap design is invalid.'];
        }
        return null;
    }

    /** @return list<array{VariableID:int,Group:string,Subgroup:string,Label:string}> */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode($this->ReadPropertyString('Sources'), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The Treemap variables are not valid JSON.', 0, $exception);
        }
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 1 || count($sources) > 100) {
            throw new InvalidArgumentException('Configure 1 to 100 unique numeric Treemap variables.');
        }
        $result = [];
        $seen = [];
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Treemap entry must be an object.');
            }
            $id = $source['VariableID'] ?? null;
            $group = $source['Group'] ?? null;
            $subgroup = $source['Subgroup'] ?? '';
            $label = $source['Label'] ?? '';
            if (!is_int($id) || $id <= 0 || isset($seen[$id]) || !IPS_VariableExists($id)
                || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [1, 2], true)) {
                throw new InvalidArgumentException('Treemap variables must be unique numeric variables.');
            }
            if (!is_string($group) || !is_string($subgroup) || !is_string($label)
                || trim($group) === '' || mb_strlen(trim($group)) > 80
                || mb_strlen(trim($subgroup)) > 80 || mb_strlen(trim($label)) > 120) {
                throw new InvalidArgumentException('Treemap group, subgroup or label is invalid.');
            }
            $seen[$id] = true;
            $result[] = [
                'VariableID' => $id, 'Group' => trim($group),
                'Subgroup'   => trim($subgroup), 'Label' => trim($label)
            ];
        }
        return $result;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['TitleFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 200) {
                return false;
            }
        }
        foreach (['GroupLabelFontSizePercent', 'SubgroupLabelFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value !== 0 && ($value < 50 || $value > 200)) {
                return false;
            }
        }
        foreach (['TitleColor', 'LabelColor', 'GroupLabelColor', 'SubgroupLabelColor', 'FrameColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        foreach (['GroupGapWidth', 'Level1BorderWidth', 'Level1GapWidth', 'Level2BorderWidth', 'Level2GapWidth',
            'Level3BorderWidth'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 0 || $value > 12) {
                return false;
            }
        }
        return $this->ParseGroupColors($this->ReadPropertyString($prefix . 'GroupColors')) !== null;
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
            if ($name === 'GroupColors') {
                $style['groupColors'] = $this->ParseGroupColors($this->ReadPropertyString($prefix . $name)) ?? [];
                continue;
            }
            $key = lcfirst($name);
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

    /** @return array<string,string>|null */
    private function ParseGroupColors(mixed $raw): ?array
    {
        try {
            $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        } catch (JsonException) {
            return null;
        }
        if (!is_array($rows) || !array_is_list($rows) || count($rows) > 100) {
            return null;
        }
        $colors = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['Group'] ?? null) || !is_int($row['Color'] ?? null)) {
                return null;
            }
            $name = trim($row['Group']);
            $color = $row['Color'];
            if ($name === '' || mb_strlen($name) > 80 || array_key_exists($name, $colors)
                || $color < -1 || $color > 0xFFFFFF) {
                return null;
            }
            $colors[$name] = $color < 0 ? '' : EChartsAsset::ColorToHex($color);
        }
        return array_filter($colors, static fn (string $color): bool => $color !== '');
    }

    private function EffectiveTheme(bool $ipsView): string
    {
        return $this->ReadPropertyString($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? 'IPSViewEChartsTheme' : 'EChartsTheme');
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        if ($this->GetConfigurationError() !== null) {
            return $this->ErrorState('Configure valid Treemap variables.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetTreemapDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Treemap values must be non-negative.');
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState('The Treemap values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'treemap', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'treemap', 'status' => 'error', 'chart' => null, 'error' => $message];
    }

    private function PublishVisualizationState(): void
    {
        try {
            $state = $this->BuildVisualizationState();
            $states = [$state];
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
            $this->ScheduleTransientRefresh($states, 'The Treemap values could not be loaded.');
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
    }

    private function PublishIPSViewHTML(): void
    {
        if (!$this->IsIPSViewHTMLPageEnabled()) {
            return;
        }
        try {
            $this->UpdateIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->GetIPSViewHTML());
        } catch (Throwable $exception) {
            $this->SendDebug('PublishIPSViewHTML', $exception::class, 0);
        }
    }

    private function IPSViewThemeCSS(): string
    {
        return EChartsIPSViewBackground::ThemeCSS(
            EChartsAsset::ThemePreviewPalette($this->EffectiveTheme(true)),
            '#echarts-treemap-root',
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
                if (($row['type'] ?? null) === 'Label'
                    && ($row['caption'] ?? null) === 'Level 2 contains subgroups or direct values; level 3 contains values inside subgroups.') {
                    $rows[] = $row;
                    continue;
                }
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
            throw new RuntimeException('The Treemap Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECTM_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
