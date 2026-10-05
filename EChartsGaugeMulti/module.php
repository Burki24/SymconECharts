<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\ConfigurationFormHelper;
use Burki24\SymconModuleHelper\DataFlowHelper;
use Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;
use Burki24\SymconModuleHelper\ResponsiveVisualizationHelper;
use Burki24\SymconModuleHelper\SVGPreviewHelper;
use Burki24\SymconModuleHelper\VisualizationAssetHelper;
use Burki24\SymconModuleHelper\VisualizationThemeHelper;
use SymconECharts\EChartsAsset;
use SymconECharts\EChartsDataProtocol;
use SymconECharts\EChartsGaugeMultiPreview;

require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/IPSViewHTMLPageHelper.php';
require_once __DIR__ . '/../libs/helper/ResponsiveVisualizationHelper.php';
require_once __DIR__ . '/../libs/helper/SVGPreviewHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationThemeHelper.php';
require_once __DIR__ . '/../libs/EChartsAsset.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/GaugePreview.php';

class EChartsGaugeMulti extends IPSModuleStrict
{
    use ConfigurationFormHelper;
    use DataFlowHelper;
    use IPSViewHTMLPageHelper;
    use ResponsiveVisualizationHelper;
    use VisualizationAssetHelper;
    use VisualizationThemeHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';

    private const MINIMUM_SOURCE_COUNT = 2;
    private const MAXIMUM_SOURCE_COUNT = 16;

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_DESIGN_INVALID = 205;

    private const PRESET_MULTI_TITLE = 'multi-title';
    private const PRESET_RING_GRID = 'ring-grid';
    private const PRESET_RING_CONCENTRIC = 'ring-concentric';
    private const PRESET_WEATHER_STATION = 'weather-station';
    private const PRESET_TACHO = 'tacho';
    private const PRESET_CHRONOGRAPH = 'chronograph';
    private const SUPPORTED_PRESETS = [
        self::PRESET_MULTI_TITLE, self::PRESET_RING_GRID, self::PRESET_RING_CONCENTRIC,
        self::PRESET_WEATHER_STATION, self::PRESET_TACHO, self::PRESET_CHRONOGRAPH
    ];
    private const IPSVIEW_OUTPUT_IDENT = 'IPSViewGauge';
    private const DESIGN_SCALE_PROPERTIES = [
        'RingWidthPercent', 'ScaleFontSizePercent', 'ValueFontSizePercent', 'TitleFontSizePercent'
    ];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('GaugePreset', self::PRESET_MULTI_TITLE);
        $this->RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO);
        $this->RegisterPropertyInteger('RingWidthPercent', 100);
        $this->RegisterPropertyInteger('ScaleFontSizePercent', 100);
        $this->RegisterPropertyInteger('ValueFontSizePercent', 100);
        $this->RegisterPropertyInteger('TitleFontSizePercent', 100);
        $this->RegisterIPSViewHTMLPageProperties();
        $this->RegisterPropertyBoolean('IPSViewUseTileDesign', true);
        $this->RegisterPropertyString('IPSViewGaugePreset', self::PRESET_MULTI_TITLE);
        $this->RegisterPropertyString('IPSViewEChartsTheme', EChartsAsset::THEME_AUTO);
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            $this->RegisterPropertyInteger('IPSView' . $name, 100);
        }
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->MaintainIPSViewHTMLVariable(self::IPSVIEW_OUTPUT_IDENT, $this->Translate('Gauge for IPSView'), 90);
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
            $form['elements'][] = $this->BuildIPSViewDesigner($form['elements']);
        }
        $form = SVGPreviewHelper::withImage(
            $form,
            'GaugePreview',
            EChartsGaugeMultiPreview::CreateSvg(
                $this->PreviewItems(),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('EChartsTheme'),
                $this->ReadPropertyString('GaugePreset')
            )
        );
        $form = SVGPreviewHelper::withImage(
            $form,
            'IPSViewGaugePreview',
            EChartsGaugeMultiPreview::CreateSvg(
                $this->PreviewItems(),
                $this->ReadPropertyString('Title'),
                $this->EffectiveIPSViewTheme(),
                $this->ReadPropertyBoolean('IPSViewUseTileDesign')
                    ? $this->ReadPropertyString('GaugePreset')
                    : $this->ReadPropertyString('IPSViewGaugePreset')
            )
        );

        return $this->EncodeConfigurationForm($form);
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
        IPS_SetProperty($this->InstanceID, 'IPSViewGaugePreset', $this->ReadPropertyString('GaugePreset'));
        IPS_SetProperty($this->InstanceID, 'IPSViewEChartsTheme', $this->ReadPropertyString('EChartsTheme'));
        foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
            IPS_SetProperty($this->InstanceID, 'IPSView' . $name, $this->ReadPropertyInteger($name));
        }
        IPS_SetProperty($this->InstanceID, 'IPSViewUseTileDesign', false);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function GetGaugeData(): string
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

        $items = [];
        foreach ($this->GetValidatedSources() as $source) {
            $current = $this->ReadCurrentSource($source['VariableID']);
            $label = $source['Label'] !== '' ? $source['Label'] : IPS_GetName($source['VariableID']);
            $items[] = [
                'id'     => 'variable-' . $source['VariableID'],
                'source' => [
                    'variableID' => $source['VariableID'],
                    'timestamp'  => $current['Timestamp']
                ],
                'gauge'  => [
                    'label'    => $label,
                    'minimum'  => $source['Minimum'],
                    'maximum'  => $source['Maximum'],
                    'unit'     => $source['Unit'],
                    'decimals' => $source['Decimals']
                ],
                'value'  => $current['Value']
            ];
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'multi',
            'theme'         => $this->ReadPropertyString('EChartsTheme'),
            'gauge'         => [
                'title'  => $this->ReadPropertyString('Title'),
                'preset' => $this->ReadPropertyString('GaugePreset'),
                'style'  => [
                    'ringWidthPercent'     => $this->ReadPropertyInteger('RingWidthPercent'),
                    'scaleFontSizePercent' => $this->ReadPropertyInteger('ScaleFontSizePercent'),
                    'valueFontSizePercent' => $this->ReadPropertyInteger('ValueFontSizePercent'),
                    'titleFontSizePercent' => $this->ReadPropertyInteger('TitleFontSizePercent')
                ]
            ],
            'items'         => $items
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderGaugeHTMLPage(false);
    }

    public function GetIPSViewHTML(): string
    {
        return $this->RenderGaugeHTMLPage(true);
    }

    public function ReceiveData(string $JSONString): string
    {
        try {
            $message = $this->DecodeDataFlowMessage($JSONString, self::DATA_ID_FROM_PARENT);
            EChartsDataProtocol::DecodeRequest($message);
        } catch (Throwable $exception) {
            $this->SendDebug('ReceiveData', $exception::class, 0);
        }

        return '';
    }

    /**
     * Revalidates the module after the Symcon kernel becomes ready.
     *
     * @param array<int, mixed> $Data
     */
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
            $this->PublishIPSViewHTML();
        }
    }

    private function RenderGaugeHTMLPage(bool $ipsView): string
    {
        $hiddenTileTitle = false;
        if (!$ipsView && function_exists('IPS_GetObject')) {
            $hiddenTileTitle = (bool) (IPS_GetObject($this->InstanceID)['ObjectIsHiddenTitle'] ?? false);
        }

        return $this->RenderVisualizationHTMLPage($ipsView, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Gauge Multi',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'ipsViewStyle'       => $ipsView ? $this->IPSViewThemeCSS() : '',
            'state'              => $this->BuildVisualizationState($ipsView),
            'translations'       => [
                'Configure 2 to 16 unique numeric sources.' => $this->Translate(
                    'Configure 2 to 16 unique numeric sources.'
                ),
                'Configure a valid Multi Gauge design.' => $this->Translate(
                    'Configure a valid Multi Gauge design.'
                ),
                'Connect an active EChartsGateway.' => $this->Translate(
                    'Connect an active EChartsGateway.'
                ),
                'The Multi Gauge values could not be loaded.' => $this->Translate(
                    'The Multi Gauge values could not be loaded.'
                )
            ],
            'options'            => [
                'echartsVersion'    => EChartsAsset::VERSION,
                'echartsThemes'     => EChartsAsset::ThemePalettes(),
                'tileHeaderVisible' => !$hiddenTileTitle
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}'       => EChartsAsset::JavaScript(),
                '{{ECHARTS_THEME_SCRIPT}}' => EChartsAsset::ThemeJavaScript()
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

        $sources = $this->GetValidatedSources();
        $this->WriteAttributeString('LastError', '');
        $this->SetSummary(count($sources) . ' sources');
        $this->SetStatus(IS_ACTIVE);
    }

    /**
     * Returns the first invalid configuration field and its module status.
     *
     * @return array{Status: int, Message: string}|null
     */
    private function GetConfigurationError(): ?array
    {
        try {
            $sources = $this->GetValidatedSources();
        } catch (UnexpectedValueException $exception) {
            $status = $exception->getCode();
            if (!in_array($status, [self::STATUS_SOURCE_INVALID, self::STATUS_RANGE_INVALID], true)) {
                $status = self::STATUS_SOURCE_INVALID;
            }

            return [
                'Status'  => $status,
                'Message' => $exception->getMessage()
            ];
        }

        if (count($sources) > 6 && ($this->ReadPropertyString('GaugePreset') === self::PRESET_CHRONOGRAPH
            || ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')
                && $this->ReadPropertyString('IPSViewGaugePreset') === self::PRESET_CHRONOGRAPH))) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The chronograph preset supports 2 to 6 Gauge sources.'
            ];
        }

        if (!in_array($this->ReadPropertyString('GaugePreset'), self::SUPPORTED_PRESETS, true)
            || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('EChartsTheme'))) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Multi Gauge design is not supported.'
            ];
        }
        foreach (['RingWidthPercent', 'ScaleFontSizePercent', 'ValueFontSizePercent', 'TitleFontSizePercent'] as $name) {
            $value = $this->ReadPropertyInteger($name);
            if ($value < 50 || $value > 150) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'Multi Gauge design scale values must be between 50 and 150 percent.'
                ];
            }
        }

        if ($this->IsIPSViewHTMLPageEnabled() && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
            if (!in_array($this->ReadPropertyString('IPSViewGaugePreset'), self::SUPPORTED_PRESETS, true)
                || !EChartsAsset::IsSupportedTheme($this->ReadPropertyString('IPSViewEChartsTheme'))) {
                return [
                    'Status'  => self::STATUS_DESIGN_INVALID,
                    'Message' => 'The selected IPSView Multi Gauge design is not supported.'
                ];
            }
            foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
                $value = $this->ReadPropertyInteger('IPSView' . $name);
                if ($value < 50 || $value > 150) {
                    return [
                        'Status'  => self::STATUS_DESIGN_INVALID,
                        'Message' => 'IPSView Multi Gauge design scale values must be between 50 and 150 percent.'
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{
     *     VariableID: int,
     *     Label: string,
     *     Minimum: float,
     *     Maximum: float,
     *     Unit: string,
     *     Decimals: int,
     *     UseVariablePresentation: bool
     * }>
     */
    private function GetValidatedSources(): array
    {
        try {
            $sources = json_decode(
                $this->ReadPropertyString('Sources'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new UnexpectedValueException(
                'Gauge sources must contain valid JSON.',
                self::STATUS_SOURCE_INVALID,
                $exception
            );
        }

        if (!is_array($sources) || !array_is_list($sources)) {
            throw new UnexpectedValueException(
                'Gauge sources must be a JSON list.',
                self::STATUS_SOURCE_INVALID
            );
        }

        $sourceCount = count($sources);
        if ($sourceCount < self::MINIMUM_SOURCE_COUNT || $sourceCount > self::MAXIMUM_SOURCE_COUNT) {
            throw new UnexpectedValueException(
                'Configure between 2 and 16 Gauge sources.',
                self::STATUS_SOURCE_INVALID
            );
        }

        $validatedSources = [];
        $variableIDs = [];
        foreach ($sources as $index => $source) {
            $sourceNumber = $index + 1;
            if (!is_array($source)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' is invalid.',
                    self::STATUS_SOURCE_INVALID
                );
            }

            $variableID = $source['VariableID'] ?? null;
            $label = $source['Label'] ?? '';
            $minimum = $source['Minimum'] ?? null;
            $maximum = $source['Maximum'] ?? null;
            $unit = $source['Unit'] ?? '';
            $decimals = $source['Decimals'] ?? null;
            $useVariablePresentation = $source['UseVariablePresentation'] ?? false;

            if (!is_int($variableID) || $variableID <= 0 || !IPS_VariableExists($variableID)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' must reference an existing numeric variable.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if (isset($variableIDs[$variableID])) {
                throw new UnexpectedValueException(
                    'Gauge source variables must be unique.',
                    self::STATUS_SOURCE_INVALID
                );
            }

            $variable = IPS_GetVariable($variableID);
            if (!in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' must reference a numeric variable.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if (!is_string($label) || !is_string($unit) || !is_bool($useVariablePresentation)) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' contains invalid text fields.',
                    self::STATUS_SOURCE_INVALID
                );
            }
            if ((!is_int($minimum) && !is_float($minimum))
                || (!is_int($maximum) && !is_float($maximum))
                || $minimum >= $maximum
            ) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' has an invalid range.',
                    self::STATUS_RANGE_INVALID
                );
            }
            if (!is_int($decimals) || $decimals < 0 || $decimals > 6) {
                throw new UnexpectedValueException(
                    'Gauge source ' . $sourceNumber . ' decimals must be between 0 and 6.',
                    self::STATUS_RANGE_INVALID
                );
            }

            $effectiveConfiguration = $this->ResolveSourceConfiguration(
                $variableID,
                (float) $minimum,
                (float) $maximum,
                $unit,
                $decimals,
                $useVariablePresentation
            );

            $variableIDs[$variableID] = true;
            $validatedSources[] = [
                'VariableID'               => $variableID,
                'Label'                    => trim($label),
                'Minimum'                  => $effectiveConfiguration['minimum'],
                'Maximum'                  => $effectiveConfiguration['maximum'],
                'Unit'                     => $effectiveConfiguration['unit'],
                'Decimals'                 => $effectiveConfiguration['decimals'],
                'UseVariablePresentation'  => $useVariablePresentation
            ];
        }

        return $validatedSources;
    }

    /** @return array{minimum: float, maximum: float, unit: string, decimals: int} */
    private function ResolveSourceConfiguration(
        int $variableID,
        float $minimum,
        float $maximum,
        string $unit,
        int $decimals,
        bool $useVariablePresentation
    ): array {
        $configuration = [
            'minimum'  => $minimum,
            'maximum'  => $maximum,
            'unit'     => trim($unit),
            'decimals' => $decimals
        ];
        if (!$useVariablePresentation) {
            return $configuration;
        }

        try {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (!is_array($presentation)) {
                return $configuration;
            }

            $profileName = $presentation['PROFILE'] ?? null;
            if (is_string($profileName) && $profileName !== '') {
                $profile = IPS_GetVariableProfile($profileName);
                if (is_array($profile)) {
                    $configuration = self::ApplyPresentationValues($configuration, [
                        'MIN'    => $profile['MinValue'] ?? null,
                        'MAX'    => $profile['MaxValue'] ?? null,
                        'SUFFIX' => $profile['Suffix'] ?? null,
                        'DIGITS' => $profile['Digits'] ?? null
                    ]);
                }
            }

            return self::ApplyPresentationValues($configuration, $presentation);
        } catch (Throwable $exception) {
            $this->SendDebug('ResolveSourceConfiguration', $exception::class, 0);

            return $configuration;
        }
    }

    /**
     * @param array{minimum: float, maximum: float, unit: string, decimals: int} $configuration
     * @param array<string, mixed> $presentation
     * @return array{minimum: float, maximum: float, unit: string, decimals: int}
     */
    private static function ApplyPresentationValues(array $configuration, array $presentation): array
    {
        $presentationMinimum = $presentation['MIN'] ?? null;
        $presentationMaximum = $presentation['MAX'] ?? null;
        if ((is_int($presentationMinimum) || is_float($presentationMinimum))
            && (is_int($presentationMaximum) || is_float($presentationMaximum))
            && is_finite((float) $presentationMinimum)
            && is_finite((float) $presentationMaximum)
            && (float) $presentationMinimum < (float) $presentationMaximum) {
            $configuration['minimum'] = (float) $presentationMinimum;
            $configuration['maximum'] = (float) $presentationMaximum;
        }
        if (array_key_exists('SUFFIX', $presentation) && is_string($presentation['SUFFIX'])) {
            $configuration['unit'] = trim($presentation['SUFFIX']);
        }
        $presentationDigits = $presentation['DIGITS'] ?? null;
        if (is_int($presentationDigits) && $presentationDigits >= 0 && $presentationDigits <= 6) {
            $configuration['decimals'] = $presentationDigits;
        }

        return $configuration;
    }

    /**
     * @return array{Value: float, Timestamp: int}
     */
    private function ReadCurrentSource(int $variableID): array
    {
        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, [
            'VariableID' => $variableID
        ]);

        try {
            $response = EChartsDataProtocol::DecodeResponse(
                $this->SendDataToParent($this->EncodeDataFlowMessage(self::DATA_ID_TO_PARENT, $request)),
                $operation
            );
        } catch (Throwable $exception) {
            $this->WriteAttributeString('LastError', 'INVALID_GATEWAY_RESPONSE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            $this->SendDebug('GetGaugeData', $exception::class, 0);
            throw new RuntimeException('The gateway returned an invalid response.');
        }

        if (!$response['Success']) {
            $errorCode = (string) ($response['Error']['Code'] ?? 'GATEWAY_REQUEST_FAILED');
            $this->WriteAttributeString('LastError', $errorCode);
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway request failed: ' . $errorCode);
        }

        $payload = $response['Payload'];
        $responseVariableID = $payload['VariableID'] ?? null;
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if ($responseVariableID !== $variableID
            || (!is_int($value) && !is_float($value))
            || !is_int($timestamp)
        ) {
            $this->WriteAttributeString('LastError', 'INVALID_CURRENT_VALUE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        return [
            'Value'     => (float) $value,
            'Timestamp' => $timestamp
        ];
    }

    private function SynchronizeSourceReferences(): void
    {
        $previousVariableIDs = $this->DecodeVariableIDList(
            $this->ReadAttributeString('RegisteredSourceVariableIDs')
        );
        $variableIDs = $this->ConfiguredVariableIDs();
        $registeredVariableIDs = array_values(array_filter(
            $variableIDs,
            static fn (int $variableID): bool => IPS_VariableExists($variableID)
        ));

        foreach (array_diff($previousVariableIDs, $registeredVariableIDs) as $variableID) {
            $this->UnregisterReference($variableID);
            $this->UnregisterMessage($variableID, VM_UPDATE);
        }
        foreach (array_diff($registeredVariableIDs, $previousVariableIDs) as $variableID) {
            $this->RegisterReference($variableID);
        }
        foreach ($registeredVariableIDs as $variableID) {
            // Register idempotently so existing instances gain live updates after a module update.
            $this->RegisterMessage($variableID, VM_UPDATE);
        }

        $this->WriteAttributeString(
            'RegisteredSourceVariableIDs',
            json_encode($registeredVariableIDs, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Returns the unique, positive variable IDs that can be recovered from the
     * current configuration, even if another source field is invalid.
     *
     * @return list<int>
     */
    private function ConfiguredVariableIDs(): array
    {
        $sources = json_decode($this->ReadPropertyString('Sources'), true);
        if (!is_array($sources) || !array_is_list($sources)) {
            return [];
        }

        $variableIDs = [];
        foreach ($sources as $source) {
            $variableID = is_array($source) ? ($source['VariableID'] ?? null) : null;
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $variableIDs, true)) {
                $variableIDs[] = $variableID;
            }
        }

        sort($variableIDs);

        return $variableIDs;
    }

    /**
     * @return list<int>
     */
    private function DecodeVariableIDList(string $json): array
    {
        $variableIDs = json_decode($json, true);
        if (!is_array($variableIDs) || !array_is_list($variableIDs)) {
            return [];
        }

        $result = [];
        foreach ($variableIDs as $variableID) {
            if (is_int($variableID) && $variableID > 0 && !in_array($variableID, $result, true)) {
                $result[] = $variableID;
            }
        }

        sort($result);

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function PreviewItems(): array
    {
        try {
            $sources = $this->GetValidatedSources();
        } catch (Throwable) {
            return [
                ['label' => 'Temperature', 'minimum' => 0.0, 'maximum' => 40.0, 'unit' => '°C', 'decimals' => 1, 'value' => 21.5],
                ['label' => 'Humidity', 'minimum' => 0.0, 'maximum' => 100.0, 'unit' => '%', 'decimals' => 0, 'value' => 54.0]
            ];
        }

        return array_map(static function (array $source): array
        {
            $value = GetValue($source['VariableID']);

            return [
                'label'    => $source['Label'] !== '' ? $source['Label'] : IPS_GetName($source['VariableID']),
                'minimum'  => $source['Minimum'],
                'maximum'  => $source['Maximum'],
                'unit'     => $source['Unit'],
                'decimals' => $source['Decimals'],
                'value'    => is_int($value) || is_float($value) ? (float) $value : $source['Minimum']
            ];
        }, array_slice($sources, 0, 6));
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(bool $ipsView = false): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'multi',
                'status'        => 'error',
                'chart'         => null,
                'error'         => $configurationError['Status'] === self::STATUS_DESIGN_INVALID
                    ? 'Configure a valid Multi Gauge design.'
                    : 'Configure 2 to 16 unique numeric sources.'
            ];
        }
        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'multi',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }

        try {
            $chart = json_decode($this->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
            if ($ipsView && !$this->ReadPropertyBoolean('IPSViewUseTileDesign')) {
                $chart['theme'] = $this->ReadPropertyString('IPSViewEChartsTheme');
                $chart['gauge']['preset'] = $this->ReadPropertyString('IPSViewGaugePreset');
                foreach (self::DESIGN_SCALE_PROPERTIES as $name) {
                    $key = lcfirst($name);
                    $chart['gauge']['style'][$key] = $this->ReadPropertyInteger('IPSView' . $name);
                }
            }
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'multi',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Multi Gauge values could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'multi',
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
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
            ));
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

    private function EffectiveIPSViewTheme(): string
    {
        return $this->ReadPropertyBoolean('IPSViewUseTileDesign')
            ? $this->ReadPropertyString('EChartsTheme')
            : $this->ReadPropertyString('IPSViewEChartsTheme');
    }

    private function IPSViewThemeCSS(): string
    {
        $palette = EChartsAsset::ThemePreviewPalette($this->EffectiveIPSViewTheme());

        return ':root {'
            . '--symc-background:' . $palette['background'] . ';'
            . '--symc-text:' . $palette['text'] . ';'
            . '--symc-text-muted:' . $palette['muted'] . ';'
            . '--symc-border:' . $palette['border'] . ';'
            . '--symc-accent:' . $palette['accent'] . ';'
            . '--symc-surface:' . $palette['surface'] . ';'
            . '} html, body { background:' . $palette['background'] . '; }';
    }

    /** @param list<array<string, mixed>> $elements @return array<string, mixed> */
    private function BuildIPSViewDesigner(array $elements): array
    {
        $tileDesigner = null;
        foreach ($elements as $element) {
            if (($element['type'] ?? null) === 'ExpansionPanel'
                && ($element['caption'] ?? null) === 'Tile designer') {
                $tileDesigner = $element;
                break;
            }
        }
        if (!is_array($tileDesigner)) {
            throw new RuntimeException('The Tile designer form section is missing.');
        }

        $designerItems = $this->PrefixIPSViewDesignerItems($tileDesigner['items'] ?? []);

        return [
            'type'     => 'ExpansionPanel',
            'caption'  => 'IPSView design',
            'expanded' => false,
            'width'    => '700px',
            'items'    => [
                ...$this->IPSViewHTMLPageFormItems(
                    'Creates a standalone WebContent variable for use as an IPSView HTML widget.'
                ),
                [
                    'type'    => 'CheckBox',
                    'name'    => 'IPSViewUseTileDesign',
                    'caption' => 'Use Tile design'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Inherited mode follows every Tile design change. Disable it for an independent IPSView appearance.'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Copy Tile design to IPSView and edit independently',
                    'onClick' => 'ECGM_CopyTileDesignToIPSView($id); return "MESSAGE:Tile design copied to IPSView.";'
                ],
                [
                    'type'     => 'ExpansionPanel',
                    'caption'  => 'Independent IPSView designer',
                    'expanded' => false,
                    'items'    => $designerItems
                ]
            ]
        ];
    }

    /** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
    private function PrefixIPSViewDesignerItems(array $items): array
    {
        $designNames = ['GaugePreset', 'EChartsTheme', ...self::DESIGN_SCALE_PROPERTIES];
        foreach ($items as &$item) {
            if (($item['name'] ?? null) === 'GaugePreview') {
                $item['name'] = 'IPSViewGaugePreview';
            } elseif (isset($item['name']) && in_array($item['name'], $designNames, true)) {
                $item['name'] = 'IPSView' . $item['name'];
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->PrefixIPSViewDesignerItems($item['items']);
            }
        }
        unset($item);

        return $items;
    }
}
