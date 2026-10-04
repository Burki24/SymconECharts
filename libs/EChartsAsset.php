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
        if (!hash_equals(self::SHA256, hash('sha256', $content))) {
            throw new RuntimeException('The bundled Apache ECharts runtime failed its integrity check.');
        }

        return $content;
    }
}
