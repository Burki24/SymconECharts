<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsLabelDesign.php';

use SymconECharts\EChartsLabelDesign;

function assertLabelDesign(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertLabelDesign(
    EChartsLabelDesign::IsValidPosition('inside', EChartsLabelDesign::CARTESIAN_POSITIONS),
    'A supported bar label position must validate.'
);
assertLabelDesign(
    !EChartsLabelDesign::IsValidPosition('bogus', EChartsLabelDesign::CARTESIAN_POSITIONS),
    'Unknown positions must be rejected.'
);
assertLabelDesign(EChartsLabelDesign::IsValidDistance(-1, true)
    && !EChartsLabelDesign::IsValidDistance(-1), 'Only automatic distance may use -1.');

$form = [['type' => 'ExpansionPanel', 'items' => [
    ['type' => 'RowLayout', 'items' => [['name' => 'ShowValues', 'type' => 'CheckBox']]]
]]];
assertLabelDesign(
    EChartsLabelDesign::InsertAfter($form, 'ShowValues', EChartsLabelDesign::CartesianControls()),
    'Shared label controls must be inserted in an existing designer row.'
);
assertLabelDesign(
    ($form[0]['items'][0]['items'][1]['name'] ?? '') === 'ValueLabelPosition'
    && ($form[0]['items'][0]['items'][2]['name'] ?? '') === 'ValueLabelDistance',
    'Position and distance must follow the visibility switch.'
);

$placement = EChartsLabelDesign::PreviewRect(10, 20, 30, 40, 'right', 8);
assertLabelDesign(
    $placement['x'] === 48.0 && $placement['anchor'] === 'start',
    'Schematic bar labels must reflect configured distance.'
);

echo "Shared ECharts label design verified.\n";
