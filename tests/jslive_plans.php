<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLivePlan;

set_error_handler(static function (int $severity, string $message): never
{ throw new RuntimeException($message); });
function planAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$count = 0;
$fields = 0;
$families = [];
$batch = null;
$batchMetadata = [];
$batchAssets = [];
foreach (glob(__DIR__ . '/fixtures/jslive-packages/*.json') as $file) {
    [$json, $package, $metadata, $reader] = jsliveFamilyFixture($file);
    if (($argv[1] ?? '') === '--batch') {
        if ($batch === null) {
            $batch = clone $package;
            $batch->charts = [];
            $batch->assets = [];
        }
        array_push($batch->charts, ...$package->charts);
        foreach ($package->assets as $asset) {
            $batchAssets[$asset->assetId] = $asset;
        }
        $batchMetadata = array_replace($batchMetadata, $metadata);
    }
    $source = static fn (int $id, ?int $archive): array => $metadata[$id];
    $context = ['gatewayID' => 16000, 'parentID' => 15000, 'fingerprint' => str_repeat('c', 64)];
    $plan = EChartsJSLivePlan::Prepare($json, [], $context, $source, $reader, $package->sourceInstallation->id, 1793448000);
    $chart = array_values($plan['charts'])[0];
    planAssert(!$chart['blocked'], basename($file) . ': plan is blocked: ' . EChartsImportJson::Canonical($chart['issues']));
    planAssert($chart['differences'] !== [] && count($chart['differences']) === count($chart['differenceDetails']), 'Each concrete difference requires a stable confirmation ID.');
    planAssert($chart['target']['parentID'] === 15000 && $chart['target']['gatewayID'] === 16000, 'Explicit creation context is retained.');
    foreach (array_merge($chart['fieldCoverage'], $chart['metadataCoverage']) as $field) {
        planAssert(in_array($field['status'], ['exact', 'approximate', 'not-applicable'], true) && $field['rule'] !== '' && $field['reason'] !== '', 'Ready chart has incomplete field coverage.');
        if ($field['status'] === 'approximate') {
            planAssert(isset($chart['differenceDetails'][$field['differenceID']]), 'Approximation lacks an individual acceptance.');
        }
        ++$fields;
    }
    $families[$chart['target']['module']] = true;
    // Export identity/time are not a new semantic revision; the reviewed packet hash still changes.
    $changed = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
    $changed->exportId = '11111111-1111-4111-8111-999999999999';
    $changed->createdAt = '2026-10-31T12:01:00Z';
    $next = EChartsJSLivePlan::Prepare(EChartsImportJson::Canonical($changed), [], $context, $source, $reader, $package->sourceInstallation->id, 1793448000);
    planAssert($next['charts'][$chart['chartKey']]['revision'] === $chart['revision'] && $next['planHash'] !== $plan['planHash'], 'Export envelope incorrectly changes semantic revision or fails to invalidate confirmation.');
    $bad = $metadata;
    $first = array_key_first($bad);
    $bad[$first]['variableType'] = 99;
    $blocked = EChartsJSLivePlan::Prepare($json, [], $context, static fn (int $id, ?int $archive): array => $bad[$id], $reader, $package->sourceInstallation->id, 1793448000);
    planAssert(array_values($blocked['charts'])[0]['blocked'], 'Changed source cannot be waived by accepting design differences.');
    ++$count;
}
planAssert($count === 40 && count($families) === 6, 'All 40 variants and six target families must have full plans.');
if ($batch !== null) {
    $batch->assets = array_values($batchAssets);
    $json = EChartsImportJson::Canonical($batch);
    $installationID = $batch->sourceInstallation->id;
    unset($batch, $batchAssets, $package, $next, $plan, $blocked, $changed, $chart);
    $batchReader = static function (array $query) use ($batchMetadata): array
    {
        $type = $batchMetadata[$query['VariableID']]['variableType'];
        $value = $type === 0 ? true : ($type === 1 ? 21 : 21.5);
        return ['VariableID' => $query['VariableID'], 'VariableType' => $type, 'Value' => $value,
            'Timestamp'      => 1793448000, 'Rows' => [], 'Truncated' => false];
    };
    try {
        $batchPlan = EChartsJSLivePlan::Prepare(
            $json,
            [],
            $context,
            static fn (int $id, ?int $archive): array => $batchMetadata[$id],
            $batchReader,
            $installationID,
            1793448000
        );
        planAssert(count($batchPlan['charts']) === 40, 'A complete 40-chart package must not be silently truncated.');
    } catch (InvalidArgumentException $exception) {
        planAssert(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED: complete plan exceeds 8 MiB'), 'Unexpected batch planning failure: ' . $exception->getMessage());
    }
    echo 'Forty-chart combined report: complete result or explicit 8-MiB rejection, no truncation; peak=' . memory_get_peak_usage(true) . ".\n";
}
echo 'JSLive full plans: 40 variants, six targets, ' . $fields . " field verdicts, explicit differences, semantic revisions and source-change blockers passed.\n";
