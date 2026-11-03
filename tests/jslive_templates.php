<?php

declare(strict_types=1);

require_once __DIR__ . '/jslive_family_fixture.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLivePlan;

function templateAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

// Exact, reviewed source bytes. The changed Progressbar template merely adds
// the text-preset/image fill fallback already implemented in the native model.
$hashes = ['6de5f11322b58a5a316941b020fccdd73b4f32beb92dfcc88e39970b49105fd1',
    'dcbda0b9487f1afdc689091de5c4542f6c37a5032a4483684103f40c5f36cc66'];
$context = ['gatewayID' => 16000, 'parentID' => 15000, 'fingerprint' => str_repeat('c', 64)];
$count = 0;
foreach (glob(__DIR__ . '/fixtures/jslive-packages/*.json') as $file) {
    [$json, $packet, $metadata, $reader] = jsliveFamilyFixture($file);
    $source = static fn (int $id, ?int $archive): array => $metadata[$id];
    $old = EChartsJSLivePlan::Prepare($json, [], $context, $source, $reader, $packet->sourceInstallation->id, 1793448000);
    $oldChart = array_values($old['charts'])[0];
    foreach ($hashes as $hash) {
        $fresh = unserialize(serialize($packet));
        jsliveFixtureCurrentTemplate($fresh, $hash);
        $raw = EChartsImportJson::Canonical($fresh);
        $plan = EChartsJSLivePlan::Prepare($raw, [], $context, $source, $reader, $fresh->sourceInstallation->id, 1793448000);
        $chart = array_values($plan['charts'])[0];
        templateAssert(!$chart['blocked'], basename($file) . ': current source template blocked: ' . EChartsImportJson::Canonical($chart['issues']));
        templateAssert($chart['target'] === $oldChart['target'] && $chart['revision'] === $oldChart['revision'], 'Reviewed source maintenance must not create a different native model or target revision.');
        templateAssert($raw === EChartsImportJson::Canonical($fresh), 'Planning modified the current export.');
        if ($chart['family'] === 'progress') {
            $asset = array_values(array_filter($fresh->assets, static fn (object $asset): bool => $asset->kind === 'inert-standard-template'))[0];
            $asset->contentBase64 = base64_encode(base64_decode($asset->contentBase64, true) . '<script>unexpected()</script>');
            $asset->sha256 = hash('sha256', base64_decode($asset->contentBase64, true));
            $asset->byteLength = strlen(base64_decode($asset->contentBase64, true));
            $previous = $asset->assetId;
            $asset->assetId = 'sha256:' . $asset->sha256;
            $fresh->charts[0]->assetRefs = array_map(static fn (string $id): string => $id === $previous ? $asset->assetId : $id, $fresh->charts[0]->assetRefs);
            $bad = EChartsJSLivePlan::Prepare(EChartsImportJson::Canonical($fresh), [], $context, $source, $reader, $fresh->sourceInstallation->id, 1793448000);
            $badChart = array_values($bad['charts'])[0];
            templateAssert($badChart['blocked'] && in_array('CUSTOM_CODE_INERT', array_column($badChart['issues'], 'code'), true), 'An unknown template must remain blocked even with valid integrity and a current producer version.');
        }
        ++$count;
    }
}
templateAssert($count === 80, 'All forty families need both current template byte variants.');
echo "JSLive 0.113 templates: 80 plans, unchanged native revisions, exact LF/CRLF provenance and unknown-template rejection verified.\n";
