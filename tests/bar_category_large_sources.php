<?php

declare(strict_types=1);

// Synthetic fixtures only: source IDs and names are not copied from an installation.
$categoryExistingVariables = array_keys($GLOBALS['symconTestVariables']);
$largeCategorySources = [];
for ($index = 0; $index < 101; ++$index) {
    $id = 88400 + $index;
    $GLOBALS['symconTestVariables'][$id] = [...$GLOBALS['symconTestVariables'][4711],
        'Name' => 'Battery', 'Value' => $index === 99 ? -250.0 : (float) ($index + 1)];
    $largeCategorySources[] = ['VariableID' => $id, 'Category' => 'Battery', 'Series' => 'Charge',
        'UseVariablePresentation'           => false, 'Unit' => '%', 'Decimals' => 1, 'Color' => -1];
}
$largeCategory = new EChartsBarCategory();
$largeCategory->Create();
$largeCategory->SetTestProperty('EnableIPSView', true);
foreach ([72, 100] as $count) {
    $largeCategory->SetTestProperty('Sources', json_encode(array_slice($largeCategorySources, 0, $count), JSON_THROW_ON_ERROR));
    $largeCategory->ApplyChanges();
    assertGatewayGauge($largeCategory->GetTestStatus() === IS_ACTIVE, 'Category Bar must accept up to 100 sources.');
    $data = json_decode($largeCategory->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        count($data['items']) === $count && count($largeCategory->GetTestReferences()) === $count,
        'Every configured Category Bar source must remain in the model and references.'
    );
    assertGatewayGauge(
        count(array_unique(array_column($data['items'], 'id'))) === $count,
        'Equal category and series names must not merge source identities.'
    );
    foreach ([$largeCategory->GetVisualizationTile(), $largeCategory->GetIPSViewHTML(), $largeCategory->GetConfigurationForm()] as $output) {
        assertGatewayGauge(strlen($output) < 1048576, 'Large Category Bar outputs must fit the Symcon output buffer.');
    }
    $largeCategory->UpdateBarPreviewFromForm('{}');
    foreach (array_slice($largeCategory->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            is_string($svg) && str_contains($svg, 'Batte')
            && preg_match('/data-preview-sources="([1-9][0-9]*)\/' . $count . '"/', $svg, $shown)
            && (int) $shown[1] < $count && !str_contains($svg, 'Example data'),
            'Large Category Bar previews must show a disclosed subset of actual sources, not example fallback.'
        );
    }
}
$previewSpans = [];
$previewCounts = [];
foreach (['simple', 'grouped', 'stacked'] as $mode) {
    foreach (['horizontal', 'vertical'] as $orientation) {
        foreach ([20, 100] as $width) {
            $largeCategory->UpdateBarPreviewFromForm(json_encode(['BarMode' => $mode, 'Orientation' => $orientation,
                'IPSViewUseTileDesign'                                      => false, 'IPSViewBarMode' => $mode, 'IPSViewOrientation' => $orientation,
                'BarWidthPercent'                                           => $width, 'IPSViewBarWidthPercent' => $width], JSON_THROW_ON_ERROR));
            foreach (array_slice($largeCategory->GetTestFormUpdates(), -2) as $outputIndex => $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                preg_match('/data-preview-sources="(\d+)\/100"/', $svg, $shown);
                preg_match('/data-preview-category-span="([\d.]+)"/', $svg, $span);
                assertGatewayGauge(isset($shown[1], $span[1]), 'Density previews must disclose the actual source subset and category span.');
                $previewSpans[$mode][$orientation][$width][$outputIndex] = (float) $span[1];
                $previewCounts[$mode][$orientation][$width][$outputIndex] = (int) $shown[1];
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg), 'Density previews must remain valid SVG.');
            }
        }
        foreach ([0, 1] as $outputIndex) {
            assertGatewayGauge(
                $previewCounts[$mode][$orientation][20][$outputIndex] > $previewCounts[$mode][$orientation][100][$outputIndex],
                'Thinner bars must disclose more real sources in either preview and orientation.'
            );
        }
    }
}
foreach (['horizontal', 'vertical'] as $orientation) {
    assertGatewayGauge(
        $previewSpans['simple'][$orientation][20][0] < $previewSpans['simple'][$orientation][100][0],
        'SVG category centers, not only the painted bar thickness, must move closer together.'
    );
}
$largeCategory->UpdateBarPreviewFromForm('{"Orientation":"horizontal","BarWidthPercent":20,"IPSViewUseTileDesign":false,"IPSViewOrientation":"horizontal","IPSViewBarWidthPercent":100}');
$independentPreviews = array_map(static fn (array $update): string => base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true), array_slice($largeCategory->GetTestFormUpdates(), -2));
preg_match('/data-preview-sources="(\d+)\/100"/', $independentPreviews[0], $tileCount);
preg_match('/data-preview-sources="(\d+)\/100"/', $independentPreviews[1], $ipsCount);
assertGatewayGauge((int) $tileCount[1] > (int) $ipsCount[1], 'Independent preview density must use its own width, not the Tile width.');
$largeCategory->UpdateBarPreviewFromForm('{"Orientation":"horizontal","BarWidthPercent":20,"AxisFontSizePercent":200,"ValueFontSizePercent":200}');
$largeTextPreview = base64_decode(substr(array_slice($largeCategory->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
preg_match('/data-preview-sources="(\d+)\/100"/', $largeTextPreview, $largeTextCount);
assertGatewayGauge((int) $largeTextCount[1] < (int) $tileCount[1], 'Preview fonts must limit the density before labels overlap.');
assertGatewayGauge(
    json_decode($largeCategory->GetBarData(), true)['bar']['style']['barWidthPercent'] === 70,
    'Density previews must not persist unsaved width or font settings.'
);
assertGatewayGauge($data['items'][99]['value'] === -250.0, 'Category Bar must retain signed offscreen source values.');
$largeCategory->SetTestProperty('BarFillMode', 'gradient');
foreach ([$largeCategory->GetVisualizationTile(), $largeCategory->GetIPSViewHTML()] as $output) {
    assertGatewayGauge(
        strlen($output) < 1048576 && str_contains($output, '"barFillMode":"gradient"'),
        'All 100 sources with inherited gradients must fit both output buffers.'
    );
}
$largeCategory->SetTestProperty('BarFillMode', 'solid');
$largeCategory->UpdateBarPreviewFromForm('{"SortOrder":"ascending"}');
$sortedCategoryPreview = base64_decode(substr(array_slice($largeCategory->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($sortedCategoryPreview) && str_contains($sortedCategoryPreview, '-250'),
    'Category Bar preview ordering must include every source before selecting its disclosed six-source subset.'
);
$largeCategoryPattern = '<svg viewBox="0 0 10 10">' . str_repeat('<path d="' . str_repeat('M0 0L10 10 ', 5940) . '"/>', 4) . '</svg>';
assertGatewayGauge(
    strlen($largeCategoryPattern) > 250000 && strlen($largeCategoryPattern) < 262144,
    'The output-budget regression must exercise a near-limit SVG, not a tiny sanitized placeholder.'
);
$largeCategoryStressSources = array_slice($largeCategorySources, 0, 100);
foreach ($largeCategoryStressSources as $index => &$source) {
    $source['Category'] = str_pad('Sensor ' . $index . ' <&" ', 120, 'X');
    $source['Series'] = str_repeat('S', 120);
}
unset($source);
$largeCategory->SetTestProperty('Sources', json_encode($largeCategoryStressSources, JSON_THROW_ON_ERROR));
$largeCategory->SetTestProperty('BarFillMode', 'svg');
$largeCategory->SetTestProperty('BarSVG', $largeCategoryPattern);
$largeCategory->SetTestProperty('EChartsTheme', 'dark');
$largeCategory->SetTestProperty('IPSViewUseTileDesign', false);
$largeCategory->SetTestProperty('IPSViewBarFillMode', 'svg');
$largeCategory->SetTestProperty('IPSViewBarSVG', $largeCategoryPattern);
$largeCategory->SetTestProperty('IPSViewEChartsTheme', 'macarons');
$largeCategory->ApplyChanges();
assertGatewayGauge($largeCategory->GetTestStatus() === IS_ACTIVE, '100 long source labels plus large SVG patterns remain valid.');
foreach (['tile' => $largeCategory->GetVisualizationTile(), 'ipsview' => $largeCategory->GetIPSViewHTML(), 'form' => $largeCategory->GetConfigurationForm()] as $kind => $output) {
    assertGatewayGauge(strlen($output) < 1048576, '100 long labels and near-limit SVGs must fit the output buffer (' . $kind . ': ' . strlen($output) . ').');
}
$largeCategory->SetTestProperty('BarFillMode', 'solid');
$largeCategory->SetTestProperty('IPSViewBarFillMode', 'solid');
$largeCategory->SetTestProperty('Sources', json_encode($largeCategorySources, JSON_THROW_ON_ERROR));
$largeCategory->ApplyChanges();
assertGatewayGauge($largeCategory->GetTestStatus() === 201, 'Category Bar must reject 101 sources.');
foreach ($largeCategorySources as $source) {
    unset($GLOBALS['symconTestVariables'][$source['VariableID']]);
}
foreach (array_diff(array_keys($GLOBALS['symconTestVariables']), $categoryExistingVariables) as $variableID) {
    unset($GLOBALS['symconTestVariables'][$variableID]);
}
unset($largeCategory, $largeCategorySources, $largeCategoryStressSources, $largeCategoryPattern, $data, $output, $svg, $update, $sortedCategoryPreview,
    $previewCounts, $previewSpans, $independentPreviews, $largeTextPreview);
gc_collect_cycles();
