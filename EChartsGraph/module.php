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
use SymconECharts\EChartsDesignerSections;
use SymconECharts\EChartsGraphOutput;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsTransientRefresh;
use SymconECharts\GraphModel;

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
require_once __DIR__ . '/../libs/GraphModel.php';
require_once __DIR__ . '/../libs/EChartsGraphOutput.php';

class EChartsGraph extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;
    use \SymconECharts\EChartsDebugFlow;

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
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewGraph';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_READ_FAILED = 204;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO,
        'GraphLayout'          => 'force',
        'NodeSize'             => 16,
        'RoamEnabled'          => true,
        'ShowLabels'           => true,
        'ShowValues'           => false,
        'TitleFontSizePercent' => 100,
        'LabelFontSizePercent' => 100,
        'TitleColor'           => -1,
        'LabelColor'           => -1,
        'NodeColor'            => -1,
        'EdgeColor'            => -1
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
            $this->RegisterTimer('GraphRefresh', 0, 'ECGR_RefreshGraph($_IPS["TARGET"]);');
            $this->RegisterTransientRefresh('ECGR');
            $this->RegisterPropertyString('Title', '');
            $this->RegisterPropertyString('Nodes', '[]');
            $this->RegisterPropertyString('Edges', '[]');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Graph for IPSView'), 90);
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
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
            $form['elements'] = $this->WithAnimationFormControls($form['elements']);
            $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECGR');
            $form = $this->WithIPSViewDesignFormState($form, 'ECGR');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Layout and interaction', 'start' => 'GraphLayout'],
                ['caption' => 'Labels and typography', 'start' => 'ShowLabels'],
                ['caption' => 'Node and line colors', 'start' => 'NodeColor']
            ]);
            $fields = [
                'Title', 'Nodes', 'Edges', 'IPSViewUseTileDesign', 'IPSViewAdaptToBackground',
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
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECGR', $fields));
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

    public function GetGraphData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetGraphDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetGraphDiagnostic(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $error = $this->GetConfigurationError();
            return $debugFlowResult = json_encode([
                'valid'     => $error === null,
                'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
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
            return $debugFlowResult = $this->RenderGraphHTMLPage(false);
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
            return $debugFlowResult = $this->RenderGraphHTMLPage(true);
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

    public function RefreshGraph(): void
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

    /** @param array<string,mixed> $values */
    protected function BarPreviewSvg(array $values, bool $ipsView): string
    {
        $theme = $this->BarPreviewValue($values, 'EChartsTheme', 'string', $ipsView);
        $palette = EChartsBarPreview::Palette($theme);
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $titleColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']);
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $nodeColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'NodeColor', 'integer', $ipsView), $palette['seriesColors'][0]);
        $edgeColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'EdgeColor', 'integer', $ipsView), $palette['muted']);
        $nodes = [
            ['id' => 'sample-1', 'name' => 'Zentrale', 'kind' => 'category', 'displayValue' => ''],
            ['id' => 'sample-2', 'name' => 'Sensor A', 'kind' => 'instance', 'displayValue' => ''],
            ['id' => 'sample-3', 'name' => 'Sensor B', 'kind' => 'variable', 'displayValue' => '21 °C'],
            ['id' => 'sample-4', 'name' => 'Aktor', 'kind' => 'instance', 'displayValue' => '']
        ];
        $links = [
            ['source' => 'sample-1', 'target' => 'sample-2'],
            ['source' => 'sample-1', 'target' => 'sample-3'],
            ['source' => 'sample-2', 'target' => 'sample-4']
        ];
        $sample = true;
        $previewSummary = '';
        try {
            $nodeRows = $this->DecodeRows($values['Nodes'] ?? $this->ReadPropertyString('Nodes'));
            $edgeRows = $this->DecodeRows($values['Edges'] ?? $this->ReadPropertyString('Edges'));
            $model = $this->ReadGraph($nodeRows, $edgeRows);
            $nodes = array_slice($model['nodes'], 0, GraphModel::PREVIEW_NODES);
            $known = array_fill_keys(array_column($nodes, 'id'), true);
            $links = array_slice(
                array_values(array_filter(
                    $model['links'],
                    static fn (array $edge): bool => isset($known[$edge['source']], $known[$edge['target']])
                )),
                0,
                GraphModel::PREVIEW_EDGES
            );
            $sample = false;
            if (count($nodes) < count($model['nodes']) || count($links) < count($model['links'])) {
                $previewSummary = sprintf(
                    $this->Translate('Preview excerpt: %d/%d nodes, %d/%d connections.'),
                    count($nodes),
                    count($model['nodes']),
                    count($links),
                    count($model['links'])
                );
            }
        } catch (Throwable) {
            // Invalid drafts are shown with an explicitly marked example.
        }
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            $sample,
            $ipsView,
            $values,
            $this->Translate('Example network'),
            $this->Translate('Graph preview'),
            $titleColor,
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $layout = $this->BarPreviewValue($values, 'GraphLayout', 'string', $ipsView);
        $nodeSize = max(6, min(40, $this->BarPreviewValue($values, 'NodeSize', 'integer', $ipsView)));
        $labelSize = max(8, min(20, (int) round(11 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        $showLabels = $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $positions = [];
        foreach ($nodes as $index => $node) {
            $angle = 2 * M_PI * $index / count($nodes) - M_PI / 2;
            $radius = $layout === 'circular' ? 122 : (92 + ($index % 3) * 18);
            $positions[(string) $node['id']] = [360 + cos($angle) * $radius * 1.55, 185 + sin($angle) * $radius];
            if (count($nodes) > 12 && $layout !== 'circular') {
                $columns = (int) ceil(sqrt(count($nodes) * 2));
                $rows = (int) ceil(count($nodes) / $columns);
                $positions[(string) $node['id']] = [55 + ($index % $columns) * 600 / max(1, $columns - 1),
                    80 + intdiv($index, $columns) * 210 / max(1, $rows - 1)];
            }
        }
        foreach ($links as $link) {
            [$x1, $y1] = $positions[$link['source']];
            [$x2, $y2] = $positions[$link['target']];
            $svg .= '<line x1="' . EChartsBarPreview::N($x1) . '" y1="' . EChartsBarPreview::N($y1)
                . '" x2="' . EChartsBarPreview::N($x2) . '" y2="' . EChartsBarPreview::N($y2)
                . '" stroke="' . EChartsBarPreview::E($edgeColor) . '" stroke-width="2"/>';
        }
        $svg .= '<g data-preview-nodes="' . count($nodes) . '" data-preview-edges="' . count($links) . '">';
        foreach ($nodes as $index => $node) {
            [$x, $y] = $positions[(string) $node['id']];
            $svg .= '<circle cx="' . EChartsBarPreview::N($x) . '" cy="' . EChartsBarPreview::N($y)
                . '" r="' . EChartsBarPreview::N($nodeSize / 2) . '" fill="' . EChartsBarPreview::E($nodeColor)
                . '" data-node="' . EChartsBarPreview::E((string) $node['id']) . '"/>';
            if ($showLabels && (count($nodes) <= 12 || $index % max(1, (int) ceil(count($nodes) / 12)) === 0)) {
                $label = mb_strimwidth((string) $node['name'], 0, 16, '…');
                if ($showValues && $node['kind'] === 'variable' && $node['displayValue'] !== '') {
                    $label .= ' ' . mb_strimwidth((string) $node['displayValue'], 0, 10, '…');
                }
                $svg .= '<text x="' . EChartsBarPreview::N($x + 12) . '" y="' . EChartsBarPreview::N($y - 10)
                    . '" fill="' . EChartsBarPreview::E($labelColor) . '" font-size="' . $labelSize . '">'
                    . EChartsBarPreview::E($label) . '</text>';
            }
        }
        $svg .= '</g>';
        if ($previewSummary !== '') {
            $svg .= '<text x="24" y="348" font-size="11" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
                . EChartsBarPreview::E($previewSummary) . '</text>';
        }
        return $svg . '</svg>';
    }

    /** @return list<array<string,mixed>> */
    private function DecodeRows(mixed $raw): array
    {
        if (is_string($raw) && strlen($raw) > 65536) {
            throw new InvalidArgumentException('Graph configuration is too large.');
        }
        $rows = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw;
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException('Graph nodes and connections must be lists.');
        }
        return $rows;
    }

    /** @param list<array<string,mixed>> $nodes @param list<array<string,mixed>> $edges @return array<string,mixed> */
    private function ReadGraph(array $nodes, array $edges): array
    {
        return GraphModel::Build(
            $nodes,
            $edges,
            static fn (int $id): array => IPS_GetObject($id),
            static fn (int $id): string => (string) GetValueFormatted($id)
        );
    }

    private function GetGraphDataForOutput(bool $ipsView): string
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
        try {
            $graph = $this->ReadGraph(
                $this->DecodeRows($this->ReadPropertyString('Nodes')),
                $this->DecodeRows($this->ReadPropertyString('Edges'))
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->DebugEChartsSetStatus(self::STATUS_READ_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'graph',
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
        $startup = EChartsGraphOutput::Startup($this->BuildVisualizationState($ipsView));
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Graph',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-graph-root', 'echarts-graph'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $startup['state'],
            'translations' => [
                'Configure at least two valid Graph nodes and one connection.'    => $this->Translate('Configure at least two valid Graph nodes and one connection.'),
                'The Graph design is invalid.'                                    => $this->Translate('The Graph design is invalid.'),
                'Connect an active EChartsGateway.'                               => $this->Translate('Connect an active EChartsGateway.'),
                'The Graph could not be loaded.'                                  => $this->Translate('The Graph could not be loaded.'),
                'Data could not be refreshed. Showing the last available values.' => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'            => EChartsAsset::VERSION,
                'echartsThemes'             => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible'         => !$hiddenTitle,
                'adaptToBackground'         => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'          => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'compressedStateGzipBase64' => $startup['compressedStateGzipBase64']
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::GraphJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript() . "\n" . EChartsAsset::GraphSupportJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->DebugEChartsSetStatus(IS_INACTIVE);
        $this->SetTimerInterval('GraphRefresh', 0);
        $this->SetTimerInterval('TransientRetry', 0);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
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
        $this->SetTimerInterval('GraphRefresh', 60000);
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            GraphModel::ValidateRows(
                $this->DecodeRows($this->ReadPropertyString('Nodes')),
                $this->DecodeRows($this->ReadPropertyString('Edges'))
            );
        } catch (Throwable) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Configure at least two valid Graph nodes and one connection.'];
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
        foreach (['TitleColor', 'LabelColor', 'NodeColor', 'EdgeColor'] as $name) {
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
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState('The Graph could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'graph', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'graph', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Graph could not be loaded.');
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
            '#echarts-graph-root',
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
                    'onClick' => 'ECGR_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
