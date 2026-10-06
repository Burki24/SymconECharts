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
$timeSeriesRuntimePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/echarts.timeseries.min.js';
$licensePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/LICENSE.txt';
$noticePath = $root . '/libs/echarts/' . EChartsAsset::VERSION . '/NOTICE.txt';
$runtime = (string) file_get_contents($runtimePath);
$timeSeriesRuntime = (string) file_get_contents($timeSeriesRuntimePath);
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
assertEChartsAsset(is_file($timeSeriesRuntimePath), 'The pinned Time Series runtime is missing.');
assertEChartsAsset(
    hash_file('sha256', $timeSeriesRuntimePath) === EChartsAsset::TIME_SERIES_SHA256,
    'The pinned Time Series runtime checksum changed.'
);
assertEChartsAsset(
    EChartsAsset::TimeSeriesJavaScript() === $timeSeriesRuntime,
    'The asset loader changed the Time Series runtime.'
);
assertEChartsAsset(
    strlen($timeSeriesRuntime) < 614400 && str_contains($timeSeriesRuntime, 'window.echarts'),
    'The Time Series ECharts runtime must expose the browser API and remain below 600 KiB.'
);
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
assertEChartsAsset(EChartsAsset::ColorToHex(-1) === '#000000', 'Negative ECharts colors must be clamped.');
assertEChartsAsset(EChartsAsset::ColorToHex(0x6B4423) === '#6B4423', 'ECharts RGB conversion changed.');
assertEChartsAsset(EChartsAsset::ColorToHex(0xFFFFFF + 1) === '#FFFFFF', 'Large ECharts colors must be clamped.');
assertEChartsAsset(
    array_keys(EChartsAsset::ThemePalettes()) === $expectedThemeIDs,
    'Every supported ECharts theme must provide a readable renderer palette.'
);
foreach (EChartsAsset::ThemePalettes() as $themeID => $palette) {
    assertEChartsAsset(
        count($palette['seriesColors']) >= 8,
        'Every ECharts theme must expose enough series colors for all Time Series sources: ' . $themeID
    );
}
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
    EChartsAsset::ThemePreviewPalette('auto')['seriesColors'][0] === '#5070DD'
        && EChartsAsset::ThemePreviewPalette('dark')['seriesColors'][0] === '#4992FF',
    'The automatic and dark Time Series palettes must match the pinned ECharts runtime and theme.'
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
