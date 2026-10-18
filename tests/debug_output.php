<?php

declare(strict_types=1);

// This integration check runs inside gateway_gauges.php with its existing offline kernel.
foreach (glob(dirname(__DIR__) . '/*/module.php') as $moduleFile) {
    $moduleClass = basename(dirname($moduleFile));
    require_once $moduleFile;
    $instance = new $moduleClass();
    $instance->CaptureDebug = true;
    $instance->Create();
    $starts = [];
    foreach ($instance->DebugMessages as [$category, $entry]) {
        assertGatewayGauge(preg_match('/:[1-9][0-9]*$/', $category) === 1, 'Debug source location must be automatic.');
        $key = $entry['Vorgang'] . ':' . $entry['Aufruf'];
        if (str_contains($category, ' | START | ')) {
            $starts[$key] = true;
        } elseif (str_contains($category, ' | ENDE | ')) {
            assertGatewayGauge(isset($starts[$key]), 'Each end must identify an existing start.');
            unset($starts[$key]);
        }
    }
    assertGatewayGauge($instance->DebugMessages !== [] && $starts === [], 'All 30 modules must balance public lifecycle debug calls.');
}
$debugGateway = new EChartsGateway();
$debugGateway->CaptureDebug = true;
$debugGateway->Create();
$firstOperation = $debugGateway->DebugMessages[0][1]['Vorgang'];
$debugGateway->DebugMessages = [];
$debugResult = json_decode($debugGateway->ForwardData('private-document-sentinel'), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge($debugResult['Success'] === false, 'Invalid requests must retain the rejection contract.');
assertGatewayGauge(!str_contains(json_encode($debugGateway->DebugMessages), 'private-document-sentinel'), 'Request contents must remain private.');
assertGatewayGauge($debugGateway->DebugMessages[0][1]['Vorgang'] !== $firstOperation, 'Separate outer calls need separate operation identities.');
assertGatewayGauge(count(array_filter($debugGateway->DebugMessages, static fn (array $entry): bool => str_contains($entry[0], ' | FEHLER | '))) > 0, 'Rejected requests must explain failure.');
$debugGateway->DebugMessages = [];
$debugCurrentRequest = json_encode([
    'DataID'          => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}',
    'ProtocolVersion' => 1,
    'Operation'       => 'current.read',
    'Payload'         => ['VariableID' => 4711]
], JSON_THROW_ON_ERROR);
$debugCurrentResult = json_decode($debugGateway->ForwardData($debugCurrentRequest), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge($debugCurrentResult['Success'] === true, 'Valid numeric reads must retain the success contract.');
$debugOperations = array_unique(array_column(array_column($debugGateway->DebugMessages, 1), 'Vorgang'));
assertGatewayGauge(count($debugOperations) === 1, 'Nested source reads must share the gateway operation.');
$debugValues = array_values(array_filter($debugGateway->DebugMessages, static fn (array $entry): bool => ($entry[1]['Meldung'] ?? '') === 'Quellwert aus Symcon gelesen.'));
assertGatewayGauge(count($debugValues) === 1 && $debugValues[0][1]['Bezug']['Wert'] === $debugCurrentResult['Payload']['Value'], 'One actual numeric source read must report the actual returned value.');
$debugGateway->DebugMessages = [];
$debugAdapter = new ReflectionMethod($debugGateway, 'SendDebug');
$debugAdapter->invoke($debugGateway, 'LegacyHelper', 'private-document-sentinel', 0);
assertGatewayGauge(!str_contains(json_encode($debugGateway->DebugMessages), 'private-document-sentinel'), 'Legacy helper diagnostics must remain private.');
assertGatewayGauge(count($debugGateway->DebugMessages) === 3, 'Standalone legacy diagnostics need a balanced safe envelope.');
$debugGateway->FailDebug = true;
assertGatewayGauge(json_decode($debugGateway->ForwardData('invalid'), true)['Success'] === false, 'Debug-channel failures must preserve gateway behavior.');
echo "Debug lifecycle, rejection, privacy and logging-failure integration verified for all 30 modules.\n";
