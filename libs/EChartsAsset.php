<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/**
 * Loads the pinned, unmodified Apache ECharts browser distribution.
 */
final class EChartsAsset
{
    public const VERSION = '6.1.0';
    public const SHA256 = 'b66b25aeb4df84e33199dc21694014d336d222cbd9deb0e5a7c14bd6aa0d0fd0';

    public static function JavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts runtime could not be loaded.');
        }
        if (!self::HasExpectedIntegrity($content)) {
            throw new RuntimeException('The bundled Apache ECharts runtime failed its integrity check.');
        }

        return self::NormalizeLineEndings($content);
    }

    /**
     * Verifies the upstream bytes while tolerating a Windows checkout that
     * converted the original LF line endings to CRLF.
     */
    public static function HasExpectedIntegrity(string $content): bool
    {
        $canonicalContent = self::NormalizeLineEndings($content);
        if (str_contains($canonicalContent, "\r")) {
            return false;
        }

        return hash_equals(self::SHA256, hash('sha256', $canonicalContent));
    }

    private static function NormalizeLineEndings(string $content): string
    {
        return str_replace("\r\n", "\n", $content);
    }
}
