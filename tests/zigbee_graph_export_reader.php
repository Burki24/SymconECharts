<?php

declare(strict_types=1);

use SymconECharts\ZigbeeGraphExportReader;

require_once dirname(__DIR__) . '/EChartsGraphZ2M/ZigbeeGraphExportReader.php';

$kernel = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ecgz-reader-' . bin2hex(random_bytes(8));
$directory = $kernel . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'IPSZigbee2MQTT'
    . DIRECTORY_SEPARATOR . 'networkmaps';
if (!mkdir($directory, 0777, true)) {
    throw new RuntimeException('Unable to create a temporary export directory.');
}

$files = [];
try {
    try {
        ZigbeeGraphExportReader::ReadLatest($kernel);
        throw new RuntimeException('A missing export was accepted.');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'A missing export was accepted.') {
            throw $exception;
        }
    }

    $old = $directory . DIRECTORY_SEPARATOR . 'zigbee-network-map-20261014-120000.json';
    $latest = $directory . DIRECTORY_SEPARATOR . 'zigbee-network-map-20261015-120000.json';
    $ignored = $directory . DIRECTORY_SEPARATOR . 'unrelated.json';
    $files = [$old, $latest, $ignored];
    file_put_contents($old, '{"captured_at":1}');
    file_put_contents($latest, json_encode([
        'captured_at' => 2,
        'padding'     => str_repeat('x', 1100000)
    ], JSON_THROW_ON_ERROR));
    file_put_contents($ignored, '{"captured_at":3}');

    if (ZigbeeGraphExportReader::ReadLatest($kernel)['captured_at'] !== 2) {
        throw new RuntimeException('The latest matching export over 1 MiB was not read.');
    }

    file_put_contents($latest, '{invalid');
    try {
        ZigbeeGraphExportReader::ReadLatest($kernel);
        throw new RuntimeException('A broken latest export was silently replaced by an older snapshot.');
    } catch (JsonException $exception) {
        // A broken latest export must be reported, not hidden by an older file.
    }
} finally {
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($directory);
    rmdir(dirname($directory));
    rmdir(dirname(dirname($directory)));
    rmdir($kernel);
}

echo "Zigbee graph export reader verified.\n";
