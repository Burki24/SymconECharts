<?php

declare(strict_types=1);

// Included by gateway_gauges.php; all hierarchy sources and names are synthetic.
(static function (): void
{
    $sources = [
        ['VariableID' => 4711, 'Group' => 'Legacy / literal', 'Subgroup' => 'Legacy subgroup', 'Label' => 'Same'],
        ['VariableID'   => 4717, 'Group' => 'Unchanged', 'Subgroup' => 'Unchanged',
            'GroupPath' => 'House / Floor / Room / Trade / Circuit / Device / Channel', 'Label' => 'Same']
    ];
    $stored = json_encode($sources, JSON_THROW_ON_ERROR);
    $module = new EChartsTreemap();
    $module->Create();
    $module->SetTestProperty('Sources', $stored);
    $module->ApplyChanges();
    $module->ApplyChanges();
    $readProperty = new ReflectionMethod($module, 'ReadPropertyString');
    $model = json_decode($module->GetTreemapData(), true, 32, JSON_THROW_ON_ERROR)['treemap'];
    assertGatewayGauge(
        $module->GetTestStatus() === IS_ACTIVE && $model['depth'] === 8
        && $readProperty->invoke($module, 'Sources') === $stored,
        'Treemap must accept eight-level paths without rewriting legacy or explicit configuration.'
    );
    assertGatewayGauge(
        $model['data'][0]['name'] === 'Legacy / literal'
        && $model['data'][0]['id'] === 'group-' . sha1('Legacy / literal'),
        'Treemap must retain legacy slash names and existing group identities.'
    );
    $node = $model['data'][1];
    foreach (['House', 'Floor', 'Room', 'Trade', 'Circuit', 'Device', 'Channel'] as $name) {
        assertGatewayGauge(
            $node['name'] === $name && count($node['children']) === 1,
            'Treemap must retain every explicit hierarchy segment.'
        );
        $node = $node['children'][0];
    }
    assertGatewayGauge(
        $node['id'] === 'variable-4717' && $node['name'] === 'Same',
        'Treemap hierarchy must retain variable identities despite identical labels.'
    );
    foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
        assertGatewayGauge(
            str_contains($html, '"depth":8') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'Eight-level Treemap models must reach both bounded outputs.'
        );
    }
    $form = json_decode($module->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
    $sourceList = findGaugeFormElement($form['elements'], 'Sources');
    assertGatewayGauge(
        in_array('GroupPath', array_column($sourceList['columns'], 'name'), true)
        && findGaugeFormElement($sourceList['form'], 'GroupPath')['type'] === 'ValidationTextBox',
        'Treemap must offer the optional path in list columns and its row editor.'
    );
    $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $sources], JSON_THROW_ON_ERROR));
    foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            is_string($svg) && str_contains($svg, 'data-preview-depth="8"')
            && str_contains($svg, 'data-depth="8"') && str_contains($svg, 'data-node="variable-4717"'),
            'Both Treemap previews must represent the full edited hierarchy, including its eighth level.'
        );
        $document = new DOMDocument();
        assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Deep Treemap previews must remain valid SVG.');
    }
    foreach (['House // Room', 'A/B/C/D/E/F/G/H'] as $invalidPath) {
        $invalid = $sources;
        $invalid[1]['GroupPath'] = $invalidPath;
        $module->SetTestProperty('Sources', json_encode($invalid, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === 201,
            'Treemap must reject empty path segments and a ninth hierarchy level.'
        );
    }
    $manySources = [];
    for ($index = 0; $index < 100; ++$index) {
        $id = 98000 + $index;
        $GLOBALS['symconTestVariables'][$id] = [
            'VariableType' => 2, 'VariableUpdated' => 1780000300, 'Value' => 1.0,
            'Name'         => 'Synthetic hierarchy source ' . $index
        ];
        $segments = [];
        for ($depth = 1; $depth <= 7; ++$depth) {
            $segments[] = str_pad('Group ' . $index . ' level ' . $depth . ' ', 80, 'x');
        }
        $manySources[] = ['VariableID' => $id, 'GroupPath' => implode(' / ', $segments),
            'Label'                    => str_pad('Leaf ' . $index . ' ', 120, 'x')];
    }
    try {
        $module->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $large = json_decode($module->GetTreemapData(), true, 32, JSON_THROW_ON_ERROR)['treemap'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $large['nodeCount'] === 800
            && $large['depth'] === 8 && $large['positiveCount'] === 100,
            'Treemap must support 100 distinct full-depth paths at its 800-node budget.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetConfigurationForm()] as $html) {
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'The 100-source eight-level Treemap must remain below the output document budget.'
            );
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $manySources], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && substr_count($svg, 'data-node=') === 800
                && strlen($update['Value']) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both bounded Treemap previews must represent all 800 nodes, not an undisclosed sample.'
            );
        }
    } finally {
        for ($index = 0; $index < 100; ++$index) {
            unset($GLOBALS['symconTestVariables'][98000 + $index]);
        }
    }
})();
