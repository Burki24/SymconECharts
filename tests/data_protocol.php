<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

require_once dirname(__DIR__) . '/libs/EChartsDataProtocol.php';

function assertProtocol(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$request = EChartsDataProtocol::CreateRequest(
    EChartsDataProtocol::OPERATION_CURRENT_READ,
    ['VariableID' => 4711]
);
assertProtocol(
    $request === [
        'ProtocolVersion' => 1,
        'Operation'       => 'current.read',
        'Payload'         => ['VariableID' => 4711]
    ],
    'Current-value request contract changed.'
);

$decodedRequest = EChartsDataProtocol::DecodeRequest(['DataID' => '{TEST}'] + $request);
assertProtocol($decodedRequest === $request, 'Request decoding must discard only the transport DataID.');

$archiveRequest = EChartsDataProtocol::CreateRequest(
    EChartsDataProtocol::OPERATION_ARCHIVE_READ,
    [
        'VariableID'     => 4711,
        'StartTimestamp' => 1780000000,
        'EndTimestamp'   => 1780000400,
        'Mode'           => 'raw',
        'Reducer'        => 'auto',
        'Limit'          => 1000
    ]
);
assertProtocol(
    $archiveRequest['Operation'] === 'archive.read',
    'Archive request operation changed.'
);

$success = EChartsDataProtocol::EncodeSuccessResponse(
    EChartsDataProtocol::OPERATION_CURRENT_READ,
    ['VariableID' => 4711, 'Value' => 0.0, 'Timestamp' => 1780000000]
);
$decodedSuccess = EChartsDataProtocol::DecodeResponse(
    $success,
    EChartsDataProtocol::OPERATION_CURRENT_READ
);
assertProtocol($decodedSuccess['Success'] === true, 'Success response was not recognized.');
assertProtocol($decodedSuccess['Payload']['Value'] === 0.0, 'Float zero must be preserved by the protocol.');

$failure = EChartsDataProtocol::EncodeErrorResponse(
    EChartsDataProtocol::OPERATION_CURRENT_READ,
    'VARIABLE_NOT_FOUND',
    'The selected variable does not exist.'
);
$decodedFailure = EChartsDataProtocol::DecodeResponse(
    $failure,
    EChartsDataProtocol::OPERATION_CURRENT_READ
);
assertProtocol($decodedFailure['Success'] === false, 'Error response was not recognized.');
assertProtocol(
    ($decodedFailure['Error']['Code'] ?? null) === 'VARIABLE_NOT_FOUND',
    'Error response code changed.'
);

try {
    EChartsDataProtocol::DecodeRequest([
        'ProtocolVersion' => 2,
        'Operation'       => EChartsDataProtocol::OPERATION_CURRENT_READ,
        'Payload'         => []
    ]);
    throw new RuntimeException('Unsupported protocol versions must be rejected.');
} catch (UnexpectedValueException) {
}

echo "Versioned ECharts data protocol verified.\n";
