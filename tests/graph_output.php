<?php

declare(strict_types=1);

use SymconECharts\EChartsGraphOutput;

require_once dirname(__DIR__) . '/libs/EChartsGraphOutput.php';

$small = ['status' => 'ready', 'chart' => ['graph' => ['nodes' => [['id' => 'a', 'name' => '<&']]]]];
$startup = EChartsGraphOutput::Startup($small);
if ($startup['state'] !== $small || $startup['compressedStateGzipBase64'] !== null) {
    throw new RuntimeException('Small Graph startup states must keep their old direct representation.');
}
$large = [...$small, 'payload' => str_repeat('<&', 20000)];
// Raw JSON is below 200000 bytes, but its actual HTML-safe representation exceeds it.
if (strlen(json_encode($large, JSON_THROW_ON_ERROR)) >= 200000) {
    throw new RuntimeException('The Graph HTML-expansion fixture is invalid.');
}
$startup = EChartsGraphOutput::Startup($large);
if ($startup['state'] !== null || !is_string($startup['compressedStateGzipBase64'])) {
    throw new RuntimeException('Graph compression must use the HTML-safe byte budget.');
}
$restored = json_decode(gzdecode(base64_decode($startup['compressedStateGzipBase64'], true)), true, 512, JSON_THROW_ON_ERROR);
if ($restored !== $large) {
    throw new RuntimeException('Graph startup compression must be lossless.');
}
echo "Graph startup packaging verified.\n";
