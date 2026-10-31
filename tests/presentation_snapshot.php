<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsVariablePresentation.php';

function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2, 'VariableProfile' => '', 'VariableCustomProfile' => '', 'VariableAction' => 0];
}
function IPS_GetVariablePresentation(int $id): array
{
    return ['PRESENTATION' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}', 'PREFIX' => 'ca. ', 'SUFFIX' => ' °C', 'DIGITS' => 1, 'MIN' => -20.0, 'MAX' => 60.0];
}
function IPS_SetVariableCustomPresentation(int $id, array $presentation): void
{
    throw new RuntimeException('Snapshot must never change source presentation.');
}

$snapshot = \SymconECharts\EChartsVariablePresentation::Snapshot(13000);
if ($snapshot['conversion']['status'] !== 'exact' || $snapshot['effective']['prefix'] !== 'ca. '
    || $snapshot['effective']['suffix'] !== ' °C' || isset($snapshot['native']['parameters']['PROFILE'])) {
    throw new RuntimeException('Shared ECharts migration presentation adapter lost formatting.');
}
echo "ECharts shared native presentation snapshot verified without changing ordinary chart resolution.\n";
