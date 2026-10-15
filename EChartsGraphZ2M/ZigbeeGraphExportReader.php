<?php

declare(strict_types=1);

namespace SymconECharts;

use DirectoryIterator;
use RuntimeException;

/** Reads the latest server-side Network Map JSON export without a module return-value transfer. */
final class ZigbeeGraphExportReader
{
    private const MAX_BYTES = 16777216;

    /** @return array<string,mixed> */
    public static function ReadLatest(string $kernelDirectory): array
    {
        $directory = rtrim($kernelDirectory, '\\/') . DIRECTORY_SEPARATOR . 'user'
            . DIRECTORY_SEPARATOR . 'IPSZigbee2MQTT' . DIRECTORY_SEPARATOR . 'networkmaps';
        if (!is_dir($directory)) {
            throw new RuntimeException('No exported Zigbee network map is available.');
        }

        $latest = '';
        foreach (new DirectoryIterator($directory) as $entry) {
            $filename = $entry->getFilename();
            if (!$entry->isFile() || $entry->isLink()
                || preg_match('/^zigbee-network-map-[0-9]{8}-[0-9]{6}\.json$/D', $filename) !== 1) {
                continue;
            }
            if ($latest === '' || strcmp($filename, $latest) > 0) {
                $latest = $filename;
            }
        }
        if ($latest === '') {
            throw new RuntimeException('No exported Zigbee network map is available.');
        }

        $path = $directory . DIRECTORY_SEPARATOR . $latest;
        if (is_link($path)) {
            throw new RuntimeException('The exported Zigbee network map could not be read.');
        }
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The exported Zigbee network map could not be read.');
        }
        try {
            if (!flock($handle, LOCK_SH)) {
                throw new RuntimeException('The exported Zigbee network map could not be read.');
            }
            $stat = fstat($handle);
            if (!is_array($stat) || $stat['size'] < 1 || $stat['size'] > self::MAX_BYTES) {
                throw new RuntimeException('The exported Zigbee network map is empty or too large.');
            }
            $raw = stream_get_contents($handle, self::MAX_BYTES + 1);
            if (!is_string($raw) || strlen($raw) < 1 || strlen($raw) > self::MAX_BYTES) {
                throw new RuntimeException('The exported Zigbee network map is empty or too large.');
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $topology = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($topology)) {
            throw new RuntimeException('The exported Zigbee network map is invalid.');
        }
        return $topology;
    }
}
