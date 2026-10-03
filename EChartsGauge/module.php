<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\DataFlowHelper;
use SymconECharts\EChartsDataProtocol;

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';

class EChartsGauge extends IPSModuleStrict
{
    use DataFlowHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyInteger('SourceVariableID', 0);
        $this->RegisterPropertyFloat('Minimum', 0.0);
        $this->RegisterPropertyFloat('Maximum', 100.0);
        $this->RegisterPropertyString('Title', '');
        $this->RegisterPropertyString('Unit', '');
        $this->RegisterPropertyInteger('Decimals', 1);
        $this->RegisterAttributeInteger('RegisteredSourceVariableID', 0);
        $this->RegisterAttributeString('LastError', '');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->Initialize();
    }

    public function GetCompatibleParents(): string
    {
        return json_encode([
            'type'      => 'connect',
            'moduleIDs' => [self::GATEWAY_MODULE_ID]
        ], JSON_THROW_ON_ERROR);
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
                'decimals' => $this->ReadPropertyInteger('Decimals')
            ],
            'value'         => (float) $value
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
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

        return null;
    }

    private function SynchronizeSourceReference(): void
    {
        $previousVariableID = $this->ReadAttributeInteger('RegisteredSourceVariableID');
        $variableID = $this->ReadPropertyInteger('SourceVariableID');
        if ($previousVariableID === $variableID) {
            return;
        }

        if ($previousVariableID > 0) {
            $this->UnregisterReference($previousVariableID);
        }

        if ($variableID > 0 && IPS_VariableExists($variableID)) {
            $this->RegisterReference($variableID);
            $this->WriteAttributeInteger('RegisteredSourceVariableID', $variableID);
        } else {
            $this->WriteAttributeInteger('RegisteredSourceVariableID', 0);
        }
    }
}
