<?php

declare(strict_types=1);

use SymconECharts\EChartsAsset;

require_once dirname(__DIR__) . '/libs/EChartsAsset.php';

function assertEChartsAsset(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$runtimePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/echarts.gauge.min.js';
$licensePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/LICENSE.txt';
$noticePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/NOTICE.txt';
$runtime = (string) file_get_contents($runtimePath);
$windowsCheckoutRuntime = str_replace("\n", "\r\n", $runtime);

assertEChartsAsset(is_file($runtimePath), 'The pinned Apache ECharts runtime is missing.');
assertEChartsAsset(
    hash_file('sha256', $runtimePath) === EChartsAsset::SHA256,
    'The pinned Apache ECharts runtime checksum changed.'
);
assertEChartsAsset(EChartsAsset::JavaScript() === $runtime, 'The asset loader changed the runtime.');
assertEChartsAsset(
    strlen($runtime) < 524288,
    'The Gauge-specific ECharts runtime must remain below 512 KiB.'
);
assertEChartsAsset(str_contains($runtime, 'window.echarts'), 'The Gauge runtime must expose the ECharts browser API.');
assertEChartsAsset(
    hash('sha256', $windowsCheckoutRuntime) !== EChartsAsset::SHA256,
    'The Windows-checkout regression fixture must differ byte-for-byte.'
);
assertEChartsAsset(
    EChartsAsset::HasExpectedIntegrity($windowsCheckoutRuntime),
    'The loader must accept the pinned runtime after a Windows CRLF checkout.'
);
assertEChartsAsset(
    !EChartsAsset::HasExpectedIntegrity($windowsCheckoutRuntime . 'changed'),
    'The loader must reject content changes beyond line-ending normalization.'
);
assertEChartsAsset(str_contains((string) file_get_contents($licensePath), 'Apache License'), 'ECharts license is missing.');
assertEChartsAsset(str_contains((string) file_get_contents($noticePath), 'Apache ECharts'), 'ECharts NOTICE is missing.');

echo 'Pinned Apache ECharts ' . EChartsAsset::VERSION . " assets verified.\n";
