<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveModel;
use SymconECharts\EChartsJSLivePlan;
use SymconECharts\EChartsJSLivePresentation;

function presentationEquivalenceAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function presentationEquivalencePlan(Closure $change, int $case = 11): array
{
    [$json, $package, $metadata, $reader] = jsliveFamilyFixture(__DIR__ . '/fixtures/jslive-packages/case-' . $case . '.json');
    $metadata = json_decode(EChartsImportJson::Canonical($metadata), true, 64, JSON_THROW_ON_ERROR);
    $source = $package->charts[0]->sources[0];
    $change($metadata[$source->variableId]);
    $before = EChartsImportJson::Canonical($metadata);
    $options = ['sourceMapping' => [], 'archiveMapping' => []];
    foreach ($package->charts[0]->sources as $item) {
        $options['sourceMapping'][$item->sourceKey] = $item->variableId;
        if ($item->archive->archiveId !== null) {
            $options['archiveMapping']['archive:' . $item->archive->archiveId] = $item->archive->archiveId;
        }
    }
    $plan = EChartsJSLivePlan::Prepare(
        $json,
        $options,
        ['gatewayID' => 16000, 'parentID' => 15000],
        static fn (int $id, ?int $archive): array => $metadata[$id],
        $reader,
        null,
        1793448000
    );
    presentationEquivalenceAssert($before === EChartsImportJson::Canonical($metadata), 'Comparison must not rewrite live metadata.');
    presentationEquivalenceAssert($json === EChartsImportJson::Canonical($package), 'Comparison must not rewrite the package.');
    return [array_values($plan['charts'])[0], $plan, $package, $metadata];
}

[$row, $plan, $package, $metadata] = presentationEquivalencePlan(static function (array &$source): void
{
    $source['native']['parameters'] += ['COLOR' => -1, 'CONTENT_COLOR' => -1, 'DECIMAL_SEPARATOR' => 'Client',
        'DISPLAY_TYPE'                          => 0, 'INTERVALS' => [], 'INTERVALS_ACTIVE' => false, 'PREVIEW_STYLE' => 1,
        'SHOW_PREVIEW'                          => true, 'THOUSANDS_SEPARATOR' => '', 'MULTILINE' => false];
    $source['native']['parameters']['MIN'] = -30;
    $source['native']['parameters']['MAX'] = 70;
});
presentationEquivalenceAssert(!$row['blocked'], 'Known defaults, equal numeric limits and an inactive numeric MULTILINE must not block a foreign import.');
presentationEquivalenceAssert($row['presentationComparisons'][0]['comparison']['status'] === 'equivalent'
    && $row['presentationComparisons'][0]['comparison']['normalizations'] !== [], 'The full plan must explain accepted metadata normalizations.');
$model = EChartsJSLiveModel::Read($row['target']['configuration']['JSLiveImportModel'], 'radar-history');
$original = $package->charts[0]->sources[0];
presentationEquivalenceAssert(EChartsImportJson::Canonical($model['sources'][$original->sourceKey]['native']) === EChartsImportJson::Canonical($original->presentation->native), 'The target must retain original formatting, not replace it with the expanded source defaults.');
$guard = array_values($plan['sourceGuard'])[0];
presentationEquivalenceAssert($guard['fingerprint'] === EChartsImportJson::Hash($metadata[$original->variableId]), 'Execution must guard the complete actual snapshot, including numeric types and defaults.');
$changed = $metadata[$original->variableId];
unset($changed['native']['parameters']['MULTILINE']);
presentationEquivalenceAssert($guard['fingerprint'] !== EChartsImportJson::Hash($changed), 'Post-plan metadata changes must still require fresh confirmation.');

foreach ([
    static function (array &$s): void
    { $s['native']['parameters']['SUFFIX'] .= ' '; },
    static function (array &$s): void
    { ++$s['native']['parameters']['DIGITS']; },
    static function (array &$s): void
    { $s['native']['parameters']['MIN'] = -31; },
    static function (array &$s): void
    { $s['native']['parameters']['MAX'] = 71; },
    static function (array &$s): void
    { $s['native']['parameters']['COLOR'] = 0; },
    static function (array &$s): void
    { $s['native']['parameters']['MULTILINE'] = true; },
    static function (array &$s): void
    { $s['native']['parameters']['UNKNOWN'] = false; },
    static function (array &$s): void
    { $s['native']['parameters']['DIGITS'] = '1'; },
    static function (array &$s): void
    { $s['native']['parameters']['INTERVALS'] = [['IntervalMinValue' => 0, 'IntervalMaxValue' => 10]]; },
    static function (array &$s): void
    { $s['native']['parameters']['INTERVALS'] = '[]'; },
    static function (array &$s): void
    { $s['native']['parameters']['PERCENTAGE'] = true; },
    static function (array &$s): void
    { $s['native']['parameters']['DECIMAL_SEPARATOR'] = ','; },
    static function (array &$s): void
    { $s['native']['presentationId'] = '{4153A8D4-5C33-C65F-C1F3-7B61AAF99B1C}'; },
    static function (array &$s): void
    { $s['variableCustomAction'] = 15001; },
    static function (array &$s): void
    { $s['archive']['logging'] = false; },
    static function (array &$s): void
    { $s['archive']['aggregationType'] = 1; },
    static function (array &$s): void
    { $s['variableType'] = 1; }
] as $change) {
    [$blocked] = presentationEquivalencePlan($change);
    presentationEquivalenceAssert($blocked['blocked'], 'Meaningful or unknown format, action, archive and type changes must remain blocking.');
}

[$native] = presentationEquivalencePlan(static function (array &$source): void
{
    $source['native']['parameters']['MIN'] = 0.0;
    $source['native']['parameters']['MAX'] = 100.0;
}, 12);
presentationEquivalenceAssert(!$native['blocked'], 'A native snapshot must accept exactly equal integer/float limits.');

$base = ['presentationId' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}', 'parameters' => ['MIN' => 0, 'MAX' => 9007199254740993]];
$other = $base;
$other['parameters']['MAX'] = 9007199254740992.0;
$losslessRejected = false;
try {
    EChartsJSLivePresentation::Compare($base, $other, 2);
} catch (InvalidArgumentException $exception) {
    $losslessRejected = str_contains($exception->getMessage(), 'lossless JSON range');
}
presentationEquivalenceAssert($losslessRejected, 'Numeric comparison must retain the lossless JSON integer boundary.');
$base['parameters']['MAX'] = 9007199254740992.0;
$other = $base;
$other['parameters']['COLOR'] = -1;
presentationEquivalenceAssert(EChartsJSLivePresentation::Compare($base, $other, 2)['status'] === 'equivalent', 'An existing large float must not be converted into a forbidden integer.');
$base['parameters']['MAX'] = 1;
$other['parameters']['MAX'] = 1.0000000000000002;
presentationEquivalenceAssert(EChartsJSLivePresentation::Compare($base, $other, 2)['status'] === 'different', 'Numeric comparison must not round away a real range difference.');
$base['parameters']['INTERVALS'] = [['IntervalMinValue' => 0, 'IntervalMaxValue' => 10, 'ConversionFactor' => 1]];
$other = $base;
$other['parameters']['INTERVALS'][0] = ['IntervalMinValue' => 0.0, 'IntervalMaxValue' => 10.0, 'ConversionFactor' => 1.0];
presentationEquivalenceAssert(EChartsJSLivePresentation::Compare($base, $other, 2)['status'] === 'equivalent', 'Exactly equal numeric interval fields may differ only in number type.');
$other['parameters']['INTERVALS'][0]['ConversionFactor'] = 10;
presentationEquivalenceAssert(EChartsJSLivePresentation::Compare($base, $other, 2)['status'] === 'different', 'Changed interval conversion factors must remain blocked.');

echo "JSLive presentation equivalence: foreign full plans, preserved originals, complete source guards and meaningful-change rejection verified.\n";
