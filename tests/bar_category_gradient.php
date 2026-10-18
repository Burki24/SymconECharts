<?php

declare(strict_types=1);

// Runs inside the Gateway fixture, with entirely synthetic variables.
(static function (): void
{
    $existingVariables = array_keys($GLOBALS['symconTestVariables']);
    $bar = new EChartsBarCategory();
    $bar->Create();
    $sources = [];
    foreach ([16.0, 100.0, -25.0, 0.0] as $index => $value) {
        $id = 88520 + $index;
        $GLOBALS['symconTestVariables'][$id] = [...$GLOBALS['symconTestVariables'][4711],
            'Name' => 'Gradient battery', 'Value' => $value];
        $sources[] = ['VariableID'    => $id, 'Category' => 'Room', 'Series' => 'Sensor ' . $index,
            'UseVariablePresentation' => false, 'Unit' => '%', 'Decimals' => 1, 'Color' => 0x123456];
    }
    $bar->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
    $bar->SetTestProperty('EnableIPSView', true);
    $bar->ApplyChanges();
    $model = json_decode($bar->GetBarData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge($model['bar']['style']['barFillMode'] === 'solid'
        && $model['items'][0]['color'] === '#123456', 'Existing Category Bar fills must remain the default.');
    $form = json_decode($bar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        findGaugeFormElement($form['elements'], 'ColorMinimum')['visible'] === false,
        'Solid bars must not expose inactive gradient controls.'
    );
    foreach (['', 'IPSView'] as $prefix) {
        foreach (['ColorMinimum', 'ColorMaximum', 'LowColor', 'MiddleColor', 'HighColor'] as $name) {
            $element = findGaugeFormElement($form['elements'], $prefix . $name);
            assertGatewayGauge(
                isset($element['onChange']) && str_contains($element['onChange'], 'ECBC_UpdateBarPreviewFromForm'),
                'Every gradient control must participate in the live preview.'
            );
        }
    }
    $bar->SetTestProperty('BarFillMode', 'gradient');
    $bar->SetTestProperty('BarSVG', '<svg><script>invalid but inactive</script></svg>');
    $bar->SetTestProperty('ColorMinimum', -0.25);
    $bar->SetTestProperty('ColorMaximum', 100.5);
    $bar->SetTestProperty('IPSViewUseTileDesign', false);
    $bar->SetTestProperty('IPSViewBarFillMode', 'gradient');
    $bar->SetTestProperty('IPSViewColorMaximum', 255.5);
    $bar->SetTestProperty('IPSViewLowColor', 0x112233);
    $bar->ApplyChanges();
    $style = json_decode($bar->GetBarData(), true, 512, JSON_THROW_ON_ERROR)['bar']['style'];
    assertGatewayGauge(
        $bar->GetTestStatus() === IS_ACTIVE && $style['colorMinimum'] === -0.25
        && $style['colorMaximum'] === 100.5 && $style['lowColor'] === '#FF0000',
        'Gradient ranges must retain fractional values and not import inactive SVG.'
    );
    $ips = $bar->GetIPSViewHTML();
    assertGatewayGauge(
        str_contains($ips, '"colorMaximum":255.5') && str_contains($ips, '"lowColor":"#112233"'),
        'IPSView must support an independent gradient range and colors.'
    );
    $form = json_decode($bar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    foreach (['', 'IPSView'] as $prefix) {
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], $prefix . 'ColorMinimum')['visible'] === true
            && findGaugeFormElement($form['elements'], $prefix . 'BarSVG')['visible'] === false,
            'Saved gradients must reveal their own controls, not SVG controls.'
        );
    }
    $bar->UpdateBarDesignForm(false, 'gradient');
    $visibility = array_slice($bar->GetTestFormUpdates(), -7);
    assertGatewayGauge(
        count(array_filter($visibility, static fn (array $update): bool => $update['Value'] === true)) === 5,
        'Selecting gradient must immediately reveal its five settings.'
    );
    $GLOBALS['symconTestCopyTarget'] = $bar;
    $bar->CopyTileDesignToIPSView();
    unset($GLOBALS['symconTestCopyTarget']);
    assertGatewayGauge(str_contains($bar->GetIPSViewHTML(), '"colorMinimum":-0.25')
        && str_contains($bar->GetIPSViewHTML(), '"colorMaximum":100.5'), 'Copying designs must not round gradient bounds.');
    $bar->SetTestProperty('IPSViewUseTileDesign', true);
    $form = json_decode($bar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        findGaugeFormElement($form['elements'], 'IPSViewColorMaximum')['enabled'] === false,
        'Inherited IPSView gradient controls must remain disabled.'
    );

    $bar->SetTestProperty('ColorMinimum', 0.0);
    $bar->SetTestProperty('ColorMaximum', 100.0);
    foreach (['simple', 'grouped', 'stacked'] as $mode) {
        foreach (['horizontal', 'vertical'] as $orientation) {
            $draft = ['BarMode' => $mode, 'Orientation' => $orientation, 'ColorMinimum' => 0.0,
                'ColorMaximum'  => 100.0, 'ValueLabelPosition' => 'inside'];
            $bar->UpdateBarPreviewFromForm(json_encode($draft, JSON_THROW_ON_ERROR));
            foreach (array_slice($bar->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(
                    is_string($svg) && str_contains($svg, 'gradientUnits="userSpaceOnUse"')
                    && str_contains($svg, 'fill="url(#bar-category-value-gradient)"')
                    && str_contains($svg, 'stop-color="#ff0000"') && str_contains($svg, 'stop-color="#ffff00"')
                    && str_contains($svg, 'stop-color="#00ff00"') && !str_contains($svg, 'NaN'),
                    'Both SVG previews must use the fixed signed axis ramp in every bar mode and orientation.'
                );
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg), 'Gradient previews must remain valid SVG.');
            }
        }
    }
    $bar->UpdateBarPreviewFromForm(json_encode(['Sources' => [$sources[0]], 'ColorMinimum' => 0.0,
        'ColorMaximum'                                    => 100.0, 'Orientation' => 'horizontal'], JSON_THROW_ON_ERROR));
    $svg = base64_decode(substr(array_slice($bar->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        str_contains($svg, 'offset="1" stop-color="#ff5200"') && !str_contains($svg, 'stop-color="#00ff00"'),
        'A 16-percent preview must end at the 16-percent ramp color, not restart a complete red-to-green ramp.'
    );
    assertGatewayGauge(
        \SymconECharts\EChartsBarPreview::RampColor(127.5, 0.0, 255.0, [0xFF0000, 0xFFFF00, 0x00FF00]) === '#ffff00',
        'The shared ramp must retain the LQI midpoint without rounding the range.'
    );
    foreach (['', 'IPSView'] as $prefix) {
        $bar->SetTestProperty('IPSViewUseTileDesign', false);
        $bar->SetTestProperty($prefix . 'ColorMinimum', 0.0);
        $bar->SetTestProperty($prefix . 'ColorMaximum', 100.0);
        foreach (['ColorMinimum' => [100.0, NAN, INF, -1.0e12 - 1],
            'ColorMaximum'       => [0.0, NAN, INF, 1.0e12 + 1], 'LowColor' => [-1, 0x1000000],
            'MiddleColor'        => [-1, 0x1000000], 'HighColor' => [-1, 0x1000000]] as $name => $invalidValues) {
            foreach ($invalidValues as $invalid) {
                $bar->SetTestProperty($prefix . $name, $invalid);
                assertGatewayGauge(
                    json_decode($bar->GetBarDiagnostic(), true)['valid'] === false,
                    'Invalid or nonfinite gradient settings must be rejected in either designer.'
                );
                $bar->SetTestProperty($prefix . $name, match ($name) {
                    'ColorMinimum' => 0.0, 'ColorMaximum' => 100.0, 'LowColor' => 0xFF0000,
                    'MiddleColor'  => 0xFFFF00, 'HighColor' => 0x00FF00
                });
            }
        }
    }
    assertGatewayGauge(
        json_decode($bar->GetBarDiagnostic(), true)['valid'] === true,
        'Correcting invalid gradient fields must restore a valid design.'
    );
    foreach (array_diff(array_keys($GLOBALS['symconTestVariables']), $existingVariables) as $id) {
        unset($GLOBALS['symconTestVariables'][$id]);
    }
})();
