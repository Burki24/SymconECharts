<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\DataFlowHelper;
use SymconECharts\EChartsDataProtocol;

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';

class EChartsGaugeMulti extends IPSModuleStrict
{
    use DataFlowHelper;

    private const GATEWAY_MODULE_ID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const DATA_ID_TO_PARENT = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';
    private const DATA_ID_FROM_PARENT = '{E4749B72-912B-E3E3-1C57-D19019FFDD84}';

    private const MINIMUM_SOURCE_COUNT = 2;
    private const MAXIMUM_SOURCE_COUNT = 16;

    private const STATUS_SOURCE_INVALID = 201;
    private const STATUS_RANGE_INVALID = 202;
    private const STATUS_PARENT_MISSING = 203;
    private const STATUS_GATEWAY_FAILED = 204;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterPropertyString('Sources', '[]');
        $this->RegisterPropertyString('Title', '');
        $this->RegisterAttributeString('RegisteredSourceVariableIDs', '[]');
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
            'gauge'         => [
                'title' => $this->ReadPropertyString('Title')
            ],
            'items'         => $items
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
            $this->GetValidatedSources();
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

        return null;
    }

    /**
     * @return list<array{
     *     VariableID: int,
     *     Label: string,
     *     Minimum: float,
     *     Maximum: float,
     *     Unit: string,
     *     Decimals: int
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
            if (!is_string($label) || !is_string($unit)) {
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

            $variableIDs[$variableID] = true;
            $validatedSources[] = [
                'VariableID' => $variableID,
                'Label'      => trim($label),
                'Minimum'    => (float) $minimum,
                'Maximum'    => (float) $maximum,
                'Unit'       => trim($unit),
                'Decimals'   => $decimals
            ];
        }

        return $validatedSources;
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
        }
        foreach (array_diff($registeredVariableIDs, $previousVariableIDs) as $variableID) {
            $this->RegisterReference($variableID);
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
}
