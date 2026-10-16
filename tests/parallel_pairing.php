<?php

declare(strict_types=1);

use SymconECharts\ParallelPairing;

require_once __DIR__ . '/../EChartsParallel/ParallelPairing.php';

function assertParallelPairing(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$first = [[100, 5], [200, 0], [300, -2], [400, 9]];
$second = [[100, 8], [200, 3], [350, 10], [400, 12]];
$third = [[100, 1], [200, -4], [400, 0]];
assertParallelPairing(
    ParallelPairing::Pair([$first, $second, $third], 100) === [
        ['timestamp' => 100, 'values' => [5.0, 8.0, 1.0]],
        ['timestamp' => 200, 'values' => [0.0, 3.0, -4.0]],
        ['timestamp' => 400, 'values' => [9.0, 12.0, 0.0]]
    ],
    'Parallel must keep only exact shared intervals in source order, including zero and negatives.'
);
assertParallelPairing(
    ParallelPairing::Pair([$first, $second, $third], 2) === [
        ['timestamp' => 200, 'values' => [0.0, 3.0, -4.0]],
        ['timestamp' => 400, 'values' => [9.0, 12.0, 0.0]]
    ],
    'Parallel must retain the newest complete intervals within its line budget.'
);
assertParallelPairing(ParallelPairing::Pair([$first, []], 100) === [], 'An empty axis cannot create a line.');
foreach ([[[100, 1], [100, 2]], [[200, 1], [100, 2]], [[100, INF]], [[0, 1]]] as $invalid) {
    try {
        ParallelPairing::Pair([$invalid, $second], 100);
        throw new RuntimeException('Invalid archive points were accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Parallel interval pairing verified.\n";
