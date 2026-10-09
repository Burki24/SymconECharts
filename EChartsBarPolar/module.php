<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsCurrentSources;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsSourceIdentity;
use SymconECharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsCurrentSources.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsSourceIdentity.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarPolar extends IPSModuleStrict
{
    use ConfigurationFormHelper;
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
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarPolar';
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const POLAR_MODES = ['radial', 'tangential'];
    private const SORT_ORDERS = ['configured', 'ascending', 'descending'];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyString('Sources', '[]');
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
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Polar for IPSView'), 90);
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
            $this->InsertIPSViewHTMLPageFormItems(
                $form['elements'],
                'Configure optional IPSView HTML output.',
                'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
            );
        }

        return $this->EncodeConfigurationForm($this->WithIPSViewDesignFormState($form, 'ECBP'));
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

    public function GetPolarData(): string
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
        $items = [];
        foreach ($sources as $index => $source) {
            $current = $this->ReadCurrentSource($source['VariableID']);
            $items[] = [
                'id'        => 'variable-' . $source['VariableID'],
                'label'     => $labels[$source['VariableID']],
                'value'     => $current['Value'],
                'decimals'  => $source['Decimals'],
                'color'     => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color']),
                'order'     => $index,
                'timestamp' => $current['Timestamp']
            ];
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'polar',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'polar'         => [
                'title'    => $this->ReadPropertyString('Title'),
                'unit'     => $sources[0]['Unit'],
                'decimals' => max(array_column($sources, 'Decimals')),
                'style'    => $this->ReadDesignStyle()
            ],
            'items'         => $items
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderPolarHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderPolarHTMLPage(true);
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

    private function RenderPolarHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage($this->ResolveHelperTranslationLanguage()),
            'title'              => 'ECharts Polar',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-bar-polar-root', 'echarts-bar-polar'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure valid Polar sources.'            => $this->Translate('Configure valid Polar sources.'),
                'Connect an active EChartsGateway.'         => $this->Translate('Connect an active EChartsGateway.'),
                'The Polar values could not be loaded.'     => $this->Translate('The Polar values could not be loaded.')
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
        if (!is_array($sources) || !array_is_list($sources) || count($sources) < 1 || count($sources) > 16) {
            throw new InvalidArgumentException('Configure 1 to 16 numeric Polar sources.');
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
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6
                || !is_string($unit) || !is_string($label)
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
            if ($commonUnit !== null && $presentation['unit'] !== $commonUnit) {
                throw new InvalidArgumentException('All Polar sources must use the same unit.');
            }
            $commonUnit = $presentation['unit'];
            $variableIDs[] = $variableID;
            $validated[] = [
                'VariableID' => $variableID,
                'Label'      => trim($label),
                'Unit'       => $presentation['unit'],
                'Decimals'   => $presentation['decimals'],
                'Color'      => $color
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
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return $this->ErrorState('The Polar values could not be loaded.');
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
        $this->RegisterPropertyString($prefix . 'PolarMode', 'radial');
        $this->RegisterPropertyString($prefix . 'SortOrder', 'configured');
        $this->RegisterPropertyBoolean($prefix . 'ShowCategoryLabels', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 60);
        $this->RegisterPropertyInteger($prefix . 'InnerRadiusPercent', 12);
        $this->RegisterPropertyInteger($prefix . 'OuterRadiusPercent', 76);
        $this->RegisterPropertyInteger($prefix . 'StartAngle', 90);
        $this->RegisterPropertyBoolean($prefix . 'Clockwise', true);
        $this->RegisterPropertyBoolean($prefix . 'RoundCaps', false);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'EChartsTheme'       => 'string',
            'PolarMode'          => 'string',
            'SortOrder'          => 'string',
            'ShowCategoryLabels' => 'boolean',
            'ShowValues'         => 'boolean',
            'ShowGrid'           => 'boolean',
            'BarWidthPercent'    => 'integer',
            'InnerRadiusPercent' => 'integer',
            'OuterRadiusPercent' => 'integer',
            'StartAngle'         => 'integer',
            'Clockwise'          => 'boolean',
            'RoundCaps'          => 'boolean'
        ];
    }

    private function IsValidDesign(string $prefix): bool
    {
        if (!EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            || !in_array($this->ReadPropertyString($prefix . 'PolarMode'), self::POLAR_MODES, true)
            || !in_array($this->ReadPropertyString($prefix . 'SortOrder'), self::SORT_ORDERS, true)
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'BarWidthPercent') > 100
            || $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') < 0
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent') > 95
            || $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent')
                - $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent') < 20
            || $this->ReadPropertyInteger($prefix . 'StartAngle') < 0
            || $this->ReadPropertyInteger($prefix . 'StartAngle') > 360
        ) {
            return false;
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        return [
            'mode'               => $this->ReadPropertyString($prefix . 'PolarMode'),
            'sortOrder'          => $this->ReadPropertyString($prefix . 'SortOrder'),
            'showCategoryLabels' => $this->ReadPropertyBoolean($prefix . 'ShowCategoryLabels'),
            'showValues'         => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'showGrid'           => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'barWidthPercent'    => $this->ReadPropertyInteger($prefix . 'BarWidthPercent'),
            'innerRadiusPercent' => $this->ReadPropertyInteger($prefix . 'InnerRadiusPercent'),
            'outerRadiusPercent' => $this->ReadPropertyInteger($prefix . 'OuterRadiusPercent'),
            'startAngle'         => $this->ReadPropertyInteger($prefix . 'StartAngle'),
            'clockwise'          => $this->ReadPropertyBoolean($prefix . 'Clockwise'),
            'roundCaps'          => $this->ReadPropertyBoolean($prefix . 'RoundCaps')
        ];
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

