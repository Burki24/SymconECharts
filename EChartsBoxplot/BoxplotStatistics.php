<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;

/** Five-number summary of complete hourly means, using R7 quantiles; no event-count weighting. */
final class BoxplotStatistics
{
    public static function Summarize(array $points, int $start, int $end): array
    {
        if (!array_is_list($points) || count($points) > 1000 || $start < 1 || $end < $start) {
            throw new InvalidArgumentException('Invalid Boxplot sample window or size.');
        }
        $values = [];
        $previous = 0;
        foreach ($points as $point) {
            if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                || !is_int($point[0]) || $point[0] <= $previous || $point[0] < $start || $point[0] > $end
                || (!is_int($point[1]) && !is_float($point[1])) || !is_finite((float) $point[1])) {
                throw new InvalidArgumentException('Boxplot samples must be finite, strictly ordered and inside the completed window.');
            }
            $previous = $point[0];
            $values[] = (float) $point[1];
        }
        $count = count($values);
        if ($count === 0) {
            return ['statistics' => null, 'count' => 0, 'firstTimestamp' => null, 'lastTimestamp' => null];
        }
        sort($values, SORT_NUMERIC);
        $quantile = static function (float $p) use ($values, $count): float
        {
            $index = ($count - 1) * $p;
            $lower = (int) floor($index);
            $fraction = $index - $lower;
            return $values[$lower] * (1 - $fraction) + $values[min($count - 1, $lower + 1)] * $fraction;
        };
        return ['statistics' => [$values[0], $quantile(0.25), $quantile(0.5), $quantile(0.75), $values[$count - 1]],
            'count'          => $count, 'firstTimestamp' => $points[0][0], 'lastTimestamp' => $points[$count - 1][0]];
    }
}
