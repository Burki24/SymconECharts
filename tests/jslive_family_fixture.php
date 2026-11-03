<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLivePlan.php';

/** Replace only reviewed inert bytes; no source code is executed. */
function jsliveFixtureCurrentTemplate(stdClass $packet, string $hash): void
{
    $packet->producer->libraryVersion = '0.113';
    $packet->producer->build = 1;
    foreach ($packet->assets as $asset) {
        if ($asset->kind !== 'inert-standard-template' || $asset->origin->template !== 'Progressbar') {
            continue;
        }
        $bytes = file_get_contents(__DIR__ . '/fixtures/jslive-templates/' . $hash . '.html');
        if (hash('sha256', $bytes) !== $hash) {
            throw new RuntimeException('Reviewed template fixture integrity changed.');
        }
        $previous = $asset->assetId;
        $asset->assetId = 'sha256:' . $hash;
        $asset->sha256 = $hash;
        $asset->byteLength = strlen($bytes);
        $asset->contentBase64 = base64_encode($bytes);
        foreach ($packet->charts as $chart) {
            $chart->assetRefs = array_map(static fn (string $id): string => $id === $previous ? $asset->assetId : $id, $chart->assetRefs);
        }
    }
}

/** Anonymous packages, with exact inert resource bytes restored only at the package boundary. */
function jsliveFamilyFixture(string $file): array
{
    $fixture = json_decode(file_get_contents($file), false, 32, JSON_THROW_ON_ERROR);
    foreach ($fixture->assets as $asset) {
        if (preg_match('/^[a-f0-9]{64}$/D', $asset->sha256) !== 1) {
            throw new RuntimeException('Invalid fixture asset.');
        }
        foreach (['font' => ['jslive-assets', '.woff2'], 'template' => ['jslive-templates', '.html']] as $kind => [$directory, $suffix]) {
            if ($asset->contentBase64 === '@fixture-' . $kind . ':' . $asset->sha256) {
                $asset->contentBase64 = base64_encode(file_get_contents(__DIR__ . '/fixtures/' . $directory . '/' . $asset->sha256 . $suffix));
            }
        }
    }
    $json = \SymconECharts\EChartsImportJson::Canonical($fixture);
    $package = \SymconECharts\EChartsJSLivePackage::Read($json);
    $metadata = [];
    foreach ($package->charts as $chart) {
        foreach ($chart->sources as $source) {
            $metadata[$source->variableId] = ['variableType' => $source->variableType, 'native' => $source->presentation->native,
                'variableAction'                             => $source->presentation->original->variable->VariableAction ?? null,
                'variableCustomAction'                       => $source->presentation->original->variable->VariableCustomAction ?? null,
                'archive'                                    => ['logging' => $source->archive->logging, 'aggregationType' => $source->archive->aggregationType,
                    'counterSettings'                                      => \SymconECharts\EChartsJSLivePackage::ArrayValue($source->archive->counterSettings)]];
        }
    }
    $reader = static function (array $query) use ($metadata): array
    {
        $type = $metadata[$query['VariableID']]['variableType'];
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
    return [$json, $package, $metadata, $reader];
}
