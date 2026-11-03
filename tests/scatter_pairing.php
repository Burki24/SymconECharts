<?php

declare(strict_types=1);

use SymconECharts\ScatterPairing;

require_once __DIR__ . '/../EChartsScatter/ScatterPairing.php';

function assertScatterPairing(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$x = [[100, 5], [200, 0], [300, 7], [400, 9]];
$y = [[100, 8], [200, -2], [350, 10], [400, 12]];
$size = [[100, 1], [200, 0], [300, 3]];
assertScatterPairing(
    ScatterPairing::Pair($x, $y) === [[100, 5.0, 8.0], [200, 0.0, -2.0], [400, 9.0, 12.0]],
    'Scatter must pair only equal interval timestamps, including zero and negative values.'
);
assertScatterPairing(
    ScatterPairing::Pair($x, $y, $size) === [[100, 5.0, 8.0, 1.0], [200, 0.0, -2.0, 0.0]],
    'Bubble points need all three sources in the same interval.'
);
assertScatterPairing(ScatterPairing::Pair([], $y) === [], 'An empty source cannot create points.');
foreach ([[[100, 1], [100, 2]], [[200, 1], [100, 2]], [[100, INF]]] as $invalid) {
    try {
        ScatterPairing::Pair($invalid, $y);
        throw new RuntimeException('Invalid archive points were accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Scatter interval pairing verified.\n";
