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
