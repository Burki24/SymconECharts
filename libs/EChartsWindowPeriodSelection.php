<?php

declare(strict_types=1);

namespace eCharts;

use InvalidArgumentException;

/** Routes calendar-style period choices and offsets through the same read-only view. */
trait EChartsWindowPeriodSelection
{
    private function WindowPeriodTransportState(array $payload): array
    {
        $ipsView = ($payload['Output'] ?? 'ipsview') !== 'tile';
        $period = $payload['Period'] ?? '';
        if ($period !== '' && !isset($this->PeriodChoices($ipsView)[$period])) {
            throw new InvalidArgumentException('The selected period is unavailable.');
        }

        return $this->BuildVisualizationState($ipsView, $payload['WindowOffset'] ?? 0, $period);
    }
}
