<?php

declare(strict_types=1);

use SymconECharts\HeatmapComparison;

require_once __DIR__ . '/../libs/EChartsArchiveQuery.php';
require_once __DIR__ . '/../libs/EChartsDataProtocol.php';
require_once __DIR__ . '/../EChartsHeatmap/HeatmapWindow.php';
require_once __DIR__ . '/../EChartsHeatmap/HeatmapComparison.php';

$now = (new DateTimeImmutable('2026-10-13 12:34:00', new DateTimeZone('Europe/Berlin')))->getTimestamp();
$rows = HeatmapComparison::Sources(json_encode([
    ['VariableID' => 10, 'Label' => 'Same name'],
    ['VariableID' => 11, 'Label' => 'Same name'],
    ['VariableID' => 10, 'PeriodOffset' => 1],
    ['VariableID' => 10]
], JSON_THROW_ON_ERROR));
$plan = HeatmapComparison::Plan($rows, '1m', [], 0, $now, 'Europe/Berlin');
if (count($plan['Panels']) !== 4 || count($plan['Requests']) !== 3
    || $plan['Panels'][0]['Query']['StartDate'] !== '2026-09-01'
    || $plan['Panels'][2]['Query']['StartDate'] !== '2026-08-01') {
    throw new RuntimeException('Four views must retain ID/period identities and reuse exactly identical archive queries.');
}
$prior = HeatmapComparison::Plan($rows, '1m', [], 1, $now, 'Europe/Berlin');
if ($prior['Panels'][2]['Query']['StartDate'] !== '2026-07-01') {
    throw new RuntimeException('Common navigation must shift every comparison view.');
}
$date = static fn (string $day): string => json_encode(['year' => 2026, 'month' => 9, 'day' => (int) $day,
    'hour'                                                     => 8, 'minute' => 30, 'second' => 0], JSON_THROW_ON_ERROR);
$own = HeatmapComparison::Sources(json_encode([['VariableID' => 10, 'UseOwnPeriod' => true,
    'AbsoluteStart'                                          => $date('1'), 'AbsoluteEnd' => $date('2')]], JSON_THROW_ON_ERROR));
$ownPlan = HeatmapComparison::Plan($own, '1m', [], 0, $now, 'Europe/Berlin');
if ($ownPlan['Panels'][0]['Range'] !== 'absolute'
    || $ownPlan['Panels'][0]['Query']['RequestedStartLabel'] !== '2026-09-01 08:30:00 +02:00') {
    throw new RuntimeException('Individual historical periods must reuse strict local date/full-hour planning.');
}
if ($ownPlan['Panels'][0]['Query']['StartTimestamp'] !== (new DateTimeImmutable('2026-09-01 09:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp()
    || $ownPlan['Panels'][0]['Query']['EndTimestamp'] !== (new DateTimeImmutable('2026-09-02 08:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp() - 1) {
    throw new RuntimeException('Only fully contained hours may enter an individual comparison period.');
}
$sevenRows = [['VariableID' => 10], ['VariableID' => 11], ['VariableID' => 12],
    ['VariableID' => 13, 'UseOwnPeriod' => true, 'AbsoluteStart' => $date('1'), 'AbsoluteEnd' => $date('2')]];
$seven = HeatmapComparison::Plan(HeatmapComparison::Sources(json_encode($sevenRows, JSON_THROW_ON_ERROR)), '3m', [], 0, $now, 'Europe/Berlin');
if (count($seven['Requests']) !== 7) {
    throw new RuntimeException('Exactly seven shared queries must remain available.');
}
try {
    HeatmapComparison::Plan(HeatmapComparison::Sources(json_encode(array_fill(0, 4, ['VariableID' => 10, 'PeriodOffset' => 120]), JSON_THROW_ON_ERROR)), '1m', [], 1, $now, 'Europe/Berlin');
    throw new RuntimeException('Navigation must stop before any individual offset exceeds 120.');
} catch (InvalidArgumentException) {
}
foreach (['[]', '{}', '[{"VariableID":"10"}]', '[{"VariableID":10,"PeriodOffset":-1}]',
    '[{"VariableID":10,"UseOwnPeriod":"true"}]', '[{"VariableID":10,"Label":null}]',
    json_encode(array_fill(0, 5, ['VariableID' => 10]), JSON_THROW_ON_ERROR), str_repeat(' ', 65537)] as $invalid) {
    try {
        HeatmapComparison::Sources($invalid);
        throw new RuntimeException('Invalid comparison sources must fail.');
    } catch (InvalidArgumentException) {
    }
}
try {
    HeatmapComparison::Plan($rows, '12m', [], 0, $now, 'Europe/Berlin');
    throw new RuntimeException('All views must share the seven-query budget.');
} catch (InvalidArgumentException $exception) {
    if (!str_contains($exception->getMessage(), 'seven')) {
        throw $exception;
    }
}
echo "Heatmap comparison planning tests passed.\n";
