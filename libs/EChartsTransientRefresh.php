<?php

declare(strict_types=1);

namespace SymconECharts;

/** Retries a failed value read without changing the chart's configuration or data contract. */
trait EChartsTransientRefresh
{
    protected function RegisterTransientRefresh(string $prefix): void
    {
        $this->RegisterTimer('TransientRetry', 0, $prefix . '_RetryTransient($_IPS["TARGET"]);');
    }

    /** @param list<array<string, mixed>> $states */
    protected function ScheduleTransientRefresh(array $states, string $loadError): void
    {
        $retry = false;
        foreach ($states as $state) {
            if (($state['status'] ?? null) === 'error' && ($state['error'] ?? null) === $loadError) {
                $retry = true;
                break;
            }
        }
        $this->SetTimerInterval('TransientRetry', $retry ? 60000 : 0);
    }
}
