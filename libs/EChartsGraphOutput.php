<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/** Lossless startup packaging shared by manual and exported graph documents. */
final class EChartsGraphOutput
{
    private const COMPRESSION_THRESHOLD = 200000;

    /** @param array<string,mixed> $state @return array{state:?array,compressedStateGzipBase64:?string} */
    public static function Startup(array $state): array
    {
        // Budget the actual HTML-safe encoding, not just the shorter raw model JSON.
        $json = json_encode($state, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP
            | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($json) <= self::COMPRESSION_THRESHOLD) {
            return ['state' => $state, 'compressedStateGzipBase64' => null];
        }
        $gzip = gzencode($json, 6);
        if ($gzip === false) {
            throw new RuntimeException('The Graph startup state could not be compressed.');
        }
        return ['state' => null, 'compressedStateGzipBase64' => base64_encode($gzip)];
    }
}
