<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsIPSViewBackground;
use SymconECharts\EChartsIPSViewDesignForm;
use SymconECharts\EChartsIPSViewTransport;
use SymconECharts\EChartsVariablePresentation;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../libs/EChartsIPSViewBackground.php';
require_once __DIR__ . '/../libs/EChartsIPSViewDesignForm.php';
require_once __DIR__ . '/../libs/EChartsIPSViewTransport.php';
require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

class EChartsBarCategory extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use EChartsIPSViewDesignForm;
    use EChartsIPSViewTransport;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';
    private const MINIMUM_SOURCE_COUNT = 1;
    private const MAXIMUM_SOURCE_COUNT = 16;
    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_DESIGN_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewBarCategory';
    private const MODES = ['simple', 'grouped', 'stacked'];
    private const ORIENTATIONS = ['vertical', 'horizontal'];
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

        return $this->EncodeConfigurationForm($this->WithIPSViewDesignFormState($form, 'ECBC'));
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

    public function GetBarData(): string
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
                'value'      => $current['Value'],
                'timestamp'  => $current['Timestamp'],
                'color'      => $source['Color'] < 0 ? '' : EChartsAsset::ColorToHex($source['Color']),
                'order'      => $index
            ];
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'bar',
            'variant'       => 'category',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'bar'           => [
                'title'       => $this->ReadPropertyString('Title'),
                'mode'        => $this->ReadPropertyString('BarMode'),
                'orientation' => $this->ReadPropertyString('Orientation'),
                'sortOrder'   => $this->ReadPropertyString('SortOrder'),
                'unit'        => $sources[0]['Unit'],
                'decimals'    => max(array_column($sources, 'Decimals')),
                'style'       => $this->ReadDesignStyle()
            ],
            'items'         => $items
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetBarDiagnostic(): string
    {
        $configurationError = $this->GetConfigurationError();

        return json_encode([
            'valid'       => $configurationError === null,
            'sourceCount' => count($this->ConfiguredVariableIDs()),
            'lastError'   => $configurationError['Message'] ?? $this->ReadAttributeString('LastError')
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderBarHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderBarHTMLPage(true);
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

    private function RenderBarHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
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

    /** @return array{Status: int, Message: string}|null */
    private function GetConfigurationError(): ?array
    {
        try {
            $this->GetValidatedSources();
        } catch (Throwable $exception) {
            return ['Status' => self::STATUS_SOURCE_INVALID, 'Message' => $exception->getMessage()];
        }
        if (!$this->IsValidDesign('') || !$this->IsValidDesign('IPSView')
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
            throw new InvalidArgumentException('Configure 1 to 16 unique numeric sources with one common unit.');
        }

        $validated = [];
        $variableIDs = [];
        $commonUnit = null;
        foreach ($sources as $source) {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Every Category Bar source must be an object.');
            }
            $variableID = $source['VariableID'] ?? null;
            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)
                || in_array($variableID, $variableIDs, true)
            ) {
                throw new InvalidArgumentException('Configure 1 to 16 unique numeric sources with one common unit.');
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
            } elseif ($presentation['unit'] !== $commonUnit) {
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
                'Color'      => $color
            ];
        }

        return $validated;
    }

    /** @return array{Value: float, Timestamp: int} */
    private function ReadCurrentSource(int $variableID): array
    {
        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, ['VariableID' => $variableID]);
        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(self::DATA_ID_TO_PARENT, $request)),
                $operation
            );
        } catch (Throwable $exception) {
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned an invalid response.', 0, $exception);
        }
        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway request failed: ' . $errorCode);
        }
        $payload = $response['Payload'];
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if (($payload['VariableID'] ?? null) !== $variableID
            || (!is_int($value) && !is_float($value))
            || !is_int($timestamp)
        ) {
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        return ['Value' => (float) $value, 'Timestamp' => $timestamp];
    }

    private function SynchronizeSourceReferences(): void
    {
        $previous = $this->DecodeVariableIDList($this->ReadAttributeString('RegisteredSourceVariableIDs'));
        $current = array_values(array_filter(
            $this->ConfiguredVariableIDs(),
            static fn (int $variableID): bool => IPS_VariableExists($variableID)
        ));
        foreach (array_diff($previous, $current) as $variableID) {
            $this->UnregisterReference($variableID);
            $this->UnregisterMessage($variableID, VM_UPDATE);
        }
        foreach (array_diff($current, $previous) as $variableID) {
            $this->RegisterReference($variableID);
        }
        foreach ($current as $variableID) {
            $this->RegisterMessage($variableID, VM_UPDATE);
        }
        $this->WriteAttributeString('RegisteredSourceVariableIDs', json_encode($current, JSON_THROW_ON_ERROR));
    }

    /** @return list<int> */
    private function ConfiguredVariableIDs(): array
    {
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }
        $result = [];
        foreach ($sources as $source) {
            $variableID = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $result, true)) {
                $result[] = $variableID;
            }
        }
        sort($result);

        return $result;
    }

    /** @return list<int> */
    private function DecodeVariableIDList(string $json): array
    {
        $ids = json_decode($json, true);
        if (!is_array($ids) || !array_is_list($ids)) {
            return [];
        }
        $result = array_values(array_filter($ids, static fn (mixed $id): bool => is_int($id) && $id > 0));
        $result = array_values(array_unique($result));
        sort($result);

        return $result;
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
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

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
        $this->RegisterPropertyString($prefix . 'BarMode', 'simple');
        $this->RegisterPropertyString($prefix . 'Orientation', 'vertical');
        $this->RegisterPropertyString($prefix . 'SortOrder', 'configured');
        $this->RegisterPropertyString($prefix . 'EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyBoolean($prefix . 'ShowValues', true);
        $this->RegisterPropertyBoolean($prefix . 'ShowGrid', true);
        $this->RegisterPropertyBoolean($prefix . 'RoundedBars', false);
        $this->RegisterPropertyInteger($prefix . 'BarWidthPercent', 70);
    }

    /** @return array<string, string> */
    private function DesignPropertyNames(): array
    {
        return [
            'BarMode'         => 'string',
            'Orientation'     => 'string',
            'SortOrder'       => 'string',
            'EChartsTheme'    => 'string',
            'ShowValues'      => 'boolean',
            'ShowGrid'        => 'boolean',
            'RoundedBars'     => 'boolean',
            'BarWidthPercent' => 'integer'
        ];
    }

    private function IsValidDesign(string $prefix): bool
    {
        return in_array($this->ReadPropertyString($prefix . 'BarMode'), self::MODES, true)
            && in_array($this->ReadPropertyString($prefix . 'Orientation'), self::ORIENTATIONS, true)
            && in_array($this->ReadPropertyString($prefix . 'SortOrder'), self::SORT_ORDERS, true)
            && EChartsAsset::IsSupportedTheme($this->ReadPropertyString($prefix . 'EChartsTheme'))
            && $this->ReadPropertyInteger($prefix . 'BarWidthPercent') >= 20
            && $this->ReadPropertyInteger($prefix . 'BarWidthPercent') <= 100;
    }

    /** @return array{showValues:bool, showGrid:bool, roundedBars:bool, barWidthPercent:int} */
    private function ReadDesignStyle(string $prefix = ''): array
    {
        return [
            'showValues'      => $this->ReadPropertyBoolean($prefix . 'ShowValues'),
            'showGrid'        => $this->ReadPropertyBoolean($prefix . 'ShowGrid'),
            'roundedBars'     => $this->ReadPropertyBoolean($prefix . 'RoundedBars'),
            'barWidthPercent' => $this->ReadPropertyInteger($prefix . 'BarWidthPercent')
        ];
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
