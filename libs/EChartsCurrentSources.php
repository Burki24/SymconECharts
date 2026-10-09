<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;
use Throwable;

/** Shared current-value Gateway access and ID-based source subscriptions. */
trait EChartsCurrentSources
{
    /** @return array{Value:float, Timestamp:int} */
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
            || !is_finite((float) $value)
            || !is_int($timestamp)
        ) {
            $this->SetStatus(self::STATUS_GATEWAY_FAILED);
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
            // Re-register idempotently when an existing instance is updated.
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
}
