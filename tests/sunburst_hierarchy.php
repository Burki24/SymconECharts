<?php

declare(strict_types=1);

// Included by gateway_gauges.php; every variable and label is synthetic.
(static function (): void
{
    $sources = [
        ['VariableID' => 4711, 'Group' => 'Legacy / literal', 'Subgroup' => 'Legacy subgroup', 'Label' => 'Same'],
        ['VariableID'   => 4717, 'Group' => 'Unchanged', 'Subgroup' => 'Unchanged',
            'GroupPath' => 'House / Floor / Room / Trade / Circuit / Device / Channel', 'Label' => 'Same']
    ];
    $stored = json_encode($sources, JSON_THROW_ON_ERROR);
    $module = new EChartsSunburst();
    $module->Create();
    $module->SetTestProperty('Sources', $stored);
    $module->SetTestProperty('IPSViewUseTileDesign', false);
    $module->SetTestProperty('IPSViewShowOuterRingLabels', false);
    $module->ApplyChanges();
    $module->ApplyChanges();
    $readProperty = new ReflectionMethod($module, 'ReadPropertyString');
    $model = json_decode($module->GetSunburstData(), true, 32, JSON_THROW_ON_ERROR)['sunburst'];
    assertGatewayGauge(
        $module->GetTestStatus() === IS_ACTIVE && $model['depth'] === 8
        && $readProperty->invoke($module, 'Sources') === $stored,
        'Sunburst must accept eight-level paths without rewriting stored legacy or explicit configuration.'
    );
    assertGatewayGauge(
        $model['data'][0]['name'] === 'Legacy / literal'
        && $model['data'][0]['id'] === 'group-' . sha1('Legacy / literal')
        && $model['data'][0]['children'][0]['id'] === 'subgroup-' . sha1("Legacy / literal\0Legacy subgroup"),
        'Sunburst must retain literal legacy slash names and stable group identities.'
    );
    $node = $model['data'][1];
    foreach (['House', 'Floor', 'Room', 'Trade', 'Circuit', 'Device', 'Channel'] as $name) {
        assertGatewayGauge(
            $node['name'] === $name && count($node['children']) === 1,
            'Sunburst must retain every configured hierarchy segment.'
        );
        $node = $node['children'][0];
    }
    assertGatewayGauge(
        $node['id'] === 'variable-4717' && $node['name'] === 'Same'
        && $model['data'][0]['children'][0]['children'][0]['id'] === 'variable-4711',
        'Sunburst must preserve separate variable identities despite identical visible names.'
    );
    foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $index => $html) {
        assertGatewayGauge(
            str_contains($html, '"depth":8') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'Eight-level Sunburst must reach both outputs within the output budget.'
        );
        assertGatewayGauge(
            str_contains($html, '"showOuterRingLabels":' . ($index === 0 ? 'true' : 'false')),
            'Independent Sunburst designs must remain unchanged for deeper hierarchies.'
        );
    }
    $form = json_decode($module->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
    $sourceList = findGaugeFormElement($form['elements'], 'Sources');
    assertGatewayGauge(
        in_array('GroupPath', array_column($sourceList['columns'], 'name'), true)
        && findGaugeFormElement($sourceList['form'], 'GroupPath')['type'] === 'ValidationTextBox',
        'Sunburst must offer the optional hierarchy path in the source list and row editor.'
    );
    $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $sources], JSON_THROW_ON_ERROR));
    foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            is_string($svg) && str_contains($svg, 'data-preview-depth="8"')
            && str_contains($svg, 'data-depth="8"') && str_contains($svg, 'data-node="variable-4717"'),
            'Both Sunburst previews must represent the full hierarchy through its eighth level.'
        );
        $document = new DOMDocument();
        assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Deep Sunburst previews must remain valid SVG.');
    }
    foreach (['House // Room', 'A/B/C/D/E/F/G/H'] as $invalidPath) {
        $invalid = $sources;
        $invalid[1]['GroupPath'] = $invalidPath;
        $module->SetTestProperty('Sources', json_encode($invalid, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === 201,
            'Sunburst must reject empty path components and a ninth hierarchy level.'
        );
    }
    $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $invalid], JSON_THROW_ON_ERROR));
    $invalidPreview = array_slice($module->GetTestFormUpdates(), -2)[0]['Value'];
    $invalidSVG = base64_decode(substr($invalidPreview, strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        !str_contains($invalidSVG, 'data-node=') && !str_contains($invalidSVG, 'Group A'),
        'Invalid Sunburst preview paths must show a diagnostic rather than unrelated example data.'
    );
    $module->SetTestProperty('Sources', $stored);
    $oldValue = $GLOBALS['symconTestVariables'][4717]['Value'];
    try {
        $GLOBALS['symconTestVariables'][4717]['Value'] = -1.0;
        $module->ApplyChanges();
        assertGatewayGauge(
            str_contains($module->GetVisualizationTile(), 'Sunburst values must be non-negative.'),
            'Deep Sunburst weights must reject negative source values.'
        );
        $GLOBALS['symconTestVariables'][4717]['Value'] = 0.0;
        $module->ApplyChanges();
        $zeroModel = json_decode($module->GetSunburstData(), true, 32, JSON_THROW_ON_ERROR)['sunburst'];
        assertGatewayGauge(
            $zeroModel['data'][1]['value'] === 0.0,
            'Zero-valued deep Sunburst branches must remain valid rather than disappearing from the model.'
        );
    } finally {
        $GLOBALS['symconTestVariables'][4717]['Value'] = $oldValue;
    }
    $maximum = [];
    for ($index = 0; $index < 100; ++$index) {
        $id = 89000 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000300,
            'Value'                                            => 1.0, 'Name' => 'Same leaf'];
        $maximum[] = ['VariableID' => $id, 'GroupPath' => implode(' / ', array_map(
            static fn (int $level): string => str_pad('Branch ' . $index . '-' . $level . ' ', 80, 'x'),
            range(1, 7)
        )), 'Label' => str_pad('Same leaf ', 120, 'x')];
    }
    try {
        $module->SetTestProperty('Sources', json_encode($maximum, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $maximumModel = json_decode($module->GetSunburstData(), true, 32, JSON_THROW_ON_ERROR)['sunburst'];
        assertGatewayGauge(
            $maximumModel['nodeCount'] === 800 && $maximumModel['depth'] === 8,
            'All 100 independent deepest Sunburst sources must retain their 800 nodes.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetConfigurationForm()] as $document) {
            assertGatewayGauge(
                strlen($document) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Deep 100-source Sunburst outputs and both previews must remain within the Symcon output limit.'
            );
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $maximum], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && substr_count($svg, 'data-node=') === 800
                && strlen($update['Value']) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both Sunburst previews must include all 800 nodes within the output budget, not a hidden sample.'
            );
        }
    } finally {
        for ($index = 0; $index < 100; ++$index) {
            unset($GLOBALS['symconTestVariables'][89000 + $index]);
        }
    }
})();
