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
