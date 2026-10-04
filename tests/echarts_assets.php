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
$runtimePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/echarts.min.js';
$licensePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/LICENSE.txt';
$noticePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/NOTICE.txt';

assertEChartsAsset(is_file($runtimePath), 'The pinned Apache ECharts runtime is missing.');
assertEChartsAsset(
    hash_file('sha256', $runtimePath) === EChartsAsset::SHA256,
    'The pinned Apache ECharts runtime checksum changed.'
);
assertEChartsAsset(EChartsAsset::JavaScript() === file_get_contents($runtimePath), 'The asset loader changed the runtime.');
assertEChartsAsset(str_contains((string) file_get_contents($licensePath), 'Apache License'), 'ECharts license is missing.');
assertEChartsAsset(str_contains((string) file_get_contents($noticePath), 'Apache ECharts'), 'ECharts NOTICE is missing.');

echo 'Pinned Apache ECharts ' . EChartsAsset::VERSION . " assets verified.\n";
