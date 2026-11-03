<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/EChartsStartupState.php';

/** Lossless startup packaging shared by manual and exported graph documents. */
final class EChartsGraphOutput
{
    /** @param array<string,mixed> $state @return array{state:?array,compressedStateGzipBase64:?string} */
    public static function Startup(array $state): array
    {
        return EChartsStartupState::Startup($state);
    }
}
