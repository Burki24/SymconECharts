<?php

declare(strict_types=1);

namespace eCharts;

/** Adapt a single string in the kernel's Migrate envelope, preserving any parent migration. */
final class EChartsPersistenceMigration
{
    public static function UpdateString(string $persistence, string $inherited, string $section, string $field, callable $upgrade): string
    {
        $state = json_decode($inherited !== '' ? $inherited : $persistence);
        $stored = $state->{$section}->{$field} ?? null;
        $updated = is_string($stored) ? $upgrade($stored) : null;
        if ($updated === null || $updated === $stored) {
            return $inherited;
        }
        $state->{$section}->{$field} = $updated;
        return json_encode($state, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
