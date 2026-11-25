<?php

declare(strict_types=1);

namespace eCharts;

final class EChartsIdentity
{
    // Assigned to the published library; version 1 derived its document identifiers from this ID.
    public const STORE_BUNDLE_ID = 'de.burki24.symconecharts';

    /** Version 1 used the bundle's product segment in PascalCase in persistent ownership seals. */
    public static function LegacyProductName(): string
    {
        $product = substr(self::STORE_BUNDLE_ID, strrpos(self::STORE_BUNDLE_ID, '.') + 1);
        $renderer = 'ECharts';
        return ucfirst(substr($product, 0, -strlen($renderer))) . $renderer;
    }
}
