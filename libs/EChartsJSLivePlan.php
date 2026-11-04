<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use InvalidArgumentException;
use stdClass;
use Throwable;

require_once __DIR__ . '/EChartsJSLiveRendering.php';

/** Pure, bounded plan compiler. The caller supplies only read capabilities; target properties are never user input. */
final class EChartsJSLivePlan
{
    public const RULE_VERSION = 'jslive-confirmed-import-1.0.0';
    public const MODULES = ['EChartsGaugeSingle', 'EChartsPie', 'EChartsTimeSeries', 'EChartsBarHistory', 'EChartsRadarHistory', 'EChartsProgress'];

    public static function Modules(): array
    {
        $result = [];
        foreach (self::MODULES as $name) {
            $module = json_decode(file_get_contents(dirname(__DIR__) . '/' . $name . '/module.json'), true, 16, JSON_THROW_ON_ERROR);
            $result[$name] = ['moduleId' => $module['id'], 'prefix' => $module['prefix']];
        }
        return $result;
    }

    public static function Prepare(string $json, array $mapping, array $context, Closure $source, Closure $read, ?string $installationID, int $now): array
    {
        if (!is_int($context['gatewayID'] ?? null) || $context['gatewayID'] < 1 || !is_int($context['parentID'] ?? null) || $context['parentID'] < 0) {
            throw new InvalidArgumentException('PACKAGE_INVALID: an existing gateway and target category are required.');
        }
        $package = EChartsJSLivePackage::Read($json);
        $preflight = EChartsJSLiveImport::Analyze($json, $mapping, $source, $installationID);
        $modules = self::Modules();
        $plan = ['format'      => 'de.burki24.symconecharts.import-plan', 'formatVersion' => '1.0', 'ruleVersion' => self::RULE_VERSION,
            'packageHash'      => $preflight['packageHash'], 'mapping' => $mapping, 'context' => $context,
            'sameInstallation' => $preflight['sameInstallation'], 'charts' => [], 'creationEnabled' => true,
            'sourceGuard'      => [], 'originGuard' => [], 'ruleFingerprint' => self::RuleFingerprint(), 'issues' => $preflight['issues']];
        $reportBytes = strlen(EChartsImportJson::Canonical($plan));
        foreach ($package->charts as $index => $chart) {
            $plan['originGuard'][] = ['instanceID' => $chart->origin->instanceId, 'moduleID' => $chart->origin->moduleId,
                'name'                             => $chart->origin->instanceName,
                'configuration'                    => array_map(EChartsImportJson::Hash(...), get_object_vars($chart->original->configuration)),
                'global'                           => array_map(EChartsImportJson::Hash(...), get_object_vars($chart->original->globalConfiguration)),
                'controls'                         => array_map(
                    static fn (stdClass $control): array => ['id' => $control->variableId, 'hash' => EChartsImportJson::Hash($control->value)],
                    get_object_vars($chart->original->runtimeState)
                )];
            $pre = $preflight['charts'][$index];
            $row = ['chartKey' => $chart->chartKey, 'name' => $chart->origin->instanceName, 'family' => $chart->origin->family,
                'identity'     => strtolower($package->sourceInstallation->id) . ':' . $chart->origin->instanceId,
                'revision'     => '', 'blocked' => true, 'target' => null, 'differences' => [], 'differenceDetails' => [],
                'issues'       => [], 'fieldCoverage' => $pre['fieldCoverage'], 'metadataCoverage' => $pre['metadataCoverage'], 'verdicts' => $pre['verdicts'],
                'reference'    => ['exportId' => $package->exportId, 'createdAt' => $package->createdAt,
                    'window'                  => $chart->time->resolvedWindow ?? null, 'output' => 'Native ECharts; no source template is executed.']];
            $row['presentationComparisons'] = array_map(static fn (array $source): array => [
                'sourceKey'  => $source['sourceKey'],
                'comparison' => $source['presentationComparison'] ?? ['status' => 'unverified', 'normalizations' => []]
            ], $pre['sources']);
            try {
                if ($plan['issues'] !== []) {
                    throw new InvalidArgumentException('FORMAT_UNSUPPORTED: unresolved package extensions.');
                }
                foreach ($package->issues as $issue) {
                    if ($issue->chartKey === '' || $issue->chartKey === $chart->chartKey) {
                        throw new InvalidArgumentException('IMPORT_BLOCKED: the exporter reports an unresolved problem.');
                    }
                }
                $model = EChartsJSLiveModel::Compile($chart, $pre, $package->assets);
                $name = EChartsJSLiveRendering::Target($model);
                $configuration = self::Configuration($model, $chart);
                $row['target'] = ['module' => $name, 'moduleId' => $modules[$name]['moduleId'], 'name' => $chart->origin->instanceName,
                    'parentID'             => $context['parentID'], 'gatewayID' => $context['gatewayID'], 'configuration' => $configuration];
                $row['revision'] = EChartsImportJson::Hash(['rules' => self::RULE_VERSION, 'identity' => $row['identity'], 'target' => $row['target']]);
                foreach ($model['sources'] as $sourceKey => $item) {
                    $key = $item['variableID'] . ':' . ($item['archiveID'] ?? 'none');
                    $plan['sourceGuard'][$key] = ['variableID' => $item['variableID'], 'archiveID' => $item['archiveID'], 'fingerprint' => $item['fingerprint']];
                }
                self::Coverage($row, $chart, $model, $pre);
                // All reads share the same family planner and budgets as the eventual target. No synthetic preview data.
                $state = EChartsJSLiveRendering::State($model, $read, $now, self::Design($configuration));
                foreach ([false, true] as $ipsView) {
                    EChartsJSLivePage::Render($state, $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58]);
                }
                $row['verdicts'] = ['sourcesArchive' => 'exact', 'presentations' => 'exact', 'design' => 'approximate'];
                $row['blocked'] = false;
            } catch (Throwable $exception) {
                $code = strstr($exception->getMessage(), ':', true);
                $known = true;
                if (!in_array($code, ['FORMAT_UNSUPPORTED', 'SOURCE_UNRESOLVED', 'PRESENTATION_UNSUPPORTED', 'CALCULATION_UNSUPPORTED',
                    'CUSTOM_CODE_INERT', 'ASSET_UNAVAILABLE', 'LIMIT_EXCEEDED', 'PACKAGE_INVALID', 'IMPORT_BLOCKED', 'SOURCE_CHANGED', 'ARCHIVE_UNAVAILABLE'], true)) {
                    $code = 'SNAPSHOT_UNAVAILABLE';
                    $known = false;
                }
                $row['issues'][] = ['code' => $code, 'message' => $known ? mb_substr($exception->getMessage(), 0, 1000)
                    : 'The source, archive, resource or native model could not be fully verified. No target will be created.'];
                $row['sourceIssues'] = array_values(array_filter($pre['issues'], static fn (array $issue): bool => in_array(
                    $issue['code'],
                    ['SOURCE_CHANGED', 'SOURCE_UNRESOLVED', 'ARCHIVE_UNAVAILABLE', 'PRESENTATION_UNSUPPORTED', 'CUSTOM_CODE_INERT', 'ASSET_UNAVAILABLE'],
                    true
                )));
            }
            $plan['charts'][$chart->chartKey] = $row;
            $reportBytes += strlen(EChartsImportJson::Canonical($row));
            if ($reportBytes > 8388608) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: complete plan exceeds 8 MiB; review smaller packages without dropping chart fields.');
            }
            // Release the old read-only preview ledger as each native chart replaces it. Do not retain two batch reports.
            unset($preflight['charts'][$index], $pre);
        }
        $plan['planHash'] = EChartsImportJson::Hash($plan);
        if (strlen(EChartsImportJson::Canonical($plan)) > 8388608) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: complete plan exceeds 8 MiB.');
        }
        return $plan;
    }

    public static function RuleFingerprint(): string
    {
        $files = array_merge(
            glob(__DIR__ . '/EChartsJSLive*.php'),
            glob(__DIR__ . '/jslive/*.json'),
            glob(__DIR__ . '/jslive/*.js'),
            glob(__DIR__ . '/jslive/*.css'),
            glob(__DIR__ . '/jslive/*.html'),
            glob(dirname(__DIR__) . '/*/JSLive*.php'),
            glob(dirname(__DIR__) . '/*/JSLive*.js'),
            [__DIR__ . '/EChartsAsset.php', __DIR__ . '/EChartsStartupState.php', __DIR__ . '/helper/manifest.json',
                dirname(__DIR__) . '/EChartsProgress/ProgressData.php', dirname(__DIR__) . '/EChartsProgress/ProgressRenderer.js',
                dirname(__DIR__) . '/EChartsRadarHistory/RadarHistoryData.php', dirname(__DIR__) . '/EChartsRadarHistory/RadarHistoryRenderer.js']
        );
        sort($files, SORT_STRING);
        return EChartsImportJson::Hash(array_map(static fn (string $file): string => hash_file('sha256', $file), $files));
    }

    /** Fresh validation under the lock without duplicating the potentially large field ledger in memory. */
    public static function Recheck(array $plan, Closure $read, int $now): array
    {
        if (!hash_equals($plan['ruleFingerprint'], self::RuleFingerprint())) {
            throw new InvalidArgumentException('PLAN_CHANGED: import rules changed.');
        }
        foreach ($plan['charts'] as $chart) {
            if ($chart['blocked']) {
                continue;
            }
            $configuration = $chart['target']['configuration'];
            $model = EChartsJSLiveModel::Read($configuration['JSLiveImportModel'], $chart['family']);
            $state = EChartsJSLiveRendering::State($model, $read, $now, self::Design($configuration));
            foreach ([false, true] as $ipsView) {
                EChartsJSLivePage::Render($state, $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58]);
            }
        }
        return $plan;
    }

    public static function Design(array $configuration): array
    {
        return ['title'   => $configuration['Title'], 'background' => $configuration['JSLiveBackgroundColor'], 'fontSizePercent' => 100,
            'showTitle'   => $configuration['JSLiveShowTitle'], 'showLegend' => $configuration['JSLiveShowLegend'],
            'showTooltip' => $configuration['JSLiveShowTooltip'], 'animationEnabled' => $configuration['JSLiveAnimationEnabled']];
    }

    private static function Configuration(array $model, stdClass $chart): array
    {
        $c = $model['settings'];
        $configuration = ['JSLiveImportModel' => EChartsImportJson::Canonical($model), 'Title' => $c['title_text'] ?? '',
            'EnableIPSView'                   => (bool) ($c['CreateIPSView'] ?? $model['global']['CreateIPSView'] ?? false), 'IPSViewUseTileDesign' => true];
        foreach (['', 'IPSView'] as $prefix) {
            foreach (['BackgroundColor' => -1, 'FontSizePercent' => 100, 'ShowTitle' => $c['title_display'] ?? false,
                'ShowLegend'            => $c['legend_display'] ?? false, 'ShowTooltip' => $c['tooltips_enabled'] ?? true, 'AnimationEnabled' => true] as $key => $value) {
                $configuration[$prefix . 'JSLive' . $key] = $value;
            }
        }
        foreach (['Period' => 'Period', 'Relativ' => 'Relative', 'Now' => 'Now', 'StartDate' => 'StartDate'] as $source => $target) {
            if (isset($model['time']['selection'][$source])) {
                $configuration['JSLive' . $target] = $model['time']['selection'][$source]['value'];
            }
        }
        return $configuration;
    }

    private static function Difference(array &$row, string $pointer, mixed $original, string $effect, string $rule): string
    {
        $id = substr(EChartsImportJson::Hash([$row['chartKey'], $pointer, $original, $effect, $rule]), 0, 32);
        if (!isset($row['differenceDetails'][$id])) {
            $row['differences'][] = $id;
            $row['differenceDetails'][$id] = ['id' => $id, 'pointer' => $pointer, 'original' => $original, 'effect' => $effect, 'rule' => $rule];
        }
        return $id;
    }

    private static function Coverage(array &$row, stdClass $chart, array $model, array $pre): void
    {
        $rules = json_decode(file_get_contents(__DIR__ . '/jslive/coverage.json'), true, 32, JSON_THROW_ON_ERROR);
        $family = $model['family'];
        $fields = [];
        $metadata = [];
        foreach ($pre['fieldCoverage'] as $field) {
            $pointer = $field['pointer'];
            $field['status'] = 'not-applicable';
            $field['target'] = null;
            $field['rule'] = 'source-context-1';
            $field['reason'] = 'Source-only operational context is retained in the export; it does not configure or modify the original object.';
            if (str_starts_with($pointer, '/original/configuration/')) {
                $key = substr($pointer, strlen('/original/configuration/'));
                $value = $chart->original->configuration->{$key};
                $rule = $rules[$family][$key] ?? $rules['common'][$key] ?? null;
                if (isset($field['elementPointer'])) {
                    $parts = array_values(array_filter(explode('/', ltrim($field['elementPointer'], '/')), static fn (string $part): bool => $part !== '' && !ctype_digit($part)));
                    $nestedKey = $key . ($parts === [] ? '' : '/' . implode('/', $parts));
                    if ($parts !== []) {
                        $rule = $rules['nested'][$family][$nestedKey] ?? null;
                    }
                    $nested = is_string($value) ? EChartsImportJson::Decode($value) : $value;
                    foreach ($field['elementPointer'] === '' ? [] : explode('/', ltrim($field['elementPointer'], '/')) as $part) {
                        $decoded = str_replace(['~1', '~0'], ['/', '~'], $part);
                        $nested = $nested instanceof stdClass ? $nested->{$decoded} : $nested[(int) $decoded];
                    }
                    $value = $nested;
                    $pointer .= '#' . $field['elementPointer'];
                }
                if ($rule === null) {
                    throw new InvalidArgumentException('FORMAT_UNSUPPORTED: original setting has no audited mapping rule: ' . $key . ($field['elementPointer'] ?? '') . '.');
                }
                $field['status'] = $rule['status'];
                $field['rule'] = $rule['rule'];
                $field['reason'] = $rule['effect'];
                if ($field['status'] !== 'not-applicable') {
                    $field['target'] = '/target/configuration/JSLiveImportModel/settings/' . $key . ($field['elementPointer'] ?? '');
                }
                if ($field['status'] === 'approximate') {
                    $field['differenceID'] = self::Difference($row, $pointer, $value, $rule['effect'], $rule['rule']);
                }
            } elseif (str_starts_with($pointer, '/original/runtimeState/')) {
                $field['status'] = 'approximate';
                $field['rule'] = 'independent-time-controls-1';
                $field['reason'] = 'Period/Relative and, for Cartesian charts, Now/StartDate initialize independent target properties. Offset remains provenance; its already adjusted StartDate is retained. Existing control variables and dashboard controls are not linked or modified.';
                $field['differenceID'] = self::Difference($row, '/original/runtimeState', $chart->original->runtimeState, $field['reason'], $field['rule']);
            } elseif (str_starts_with($pointer, '/original/globalConfiguration/')) {
                $field['status'] = 'approximate';
                $field['rule'] = 'native-output-1';
                $field['reason'] = 'Native tile and IPSView HTML replace the JSLive web hook, viewport and output wrappers. RefreshTime is bounded to 1–300 seconds; original output objects remain unchanged.';
                $field['differenceID'] = self::Difference($row, '/original/globalConfiguration', $chart->original->globalConfiguration, $field['reason'], $field['rule']);
            }
            $fields[] = $field;
        }
        foreach ($pre['metadataCoverage'] as $field) {
            $pointer = $field['pointer'];
            $field['status'] = 'not-applicable';
            $field['target'] = null;
            $field['rule'] = 'inert-provenance-1';
            $field['reason'] = 'Provenance and comparison snapshot remain in the export. No source profile, action, template or archived value is installed or executed.';
            $target = self::MetadataTarget($pointer, $chart, $model);
            if ($target !== null) {
                $field['status'] = 'exact';
                $field['target'] = '/target/configuration/JSLiveImportModel' . $target;
                $field['rule'] = 'frozen-source-metadata-1';
                $field['reason'] = 'Verified IDs and profile-independent native formatting are retained; source and archive settings are not written.';
            } elseif (str_starts_with($pointer, '/time/')) {
                $field['status'] = 'approximate';
                $field['rule'] = 'legacy-initial-query-native-refresh-1';
                $field['reason'] = 'Initial archive windows, rounding, offsets and reducers follow versioned JSLive rules. Native refresh reloads bounded archive snapshots instead of reproducing browser-side incremental averages; running interval values may differ until archived.';
                $field['differenceID'] = self::Difference($row, '/time', $model['time'], $field['reason'], $field['rule']);
            }
            $metadata[] = $field;
        }
        $row['fieldCoverage'] = $fields;
        $row['metadataCoverage'] = $metadata;
        self::Difference(
            $row,
            '/appearance/native-layout',
            $chart->appearance,
            'ECharts lays out fonts, label collisions, ticks, tooltips, animation and responsive shapes independently. Compare both downloadable target HTML previews; pixel identity is not promised.',
            'native-geometry-1'
        );
        self::Difference(
            $row,
            '/appearance/background-context',
            null,
            'The pinned source templates declare no page background. The native target starts transparent and inherits its tile/IPSView container; a standalone preview uses its browser canvas. Choose an explicit background in the target designer when the original embedding used an opaque canvas.',
            'native-parent-background-1'
        );
        if ($family === 'progress' && $model['settings']['shape_preset'] === 'text' && $model['settings']['Type'] === 'stroke'
            && $model['settings']['shape_path'] === '' && $model['settings']['shape_svg'] === '') {
            self::Difference(
                $row,
                '/original/configuration/Type',
                'stroke',
                'The text preset is rendered as fillable native text. A source image/text preset without a stroke path cannot supply equivalent stroke progress; the native target makes its progress visible.',
                'native-text-preset-1'
            );
        }
        if ($family === 'progress') {
            $settings = $model['settings'];
            $image = $settings['shape_svg'] !== '' || ($settings['shape_path'] === '' && $settings['shape_preset'] === 'text');
            $activePaint = $image ? null : ($settings['Type'] === 'stroke' ? 'stroke' : 'fill');
            foreach ($model['progressPaints'] ?? [] as $key => $paint) {
                if ($key === $activePaint && $paint['kind'] === 'gradient' && $paint['duration'] > 0) {
                    self::Difference(
                        $row,
                        '/appearance/paint/' . $key,
                        $paint['duration'],
                        'The validated palette and direction move cyclically with the original duration using a bounded native canvas. Each document has its own start phase; browser scheduling and disabled target animations can differ from the source.',
                        'native-progress-cyclic-gradient-1'
                    );
                }
            }
            if ($activePaint === 'fill' && $settings['fill_backgroundFile'] !== '' && $settings['override_fill'] === '') {
                self::Difference(
                    $row,
                    '/appearance/svg-fill-route',
                    null,
                    'The source template supplies an extensionless relative getFillImg route which loading-bar can treat as an invalid paint and display black. The target uses the validated embedded SVG locally and may therefore show the intended image instead of that source defect.',
                    'native-progress-svg-route-1'
                );
            }
        }
    }

    private static function MetadataTarget(string $pointer, stdClass $chart, array $model): ?string
    {
        $target = null;
        $presentation = null;
        $type = null;
        $base = null;
        $rest = null;
        if (preg_match('~^/sources/([0-9]+)/(.*)$~', $pointer, $m) === 1) {
            $source = $chart->sources[(int) $m[1]];
            $rest = $m[2];
            $base = '/sources/' . EChartsImportJson::Pointer($source->sourceKey);
            $target = $base . '/' . match ($rest) {
                'variableId' => 'variableID', 'sourceKey' => '', 'archive/archiveId' => 'archiveID', default => $rest
            };
            $presentation = $source->presentation;
            $type = $source->variableType;
        } elseif (preg_match('~^/bindings/([0-9]+)/(.*)$~', $pointer, $m) === 1) {
            $index = (int) $m[1];
            $binding = $chart->bindings[$index];
            $rest = $m[2];
            $base = '/bindings/' . $index;
            $target = $base . '/' . match ($rest) {
                'bindingKey' => 'key', default => $rest
            };
            $presentation = $binding->settings->presentation ?? null;
            $type = $model['sources'][$binding->sourceKey]['variableType'];
            if (str_starts_with($rest, 'settings/presentation/')) {
                $rest = substr($rest, strlen('settings/'));
            }
        } elseif (preg_match('~^/axes/([0-9]+)/(.*)$~', $pointer, $m) === 1) {
            $base = '/axes/' . $m[1];
            $rest = $m[2];
            $target = $base . '/' . $rest;
        }
        if ($base !== null && $rest !== null && str_starts_with($rest, 'presentation/')) {
            $suffix = substr($rest, strlen('presentation/'));
            $target = $base . '/' . $suffix;
            if (str_starts_with($suffix, 'original/') && $presentation instanceof stdClass && $type !== null) {
                foreach (EChartsJSLiveImport::Presentation($presentation, $type)['conversion']['fieldCoverage'] as $conversion) {
                    if ('/' . $suffix === $conversion['source'] || str_starts_with('/' . $suffix, $conversion['source'] . '/')) {
                        $target = $base . $conversion['target'];
                        break;
                    }
                }
            }
        }
        if ($target === null || str_contains($target, '/original/') || str_ends_with($target, '/Profile')) {
            return null;
        }
        $value = $model;
        foreach (explode('/', ltrim(rtrim($target, '/'), '/')) as $part) {
            $key = str_replace(['~1', '~0'], ['/', '~'], $part);
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return rtrim($target, '/');
    }
}
