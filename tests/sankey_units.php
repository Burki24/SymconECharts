<?php

declare(strict_types=1);

use SymconECharts\SankeyModel;

require_once dirname(__DIR__) . '/EChartsSankey/SankeyModel.php';

$sources = [['Source' => 'a', 'Target' => 'b', 'VariableID' => 10],
    ['Source' => 'b', 'Target' => 'c', 'VariableID' => 11],
    ['Source' => 'a', 'Target' => 'c', 'VariableID' => 10]];
$reads = [];
$read = static function (int $id) use (&$reads): string
{
    $reads[] = $id;
    return $id === 10 ? ' W ' : 'W';
};
if (SankeyModel::ResolveUnit($sources, 'automatic', 'ignored', $read) !== 'W' || $reads !== [10, 11]) {
    throw new RuntimeException('Automatic Sankey units must resolve once per ID, not per connection.');
}
if (SankeyModel::ResolveUnit($sources, 'manual', ' kWh ', static function (): never
{
    throw new RuntimeException('Legacy mode must not inspect presentation metadata.');
}) !== 'kWh' || SankeyModel::ResolveUnit($sources, 'checked', 'W', $read) !== 'W') {
    throw new RuntimeException('Manual display compatibility and explicitly checked units must remain separate.');
}
if (SankeyModel::ResolveUnit($sources, 'automatic', '', static fn (): string => '') !== '') {
    throw new RuntimeException('Entirely unitless flows must remain supported.');
}
foreach ([
    ['automatic', '', static fn (int $id): string => $id === 10 ? 'W' : 'kW', 'Connection 2:'],
    ['automatic', '', static fn (int $id): string => $id === 10 ? 'W' : '', 'Connection 2:'],
    ['checked', 'kWh', $read, 'Connection 1:'],
    ['checked', 'w', $read, 'Connection 1:'],
    ['automatic', '', static function (): never
    { throw new RuntimeException('private metadata error'); }, 'Connection 1:'],
    ['automatic', '', static fn (): string => str_repeat('W', 25), 'Connection 1:'],
    ['invalid', '', $read, 'unit mode'],
    ['manual', str_repeat('W', 25), $read, '24 characters']
] as [$mode, $unit, $reader, $message]) {
    try {
        SankeyModel::ResolveUnit($sources, $mode, $unit, $reader);
        throw new RuntimeException('Invalid or incompatible Sankey units were accepted.');
    } catch (InvalidArgumentException $e) {
        if (!str_contains($e->getMessage(), $message) || str_contains($e->getMessage(), 'private metadata error')) {
            throw new RuntimeException('Unit errors must identify the connection without leaking metadata failures.');
        }
    }
}
echo "Sankey optional unit policies verified.\n";
