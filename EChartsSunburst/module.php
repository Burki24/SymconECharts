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
use SymconECharts\EChartsHierarchySources;
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
require_once __DIR__ . '/../libs/EChartsHierarchySources.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsTransientRefresh.php';
require_once __DIR__ . '/../libs/TreemapModel.php';

class EChartsSunburst extends IPSModuleStrict
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewSunburst';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_VALUE_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'                   => EChartsAsset::THEME_AUTO,
        'ShowLabels'                     => true,
        'ShowValues'                     => false,
        'ShowTooltip'                    => true,
        'LabelRotation'                  => 'radial',
        'LabelMinAngle'                  => 0,
        'ShowInnerRingLabels'            => true,
        'ShowMiddleRingLabels'           => true,
        'ShowOuterRingLabels'            => true,
        'InnerRingLabelFontSizePercent'  => 0,
        'MiddleRingLabelFontSizePercent' => 0,
        'OuterRingLabelFontSizePercent'  => 0,
        'TitleFontSizePercent'           => 100,
        'LabelFontSizePercent'           => 100,
        'TitleColor'                     => -1,
        'LabelColor'                     => -1,
        'GroupColors'                    => '[]',
        'InnerRadiusPercent'             => 0,
        'OuterRadiusPercent'             => 85,
        'StartAngle'                     => 90,
        'Clockwise'                      => true,
        'SortMode'                       => 'original',
        'BorderWidth'                    => 1,
        'BorderRadius'                   => 0,
        'BorderColor'                    => -1,
        'ClickToRoot'                    => true
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
            $this->RegisterTransientRefresh('ECSB');
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
            $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Sunburst for IPSView'), 90);
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
            $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECSB');
            $form = $this->WithIPSViewDesignFormState($form, 'ECSB');
            $form = EChartsDesignerSections::Group($form, [
                ['caption' => 'Layout and labels', 'start' => 'ShowLabels'],
                ['caption' => 'Typography and colors', 'start' => 'TitleColor'],
                ['caption' => 'Rings and navigation', 'start' => 'InnerRadiusPercent']
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
            return $debugFlowResult = $this->EncodeConfigurationForm($this->WithBarPreviewForm($form, 'ECSB', $fields));
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

    public function GetSunburstData(): string
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetSunburstDataForOutput(false);
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetSunburstDiagnostic(): string
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
            return $debugFlowResult = $this->RenderSunburstHTMLPage(false);
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
            return $debugFlowResult = $this->RenderSunburstHTMLPage(true);
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
        $title = (string) ($values['Title'] ?? $this->ReadPropertyString('Title'));
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            true,
            $ipsView,
            $values,
            $this->Translate('Example data'),
            $this->Translate('Sunburst preview'),
            EChartsBarPreview::Color($this->BarPreviewValue($values, 'TitleColor', 'integer', $ipsView), $palette['text']),
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $rows = $values['Sources'] ?? json_decode($this->ReadPropertyString('Sources'), true);
        try {
            $model = EChartsHierarchySources::Preview($rows, 'Sunburst');
        } catch (InvalidArgumentException) {
            return $svg . '<text x="32" y="100" fill="' . EChartsBarPreview::E($palette['text']) . '">'
                . EChartsBarPreview::E($this->Translate('Configure a valid hierarchy path with up to seven groups.')) . '</text></svg>';
        }
        $svg = str_replace('role="img"', 'role="img" data-preview-sources="' . $model['sourceCount']
            . '" data-preview-depth="' . $model['depth'] . '"', $svg);
        $sortMode = $this->BarPreviewValue($values, 'SortMode', 'string', $ipsView);
        $total = $model['total'];
        $groupColorsProperty = $ipsView && !($values['IPSViewUseTileDesign']
            ?? $this->ReadPropertyBoolean('IPSViewUseTileDesign')) ? 'IPSViewGroupColors' : 'GroupColors';
        $configuredColors = $this->ParseGroupColors(
            $values[$groupColorsProperty] ?? $this->ReadPropertyString($groupColorsProperty)
        ) ?? [];
        $rootColors = [];
        foreach ($model['data'] as $index => $group) {
            $rootColors[$group['id']] = $configuredColors[$group['name']]
                ?? $palette['seriesColors'][$index % count($palette['seriesColors'])];
        }
        $border = EChartsBarPreview::Color($this->BarPreviewValue($values, 'BorderColor', 'integer', $ipsView), $palette['background']);
        $borderWidth = max(0, min(12, $this->BarPreviewValue($values, 'BorderWidth', 'integer', $ipsView)));
        $inner = max(0, min(50, $this->BarPreviewValue($values, 'InnerRadiusPercent', 'integer', $ipsView)));
        $outer = max(50, min(95, $this->BarPreviewValue($values, 'OuterRadiusPercent', 'integer', $ipsView)));
        $radius = 60 + ($outer - 50) * 1.8;
        $hole = $inner * 1.2;
        $cx = 360;
        $cy = 195;
        $startAngle = max(0, min(360, $this->BarPreviewValue($values, 'StartAngle', 'integer', $ipsView)));
        $clockwise = $this->BarPreviewValue($values, 'Clockwise', 'boolean', $ipsView);
        $showLabels = $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView);
        $ringLabels = [
            $this->BarPreviewValue($values, 'ShowInnerRingLabels', 'boolean', $ipsView),
            $this->BarPreviewValue($values, 'ShowMiddleRingLabels', 'boolean', $ipsView),
            $this->BarPreviewValue($values, 'ShowOuterRingLabels', 'boolean', $ipsView)
        ];
        $generalSize = $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView);
        $ringSizes = [
            $this->BarPreviewValue($values, 'InnerRingLabelFontSizePercent', 'integer', $ipsView),
            $this->BarPreviewValue($values, 'MiddleRingLabelFontSizePercent', 'integer', $ipsView),
            $this->BarPreviewValue($values, 'OuterRingLabelFontSizePercent', 'integer', $ipsView)
        ];
        $labelMinAngle = max(0, min(45, $this->BarPreviewValue($values, 'LabelMinAngle', 'integer', $ipsView)));
        $labelColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'LabelColor', 'integer', $ipsView), $palette['text']);
        $labelRotation = $this->BarPreviewValue($values, 'LabelRotation', 'string', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $depthCount = $model['depth'];
        $draw = function (array $nodes, int $depth, float $offset, string $parentColor = '') use (
            &$draw,
            &$svg,
            $rootColors,
            $sortMode,
            $total,
            $depthCount,
            $hole,
            $radius,
            $borderWidth,
            $border,
            $cx,
            $cy,
            $clockwise,
            $startAngle,
            $showLabels,
            $showValues,
            $ringLabels,
            $ringSizes,
            $generalSize,
            $labelMinAngle,
            $labelColor,
            $labelRotation
        ): void {
            if ($sortMode === 'asc' || $sortMode === 'desc') {
                usort($nodes, static fn (array $left, array $right): int => $sortMode === 'asc'
                    ? $left['value'] <=> $right['value'] : $right['value'] <=> $left['value']);
            }
            foreach ($nodes as $node) {
                $share = 100.0 * $node['value'] / $total;
                $color = $depth === 1 ? $rootColors[$node['id']] : $parentColor;
                $level = min(2, $depth - 1);
                $ringRadius = $hole + ($radius - $hole) * ($depth - 0.5) / $depthCount;
                $ringWidth = ($radius - $hole) / $depthCount - $borderWidth;
                $svg .= '<circle data-node="' . EChartsBarPreview::E($node['id']) . '" data-depth="' . $depth
                    . '" cx="' . $cx . '" cy="' . $cy . '" r="' . EChartsBarPreview::N($ringRadius)
                    . '" fill="none" stroke="' . EChartsBarPreview::E($color)
                    . '" stroke-width="' . EChartsBarPreview::N(max(1, $ringWidth))
                    . '" pathLength="100" stroke-dasharray="' . EChartsBarPreview::N(max(0, $share - 0.25))
                    . ' 100" stroke-dashoffset="' . EChartsBarPreview::N($clockwise ? -$offset : $offset + $share)
                    . '" transform="rotate(-' . $startAngle . ' ' . $cx . ' ' . $cy . ')"/>';
                if ($showLabels && $ringLabels[$level] && 3.6 * $share >= $labelMinAngle && $share >= 3 && $ringWidth >= 8) {
                    $angle = -$startAngle + ($clockwise ? 1 : -1) * 3.6 * ($offset + $share / 2);
                    $radians = deg2rad($angle);
                    $x = $cx + $ringRadius * cos($radians);
                    $y = $cy + $ringRadius * sin($radians);
                    $scale = $ringSizes[$level] > 0 ? $ringSizes[$level] : $generalSize;
                    $size = max(8, min(22, (int) round(10 * $scale / 100)));
                    $rotation = match ($labelRotation) {
                        'radial'     => $angle,
                        'tangential' => $angle + 90,
                        default      => 0
                    };
                    $text = $node['name'] . ($showValues && !isset($node['children']) ? ' ' . $node['value'] : '');
                    $svg .= '<text x="' . EChartsBarPreview::N($x) . '" y="' . EChartsBarPreview::N($y)
                        . '" text-anchor="middle" dominant-baseline="middle" font-size="' . $size
                        . '" fill="' . EChartsBarPreview::E($labelColor) . '" transform="rotate('
                        . EChartsBarPreview::N($rotation) . ' ' . EChartsBarPreview::N($x) . ' '
                        . EChartsBarPreview::N($y) . ')">'
                        . EChartsBarPreview::E(mb_strimwidth($text, 0, 12, '…')) . '</text>';
                }
                if (isset($node['children'])) {
                    $draw($node['children'], $depth + 1, $offset, $color);
                }
                $offset += $share;
            }
        };
        $draw($model['data'], 1, 0.0);
        if ($borderWidth > 0) {
            for ($level = 1; $level < $depthCount; ++$level) {
                $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="'
                    . EChartsBarPreview::N($hole + ($radius - $hole) * $level / $depthCount)
                    . '" fill="none" stroke="' . EChartsBarPreview::E($border)
                    . '" stroke-width="' . $borderWidth . '"/>';
            }
        }
        $svg .= '<text x="32" y="352" font-size="10" fill="' . EChartsBarPreview::E($palette['muted']) . '">'
            . EChartsBarPreview::E($this->Translate('Schematic hierarchy; zoom in the chart to inspect small nodes.')) . '</text>';
        return $svg . '</svg>';
    }

    private function GetSunburstDataForOutput(bool $ipsView): string
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
        $sources = $this->GetValidatedSources();
        $values = [];
        foreach ($sources as $source) {
            $id = $source['VariableID'];
            $values[$id] = [...$this->ReadCurrentSource($id), 'Name' => IPS_GetName($id)];
        }
        try {
            $model = TreemapModel::Build($sources, $values, 'Sunburst');
        } catch (InvalidArgumentException $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->DebugEChartsSetStatus(self::STATUS_VALUE_INVALID);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->DebugEChartsSetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion'  => 1,
            'family'         => 'sunburst',
            'theme'          => $this->EffectiveTheme($ipsView),
            'sunburst'       => [
                'title' => $this->ReadPropertyString('Title'),
                'style' => $this->ReadDesignStyle($ipsView),
                ...$model
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function RenderSunburstHTMLPage(bool $ipsView): string
    {
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Sunburst',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-sunburst-root', 'echarts-sunburst'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $this->BuildVisualizationState($ipsView),
            'translations' => [
                'Configure valid Sunburst variables.'                              => $this->Translate('Configure valid Sunburst variables.'),
                'Connect an active EChartsGateway.'                                => $this->Translate('Connect an active EChartsGateway.'),
                'The Sunburst values could not be loaded.'                         => $this->Translate('The Sunburst values could not be loaded.'),
                'Sunburst values must be non-negative.'                            => $this->Translate('Sunburst values must be non-negative.'),
                'No positive values'                                               => $this->Translate('No positive values'),
                'Data could not be refreshed. Showing the last available values.'  => $this->Translate(
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
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::SunburstJavaScript(),
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
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Sunburst design is invalid.'];
        }
        return null;
    }

    /** @return list<array{VariableID:int,Group:string,Subgroup:string,Label:string,Path:list<string>}> */
    private function GetValidatedSources(): array
    {
        return EChartsHierarchySources::Resolve($this->ReadPropertyString('Sources'), 'Sunburst');
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
        foreach (['InnerRingLabelFontSizePercent', 'MiddleRingLabelFontSizePercent',
            'OuterRingLabelFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value !== 0 && ($value < 50 || $value > 200)) {
                return false;
            }
        }
        if (!in_array($this->ReadPropertyString($prefix . 'LabelRotation'), ['radial', 'tangential', 'horizontal'], true)
            || !in_array($this->ReadPropertyString($prefix . 'SortMode'), ['original', 'asc', 'desc'], true)) {
            return false;
        }
        foreach (['TitleColor', 'LabelColor', 'BorderColor'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }
        $inner = $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent');
        $outer = $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent');
        $border = $this->ReadPropertyInteger($prefix . 'BorderWidth');
        $radius = $this->ReadPropertyInteger($prefix . 'BorderRadius');
        $startAngle = $this->ReadPropertyInteger($prefix . 'StartAngle');
        $labelMinAngle = $this->ReadPropertyInteger($prefix . 'LabelMinAngle');
        if ($inner < 0 || $inner > 50 || $outer < 50 || $outer > 95 || $inner >= $outer
            || $border < 0 || $border > 12 || $radius < 0 || $radius > 20
            || $startAngle < 0 || $startAngle > 360 || $labelMinAngle < 0 || $labelMinAngle > 45) {
            return false;
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
            return $this->ErrorState('Configure valid Sunburst variables.');
        }
        if (!$this->HasActiveParent()) {
            return $this->ErrorState('Connect an active EChartsGateway.');
        }
        try {
            $chart = json_decode($this->GetSunburstDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return $this->ErrorState('Sunburst values must be non-negative.');
        } catch (Throwable $exception) {
            $this->DebugFlowException(__FUNCTION__, __LINE__, $exception);
            return $this->ErrorState('The Sunburst values could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'sunburst', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'sunburst', 'status' => 'error', 'chart' => null, 'error' => $message];
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
                $this->ScheduleTransientRefresh($states, 'The Sunburst values could not be loaded.');
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
            '#echarts-sunburst-root',
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
                    && ($row['caption'] ?? null) === 'The first three rings retain their design settings. Deeper rings use the outer ring settings.') {
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
            throw new RuntimeException('The Sunburst Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECSB_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
