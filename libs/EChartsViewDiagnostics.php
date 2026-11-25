<?php

declare(strict_types=1);

namespace eCharts;

use Closure;

/** A viewer's archive request must not overwrite the configured instance's diagnostics. */
trait EChartsViewDiagnostics
{
    private ?string $viewError = null;

    protected function WithViewDiagnostics(bool $ephemeral, Closure $build): mixed
    {
        if (!$ephemeral) {
            return $build();
        }
        $previous = $this->viewError;
        $this->viewError = '';
        try {
            return $build();
        } finally {
            $this->viewError = $previous;
        }
    }

    protected function SetViewError(string $message): void
    {
        if ($this->viewError === null) {
            $this->WriteAttributeString('LastError', $message);
        } else {
            $this->viewError = $message;
        }
    }

    protected function GetViewError(): string
    {
        return $this->viewError ?? $this->ReadAttributeString('LastError');
    }

    protected function SetViewStatus(int $status): void
    {
        if ($this->viewError === null) {
            $this->DebugEChartsSetStatus($status);
        }
    }
}
