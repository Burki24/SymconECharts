<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use stdClass;

require_once __DIR__ . '/EChartsJSLiveImport.php';
require_once __DIR__ . '/EChartsJSLiveTime.php';
require_once __DIR__ . '/EChartsSvgImage.php';
require_once __DIR__ . '/EChartsSvgPath.php';
require_once __DIR__ . '/EChartsJSLivePath.php';
require_once __DIR__ . '/../EChartsProgress/JSLivePaint.php';
require_once __DIR__ . '/../EChartsProgress/ProgressData.php';

/** Typed, frozen, profile-independent inputs to the family-owned ECharts adapters. No arbitrary options/code. */
final class EChartsJSLiveModel
{
    public const VERSION = '1.0.0';
    public const MAX_BYTES = 524288;

    public static function Read(string $json, string $family): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: imported model exceeds 512 KiB.');
        }
        $object = EChartsImportJson::Decode($json);
        if (!$object instanceof stdClass || ($object->version ?? null) !== self::VERSION || ($object->family ?? null) !== $family) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: incompatible imported model.');
        }
        $model = EChartsJSLivePackage::ArrayValue($object);
        if (!is_array($model['settings'] ?? null) || !is_array($model['sources'] ?? null) || !is_array($model['bindings'] ?? null)
            || count($model['sources']) > 64 || count($model['bindings']) > 100 || !is_array($model['resources'] ?? null)) {
            throw new InvalidArgumentException('PACKAGE_INVALID: invalid imported model structure.');
        }
        $sourceModule = match ($family) {
            'cartesian-history' => 'SymconJSLiveChart', 'pie' => 'SymconJSLiveDoughnutPie',
            'gauge'             => 'SymconJSLiveGauge', 'radar-history' => 'SymconJSLiveRadarChart', 'progress' => 'SymconJSLiveProgressbar'
        };
        $schema = json_decode(file_get_contents(__DIR__ . '/jslive/properties.json'), true, 32, JSON_THROW_ON_ERROR)[$sourceModule];
        $listSchema = json_decode(file_get_contents(__DIR__ . '/jslive/lists.json'), true, 32, JSON_THROW_ON_ERROR)[$sourceModule];
        if (array_diff(array_keys($schema), array_keys($model['settings'])) !== [] || array_diff(array_keys($model['settings']), array_keys($schema)) !== []) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: incomplete imported settings contract.');
        }
        foreach ($schema as $key => $type) {
            $value = $model['settings'][$key];
            $valid = in_array($key, ['Datasets', 'Axes', 'Ticks', 'Highlights', 'customScale', 'xaxes_override_list'], true)
                ? is_array($value) && array_is_list($value) && count($value) <= 100
                : ($key === 'shape_svg' && is_array($value) ? true : match ($type) {
                    'string' => is_string($value), 'boolean' => is_bool($value), 'integer' => is_int($value),
                    'float'  => (is_int($value) || is_float($value)) && is_finite((float) $value)
                });
            if (!$valid) {
                throw new InvalidArgumentException('PACKAGE_INVALID: invalid imported setting ' . $key . '.');
            }
            if (isset($listSchema[$key])) {
                self::Rows($value, $listSchema[$key], $key);
            }
        }
        foreach ($model['sources'] as $sourceKey => $source) {
            if (!is_int($source['variableID'] ?? null) || $source['variableID'] < 1 || !in_array($source['variableType'] ?? null, [0, 1, 2], true)) {
                throw new InvalidArgumentException('SOURCE_UNRESOLVED: invalid imported source.');
            }
            self::Native($source['native'], $source['variableType']);
            if (!is_string($sourceKey) || preg_match('/^variable:[1-9][0-9]*$/D', $sourceKey) !== 1
                || (!is_int($source['archiveID'] ?? null) && ($source['archiveID'] ?? null) !== null)
                || !is_string($source['label'] ?? null) || !is_array($source['effective'] ?? null)) {
                throw new InvalidArgumentException('PACKAGE_INVALID: invalid frozen source identity or metadata.');
            }
        }
        if (!array_is_list($model['bindings']) || !is_array($model['axes'] ?? null) || !array_is_list($model['axes']) || count($model['axes']) > 16) {
            throw new InvalidArgumentException('PACKAGE_INVALID: invalid frozen bindings or axes.');
        }
        $keys = [];
        foreach ($model['bindings'] as $binding) {
            $source = $model['sources'][$binding['sourceKey']] ?? null;
            if ($source === null || $binding['variableID'] !== $source['variableID']) {
                throw new InvalidArgumentException('SOURCE_UNRESOLVED: imported binding differs from its source.');
            }
            self::Native($binding['native'], $source['variableType']);
            if (!is_string($binding['key'] ?? null) || isset($keys[$binding['key']]) || !is_bool($binding['enabled'] ?? null)
                || !is_array($binding['settings'] ?? null) || !is_array($binding['effective'] ?? null)) {
                throw new InvalidArgumentException('PACKAGE_INVALID: invalid frozen binding.');
            }
            $keys[$binding['key']] = true;
        }
        if (!is_array($model['global'] ?? null) || array_diff(array_keys($model['global']), ['CreateIPSView', 'DataMode', 'EnableViewport', 'RefreshTime', 'viewport_content']) !== []) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown frozen global setting.');
        }
        if ($model['time'] !== null) {
            EChartsJSLiveTime::Window($model['time'], $family, 1793448000);
        }
        $fonts = json_decode(file_get_contents(__DIR__ . '/jslive/fonts.json'), true, 16, JSON_THROW_ON_ERROR);
        foreach ($model['resources'] as $resource) {
            $font = $fonts[$resource['sha256'] ?? ''] ?? null;
            $bytes = base64_decode($resource['data'] ?? '', true);
            if (($resource['kind'] ?? '') !== 'font' || $font === null || $bytes === false || hash('sha256', $bytes) !== $resource['sha256']
                || $resource['family'] !== $font['family'] || $resource['license'] !== $font['license']) {
                throw new InvalidArgumentException('ASSET_UNAVAILABLE: imported font integrity/license differs.');
            }
        }
        if ($family === 'progress' && is_array($model['settings']['shape_svg'] ?? null)) {
            $model['settings']['shape_svg'] = EChartsSvgImage::Import($model['settings']['shape_svg']['dataUri']);
        }
        self::ValidateFamily($family, $model['settings'], $model['bindings'], $model['axes']);
        if ($family === 'progress') {
            if (isset($model['progressLabel']) && (!is_array($model['progressLabel'])
                || array_diff(array_keys($model['progressLabel']), ['prefix', 'suffix']) !== []
                || !is_string($model['progressLabel']['prefix'] ?? null) || !is_string($model['progressLabel']['suffix'] ?? null))) {
                throw new InvalidArgumentException('PACKAGE_INVALID: invalid frozen progress label.');
            }
            $paints = JSLiveProgressPaint::Build($model['settings']);
            if (isset($model['progressPaints']) && EChartsImportJson::Canonical($model['progressPaints']) !== EChartsImportJson::Canonical($paints)) {
                throw new InvalidArgumentException('PACKAGE_INVALID: frozen paints differ from their validated settings.');
            }
            if ($paints !== []) {
                $model['progressPaints'] = $paints;
            }
        }
        if (strlen(EChartsImportJson::Canonical($model)) > self::MAX_BYTES) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: imported paint model exceeds 512 KiB.');
        }
        return $model;
    }

    public static function Compile(stdClass $chart, array $preflight, array $assets): array
    {
        if ($preflight['verdicts']['sourcesArchive'] !== 'exact' || $preflight['verdicts']['presentations'] !== 'exact') {
            throw new InvalidArgumentException('SOURCE_UNRESOLVED: source/archive/presentation preflight is not exact.');
        }
        $schema = json_decode(file_get_contents(__DIR__ . '/jslive/properties.json'), true, 32, JSON_THROW_ON_ERROR)[$chart->origin->moduleName];
        $settings = EChartsJSLivePackage::ArrayValue($chart->original->configuration);
        if (array_diff(array_keys($schema), array_keys($settings)) !== []) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: the exporter omitted required original properties.');
        }
        foreach ($settings as $name => $value) {
            $type = $schema[$name] ?? null;
            $valid = match ($type) {
                'string' => is_string($value), 'boolean' => is_bool($value), 'integer' => is_int($value),
                'float'  => (is_float($value) || is_int($value)) && is_finite((float) $value), default => false
            };
            if (!$valid) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown or incorrectly typed original property ' . $name . '.');
            }
            if (str_contains(strtolower($name), 'color') && !str_contains(strtolower($name), 'alpha') && is_int($value) && ($value < -1 || $value > 0xFFFFFF)) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid original color ' . $name . '.');
            }
        }
        if (($settings['TemplateScriptID'] ?? 0) !== 0) {
            throw new InvalidArgumentException('CUSTOM_CODE_INERT: custom templates require separate review; no template is executed.');
        }
        $family = $chart->origin->family;
        $limit = match ($family) {
            'gauge', 'progress' => 1, 'pie' => 100, default => 32
        };
        $values = array_filter($chart->bindings, static fn (stdClass $binding): bool => $binding->role !== 'reference');
        if ($values === [] || count($values) > $limit || count($chart->bindings) > ($family === 'radar-history' ? 64 : $limit)) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: family binding budget exceeded.');
        }
        $sourceMap = [];
        $sources = [];
        foreach ($chart->sources as $index => $source) {
            $row = $preflight['sources'][$index];
            if ($row['status'] !== 'verified-metadata' || !in_array($source->variableType, $family === 'cartesian-history' ? [0, 1, 2] : [1, 2], true)) {
                throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: incompatible source type.');
            }
            $normalized = EChartsJSLiveImport::Presentation($source->presentation, $source->variableType);
            $sourceMap[$source->variableId] = $row['variableID'];
            $sources[$source->sourceKey] = ['variableID' => $row['variableID'], 'variableType' => $source->variableType,
                'archiveID'                              => $row['archiveID'], 'archive' => EChartsJSLivePackage::ArrayValue($source->archive),
                'native'                                 => $normalized['native'], 'effective' => $normalized['effective'], 'label' => $source->label,
                'fingerprint'                            => $row['currentFingerprint']];
        }
        $bindings = [];
        foreach ($chart->bindings as $index => $binding) {
            $source = $sources[$binding->sourceKey];
            $format = ($binding->settings->presentation ?? null) instanceof stdClass
                ? EChartsJSLiveImport::Presentation($binding->settings->presentation, $source['variableType']) : null;
            $row = EChartsJSLivePackage::ArrayValue($binding->settings);
            unset($row['presentation'], $row['Profile']);
            $bindings[] = ['key' => $binding->bindingKey, 'sourceKey' => $binding->sourceKey, 'variableID' => $source['variableID'],
                'role'           => $binding->role, 'enabled' => $binding->enabled, 'settings' => self::Rebind($row, $sourceMap),
                'native'         => $format['native'] ?? $source['native'], 'effective' => $format['effective'] ?? $source['effective']];
        }
        $listSchemas = json_decode(file_get_contents(__DIR__ . '/jslive/lists.json'), true, 32, JSON_THROW_ON_ERROR)[$chart->origin->moduleName];
        foreach (['Datasets', 'Axes', 'Ticks', 'Highlights', 'customScale', 'xaxes_override_list'] as $name) {
            if (isset($settings[$name])) {
                $rows = EChartsImportJson::Decode($settings[$name]);
                if (!is_array($rows) || !array_is_list($rows) || count($rows) > 100) {
                    throw new InvalidArgumentException('LIMIT_EXCEEDED: invalid or oversized original list ' . $name . '.');
                }
                self::Rows(EChartsJSLivePackage::ArrayValue($rows), $listSchemas[$name] ?? [], $name);
                $settings[$name] = self::Rebind(EChartsJSLivePackage::ArrayValue($rows), $sourceMap);
            }
        }
        if (isset($settings['Variable'])) {
            $settings['Variable'] = $sourceMap[$settings['Variable']];
        }
        $axes = [];
        foreach ($chart->axes as $axis) {
            $row = EChartsJSLivePackage::ArrayValue($axis);
            $presentation = $axis->presentation ?? null;
            unset($row['Profile'], $row['presentation']);
            if ($presentation instanceof stdClass) {
                // Axis profiles have already been independently validated by preflight.
                $row['native'] = EChartsJSLivePackage::ArrayValue($presentation->native);
                $row['effective'] = EChartsJSLivePackage::ArrayValue($presentation->effective);
            }
            $axes[] = $row;
        }
        if (count($axes) > 16) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: at most 16 original axes are supported.');
        }
        self::ValidateFamily($family, $settings, $bindings, $axes);
        $resources = [];
        $standardTemplate = match ($family) {
            'cartesian-history' => 'Chart', 'pie' => 'Doughnut-PIE',
            'radar-history'     => 'RadarChart', 'progress' => 'Progressbar', 'gauge' => $settings['template']
        };
        $templates = json_decode(file_get_contents(__DIR__ . '/jslive/templates.json'), true, 16, JSON_THROW_ON_ERROR);
        $templateVerified = false;
        foreach ($assets as $asset) {
            if (!in_array($asset->assetId, $chart->assetRefs, true)) {
                continue;
            }
            if ($asset->kind === 'font') {
                $fonts = json_decode(file_get_contents(__DIR__ . '/jslive/fonts.json'), true, 16, JSON_THROW_ON_ERROR);
                $font = $fonts[$asset->sha256] ?? null;
                if ($font === null || $asset->mediaType !== 'font/woff2') {
                    throw new InvalidArgumentException('ASSET_UNAVAILABLE: font content/license has no pinned trusted mapping.');
                }
                $resources[] = ['kind' => 'font', 'family' => $font['family'], 'license' => $font['license'],
                    'sha256'           => $asset->sha256, 'data' => $asset->contentBase64];
            } elseif ($asset->kind === 'inert-standard-template') {
                if (($asset->origin->template ?? '') !== $standardTemplate || !in_array($asset->sha256, $templates[$standardTemplate]['sha256'], true)) {
                    throw new InvalidArgumentException('CUSTOM_CODE_INERT: changed or unknown standard template has no validated native adapter.');
                }
                $templateVerified = true;
            } elseif ($asset->kind === 'inert-font-css') {
                $knownCSS = array_merge(...array_column($fonts ?? json_decode(file_get_contents(__DIR__ . '/jslive/fonts.json'), true, 16, JSON_THROW_ON_ERROR), 'cssSha256'));
                if (!in_array($asset->sha256, $knownCSS, true)) {
                    throw new InvalidArgumentException('ASSET_UNAVAILABLE: unrecognized font CSS remains inert.');
                }
            } elseif ($asset->kind !== 'shape') {
                throw new InvalidArgumentException('CUSTOM_CODE_INERT: unsupported resource stays inert.');
            }
        }
        if (!$templateVerified) {
            throw new InvalidArgumentException('ASSET_UNAVAILABLE: the standard template reference was not included or verified.');
        }
        $requiredFonts = array_values(array_unique(array_filter(array_values(array_filter($settings, static fn (mixed $value, string $key): bool => str_ends_with($key, '_fontFamily') && is_string($value) && $value !== '', ARRAY_FILTER_USE_BOTH)))));
        if (array_diff($requiredFonts, array_column($resources, 'family')) !== []) {
            throw new InvalidArgumentException('ASSET_UNAVAILABLE: a configured font has no verified embedded resource.');
        }
        $time = $chart->time === null ? null : EChartsJSLivePackage::ArrayValue($chart->time);
        if ($time !== null) {
            if ($time['rulesVersion'] !== 'jslive-' . $family . '-1') {
                throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: unknown archive rules.');
            }
            foreach ($time['selection'] as &$control) {
                unset($control['variableId']);
            }
            unset($control);
            EChartsJSLiveTime::Window($time, $family, 1793448000);
            $bindingLookup = array_column($chart->bindings, null, 'bindingKey');
            $sourceLookup = array_column($chart->sources, null, 'sourceKey');
            foreach ($chart->time->queries as $query) {
                $binding = $bindingLookup[$query->bindingKey];
                $source = $sourceLookup[$query->sourceKey];
                $period = $time['selection']['Period']['value'];
                $raw = $family === 'cartesian-history' ? ($binding->settings->HighRes ?? 7) <= $period : $period >= 6;
                $counter = $source->archive->aggregationType === 1;
                $expected = ['mode'    => $raw ? 'raw' : 'aggregated', 'valueField' => $raw ? 'Value' : 'Avg', 'counter' => $counter,
                    'rawCounterRule'   => $counter && $raw ? 'absolute-adjacent-difference-current-value-boundary-v1' : null,
                    'precision'        => $counter && $family === 'cartesian-history' ? 5 : ($settings['data_precision'] ?? 2),
                    'decimation'       => $raw && $family === 'cartesian-history' && $period !== 7 ? ($settings['data_highResSteps'] ?? 1) : 1,
                    'missingData'      => $family === 'cartesian-history' ? 'legacy-current-value-fallback-unless-boolean' : 'legacy-zero-initialized-buckets',
                    'runningIntervals' => 'jslive-includes-current-interval-with-browser-updates',
                    'radarMode'        => $family === 'radar-history' ? ($binding->settings->Mode ?? null) : null,
                    'radarReference'   => $family === 'radar-history' ? ($binding->settings->Reference ?? 0) : null,
                    'radarReducer'     => $family === 'radar-history' ? 'legacy-reference-count-or-last-assigned-bucket-value-v1' : null,
                    'boundary'         => 'inclusive-kernel-range-adapted-to-exclusive-plan', 'timestampUnit' => 'seconds'];
                if (EChartsImportJson::Canonical($expected) !== EChartsImportJson::Canonical($query->processing)) {
                    throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: archive processing differs from its versioned original rule.');
                }
            }
            // Resolved windows are volatile comparison data, not the identity of a rolling configuration.
            $time = array_intersect_key($time, array_flip(['timezone', 'selection', 'rulesVersion']));
        }
        $model = ['version' => self::VERSION, 'family' => $family, 'settings' => $settings, 'sources' => $sources,
            'bindings'      => $bindings, 'axes' => $axes, 'time' => $time, 'resources' => $resources,
            'global'        => EChartsJSLivePackage::ArrayValue($chart->original->globalConfiguration)];
        if ($family === 'progress') {
            $model['progressLabel'] = ProgressData::SourceLabel(EChartsJSLivePackage::ArrayValue($chart->sources[0]->presentation->original));
        }
        if ($family === 'progress' && ($paints = JSLiveProgressPaint::Build($settings)) !== []) {
            $model['progressPaints'] = $paints;
        }
        if (strlen(EChartsImportJson::Canonical($model)) > self::MAX_BYTES) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: frozen family model exceeds 512 KiB.');
        }
        self::Read(EChartsImportJson::Canonical($model), $family);
        return $model;
    }

    private static function Native(array $native, int $type): void
    {
        if (isset($native['parameters']['PROFILE'])) {
            throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: imported formatting must be profile independent.');
        }
        $normalized = \Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper::Normalize($type, ['PRESENTATION' => $native['presentationId']] + $native['parameters']);
        if ($normalized['conversion']['status'] !== 'exact' || EChartsImportJson::Canonical($normalized['native']) !== EChartsImportJson::Canonical($native)
            || ($native['parameters']['DIGITS'] ?? 0) > 20) {
            throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: invalid or unrepresentable frozen native formatting.');
        }
        $p = $native['parameters'];
        if (($p['PERCENTAGE'] ?? false) && (!isset($p['MIN'], $p['MAX']) || (float) $p['MIN'] === (float) $p['MAX'])) {
            throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: percentage formatting requires a known nonempty source range.');
        }
    }

    private static function Rebind(array $row, array $mapping): array
    {
        foreach ($row as $key => $value) {
            if (in_array($key, ['Variable', 'Reference'], true)) {
                if (!is_int($value) || $value < 0) {
                    throw new InvalidArgumentException('SOURCE_UNRESOLVED: source IDs must be nonnegative integers.');
                }
                if ($value > 0) {
                    $row[$key] = $mapping[$value] ?? throw new InvalidArgumentException('SOURCE_UNRESOLVED: unmapped nested source.');
                }
            } elseif ($key === 'Profile') {
                $row[$key] = ''; // Source profiles are frozen as native metadata, never resolved by name in the target.
            } elseif (is_array($value)) {
                $row[$key] = self::Rebind($value, $mapping);
            }
        }
        return $row;
    }

    private static function Rows(array $rows, array $schema, string $pointer): void
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: invalid original row ' . $pointer . '.');
            }
            foreach ($row as $key => $value) {
                $type = match (true) {
                    is_bool($value)   => 'boolean', is_int($value), is_float($value) => 'number',
                    is_string($value) => 'string', is_array($value) => 'array', default => 'invalid'
                };
                if (!in_array($type, $schema[$key]['types'] ?? [], true)) {
                    throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown or mistyped original list field ' . $pointer . '/' . $key . '.');
                }
                if (is_array($value)) {
                    if (!array_is_list($value) || count($value) > 100) {
                        throw new InvalidArgumentException('LIMIT_EXCEEDED: nested list is too large.');
                    }
                    self::Rows($value, $schema[$key]['rows'] ?? [], $pointer . '/' . $index . '/' . $key);
                }
            }
        }
    }

    private static function ValidateFamily(string $family, array &$settings, array $bindings, array $axes): void
    {
        $values = array_filter($bindings, static fn (array $binding): bool => $binding['role'] !== 'reference');
        $limit = match ($family) {
            'gauge', 'progress' => 1, 'pie' => 100, default => 32
        };
        if ($values === [] || count($values) > $limit || count($bindings) > ($family === 'radar-history' ? 64 : $limit)) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: frozen family binding budget exceeded.');
        }
        foreach ($settings as $key => $value) {
            if (is_int($value) && preg_match('/(?:_fontSize|_width|_trailWidth|_backgroundExtrude|_radius)$/D', $key) === 1 && ($value < 0 || $value > 10000)) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: graphical size exceeds the bounded native import geometry.');
            }
        }
        if (in_array($family, ['gauge', 'progress'], true)) {
            $min = $settings[$family === 'gauge' ? 'min' : 'data_min'] ?? null;
            $max = $settings[$family === 'gauge' ? 'max' : 'data_max'] ?? null;
            if ((!is_float($min) && !is_int($min)) || (!is_float($max) && !is_int($max))
                || !EChartsValueRange::IsValid(min($min, $max), max($min, $max))) {
                throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: a finite nonempty scale is required.');
            }
        }
        if ($family === 'gauge' && !in_array($settings['template'], ['CanvasGauges-Radial', 'CanvasGauges-Linear', 'CanvasGauges-Linear(vertical)', 'CanvasGauges-Compass'], true)) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown gauge geometry.');
        }
        if ($family === 'gauge' && ($settings['min'] >= $settings['max'] || $settings['ticks_minorTicks'] < 0 || $settings['ticks_minorTicks'] > 32
            || (count($settings['Ticks']) + 2) * ($settings['ticks_minorTicks'] + 2) > 4096)) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: invalid gauge scale or excessive tick geometry.');
        }
        if ($family === 'progress') {
            if (!in_array($settings['Type'], ['stroke', 'fill'], true) || !in_array($settings['shape_preset'], ['bubble', 'circle', 'fan', 'line', 'rainbow', 'energy', 'stripe', 'text'], true)) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown progress geometry.');
            }
            foreach (['stroke_dir' => ['normal', 'reverse'], 'stroke_lincap' => ['', 'butt', 'round', 'square'],
                'fill_dir'         => ['ltr', 'rtl', 'btt', 'ttb'], 'style_fontPosition' => ['center', 'bottom']] as $key => $choices) {
                if (!in_array($settings[$key], $choices, true)) {
                    throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown progress direction or layout.');
                }
            }
            if ($settings['data_precision'] < 0 || $settings['data_precisionCustom'] < 0) {
                throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: negative progress precision steps are not supported.');
            }
            if (is_string($settings['shape_svg']) && $settings['shape_svg'] !== '') {
                $settings['shape_svg'] = EChartsSvgImage::Import($settings['shape_svg']);
            }
            if ($settings['shape_path'] !== '') {
                EChartsJSLivePath::Validate($settings['shape_path']);
            }
        }
        if ($family === 'cartesian-history') {
            $idents = array_column($axes, 'Ident');
            if (count($idents) !== count(array_unique($idents))) {
                throw new InvalidArgumentException('PACKAGE_INVALID: duplicate axis identity.');
            }
            foreach ($bindings as $binding) {
                $row = $binding['settings'];
                if (!in_array($row['Type'] ?? null, ['line', 'bar'], true)
                    || ($binding['enabled'] && !in_array($row['Axes'] ?? null, $idents, true))) {
                    throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: unresolved Cartesian series/axis model.');
                }
            }
            if (($settings['data_highResSteps'] ?? 0) < 1 || $settings['data_highResSteps'] > 10000) {
                throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: invalid original raw decimation.');
            }
        }
        foreach ($bindings as $binding) {
            $point = $binding['settings']['PointStyle'] ?? '';
            if (!is_string($point) || !in_array($point, ['', 'circle', 'cross', 'crossRot', 'dash', 'line', 'rect', 'rectRounded', 'rectRot', 'star', 'triangle'], true)) {
                throw new InvalidArgumentException('FORMAT_UNSUPPORTED: custom point resources have no validated adapter.');
            }
        }
        if (isset($settings['points_Style']) && !in_array($settings['points_Style'], ['', 'circle', 'cross', 'crossRot', 'dash', 'line', 'rect', 'rectRounded', 'rectRot', 'star', 'triangle'], true)) {
            throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unknown global point style.');
        }
        if ($family === 'pie' && !in_array($settings['type'], ['pie', 'doughnut'], true)) {
            throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: unknown Pie model.');
        }
        if ($family === 'radar-history' && (($settings['data_sections'] ?? 0) < 1 || $settings['data_sections'] > 100)) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: Radar classes must be between 1 and 100.');
        }
        if ($family === 'radar-history' && !EChartsValueRange::IsValid($settings['data_minValue'], $settings['data_maxValue'])) {
            throw new InvalidArgumentException('CALCULATION_UNSUPPORTED: invalid historical Radar class range.');
        }
        if ($family === 'radar-history') {
            $references = array_column(array_filter($bindings, static fn (array $binding): bool => $binding['role'] === 'reference'), 'variableID');
            foreach ($values as $binding) {
                $id = $binding['settings']['Reference'];
                if (!is_int($id) || $id < 0 || ($id > 0 && !in_array($id, $references, true))) {
                    throw new InvalidArgumentException('SOURCE_UNRESOLVED: historical Radar reference has no explicit source binding.');
                }
            }
        }
    }
}
