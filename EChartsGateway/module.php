<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\DataFlowHelper;
use SymconECharts\EChartsDataProtocol;

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';

class EChartsGateway extends IPSModuleStrict
{
    use DataFlowHelper;

    private const DATA_ID_FROM_CHILD = '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}';

    public function Create(): void
    {
        parent::Create();
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->SetStatus(IS_ACTIVE);
    }

    public function ForwardData(string $JSONString): string
    {
        $operation = 'invalid';

        try {
            $message = $this->DecodeDataFlowMessage($JSONString, self::DATA_ID_FROM_CHILD);
            $request = EChartsDataProtocol::DecodeRequest($message);
            $operation = $request['Operation'];
        } catch (Throwable) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_REQUEST',
                'The gateway request is invalid.'
            );
        }

        if ($operation !== EChartsDataProtocol::OPERATION_CURRENT_READ) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'UNSUPPORTED_OPERATION',
                'The gateway operation is not supported.'
            );
        }

        $variableID = $request['Payload']['VariableID'] ?? null;
        if (!is_int($variableID) || $variableID <= 0) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_VARIABLE_ID',
                'A positive integer VariableID is required.'
            );
        }

        if (!IPS_VariableExists($variableID)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'VARIABLE_NOT_FOUND',
                'The selected variable does not exist.'
            );
        }

        $variable = IPS_GetVariable($variableID);
        $variableType = $variable['VariableType'] ?? null;
        if (!in_array($variableType, [1, 2], true)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'VARIABLE_NOT_NUMERIC',
                'The selected variable is not numeric.'
            );
        }

        $value = GetValue($variableID);
        if (!is_int($value) && !is_float($value)) {
            return EChartsDataProtocol::EncodeErrorResponse(
                $operation,
                'INVALID_VARIABLE_VALUE',
                'The selected variable does not contain a numeric value.'
            );
        }

        return EChartsDataProtocol::EncodeSuccessResponse($operation, [
            'VariableID'   => $variableID,
            'VariableType' => $variableType,
            'Value'        => $value,
            'Timestamp'    => (int) ($variable['VariableUpdated'] ?? 0)
        ]);
    }
}
