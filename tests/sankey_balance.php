<?php

declare(strict_types=1);

use SymconECharts\SankeyModel;

require_once dirname(__DIR__) . '/EChartsSankey/SankeyModel.php';

function balanceAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$nodes = [['Key' => 'grid', 'Label' => 'Same'], ['Key' => 'house', 'Label' => '<House>'],
    ['Key' => 'load', 'Label' => 'Same']];
$sources = [['Source' => 'grid', 'Target' => 'house', 'VariableID' => 10],
    ['Source' => 'house', 'Target' => 'load', 'VariableID' => 11]];
$values = [10 => ['Value' => 1000.0, 'Timestamp' => 100], 11 => ['Value' => 750.0, 'Timestamp' => 101]];
$legacy = SankeyModel::Build($nodes, $sources, $values);
$enabled = $nodes;
$enabled[1]['Balance'] = true;
$model = SankeyModel::Build($enabled, $sources, $values);
balanceAssert(count($model['data']) === 4 && count($model['links']) === 3, 'Explicit node balance must add a residual terminal and flow.');
$rest = $model['links'][2];
balanceAssert($rest['source'] === 'house' && $rest['target'] === '@balance:house' && $rest['value'] === 250.0
    && $rest['calculated'] === true && $rest['balanceFor'] === 'house' && $rest['balanceIncoming'] === 1000.0
    && $rest['balanceOutgoing'] === 750.0 && !isset($rest['variableID'], $rest['timestamp']), 'Residuals must be calculated from measured flows only, with no invented variable or timestamp.');
balanceAssert(
    array_slice($model['links'], 0, 2) === $legacy['links'] && array_slice($model['data'], 0, 3) === $legacy['data'],
    'Measured widths, identities, timestamps and node values must remain unchanged.'
);
balanceAssert(
    $model['data'][3]['label'] === 'Unassigned (calculated)' && $model['data'][3]['calculated'] === true,
    'A calculated terminal must be visibly and structurally distinguished from a measurement.'
);
$missing = SankeyModel::Build($enabled, $sources, array_replace($values, [11 => ['Value' => 1200.0, 'Timestamp' => 101]]));
balanceAssert(
    $missing['links'][2]['source'] === '@balance:house' && $missing['links'][2]['target'] === 'house'
    && $missing['links'][2]['value'] === 200.0 && $missing['data'][3]['label'] === 'Unexplained inflow (calculated)',
    'An outgoing surplus must create an unexplained incoming amount, never a negative residual.'
);
balanceAssert($missing['data'][3]['name'] === $model['data'][3]['name'], 'Generated identities must survive direction changes.');
$disabled = $nodes;
$disabled[1]['Balance'] = false;
balanceAssert(SankeyModel::Build($disabled, $sources, $values) === $legacy, 'False and absent balance options must preserve exact old models.');
balanceAssert(
    SankeyModel::Build($enabled, $sources, array_replace($values, [11 => $values[10]])) === SankeyModel::Build($nodes, $sources, array_replace($values, [11 => $values[10]])),
    'Equal measured totals must not create a fake zero residual.'
);

$reversed = $sources;
$reversed[1]['ReverseNegative'] = true;
$afterReversal = SankeyModel::Build($enabled, $reversed, array_replace($values, [11 => ['Value' => -750.0, 'Timestamp' => 101]]));
balanceAssert(
    $afterReversal['links'][2]['value'] === 1750.0 && $afterReversal['links'][2]['source'] === 'house',
    'Balances must use actual reversed directions, not configured endpoints.'
);
// A selected source/sink is allowed, but no unselected endpoint is automatically balanced.
$endpoints = $nodes;
$endpoints[0]['Balance'] = $endpoints[2]['Balance'] = true;
$terminals = SankeyModel::Build($endpoints, $sources, $values);
balanceAssert(count($terminals['links']) === 4 && $terminals['links'][2]['target'] === 'grid'
    && $terminals['links'][3]['source'] === 'load', 'Only expressly chosen nodes, including endpoints, may create balancing flows.');

$roundNodes = [...$enabled, ['Key' => 'other', 'Label' => 'Other']];
$roundSources = [...$sources, ['Source' => 'other', 'Target' => 'house', 'VariableID' => 12]];
$roundValues = [10 => ['Value' => 0.1, 'Timestamp' => 100], 11 => ['Value' => 0.3, 'Timestamp' => 100], 12 => ['Value' => 0.2, 'Timestamp' => 100]];
balanceAssert(
    count(SankeyModel::Build($roundNodes, $roundSources, $roundValues)['links']) === 3,
    'Floating-point roundoff must not become a claimed unmeasured flow.'
);
$tiny = SankeyModel::Build($enabled, $sources, [10 => ['Value' => 1.0e-300, 'Timestamp' => 100], 11 => ['Value' => 0.0, 'Timestamp' => 100]]);
balanceAssert($tiny['links'][1]['value'] === 1.0e-300, 'Relative tolerance must not erase genuinely tiny flows.');
$huge = SankeyModel::Build($enabled, $sources, [10 => ['Value' => PHP_FLOAT_MAX, 'Timestamp' => 100], 11 => ['Value' => 0.0, 'Timestamp' => 100]]);
balanceAssert($huge['links'][1]['value'] === PHP_FLOAT_MAX, 'A finite residual must not be rejected by counting generated flows as new measurements.');

foreach ([null, 1, 'true', []] as $invalid) {
    $bad = $nodes;
    $bad[1]['Balance'] = $invalid;
    try {
        SankeyModel::Build($bad, $sources, $values);
        throw new RuntimeException('Non-Boolean balance option was accepted.');
    } catch (InvalidArgumentException $e) {
        balanceAssert(str_contains($e->getMessage(), 'Node 2:'), 'Invalid balance selection must identify its row.');
    }
}
$many = $enabled;
for ($i = 3; $i < 99; ++$i) {
    $many[] = ['Key' => 'n' . $i, 'Label' => 'Node'];
}
balanceAssert(count(SankeyModel::Build($many, $sources, $values)['data']) === 4, '99 configured nodes plus one generated node must fit, including isolated nodes.');
$many[] = ['Key' => 'n99', 'Label' => 'Node'];
try {
    SankeyModel::Build($many, $sources, $values);
    throw new RuntimeException('Generated nodes exceeded the total 100-node budget.');
} catch (InvalidArgumentException $e) {
    balanceAssert(str_contains($e->getMessage(), '100 nodes'), 'Budget diagnostics must explain the unchanged limit.');
}
$edgeNodes = [];
$edgeSources = [];
for ($i = 0; $i < 98; ++$i) {
    $edgeNodes[] = ['Key' => 'e' . $i, 'Label' => 'Same', ...($i === 1 ? ['Balance' => true] : [])];
}
for ($from = 0; count($edgeSources) < 200; ++$from) {
    for ($to = $from + 1; $to < 98 && count($edgeSources) < 200; ++$to) {
        $edgeSources[] = ['Source' => 'e' . $from, 'Target' => 'e' . $to, 'VariableID' => 10];
    }
}
balanceAssert(
    count(SankeyModel::Build($edgeNodes, array_slice($edgeSources, 0, 199), $values)['links']) === 200,
    '199 measured edges plus one computed edge must fit the unchanged link budget.'
);
try {
    SankeyModel::Build($edgeNodes, $edgeSources, $values);
    throw new RuntimeException('Computed flow exceeded the total 200-edge limit.');
} catch (InvalidArgumentException $e) {
    balanceAssert(str_contains($e->getMessage(), '200 connections'), 'A dynamic balance overflow must explain the unchanged edge limit.');
}
// Two selected adjacent nodes must use the same measured snapshot, not recursive generated totals.
$chainNodes = [...$enabled, ['Key' => 'last', 'Label' => 'Last']];
$chainNodes[2]['Balance'] = true;
$chainSources = [...$sources, ['Source' => 'load', 'Target' => 'last', 'VariableID' => 12]];
$chain = SankeyModel::Build($chainNodes, $chainSources, $values + [12 => ['Value' => 500.0, 'Timestamp' => 102]]);
balanceAssert(
    array_column(array_slice($chain['links'], 3), 'value') === [250.0, 250.0],
    'Adjacent balances must not cascade or count generated values again.'
);
echo "Sankey explicit balances, originals, reversal, tolerance and node budget verified.\n";
