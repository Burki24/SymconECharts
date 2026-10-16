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
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\ZigbeeGraphExportReader;
use SymconECharts\ZigbeeGraphModel;

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
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/ZigbeeGraphExportReader.php';
require_once __DIR__ . '/../libs/ZigbeeGraphModel.php';

class EChartsGraphZ2M extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use EChartsAnimationDesign;
    use EChartsBarPreviewForm;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use EChartsTransientRefresh;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const NETWORK_MAP_MODULE_ID = '{C07834E7-9821-218C-C3EC-A4A87FFB8D7D}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewZigbeeGraph';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_READ_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'             => EChartsAsset::THEME_AUTO,
        'GraphLayout'              => 'force',
        'NodeSize'                 => 16,
        'RoamEnabled'              => true,
        'ShowLabels'               => true,
        'ShowLinkArrows'           => false,
        'LinkColorMode'            => 'uniform',
        'TitleFontSizePercent'     => 100,
        'LabelFontSizePercent'     => 100,
        'TitleColor'               => -1,
        'LabelColor'               => -1,
        'NodeColor'                => -1,
        'RouterColor'              => -1,
        'EndDeviceColor'           => -1,
        'EdgeColor'                => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('ZigbeeGraphRefresh', 0, 'ECGZ_RefreshGraph($_IPS["TARGET"]);');
        $this->RegisterTransientRefresh('ECGZ');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyInteger('NetworkMapInstanceID', 0);
        $this->RegisterPropertyInteger('MinimumLQI', 0);
        $this->RegisterPropertyInteger('MaximumLinks', 300);
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
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Zigbee graph for IPSView'), 90);
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
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECGZ');
        $form = $this->WithIPSViewDesignFormState($form, 'ECGZ');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Layout and interaction', 'start' => 'GraphLayout'],
            ['caption' => 'Labels and typography', 'start' => 'ShowLabels'],
            ['caption' => 'Node and line colors', 'start' => 'NodeColor']
        ]);
        $fields = [
            'Title', 'NetworkMapInstanceID', 'MinimumLQI', 'MaximumLinks',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
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
        return $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECGZ', $fields));
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

    public function GetGraphData(): string
    {
        return $this->GetGraphDataForOutput(false);
    }

    public function GetGraphDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode([
            'valid'     => $error === null,
            'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderGraphHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderGraphHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    public function RefreshGraph(): void
    {
        $this->PublishVisualizationState();
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
        }
    }

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $titleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']);
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $nodeColors = [
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'NodeColor', 'integer', $ipsView), $palette['seriesColors'][0]),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'RouterColor', 'integer', $ipsView), $palette['seriesColors'][1]),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'EndDeviceColor', 'integer', $ipsView), $palette['seriesColors'][3])
        ];
        $edgeColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'EdgeColor', 'integer', $ipsView), $palette['muted']);
        $linkColorMode = $this->BarPreviewValue($values, 'LinkColorMode', 'string', $ipsView);
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            true,
            $ipsView,
            $values,
            $this->Translate('Example network'),
            $this->Translate('Zigbee graph preview'),
            $titleColor,
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $positions = $this->BarPreviewValue($values, 'GraphLayout', 'string', $ipsView) === 'circular'
            ? [[360, 75], [505, 185], [360, 295], [215, 185]]
            : [[210, 190], [380, 120], [390, 250], [560, 180]];
        $links = [[0, 1], [0, 2], [1, 3], [2, 3]];
        $arrows = $this->BarPreviewValue($values, 'ShowLinkArrows', 'boolean', $ipsView);
        $markerID = $ipsView ? 'ecgz-preview-arrow-ipsview' : 'ecgz-preview-arrow-tile';
        $lineColors = $linkColorMode === 'lqi'
            ? ['#D9534F', '#E6B94A', '#37A873', '#E6B94A']
            : array_fill(0, count($links), $edgeColor);
        if ($arrows) {
            $svg .= '<defs>';
            foreach ($lineColors as $index => $lineColor) {
                $svg .= '<marker id="' . $markerID . '-' . $index . '" viewBox="0 0 10 10" refX="10" refY="5"'
                    . ' markerWidth="6" markerHeight="6" orient="auto-start-reverse">'
                    . '<path d="M 0 0 L 10 5 L 0 10 z" fill="' . EChartsBarPreview::E($lineColor) . '"/>'
                    . '</marker>';
            }
            $svg .= '</defs>';
        }
        foreach ($links as $index => [$from, $to]) {
            [$x1, $y1] = $positions[$from];
            [$x2, $y2] = $positions[$to];
            $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2
                . '" stroke="' . EChartsBarPreview::E($lineColors[$index]) . '" stroke-width="2"'
                . ($arrows ? ' marker-end="url(#' . $markerID . '-' . $index . ')"' : '') . '/>';
        }
        $nodeSize = max(6, min(40, $this->BarPreviewValue($values, 'NodeSize', 'integer', $ipsView)));
        $labelSize = max(8, min(20, (int) round(11 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        foreach (['Coordinator', 'Router A', 'Router B', 'Sensor'] as $index => $name) {
            [$x, $y] = $positions[$index];
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . EChartsBarPreview::N($nodeSize / 2)
                . '" fill="' . EChartsBarPreview::E($nodeColors[$index === 3 ? 2 : min($index, 1)]) . '"/>';
            if ($this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView)) {
                $svg .= '<text x="' . ($x + 12) . '" y="' . ($y - 10) . '" fill="'
                    . EChartsBarPreview::E($labelColor) . '" font-size="' . $labelSize . '">'
                    . EChartsBarPreview::E($name) . '</text>';
            }
        }
        return $svg . '</svg>';
    }

    /** @return array<string,mixed> */
    private function ReadGraph(): array
    {
        $sourceID = $this->ReadPropertyInteger('NetworkMapInstanceID');
        $instance = IPS_GetInstance($sourceID);
        if (($instance['ModuleInfo']['ModuleID'] ?? '') !== self::NETWORK_MAP_MODULE_ID) {
            throw new RuntimeException('The selected Zigbee2MQTT Network Map is unavailable.');
        }
        $topology = ZigbeeGraphExportReader::ReadLatest(IPS_GetKernelDir());
        return ZigbeeGraphModel::Build(
            $topology,
            $this->ReadPropertyInteger('MinimumLQI'),
            $this->ReadPropertyInteger('MaximumLinks')
        );
    }

    private function GetGraphDataForOutput(bool $ipsView): string
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
        try {
            $graph = $this->ReadGraph();
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_READ_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'zigbee-graph',
            'theme'         => $this->EffectiveTheme($ipsView),
            'graph'         => [
                'title' => $this->ReadPropertyString('Title'),
                'style' => $this->ReadDesignStyle($ipsView),
                ...$graph
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function RenderGraphHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Zigbee Graph',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-zigbee-graph-root', 'echarts-zigbee-graph'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Select a Zigbee2MQTT Network Map and valid connection filters.'   => $this->Translate('Select a Zigbee2MQTT Network Map and valid connection filters.'),
                'The export cannot be assigned to multiple Network Map instances.' => $this->Translate('The export cannot be assigned to multiple Network Map instances.'),
                'No exported Zigbee network map is available.'                     => $this->Translate('No exported Zigbee network map is available.'),
                'The Graph design is invalid.'                                     => $this->Translate('The Graph design is invalid.'),
                'Connect an active EChartsGateway.'                                => $this->Translate('Connect an active EChartsGateway.'),
                'The Graph could not be loaded.'                                   => $this->Translate('The Graph could not be loaded.'),
                'Data could not be refreshed. Showing the last available values.'  => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                ),
                'devices'     => $this->Translate('devices'),
                'links'       => $this->Translate('links'),
                'snapshot'    => $this->Translate('snapshot'),
                'Coordinator' => $this->Translate('Coordinator'),
                'Router'      => $this->Translate('Router'),
                'EndDevice'   => $this->Translate('EndDevice'),
                'End device'  => $this->Translate('End device'),
                'Unknown'     => $this->Translate('Unknown')
            ],
            'options' => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTitle,
                'adaptToBackground' => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'  => $ipsView ? $this->EChartsIPSViewTransportOptions() : null
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::GraphJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        $this->SetTimerInterval('ZigbeeGraphRefresh', 0);
        $this->SetTimerInterval('TransientRetry', 0);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
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
        $this->SetTimerInterval('ZigbeeGraphRefresh', 300000);
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        $sourceID = $this->ReadPropertyInteger('NetworkMapInstanceID');
        $minimumLQI = $this->ReadPropertyInteger('MinimumLQI');
        $maximumLinks = $this->ReadPropertyInteger('MaximumLinks');
        if ($sourceID < 1 || $minimumLQI < 0 || $minimumLQI > 255 || $maximumLinks < 1 || $maximumLinks > 2000) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Select a Zigbee2MQTT Network Map and valid connection filters.'];
        }
        try {
            $instance = IPS_GetInstance($sourceID);
            if (($instance['ModuleInfo']['ModuleID'] ?? '') !== self::NETWORK_MAP_MODULE_ID) {
                throw new RuntimeException('Wrong source module.');
            }
            if (count(IPS_GetInstanceListByModuleID(self::NETWORK_MAP_MODULE_ID)) !== 1) {
                return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'The export cannot be assigned to multiple Network Map instances.'];
            }
        } catch (Throwable) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Select a Zigbee2MQTT Network Map and valid connection filters.'];
        }
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Graph design is invalid.'];
        }
        return null;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'GraphLayout'), ['force', 'circular'], true)
            || !in_array($this->ReadPropertyString($prefix . 'LinkColorMode'), ['uniform', 'lqi'], true)
            || $this->ReadPropertyInteger($prefix . 'NodeSize') < 6
            || $this->ReadPropertyInteger($prefix . 'NodeSize') > 40
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['TitleFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 200) {
                return false;
            }
        }
        foreach (['TitleColor', 'LabelColor', 'NodeColor', 'RouterColor', 'EndDeviceColor', 'EdgeColor'] as $name) {
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
        $style = [...$this->ReadAnimationStyle($prefix)];
        foreach (self::DESIGN_DEFAULTS as $name => $default) {
            if ($name === 'EChartsTheme') {
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

    private function EffectiveTheme(bool $ipsView): string
    {
        return $this->ReadPropertyString($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? 'IPSViewEChartsTheme' : 'EChartsTheme');
    }

    /** @return array<string,mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return $this->ErrorState($configurationError['Message']);
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetGraphDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState($exception->getMessage() === 'No exported Zigbee network map is available.'
                ? 'No exported Zigbee network map is available.' : 'The Graph could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'zigbee-graph', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'zigbee-graph', 'status' => 'error', 'chart' => null, 'error' => $message];
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
            $this->ScheduleTransientRefresh($states, 'The Graph could not be loaded.');
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
            '#echarts-zigbee-graph-root',
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
            throw new RuntimeException('The Graph Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECGZ_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
