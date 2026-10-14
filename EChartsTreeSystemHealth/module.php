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
use SymconECharts\TreeHealth;
use SymconECharts\TreeHierarchy;

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
require_once __DIR__ . '/../libs/TreeHierarchy.php';
require_once __DIR__ . '/TreeHealth.php';

class EChartsTreeSystemHealth extends IPSModuleStrict
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
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewTreeSystemHealth';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_READ_FAILED = 204;
    private const STATUS_RULES_INVALID = 205;
    private const DESIGN_DEFAULTS = [
        'EChartsTheme'         => EChartsAsset::THEME_AUTO,
        'TreeLayout'           => 'orthogonal',
        'Orientation'          => 'LR',
        'EdgeShape'            => 'curve',
        'InitialDepth'         => 2,
        'RoamEnabled'          => true,
        'ShowLabels'           => true,
        'ShowValues'           => false,
        'NodeSize'             => 10,
        'TitleFontSizePercent' => 100,
        'LabelFontSizePercent' => 100,
        'TitleColor'           => -1,
        'LabelColor'           => -1,
        'CategoryColor'        => -1,
        'InstanceColor'        => -1,
        'VariableColor'        => -1,
        'EdgeColor'            => -1,
        'HealthyColor'         => -1,
        'WarningColor'         => -1,
        'CriticalColor'        => -1
    ];

    public function Create(): void
    {
        parent::Create();
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('TreeRefresh', 0, 'ECTH_RefreshTree($_IPS["TARGET"]);');
        $this->RegisterTransientRefresh('ECTH');
        $this->RegisterPropertyInteger('RootObjectID', 0);
        $this->RegisterPropertyBoolean('UseSymconRoot', false);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyInteger('MaxDepth', 3);
        $this->RegisterPropertyInteger('MaxNodes', 80);
        $this->RegisterPropertyBoolean('IncludeHidden', false);
        $this->RegisterPropertyString('VariableNameFilter', '');
        $this->RegisterPropertyString('StatusRules', '[]');
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
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('System Health Tree for IPSView'), 90);
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
        foreach ($form['elements'] as &$element) {
            if (($element['name'] ?? null) === 'RootObjectID') {
                $element['enabled'] = !$this->ReadPropertyBoolean('UseSymconRoot');
                break;
            }
        }
        unset($element);
        $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        $form['elements'] = $this->WithAnimationFormControls($form['elements']);
        $form['elements'] = $this->WithAnimationFormVisibility($form['elements']);
        $form['elements'] = $this->WithAnimationFormCallbacks($form['elements'], 'ECTH');
        $form = $this->WithIPSViewDesignFormState($form, 'ECTH');
        $form = EChartsDesignerSections::Group($form, [
            ['caption' => 'Layout and interaction', 'start' => 'TreeLayout'],
            ['caption' => 'Labels and typography', 'start' => 'ShowLabels'],
            ['caption' => 'Node and line colors', 'start' => 'CategoryColor'],
            ['caption' => 'Status colors', 'start' => 'HealthyColor']
        ]);
        $fields = [
            'Title', 'UseSymconRoot', 'RootObjectID', 'MaxDepth', 'MaxNodes', 'IncludeHidden', 'VariableNameFilter', 'StatusRules',
            'IPSViewUseTileDesign', 'IPSViewAdaptToBackground', 'IPSViewBackgroundColor',
            'IPSViewBackgroundOpacityPercent',
            ...array_keys(self::DESIGN_DEFAULTS),
            ...array_map(static fn (string $name): string => 'IPSView' . $name, array_keys(self::DESIGN_DEFAULTS))
        ];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['AnimationEnabled', 'AnimationDuration', 'AnimationDurationUpdate', 'AnimationEasing',
                'AnimationEasingUpdate', 'AnimationDelay', 'AnimationDelayUpdate'] as $name) {
                $fields[] = $prefix . $name;
            }
        }
        return $this->EncodeConfigurationForm($this->WithStatusRuleForm(
            $this->WithBarPreviewForm($form, 'ECTH', $fields),
            $fields
        ));
    }

    public function GetStatusRuleEditorForm(string $Direction, float $WarningAt): string
    {
        $form = $this->LoadConfigurationForm();
        foreach ($form['elements'] as $panel) {
            if (($panel['caption'] ?? null) !== 'Plant status') {
                continue;
            }
            foreach ($panel['items'] as $item) {
                if (($item['name'] ?? null) !== 'StatusRules') {
                    continue;
                }
                $fields = $item['form'];
                foreach ($fields as &$field) {
                    if (($field['name'] ?? null) === 'CriticalAt') {
                        $field[$Direction === 'low' ? 'maximum' : 'minimum'] = $WarningAt;
                    } elseif (in_array($field['name'] ?? null, ['Direction', 'WarningAt'], true)) {
                        $field['onChange'] = 'ECTH_UpdateStatusRuleEditor($id, $Direction, $WarningAt);';
                    }
                }
                unset($field);
                return json_encode($fields, JSON_THROW_ON_ERROR);
            }
        }
        throw new RuntimeException('The Tree status rule editor is missing.');
    }

    public function UpdateStatusRuleEditor(string $Direction, float $WarningAt): void
    {
        if ($Direction === 'low') {
            $this->UpdateFormField('CriticalAt', 'minimum', -PHP_FLOAT_MAX);
            $this->UpdateFormField('CriticalAt', 'maximum', $WarningAt);
        } else {
            $this->UpdateFormField('CriticalAt', 'maximum', PHP_FLOAT_MAX);
            $this->UpdateFormField('CriticalAt', 'minimum', $WarningAt);
        }
    }

    public function UpdateStatusRulesForm(string $RulesJson): void
    {
        try {
            $rows = json_decode($RulesJson, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $rows = null;
        }
        $issues = $this->StatusRuleIssues($rows);
        if (is_array($rows) && array_is_list($rows)) {
            $this->UpdateFormField('StatusRules', 'values', json_encode(
                self::ColoredStatusRuleRows($rows, $issues),
                JSON_THROW_ON_ERROR
            ));
        }
        $this->UpdateFormField('StatusRulesDiagnostics', 'caption', $this->StatusRuleIssueText($issues));
        $this->UpdateFormField('StatusRulesDiagnostics', 'visible', $issues !== []);
    }

    public function UpdateImportThresholdBound(string $Direction, float $WarningAt): void
    {
        $this->UpdateFormField('ImportCriticalAt', 'minimum', $Direction === 'low' ? -PHP_FLOAT_MAX : $WarningAt);
        $this->UpdateFormField('ImportCriticalAt', 'maximum', $Direction === 'low' ? $WarningAt : PHP_FLOAT_MAX);
    }

    public function InvalidateStatusRuleCandidates(): void
    {
        $this->UpdateFormField('ImportCandidates', 'values', '[]');
        $this->UpdateFormField('ImportSearchSummary', 'caption', $this->Translate('Enter a name and click Search.'));
    }

    public function SearchStatusRuleCandidates(
        string $Name,
        string $Type,
        bool $UseSymconRoot,
        int $RootObjectID,
        int $MaxDepth,
        int $MaxNodes,
        bool $IncludeHidden,
        string $VariableNameFilter,
        string $RulesJson
    ): void {
        try {
            $rules = json_decode($RulesJson, true, 64, JSON_THROW_ON_ERROR);
            if ($this->StatusRuleIssues($rules) !== []) {
                throw new InvalidArgumentException('Correct the existing status rules before searching.');
            }
            $result = $this->FindStatusRuleCandidates(
                $Name,
                $Type,
                $UseSymconRoot,
                $RootObjectID,
                $MaxDepth,
                $MaxNodes,
                $IncludeHidden,
                $VariableNameFilter,
                $rules
            );
            $this->UpdateFormField('ImportCandidates', 'values', json_encode($result['rows'], JSON_THROW_ON_ERROR));
            $summary = sprintf(
                $this->Translate('%d matching variables; %d already have a rule.'),
                count($result['rows']),
                $result['existing']
            );
            if ($result['truncated']) {
                $summary .= ' ' . $this->Translate('The Tree node limit was reached; increase it to search further.');
            }
            $this->UpdateFormField('ImportSearchSummary', 'caption', $summary);
        } catch (Throwable $exception) {
            $this->UpdateFormField('ImportCandidates', 'values', '[]');
            $this->UpdateFormField('ImportSearchSummary', 'caption', $this->Translate($exception->getMessage()));
        }
    }

    public function AddStatusRuleCandidates(
        string $Name,
        string $Type,
        string $Direction,
        float $WarningAt,
        float $CriticalAt,
        bool $UseSymconRoot,
        int $RootObjectID,
        int $MaxDepth,
        int $MaxNodes,
        bool $IncludeHidden,
        string $VariableNameFilter,
        string $RulesJson,
        string $CandidatesJson,
        string $PreviewValuesJson
    ): string {
        try {
            if (!in_array($Direction, ['high', 'low'], true) || !is_finite($WarningAt) || !is_finite($CriticalAt)
                || ($Direction === 'high' && $CriticalAt < $WarningAt)
                || ($Direction === 'low' && $CriticalAt > $WarningAt)) {
                throw new InvalidArgumentException('Warning and critical thresholds are in the wrong order.');
            }
            $rules = json_decode($RulesJson, true, 64, JSON_THROW_ON_ERROR);
            $candidates = json_decode($CandidatesJson, true, 64, JSON_THROW_ON_ERROR);
            if ($this->StatusRuleIssues($rules) !== [] || !is_array($candidates) || !array_is_list($candidates)) {
                throw new InvalidArgumentException('Correct the status rule list and search again.');
            }
            $fresh = $this->FindStatusRuleCandidates(
                $Name,
                $Type,
                $UseSymconRoot,
                $RootObjectID,
                $MaxDepth,
                $MaxNodes,
                $IncludeHidden,
                $VariableNameFilter,
                $rules
            );
            $allowed = array_fill_keys(array_column($fresh['rows'], 'VariableID'), true);
            $selected = [];
            foreach ($candidates as $candidate) {
                if (!is_array($candidate) || ($candidate['Selected'] ?? false) !== true) {
                    continue;
                }
                $id = $candidate['VariableID'] ?? null;
                if (!is_int($id) || !isset($allowed[$id])) {
                    throw new InvalidArgumentException('The selected variables changed; search again.');
                }
                $selected[$id] = true;
            }
            if ($selected === []) {
                throw new InvalidArgumentException('Select at least one matching variable.');
            }
            if (count($rules) + count($selected) > 3000) {
                throw new InvalidArgumentException('At most 3000 status rules are allowed.');
            }
            foreach ($rules as &$rule) {
                unset($rule['rowColor']);
            }
            unset($rule);
            foreach (array_keys($selected) as $id) {
                $rules[] = [
                    'VariableID' => $id,
                    'Direction'  => $Direction,
                    'WarningAt'  => $WarningAt,
                    'CriticalAt' => $CriticalAt
                ];
            }
            $previewValues = json_decode($PreviewValuesJson, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($previewValues)) {
                throw new InvalidArgumentException('The draft preview settings are invalid.');
            }
            $previewValues['StatusRules'] = $rules;
            $this->UpdateStatusRulesForm(json_encode($rules, JSON_THROW_ON_ERROR));
            $this->UpdateBarPreviewFromForm(json_encode($previewValues, JSON_THROW_ON_ERROR));
            return 'MESSAGE:' . sprintf(
                $this->Translate('%d status rules added. Review them and apply the form to save.'),
                count($selected)
            );
        } catch (Throwable $exception) {
            return $this->Translate($exception->getMessage());
        }
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($this->HandleIPSViewHTMLPageAction($Ident, $Value)) {
            return;
        }
        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function UpdateTreeRootAvailability(bool $UseSymconRoot): void
    {
        $this->UpdateFormField('RootObjectID', 'enabled', !$UseSymconRoot);
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

    public function GetTreeData(): string
    {
        return $this->GetTreeDataForOutput(false);
    }

    public function GetTreeDiagnostic(): string
    {
        $error = $this->GetConfigurationError();
        return json_encode([
            'valid'     => $error === null,
            'lastError' => $error['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderTreeHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderTreeHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->HandleEChartsIPSViewRequest(
            $JSONString,
            self::DATA_ID_FROM_PARENT,
            fn (): array => $this->BuildVisualizationState(true)
        );
    }

    public function RefreshTree(): void
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
        $edgeColor = EChartsBarPreview::Color($this->BarPreviewValue($values, 'EdgeColor', 'integer', $ipsView), $palette['muted']);
        $colors = [
            'category' => EChartsBarPreview::Color($this->BarPreviewValue($values, 'CategoryColor', 'integer', $ipsView), $palette['seriesColors'][0]),
            'instance' => EChartsBarPreview::Color($this->BarPreviewValue($values, 'InstanceColor', 'integer', $ipsView), $palette['seriesColors'][1]),
            'variable' => EChartsBarPreview::Color($this->BarPreviewValue($values, 'VariableColor', 'integer', $ipsView), $palette['seriesColors'][2])
        ];
        $healthColors = [
            'ok'       => EChartsBarPreview::Color($this->BarPreviewValue($values, 'HealthyColor', 'integer', $ipsView), '#2EAD69'),
            'warning'  => EChartsBarPreview::Color($this->BarPreviewValue($values, 'WarningColor', 'integer', $ipsView), '#F3A43B'),
            'critical' => EChartsBarPreview::Color($this->BarPreviewValue($values, 'CriticalColor', 'integer', $ipsView), '#E54F55')
        ];
        $sample = true;
        $nodes = [
            ['name' => 'Haus', 'kind' => 'category'], ['name' => 'Wohnen', 'kind' => 'category'],
            ['name'                                           => 'Sensor', 'kind' => 'instance'], ['name' => 'Temperatur', 'kind' => 'variable', 'displayValue' => '21 °C']
        ];
        $useSymconRoot = (bool) ($values['UseSymconRoot'] ?? $this->ReadPropertyBoolean('UseSymconRoot'));
        $rootID = $useSymconRoot ? 0 : (int) ($values['RootObjectID'] ?? $this->ReadPropertyInteger('RootObjectID'));
        $variableNameFilter = trim((string) ($values['VariableNameFilter'] ?? $this->ReadPropertyString('VariableNameFilter')));
        try {
            $rules = TreeHealth::Parse($values['StatusRules'] ?? $this->ReadPropertyString('StatusRules'));
        } catch (Throwable) {
            $rules = [];
        }
        if ($useSymconRoot || ($rootID > 0 && IPS_ObjectExists($rootID))) {
            try {
                $tree = $this->ReadHierarchy(
                    $rootID,
                    $variableNameFilter === ''
                        ? min(3, (int) ($values['MaxDepth'] ?? $this->ReadPropertyInteger('MaxDepth')))
                        : (int) ($values['MaxDepth'] ?? $this->ReadPropertyInteger('MaxDepth')),
                    12,
                    (bool) ($values['IncludeHidden'] ?? $this->ReadPropertyBoolean('IncludeHidden')),
                    $rules,
                    $variableNameFilter
                );
                $nodes = $this->PreviewNodes($tree['root']);
                $sample = false;
            } catch (Throwable) {
                $sample = true;
            }
        }
        if ($sample && $rules !== []) {
            $nodes[0]['health'] = 'critical';
            $nodes[1]['health'] = 'warning';
            $nodes[3]['health'] = 'ok';
        }
        $svg = EChartsBarPreview::Open(
            $palette,
            $title,
            $sample,
            $ipsView,
            $values,
            $this->Translate('Example structure'),
            $this->Translate('Tree preview'),
            $titleColor,
            $this->BarPreviewValue($values, 'TitleFontSizePercent', 'integer', $ipsView)
        );
        $layout = $this->BarPreviewValue($values, 'TreeLayout', 'string', $ipsView);
        $orientation = $this->BarPreviewValue($values, 'Orientation', 'string', $ipsView);
        $edgeShape = $this->BarPreviewValue($values, 'EdgeShape', 'string', $ipsView);
        $nodeSize = max(4, min(22, $this->BarPreviewValue($values, 'NodeSize', 'integer', $ipsView)));
        $labelSize = max(8, min(20, (int) round(11 * $this->BarPreviewValue($values, 'LabelFontSizePercent', 'integer', $ipsView) / 100)));
        $showLabels = $this->BarPreviewValue($values, 'ShowLabels', 'boolean', $ipsView);
        $showValues = $this->BarPreviewValue($values, 'ShowValues', 'boolean', $ipsView);
        $positions = $layout === 'radial'
            ? [[360, 190], [225, 115], [510, 120], [390, 285]]
            : [[100, 185], [265, 100], [435, 170], [600, 255]];
        if ($layout !== 'radial') {
            $positions = array_map(static function (array $point) use ($orientation): array
            {
                return match ($orientation) {
                    'RL'    => [720 - $point[0], $point[1]],
                    'TB'    => [60 + $point[1] * 1.55, 35 + $point[0] * 0.43],
                    'BT'    => [60 + $point[1] * 1.55, 325 - $point[0] * 0.43],
                    default => $point
                };
            }, $positions);
        }
        $nodes = array_slice($nodes, 0, 4);
        foreach ($nodes as $index => $node) {
            [$x, $y] = $positions[$index];
            if ($index > 0) {
                [$px, $py] = $positions[$index - 1];
                $path = $edgeShape === 'polyline'
                    ? 'M ' . $px . ' ' . $py . ' L ' . (($px + $x) / 2) . ' ' . $py . ' L ' . (($px + $x) / 2) . ' ' . $y . ' L ' . $x . ' ' . $y
                    : 'M ' . $px . ' ' . $py . ' Q ' . (($px + $x) / 2) . ' ' . (($py + $y) / 2 - 35) . ' ' . $x . ' ' . $y;
                $svg .= '<path d="' . EChartsBarPreview::E($path) . '" fill="none" stroke="' . EChartsBarPreview::E($edgeColor) . '" stroke-width="2"/>';
            }
            $kind = (string) ($node['kind'] ?? 'category');
            $health = (string) ($node['health'] ?? '');
            $color = $healthColors[$health] ?? ($colors[$kind] ?? $colors['category']);
            $svg .= '<circle cx="' . EChartsBarPreview::N($x) . '" cy="' . EChartsBarPreview::N($y)
                . '" r="' . EChartsBarPreview::N($nodeSize / 2) . '" fill="' . EChartsBarPreview::E($color) . '"/>';
            if ($showLabels) {
                $name = mb_strimwidth((string) ($node['name'] ?? ''), 0, 14, '…');
                if ($showValues && $kind === 'variable') {
                    $name .= ' ' . mb_strimwidth((string) ($node['displayValue'] ?? ''), 0, 12, '…');
                }
                $svg .= '<text x="' . EChartsBarPreview::N($x + 9) . '" y="' . EChartsBarPreview::N($y - 10)
                    . '" fill="' . EChartsBarPreview::E($labelColor) . '" font-size="' . $labelSize . '">'
                    . EChartsBarPreview::E($name) . '</text>';
            }
        }
        return $svg . '</svg>';
    }

    /** @param list<array<string,mixed>> $rules
     *  @return array{rows:list<array<string,mixed>>,existing:int,truncated:bool}
     */
    private function FindStatusRuleCandidates(
        string $name,
        string $type,
        bool $useSymconRoot,
        int $rootObjectID,
        int $maxDepth,
        int $maxNodes,
        bool $includeHidden,
        string $variableNameFilter,
        array $rules
    ): array {
        $name = trim($name);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || !in_array($type, ['numeric', 'int', 'float'], true)) {
            throw new InvalidArgumentException('Enter a search term of at most 120 characters and a numeric type.');
        }
        if (!$useSymconRoot && ($rootObjectID < 1 || !IPS_ObjectExists($rootObjectID)
            || !in_array(IPS_GetObject($rootObjectID)['ObjectType'] ?? null, [0, 1], true))) {
            throw new InvalidArgumentException('Select a valid Tree root before searching.');
        }
        $hierarchy = TreeHierarchy::Build(
            $useSymconRoot ? 0 : $rootObjectID,
            $maxDepth,
            $maxNodes,
            $includeHidden,
            static fn (int $id): array => $id === 0
                ? ['ObjectType' => 0, 'ObjectName' => 'Symcon', 'ObjectIsHidden' => false]
                : IPS_GetObject($id),
            static fn (int $id): array => IPS_GetChildrenIDs($id),
            static fn (int $id): string => '',
            $variableNameFilter
        );
        $existingIDs = array_fill_keys(array_column($rules, 'VariableID'), true);
        $typeLabels = [1 => $this->Translate('Integer'), 2 => $this->Translate('Float')];
        $rows = [];
        $existing = 0;
        $walk = static function (array $node, string $parent) use (
            &$walk,
            &$rows,
            &$existing,
            $name,
            $type,
            $existingIDs,
            $typeLabels
        ): void {
            $nodeName = (string) ($node['name'] ?? '');
            $path = $parent === '' ? $nodeName : $parent . ' > ' . $nodeName;
            if (($node['kind'] ?? null) === 'variable'
                && mb_stripos($nodeName, $name, 0, 'UTF-8') !== false) {
                $id = (int) substr((string) $node['id'], 7);
                if (IPS_VariableExists($id)) {
                    $variableType = IPS_GetVariable($id)['VariableType'] ?? null;
                    if (($type === 'numeric' && in_array($variableType, [1, 2], true))
                        || ($type === 'int' && $variableType === 1)
                        || ($type === 'float' && $variableType === 2)) {
                        if (isset($existingIDs[$id])) {
                            ++$existing;
                        } else {
                            $rows[] = [
                                'Selected'     => true,
                                'Name'         => $nodeName,
                                'Path'         => $path,
                                'VariableType' => $typeLabels[$variableType],
                                'VariableID'   => $id
                            ];
                        }
                    }
                }
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child, $path);
            }
        };
        $walk($hierarchy['root'], '');
        return ['rows' => $rows, 'existing' => $existing, 'truncated' => $hierarchy['truncated']];
    }

    /** @param array<string,mixed> $node @return list<array<string,mixed>> */
    private function PreviewNodes(array $node): array
    {
        $nodes = [$node];
        foreach ($node['children'] ?? [] as $child) {
            array_push($nodes, ...$this->PreviewNodes($child));
        }
        return $nodes;
    }

    private function GetTreeDataForOutput(bool $ipsView): string
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
            $hierarchy = $this->ReadHierarchy(
                $this->ReadPropertyBoolean('UseSymconRoot') ? 0 : $this->ReadPropertyInteger('RootObjectID'),
                $this->ReadPropertyInteger('MaxDepth'),
                $this->ReadPropertyInteger('MaxNodes'),
                $this->ReadPropertyBoolean('IncludeHidden'),
                TreeHealth::Parse($this->ReadPropertyString('StatusRules')),
                $this->ReadPropertyString('VariableNameFilter')
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', $exception->getMessage());
            $this->SetStatus(self::STATUS_READ_FAILED);
            throw $exception;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'tree',
            'theme'         => $this->EffectiveTheme($ipsView),
            'tree'          => [
                'title'     => $this->ReadPropertyString('Title'),
                'style'     => $this->ReadDesignStyle($ipsView),
                'root'      => $hierarchy['root'],
                'count'     => $hierarchy['count'],
                'truncated' => $hierarchy['truncated']
            ]
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array{root:array<string,mixed>,count:int,truncated:bool,variableIDs:list<int>} */
    private function ReadHierarchy(
        int $rootID,
        int $maxDepth,
        int $maxNodes,
        bool $includeHidden,
        array $rules,
        string $variableNameFilter
    ): array {
        $hierarchy = TreeHierarchy::Build(
            $rootID,
            $maxDepth,
            $maxNodes,
            $includeHidden,
            static fn (int $id): array => $id === 0
                ? ['ObjectType' => 0, 'ObjectName' => 'Symcon', 'ObjectIsHidden' => false]
                : IPS_GetObject($id),
            static fn (int $id): array => IPS_GetChildrenIDs($id),
            static fn (int $id): string => (string) GetValueFormatted($id),
            $variableNameFilter
        );
        $hierarchy['root'] = TreeHealth::Annotate(
            $hierarchy['root'],
            $rules,
            static fn (int $id): mixed => GetValue($id)
        );
        return $hierarchy;
    }

    private function RenderTreeHTMLPage(bool $ipsView): string
    {
        $hiddenTitle = !$ipsView && function_exists('IPS_GetObject')
            && (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        $state = $this->BuildVisualizationState($ipsView);
        $compressedState = null;
        $stateJSON = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($stateJSON) > 200000) {
            $gzip = gzencode($stateJSON, 6);
            if ($gzip === false) {
                throw new RuntimeException('The Tree startup state could not be compressed.');
            }
            $compressedState = base64_encode($gzip);
            $state = null;
        }
        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Tree System Health',
            'visualizationTheme' => $this->VisualizationThemeCSS() . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-tree-root', 'echarts-tree'),
            'ipsViewStyle' => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'        => $state,
            'translations' => [
                'Select a valid Tree root.'                                         => $this->Translate('Select a valid Tree root.'),
                'Select a valid Tree root and limits.'                              => $this->Translate('Select a valid Tree root and limits.'),
                'The Tree design is invalid.'                                       => $this->Translate('The Tree design is invalid.'),
                'Connect an active EChartsGateway.'                                 => $this->Translate('Connect an active EChartsGateway.'),
                'The Tree could not be loaded.'                                     => $this->Translate('The Tree could not be loaded.'),
                'Only the first configured nodes are shown.'                        => $this->Translate('Only the first configured nodes are shown.'),
                'Status: OK'                                                        => $this->Translate('Status: OK'),
                'Status: Warning'                                                   => $this->Translate('Status: Warning'),
                'Status: Critical'                                                  => $this->Translate('Status: Critical'),
                'The Tree status rules are invalid.'                                => $this->Translate('The Tree status rules are invalid.'),
                'Data could not be refreshed. Showing the last available values.'   => $this->Translate(
                    'Data could not be refreshed. Showing the last available values.'
                )
            ],
            'options' => [
                'echartsVersion'            => EChartsAsset::VERSION,
                'echartsThemes'             => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible'         => !$hiddenTitle,
                'adaptToBackground'         => $ipsView && $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
                'ipsViewTransport'          => $ipsView ? $this->EChartsIPSViewTransportOptions() : null,
                'compressedStateGzipBase64' => $compressedState
            ],
            'replacements' => [
                '{{ECHARTS_SCRIPT}}'           => EChartsAsset::TreeJavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}'     => EChartsAsset::ThemeJavaScript(),
                '{{ECHARTS_DESIGN_SCRIPT}}'    => EChartsAsset::DesignJavaScript(),
                '{{IPSVIEW_TRANSPORT_SCRIPT}}' => $ipsView ? $this->EChartsIPSViewTransportJavaScript() : ''
            ]
        ]);
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        $this->SetTimerInterval('TreeRefresh', 0);
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
        $this->SetTimerInterval('TreeRefresh', 60000);
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);
    }

    /** @return array{Status:int,Message:string}|null */
    private function GetConfigurationError(): ?array
    {
        $rootID = $this->ReadPropertyInteger('RootObjectID');
        if ((!$this->ReadPropertyBoolean('UseSymconRoot')
            && ($rootID < 1 || !IPS_ObjectExists($rootID)
                || !in_array(IPS_GetObject($rootID)['ObjectType'] ?? null, [0, 1], true)))
            || $this->ReadPropertyInteger('MaxDepth') < 1 || $this->ReadPropertyInteger('MaxDepth') > 6
            || $this->ReadPropertyInteger('MaxNodes') < 10 || $this->ReadPropertyInteger('MaxNodes') > 3000
            || mb_strlen(trim($this->ReadPropertyString('VariableNameFilter')), 'UTF-8') > 120) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => 'Select a valid Tree root and limits.'];
        }
        try {
            $rules = TreeHealth::Parse($this->ReadPropertyString('StatusRules'));
            foreach (array_keys($rules) as $id) {
                if (!IPS_VariableExists($id)
                    || !in_array(IPS_GetVariable($id)['VariableType'] ?? null, [0, 1, 2], true)) {
                    throw new InvalidArgumentException('The Tree status rules are invalid.');
                }
            }
        } catch (Throwable) {
            return ['Status' => self::STATUS_RULES_INVALID, 'Message' => 'The Tree status rules are invalid.'];
        }
        if (!$this->IsValidDesign('')
            || (!$this->ReadPropertyBoolean('IPSViewUseTileDesign') && !$this->IsValidDesign('IPSView'))
            || !EChartsIPSViewBackground::IsValid(
                $this->ReadPropertyInteger('IPSViewBackgroundColor'),
                $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
            )) {
            return ['Status' => self::STATUS_DESIGN_INVALID, 'Message' => 'The Tree design is invalid.'];
        }
        return null;
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'TreeLayout'), ['orthogonal', 'radial'], true)
            || !in_array($this->ReadPropertyString($prefix . 'Orientation'), ['LR', 'RL', 'TB', 'BT'], true)
            || !in_array($this->ReadPropertyString($prefix . 'EdgeShape'), ['curve', 'polyline'], true)
            || $this->ReadPropertyInteger($prefix . 'InitialDepth') < 0
            || $this->ReadPropertyInteger($prefix . 'InitialDepth') > 6
            || $this->ReadPropertyInteger($prefix . 'NodeSize') < 4
            || $this->ReadPropertyInteger($prefix . 'NodeSize') > 30
            || !$this->AnimationIsValid($prefix)) {
            return false;
        }
        foreach (['TitleFontSizePercent', 'LabelFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 200) {
                return false;
            }
        }
        foreach (['TitleColor', 'LabelColor', 'CategoryColor', 'InstanceColor', 'VariableColor', 'EdgeColor',
            'HealthyColor', 'WarningColor', 'CriticalColor'] as $name) {
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
            $chart = json_decode($this->GetTreeDataForOutput($ipsView), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);
            return $this->ErrorState('The Tree could not be loaded.');
        }
        return ['schemaVersion' => 1, 'family' => 'tree', 'status' => 'ready', 'chart' => $chart, 'error' => null];
    }

    /** @return array<string,mixed> */
    private function ErrorState(string $message): array
    {
        return ['schemaVersion' => 1, 'family' => 'tree', 'status' => 'error', 'chart' => null, 'error' => $message];
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
            $this->ScheduleTransientRefresh($states, 'The Tree could not be loaded.');
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
            '#echarts-tree-root',
            $this->ReadPropertyBoolean('IPSViewAdaptToBackground'),
            $this->ReadPropertyInteger('IPSViewBackgroundColor'),
            $this->ReadPropertyInteger('IPSViewBackgroundOpacityPercent')
        );
    }

    /** @param array<string,mixed> $form @param list<string> $fields @return array<string,mixed> */
    private function WithStatusRuleForm(array $form, array $fields): array
    {
        $stored = $this->ReadPropertyString('StatusRules');
        $rows = json_decode($stored, true);
        $issues = $this->StatusRuleIssues($stored);
        foreach ($form['elements'] as &$panel) {
            if (($panel['caption'] ?? null) !== 'Plant status') {
                continue;
            }
            foreach ($panel['items'] as &$item) {
                if (($item['name'] ?? null) === 'StatusRuleImport') {
                    $draftPairs = array_map(
                        static fn (string $field): string => var_export($field, true) . ' => $' . $field,
                        $fields
                    );
                    $button = &$item['popup']['buttons'][0];
                    $click = (string) $button['onClick'];
                    if (!str_ends_with($click, ');')) {
                        throw new RuntimeException('The Tree status import action is invalid.');
                    }
                    $button['onClick'] = substr($click, 0, -2)
                        . ', json_encode([' . implode(', ', $draftPairs) . ']));';
                    unset($button);
                } elseif (($item['name'] ?? null) === 'StatusRules') {
                    $previewAction = trim((string) ($item['onChange'] ?? ''));
                    $previewAction = str_replace(
                        "'StatusRules' => \$StatusRules",
                        "'StatusRules' => \$statusRuleRows",
                        $previewAction
                    );
                    unset($item['onChange']);
                    $item['form'] = 'return json_decode(ECTH_GetStatusRuleEditorForm($id, '
                        . '(string) ($StatusRules["Direction"] ?? "high"), '
                        . '(float) ($StatusRules["WarningAt"] ?? 1)), true);';
                    $action = '$statusRuleRows = []; foreach ($StatusRules as $statusRuleRow) {'
                        . ' $statusRuleRows[] = $statusRuleRow; } '
                        . 'ECTH_UpdateStatusRulesForm($id, json_encode($statusRuleRows));';
                    foreach (['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'] as $event) {
                        $item[$event] = $action . ($previewAction === '' ? '' : ' ' . $previewAction);
                    }
                    if (is_array($rows) && array_is_list($rows)) {
                        $item['values'] = self::ColoredStatusRuleRows($rows, $issues);
                    }
                } elseif (($item['name'] ?? null) === 'StatusRulesDiagnostics') {
                    $item['caption'] = $this->StatusRuleIssueText($issues);
                    $item['visible'] = $issues !== [];
                }
            }
            unset($item);
            break;
        }
        unset($panel);
        return $form;
    }

    /** @return list<array{row:int,code:string,id?:int,firstRow?:int}> */
    private function StatusRuleIssues(mixed $input): array
    {
        $issues = TreeHealth::Issues($input);
        $rows = is_string($input) ? json_decode($input, true) : $input;
        if (!is_array($rows) || !array_is_list($rows)) {
            return $issues;
        }
        $checked = [];
        foreach ($rows as $index => $row) {
            $id = is_array($row) ? ($row['VariableID'] ?? null) : null;
            if (!is_int($id) || $id < 1 || isset($checked[$id])) {
                continue;
            }
            $checked[$id] = true;
            if (!IPS_VariableExists($id)) {
                $issues[] = ['row' => $index + 1, 'code' => 'missing', 'id' => $id];
            } elseif (!in_array(IPS_GetVariable($id)['VariableType'] ?? null, [0, 1, 2], true)) {
                $issues[] = ['row' => $index + 1, 'code' => 'type', 'id' => $id];
            }
        }
        return $issues;
    }

    /** @param list<array<string,mixed>> $rows
     *  @param list<array{row:int,code:string,id?:int,firstRow?:int}> $issues
     *  @return list<array<string,mixed>>
     */
    private static function ColoredStatusRuleRows(array $rows, array $issues): array
    {
        $invalid = [];
        foreach ($issues as $issue) {
            if ($issue['row'] > 0) {
                $invalid[$issue['row']] = true;
            }
            if (isset($issue['firstRow'])) {
                $invalid[$issue['firstRow']] = true;
            }
        }
        foreach ($rows as $index => &$row) {
            if (is_array($row)) {
                $row['rowColor'] = isset($invalid[$index + 1]) ? '#C94B4B' : '';
            }
        }
        unset($row);
        return $rows;
    }

    /** @param list<array{row:int,code:string,id?:int,firstRow?:int}> $issues */
    private function StatusRuleIssueText(array $issues): string
    {
        $messages = [];
        foreach (array_slice($issues, 0, 12) as $issue) {
            $messages[] = match ($issue['code']) {
                'duplicate' => sprintf(
                    $this->Translate('Rule %d: variable %s is already used in rule %d.'),
                    $issue['row'],
                    $this->StatusRuleVariableLabel($issue['id']),
                    $issue['firstRow']
                ),
                'threshold' => sprintf($this->Translate(
                    'Rule %d: variable %s has warning and critical thresholds in the wrong order.'
                ), $issue['row'], $this->StatusRuleVariableLabel($issue['id'])),
                'missing' => sprintf(
                    $this->Translate('Rule %d: variable %d no longer exists.'),
                    $issue['row'],
                    $issue['id']
                ),
                'type' => sprintf(
                    $this->Translate('Rule %d: variable %d must be Boolean or numeric.'),
                    $issue['row'],
                    $issue['id']
                ),
                'too_many' => $this->Translate('At most 3000 status rules are allowed.'),
                default    => $issue['row'] > 0
                    ? sprintf($this->Translate('Rule %d is incomplete or invalid.'), $issue['row'])
                    : $this->Translate('The status rule list is invalid.')
            };
        }
        if (count($issues) > 12) {
            $messages[] = sprintf($this->Translate('Further status rule errors: %d.'), count($issues) - 12);
        }
        return implode('  ', $messages);
    }

    private function StatusRuleVariableLabel(int $id): string
    {
        return IPS_VariableExists($id) ? IPS_GetName($id) . ' (ID ' . $id . ')' : (string) $id;
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
            throw new RuntimeException('The Tree Tile designer section is missing.');
        }
        return [
            'type'  => 'ExpansionPanel', 'caption' => 'IPSView design', 'expanded' => false,
            'items' => [
                ...$this->IPSViewHTMLPageFormItems('Creates a standalone WebContent variable for IPSView.'),
                EChartsIPSViewBackground::FormRow(),
                ['type' => 'CheckBox', 'name' => 'IPSViewUseTileDesign', 'caption' => 'Use Tile design'],
                ['type'       => 'Button', 'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECTH_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'],
                ['type'     => 'ExpansionPanel', 'caption' => 'Independent IPSView designer', 'expanded' => false,
                    'items' => $rows]
            ]
        ];
    }
}
