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
use SymconECharts\EChartsGaugeSinglePreview;

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

class EChartsGaugeSingle extends IPSModuleStrict
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

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;
    private const STATUS_DESIGN_INVALID = 205;

    private const PRESET_SIMPLE = 'simple';
    private const SUPPORTED_PRESETS = ['basic', self::PRESET_SIMPLE, 'progress', 'speed'];

    public function Create(): void
    {
        parent::Create();

        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyFloat('Minimum', 0.0);
        $this->RegisterPropertyFloat('Maximum', 100.0);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
        $this->RegisterPropertyString('GaugePreset', self::PRESET_SIMPLE);
        $this->RegisterAttributeInteger('RegisteredSourceVariableID', 0);
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->Initialize();
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->PublishVisualizationState();
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
        $form = SVGPreviewHelper::withImage(
            $form,
            'GaugePreview',
            $this->BuildGaugePreviewSvg(
                $this->ReadPropertyInteger('SourceVariableID'),
                $this->ReadPropertyFloat('Minimum'),
                $this->ReadPropertyFloat('Maximum'),
                $this->ReadPropertyString('Title'),
                $this->ReadPropertyString('Unit'),
                $this->ReadPropertyInteger('Decimals'),
                $this->ReadPropertyString('GaugePreset')
            )
        );

        return $this->EncodeConfigurationForm($form);
    }

    /**
     * Refreshes the configuration-form preview with values that are not persisted yet.
     */
    public function UpdateGaugePreview(
        int $VariableID,
        float $Minimum,
        float $Maximum,
        string $Title,
        string $Unit,
        int $Decimals,
        string $GaugePreset = self::PRESET_SIMPLE
    ): void {
        $this->UpdateFormField(
            'GaugePreview',
            'image',
            SVGPreviewHelper::dataUri($this->BuildGaugePreviewSvg(
                $VariableID,
                $Minimum,
                $Maximum,
                $Title,
                $Unit,
                $Decimals,
                $GaugePreset
            ))
        );
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
            $this->WriteAttributeString('LastError', 'No active EChartsGateway is connected.');
            $this->SetStatus(self::STATUS_PARENT_MISSING);
            throw new RuntimeException('No active EChartsGateway is connected.');
        }

        $operation = EChartsDataProtocol::OPERATION_CURRENT_READ;
        $request = EChartsDataProtocol::CreateRequest($operation, [
            'VariableID' => $this->ReadPropertyInteger('SourceVariableID')
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
        $value = $payload['Value'] ?? null;
        $timestamp = $payload['Timestamp'] ?? null;
        if ((!is_int($value) && !is_float($value)) || !is_int($timestamp)) {
            $this->WriteAttributeString('LastError', 'INVALID_CURRENT_VALUE');
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
            throw new RuntimeException('The gateway returned invalid current-value data.');
        }

        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(IS_ACTIVE);

        return json_encode([
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'source'        => [
                'variableID' => $this->ReadPropertyInteger('SourceVariableID'),
                'timestamp'  => $timestamp
            ],
            'gauge'         => [
                'title'    => $this->ReadPropertyString('Title'),
                'minimum'  => $this->ReadPropertyFloat('Minimum'),
                'maximum'  => $this->ReadPropertyFloat('Maximum'),
                'unit'     => $this->ReadPropertyString('Unit'),
                'decimals' => $this->ReadPropertyInteger('Decimals'),
                'preset'   => $this->ReadPropertyString('GaugePreset')
            ],
            'value'         => (float) $value
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function GetVisualizationTile(): string
    {
        return $this->RenderVisualizationHTMLPage(false, [
            'language'           => $this->NormalizeHelperTranslationLanguage(
                $this->ResolveHelperTranslationLanguage()
            ),
            'title'              => 'ECharts Gauge Single',
            'visualizationTheme' => $this->VisualizationThemeCSS()
                . "\n\n"
                . $this->ResponsiveVisualizationCSS('#echarts-gauge-root', 'echarts-gauge'),
            'state'              => $this->BuildVisualizationState(),
            'translations'       => $this->IPSViewTranslationsFor([
                'Configure a numeric source variable.',
                'Configure a valid Gauge range.',
                'Configure a valid Gauge design.',
                'Connect an active EChartsGateway.',
                'The Gauge value could not be loaded.',
                'Apache ECharts could not be initialized.'
            ]),
            'options'            => [
                'echartsVersion' => EChartsAsset::VERSION
            ],
            'replacements'       => [
                '{{ECHARTS_SCRIPT}}' => EChartsAsset::JavaScript()
            ]
        ]);
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

            return;
        }

        if ($Message === VM_UPDATE && $SenderID === $this->ReadPropertyInteger('SourceVariableID')) {
            $this->PublishVisualizationState();
        }
    }

    private function Initialize(): void
    {
        $this->SetStatus(IS_INACTIVE);
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->SetSummary('');
        $this->SynchronizeSourceReference();

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
        $this->SetSummary(IPS_GetName($this->ReadPropertyInteger('SourceVariableID')));
        $this->SetStatus(IS_ACTIVE);
    }

    private function BuildGaugePreviewSvg(
        int $variableID,
        float $minimum,
        float $maximum,
        string $title,
        string $unit,
        int $decimals,
        string $preset
    ): string {
        if (!is_finite($minimum) || !is_finite($maximum) || $minimum >= $maximum) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Minimum must be lower than maximum.')
            );
        }
        if (!in_array($preset, self::SUPPORTED_PRESETS, true)) {
            return EChartsGaugeSinglePreview::CreateErrorSvg(
                $this->Translate('Select a supported Gauge preset.')
            );
        }

        $value = $this->ResolveGaugePreviewValue($variableID, $minimum, $maximum);
        $language = $this->NormalizeHelperTranslationLanguage(
            $this->ResolveHelperTranslationLanguage()
        );

        return EChartsGaugeSinglePreview::CreateSvg(
            $value,
            $minimum,
            $maximum,
            $title,
            $unit,
            $decimals,
            $language,
            $preset
        );
    }

    private function ResolveGaugePreviewValue(int $variableID, float $minimum, float $maximum): float
    {
        if ($variableID > 0 && IPS_VariableExists($variableID)) {
            try {
                $variable = IPS_GetVariable($variableID);
                if (in_array($variable['VariableType'] ?? null, [1, 2], true)) {
                    $value = GetValue($variableID);
                    if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
                        return (float) $value;
                    }
                }
            } catch (Throwable $exception) {
                $this->SendDebug('ResolveGaugePreviewValue', $exception::class, 0);
            }
        }

        return $minimum + (($maximum - $minimum) / 2);
    }

    /**
     * Returns the first invalid configuration field and its module status.
     *
     * @return array{Status: int, Message: string}|null
     */
    private function GetConfigurationError(): ?array
    {
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($variableID <= 0 || !IPS_VariableExists($variableID)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'Select an existing numeric source variable.'
            ];
        }

        $variable = IPS_GetVariable($variableID);
        if (!in_array($variable['VariableType'] ?? null, [1, 2], true)) {
            return [
                'Status'  => self::STATUS_SOURCE_INVALID,
                'Message' => 'The selected source variable must be numeric.'
            ];
        }

        if ($this->ReadPropertyFloat('Minimum') >= $this->ReadPropertyFloat('Maximum')) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge range minimum must be lower than maximum.'
            ];
        }

        $decimals = $this->ReadPropertyInteger('Decimals');
        if ($decimals < 0 || $decimals > 6) {
            return [
                'Status'  => self::STATUS_RANGE_INVALID,
                'Message' => 'Gauge decimals must be between 0 and 6.'
            ];
        }

        if (!in_array($this->ReadPropertyString('GaugePreset'), self::SUPPORTED_PRESETS, true)) {
            return [
                'Status'  => self::STATUS_DESIGN_INVALID,
                'Message' => 'The selected Gauge preset is not supported.'
            ];
        }

        return null;
    }

    private function SynchronizeSourceReference(): void
    {
        $previousVariableID = $this->ReadAttributeInteger('RegisteredSourceVariableID');
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        $variableExists = $variableID > 0 && IPS_VariableExists($variableID);

        if ($previousVariableID > 0 && ($previousVariableID !== $variableID || !$variableExists)) {
            $this->UnregisterReference($previousVariableID);
            $this->UnregisterMessage($previousVariableID, VM_UPDATE);
        }

        if ($variableExists) {
            if ($previousVariableID !== $variableID) {
                $this->RegisterReference($variableID);
            }
            $this->RegisterMessage($variableID, VM_UPDATE);
            $this->WriteAttributeInteger('RegisteredSourceVariableID', $variableID);
        } else {
            $this->WriteAttributeInteger('RegisteredSourceVariableID', 0);
        }
    }

    /** @return array<string, mixed> */
    private function BuildVisualizationState(): array
    {
        $configurationError = $this->GetConfigurationError();
        if ($configurationError !== null) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => match ($configurationError['Status']) {
                    self::STATUS_SOURCE_INVALID => 'Configure a numeric source variable.',
                    self::STATUS_DESIGN_INVALID => 'Configure a valid Gauge design.',
                    default                     => 'Configure a valid Gauge range.'
                }
            ];
        }

        if (!$this->HasActiveParent()) {
            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'Connect an active EChartsGateway.'
            ];
        }

        try {
            $chart = json_decode($this->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->SendDebug('BuildVisualizationState', $exception::class, 0);

            return [
                'schemaVersion' => 1,
                'family'        => 'gauge',
                'variant'       => 'single',
                'status'        => 'error',
                'chart'         => null,
                'error'         => 'The Gauge value could not be loaded.'
            ];
        }

        return [
            'schemaVersion' => 1,
            'family'        => 'gauge',
            'variant'       => 'single',
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
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION
            ));
        } catch (Throwable $exception) {
            $this->SendDebug('PublishVisualizationState', $exception::class, 0);
        }
    }
}
