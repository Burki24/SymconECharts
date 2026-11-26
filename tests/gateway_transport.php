<?php

declare(strict_types=1);

use eCharts\EChartsAsset;
use eCharts\EChartsDataProtocol;
use eCharts\EChartsIdentity;

// Exercise the actual gateway with the existing kernel, including exclusive native hook ownership.
if (!isset($gateway)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/gateway_gauges.php') . ' --gateway-transport', $exitCode);
    exit($exitCode);
}

$transportGlobals = [];
foreach (['symconTestInstances', 'symconTestModuleInstances', 'symconTestGatewayObjects', 'symconTestGatewayResponders', 'symconTestHookOwners', 'symconTestTransportRefreshCalls'] as $key) {
    $transportGlobals[$key] = $GLOBALS[$key] ?? null;
}
$transportServer = $_SERVER;
$transportGet = $_GET;
$gatewayModuleID = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
$channel = str_repeat('a', 32);

try {
    $GLOBALS['symconTestInstances'] = [];
    $GLOBALS['symconTestModuleInstances'][$gatewayModuleID] = [];
    $GLOBALS['symconTestGatewayObjects'] = [];
    $GLOBALS['symconTestGatewayResponders'] = [];
    // An unrelated integration keeps its own registration throughout the migration.
    $GLOBALS['symconTestHookOwners'] = ['third-party' => 4321];
    $GLOBALS['symconTestTransportRefreshCalls'] = [];
    $createGateway = static function (int $id, bool $upgrade = false) use ($gatewayModuleID): TestableEChartsGateway
    {
        $instance = new TestableEChartsGateway();
        $instance->InstanceID = $id;
        $GLOBALS['symconTestInstances'][$id] = [
            'ConnectionID' => 0, 'InstanceStatus' => IS_ACTIVE,
            'ModuleInfo'   => ['ModuleID' => $gatewayModuleID]
        ];
        $GLOBALS['symconTestModuleInstances'][$gatewayModuleID][] = $id;
        $GLOBALS['symconTestGatewayObjects'][$id] = $instance;
        if ($upgrade) {
            $instance->RestoreTestHook(EChartsIdentity::LegacyProductName());
        }
        $instance->Create();
        $instance->ApplyChanges();
        return $instance;
    };
    $first = $createGateway(6001, true);
    assertGatewayGauge($first->GetTestHooks() === ['eCharts'] && !isset($GLOBALS['symconTestHookOwners'][EChartsIdentity::LegacyProductName()]), 'A hot update must replace its own old registration with the new route.');
    // During a real library reload, another object's PHP interface may not exist yet.
    $GLOBALS['symconTestInstances'][9001] = [
        'ConnectionID' => 0, 'InstanceStatus' => 105,
        'ModuleInfo'   => ['ModuleID' => $gatewayModuleID]
    ];
    $GLOBALS['symconTestModuleInstances'][$gatewayModuleID][] = 9001;
    $first->ApplyChanges();
    assertGatewayGauge(!in_array(9001, $GLOBALS['symconTestTransportRefreshCalls'] ?? [], true), 'Reload must not call a gateway whose module interface is not yet available.');
    unset($GLOBALS['symconTestInstances'][9001]);
    $GLOBALS['symconTestModuleInstances'][$gatewayModuleID] = [6001];
    $second = $createGateway(7001);
    assertGatewayGauge($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME] === 6001, 'Adding a gateway must not replace the deterministic shared hook owner.');

    foreach ([6101 => 6001, 7101 => 7001] as $device => $parent) {
        $GLOBALS['symconTestInstances'][$device] = ['ConnectionID' => $parent];
        $GLOBALS['symconTestGatewayResponders'][$parent] = [static function (string $json) use ($device, $channel): string
        {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR)['Payload'];
            if ($payload['InstanceID'] !== $device || $payload['Channel'] !== $channel) {
                return '';
            }
            return EChartsDataProtocol::EncodeSuccessResponse('ipsview.state', ['State' => ['device' => $device, 'output' => $payload['Output'], 'period' => $payload['Period'] ?? '', 'offset' => $payload['WindowOffset']]]);
        }];
    }
    $getState = static function (TestableEChartsGateway $handler, int $device, string $token, array $query = []): array
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = '/hook/eCharts/state/' . $device . '/' . $token;
        $_GET = $query;
        $body = $handler->ProcessTestHook();
        return ['status' => http_response_code(), 'state' => json_decode($body, true)];
    };
    foreach ([$first, $second] as $handler) {
        foreach ([6101, 7101] as $device) {
            $result = $getState($handler, $device, $channel);
            assertGatewayGauge($result['status'] === 200 && $result['state']['device'] === $device, 'Every hook handler must route through the actual connected gateway.');
            foreach (['tile', 'ipsview'] as $output) {
                $view = $getState($handler, $device, $channel, ['output' => $output, 'period' => '7d', 'offset' => '2']);
                assertGatewayGauge($view['status'] === 200 && $view['state']['period'] === '7d' && $view['state']['output'] === $output && $view['state']['offset'] === 2, 'The selected period and navigation offset must reach the correct output across gateways.');
            }
        }
    }
    assertGatewayGauge($getState($first, 7101, str_repeat('b', 32))['status'] === 404, 'Cross-gateway routing must preserve channel authorization.');
    foreach (['/assets/interaction.js', '/state/7101/' . $channel] as $suffix) {
        $_SERVER['SCRIPT_NAME'] = '/hook/' . EChartsIdentity::LegacyProductName() . $suffix;
        assertGatewayGauge($first->ProcessTestHook() === '' && http_response_code() === 404, 'The retired hook must not serve cached clients as an alias.');
    }
    foreach ([['24h'], str_repeat('a', 33), '24 hours'] as $invalidPeriod) {
        assertGatewayGauge($getState($first, 7101, $channel, ['period' => $invalidPeriod])['status'] === 400, 'Malformed period parameters must fail before data is loaded.');
    }
    assertGatewayGauge($getState($first, 7101, $channel, ['offset' => '-1'])['status'] === 400, 'Invalid offsets must still fail before dispatch.');
    assertGatewayGauge($getState($first, 7101, $channel, ['output' => 'tile'])['state']['output'] === 'tile', 'Native tile navigation must retain its authenticated output contract.');
    assertGatewayGauge($first->GetTransportState(7101, $channel, 0, 'ipsview') === '', 'A gateway must not impersonate another gateway child.');
    $third = $createGateway(5501);
    assertGatewayGauge($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME] === 5501, 'An out-of-order lower ID must become the single deterministic owner.');
    assertGatewayGauge($getState($third, 7101, $channel)['status'] === 200, 'Changing the owner must preserve existing state URLs.');

    $removeGateway = static function (int $id) use ($gatewayModuleID): void
    {
        unset($GLOBALS['symconTestInstances'][$id], $GLOBALS['symconTestGatewayObjects'][$id]);
        $GLOBALS['symconTestModuleInstances'][$gatewayModuleID] = array_values(array_diff($GLOBALS['symconTestModuleInstances'][$gatewayModuleID], [$id]));
        foreach ($GLOBALS['symconTestHookOwners'] as $hook => $owner) {
            if ($owner === $id) {
                unset($GLOBALS['symconTestHookOwners'][$hook]);
            }
        }
        foreach ($GLOBALS['symconTestGatewayObjects'] as $peer) {
            if (in_array(['SenderID' => $id, 'Message' => IM_DELETE], $peer->GetTestMessages(), true)) {
                $peer->MessageSink(1, $id, IM_DELETE, []);
            }
        }
    };
    $removeGateway(5501);
    assertGatewayGauge($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME] === 6001, 'Deleting the hook owner must automatically restore a surviving gateway.');
    $removeGateway(6001);
    assertGatewayGauge($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME] === 7001, 'Repeated deletion must hand off the hook to the remaining gateway.');
    assertGatewayGauge($getState($second, 7101, $channel)['status'] === 200, 'The remaining child must recover without ApplyChanges or configuration writes.');
    $second->MessageSink(2, 0, IPS_KERNELSTARTED, []);
    $second->ApplyChanges();
    assertGatewayGauge($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME] === 7001, 'Kernel startup and repeated updates must retain valid hook ownership.');
    $removeGateway(7001);
    assertGatewayGauge(!isset($GLOBALS['symconTestHookOwners'][EChartsAsset::HOOK_NAME]), 'Removing the last gateway must leave no stale hook.');
    assertGatewayGauge($GLOBALS['symconTestHookOwners'] === ['third-party' => 4321], 'Upgrade, peer handoff and removal must preserve unrelated hook registrations.');
} finally {
    foreach ($transportGlobals as $key => $value) {
        if ($value === null) {
            unset($GLOBALS[$key]);
        } else {
            $GLOBALS[$key] = $value;
        }
    }
    $_SERVER = $transportServer;
    $_GET = $transportGet;
    http_response_code(200);
}

if (array_slice($argv, 1) === ['--gateway-transport']) {
    echo "Multi-gateway hook ownership, handoff, authenticated routing and restart verified.\n";
}
