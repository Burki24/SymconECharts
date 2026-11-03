<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use stdClass;

require_once __DIR__ . '/EChartsImportJson.php';

/** Validates the actual producer's 1.0 envelope, identities, references and inert assets. */
final class EChartsJSLivePackage
{
    public const FAMILIES = [
        'SymconJSLiveChart'       => ['cartesian-history', '{4713B9C2-22C8-7A45-060C-8C678DE05CC6}'],
        'SymconJSLiveDoughnutPie' => ['pie', '{9419245E-CE2E-F949-AAB6-714E2045632F}'],
        'SymconJSLiveGauge'       => ['gauge', '{71B93700-9659-97C6-AD83-984C2B44139F}'],
        'SymconJSLiveRadarChart'  => ['radar-history', '{95C8F306-4E51-949E-E25B-FD5C1F173295}'],
        'SymconJSLiveProgressbar' => ['progress', '{934051F5-EE82-953D-5241-A29D74CBC251}']
    ];

    public static function Read(string $json): stdClass
    {
        $package = EChartsImportJson::Decode($json);
        self::Fields($package, ['format', 'formatVersion', 'exportId', 'createdAt', 'producer', 'sourceInstallation', 'charts', 'assets', 'issues', 'extensions']);
        if ($package->format !== 'de.burki24.jslive-echarts' || $package->formatVersion !== '1.0') {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: only de.burki24.jslive-echarts 1.0 is supported.');
        }
        self::UUID($package->exportId);
        if (!is_string($package->createdAt) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.[0-9]+)?(?:Z|\+00:00)$/D', $package->createdAt) !== 1) {
            self::Invalid('Invalid UTC export timestamp.');
        }
        $utc = substr($package->createdAt, 0, 19) . 'Z';
        $created = strtotime($utc);
        if ($created === false || gmdate('Y-m-d\TH:i:s\Z', $created) !== $utc) {
            self::Invalid('Invalid UTC export timestamp.');
        }
        self::Fields($package->producer, ['libraryId', 'libraryVersion', 'build', 'exporterVersion', 'symconVersion', 'phpVersion']);
        if ($package->producer->libraryId !== '{1189E095-5FC2-22A5-12DF-2B177A36DB8C}') {
            self::Invalid('Unexpected producer library identity.');
        }
        foreach (['libraryVersion', 'exporterVersion', 'symconVersion', 'phpVersion'] as $key) {
            self::Text($package->producer->{$key});
        }
        self::Integer($package->producer->build, false);
        self::Fields($package->sourceInstallation, ['id']);
        self::UUID($package->sourceInstallation->id);
        self::Object($package->extensions);
        self::List($package->charts, 100);
        if ($package->charts === []) {
            self::Invalid('A package must contain at least one chart.');
        }
        self::List($package->assets, 512);
        self::Issues($package->issues);
        $assetIDs = [];
        $totalBytes = 0;
        foreach ($package->assets as $asset) {
            self::Fields($asset, ['assetId', 'kind', 'mediaType', 'byteLength', 'sha256', 'contentBase64', 'origin']);
            foreach (['assetId', 'kind', 'mediaType', 'sha256', 'contentBase64'] as $key) {
                self::Text($asset->{$key});
            }
            self::Object($asset->origin);
            if (!is_string($asset->origin->license ?? null)) {
                self::Invalid('Asset license provenance is missing.');
            }
            self::Integer($asset->byteLength, false);
            if ($asset->byteLength > 1048576 || ($totalBytes += $asset->byteLength) > 8388608) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: resource byte budget exceeded.');
            }
            $bytes = base64_decode($asset->contentBase64, true);
            if ($bytes === false || base64_encode($bytes) !== $asset->contentBase64
                || strlen($bytes) !== $asset->byteLength || hash('sha256', $bytes) !== $asset->sha256
                || $asset->assetId !== 'sha256:' . $asset->sha256 || isset($assetIDs[$asset->assetId])) {
                self::Invalid('Resource encoding, length, SHA-256 or identity is invalid.');
            }
            $assetIDs[$asset->assetId] = true;
            unset($bytes);
        }
        $chartIDs = [];
        $bindings = 0;
        foreach ($package->charts as $chart) {
            self::Fields($chart, ['chartKey', 'origin', 'original', 'sources', 'bindings', 'axes', 'time', 'appearance', 'assetRefs', 'coverage', 'reference']);
            self::Fields($chart->origin, ['instanceId', 'moduleId', 'moduleName', 'instanceName', 'family', 'templateVariant']);
            self::Integer($chart->origin->instanceId);
            foreach (['moduleId', 'moduleName', 'instanceName', 'family', 'templateVariant'] as $key) {
                self::Text($chart->origin->{$key});
            }
            $family = self::FAMILIES[$chart->origin->moduleName] ?? null;
            if ($family === null || [$chart->origin->family, $chart->origin->moduleId] !== $family) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: chart family and module identity do not match a supported producer.');
            }
            if (!is_string($chart->chartKey) || $chart->chartKey !== 'instance:' . $chart->origin->instanceId || isset($chartIDs[$chart->chartKey])) {
                self::Invalid('Duplicate or inconsistent chart identity.');
            }
            $chartIDs[$chart->chartKey] = true;
            self::Fields($chart->original, ['configuration', 'globalConfiguration', 'runtimeState', 'excluded']);
            foreach (['configuration', 'globalConfiguration', 'runtimeState'] as $key) {
                self::Object($chart->original->{$key});
            }
            $configuration = $chart->original->configuration;
            if ($chart->origin->templateVariant !== ($configuration->template ?? $configuration->type ?? $configuration->shape_preset ?? 'standard')) {
                self::Invalid('Template variant differs from its original configuration.');
            }
            if (strlen(EChartsImportJson::Canonical($chart->original->configuration)) > 524288) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: original chart configuration exceeds 512 KiB.');
            }
            self::List($chart->original->excluded, 1000);
            foreach ($chart->original->excluded as $excluded) {
                self::Fields($excluded, ['pointer', 'reason']);
                self::Text($excluded->pointer);
                self::Text($excluded->reason);
                if (!str_starts_with($excluded->pointer, '/') || $excluded->reason === '') {
                    self::Invalid('Invalid operational exclusion declaration.');
                }
            }
            self::Object($chart->appearance);
            if (!isset($chart->appearance->settings, $chart->appearance->global)
                || EChartsImportJson::Canonical($chart->appearance->settings) !== EChartsImportJson::Canonical($chart->original->configuration)
                || EChartsImportJson::Canonical($chart->appearance->global) !== EChartsImportJson::Canonical($chart->original->globalConfiguration)) {
                self::Invalid('Resolved configuration copies differ from the original.');
            }
            self::List($chart->axes, 3200);
            foreach ($chart->axes as $axis) {
                self::Object($axis);
            }
            self::List($chart->sources, 3200);
            self::List($chart->bindings, 3200);
            if (($bindings += count($chart->bindings)) > 3200) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: package exceeds 3200 bindings.');
            }
            $sourceIDs = [];
            foreach ($chart->sources as $source) {
                self::Fields($source, ['sourceKey', 'variableId', 'variableType', 'label', 'presentation', 'archive']);
                self::Integer($source->variableId);
                if (!is_int($source->variableType) || !in_array($source->variableType, [0, 1, 2, 3], true)
                    || !is_string($source->sourceKey) || $source->sourceKey !== 'variable:' . $source->variableId || isset($sourceIDs[$source->sourceKey])) {
                    self::Invalid('Invalid or duplicate variable identity/type.');
                }
                self::Text($source->label);
                self::Object($source->presentation);
                self::Fields($source->archive, ['archiveId', 'logging', 'aggregationType', 'counterSettings', 'availability']);
                if ($source->archive->archiveId !== null) {
                    self::Integer($source->archive->archiveId);
                }
                if (!in_array($source->archive->logging, [true, false, null], true)
                    || !in_array($source->archive->aggregationType, [0, 1, null], true)
                    || !in_array($source->archive->availability, ['missing', 'metadata-only', 'unresolved'], true)) {
                    self::Invalid('Invalid archive metadata types.');
                }
                if ($source->archive->counterSettings !== null) {
                    self::Fields($source->archive->counterSettings, ['ignoreZeros']);
                    if (!is_bool($source->archive->counterSettings->ignoreZeros)) {
                        self::Invalid('Invalid counter settings.');
                    }
                }
                $sourceIDs[$source->sourceKey] = true;
            }
            $bindingIDs = [];
            foreach ($chart->bindings as $binding) {
                self::Fields($binding, ['bindingKey', 'sourceKey', 'role', 'enabled', 'settings', 'appearance', 'originalPointer']);
                self::Text($binding->originalPointer);
                if (!str_starts_with($binding->originalPointer, '/') || !is_bool($binding->enabled)
                    || !in_array($binding->role, ['value', 'slice', 'reference'], true)
                    || $binding->bindingKey !== $chart->chartKey . '#' . $binding->originalPointer
                    || isset($bindingIDs[$binding->bindingKey])
                    || ($binding->sourceKey !== null && (!is_string($binding->sourceKey) || !isset($sourceIDs[$binding->sourceKey])))) {
                    self::Invalid('Invalid binding identity, type or source reference.');
                }
                self::Object($binding->settings);
                self::Object($binding->appearance);
                $original = self::At($chart->original->configuration, $binding->originalPointer);
                $originalID = $original instanceof stdClass ? ($original->Variable ?? null) : $original;
                if ($binding->sourceKey !== null && (!is_int($originalID) || 'variable:' . $originalID !== $binding->sourceKey)) {
                    self::Invalid('Binding source differs from its original configuration pointer.');
                }
                $settings = $original;
                if ($binding->originalPointer === '/Variable') {
                    $settings = $chart->original->configuration;
                } elseif ($binding->role === 'reference' && str_ends_with($binding->originalPointer, '/Reference')) {
                    $settings = self::At($chart->original->configuration, substr($binding->originalPointer, 0, -10));
                }
                self::Object($settings);
                foreach (get_object_vars($settings) as $key => $value) {
                    if (!property_exists($binding->settings, $key) || EChartsImportJson::Canonical($binding->settings->{$key}) !== EChartsImportJson::Canonical($value)) {
                        self::Invalid('Binding settings differ from their original configuration.');
                    }
                }
                $enabled = $chart->origin->family !== 'cartesian-history' || ($settings->Axes ?? '') !== '';
                if ($binding->enabled !== $enabled) {
                    self::Invalid('Binding visibility differs from the original row.');
                }
                $bindingIDs[$binding->bindingKey] = $binding->sourceKey;
            }
            if ($chart->time !== null) {
                self::Fields($chart->time, ['timezone', 'selection', 'resolvedWindow', 'rulesVersion', 'queries']);
                if (!is_string($chart->time->timezone) || !in_array($chart->time->timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
                    self::Invalid('Invalid source timezone.');
                }
                self::Object($chart->time->selection);
                self::Text($chart->time->rulesVersion);
                self::Fields($chart->time->resolvedWindow, ['fromInclusive', 'untilExclusive']);
                self::Window($chart->time->resolvedWindow);
                self::List($chart->time->queries, 3200);
                $queryIDs = [];
                foreach ($chart->time->queries as $query) {
                    self::Fields($query, ['bindingKey', 'sourceKey', 'fromInclusive', 'untilExclusive', 'displayOffsetSeconds', 'aggregationLevel', 'processing', 'rulesVersion', 'originalRules']);
                    if (!is_string($query->bindingKey) || !array_key_exists($query->bindingKey, $bindingIDs) || isset($queryIDs[$query->bindingKey])
                        || $query->sourceKey !== $bindingIDs[$query->bindingKey]) {
                        self::Invalid('Invalid or duplicate archive query reference.');
                    }
                    self::Window($query);
                    self::Object($query->processing);
                    self::Object($query->originalRules);
                    self::Text($query->rulesVersion);
                    if (!is_int($query->displayOffsetSeconds) || !is_int($query->aggregationLevel)) {
                        self::Invalid('Invalid time/query numeric types.');
                    }
                    $queryIDs[$query->bindingKey] = true;
                }
                if (array_diff_key($bindingIDs, $queryIDs) !== []) {
                    self::Invalid('Archive queries do not cover every binding.');
                }
            } elseif (in_array($chart->origin->family, ['cartesian-history', 'radar-history'], true)) {
                self::Invalid('Historical chart is missing its time contract.');
            }
            self::List($chart->assetRefs, 512);
            if (count(array_unique($chart->assetRefs, SORT_REGULAR)) !== count($chart->assetRefs)) {
                self::Invalid('Duplicate chart resource reference.');
            }
            foreach ($chart->assetRefs as $ref) {
                if (!is_string($ref) || !isset($assetIDs[$ref])) {
                    self::Invalid('Unresolved chart resource reference.');
                }
            }
            self::List($chart->coverage, 100000);
            $coverage = [];
            foreach ($chart->coverage as $field) {
                self::Fields($field, ['pointer', 'status', 'target', 'rule', 'reason']);
                foreach (['pointer', 'status', 'rule', 'reason'] as $key) {
                    self::Text($field->{$key});
                }
                if (!in_array($field->status, ['exact', 'approximate', 'unsupported', 'not-applicable'], true)
                    || !is_string($field->target) && $field->target !== null || isset($coverage[$field->pointer])) {
                    self::Invalid('Invalid or duplicate export field coverage.');
                }
                $coverage[$field->pointer] = true;
            }
            foreach (get_object_vars($chart->original->configuration) as $key => $_) {
                if (!isset($coverage['/' . EChartsImportJson::Pointer($key)])) {
                    self::Invalid('Export coverage omits an original configuration field.');
                }
            }
        }
        foreach ($package->issues as $issue) {
            if (!isset($chartIDs[$issue->chartKey])) {
                self::Invalid('Producer issue references an unknown chart.');
            }
        }
        return $package;
    }

    public static function ArrayValue(mixed $value): mixed
    {
        if ($value instanceof stdClass || is_array($value)) {
            return array_map(self::ArrayValue(...), (array) $value);
        }
        return $value;
    }

    /** Resolve pointers through original JSON property strings without executing any contents. */
    public static function At(mixed $value, string $pointer): mixed
    {
        if (!str_starts_with($pointer, '/') || preg_match('/~(?![01])/', $pointer) === 1) {
            self::Invalid('Invalid original JSON pointer.');
        }
        foreach (explode('/', substr($pointer, 1)) as $part) {
            if (is_string($value)) {
                $value = EChartsImportJson::Decode($value);
            }
            $key = str_replace(['~1', '~0'], ['/', '~'], $part);
            if ($value instanceof stdClass && property_exists($value, $key)) {
                $value = $value->{$key};
            } elseif (is_array($value) && ctype_digit($key) && (string) (int) $key === $key && array_key_exists((int) $key, $value)) {
                $value = $value[(int) $key];
            } else {
                self::Invalid('Original JSON pointer cannot be resolved.');
            }
        }
        return $value;
    }

    /** Match the producer's map/list boundary, without changing canonical JSON globally. */
    public static function Wire(mixed $value, string $key = ''): mixed
    {
        if (is_array($value)) {
            if ($value === [] && in_array($key, ['parameters', 'profiles', 'templates', 'extra', 'configuration', 'globalConfiguration', 'runtimeState'], true)) {
                return new stdClass();
            }
            foreach ($value as $name => $part) {
                $value[$name] = self::Wire($part, (string) $name);
            }
        }
        return $value;
    }

    private static function Fields(mixed $value, array $keys): void
    {
        self::Object($value);
        $actual = array_keys(get_object_vars($value));
        sort($actual, SORT_STRING);
        sort($keys, SORT_STRING);
        if ($actual !== $keys) {
            self::Invalid('Required object fields are missing or unknown.');
        }
    }

    private static function Object(mixed $value): void
    {
        if (!$value instanceof stdClass) {
            self::Invalid('Expected a JSON object.');
        }
    }

    private static function List(mixed $value, int $maximum): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            self::Invalid('Expected a JSON list.');
        }
        if (count($value) > $maximum) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: list exceeds its structural budget.');
        }
    }

    private static function Text(mixed $value): void
    {
        if (!is_string($value)) {
            self::Invalid('Expected a text field.');
        }
    }

    private static function Integer(mixed $value, bool $positive = true): void
    {
        if (!is_int($value) || $value < ($positive ? 1 : 0)) {
            self::Invalid('Expected a valid integer identity or length.');
        }
    }

    private static function UUID(mixed $value): void
    {
        if (!is_string($value) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di', $value) !== 1) {
            self::Invalid('Invalid installation/export UUID.');
        }
    }

    private static function Window(stdClass $value): void
    {
        if (!is_int($value->fromInclusive) || !is_int($value->untilExclusive) || $value->fromInclusive >= $value->untilExclusive) {
            self::Invalid('Invalid half-open historical window.');
        }
    }

    private static function Issues(mixed $issues): void
    {
        self::List($issues, 10000);
        foreach ($issues as $issue) {
            self::Fields($issue, ['code', 'severity', 'chartKey', 'pointer', 'message', 'effect', 'resolution']);
            foreach (get_object_vars($issue) as $text) {
                self::Text($text);
            }
            if (!in_array($issue->severity, ['info', 'warning', 'error'], true)) {
                self::Invalid('Invalid producer issue severity.');
            }
        }
    }

    private static function Invalid(string $message): never
    {
        throw new InvalidArgumentException('PACKAGE_INVALID: ' . $message);
    }
}
