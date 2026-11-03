<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveModel.php';
require_once __DIR__ . '/../libs/EChartsJSLiveRendering.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveImport;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePackage;
use SymconECharts\EChartsJSLivePage;
use SymconECharts\EChartsJSLiveRendering;

$external = isset($argv[1]) && is_dir($argv[1]);
$emit = ($argv[1] ?? '') === '--emit';
$path = $external ? $argv[1] : (__DIR__ . '/fixtures/jslive-packages');
$expectedCount = $external ? filter_var($argv[2] ?? '40', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) : 40;
if ($expectedCount === false) {
    throw new InvalidArgumentException('Expected external package count must be between 1 and 100.');
}
$count = 0;
$states = [];
$maximumHTML = 0;
foreach (glob($path . '/*.json') as $file) {
    $json = file_get_contents($file);
    if (!$external) {
        $fixture = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
        foreach ($fixture->assets as $asset) {
            if ($asset->contentBase64 === '@fixture-font:' . $asset->sha256 && preg_match('/^[a-f0-9]{64}$/D', $asset->sha256) === 1) {
                $asset->contentBase64 = base64_encode(file_get_contents(__DIR__ . '/fixtures/jslive-assets/' . $asset->sha256 . '.woff2'));
            }
            if ($asset->contentBase64 === '@fixture-template:' . $asset->sha256 && preg_match('/^[a-f0-9]{64}$/D', $asset->sha256) === 1) {
                $asset->contentBase64 = base64_encode(file_get_contents(__DIR__ . '/fixtures/jslive-templates/' . $asset->sha256 . '.html'));
            }
        }
        $json = EChartsImportJson::Canonical($fixture);
    }
    $package = EChartsJSLivePackage::Read($json);
    $metadata = [];
    foreach ($package->charts[0]->sources as $source) {
        $metadata[$source->variableId] = ['variableType' => $source->variableType, 'native' => $source->presentation->native,
            'variableAction'                             => $source->presentation->original->variable->VariableAction ?? null,
            'variableCustomAction'                       => $source->presentation->original->variable->VariableCustomAction ?? null,
            'archive'                                    => ['logging' => $source->archive->logging, 'aggregationType' => $source->archive->aggregationType,
                'counterSettings'                                      => EChartsJSLivePackage::ArrayValue($source->archive->counterSettings)]];
    }
    $report = EChartsJSLiveImport::Analyze($json, [], static fn (int $id, ?int $archive): array => $metadata[$id], $package->sourceInstallation->id);
    try {
        $model = EChartsJSLiveModel::Compile($package->charts[0], $report['charts'][0], $package->assets);
    } catch (Throwable $e) {
        throw new RuntimeException(basename($file) . ': ' . $e->getMessage(), 0, $e);
    }
    if (str_contains(EChartsImportJson::Canonical($model), '"PROFILE":') || $model['version'] !== '1.0.0') {
        throw new RuntimeException('Target retains a profile dependency.');
    }
    $types = array_column($model['sources'], 'variableType', 'variableID');
    $read = static function (array $query) use ($types): array
    {
        $type = $types[$query['VariableID']];
        $value = $type === 0 ? true : ($type === 1 ? 21 : 21.5);
        $rows = [];
        if ($query['Mode'] !== 'current') {
            $until = $query['UntilExclusive'] - 1;
            $start = $query['FromInclusive'];
            foreach ([$until, intdiv($start + $until, 2), $start] as $timestamp) {
                $rows[] = ['TimeStamp' => $timestamp, $query['Mode'] === 'raw' ? 'Value' : 'Avg' => $value];
            }
        }
        return ['VariableID' => $query['VariableID'], 'VariableType' => $type, 'Value' => $value,
            'Timestamp'      => 1793448000, 'Rows' => $rows, 'Truncated' => false];
    };
    $state = EChartsJSLiveRendering::State($model, $read, 1793448000);
    foreach ([false, true] as $ipsView) {
        $html = EChartsJSLivePage::Render($state, $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58]);
        $maximumHTML = max($maximumHTML, strlen($html));
        if (getenv('JSLIVE_HTML_OUTPUT') !== false) {
            $directory = getenv('JSLIVE_HTML_OUTPUT');
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create local HTML output directory.');
            }
            file_put_contents($directory . '/' . pathinfo($file, PATHINFO_FILENAME) . ($ipsView ? '-ipsview' : '-tile') . '.html', $html);
        }
        if (!str_starts_with($html, '<!doctype html>') || !str_contains($html, 'window.SYMC_VISUALIZATION') || str_contains($html, '/hook/JSLive/')) {
            throw new RuntimeException('Output is empty or retains a JSLive runtime dependency.');
        }
    }
    if ($emit) {
        $states[] = $state;
    }
    ++$count;
}
if ($count !== $expectedCount) {
    throw new RuntimeException('Expected ' . $expectedCount . ' family fixtures, got ' . $count);
}
echo $emit ? EChartsImportJson::Canonical($states) : ('JSLive frozen native family models and both HTML outputs: ' . $count . ' passed; largest HTML ' . $maximumHTML . " bytes.\n");
