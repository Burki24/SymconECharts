<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsBoxplot/BoxplotStatistics.php';

use SymconECharts\BoxplotStatistics;

$summary = BoxplotStatistics::Summarize([[10, 8], [20, -4], [30, 0], [40, 4]], 1, 40);
if ($summary['statistics'] !== [-4.0, -1.0, 2.0, 5.0, 8.0] || $summary['count'] !== 4) {
    throw new RuntimeException('R7 quartiles or negative/zero values are incorrect.');
}
if (BoxplotStatistics::Summarize([[10, 7]], 1, 40)['statistics'] !== [7.0, 7.0, 7.0, 7.0, 7.0]
    || BoxplotStatistics::Summarize([], 1, 40)['statistics'] !== null) {
    throw new RuntimeException('Sparse archives must not manufacture samples.');
}
foreach ([[[0, 1]], [[10, NAN]], [[10, 1], [10, 2]], [[20, 1], [10, 2]], [[50, 1]]] as $invalid) {
    try {
        BoxplotStatistics::Summarize($invalid, 1, 40);
        throw new RuntimeException('Invalid Boxplot samples accepted.');
    }
    catch (InvalidArgumentException) {
    }
}
$extreme = BoxplotStatistics::Summarize([[10, -1.0e308], [20, 1.0e308]], 1, 40)['statistics'];
if (!is_finite($extreme[1]) || $extreme[2] !== 0.0) {
    throw new RuntimeException('Finite extreme samples must retain finite quantiles.');
}
echo "Boxplot statistics verified.\n";

// R7 on [-10, 1, 2, 3, 4, 5, 6, 20]: Q1=1.75, Q3=5.25; fences=-3.5/10.5.
$iqrPoints = array_map(static fn ($i, $v): array => [10 + $i * 10, $v], range(0, 7), [-10, 1, 2, 3, 4, 5, 6, 20]);
$iqr = BoxplotStatistics::Summarize($iqrPoints, 1, 80, 'iqr');
if ($iqr['statistics'] !== [1.0, 1.75, 3.5, 5.25, 6.0]
    || $iqr['outliers'] !== [[10, -10.0], [80, 20.0]] || $iqr['outlierCount'] !== 2
    || $iqr['sampleMinimum'] !== -10.0 || $iqr['sampleMaximum'] !== 20.0 || $iqr['count'] !== 8) {
    throw new RuntimeException('IQR whiskers must end at observed inliers and preserve separate timestamped outliers.');
}
$fencePoints = array_map(static fn ($i, $v): array => [10 + $i * 10, $v], range(0, 8), [-3, 0, 0, 1, 1, 1, 2, 2, 5]);
$fence = BoxplotStatistics::Summarize($fencePoints, 1, 90, 'iqr');
if ($fence['statistics'] !== [-3.0, 0.0, 1.0, 2.0, 5.0] || $fence['outliers'] !== []) {
    throw new RuntimeException('Samples exactly on a fence must remain inliers.');
}
$zeroIQR = BoxplotStatistics::Summarize([[10, 0], [20, 0], [30, 0], [40, 0], [50, 9]], 1, 50, 'iqr');
if ($zeroIQR['statistics'] !== [0.0, 0.0, 0.0, 0.0, 0.0] || $zeroIQR['outliers'] !== [[50, 9.0]]) {
    throw new RuntimeException('Zero IQR must not hide distinct samples outside its zero-width fences.');
}
foreach ([[], [[10, 7]], [[10, 7], [20, 7]], [[10, -1.0e308], [20, 1.0e308]]] as $points) {
    $result = BoxplotStatistics::Summarize($points, 1, 80, 'iqr');
    if ($result['outliers'] !== [] || $result['outlierCount'] !== 0
        || json_encode($result, JSON_THROW_ON_ERROR) === '') {
        throw new RuntimeException('Sparse, constant and extreme finite IQR inputs must stay finite and lossless.');
    }
}
// No overflow/underflow in classification even with an enormous or subnormal range.
foreach ([1.0e306, 1.0e-310] as $scale) {
    $scaled = array_map(static fn ($p): array => [$p[0], $p[1] * $scale], $iqrPoints);
    $result = BoxplotStatistics::Summarize($scaled, 1, 80, 'iqr');
    if ($result['outlierCount'] !== 2 || array_column($result['outliers'], 0) !== [10, 80]) {
        throw new RuntimeException('IQR classification must survive extreme finite scales.');
    }
}
try {
    BoxplotStatistics::Summarize([], 1, 80, 'unknown');
    throw new RuntimeException('Unknown whisker model accepted.');
} catch (InvalidArgumentException) {
}
echo "Optional IQR whiskers and timestamped outliers verified.\n";
