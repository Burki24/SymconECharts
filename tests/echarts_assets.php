<?php

declare(strict_types=1);

use SymconECharts\EChartsAsset;

require_once dirname(__DIR__) . '/libs/EChartsAsset.php';

function assertEChartsAsset(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$runtimePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/echarts.gauge.min.js';
$licensePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/LICENSE.txt';
$noticePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/NOTICE.txt';
$runtime = (string) file_get_contents($runtimePath);
$runtimeSource = (string) file_get_contents($root . '/.tools/echarts-runtime/src/gauge-runtime.js');
$windowsCheckoutRuntime = str_replace("\n", "\r\n", $runtime);
$expectedThemeIDs = ['auto', 'dark', 'vintage', 'macarons', 'infographic', 'shine', 'roma'];

assertEChartsAsset(is_file($runtimePath), 'The pinned Apache ECharts runtime is missing.');
assertEChartsAsset(
    hash_file('sha256', $runtimePath) === EChartsAsset::SHA256,
    'The pinned Apache ECharts runtime checksum changed.'
);
assertEChartsAsset(EChartsAsset::JavaScript() === $runtime, 'The asset loader changed the runtime.');
assertEChartsAsset(
    strlen($runtime) < 524288,
    'The Gauge-specific ECharts runtime must remain below 512 KiB.'
);
assertEChartsAsset(str_contains($runtime, 'window.echarts'), 'The Gauge runtime must expose the ECharts browser API.');
assertEChartsAsset(
    str_contains($runtimeSource, 'GraphicComponent'),
    'The Gauge runtime must include the native ECharts Graphic Component used by dial plates.'
);
assertEChartsAsset(
    hash('sha256', $windowsCheckoutRuntime) !== EChartsAsset::SHA256,
    'The Windows-checkout regression fixture must differ byte-for-byte.'
);
assertEChartsAsset(
    EChartsAsset::HasExpectedIntegrity($windowsCheckoutRuntime),
    'The loader must accept the pinned runtime after a Windows CRLF checkout.'
);
assertEChartsAsset(
    !EChartsAsset::HasExpectedIntegrity($windowsCheckoutRuntime . 'changed'),
    'The loader must reject content changes beyond line-ending normalization.'
);
assertEChartsAsset(str_contains((string) file_get_contents($licensePath), 'Apache License'), 'ECharts license is missing.');
assertEChartsAsset(str_contains((string) file_get_contents($noticePath), 'Apache ECharts'), 'ECharts NOTICE is missing.');

assertEChartsAsset(EChartsAsset::ThemeIDs() === $expectedThemeIDs, 'The supported ECharts theme catalog changed.');
assertEChartsAsset(
    array_keys(EChartsAsset::ThemePalettes()) === $expectedThemeIDs,
    'Every supported ECharts theme must provide a readable renderer palette.'
);
$themeBundle = EChartsAsset::ThemeJavaScript();
foreach (array_slice($expectedThemeIDs, 1) as $themeID) {
    $themePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/themes/' . $themeID . '.js';
    assertEChartsAsset(is_file($themePath), 'The pinned Apache ECharts theme is missing: ' . $themeID);
    assertEChartsAsset(
        hash_file('sha256', $themePath) === EChartsAsset::THEME_SHA256[$themeID],
        'The pinned Apache ECharts theme checksum changed: ' . $themeID
    );
    assertEChartsAsset(
        str_contains($themeBundle, "registerTheme('" . $themeID . "'"),
        'The ECharts theme bundle must register: ' . $themeID
    );
    $themeContent = (string) file_get_contents($themePath);
    assertEChartsAsset(
        EChartsAsset::HasExpectedThemeIntegrity($themeID, str_replace("\n", "\r\n", $themeContent)),
        'The theme loader must tolerate Windows line endings: ' . $themeID
    );
}
assertEChartsAsset(
    strlen($runtime) + strlen($themeBundle) < 524288,
    'The Gauge runtime and all official themes must remain below 512 KiB.'
);
assertEChartsAsset(
    EChartsAsset::ThemePreviewPalette('auto')['accent'] === '#55CBB5',
    'The automatic Symcon theme preview palette changed.'
);
assertEChartsAsset(
    EChartsAsset::ThemePreviewPalette('vintage')['background'] === '#FEF8EF',
    'The Vintage preview must use the official theme background.'
);
assertEChartsAsset(
    EChartsAsset::ThemePreviewPalette('shine')['gaugeAxisLine'] === [
        [0.2, '#2B821D'],
        [0.8, '#005EAA'],
        [1.0, '#C12E34']
    ],
    'The Shine preview must use the official segmented Gauge axis line.'
);
assertEChartsAsset(
    !EChartsAsset::HasExpectedThemeIntegrity('vintage', (string) file_get_contents(
        $root . '/libs/echarts/' . EChartsAsset::VERSION . '/themes/vintage.js'
    ) . 'changed'),
    'The theme loader must reject content changes beyond line-ending normalization.'
);

echo 'Pinned Apache ECharts ' . EChartsAsset::VERSION . " assets verified.\n";
