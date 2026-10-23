<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsBarWaterfall/WaterfallData.php';

use SymconECharts\WaterfallData;

function assertWaterfallData(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

assertWaterfallData(WaterfallData::Totals([100, -120, 5, 0]) === [100.0, -20.0, -15.0, -15.0], 'Signed totals must preserve order, zero crossings and real zero.');
assertWaterfallData(WaterfallData::Totals([-5, 2]) === [-5.0, -3.0], 'The first source is an absolute signed start value.');
$values = [100, ...array_fill(0, 99, -1)];
$totals = WaterfallData::Totals($values);
assertWaterfallData(count($totals) === 100 && $totals[99] === 1.0 && $totals[4] === 96.0, 'A preview prefix must not replace the complete hundred-source balance.');
foreach ([[], array_fill(0, 101, 1), [1, '2'], [1, INF], [NAN, 1], [1 => 1, 2 => 2]] as $invalid) {
    try {
        WaterfallData::Totals($invalid);
        throw new LogicException('Invalid waterfall arithmetic accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([[PHP_FLOAT_MAX, PHP_FLOAT_MAX], [-PHP_FLOAT_MAX, -PHP_FLOAT_MAX]] as $overflow) {
    try {
        WaterfallData::Totals($overflow);
        throw new LogicException('Nonfinite cumulative totals accepted.');
    } catch (RuntimeException $exception) {
        assertWaterfallData($exception->getMessage() === 'The Waterfall total is not finite.', 'Overflow must fail without a truncated balance.');
    }
}
echo "Full Waterfall signed arithmetic verified.\n";

$sources = [['VariableID' => 10], ['VariableID' => 11, 'SubtotalAfter' => true], ['VariableID' => 12]];
$currents = [['Value' => 100.0, 'Timestamp' => 1], ['Value' => -20.0, 'Timestamp' => 2], ['Value' => 5.0, 'Timestamp' => 3]];
$labels = [10 => 'Same', 11 => '<Step>', 12 => 'Same'];
$steps = WaterfallData::Steps($sources, $currents, $labels, 'End');
assertWaterfallData(array_column($steps, 'kind') === ['start', 'change', 'subtotal', 'change', 'total'], 'A selected subtotal must become a separate calculated step.');
assertWaterfallData($steps[2]['before'] === 0.0 && $steps[2]['after'] === 80.0 && $steps[3]['before'] === 80.0
    && $steps[4]['after'] === 85.0 && $steps[2]['calculated'] === true && !isset($steps[2]['timestamp']), 'A subtotal must start at zero and never be added again or invent a measurement timestamp.');

$restartSources = [...$sources, ['VariableID' => 13]];
$restartSources[2]['Restart'] = true;
$restarted = WaterfallData::Steps($restartSources, [...array_slice($currents, 0, 2), ['Value' => -5.0, 'Timestamp' => 3], ['Value' => 2.0, 'Timestamp' => 4]], $labels + [13 => 'Last'], 'End');
assertWaterfallData(
    $restarted[3]['kind'] === 'restart' && $restarted[3]['before'] === 0.0 && $restarted[3]['after'] === -5.0
    && $restarted[3]['previousTotal'] === 80.0 && $restarted[4]['before'] === -5.0 && $restarted[5]['after'] === -3.0,
    'A restart must begin an independent absolute balance and retain the previous closing amount.'
);
assertWaterfallData(
    $restarted[3]['label'] === 'Restart: Same' && $restarted[3]['section'] === 2
    && $restarted[5]['label'] === 'Final section total: End' && $restarted[0]['section'] === 1,
    'Separate sections and the final section total must be explicitly identified.'
);
$oldSources = array_map(static fn (array $row): array => ['VariableID' => $row['VariableID']], $sources);
$legacy = WaterfallData::Steps($oldSources, $currents, $labels, 'End');
assertWaterfallData($legacy === [
    ['id' => 'variable-10', 'label' => 'Same', 'kind' => 'start', 'change' => 100.0, 'before' => 0.0, 'after' => 100.0, 'timestamp' => 1],
    ['id' => 'variable-11', 'label' => '<Step>', 'kind' => 'change', 'change' => -20.0, 'before' => 100.0, 'after' => 80.0, 'timestamp' => 2],
    ['id' => 'variable-12', 'label' => 'Same', 'kind' => 'change', 'change' => 5.0, 'before' => 80.0, 'after' => 85.0, 'timestamp' => 3],
    ['id' => 'total', 'label' => 'End', 'kind' => 'total', 'change' => 85.0, 'before' => 0.0, 'after' => 85.0]
], 'Old lists must keep the exact public step model, not only the same final value.');
$falseSources = array_map(static fn (array $row): array => [...$row, 'SubtotalAfter' => false, 'Restart' => false], $oldSources);
assertWaterfallData(WaterfallData::Steps($falseSources, $currents, $labels, 'End') === $legacy, 'Explicit false and missing row options must be identical.');
$reordered = WaterfallData::Steps([$sources[0], $sources[2], $sources[1]], [$currents[0], $currents[2], $currents[1]], $labels, 'End');
assertWaterfallData(
    $reordered[3]['id'] === $steps[2]['id'] && $reordered[3]['after'] === 85.0,
    'Subtotal anchors follow source IDs after reorder, never labels or row positions.'
);
foreach (['SubtotalAfter', 'Restart'] as $name) {
    foreach ([1, 'true', null, []] as $invalid) {
        try {
            WaterfallData::SourceOptions([$name => $invalid], 1);
            throw new LogicException('Invalid row option accepted.');
        } catch (InvalidArgumentException $e) {
            assertWaterfallData(str_contains($e->getMessage(), 'Source 2'), 'Invalid row options must identify the source.');
        }
    }
}
try {
    WaterfallData::SourceOptions(['Restart' => true], 0);
    throw new LogicException('The first start row was accepted as a restart.');
} catch (InvalidArgumentException) {
}
assertWaterfallData(
    WaterfallData::Totals([PHP_FLOAT_MAX, PHP_FLOAT_MAX, -1], [false, true, true]) === [PHP_FLOAT_MAX, PHP_FLOAT_MAX, -1.0],
    'An absolute restart must not overflow by being temporarily added to the old section.'
);
try {
    WaterfallData::Totals([PHP_FLOAT_MAX, PHP_FLOAT_MAX, 1], [false, false, true]);
    throw new LogicException('A later restart concealed an earlier section overflow.');
} catch (RuntimeException) {
}
foreach ([[true, false], [false], [false, 1], [1 => false, 2 => true]] as $invalidRestarts) {
    try {
        WaterfallData::Totals([1, 2], $invalidRestarts);
        throw new LogicException('Invalid arithmetic restart mask accepted.');
    } catch (InvalidArgumentException) {
    }
}
$manySources = $manyValues = $manyLabels = [];
for ($i = 0; $i < 100; ++$i) {
    $manySources[] = ['VariableID' => $i + 1, 'SubtotalAfter' => true, 'Restart' => $i > 0];
    $manyValues[] = ['Value' => (float) -$i, 'Timestamp' => $i];
    $manyLabels[$i + 1] = 'Same';
}
$many = WaterfallData::Steps($manySources, $manyValues, $manyLabels, 'End');
assertWaterfallData(
    count($many) === 201 && count(array_unique(array_column($many, 'id'))) === 201
    && $many[200]['after'] === -99.0 && $many[200]['section'] === 100,
    'One hundred sources permit at most one subtotal each and a single final total, not new source reads.'
);
echo "Waterfall optional subtotals, independent restarts and exact legacy steps verified.\n";
