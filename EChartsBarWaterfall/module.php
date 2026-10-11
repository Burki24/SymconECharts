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
use SymconECharts\EChartsSourceIdentity;
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
require_once __DIR__ . '/../libs/EChartsAnimationDesign.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsDesignerSections.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarWaterfall extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use DataFlowHelper;
    use EChartsCurrentSources;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
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

    public function Create(): void
    {
        parent::Create();

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
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Waterfall for IPSView'), 90);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
            $this->PublishIPSViewHTML();
        }
    }

    public function GetCompatibleParents(): string
    {
        return json_encode([
            'type'      => 'connect',
            'moduleIDs' => [self::GATEWAY_MODULE_ID]
        ], JSON_THROW_ON_ERROR);
    }

    public function GetConfigurationForm(): string
    {
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

        $form = $this->WithIPSViewDesignFormState($form, 'ECBW');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Labels and geometry', 'start' => 'ShowValues'],
            ['caption' => 'Step colors', 'start' => 'StartColor']
        ]);
        $fields = array_merge(
            ['Title', 'Sources', 'TotalLabel', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
                'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent'],
            array_keys($this->DesignPropertyNames()),
            array_map(static fn (string $name): string => 'IPSView' . $name, array_keys($this->DesignPropertyNames()))
        );

        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECBW', $fields));
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
    }

    public function GetWaterfallData(): string
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);
            throw new RuntimeException($configurationError['Message']);
        }
        if (!$this->HasActiveParent()) {
            $this->SetStatus(self::STATUS_PARENT_MISSING);
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
                $this->SetStatus(self::STATUS_GATEWAY_FAILED);
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
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
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
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderWaterfallHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderWaterfallHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    /** @param array<int, mixed> $Data */
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
        $palette = EChartsBarPreview::Palette($this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView));
        $data = EChartsBarPreview::Sources($this->ReadPropertyString('Sources'), $values);
        $items = array_slice($data['items'], 0, 5);
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
            $this->Translate('Chart preview')
        );
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
                    . '" stroke="' . EChartsBarPreview::E($palette['track']) . '"/>';
            }
        }
        $svg .= '<line x1="70" y1="' . EChartsBarPreview::N($y(0)) . '" x2="680" y2="'
            . EChartsBarPreview::N($y(0)) . '" stroke="' . EChartsBarPreview::E($palette['border']) . '"/>';
        $width = 580 / (count($items) + 1);
        $barWidth = $width * max(20, min(100, $this->BarPreviewValue($values, 'BarWidthPercent', 'integer', $ipsView))) / 100;
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
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
            $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="309" text-anchor="middle" fill="'
                . EChartsBarPreview::E($palette['text']) . '" font-size="11">'
                . EChartsBarPreview::E(mb_strimwidth($item['label'], 0, 13, '…')) . '</text>';
            if ($showValues) {
                $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="'
                    . EChartsBarPreview::N($top - 5) . '" text-anchor="middle" fill="'
                    . EChartsBarPreview::E($palette['text']) . '" font-size="11">'
                    . EChartsBarPreview::E(EChartsBarPreview::N($item['value'])) . '</text>';
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
        $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="309" text-anchor="middle" fill="'
            . EChartsBarPreview::E($palette['text']) . '" font-size="11">'
            . EChartsBarPreview::E($label !== '' ? $label : 'Gesamt') . '</text>';
        if ($showValues) {
            $svg .= '<text x="' . EChartsBarPreview::N($x + $barWidth / 2) . '" y="'
                . EChartsBarPreview::N($top - 5) . '" text-anchor="middle" fill="'
                . EChartsBarPreview::E($palette['text']) . '" font-size="11">'
                . EChartsBarPreview::E(EChartsBarPreview::N($total)) . '</text>';
        }

        return $svg . '</svg>';
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
                'Configure valid Waterfall sources.'        => $this->Translate('Configure valid Waterfall sources.'),
                'Connect an active EChartsGateway.'         => $this->Translate('Connect an active EChartsGateway.'),
                'The Waterfall values could not be loaded.' => $this->Translate('The Waterfall values could not be loaded.')
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
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $this->SetSummary('');
        $this->SynchronizeSourceReferences();
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            $this->WriteAttributeString('LastError', $configurationError['Message']);
            $this->SetStatus($configurationError['Status']);

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
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

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
        try {
            $this->UpdateVisualizationValue(json_encode(
                $this->BuildVisualizationState(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
            $this->PushEChartsIPSViewState(fn (): array => $this->BuildVisualizationState(true));
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

    private function RegisterDesignProperties(string $prefix = ''): void
    {
        $this->RegisterPropertyString($prefix . 'EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 60);
        $this->RegisterPropertyInteger($prefix . 'StartColor', -1);
        $this->RegisterPropertyInteger($prefix . 'IncreaseColor', -1);
        $this->RegisterPropertyInteger($prefix . 'DecreaseColor', -1);
        $this->RegisterPropertyInteger($prefix . 'TotalColor', -1);
        $this->RegisterAnimationProperties($prefix, true, 350, 500);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'EChartsTheme'            => 'string',
            'ShowValues'              => 'boolean',
            'ShowGrid'                => 'boolean',
            'BarWidthPercent'         => 'integer',
            'StartColor'              => 'integer',
            'IncreaseColor'           => 'integer',
            'DecreaseColor'           => 'integer',
            'TotalColor'              => 'integer',
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
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') > 100
        ) {
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
            'showValues'      => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'showGrid'        => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'barWidthPercent' => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'colors'          => $colors
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
