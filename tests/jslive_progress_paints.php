<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveImport;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePage;
use SymconECharts\EChartsJSLivePlan;
use SymconECharts\EChartsJSLiveRendering;
use SymconECharts\ProgressData;

function paintAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

[$json, $packet, $metadata, $reader] = jsliveFamilyFixture(__DIR__ . '/fixtures/jslive-packages/case-25.json');
$source = static fn (int $id, ?int $archive): array => $metadata[$id];
$report = EChartsJSLiveImport::Analyze($json, [], $source, $packet->sourceInstallation->id);
$original = $packet->charts[0];
$originalBytes = EChartsImportJson::Canonical($original);
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><linearGradient id="g"><stop stop-color="#ff0000"/><stop offset="1" stop-color="#00ff00"/></linearGradient></defs><rect width="100" height="100" fill="url(#g)"/></svg>';
$pattern = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><pattern id="p" width="20" height="20" patternUnits="userSpaceOnUse" patternTransform="rotate(35)"><rect width="10" height="20" fill="#ff0000"/></pattern></defs><rect width="100" height="100" fill="url(#p)"/></svg>';
$compile = static function (array $changes) use ($original, $report, $packet): array
{
    $chart = unserialize(serialize($original));
    foreach ($changes as $key => $value) {
        $chart->original->configuration->{$key} = $value;
        $chart->bindings[0]->settings->{$key} = $value;
    }
    return EChartsJSLiveModel::Compile($chart, $report['charts'][0], $packet->assets);
};
$variants = [
    'stroke-gradient'     => ['override_stroke' => 'data:ldbar/res,gradient(0,4,#f00,#ff0,#0f0)'],
    'fill-gradient'       => ['Type' => 'fill', 'fill_dir' => 'btt', 'override_fill' => 'data:ldbar/res,gradient(90,2,#f00,#ff0,#0f0)'],
    'svg-file'            => ['Type' => 'fill', 'fill_backgroundFile' => base64_encode($svg), 'fill_backgroundFileType' => 'image/svg+xml'],
    'svg-override'        => ['Type' => 'fill', 'override_fill' => 'data:image/svg+xml;base64,' . base64_encode($svg)],
    'svg-pattern'         => ['Type' => 'fill', 'override_fill' => 'data:image/svg+xml;base64,' . base64_encode($pattern)],
    'override-precedence' => ['Type' => 'fill', 'fill_backgroundFile' => base64_encode($svg), 'fill_backgroundFileType' => 'image/svg+xml',
        'override_fill'              => 'data:ldbar/res,gradient(45,0,#123456,#abcdef)'],
    'reverse' => ['data_min' => 100.0, 'data_max' => 0.0, 'stroke_dir' => 'reverse', 'override_stroke' => 'data:ldbar/res,gradient(45,4,#123456,#abcdef)']
];
$states = [];
foreach ($variants as $name => $changes) {
    $model = $compile($changes);
    $reloaded = EChartsJSLiveModel::Read(EChartsImportJson::Canonical($model), 'progress');
    paintAssert(EChartsImportJson::Canonical($model) === EChartsImportJson::Canonical($reloaded), 'Paint model must survive a strict reload: ' . $name);
    foreach ($changes as $key => $value) {
        paintAssert($model['settings'][$key] === $value, 'Original typed paint/scale settings must remain frozen: ' . $key);
    }
    $state = EChartsJSLiveRendering::State($reloaded, $reader, 1793448000);
    paintAssert(isset($state['chart']['paints']), 'Validated paint must reach the renderer.');
    foreach ([false, true] as $ipsView) {
        $html = EChartsJSLivePage::Render($state, $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58]);
        paintAssert(strlen($html) < 1048576 && !str_contains($html, '/hook/JSLive/'), 'Both outputs must stay local and bounded.');
    }
    $states[$name] = $state;
}
paintAssert(EChartsImportJson::Canonical($original) === $originalBytes, 'Compiling must not modify the original chart.');
paintAssert($states['override-precedence']['chart']['paints']['fill']['kind'] === 'gradient', 'Explicit fill override wins over the file.');
paintAssert($states['reverse']['chart']['data']['ratio'] > 0.5, 'Signed scale projection remains intact.');

// The template reads the default profile only. Native suffixes and a custom
// profile must not silently change its visible label; tooltips keep both.
$originalFormat = ['variable' => ['VariableProfile' => '', 'VariableCustomProfile' => 'Custom'],
    'profiles'                => ['Custom' => ['Prefix' => 'custom ', 'Suffix' => ' %'], 'Default' => ['Prefix' => 'old ', 'Suffix' => ' W']]];
paintAssert(ProgressData::SourceLabel($originalFormat) === ['prefix' => '', 'suffix' => ''], 'Custom/native formats do not supply original label affixes.');
$originalFormat['variable']['VariableProfile'] = 'Default';
paintAssert(ProgressData::SourceLabel($originalFormat) === ['prefix' => 'old ', 'suffix' => ' W'], 'Original default-profile affixes must be translated literally.');
$rounding = $compile(['data_min' => -100.0, 'data_max' => 100.0, 'data_precisionCustom' => 0.1]);
$rounded = ProgressData::Build($rounding, static function (array $query) use ($reader): array
{
    $answer = $reader($query);
    $answer['Value'] = 0.3;
    return $answer;
});
paintAssert($rounded['displayValue'] === 0.3, 'Original reciprocal rounding must not introduce 0.30000000000000004.');
$oldModel = $rounding;
unset($oldModel['progressLabel']);
paintAssert(!isset(EChartsJSLiveModel::Read(EChartsImportJson::Canonical($oldModel), 'progress')['progressLabel']), 'Reading old models must not rewrite their frozen settings.');
$longLabel = $rounding;
$longLabel['progressLabel']['prefix'] = str_repeat('X', 8192);
paintAssert(EChartsJSLiveModel::Read(EChartsImportJson::Canonical($longLabel), 'progress')['progressLabel'] === $longLabel['progressLabel'], 'Label texts share the existing total model budget, not an arbitrary new field limit.');

foreach ([
    ['override_stroke' => 'https://invalid.example/paint.svg'],
    ['override_fill' => 'javascript:alert(1)'],
    ['override_fill' => 'data:ldbar/res,gradient(0,1,#f00,#0f0);alert(1)'],
    ['override_fill' => 'data:ldbar/res,gradient(1e999,1,#f00,#0f0)'],
    ['override_fill' => 'data:ldbar/res,gradient(0,-1,#f00,#0f0)'],
    ['override_fill' => 'data:ldbar/res,gradient(0,1,#f00,url(https://invalid.example))'],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode('<svg viewBox="0 0 10 10" onload="alert(1)"><rect width="10" height="10"/></svg>')],
    ['fill_backgroundFile' => base64_encode($svg), 'fill_backgroundFileType' => 'image/png'],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('fill="#ff0000"', 'fill="url(#p)"', $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('width="20" height="20"', 'width="0" height="20"', $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('width="100" height="100" viewBox', 'width="10000" height="100" viewBox', $svg))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('rotate(35)', 'scale(0.000000001)', $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('fill="url(#p)"', 'fill="url(#missing)"', $pattern))],
    ['override_fill' => 'data:ldbar/res,gradient(0,1,' . implode(',', array_fill(0, 17, '#f00')) . ')'],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace(['<svg ', 'fill="#ff0000"'], ['<svg fill="url(#p)" ', 'fill="inherit"'], $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace(['<svg ', 'fill="#ff0000"'], ['<svg stroke="url(#p)" ', 'fill="none"'], $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace(['<svg ', 'fill="#ff0000"'], ['<svg fill="url(#p)" ', 'fill="INHERIT"'], $pattern))],
    ['override_fill' => 'data:image/svg+xml;base64,' . base64_encode(str_replace(['<svg ', 'fill="#ff0000"'], ['<svg fill="url(#p)" ', 'style="fill:UnSeT"'], $pattern))]
] as $invalid) {
    try {
        $compile($invalid);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Unsafe or unsupported paint was accepted.');
}

// The full native plan must report the new adapter, including the explicit
// native animation timing and the source's broken extensionless image route.
foreach ($variants as $name => $changes) {
    $changed = unserialize(serialize($packet));
    foreach ($changes as $key => $value) {
        $changed->charts[0]->original->configuration->{$key} = $value;
        $changed->charts[0]->bindings[0]->settings->{$key} = $value;
    }
    $changed->charts[0]->appearance->settings = clone $changed->charts[0]->original->configuration;
    $plan = EChartsJSLivePlan::Prepare(
        EChartsImportJson::Canonical($changed),
        [],
        ['gatewayID' => 16000, 'parentID' => 15000, 'fingerprint' => str_repeat('c', 64)],
        $source,
        $reader,
        $packet->sourceInstallation->id,
        1793448000
    );
    $row = array_values($plan['charts'])[0];
    paintAssert(!$row['blocked'], 'Paint must pass the full native plan: ' . $name);
    $rules = array_column($row['differenceDetails'], 'rule');
    paintAssert(in_array('native-progress-svg-route-1', $rules, true) === ($name === 'svg-file'), 'Only an active native SVG file route needs its source-defect warning.');
    paintAssert(in_array('native-progress-cyclic-gradient-1', $rules, true) === in_array($name, ['stroke-gradient', 'fill-gradient', 'reverse'], true), 'Independent native gradient timing must be explicitly reported.');
    paintAssert(in_array('native-progress-validated-paint-1', $rules, true), 'Paints must have an audited field mapping.');
}
$tampered = $compile($variants['svg-file']);
$tampered['progressPaints']['fill']['dataUri'] = 'https://invalid.example/changed.svg';
try {
    EChartsJSLiveModel::Read(EChartsImportJson::Canonical($tampered), 'progress');
    throw new RuntimeException('A forged derived SVG URI was trusted.');
} catch (InvalidArgumentException $exception) {
    paintAssert(str_starts_with($exception->getMessage(), 'PACKAGE_INVALID:'), 'Unexpected derived-paint rejection.');
}

if (($argv[1] ?? '') === '--html') {
    $directory = $argv[2] ?? '';
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Unable to create paint HTML directory.');
    }
    $index = 23;
    foreach ($states as $state) {
        foreach ([false, true] as $ipsView) {
            file_put_contents(
                $directory . '/case-' . $index . ($ipsView ? '-ipsview' : '-tile') . '.html',
                EChartsJSLivePage::Render($state, $ipsView, ['tileHeaderInset' => $ipsView ? 0 : 58])
            );
        }
        ++$index;
    }
} elseif (($argv[1] ?? '') === '--emit') {
    echo EChartsImportJson::Canonical($states);
} else {
    echo "JSLive progress paint validation, model reload, precedence, original preservation and both outputs passed.\n";
}
