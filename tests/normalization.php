<?php

declare(strict_types=1);

use SymconECharts\EChartsCurrentComparisonSources;
use SymconECharts\EChartsNormalization;

function assertNormalization(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$normalizer = __DIR__ . '/../libs/EChartsNormalization.php';
assertNormalization(is_file($normalizer), 'Current comparisons need the explicit shared Min/Max normalization contract.');
require_once $normalizer;
require_once __DIR__ . '/../libs/EChartsCurrentComparisonSources.php';

assertNormalization(EChartsNormalization::Range([]) === [0.0, 100.0], 'Old source rows retain additive 0–100 defaults.');
assertNormalization(\SymconECharts\EChartsValueRange::IsValid(-10.5, 40)
    && \SymconECharts\EChartsValueRange::IsValid(0.0001, 0.0002)
    && !\SymconECharts\EChartsValueRange::IsValid(false, 100)
    && !\SymconECharts\EChartsValueRange::IsValid('0', 100)
    && !\SymconECharts\EChartsValueRange::IsValid(-INF, 100)
    && !\SymconECharts\EChartsValueRange::IsValid(0, NAN), 'Shared range validation keeps numeric type, finite bounds and strict ordering.');
$range = ['Minimum' => -20, 'Maximum' => 40];
foreach ([-100 => 0.0, -20 => 0.0, -5 => 25.0, 10 => 50.0, 40 => 100.0, 90 => 100.0] as $raw => $expected) {
    assertNormalization(
        EChartsNormalization::Value((float) $raw, $range) === $expected,
        'Normalization uses the configured range and clips only the projected geometry.'
    );
    assertNormalization(
        EChartsNormalization::Metadata((float) $raw, $range)
        === ['originalValue' => (float) $raw, 'minimum' => -20.0, 'maximum' => 40.0],
        'Unclipped originals and configured ranges remain available to every output.'
    );
}
foreach ([['Minimum' => null], ['Minimum'        => '0'], ['Maximum' => false], ['Minimum' => 4, 'Maximum' => 4],
    ['Minimum' => 4, 'Maximum' => 3], ['Minimum' => -INF], ['Maximum' => NAN],
    ['Minimum' => -PHP_FLOAT_MAX, 'Maximum' => PHP_FLOAT_MAX],
    ['Minimum' => PHP_INT_MAX - 1, 'Maximum' => PHP_INT_MAX]] as $invalid) {
    try {
        EChartsNormalization::Range($invalid);
        throw new LogicException('Malformed normalization ranges must not fall back to defaults.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([INF, -INF, NAN] as $invalid) {
    try {
        EChartsNormalization::Value($invalid, []);
        throw new LogicException('Non-finite original values must be rejected.');
    } catch (InvalidArgumentException) {
    }
}
assertNormalization(
    EChartsNormalization::Value(0.0, ['Minimum' => -1.0e300, 'Maximum' => 1.0e300]) === 50.0,
    'Large finite valid spans must not overflow during scaling.'
);
assertNormalization(
    EChartsNormalization::Value(0.00015, ['Minimum' => 0.0001, 'Maximum' => 0.0002]) > 49.9999,
    'Fractional ranges remain meaningful.'
);

// Synthetic sources only, independent of a live Symcon installation.
function IPS_VariableExists(int $id): bool
{
    return in_array($id, [81001, 81002], true);
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2];
}
function IPS_GetName(int $id): string
{
    return 'Same label';
}
function IPS_GetVariablePresentation(int $id): array
{
    return ['SUFFIX' => $id === 81001 ? '°C' : '%', 'DIGITS' => $id === 81001 ? 2 : 0];
}
$json = json_encode([['VariableID' => 81001, 'Minimum' => -20, 'Maximum' => 40],
    ['VariableID' => 81002, 'Minimum' => 0, 'Maximum' => 100]], JSON_THROW_ON_ERROR);
$resolved = EChartsCurrentComparisonSources::Resolve($json, 'PictorialBar', 100, true);
assertNormalization(
    array_column($resolved, 'VariableID') === [81001, 81002]
    && array_column($resolved, 'Label') === ['Same label', 'Same label']
    && array_column($resolved, 'Unit') === ['°C', '%']
    && array_column($resolved, 'Decimals') === [2, 0]
    && $resolved[0]['Minimum'] === -20.0 && $resolved[1]['Maximum'] === 100.0,
    'Normalized comparison sources retain ID identity, original units, precision and explicit ranges.'
);
try {
    EChartsCurrentComparisonSources::Resolve($json, 'PictorialBar', 100);
    throw new LogicException('Mixed raw units must remain invalid without normalization.');
} catch (InvalidArgumentException) {
}
$inactive = json_encode([['VariableID' => 81001, 'Minimum' => 'not active', 'Maximum' => null]], JSON_THROW_ON_ERROR);
assertNormalization(
    count(EChartsCurrentComparisonSources::Resolve($inactive, 'Funnel')) === 1,
    'Inactive range fields do not change Funnel or legacy current comparisons.'
);

echo "Explicit Min/Max normalization and compatible current sources verified.\n";
