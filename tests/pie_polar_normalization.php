<?php

declare(strict_types=1);

// Included by gateway_gauges.php; all source values and identities are synthetic.
(static function (): void
{
    $sources = [
        ['VariableID' => 4711, 'Label' => 'Temperature', 'Unit' => '°C', 'Decimals' => 1, 'UseVariablePresentation' => false, 'Minimum' => -20, 'Maximum' => 0],
        ['VariableID' => 4717, 'Label' => 'Humidity', 'Unit' => '%', 'Decimals' => 0, 'UseVariablePresentation' => false, 'Minimum' => 0, 'Maximum' => 100]
    ];
    $saved = [$GLOBALS['symconTestVariables'][4711]['Value'], $GLOBALS['symconTestVariables'][4717]['Value']];
    try {
        $GLOBALS['symconTestVariables'][4711]['Value'] = -10.0;
        $GLOBALS['symconTestVariables'][4717]['Value'] = 80.0;
        foreach ([
            [EChartsPie::class, 'GetPieData', 'pie'],
            [EChartsBarPolar::class, 'GetPolarData', 'polar']
        ] as [$class, $method, $key]) {
            $GLOBALS['symconTestVariables'][4711]['Value'] = -10.0;
            $GLOBALS['symconTestVariables'][4717]['Value'] = 80.0;
            $module = new $class();
            $module->Create();
            $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
            $module->SetTestProperty('EnableIPSView', true);
            $module->SetTestProperty('NormalizeValues', true);
            $module->ApplyChanges();
            $data = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $module->GetTestStatus() === IS_ACTIVE
                && $data[$key]['normalized'] === true
                && array_column($data['items'], 'value') === [50.0, 80.0]
                && array_column($data['items'], 'originalValue') === [-10.0, 80.0]
                && array_column($data['items'], 'unit') === ['°C', '%']
                && $data['items'][0]['minimum'] === -20.0
                && $data['items'][0]['maximum'] === 0.0,
                $class . ' must normalize geometry independently while retaining original values, units and ranges.'
            );
            assertGatewayGauge(
                str_contains($module->GetVisualizationTile(), '"normalized":true')
                && str_contains($module->GetIPSViewHTML(), '"normalized":true'),
                $class . ' must expose normalization to both renderers.'
            );
            $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $controls = array_column($form['elements'], null, 'name');
            assertGatewayGauge(
                str_contains($controls['NormalizeValues']['onChange'] ?? '', 'UpdateBarPreviewFromForm')
                && in_array('Minimum', array_column($controls['Sources']['columns'], 'name'), true)
                && in_array('Maximum', array_column($controls['Sources']['columns'], 'name'), true),
                $class . ' must offer source ranges and update unsaved normalization previews.'
            );
            $module->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => false, 'Sources' => [
                ...$sources
            ]], JSON_THROW_ON_ERROR));
            $module->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => [
                ...$sources
            ]], JSON_THROW_ON_ERROR));
            $updates = array_slice($module->GetTestFormUpdates(), -2);
            $draftModel = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR);
            $draftSvg = base64_decode(substr($updates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                count($updates) === 2 && $module->GetTestStatus() === IS_ACTIVE
                && $draftModel[$key]['normalized'] === true
                && is_string($draftSvg)
                && str_contains(strtolower($draftSvg), 'normal'),
                $class . ' must update both normalization draft previews without persisting properties.'
            );
            foreach ([[-50.0, 0.0], [10.0, 100.0]] as [$input, $expected]) {
                $GLOBALS['symconTestVariables'][4711]['Value'] = $input;
                $module->ApplyChanges();
                $clipped = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR);
                assertGatewayGauge(
                    $clipped['items'][0]['value'] === $expected
                    && $clipped['items'][0]['originalValue'] === $input,
                    $class . ' must clip only geometry for out-of-range originals.'
                );
            }
            $GLOBALS['symconTestVariables'][4711]['Value'] = -10.0;
            foreach ([
                ['Minimum' => 5, 'Maximum' => 5],
                ['Minimum' => 6, 'Maximum' => 5],
                ['Minimum' => '0', 'Maximum' => 100],
                ['Minimum' => -PHP_FLOAT_MAX, 'Maximum' => PHP_FLOAT_MAX]
            ] as $limits) {
                $invalid = $sources;
                $invalid[0] = array_replace($invalid[0], $limits);
                $module->SetTestProperty('Sources', json_encode($invalid, JSON_THROW_ON_ERROR));
                $module->ApplyChanges();
                assertGatewayGauge(
                    $module->GetTestStatus() === 201,
                    $class . ' must reject invalid active source normalization ranges.'
                );
            }
            $module->SetTestProperty('NormalizeValues', false);
            $module->ApplyChanges();
            assertGatewayGauge(
                $module->GetTestStatus() === 201,
                $class . ' must retain its legacy mixed-unit rejection when normalization is off.'
            );
            $legacy = $sources;
            $legacy[0]['Minimum'] = 10;
            $legacy[0]['Maximum'] = 0;
            $legacy[1]['Unit'] = '°C';
            $module->SetTestProperty('Sources', json_encode($legacy, JSON_THROW_ON_ERROR));
            $GLOBALS['symconTestVariables'][4711]['Value'] = 10.0;
            $module->ApplyChanges();
            $legacyData = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $module->GetTestStatus() === IS_ACTIVE
                && $legacyData[$key]['normalized'] === false
                && $legacyData['items'][0]['value'] === 10.0
                && !array_key_exists('originalValue', $legacyData['items'][0]),
                $class . ' must ignore disabled normalization ranges and preserve raw legacy models.'
            );
        }
    } finally {
        [$GLOBALS['symconTestVariables'][4711]['Value'], $GLOBALS['symconTestVariables'][4717]['Value']] = $saved;
    }
})();
