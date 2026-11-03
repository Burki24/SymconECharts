<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

// Reuse the established Kernel doubles and real Gateway through its narrow hook.
if (!isset($gateway)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/gateway_gauges.php') . ' --archive-states', $exitCode);
    exit($exitCode);
}

/** @param array<string, mixed> $overrides @return array<string, mixed> */
function stateArchiveRequest(EChartsGateway $gateway, array $overrides = []): array
{
    $payload = array_replace([
        'VariableID'     => 4800,
        'StartTimestamp' => 1780000100,
        'EndTimestamp'   => 1780000400,
        'Limit'          => 100
    ], $overrides);

    return EChartsDataProtocol::DecodeResponse($gateway->ForwardData(json_encode([
        'DataID' => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'
    ] + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_ARCHIVE_STATES_READ, $payload), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)), EChartsDataProtocol::OPERATION_ARCHIVE_STATES_READ);
}

$GLOBALS['symconTestVariables'][4800] = [
    'VariableType' => 0, 'VariableUpdated' => 1780000500, 'Value' => false, 'Name' => 'Synthetic contact'
];
$GLOBALS['symconTestVariables'][4801] = [
    'VariableType' => 1, 'VariableUpdated' => 1780000500, 'Value' => 99, 'Name' => 'Synthetic state'
];
$GLOBALS['symconTestArchiveVariables'][4800] = [
    'AggregationType' => 0,
    'LoggedValues'    => [
        ['TimeStamp' => 1780000400, 'Value' => false],
        ['TimeStamp' => 1780000300, 'Value' => 1.0],
        ['TimeStamp' => 1780000100, 'Value' => true],
        ['TimeStamp' => 1780000099, 'Value' => 0]
    ]
];
$GLOBALS['symconTestArchiveVariables'][4801] = [
    'AggregationType' => 0,
    'LoggedValues'    => [
        ['TimeStamp' => 1780000300, 'Value' => -4.0],
        ['TimeStamp' => 1780000100, 'Value' => 2],
        ['TimeStamp' => 1780000099, 'Value' => 3]
    ]
];
$fixture = $GLOBALS['symconTestArchiveVariables'];
$queriesBefore = $GLOBALS['symconTestArchiveQueryCount'];
$result = stateArchiveRequest($gateway);
assertGatewayGauge($result['Success'] === true, 'Boolean state history must be available as a separate operation.');
assertGatewayGauge($result['Payload'] === [
    'VariableID'             => 4800,
    'VariableType'           => 0,
    'ArchiveAggregationType' => 'standard',
    'StartTimestamp'         => 1780000100,
    'EndTimestamp'           => 1780000400,
    'Points'                 => [[1780000100, 1], [1780000300, 1], [1780000400, 0]],
    'PreviousPoint'          => [1780000099, 0],
    'Truncated'              => false
], 'State history must preserve raw boundary changes and a strictly earlier predecessor, normalized to integers.');
assertGatewayGauge($GLOBALS['symconTestArchiveQueryCount'] - $queriesBefore === 2, 'States use exactly one bounded window query and one predecessor query.');
stateArchiveRequest($gateway);
assertGatewayGauge($GLOBALS['symconTestArchiveQueryCount'] - $queriesBefore === 2, 'Identical successful state requests must use the bounded cache.');
$integerResult = stateArchiveRequest($gateway, ['VariableID' => 4801]);
assertGatewayGauge($integerResult['Payload']['Points'] === [[1780000100, 2], [1780000300, -4]], 'Integer states must accept signed values and integral archive floats.');
$limited = stateArchiveRequest($gateway, ['Limit' => 2]);
assertGatewayGauge($limited['Payload']['Truncated'] === true && $limited['Payload']['Points'] === [[1780000300, 1], [1780000400, 0]], 'Truncation must retain the latest bounded records and be explicit.');
$unknown = stateArchiveRequest($gateway, ['StartTimestamp' => 1780000000]);
assertGatewayGauge($unknown['Payload']['PreviousPoint'] === null, 'An unknown beginning must remain null, never use the current variable value.');
$constant = stateArchiveRequest($gateway, ['StartTimestamp' => 1780000401, 'EndTimestamp' => 1780000500]);
assertGatewayGauge($constant['Payload']['Points'] === [] && $constant['Payload']['PreviousPoint'] === [1780000400, 0], 'A constant state window must retain its predecessor without inventing changes.');
$maximumBudget = stateArchiveRequest($gateway, ['Limit' => 2000]);
assertGatewayGauge($maximumBudget['Success'] === true && $maximumBudget['Payload']['Truncated'] === false, 'The documented maximum state point budget must be accepted.');
$gateway->ApplyChanges();
$queriesBefore = $GLOBALS['symconTestArchiveQueryCount'];
$epoch = stateArchiveRequest($gateway, ['StartTimestamp' => 1, 'EndTimestamp' => 2]);
assertGatewayGauge($epoch['Payload']['PreviousPoint'] === null && $GLOBALS['symconTestArchiveQueryCount'] - $queriesBefore === 1, 'Start timestamp one must not query end zero, which means now in Symcon.');

$invalidRequests = [
    [['VariableID' => 0], 'INVALID_VARIABLE_ID'],
    [['VariableID' => '4800'], 'INVALID_VARIABLE_ID'],
    [['VariableID' => 99999], 'VARIABLE_NOT_FOUND'],
    [['VariableID' => 4711], 'VARIABLE_NOT_DISCRETE'],
    [['VariableID' => 4712], 'VARIABLE_NOT_DISCRETE'],
    [['StartTimestamp' => 0], 'INVALID_TIME_RANGE'],
    [['EndTimestamp' => 1780000100], 'INVALID_TIME_RANGE'],
    [['Limit' => 0], 'INVALID_ARCHIVE_LIMIT'],
    [['Limit' => 2001], 'INVALID_ARCHIVE_LIMIT'],
    [['Limit' => 1.0], 'INVALID_ARCHIVE_LIMIT']
];
foreach ($invalidRequests as [$overrides, $expectedCode]) {
    $result = stateArchiveRequest($gateway, $overrides);
    assertGatewayGauge($result['Success'] === false && $result['Error']['Code'] === $expectedCode, 'Invalid state archive requests must fail with ' . $expectedCode . '.');
}
$GLOBALS['symconTestArchiveVariables'][4800]['AggregationType'] = 1;
$gateway->ApplyChanges();
$counter = stateArchiveRequest($gateway);
assertGatewayGauge($counter['Success'] === false && $counter['Error']['Code'] === 'UNSUPPORTED_ARCHIVE_TYPE', 'Counter archives must never be interpreted as discrete states.');
unset($GLOBALS['symconTestArchiveVariables'][4800]);
$gateway->ApplyChanges();
$unarchived = stateArchiveRequest($gateway);
assertGatewayGauge($unarchived['Success'] === false && $unarchived['Error']['Code'] === 'SOURCE_NOT_ARCHIVED', 'Unarchived discrete variables must fail without using current values.');
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$savedArchiveIDs = $GLOBALS['symconTestArchiveInstances'];
$GLOBALS['symconTestArchiveInstances'] = [];
$gateway->ApplyChanges();
$missing = stateArchiveRequest($gateway);
assertGatewayGauge($missing['Success'] === false && $missing['Error']['Code'] === 'ARCHIVE_NOT_FOUND', 'A missing archive must fail safely.');
$GLOBALS['symconTestArchiveInstances'] = $savedArchiveIDs;

foreach ([0.5, 2, '1', null, INF, NAN] as $invalidValue) {
    $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'][0]['Value'] = $invalidValue;
    $gateway->ApplyChanges();
    $invalid = stateArchiveRequest($gateway);
    assertGatewayGauge($invalid['Success'] === false && $invalid['Error']['Code'] === 'INVALID_ARCHIVE_DATA', 'Invalid Boolean archive values must not silently become states.');
}
$GLOBALS['symconTestArchiveVariables'] = $fixture;
foreach ([1780000400.0, '1780000400'] as $invalidTimestamp) {
    $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'][0]['TimeStamp'] = $invalidTimestamp;
    $gateway->ApplyChanges();
    $invalid = stateArchiveRequest($gateway);
    assertGatewayGauge($invalid['Success'] === false && $invalid['Error']['Code'] === 'INVALID_ARCHIVE_DATA', 'Archive timestamps must be actual integers, not coerced values.');
}
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'][3]['Value'] = 2;
$gateway->ApplyChanges();
$invalidPredecessor = stateArchiveRequest($gateway);
assertGatewayGauge($invalidPredecessor['Success'] === false && $invalidPredecessor['Error']['Code'] === 'INVALID_ARCHIVE_DATA', 'The predecessor must satisfy the same discrete-state contract as window rows.');
$GLOBALS['symconTestArchiveVariables'] = $fixture;
foreach ([0.5, '1', true, INF, NAN, (float) PHP_INT_MAX] as $invalidValue) {
    $GLOBALS['symconTestArchiveVariables'][4801]['LoggedValues'][0]['Value'] = $invalidValue;
    $gateway->ApplyChanges();
    $invalid = stateArchiveRequest($gateway, ['VariableID' => 4801]);
    assertGatewayGauge($invalid['Success'] === false && $invalid['Error']['Code'] === 'INVALID_ARCHIVE_DATA', 'Invalid Integer archive values must not silently truncate or overflow.');
}
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'] = [
    ['TimeStamp' => 1780000300, 'Value' => true],
    ['TimeStamp' => 1780000300, 'Value' => false]
];
$gateway->ApplyChanges();
$conflict = stateArchiveRequest($gateway);
assertGatewayGauge($conflict['Success'] === false && $conflict['Error']['Code'] === 'INVALID_ARCHIVE_DATA', 'Conflicting states at one timestamp must fail, not invent ordering.');
$GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'][1]['Value'] = true;
$gateway->ApplyChanges();
$duplicate = stateArchiveRequest($gateway);
assertGatewayGauge($duplicate['Payload']['Points'] === [[1780000300, 1]], 'Identical duplicate rows may be safely collapsed.');

// Native Archive Control can report sub-second changes as zero-duration rows.
// Only one positive duration can identify the state that actually follows that second.
$durationRows = [
    ['TimeStamp' => 1780000400, 'Value' => true, 'Duration' => 100],
    ['TimeStamp' => 1780000300, 'Value' => false, 'Duration' => 100],
    ['TimeStamp' => 1780000300, 'Value' => true, 'Duration' => 0],
    ['TimeStamp' => 1780000300, 'Value' => false, 'Duration' => 0],
    ['TimeStamp' => 1780000300, 'Value' => true, 'Duration' => 0],
    ['TimeStamp' => 1780000100, 'Value' => true, 'Duration' => 200]
];
foreach ([$durationRows, array_reverse($durationRows), [$durationRows[2], $durationRows[5], $durationRows[1], $durationRows[0], $durationRows[3], $durationRows[4]]] as $rows) {
    $GLOBALS['symconTestArchiveVariables'] = $fixture;
    $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'] = $rows;
    $beforeRows = $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'];
    $gateway->ApplyChanges();
    $durationResult = stateArchiveRequest($gateway);
    assertGatewayGauge(
        $durationResult['Success'] === true
        && $durationResult['Payload']['Points'] === [[1780000100, 1], [1780000300, 0], [1780000400, 1]]
        && ($durationResult['Payload']['RawPointCount'] ?? null) === count($rows)
        && $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'] === $beforeRows,
        'A unique positive archive duration must resolve zero-duration changes independently of row order without modifying archive rows.'
    );
}
foreach ([[0, 0], [1, 1], [null, 1], [1, null], [-1, 1], [1, -1], ['0', 1], [0, '1'], [0, 1.0], [true, 0]] as [$firstDuration, $secondDuration]) {
    $GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'] = [
        ['TimeStamp' => 1780000300, 'Value' => true, 'Duration' => $firstDuration],
        ['TimeStamp' => 1780000300, 'Value' => false, 'Duration' => $secondDuration]
    ];
    $gateway->ApplyChanges();
    $ambiguous = stateArchiveRequest($gateway);
    assertGatewayGauge(
        $ambiguous['Success'] === false && $ambiguous['Error']['Code'] === 'INVALID_ARCHIVE_DATA',
        'Missing, invalid, zero-only or competing positive duration metadata must not invent a state ordering.'
    );
}
$GLOBALS['symconTestArchiveVariables'][4800]['LoggedValues'] = $durationRows;
$gateway->ApplyChanges();
$durationLimited = stateArchiveRequest($gateway, ['Limit' => 2]);
assertGatewayGauge(
    $durationLimited['Success'] === true && $durationLimited['Payload']['Truncated'] === true,
    'Collapsing same-second rows must never conceal an exceeded raw-row budget.'
);
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$GLOBALS['symconTestArchiveVariables'][4801]['LoggedValues'] = [
    ['TimeStamp' => 1780000300, 'Value' => 2, 'Duration' => 0],
    ['TimeStamp' => 1780000300, 'Value' => -4.0, 'Duration' => 1]
];
$gateway->ApplyChanges();
$integerDuration = stateArchiveRequest($gateway, ['VariableID' => 4801]);
assertGatewayGauge(
    $integerDuration['Success'] === true && $integerDuration['Payload']['Points'] === [[1780000300, -4]],
    'The same duration policy must preserve original signed Integer state values.'
);
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$GLOBALS['symconTestArchiveVariables'][4801]['LoggedValues'] = [
    ['TimeStamp' => 1780000100, 'Value' => 0],
    ['TimeStamp' => 1780000400, 'Value' => 2],
    ['TimeStamp' => 1780000300, 'Value' => 1]
];
$gateway->ApplyChanges();
$sorted = stateArchiveRequest($gateway, ['VariableID' => 4801, 'Limit' => 2]);
assertGatewayGauge($sorted['Payload']['Truncated'] === true && $sorted['Payload']['Points'] === [[1780000300, 1], [1780000400, 2]], 'Gateway must sort deterministically before retaining the newest bounded points.');
$GLOBALS['symconTestArchiveVariables'] = $fixture;
$gateway->ApplyChanges();
assertGatewayGauge($GLOBALS['symconTestVariables'][4800]['Value'] === false && $GLOBALS['symconTestVariables'][4801]['Value'] === 99 && $GLOBALS['symconTestArchiveVariables'] === $fixture, 'State history must not write current values or archived rows.');

echo "Gateway discrete state archives, predecessor boundaries and truncation verified.\n";
