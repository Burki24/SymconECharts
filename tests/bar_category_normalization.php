<?php

declare(strict_types=1);

// Included by gateway_gauges.php; synthetic variables only.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    try {
        $chart = new EChartsBarCategory();
        $chart->Create();
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewOrientation', 'horizontal');
        $rows = [];
        foreach ([[-5.0, -10.0, 0.0, '°C', 1], [75.0, 0.0, 100.0, '%', 0], [300.0, 0.0, 255.0, 'LQI', 0]] as $index => $specification) {
            [$value, $minimum, $maximum, $unit, $decimals] = $specification;
            $id = 93200 + $index;
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => $value, 'Name' => 'Equal <label>'];
            $rows[] = ['VariableID'       => $id, 'Category' => 'Equal <label>', 'Series' => 'Equal <series>',
                'UseVariablePresentation' => false, 'Unit' => $unit, 'Decimals' => $decimals,
                'Minimum'                 => $minimum, 'Maximum' => $maximum];
        }
        $chart->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 201,
            'Raw Category Bar must retain its same-unit requirement.'
        );
        $chart->SetTestProperty('NormalizeValues', true);
        $chart->ApplyChanges();
        $result = json_decode($chart->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $result['bar']['normalized'] === true
            && $result['bar']['unit'] === '' && array_column($result['items'], 'value') === [50.0, 75.0, 100.0]
            && array_column($result['items'], 'originalValue') === [-5.0, 75.0, 300.0]
            && array_column($result['items'], 'unit') === ['°C', '%', 'LQI']
            && array_column($result['items'], 'decimals') === [1, 0, 0]
            && count(array_unique(array_column($result['items'], 'id'))) === 3,
            'Category Bar normalization must permit mixed units, preserve signed originals, clamp only geometry and retain source IDs.'
        );
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $document) {
            assertGatewayGauge(
                str_contains($document, '"normalized":true') && str_contains($document, '"originalValue":-5.0')
                && strlen($document) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both Category Bar outputs must carry the normalized model and original metadata below the output limit.'
            );
        }
        $chart->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $rows], JSON_THROW_ON_ERROR));
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')));
            assertGatewayGauge(
                str_contains($svg, 'Normalized 0–100 per source') && str_contains($svg, '-5.0 °C')
                && str_contains($svg, 'Equal &lt;label&gt;') && !str_contains($svg, '93200'),
                'Normalized Tile and IPSView previews must mark the geometry and keep escaped original labels/values without IDs.'
            );
        }
        $invalidRows = $rows;
        $invalidRows[0]['Maximum'] = -10.0;
        $chart->SetTestProperty('Sources', json_encode($invalidRows, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 201,
            'An active normalization range must have finite ordered bounds.'
        );
        $chart->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $invalidRows], JSON_THROW_ON_ERROR));
        $update = array_slice($chart->GetTestFormUpdates(), -2)[0];
        assertGatewayGauge(
            str_contains(base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,'))), 'Invalid normalization range.'),
            'Invalid normalization drafts must show a bounded explanatory preview, not throw.'
        );
        $nativeRows = $rows;
        $nativeRows[0]['UseVariablePresentation'] = true;
        $nativeRows[0]['Unit'] = 'manual fallback';
        $GLOBALS['symconTestVariables'][93200]['Presentation'] = ['MIN' => -40.0, 'MAX' => 80.0, 'SUFFIX' => ' °C', 'DIGITS' => 2];
        $chart->SetTestProperty('Sources', json_encode($nativeRows, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $native = json_decode($chart->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $native['items'][0]['value'] === 50.0 && $native['items'][0]['unit'] === '°C'
            && $native['items'][0]['decimals'] === 2 && $native['items'][0]['minimum'] === -10.0 && $native['items'][0]['maximum'] === 0.0,
            'Native original formatting must not replace the explicitly configured normalization bounds.'
        );
        $sameUnit = $rows;
        foreach ($sameUnit as &$row) {
            $row['Unit'] = '°C';
            $row['Minimum'] = 'unused';
            $row['Maximum'] = null;
        }
        unset($row);
        $chart->SetTestProperty('NormalizeValues', false);
        $chart->SetTestProperty('Sources', json_encode($sameUnit, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $raw = json_decode($chart->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $raw['bar']['normalized'] === false
            && $raw['bar']['unit'] === '°C' && $raw['items'][0]['value'] === -5.0,
            'Inactive ranges must not invalidate legacy signed Category Bar values.'
        );
        $largeRows = [];
        for ($index = 0; $index < 100; ++$index) {
            $id = 93300 + $index;
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => (float) $index, 'Name' => 'Source ' . $index];
            $largeRows[] = ['VariableID'  => $id, 'Category' => 'Category ' . $index,
                'UseVariablePresentation' => false, 'Unit' => $index % 2 === 0 ? '%' : 'LQI'];
        }
        $chart->SetTestProperty('NormalizeValues', true);
        $chart->SetTestProperty('Sources', json_encode($largeRows, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $result = json_decode($chart->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($result['items']) === 100 && $result['items'][99]['minimum'] === 0.0
            && $result['items'][99]['maximum'] === 100.0,
            'Normalization must retain all 100 sources and provide explicit default ranges for old rows.'
        );
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $document) {
            assertGatewayGauge(
                strlen($document) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'A hundred mixed-unit normalized Category Bar sources must fit both output buffers.'
            );
        }
        foreach ($largeRows as $index => &$row) {
            $row['Category'] = str_pad('Sensor ' . $index . ' <&" ', 120, 'X');
            $row['Series'] = str_repeat('S', 120);
        }
        unset($row);
        $pattern = '<svg viewBox="0 0 10 10">' . str_repeat('<path d="' . str_repeat('M0 0L10 10 ', 5940) . '"/>', 4) . '</svg>';
        $chart->SetTestProperty('Sources', json_encode($largeRows, JSON_THROW_ON_ERROR));
        foreach (['', 'IPSView'] as $prefix) {
            $chart->SetTestProperty($prefix . 'BarFillMode', 'svg');
            $chart->SetTestProperty($prefix . 'BarSVG', $pattern);
        }
        $chart->ApplyChanges();
        $stressSizes = [];
        foreach (['Tile' => $chart->GetVisualizationTile(), 'IPSView' => $chart->GetIPSViewHTML(), 'form' => $chart->GetConfigurationForm()] as $kind => $document) {
            $stressSizes[$kind] = strlen($document);
            assertGatewayGauge(
                strlen($document) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Normalization metadata with 100 long labels and near-limit SVGs must fit the ' . $kind . ' buffer: ' . strlen($document)
            );
        }
        echo 'Category Bar normalized stress bytes: ' . json_encode($stressSizes, JSON_THROW_ON_ERROR) . "\n";
    } finally {
        $GLOBALS['symconTestVariables'] = $savedVariables;
    }
})();

echo "Category Bar normalization integration verified.\n";
