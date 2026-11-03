<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveImport.php';
require_once __DIR__ . '/jslive_import_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveImport;
use SymconECharts\EChartsJSLivePackage;

set_error_handler(static function (int $severity, string $message): bool
{
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new RuntimeException($message);
});

function assertJSLiveImport(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach (['{"format":1,"format":2}', '{"a":1,"\u0061":2}', '{"nested":{"x":1,"x":2}}',
    '{"value":9007199254740992}', '{"value":1e999}', '{"value":1e-999}', "\xEF\xBB\xBF{}", '{"x":01}', '{"x":"\uD800"}',
    '', '[1,]', '{"x":1,}', '{}x', '{"x":truefalse}', str_repeat('[', 33) . '0' . str_repeat(']', 33)] as $invalid) {
    try {
        EChartsImportJson::Decode($invalid);
        throw new LogicException('Invalid JSON passed package preflight.');
    } catch (InvalidArgumentException) {
    }
}
$document = EChartsImportJson::Decode('{"z":{},"a":[],"n":1.0,"zero":0,"no":false,"null":null}');
assertJSLiveImport(EChartsImportJson::Canonical($document) === '{"a":[],"n":1.0,"no":false,"null":null,"z":{},"zero":0}', 'Canonical JSON lost types, order or empty object/list identity.');
assertJSLiveImport(EChartsImportJson::Canonical(EChartsImportJson::Decode('{"a":1,"b":2}'))
    === EChartsImportJson::Canonical(EChartsImportJson::Decode('{"b":2,"a":1}')), 'Object key order changed the fingerprint.');

echo "Strict migration JSON and canonical fingerprints verified.\n";
assertJSLiveImport(is_array(EChartsImportJson::Decode(str_repeat('[', 32) . '0' . str_repeat(']', 32))), 'The supported 32-container boundary was rejected.');

function importAnalysis(stdClass $package, array $options = [], ?string $installation = '22222222-2222-4222-8222-222222222222', ?callable $reader = null): array
{
    return EChartsJSLiveImport::Analyze(EChartsImportJson::Canonical($package), $options, $reader ?? jsliveImportMetadata(...), $installation);
}

function importIssues(array $report): array
{
    return array_column(array_merge($report['issues'], ...array_column($report['charts'], 'issues')), 'code');
}

$base = jsliveImportPackage();
$before = EChartsImportJson::Canonical($base);
$report = importAnalysis($base);
$chart = $report['charts'][0];
assertJSLiveImport($report['creationEnabled'] === false && $chart['creationEnabled'] === false && $chart['target']['partial'] === true, 'A preview must never authorize creation or imply complete target mapping.');
assertJSLiveImport($report['exportSnapshot']['sourceInstallation']->id === $base->sourceInstallation->id
    && $report['exportSnapshot']['producer']->libraryVersion === '0.111', 'Plan omitted producer provenance or the installation part of its repetition identity.');
assertJSLiveImport($chart['verdicts']['sourcesArchive'] === 'exact' && $chart['verdicts']['presentations'] === 'exact', 'Independent source and native presentation validation failed.');
assertJSLiveImport($chart['target']['module'] === 'EChartsGaugeSingle' && $chart['target']['configuration']['SourceVariableID'] === 13000
    && $chart['target']['configuration']['Minimum'] === 0.0 && $chart['target']['configuration']['Decimals'] === 0, 'Typed Gauge target draft lost zero, precision or source identity.');
assertJSLiveImport($chart['verdicts']['design'] === 'unsupported' && in_array('DESIGN_APPROXIMATED', importIssues($report), true), 'Preserved original fields must not count as rendering equivalence.');
$fields = array_column($chart['fieldCoverage'], null, 'pointer');
foreach (['UnknownZero', 'UnknownFalse', 'UnknownEmpty', 'UnknownNull', 'title_fontSize'] as $key) {
    assertJSLiveImport($fields['/original/configuration/' . $key]['status'] === 'unsupported', 'Unknown or pixel/percentage design fields were silently defaulted.');
}
assertJSLiveImport(EChartsImportJson::Canonical($report) === EChartsImportJson::Canonical(importAnalysis($base)) && $before === EChartsImportJson::Canonical($base), 'Repeated preview changed the input or deterministic plan.');
$changed = clone $base;
$changed->exportId = '33333333-3333-4333-8333-333333333333';
$changed->createdAt = '2026-10-31T13:00:00Z';
$changed = importAnalysis($changed);
assertJSLiveImport($report['revisionHash'] === $changed['revisionHash'] && $report['packageHash'] !== $changed['packageHash'], 'Re-export identity was confused with the semantic revision.');
$precision = ini_get('serialize_precision');
ini_set('serialize_precision', '4');
assertJSLiveImport(EChartsImportJson::Canonical(1.23456789) === '1.23456789' && ini_get('serialize_precision') === '4', 'Canonical float hashing changed the ambient serializer setting.');
ini_set('serialize_precision', $precision);

$foreignReads = 0;
$foreign = importAnalysis($base, [], null, static function () use (&$foreignReads): array
{
    ++$foreignReads;
    return [];
});
assertJSLiveImport($foreignReads === 0 && in_array('SOURCE_UNRESOLVED', importIssues($foreign), true), 'Foreign IDs were guessed or looked up without explicit mapping.');
$mapped = importAnalysis($base, ['sourceMapping' => ['variable:13000' => 14000]], null);
assertJSLiveImport($mapped['charts'][0]['sources'][0]['variableID'] === 14000 && $report['planHash'] !== $mapped['planHash'], 'Explicit source mapping is missing from the bound plan.');
$sourceChanged = importAnalysis($base, [], $base->sourceInstallation->id, static function (int $id, ?int $archive): array
{
    $row = jsliveImportMetadata($id, $archive);
    $row['native'] = clone $row['native'];
    $row['native']->parameters = clone $row['native']->parameters;
    $row['native']->parameters->SUFFIX = ' V';
    return $row;
});
assertJSLiveImport(in_array('SOURCE_CHANGED', importIssues($sourceChanged), true) && $sourceChanged['planHash'] !== $report['planHash'], 'Changed metadata did not invalidate the plan.');
$tampered = jsliveImportPackage();
$tampered->charts[0]->sources[0]->presentation->native->parameters->SUFFIX = ' V';
assertJSLiveImport(in_array('PRESENTATION_UNSUPPORTED', importIssues(importAnalysis($tampered)), true), 'A forged exporter conversion=exact bypassed independent normalization.');
$bindingFormat = jsliveImportPackage();
$formatSnapshot = EChartsImportJson::Decode(EChartsImportJson::Canonical($bindingFormat->charts[0]->sources[0]->presentation));
$formatSnapshot->native->parameters->DIGITS = 5;
$bindingFormat->charts[0]->bindings[0]->settings->presentation = $formatSnapshot;
$bindingFormat = importAnalysis($bindingFormat);
assertJSLiveImport($bindingFormat['charts'][0]['verdicts']['presentations'] === 'blocked'
    && $bindingFormat['charts'][0]['target']['bindings'][0]['native'] === null, 'An unsupported binding format silently fell back to source formatting.');
$legacy = jsliveImportPackage();
$legacyProfile = ['ProfileName' => 'Synthetic.Percent', 'ProfileType' => 2, 'MinValue' => 0.0, 'MaxValue' => 100.0, 'StepSize' => 1.0,
    'Digits'                    => 0, 'Prefix' => '', 'Suffix' => ' %', 'Icon' => '', 'Associations' => []];
$legacyNormalized = \Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper::Normalize(
    2,
    ['PRESENTATION' => \Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper::LEGACY, 'PROFILE' => 'Synthetic.Percent'],
    ['Synthetic.Percent' => $legacyProfile]
);
$legacy->charts[0]->sources[0]->presentation = EChartsImportJson::Decode(EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($legacyNormalized)));
$legacyReport = importAnalysis($legacy, [], $base->sourceInstallation->id, static fn (int $id, ?int $archive): array => ['variableType' => 2, 'native' => EChartsJSLivePackage::Wire($legacyNormalized['native']), 'archive' => null]);
assertJSLiveImport($legacyReport['charts'][0]['verdicts']['presentations'] === 'exact'
    && !isset($legacyReport['charts'][0]['sources'][0]['native']['parameters']['PROFILE']), 'Legacy profile definitions did not become profile-independent native plan data.');

foreach (['SymconJSLiveChart' => 'EChartsTimeSeries', 'SymconJSLiveDoughnutPie' => 'EChartsPie',
    'SymconJSLiveGauge'       => 'EChartsGaugeSingle', 'SymconJSLiveRadarChart' => null, 'SymconJSLiveProgressbar' => 'EChartsPictorialBar'] as $family => $target) {
    $analysis = importAnalysis(jsliveImportPackage($family));
    assertJSLiveImport($analysis['charts'][0]['target']['module'] === $target, 'Family mapping differs from the 1.0 compatibility contract: ' . $family);
}
$history = jsliveImportPackage('SymconJSLiveChart');
$history->charts[0]->bindings[0]->settings->Type = 'bar';
$historyRows = EChartsImportJson::Decode($history->charts[0]->original->configuration->Datasets);
$historyRows[0]->Type = 'bar';
$history->charts[0]->original->configuration->Datasets = EChartsImportJson::Canonical($historyRows);
$history->charts[0]->appearance->settings = $history->charts[0]->original->configuration;
assertJSLiveImport(importAnalysis($history)['charts'][0]['target']['module'] === 'EChartsBarHistory', 'Historical bars were mapped to current categories.');
$foreignArchive = importAnalysis($history, ['sourceMapping' => ['variable:13000' => 14000]], null);
assertJSLiveImport(in_array('SOURCE_UNRESOLVED', importIssues($foreignArchive), true), 'Historical imports did not require explicit archive mapping.');
$foreignArchive = importAnalysis($history, ['sourceMapping' => ['variable:13000' => 14000], 'archiveMapping' => ['archive:2' => 3]], null);
assertJSLiveImport($foreignArchive['charts'][0]['sources'][0]['archiveID'] === 3, 'Explicit archive identity was lost.');
$disabledArchive = importAnalysis($history, [], $base->sourceInstallation->id, static function (int $id, ?int $archive): array
{
    $metadata = jsliveImportMetadata($id, $archive);
    $metadata['archive']['logging'] = false;
    return $metadata;
});
assertJSLiveImport(in_array('ARCHIVE_UNAVAILABLE', importIssues($disabledArchive), true), 'Missing logging was implicitly accepted.');

$multiRing = jsliveImportPackage('SymconJSLiveDoughnutPie');
$multiRing->charts[0]->appearance->rings[] = $multiRing->charts[0]->appearance->rings[0];
assertJSLiveImport(importAnalysis($multiRing)['charts'][0]['target']['module'] === null, 'Multiple rings were flattened.');
$reverseRange = jsliveImportPackage();
$reverseRange->charts[0]->original->configuration->min = 100.0;
$reverseRange->charts[0]->original->configuration->max = 0.0;
$reverseRange->charts[0]->appearance->settings = $reverseRange->charts[0]->original->configuration;
$reverseRange->charts[0]->bindings[0]->settings = $reverseRange->charts[0]->original->configuration;
assertJSLiveImport(!isset(importAnalysis($reverseRange)['charts'][0]['target']['configuration']['Minimum']), 'Reversed Gauge scales were marked exactly transferable.');

$assetPackage = jsliveImportPackage();
$bytes = '<svg onload="mustNeverExecute()"><script>mustNeverExecute()</script></svg>';
$hash = hash('sha256', $bytes);
$assetPackage->assets[] = (object) ['assetId' => 'sha256:' . $hash, 'kind' => 'inert-template', 'mediaType' => 'text/plain', 'byteLength' => strlen($bytes),
    'sha256'                                  => $hash, 'contentBase64' => base64_encode($bytes), 'origin' => (object) ['license' => 'not-inferred']];
$assetPackage->charts[0]->assetRefs[] = 'sha256:' . $hash;
$assetReport = importAnalysis($assetPackage);
assertJSLiveImport($assetReport['assets'][0]['verified'] === true && !str_contains(EChartsImportJson::Canonical($assetReport), $bytes)
    && in_array('CUSTOM_CODE_INERT', importIssues($assetReport), true), 'Resources were not verified as inert, or their executable contents entered the UI.');

$invalidMutations = [
    static function (stdClass $p): void
    { $p->formatVersion = '1.1'; },
    static function (stdClass $p): void
    { $p->createdAt = '2026-02-30T12:00:00Z'; },
    static function (stdClass $p): void
    { $p->producer->libraryId = '{11111111-1111-4111-8111-111111111111}'; },
    static function (stdClass $p): void
    { $p->charts[] = $p->charts[0]; },
    static function (stdClass $p): void
    { $p->charts[0]->origin->moduleId = '{}'; },
    static function (stdClass $p): void
    { $p->charts[0]->sources[0]->variableType = false; },
    static function (stdClass $p): void
    { $p->charts[0]->bindings[0]->sourceKey = 'variable:999'; },
    static function (stdClass $p): void
    { $p->charts[0]->bindings[0]->sourceKey = []; },
    static function (stdClass $p): void
    {
        $p->charts[0]->bindings[0]->originalPointer = '/missing';
        $p->charts[0]->bindings[0]->bindingKey = 'instance:12000#/missing';
    },
    static function (stdClass $p): void
    { $p->charts[0]->coverage = []; },
    static function (stdClass $p): void
    { $p->charts[0]->original->excluded = [false]; },
    static function (stdClass $p): void
    { $p->charts[0]->sources[0]->archive->logging = 1; },
    static function (stdClass $p): void
    { $p->assets[0]->sha256 = str_repeat('0', 64); }
];
foreach ($invalidMutations as $mutate) {
    $invalid = EChartsImportJson::Decode(EChartsImportJson::Canonical($assetPackage));
    $mutate($invalid);
    try {
        EChartsJSLiveImport::Analyze(EChartsImportJson::Canonical($invalid), [], static function (): never
        {
            throw new LogicException('Malformed package reached a kernel metadata read.');
        }, null);
        throw new LogicException('Malformed migration package was accepted.');
    } catch (InvalidArgumentException) {
    }
}
try {
    EChartsImportJson::Upload('data:application/json;base64,e30=');
    throw new LogicException('Data URL was accepted as SelectFile content.');
} catch (InvalidArgumentException) {
}
assertJSLiveImport(EChartsImportJson::Upload(base64_encode($before)) === $before, 'Upload changed exact package bytes.');
$many = importAnalysis(jsliveImportMany(33));
assertJSLiveImport(count($many['charts'][0]['target']['bindings']) === 33 && in_array('LIMIT_EXCEEDED', importIssues($many), true), 'Target limits silently truncated the original source model.');
$duplicates = importAnalysis(jsliveImportMany(2), ['sourceMapping' => ['variable:13000' => 14000, 'variable:13001' => 14000]]);
assertJSLiveImport(in_array('SOURCE_UNRESOLVED', importIssues($duplicates), true), 'Different original source IDs were silently merged.');
$typedSnapshot = jsliveImportPackage();
$typedSnapshot->charts[0]->sources[0]->presentation->original->variable = (object) ['VariableAction' => 7];
assertJSLiveImport(in_array('SOURCE_CHANGED', importIssues(importAnalysis($typedSnapshot)), true), 'Changed source actions were ignored.');
$foreign = jsliveImportPackage();
$foreign->extensions->unrecognized = false;
assertJSLiveImport(in_array('FORMAT_UNSUPPORTED', importIssues(importAnalysis($foreign)), true), 'Unknown extension false was silently ignored.');
$producerIssue = jsliveImportPackage();
$producerIssue->issues[] = (object) ['code' => 'ASSET_UNAVAILABLE', 'severity' => 'error', 'chartKey' => 'instance:12000', 'pointer' => '/Fonts/Synthetic',
    'message'                               => 'Synthetic font is missing.', 'effect' => 'Original typography is unresolved.', 'resolution' => 'Provide the original font.'];
assertJSLiveImport(importAnalysis($producerIssue)['exportSnapshot']['issues'][0]->message === 'Synthetic font is missing.', 'Full report discarded the original producer issue details.');
foreach ([str_repeat(' ', EChartsImportJson::MAXIMUM_BYTES + 1), '[' . str_repeat('0,', 100000) . '0]'] as $oversized) {
    try {
        EChartsImportJson::Decode($oversized);
        throw new LogicException('Package byte/complexity budget was bypassed.');
    } catch (InvalidArgumentException $exception) {
        assertJSLiveImport(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:'), 'Budget rejection must identify its cause.');
    }
}
unset($oversized);
$fractional = jsliveImportPackage();
$fractional->createdAt = '2026-10-31T12:00:00.123+00:00';
assertJSLiveImport(importAnalysis($fractional)['summary']['charts'] === 1, 'Valid fractional UTC RFC 3339 timestamp was rejected.');
$largeReport = jsliveImportPackage();
$extra = new stdClass();
for ($i = 0; $i < 15000; ++$i) {
    $extra->{'field' . $i} = false;
}
$largeChart = $largeReport->charts[0];
$largeChart->original->configuration->Extra = $extra;
$largeChart->appearance->settings = $largeChart->original->configuration;
$largeChart->bindings[0]->settings = $largeChart->original->configuration;
$largeChart->coverage[] = (object) ['pointer' => '/Extra', 'status' => 'exact', 'target' => '/original/configuration/Extra', 'rule' => 'export-original-1', 'reason' => 'Preserved by producer.'];
try {
    importAnalysis($largeReport);
    throw new LogicException('Oversized complete report was silently truncated or exceeded its process budget.');
} catch (InvalidArgumentException $exception) {
    assertJSLiveImport(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:'), 'Report size guard did not reject before memory exhaustion.');
}
unset($largeReport, $largeChart, $extra);
$excluded = jsliveImportPackage();
$excluded->charts[0]->original->excluded = [(object) ['pointer' => '/ImportantUnknownField', 'reason' => 'Claimed operational by producer.']];
$exclusionLedger = array_column(importAnalysis($excluded)['charts'][0]['fieldCoverage'], null, 'pointer');
assertJSLiveImport($exclusionLedger['/original/excluded/0/pointer']['status'] === 'unsupported', 'Unknown excluded fields were automatically classified as not applicable.');
$historical = jsliveImportMany(2);
$rows = EChartsImportJson::Decode($historical->charts[0]->original->configuration->Datasets);
$rows[1]->Axes = '';
$historical->charts[0]->original->configuration->Datasets = EChartsImportJson::Canonical($rows);
$historical->charts[0]->bindings[1]->settings->Axes = '';
$historical->charts[0]->bindings[1]->enabled = false;
$reads = [];
$hidden = importAnalysis($historical, [], $base->sourceInstallation->id, static function (int $id, ?int $archive) use (&$reads): array
{
    $reads[] = $id;
    return jsliveImportMetadata($id, $archive);
});
assertJSLiveImport($reads === [13000, 13001] && $hidden['charts'][0]['target']['bindings'][1]['enabled'] === false, 'Hidden bindings were skipped or re-enabled.');
$historical->charts[0]->time->queries[0]->sourceKey = 'variable:13001';
try {
    importAnalysis($historical);
    throw new LogicException('Query source was allowed to diverge from its binding identity.');
} catch (InvalidArgumentException) {
}
foreach (['{"ModuleID":"legacy","Config":{}}', '{"format":"de.burki24.jslive-echarts","formatVersion":"2.0"}'] as $legacyPackage) {
    try {
        EChartsJSLivePackage::Read($legacyPackage);
        throw new LogicException('Legacy or incomplete envelopes were silently interpreted as contract 1.0.');
    } catch (InvalidArgumentException) {
    }
}
echo "JSLive 1.0 package verification, mapping, independent coverage, inert resources and read-only deterministic plans verified.\n";
