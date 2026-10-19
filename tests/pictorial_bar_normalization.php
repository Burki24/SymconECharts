<?php

declare(strict_types=1);

(static function (): void
{
    $variablesBefore = array_keys($GLOBALS['symconTestVariables']);
    $originalVariables = [88901 => $GLOBALS['symconTestVariables'][88901] ?? null, 88902 => $GLOBALS['symconTestVariables'][88902] ?? null];
    $normalizedPict = new TestableEChartsPictorialBar();
    $normalizedPict->Create();
    $normalizationSources = [
        ['VariableID' => 88901, 'Label' => 'Temperature', 'Minimum' => -20, 'Maximum' => 10],
        ['VariableID' => 88902, 'Label' => 'Humidity', 'Minimum' => 0, 'Maximum' => 100]
    ];
    $GLOBALS['symconTestVariables'][88901] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                              => -5.0, 'Name' => 'Temperature', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
    $GLOBALS['symconTestVariables'][88902] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                              => 75.0, 'Name' => 'Humidity', 'Presentation' => ['SUFFIX' => ' %', 'DIGITS' => 0]];
    $normalizedPict->SetTestProperty('Sources', json_encode($normalizationSources, JSON_THROW_ON_ERROR));
    $normalizedPict->SetTestProperty('NormalizeValues', true);
    $normalizedPict->SetTestProperty('ScaleMaximum', 255);
    $normalizedPict->SetTestProperty('EnableIPSView', true);
    $normalizedPict->ApplyChanges();
    $normalizationData = json_decode($normalizedPict->GetPictorialBarData(), true, 512, JSON_THROW_ON_ERROR)['pictorialBar'];
    assertGatewayGauge(
        $normalizedPict->GetTestStatus() === IS_ACTIVE && $normalizationData['normalized'] === true
        && $normalizationData['unit'] === '' && $normalizationData['data'][0]['value'] === 50.0
        && $normalizationData['data'][1]['value'] === 75.0 && $normalizationData['data'][0]['originalValue'] === -5.0
        && $normalizationData['data'][0]['minimum'] === -20.0 && $normalizationData['data'][0]['maximum'] === 10.0
        && $normalizationData['data'][0]['unit'] === '°C' && $normalizationData['data'][1]['unit'] === '%'
        && $normalizationData['style']['scaleMaximum'] === 255,
        'PictorialBar normalization must project distinct source ranges while preserving original display metadata and saved design.'
    );
    assertGatewayGauge(
        str_contains($normalizedPict->GetVisualizationTile(), '"normalized":true')
        && str_contains($normalizedPict->GetIPSViewHTML(), '"normalized":true'),
        'Both PictorialBar outputs must carry normalized comparison metadata.'
    );
    $GLOBALS['symconTestVariables'][88901]['Value'] = -50.0;
    $GLOBALS['symconTestVariables'][88902]['Value'] = 150.0;
    $clippedNormalization = json_decode($normalizedPict->GetPictorialBarData(), true, 512, JSON_THROW_ON_ERROR)['pictorialBar']['data'];
    assertGatewayGauge(
        $clippedNormalization[0]['value'] === 0.0 && $clippedNormalization[0]['originalValue'] === -50.0
        && $clippedNormalization[1]['value'] === 100.0 && $clippedNormalization[1]['originalValue'] === 150.0,
        'Normalized geometry must clamp beyond source limits without altering originals.'
    );
    $GLOBALS['symconTestVariables'][88901]['Value'] = -5.0;
    $GLOBALS['symconTestVariables'][88902]['Value'] = 75.0;
    $normalizationProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($normalizedPict);
    $normalizationQueries = $GLOBALS['symconTestArchiveQueryCount'];
    $normalizedPict->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $normalizationSources,
        'SymbolRepeat'                                                       => false, 'ScaleMaximum' => 255, 'IPSViewUseTileDesign' => false,
        'IPSViewSymbolRepeat'                                                => false, 'IPSViewScaleMaximum' => 2], JSON_THROW_ON_ERROR));
    foreach (array_slice($normalizedPict->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            str_contains($svg, 'Normalized preview (0–100)') && str_contains($svg, '-5.0 °C')
            && str_contains($svg, '75 %') && !str_contains($svg, 'Preview requires'),
            'Normalized draft previews must accept mixed signed sources and identify the normalized comparison while labeling original values.'
        );
    }
    assertGatewayGauge($normalizationProperties === (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($normalizedPict)
        && $normalizationQueries === $GLOBALS['symconTestArchiveQueryCount'], 'Normalized previews must remain read-only and archive-free.');
    $invalidNormalizationSources = $normalizationSources;
    $invalidNormalizationSources[1]['Maximum'] = 0;
    $normalizedPict->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $invalidNormalizationSources], JSON_THROW_ON_ERROR));
    foreach (array_slice($normalizedPict->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(str_contains($svg, 'Preview requires valid numeric values and source Min/Max.'), 'Invalid normalization draft limits must fail visibly.');
    }
    $normalizedPict->SetTestProperty('Sources', json_encode($invalidNormalizationSources, JSON_THROW_ON_ERROR));
    $normalizedPict->ApplyChanges();
    assertGatewayGauge($normalizedPict->GetTestStatus() === 201, 'Equal normalization limits must reject the source configuration.');
    $normalizedPict->SetTestProperty('NormalizeValues', false);
    $normalizedPict->SetTestProperty('Sources', json_encode([$invalidNormalizationSources[1]], JSON_THROW_ON_ERROR));
    $normalizedPict->ApplyChanges();
    assertGatewayGauge(
        $normalizedPict->GetTestStatus() === IS_ACTIVE
        && json_decode($normalizedPict->GetPictorialBarData(), true)['pictorialBar']['data'][0]['value'] === 75.0,
        'Inactive normalization ignores its range fields and retains legacy raw geometry.'
    );
    $normalizedPict->SetTestProperty('Sources', json_encode($normalizationSources, JSON_THROW_ON_ERROR));
    $normalizedPict->ApplyChanges();
    assertGatewayGauge($normalizedPict->GetTestStatus() === 201, 'Inactive normalization must still reject mixed units.');
    foreach (array_diff(array_keys($GLOBALS['symconTestVariables']), $variablesBefore) as $variableID) {
        unset($GLOBALS['symconTestVariables'][$variableID]);
    }
    foreach ($originalVariables as $variableID => $variable) {
        if ($variable !== null) {
            $GLOBALS['symconTestVariables'][$variableID] = $variable;
        }
    }
})();

echo "PictorialBar normalization integration verified.\n";
