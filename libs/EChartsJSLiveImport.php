<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper;
use InvalidArgumentException;
use stdClass;
use Throwable;

require_once __DIR__ . '/EChartsJSLivePackage.php';
require_once __DIR__ . '/helper/VariablePresentationNormalizationHelper.php';
require_once __DIR__ . '/EChartsValueRange.php';

/** Read-only, versioned JSLive import preflight; never creates a Symcon object. */
final class EChartsJSLiveImport
{
    public const RULE_VERSION = 'jslive-import-preview-1.0.0';

    /** The reader is a read-only metadata adapter; this class has no kernel write capability. */
    public static function Analyze(string $json, array $options, callable $reader, ?string $installationID): array
    {
        $package = EChartsJSLivePackage::Read($json);
        if (array_diff(array_keys($options), ['sourceMapping', 'archiveMapping']) !== []) {
            throw new InvalidArgumentException('PACKAGE_INVALID: unknown preview option.');
        }
        foreach (['sourceMapping', 'archiveMapping'] as $name) {
            if (array_key_exists($name, $options) && !is_array($options[$name])) {
                throw new InvalidArgumentException('PACKAGE_INVALID: invalid source/archive mapping.');
            }
            foreach ($options[$name] ?? [] as $key => $id) {
                $prefix = $name === 'sourceMapping' ? 'variable' : 'archive';
                if (!is_string($key) || preg_match('/^' . $prefix . ':[1-9][0-9]*$/D', $key) !== 1 || !is_int($id) || $id <= 0) {
                    throw new InvalidArgumentException('PACKAGE_INVALID: mappings require string keys and positive integer IDs.');
                }
            }
        }
        $known = ['sourceMapping' => [], 'archiveMapping' => []];
        foreach ($package->charts as $chart) {
            foreach ($chart->sources as $source) {
                $known['sourceMapping'][$source->sourceKey] = true;
                if ($source->archive->archiveId !== null) {
                    $known['archiveMapping']['archive:' . $source->archive->archiveId] = true;
                }
            }
        }
        foreach ($known as $name => $keys) {
            if (array_diff_key($options[$name] ?? [], $keys) !== []) {
                throw new InvalidArgumentException('PACKAGE_INVALID: mapping references an unknown source or archive.');
            }
        }
        $revision = clone $package;
        unset($revision->exportId, $revision->createdAt);
        $revision->charts = array_map(static function (stdClass $chart): stdClass
        {
            $copy = clone $chart;
            unset($copy->reference);
            return $copy;
        }, $package->charts);
        $sameInstallation = $installationID !== null && strcasecmp($package->sourceInstallation->id, $installationID) === 0;
        $report = ['format'   => 'de.burki24.symconecharts.import-preview', 'formatVersion' => '1.0',
            'ruleVersion'     => self::RULE_VERSION, 'packageHash' => EChartsImportJson::Hash($package),
            'revisionHash'    => EChartsImportJson::Hash($revision), 'sameInstallation' => $sameInstallation,
            'exportSnapshot'  => ['exportId' => $package->exportId, 'createdAt' => $package->createdAt,
                'producer'                   => $package->producer, 'sourceInstallation' => $package->sourceInstallation,
                'issues'                     => $package->issues, 'extensions' => $package->extensions],
            'mapping'         => (object) array_map(static fn (array $values): object => (object) $values, $options),
            'creationEnabled' => false, 'charts' => [], 'assets' => [], 'issues' => [],
            'analysisLimits'  => ['jsonNodes' => 100000, 'reportBytes' => 8388608],
            'summary'         => ['charts' => count($package->charts), 'fields' => 0, 'bindings' => 0, 'exact' => 0, 'approximate' => 0, 'unsupported' => 0, 'not-applicable' => 0]];
        if (get_object_vars($package->extensions) !== []) {
            $report['issues'][] = self::Issue('FORMAT_UNSUPPORTED', '', '/extensions', 'Unknown extensions cannot be ignored.', 'Use a package without unknown extensions or add a versioned rule.');
        }
        foreach ($package->assets as $asset) {
            $report['assets'][] = ['assetId' => $asset->assetId, 'kind' => $asset->kind, 'mediaType' => $asset->mediaType,
                'byteLength'                 => $asset->byteLength, 'sha256' => $asset->sha256, 'license' => $asset->origin->license,
                'origin'                     => $asset->origin, 'verified'                   => true, 'usage' => 'inert-reference-only'];
        }
        $cache = [];
        $reportBytes = strlen(EChartsImportJson::Canonical($report));
        foreach ($package->charts as $chart) {
            $result = self::Chart($chart, $package, $options, $sameInstallation, $reader, $cache);
            $reportBytes += strlen(EChartsImportJson::Canonical($result)) + 1;
            if ($reportBytes > 8388608) {
                throw new InvalidArgumentException('LIMIT_EXCEEDED: complete preview report exceeds 8 MiB.');
            }
            $report['charts'][] = $result;
            $report['summary']['bindings'] += count($chart->bindings);
            foreach (array_merge($result['fieldCoverage'], $result['metadataCoverage']) as $field) {
                ++$report['summary']['fields'];
                ++$report['summary'][$field['status']];
            }
        }
        $report['planHash'] = EChartsImportJson::Hash($report);
        if (strlen(EChartsImportJson::Canonical($report)) > 8388608) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: complete preview report exceeds 8 MiB.');
        }
        return $report;
    }

    public static function Presentation(stdClass $snapshot, int $type): array
    {
        if (($snapshot->conversion->status ?? null) !== 'exact' || !($snapshot->native ?? null) instanceof stdClass
            || !($snapshot->original ?? null) instanceof stdClass || !($snapshot->original->presentation ?? null) instanceof stdClass) {
            throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: incomplete presentation snapshot.');
        }
        $original = EChartsJSLivePackage::ArrayValue($snapshot->original);
        $parameters = $original['presentation'];
        if (isset($parameters['TEMPLATE'])) {
            $template = $original['templates'][$parameters['TEMPLATE']] ?? null;
            if (!is_array($template) || ($template['PresentationID'] ?? null) !== ($parameters['PRESENTATION'] ?? null)
                || !is_array($template['Values'] ?? null)) {
                throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: missing or incompatible native template.');
            }
            $parameters = array_replace($template['Values'], $parameters);
            unset($parameters['TEMPLATE']);
        }
        $profiles = $original['profiles'] ?? [];
        $profileIcon = $profiles[$parameters['PROFILE'] ?? '']['Icon'] ?? '';
        if (($parameters['ICON'] ?? $profileIcon) === '' && ($original['objectIcon'] ?? '') !== '') {
            $parameters['ICON'] = $original['objectIcon'];
        }
        $normalized = VariablePresentationNormalizationHelper::Normalize($type, $parameters, $profiles);
        if ($normalized['conversion']['status'] !== 'exact'
            || EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($normalized['native'])) !== EChartsImportJson::Canonical($snapshot->native)
            || EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($normalized['effective'])) !== EChartsImportJson::Canonical($snapshot->effective ?? null)) {
            throw new InvalidArgumentException('PRESENTATION_UNSUPPORTED: declared native/effective formatting differs from independent normalization.');
        }
        return $normalized;
    }

    private static function Chart(stdClass $chart, stdClass $package, array $options, bool $same, callable $reader, array &$cache): array
    {
        $config = (array) $chart->original->configuration;
        $target = self::Target($chart, $config);
        $result = ['chartKey'  => $chart->chartKey, 'name' => $chart->origin->instanceName, 'family' => $chart->origin->family,
            'target'           => $target, 'verdicts' => ['sourcesArchive' => 'exact', 'presentations' => 'exact', 'design' => 'unsupported'],
            'sources'          => [], 'issues' => [], 'fieldCoverage' => [], 'metadataCoverage' => [], 'status' => 'blocked',
            'originalSnapshot' => $chart,
            'previewKind'      => 'configuration-plan-only', 'creationEnabled' => false];
        $coverageBytes = strlen(EChartsImportJson::Canonical($chart));
        if ($target['module'] === null) {
            $result['issues'][] = self::Issue('CALCULATION_UNSUPPORTED', $chart->chartKey, '/origin/templateVariant', $target['reason'], 'Implement an equivalent target model before import.');
        }
        if ($chart->time !== null) {
            $result['issues'][] = self::Issue(
                'CALCULATION_UNSUPPORTED',
                $chart->chartKey,
                '/time',
                'Legacy running intervals, fallback values, offsets and reducers have no validated identical target rule yet.',
                'Keep the original historical chart; add and verify its exact query rule before import.'
            );
        }
        $sourceLookup = [];
        $mappedIDs = [];
        foreach ($chart->sources as $index => $source) {
            $pointer = '/sources/' . $index;
            $id = $options['sourceMapping'][$source->sourceKey] ?? ($same ? $source->variableId : null);
            $archive = $source->archive->archiveId;
            $archiveKey = $archive === null ? '' : 'archive:' . $archive;
            $mappedArchive = $archive === null ? null : ($options['archiveMapping'][$archiveKey] ?? ($same ? $archive : null));
            $row = ['sourceKey' => $source->sourceKey, 'originalVariableID' => $source->variableId,
                'variableID'    => $id, 'variableType' => $source->variableType, 'archiveKey' => $archiveKey, 'archiveID' => $mappedArchive,
                'label'         => $source->label, 'status' => 'blocked', 'native' => null, 'currentFingerprint' => null];
            try {
                if ($id === null || ($chart->time !== null && $mappedArchive === null)) {
                    throw new InvalidArgumentException('Explicit ID mapping is required for a foreign or unconfirmed installation.');
                }
                if (isset($mappedIDs[$id]) && $mappedIDs[$id] !== $source->sourceKey) {
                    throw new InvalidArgumentException('Different source identities must not be merged into one target variable.');
                }
                $mappedIDs[$id] = $source->sourceKey;
                $key = $id . ':' . ($mappedArchive ?? 0);
                if (!array_key_exists($key, $cache)) {
                    try {
                        $cache[$key] = $reader($id, $mappedArchive);
                    } catch (Throwable) {
                        $cache[$key] = null;
                    }
                }
                $current = $cache[$key];
                if (!is_array($current) || ($current['variableType'] ?? null) !== $source->variableType) {
                    throw new InvalidArgumentException('Source variable is missing, unreadable or has a different type.');
                }
                $row['currentFingerprint'] = EChartsImportJson::Hash($current);
                $normalized = self::Presentation($source->presentation, $source->variableType);
                $row['native'] = EChartsJSLivePackage::Wire($normalized['native']);
                if (EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($current['native'] ?? null)) !== EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($normalized['native']))) {
                    $result['issues'][] = self::Issue('SOURCE_CHANGED', $chart->chartKey, $pointer . '/presentation', 'Current native source metadata differs from the export snapshot.', 'Export again or review a fresh source mapping.');
                    $result['verdicts']['sourcesArchive'] = 'blocked';
                }
                foreach (['VariableAction' => 'variableAction', 'VariableCustomAction' => 'variableCustomAction'] as $exported => $actualKey) {
                    if (property_exists($source->presentation->original->variable ?? new stdClass(), $exported)
                        && $source->presentation->original->variable->{$exported} !== ($current[$actualKey] ?? null)) {
                        $result['issues'][] = self::Issue('SOURCE_CHANGED', $chart->chartKey, $pointer . '/presentation/original/variable/' . $exported, 'Current source action differs from the export snapshot.', 'Export again or review a fresh source mapping.');
                        $result['verdicts']['sourcesArchive'] = 'blocked';
                    }
                }
                if ($chart->time !== null) {
                    $expected = EChartsJSLivePackage::ArrayValue($source->archive);
                    $actual = $current['archive'] ?? null;
                    if (!is_array($actual) || ($actual['logging'] ?? null) !== true || $expected['logging'] !== true
                        || ($actual['aggregationType'] ?? null) !== $expected['aggregationType']
                        || ($actual['counterSettings'] ?? null) !== $expected['counterSettings']) {
                        $result['issues'][] = self::Issue('ARCHIVE_UNAVAILABLE', $chart->chartKey, $pointer . '/archive', 'Archive logging, type or counter policy is missing or differs.', 'Map a compatible existing archive; no archive is changed automatically.');
                        $result['verdicts']['sourcesArchive'] = 'blocked';
                    }
                }
                $row['status'] = 'verified-metadata';
                $sourceLookup[$source->sourceKey] = ['id' => $id, 'source' => $source, 'presentation' => $normalized];
            } catch (Throwable $exception) {
                $code = str_starts_with($exception->getMessage(), 'PRESENTATION_UNSUPPORTED:') ? 'PRESENTATION_UNSUPPORTED' : 'SOURCE_UNRESOLVED';
                $result['issues'][] = self::Issue($code, $chart->chartKey, $pointer, $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Source metadata could not be validated.', 'Resolve the source or presentation before import.');
                $result['verdicts'][$code === 'PRESENTATION_UNSUPPORTED' ? 'presentations' : 'sourcesArchive'] = 'blocked';
                $unverified = $code === 'PRESENTATION_UNSUPPORTED' ? 'sourcesArchive' : 'presentations';
                if ($result['verdicts'][$unverified] === 'exact') {
                    $result['verdicts'][$unverified] = 'not-verified';
                }
            }
            $result['sources'][] = $row;
        }
        if ($chart->sources === [] || $chart->bindings === []) {
            $result['issues'][] = self::Issue('SOURCE_UNRESOLVED', $chart->chartKey, '/bindings', 'The chart has no complete source/binding model.', 'Resolve the original configuration first.');
            $result['verdicts']['sourcesArchive'] = 'blocked';
            $result['verdicts']['presentations'] = 'not-verified';
        }
        $bindings = [];
        foreach ($chart->bindings as $index => $binding) {
            $item = $sourceLookup[$binding->sourceKey ?? ''] ?? null;
            if ($item === null) {
                $result['issues'][] = self::Issue('SOURCE_UNRESOLVED', $chart->chartKey, '/bindings/' . $index, 'A binding has no verified source mapping.', 'Resolve every binding, including hidden ones.');
                if ($result['verdicts']['presentations'] === 'exact') {
                    $result['verdicts']['presentations'] = 'not-verified';
                }
                continue;
            }
            $format = $item['presentation'];
            try {
                if (($binding->settings->presentation ?? null) !== null) {
                    $format = self::Presentation($binding->settings->presentation, $item['source']->variableType);
                }
            } catch (Throwable) {
                $format = null;
                $result['verdicts']['presentations'] = 'blocked';
                $result['issues'][] = self::Issue('PRESENTATION_UNSUPPORTED', $chart->chartKey, '/bindings/' . $index . '/settings/presentation', 'Binding-specific formatting is not completely normalized.', 'Resolve the binding profile; source formatting is not a substitute.');
            }
            $bindings[] = ['bindingKey' => $binding->bindingKey, 'variableID' => $item['id'], 'enabled' => $binding->enabled,
                'role'                  => $binding->role, 'native' => $format === null ? null : EChartsJSLivePackage::Wire($format['native']), 'settings' => $binding->settings];
        }
        $result['target']['bindings'] = $bindings;
        $result['target']['configuration'] = self::Configuration($chart, $config, $sourceLookup);
        $result['target']['partial'] = true;
        self::TargetBudget($chart, $result);
        $mapping = ['Variable' => ['SourceVariableID', 'identity'], 'title_text' => ['Title', 'text'], 'min' => ['Minimum', 'number'], 'max' => ['Maximum', 'number'],
            'precision'        => ['Decimals', 'digits'], 'data_min' => ['ScaleMinimum', 'number'], 'data_max' => ['ScaleMaximum', 'number']];
        foreach ($config as $key => $value) {
            $pointer = '/original/configuration/' . EChartsImportJson::Pointer($key);
            $rule = $mapping[$key] ?? null;
            $mapped = $rule !== null && array_key_exists($rule[0], $result['target']['configuration']);
            self::Record($result, 'fieldCoverage', self::Field(
                $pointer,
                $mapped ? 'exact' : 'unsupported',
                $mapped ? '/target/configuration/' . $rule[0] : null,
                $mapped ? 'direct-property-1' : 'unmapped-original-1',
                $mapped ? 'Typed direct property in the partial target draft.' : 'No validated target rule; retained in the original package, not silently defaulted.'
            ), $coverageBytes);
            if (is_array($value) || $value instanceof stdClass || in_array($key, ['Datasets', 'Axes', 'Ticks', 'Highlights', 'customScale'], true)) {
                try {
                    $decoded = is_string($value) ? EChartsImportJson::Decode($value) : $value;
                    foreach (self::Leaves($decoded, '') as $nested) {
                        $field = self::Field($pointer, 'unsupported', null, 'nested-original-1', 'Nested original setting needs its own target rule.');
                        foreach ($bindings as $bindingIndex => $bindingPlan) {
                            $bindingPointer = substr($bindingPlan['bindingKey'], strlen($chart->chartKey) + 1);
                            if ('/' . $key . $nested === $bindingPointer . '/Variable') {
                                $field = self::Field($pointer, 'exact', '/target/bindings/' . $bindingIndex . '/variableID', 'binding-identity-1', 'The original variable ID is retained in the binding plan; no source value is changed.');
                            }
                        }
                        $field['elementPointer'] = $nested;
                        self::Record($result, 'fieldCoverage', $field, $coverageBytes);
                    }
                } catch (Throwable $exception) {
                    if (str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:')) {
                        throw $exception;
                    }
                    $result['issues'][] = self::Issue('PACKAGE_INVALID', $chart->chartKey, $pointer, 'Embedded original JSON is invalid or exceeds structural limits.', 'Correct the producer package.');
                }
            }
        }
        foreach (['globalConfiguration', 'runtimeState', 'excluded'] as $section) {
            foreach (self::Leaves($chart->original->{$section}, '/original/' . $section) as $pointer) {
                $operational = $section === 'excluded' && $chart->original->excluded === [];
                if ($section === 'excluded' && preg_match('~^/original/excluded/([0-9]+)/~', $pointer, $match) === 1) {
                    $operational = in_array($chart->original->excluded[(int) $match[1]]->pointer, ['/LastUploadedConfig', '/Debug', '/EnableCache'], true);
                }
                self::Record($result, 'fieldCoverage', self::Field(
                    $pointer,
                    $operational ? 'not-applicable' : 'unsupported',
                    null,
                    'source-context-1',
                    $operational ? 'The producer explicitly excluded operational state; no target setting is inferred.' : 'Original output, control or exclusion policy is retained; target behavior is not yet equivalent.'
                ), $coverageBytes);
            }
        }
        foreach (['sources', 'bindings', 'axes', 'time', 'appearance', 'reference', 'assetRefs'] as $section) {
            foreach (self::Leaves($chart->{$section}, '/' . $section) as $pointer) {
                $status = 'unsupported';
                $targetPointer = null;
                $reason = 'Export metadata inventoried independently; original preservation alone is not target equivalence.';
                if (preg_match('~^/sources/([0-9]+)/(sourceKey|variableId|variableType|presentation/(?:native|effective))(/|$)~', $pointer, $match) === 1
                    && $result['sources'][(int) $match[1]]['status'] === 'verified-metadata') {
                    $status = 'exact';
                    $targetPointer = '/sources/' . $match[1] . '/' . match ($match[2]) {
                        'sourceKey' => 'sourceKey', 'variableId' => 'variableID', 'variableType' => 'variableType', default => 'native'
                    };
                    $reason = 'Source identity and native formatting verified independently for the plan; no source presentation is written.';
                }
                if (($chart->{$section} === null || $chart->{$section} === []) && $pointer === '/' . $section) {
                    $status = 'not-applicable';
                    $reason = 'This optional dependency is explicitly absent in the source package.';
                }
                self::Record($result, 'metadataCoverage', self::Field($pointer, $status, $targetPointer, 'metadata-inventory-1', $reason), $coverageBytes);
            }
        }
        $unsupported = count(array_filter($result['fieldCoverage'], static fn (array $field): bool => $field['status'] === 'unsupported'));
        if ($unsupported > 0) {
            $result['issues'][] = self::Issue(
                'DESIGN_APPROXIMATED',
                $chart->chartKey,
                '/original/configuration',
                'Original settings have no verified target rule. The displayed draft is incomplete.',
                'Implement or individually review each listed difference before an actual import.'
            );
        }
        $result['issues'][] = self::Issue('DESIGN_APPROXIMATED', $chart->chartKey, '/appearance', 'Rendered equivalence has not been verified for either output context.', 'Compare the complete target design in tile and IPSView before approving an import.');
        if ($chart->reference !== null) {
            $result['issues'][] = self::Issue('FORMAT_UNSUPPORTED', $chart->chartKey, '/reference', 'Embedded comparison values are inert and are not archive data or evidence of rendering equivalence.', 'Use a separately validated comparison reader.');
        }
        foreach ($chart->axes as $index => $axis) {
            if (($axis->presentation ?? null) !== null) {
                try {
                    $profiles = (array) ($axis->presentation->original->profiles ?? new stdClass());
                    $first = reset($profiles);
                    $type = $first instanceof stdClass ? ($first->ProfileType ?? 2) : 2;
                    self::Presentation($axis->presentation, $type);
                } catch (Throwable) {
                    $result['verdicts']['presentations'] = 'blocked';
                    $result['issues'][] = self::Issue('PRESENTATION_UNSUPPORTED', $chart->chartKey, '/axes/' . $index, 'Axis profile cannot be normalized independently.', 'Resolve the axis profile instead of using source defaults.');
                }
            }
        }
        foreach ($chart->assetRefs as $id) {
            foreach ($package->assets as $asset) {
                if ($asset->assetId === $id && ($asset->kind === 'inert-template' || $asset->origin->license === 'not-inferred')) {
                    $result['issues'][] = self::Issue($asset->kind === 'inert-template' ? 'CUSTOM_CODE_INERT' : 'ASSET_UNAVAILABLE', $chart->chartKey, '/assetRefs', 'Custom or license-unresolved resource remains inert; no code or asset is installed.', 'Review the resource and its license separately.');
                }
            }
        }
        foreach ($package->issues as $issue) {
            if ($issue->chartKey === $chart->chartKey) {
                $result['issues'][] = self::Issue($issue->code, $chart->chartKey, $issue->pointer, 'The producer reports an unresolved export problem.', 'Resolve the original export issue before import.');
            }
        }
        return $result;
    }

    private static function Target(stdClass $chart, array $config): array
    {
        $module = null;
        $reason = 'This source model has no equivalent existing target family.';
        switch ($chart->origin->family) {
            case 'cartesian-history':
                $types = [];
                foreach ($chart->bindings as $binding) {
                    $types[] = $binding->settings->Type ?? 'line';
                }
                $types = array_values(array_unique($types, SORT_REGULAR));
                $module = $types === ['bar'] ? 'EChartsBarHistory' : ($types === ['line'] ? 'EChartsTimeSeries' : null);
                $reason = $module === null ? 'A simultaneous line/bar model cannot be split into separate charts automatically.' : 'Candidate only: historical calculation and design rules still need exact mapping.';
                break;
            case 'pie':
                $rings = $chart->appearance->rings ?? null;
                $module = is_array($rings) && count($rings) === 1 ? 'EChartsPie' : null;
                $reason = $module === null ? 'Multiple or unresolved rings must not be flattened into a single Pie.' : 'Single-ring candidate; formatting, geometry and weights require validation.';
                break;
            case 'gauge':
                $module = $chart->origin->templateVariant === 'CanvasGauges-Radial' ? 'EChartsGaugeSingle' : null;
                $reason = $module === null ? 'Linear and compass gauges require their own equivalent geometry.' : 'Radial candidate; ticks, highlights, conversion and full styling remain separate rules.';
                break;
            case 'progress':
                $module = ($config['Type'] ?? null) === 'fill' && in_array($config['shape_preset'] ?? null, ['line', 'rect'], true)
                    && ($config['shape_svg'] ?? '') === '' && ($config['shape_path'] ?? '') === '' ? 'EChartsPictorialBar' : null;
                $reason = $module === null ? 'Stroke progress and custom path geometry have no verified equivalent fill model.' : 'Fill candidate only; clipping direction and geometry require comparison.';
                break;
            case 'radar-history':
                $reason = 'Historical Radar classes/references cannot be mapped to the current-value EChartsRadar.';
        }
        $id = $module === null ? null : json_decode((string) file_get_contents(dirname(__DIR__) . '/' . $module . '/module.json'), true, 8, JSON_THROW_ON_ERROR)['id'];
        return ['module' => $module, 'moduleId' => $id, 'reason' => $reason, 'configuration' => [], 'bindings' => [], 'partial' => true];
    }

    private static function Configuration(stdClass $chart, array $config, array $sources): array
    {
        $target = self::Target($chart, $config)['module'];
        if ($target === null) {
            return [];
        }
        $draft = [];
        if (is_string($config['title_text'] ?? null)) {
            $draft['Title'] = $config['title_text'];
        }
        if ($target === 'EChartsGaugeSingle') {
            if (EChartsValueRange::IsValid($config['min'] ?? null, $config['max'] ?? null)) {
                $draft['Minimum'] = $config['min'];
                $draft['Maximum'] = $config['max'];
                $draft['UseVariablePresentation'] = false;
            }
            if (is_int($config['precision'] ?? null) && $config['precision'] >= 0 && $config['precision'] <= 6) {
                $draft['Decimals'] = $config['precision'];
            }
            $binding = $chart->bindings[0] ?? null;
            if ($binding !== null && isset($sources[$binding->sourceKey ?? ''])) {
                $draft['SourceVariableID'] = $sources[$binding->sourceKey]['id'];
            }
        }
        return $draft;
    }

    private static function TargetBudget(stdClass $chart, array &$result): void
    {
        $module = $result['target']['module'];
        $limit = match ($module) {
            'EChartsGaugeSingle'                => 1, 'EChartsTimeSeries', 'EChartsBarHistory' => 32,
            'EChartsPie', 'EChartsPictorialBar' => 100, default => null
        };
        $result['target']['budget'] = ['sourceCount' => count($chart->bindings), 'sourceLimit' => $limit,
            'sourceListByteLimit'                    => $module === 'EChartsGaugeSingle' ? null : 65536,
            'archive'                                => $chart->time === null ? 'not-applicable' : 'not-proven-no-history-loaded'];
        if ($limit === null) {
            return;
        }
        $ids = array_column($result['target']['bindings'], 'variableID');
        if (count($chart->bindings) > $limit || count($ids) !== count(array_unique($ids))) {
            $result['issues'][] = self::Issue('LIMIT_EXCEEDED', $chart->chartKey, '/bindings', 'The target source limit or unique-variable model cannot represent all original bindings.', 'Keep all original bindings; select an equivalent target model instead of truncating or merging.');
        }
        $units = [];
        foreach ($chart->sources as $source) {
            if (!in_array($source->variableType, [1, 2], true)) {
                $result['issues'][] = self::Issue('CALCULATION_UNSUPPORTED', $chart->chartKey, '/sources', 'The candidate family requires numeric source variables.', 'Choose a target model that preserves the source type.');
            }
            $units[] = $source->presentation->effective->suffix ?? null;
        }
        if (in_array($module, ['EChartsPie', 'EChartsPictorialBar'], true) && count(array_unique($units, SORT_REGULAR)) > 1) {
            $result['issues'][] = self::Issue('CALCULATION_UNSUPPORTED', $chart->chartKey, '/sources', 'The candidate raw-value model requires a common unit; normalization is never enabled implicitly.', 'Review the original units and choose an explicit compatible model.');
        }
        $proposed = [];
        foreach ($result['target']['bindings'] as $binding) {
            $sourceIndex = array_search($binding['variableID'], array_column($result['sources'], 'variableID'), true);
            $proposed[] = ['VariableID' => $binding['variableID'], 'Label' => $binding['settings']->Label ?? $result['sources'][$sourceIndex]['label']];
        }
        $result['target']['sourcePlan'] = $proposed;
        $bytes = strlen(EChartsImportJson::Canonical($proposed));
        $result['target']['budget']['minimumSourceListBytes'] = $bytes;
        $result['target']['budget']['finalConfiguration'] = 'not-proven-partial-draft';
        if ($module !== 'EChartsGaugeSingle' && $bytes > 65536) {
            $result['issues'][] = self::Issue('LIMIT_EXCEEDED', $chart->chartKey, '/bindings', 'The binding draft exceeds the target source-list byte budget.', 'Split the reviewed migration plan without dropping source information.');
        }
    }

    private static function Field(string $pointer, string $status, ?string $target, string $rule, string $reason): array
    {
        return compact('pointer', 'status', 'target', 'rule', 'reason');
    }

    private static function Record(array &$result, string $section, array $field, int &$bytes): void
    {
        $bytes += strlen(EChartsImportJson::Canonical($field)) + 1;
        if ($bytes > 8388608) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: complete preview report exceeds 8 MiB.');
        }
        $result[$section][] = $field;
    }

    private static function Issue(string $code, string $chartKey, string $pointer, string $message, string $resolution): array
    {
        return ['code' => $code, 'severity' => 'error', 'chartKey' => $chartKey, 'pointer' => $pointer,
            'message'  => $message, 'effect' => 'Complete automatic import is blocked.', 'resolution' => $resolution];
    }

    /** @return \Generator<int, string> */
    private static function Leaves(mixed $value, string $pointer, int $depth = 0): \Generator
    {
        if ($depth > 32) {
            throw new InvalidArgumentException('LIMIT_EXCEEDED: nested original property JSON exceeds the preview depth.');
        }
        if (is_string($value) && in_array(basename($pointer), ['Variables', 'Datasets', 'Axes', 'Ticks', 'Highlights', 'customScale'], true)
            && in_array(substr(ltrim($value), 0, 1), ['[', '{'], true)) {
            yield from self::Leaves(EChartsImportJson::Decode($value), $pointer, $depth + 1);
            return;
        }
        if (($value instanceof stdClass || is_array($value)) && (array) $value !== []) {
            foreach ((array) $value as $key => $part) {
                yield from self::Leaves($part, $pointer . '/' . EChartsImportJson::Pointer((string) $key), $depth + 1);
            }
        } else {
            yield $pointer;
        }
    }
}
