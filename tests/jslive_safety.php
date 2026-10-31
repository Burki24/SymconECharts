<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';
require_once __DIR__ . '/../libs/EChartsJSLiveArchive.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveArchive;
use SymconECharts\EChartsJSLiveImport;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLiveSourceReader;

set_error_handler(static function (int $severity, string $message): never
{ throw new RuntimeException($message); });
foreach (['EChartsGateway/locale.json', 'EChartsRadarHistory/locale.json', 'EChartsProgress/locale.json', 'libs/jslive/locale.json'] as $locale) {
    $translations = json_decode(file_get_contents(__DIR__ . '/../' . $locale), true, 32, JSON_THROW_ON_ERROR)['translations']['de'];
    foreach ($translations as $text) {
        if (preg_match('/[\x{00C2}\x{00C3}\x{FFFD}]|\x{00E2}\x{20AC}/u', $text) === 1) {
            throw new RuntimeException($locale . ': corrupted German translation encoding.');
        }
    }
}
function safeAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function safeReject(callable $work, string $prefix): void
{
    try {
        $work();
    } catch (Throwable $e) {
        safeAssert(str_starts_with($e->getMessage(), $prefix), $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $prefix);
}

[$json, $packet, $metadata] = jsliveFamilyFixture(__DIR__ . '/fixtures/jslive-packages/case-01.json');
$report = EChartsJSLiveImport::Analyze($json, [], static fn (int $id, ?int $archive): array => $metadata[$id], $packet->sourceInstallation->id);
$compile = static fn (object $chart, ?array $assets = null): array => EChartsJSLiveModel::Compile($chart, $report['charts'][0], $assets ?? $packet->assets);
$original = $packet->charts[0];
foreach (['unknownUnsafeSetting' => true, 'TemplateScriptID' => 12345, 'title_fontSize' => 'large'] as $key => $value) {
    $chart = unserialize(serialize($original));
    $chart->original->configuration->{$key} = $value;
    safeReject(fn () => $compile($chart), $key === 'TemplateScriptID' ? 'CUSTOM_CODE_INERT:' : 'FORMAT_UNSUPPORTED:');
}
$assets = unserialize(serialize($packet->assets));
foreach ($assets as $asset) {
    if ($asset->kind === 'inert-standard-template') {
        $bytes = base64_decode($asset->contentBase64, true) . '<script>unsafe()</script>';
        $asset->contentBase64 = base64_encode($bytes);
        $asset->sha256 = hash('sha256', $bytes);
        $asset->byteLength = strlen($bytes);
    }
}
safeReject(fn () => $compile($original, $assets), 'CUSTOM_CODE_INERT:');
$model = $compile($original);
if ($model['resources'] !== []) {
    $invalid = $model;
    $invalid['resources'][0]['data'] = base64_encode('not a verified font');
    safeReject(fn () => EChartsJSLiveModel::Read(EChartsImportJson::Canonical($invalid), $invalid['family']), 'ASSET_UNAVAILABLE:');
}
$invalid = $model;
$first = array_key_first($invalid['sources']);
$invalid['sources'][$first]['native']['parameters']['PROFILE'] = 'Legacy';
safeReject(fn () => EChartsJSLiveModel::Read(EChartsImportJson::Canonical($invalid), $invalid['family']), 'PRESENTATION_UNSUPPORTED:');
$invalid = $model;
$invalid['bindings'][0]['variableID'] = 99999;
safeReject(fn () => EChartsJSLiveModel::Read(EChartsImportJson::Canonical($invalid), $invalid['family']), 'SOURCE_UNRESOLVED:');
foreach (['M0 0 L1', 'M0 0,,L1 1', 'M0 0 A2 2 0 2 0 4 4', 'M0 0 L1e99 1', 'M0 0 javascript:alert(1)'] as $path) {
    safeReject(fn () => \SymconECharts\EChartsJSLivePath::Validate($path), str_contains($path, 'javascript') ? 'The SVG contains' : 'FORMAT_UNSUPPORTED:');
}

// Test the actual Gateway transport boundary, including the sentinel and changed archive metadata.
$GLOBALS['safeArchive'] = ['rows' => [['TimeStamp' => 150, 'Value' => 3.5]], 'type' => 0, 'ignoreZeros' => false, 'logged' => true, 'reads' => []];
function IPS_VariableExists(int $id): bool
{
    return $id === 13000;
}
function IPS_InstanceExists(int $id): bool
{
    return $id === 2;
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2, 'VariableUpdated' => 200];
}
function IPS_GetInstance(int $id): array
{
    return ['ModuleInfo' => ['ModuleID' => '{43192F0B-135B-4CE7-A0A7-1475603F3060}']];
}
function GetValue(int $id): float
{
    return 3.5;
}
function AC_GetLoggingStatus(int $archive, int $id): bool
{
    return $GLOBALS['safeArchive']['logged'];
}
function AC_GetAggregationType(int $archive, int $id): int
{
    return $GLOBALS['safeArchive']['type'];
}
function AC_GetCounterIgnoreZeros(int $archive, int $id): bool
{
    return $GLOBALS['safeArchive']['ignoreZeros'];
}
function AC_GetLoggedValues(int $archive, int $id, int $from, int $until, int $limit): array
{
    $GLOBALS['safeArchive']['reads'][] = [$from, $until, $limit];
    return $GLOBALS['safeArchive']['rows'];
}
function AC_GetAggregatedValues(int $archive, int $id, int $level, int $from, int $until, int $limit): array
{
    return array_map(static fn (array $row): array => ['TimeStamp' => $row['TimeStamp'], 'Avg' => $row['Value']], AC_GetLoggedValues($archive, $id, $from, $until, $limit));
}
$query = ['VariableID'     => 13000, 'ArchiveID' => 2, 'Mode' => 'raw', 'FromInclusive' => 100, 'UntilExclusive' => 200,
    'ExpectedVariableType' => 2, 'ExpectedAggregationType' => 0, 'ExpectedCounterIgnoreZeros' => null, 'Limit' => 1];
$result = EChartsJSLiveArchive::Read($query);
safeAssert($result['Rows'][0]['Value'] === 3.5 && $GLOBALS['safeArchive']['reads'] === [[100, 199, 2]], 'Inclusive kernel range and budget sentinel differ.');
$GLOBALS['safeArchive']['rows'][] = ['TimeStamp' => 140, 'Value' => 4.5];
$result = EChartsJSLiveArchive::Read($query);
safeAssert($result['Truncated'] && $result['Rows'] === [], 'Truncation must never expose partial history.');
$GLOBALS['safeArchive']['type'] = 1;
safeReject(fn () => EChartsJSLiveArchive::Read($query), 'SOURCE_CHANGED:');
$query['ExpectedAggregationType'] = 1;
$query['ExpectedCounterIgnoreZeros'] = true;
safeReject(fn () => EChartsJSLiveArchive::Read($query), 'SOURCE_CHANGED:');
$GLOBALS['safeArchive']['type'] = 0;
$query['ExpectedAggregationType'] = 0;
$query['Limit'] = 2;
$GLOBALS['safeArchive']['rows'] = [['TimeStamp' => 140, 'Value' => 1], ['TimeStamp' => 150, 'Value' => 2]];
safeReject(fn () => EChartsJSLiveArchive::Read($query), 'INVALID_ARCHIVE_DATA:');
$GLOBALS['safeArchive']['logged'] = false;
safeReject(fn () => EChartsJSLiveArchive::Read($query), 'ARCHIVE_UNAVAILABLE:');

$reads = 0;
$total = 0;
$read = static function (array $q) use (&$reads, &$total): array
{
    ++$reads;
    $total += $q['Limit'];
    return ['VariableID' => $q['VariableID'], 'VariableType' => 2, 'Rows' => [], 'Truncated' => false];
};
$queries = [];
for ($i = 0; $i < 63; ++$i) {
    $q = $query;
    $q['FromInclusive'] = 100 + $i;
    $queries[] = $q;
}
$reader = new EChartsJSLiveSourceReader([...$queries, $queries[0]], $read(...));
foreach ($queries as $q) {
    $reader->Read($q);
    $reader->Read($q);
}
safeAssert($reads === 63 && $total <= 32000, 'Duplicate queries must share the preallocated total budget.');
$extra = $query;
$extra['FromInclusive'] = 99;
safeReject(fn () => $reader->Read($extra), 'INVALID_REQUEST:');
safeReject(fn () => new EChartsJSLiveSourceReader([...$queries, $extra], $read(...)), 'LIMIT_EXCEEDED:');
$badReader = new EChartsJSLiveSourceReader([$query], static fn (array $q): array => ['VariableID' => $q['VariableID'], 'VariableType' => 2, 'Rows' => [], 'Truncated' => true]);
safeReject(fn () => $badReader->Read($query), 'LIMIT_EXCEEDED:');
echo "JSLive typed resources, custom-code rejection, path safety, archive sentinel/type/policy checks and deduplicated shared budgets passed.\n";
