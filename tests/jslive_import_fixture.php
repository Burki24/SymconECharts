<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveImport.php';

use Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper;
use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLivePackage;

/** Synthetic contract fixture; contains neither installation data nor third-party assets. */
function jsliveImportPackage(string $module = 'SymconJSLiveGauge'): stdClass
{
    [$family, $guid] = EChartsJSLivePackage::FAMILIES[$module];
    $normal = VariablePresentationNormalizationHelper::Normalize(2, ['PRESENTATION' => VariablePresentationNormalizationHelper::VALUE, 'SUFFIX' => ' %', 'DIGITS' => 0]);
    $presentation = json_decode(EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($normal)), false, 32, JSON_THROW_ON_ERROR);
    $config = ['title_text' => 'Synthetic chart', 'title_display' => true, 'title_fontSize' => 19,
        'UnknownZero'       => 0, 'UnknownFalse' => false, 'UnknownEmpty' => '', 'UnknownNull' => null];
    $settings = ['Variable' => 13000];
    $pointer = '/Variable';
    if (in_array($family, ['gauge', 'progress'], true)) {
        $config += ['Variable' => 13000, 'min' => 0.0, 'max' => 100.0, 'precision' => 0];
        if ($family === 'gauge') {
            $config['template'] = 'CanvasGauges-Radial';
        } else {
            $config += ['Type' => 'fill', 'shape_preset' => 'rect', 'shape_svg' => '', 'shape_path' => '', 'data_min' => 0.0, 'data_max' => 100.0];
        }
        $settings = $config;
    } else {
        $pointer = '/Datasets/0';
        $settings += ['Type' => 'line', 'Axes' => 'percent', 'Offset' => 0, 'HighRes' => 7];
        $dataset = $settings;
        if ($family === 'pie') {
            $pointer .= '/Variables/0';
            $dataset = ['Variables' => [$settings], 'Profile' => ''];
        }
        $config['Datasets'] = EChartsImportJson::Canonical([$dataset]);
    }
    $coverage = [];
    foreach ($config as $key => $_) {
        $coverage[] = ['pointer' => '/' . EChartsImportJson::Pointer($key), 'status' => 'exact', 'target' => '/original/configuration/' . EChartsImportJson::Pointer($key),
            'rule'               => 'export-original-1', 'reason' => 'Preserved by producer.'];
    }
    $chart = ['chartKey' => 'instance:12000', 'origin' => ['instanceId' => 12000, 'moduleId' => $guid, 'moduleName' => $module,
        'instanceName'                                                  => 'Synthetic chart', 'family' => $family, 'templateVariant' => $config['template'] ?? $config['shape_preset'] ?? 'standard'],
        'original' => ['configuration' => $config, 'globalConfiguration' => new stdClass(), 'runtimeState' => new stdClass(), 'excluded' => []],
        'sources'  => [['sourceKey' => 'variable:13000', 'variableId' => 13000, 'variableType' => 2, 'label' => 'Repeated name', 'presentation' => $presentation,
            'archive'               => ['archiveId' => 2, 'logging' => true, 'aggregationType' => 0, 'counterSettings' => null, 'availability' => 'metadata-only']]],
        'bindings' => [['bindingKey' => 'instance:12000#' . $pointer, 'sourceKey' => 'variable:13000', 'role' => $family === 'pie' ? 'slice' : 'value',
            'enabled'                => true, 'settings' => $settings, 'appearance' => $settings, 'originalPointer' => $pointer]], 'axes' => [], 'time' => null,
        'appearance' => ['settings' => $config, 'global' => new stdClass(), 'rings' => $family === 'pie' ? [['settings' => $dataset, 'presentation' => null]] : []],
        'assetRefs'  => [], 'coverage' => $coverage, 'reference' => null];
    if (in_array($family, ['cartesian-history', 'radar-history'], true)) {
        $chart['time'] = ['timezone' => 'Europe/Berlin', 'selection' => new stdClass(), 'resolvedWindow' => ['fromInclusive' => 1793000000, 'untilExclusive' => 1793003600],
            'rulesVersion'           => 'synthetic-1', 'queries' => [['bindingKey' => $chart['bindings'][0]['bindingKey'], 'sourceKey' => 'variable:13000',
                'fromInclusive'                                                    => 1793000000, 'untilExclusive' => 1793003600, 'displayOffsetSeconds' => 0, 'aggregationLevel' => 0,
                'processing'                                                       => new stdClass(), 'rulesVersion' => 'synthetic-1', 'originalRules' => new stdClass()]]];
    }
    return json_decode(EChartsImportJson::Canonical(['format' => 'de.burki24.jslive-echarts', 'formatVersion' => '1.0',
        'exportId'                                            => '11111111-1111-4111-8111-111111111111', 'createdAt' => '2026-10-31T12:00:00Z',
        'producer'                                            => ['libraryId' => '{1189E095-5FC2-22A5-12DF-2B177A36DB8C}', 'libraryVersion' => '0.111', 'build' => 100,
            'exporterVersion'                                                 => '1.0.0', 'symconVersion' => '9.1', 'phpVersion' => '8.5.8'],
        'sourceInstallation' => ['id' => '22222222-2222-4222-8222-222222222222'], 'charts' => [$chart], 'assets' => [], 'issues' => [], 'extensions' => new stdClass()]), false, 32, JSON_THROW_ON_ERROR);
}

function jsliveImportMetadata(int $id, ?int $archive): array
{
    $fixture = jsliveImportPackage();
    return ['variableType' => 2, 'native' => $fixture->charts[0]->sources[0]->presentation->native,
        'archive'          => $archive === null ? null : ['logging' => true, 'aggregationType' => 0, 'counterSettings' => null]];
}

function jsliveImportMany(int $count): stdClass
{
    $package = jsliveImportPackage('SymconJSLiveChart');
    $chart = $package->charts[0];
    $source = $chart->sources[0];
    $binding = $chart->bindings[0];
    $query = $chart->time->queries[0];
    $chart->sources = $chart->bindings = $chart->time->queries = [];
    $rows = [];
    for ($i = 0; $i < $count; ++$i) {
        $entry = clone $source;
        $entry->variableId += $i;
        $entry->sourceKey = 'variable:' . $entry->variableId;
        $chart->sources[] = $entry;
        $row = clone $binding->settings;
        $row->Variable = $entry->variableId;
        $rows[] = $row;
        $copy = clone $binding;
        $copy->sourceKey = $entry->sourceKey;
        $copy->originalPointer = '/Datasets/' . $i;
        $copy->bindingKey = $chart->chartKey . '#' . $copy->originalPointer;
        $copy->settings = $row;
        $chart->bindings[] = $copy;
        $copyQuery = clone $query;
        $copyQuery->sourceKey = $entry->sourceKey;
        $copyQuery->bindingKey = $copy->bindingKey;
        $chart->time->queries[] = $copyQuery;
    }
    $chart->original->configuration->Datasets = EChartsImportJson::Canonical($rows);
    $chart->appearance->settings = $chart->original->configuration;
    return $package;
}
