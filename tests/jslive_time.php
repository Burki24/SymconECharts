<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveTime.php';

use SymconECharts\EChartsJSLiveTime;

$fixtures = json_decode(file_get_contents(__DIR__ . '/fixtures/jslive-calendar-rules.json'), true, 32, JSON_THROW_ON_ERROR);
foreach ($fixtures as $fixture) {
    $zone = new DateTimeZone('Europe/Berlin');
    $now = (new DateTimeImmutable($fixture['date'], $zone))->getTimestamp();
    $time = ['timezone' => 'Europe/Berlin', 'selection' => ['Period' => ['value' => $fixture['period']],
        'Relativ'                                                    => ['value' => $fixture['relative']], 'Now' => ['value' => true]]];
    $actual = EChartsJSLiveTime::Window($time, $fixture['family'], $now, $fixture['offset']);
    if (array_values(array_intersect_key($actual, array_flip(['fromInclusive', 'untilExclusive', 'displayOffsetSeconds', 'aggregationLevel']))) !== $fixture['expected']) {
        throw new RuntimeException('Legacy time rule changed: ' . json_encode($fixture));
    }
}
$rule = ['mode' => 'raw', 'period' => 5, 'counter' => true, 'decimation' => 1, 'precision' => 5, 'fromInclusive' => 100, 'displayOffsetSeconds' => 86400];
$rows = [['TimeStamp' => 300, 'Value' => 105.0], ['TimeStamp' => 200, 'Value' => 100.0]];
$points = EChartsJSLiveTime::Cartesian($rows, $rule, 110.0, 400);
if (array_column($points, 'y') !== [5.0, 5.0] || array_column($points, 'x') !== [86600000, 86700000]) {
    throw new RuntimeException('Counter boundary or source/display timestamp changed.');
}
$bool = EChartsJSLiveTime::Cartesian([['TimeStamp' => 200, 'Value' => true], ['TimeStamp' => 100, 'Value' => false]], $rule, true, 400, ['ACTIVE', 'PAUSED']);
if (array_column($bool, 'y') !== ['PAUSED', 'ACTIVE'] || EChartsJSLiveTime::Cartesian([], $rule, true, 400, ['ACTIVE', 'PAUSED']) !== []) {
    throw new RuntimeException('Boolean axis conversion or empty-archive rule changed.');
}
echo 'JSLive time: ' . count($fixtures) . " frozen reference windows plus raw counter/Boolean fixtures passed.\n";
