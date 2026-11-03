<?php

declare(strict_types=1);

// Included by gateway_gauges.php with synthetic Kernel boundaries.
(static function (): void
{
    $module = new EChartsGraph();
    $module->Create();
    $defaults = $module->GetTestProperties();
    assertGatewayGauge(
        $defaults['Nodes'] === '[]' && $defaults['Edges'] === '[]'
        && $defaults['GraphLayout'] === 'force' && $defaults['RoamEnabled'] === true,
        'Existing Graph defaults must remain unchanged.'
    );
    $nodes = [];
    $edges = [];
    for ($index = 0; $index < 300; ++$index) {
        $id = 94000 + $index;
        $nodes[] = ['ObjectID' => $id, 'Label' => str_repeat('<&', 50)];
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Same name'];
        $GLOBALS['symconTestTreeValues'][$id] = str_repeat('<&', 150);
    }
    for ($from = 0; count($edges) < 1000; ++$from) {
        for ($to = $from + 1; $to < 300 && count($edges) < 1000; ++$to) {
            $edges[] = ['SourceObjectID' => 94000 + $from, 'TargetObjectID' => 94000 + $to];
        }
    }
    try {
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Edges', json_encode($edges, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewGraphLayout', 'circular');
        $module->SetTestProperty('EnableIPSView', true);
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $module->GetTestProperties() === $saved,
            'Large Graphs must apply repeatedly without configuration rewrites.'
        );
        $model = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge(
            count($model['nodes']) === 300 && count($model['links']) === 1000
            && count(array_unique(array_column($model['nodes'], 'id'))) === 300,
            'All maximum Graph identities and edges must remain in the live model.'
        );
        $sizes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $sizes[] = strlen($html);
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                && str_contains($html, 'SYMC_ECHARTS_GRAPH'),
                'Both maximum Graph documents must include all nodes and shared support below 1 MiB.'
            );
            assertGatewayGauge(
                preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $html, $match) === 1,
                'The Graph startup bootstrap must remain accessible.'
            );
            $bootstrap = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $bootstrap['state'] === null
                && is_string($bootstrap['options']['compressedStateGzipBase64']),
                'Large HTML-safe Graph startup states must be losslessly compressed.'
            );
            $restored = json_decode(
                gzdecode(base64_decode($bootstrap['options']['compressedStateGzipBase64'], true)),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['chart']['graph'];
            assertGatewayGauge(
                $restored['nodes'] === $model['nodes'] && $restored['links'] === $model['links'],
                'Startup compression must retain every full name, value, ID and connection.'
            );
        }
        $form = $module->GetConfigurationForm();
        assertGatewayGauge(
            strlen($form) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($form, '2–300'),
            'The large Graph form must publish the new bounds under the output limit.'
        );
        $module->UpdateBarPreviewFromForm(json_encode(['Nodes' => $nodes, 'Edges' => $edges], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && simplexml_load_string($svg) !== false
                && str_contains($svg, 'data-preview-nodes="40"')
                && str_contains($svg, '40/300') && str_contains($svg, '/1000')
                && str_contains($svg, 'object-94000') && !str_contains($svg, 'sample-1')
                && !str_contains($svg, '<&'),
                'Both large previews must show safe, explicitly counted real excerpts, not an unrelated example.'
            );
        }
        assertGatewayGauge($module->GetTestProperties() === $saved, 'Preview drafts must not persist settings.');
        foreach ([
            [[...$nodes, ['ObjectID' => 94300]], $edges],
            [$nodes, [...$edges, ['SourceObjectID' => 94004, 'TargetObjectID' => 94005]]],
            [$nodes, [['SourceObjectID' => 94000, 'TargetObjectID' => 94000]]],
            [$nodes, [['SourceObjectID' => 94000, 'TargetObjectID' => 99999]]],
            [$nodes, [['SourceObjectID' => 94000, 'TargetObjectID' => 94001],
                ['SourceObjectID' => 94001, 'TargetObjectID' => 94000]]]
        ] as [$badNodes, $badEdges]) {
            $module->SetTestProperty('Nodes', json_encode($badNodes, JSON_THROW_ON_ERROR));
            $module->SetTestProperty('Edges', json_encode($badEdges, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === 201, 'Graph must reject over-budget and invalid connections.');
        }
        $module->SetTestProperty('Nodes', json_encode(array_slice($nodes, 0, 80), JSON_THROW_ON_ERROR));
        $oldEdges = array_slice(array_values(array_filter(
            $edges,
            static fn (array $edge): bool => $edge['SourceObjectID'] < 94080 && $edge['TargetObjectID'] < 94080
        )), 0, 200);
        $module->SetTestProperty('Edges', json_encode($oldEdges, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $old = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && count($old['nodes']) === 80 && count($old['links']) === 200,
            'Previously valid configurations must remain valid without migration.'
        );
        $zigbee = new EChartsGraphZ2M();
        $zigbee->Create();
        $zigbeeDefaults = $zigbee->GetTestProperties();
        assertGatewayGauge(
            $zigbeeDefaults['MaximumLinks'] === 300 && $zigbeeDefaults['MinimumLQI'] === 0,
            'General Graph expansion must not change Z2M export or link defaults.'
        );
        $zigbeeForm = json_decode($zigbee->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($zigbeeForm['elements'], 'MaximumLinks')['maximum'] === 2000,
            'Z2M must retain its separate 2000-link protection.'
        );
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Edges', json_encode([...array_slice($edges, 0, 999),
            ['SourceObjectID' => 94001, 'TargetObjectID' => 94000]
        ], JSON_THROW_ON_ERROR));
        $module->SetTestProperty('DirectedEdges', true);
        $directedSaved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        $directed = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $directed['directed']
            && count($directed['nodes']) === 300 && count($directed['links']) === 1000
            && $directed['links'][999]['reciprocal'] && $module->GetTestProperties() === $directedSaved,
            'Maximum directed Graphs must retain budgets and configuration without migration.'
        );
        $weightedEdges = [...array_slice($edges, 0, 999), ['SourceObjectID' => 94001, 'TargetObjectID' => 94000]];
        $weightedEdges[0] += ['WeightMode' => 'manual', 'Weight' => 50];
        $module->SetTestProperty('Edges', json_encode($weightedEdges, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'A weight must not reduce existing count budgets.');
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetConfigurationForm()] as $output) {
            assertGatewayGauge(
                strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Directed metadata and arrow previews must remain within all existing output budgets.'
            );
        }
        $module->SetTestProperty('Edges', str_repeat(' ', 65537));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'Directed edges retain the 64-KiB list guard.');
        $module->SetTestProperty('Nodes', str_repeat(' ', 65537));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'The existing per-list 64-KiB guard must remain active.');
        printf("Graph 300/1000 output bytes: Tile %d, IPSView %d, form %d.\n", ...[...$sizes, strlen($form)]);
    } finally {
        foreach ($nodes as $node) {
            unset($GLOBALS['symconTestTreeObjects'][$node['ObjectID']], $GLOBALS['symconTestTreeValues'][$node['ObjectID']]);
        }
    }
})();
