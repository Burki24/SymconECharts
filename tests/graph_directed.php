<?php

declare(strict_types=1);

// Included by gateway_gauges.php; only synthetic objects, no live installation.
(static function (): void
{
    $module = new EChartsGraph();
    $module->Create();
    assertGatewayGauge($module->GetTestProperties()['DirectedEdges'] === false, 'Legacy Graphs must remain undirected.');
    $nodes = [];
    for ($index = 0; $index < 3; ++$index) {
        $id = 95000 + $index;
        $nodes[] = ['ObjectID' => $id, 'Label' => $index === 0 ? '<Room & A>' : 'Same name'];
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Same name'];
        $GLOBALS['symconTestTreeValues'][$id] = '25 %';
    }
    $edges = [
        ['SourceObjectID' => 95000, 'TargetObjectID' => 95001],
        ['SourceObjectID' => 95001, 'TargetObjectID' => 95000],
        ['SourceObjectID' => 95000, 'TargetObjectID' => 95002]
    ];
    try {
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Edges', json_encode($edges, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'Undirected reversed duplicates must still fail.');
        $module->SetTestProperty('DirectedEdges', true);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewGraphLayout', 'circular');
        $module->SetTestProperty('EdgeColor', 0x123456);
        $module->SetTestProperty('IPSViewEdgeColor', 0xABCDEF);
        $module->SetTestProperty('EnableIPSView', true);
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        $data = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $data['directed'] === true
            && count($data['links']) === 3 && $data['links'][1]['source'] === 'object-95001'
            && $data['links'][1]['target'] === 'object-95000'
            && $data['links'][0]['reciprocal'] && !$data['links'][2]['reciprocal'],
            'Both directed endpoints and shared reciprocal planning must survive the public model.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                && str_contains($html, '"directed":true'), 'Both output adapters must carry directed semantics.');
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $control = findGaugeFormElement($form['elements'], 'DirectedEdges');
        assertGatewayGauge(
            $control['type'] === 'CheckBox'
            && str_contains($control['onChange'], "'DirectedEdges' => $" . 'DirectedEdges'),
            'The model checkbox must pass unsaved true and false through the shared form callback.'
        );
        foreach ([true, false] as $directed) {
            $module->UpdateBarPreviewFromForm(json_encode([
                'DirectedEdges' => $directed, 'Edges' => $directed ? $edges : [$edges[0]]
            ], JSON_THROW_ON_ERROR));
            foreach (array_slice($module->GetTestFormUpdates(), -2) as $index => $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = simplexml_load_string($svg);
                assertGatewayGauge($xml !== false && !str_contains($svg, 'sample-1')
                    && str_contains($svg, '&lt;Room &amp; A&gt;'), 'Both previews must use escaped real nodes.');
                $xml->registerXPathNamespace('s', 'http://www.w3.org/2000/svg');
                $paths = $xml->xpath('//s:path[@marker-end]');
                assertGatewayGauge(count($paths) === ($directed ? 3 : 0), 'Unsaved direction must add/remove every arrow in both SVGs.');
                if ($directed) {
                    assertGatewayGauge(
                        str_contains($svg, $index === 0 ? '#123456' : '#ABCDEF')
                        && (string) $paths[0]['d'] !== (string) $paths[1]['d'],
                        'Directed previews must retain independent edge colors and separated paths.'
                    );
                }
            }
        }
        assertGatewayGauge($module->GetTestProperties() === $saved, 'Apply and draft previews must not rewrite configurations or designs.');
        foreach ([
            [...$edges, $edges[0]],
            [['SourceObjectID' => 95000, 'TargetObjectID' => 95000]],
            [['SourceObjectID' => 95000, 'TargetObjectID' => 59999]]
        ] as $invalid) {
            $module->SetTestProperty('Edges', json_encode($invalid, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === 201, 'Directed mode must retain all source guards.');
        }
        // Directed cycles are relationships, not Sankey flows; no DAG restriction.
        $module->SetTestProperty('Edges', json_encode([$edges[0],
            ['SourceObjectID' => 95001, 'TargetObjectID' => 95002],
            ['SourceObjectID' => 95002, 'TargetObjectID' => 95000]
        ], JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Directed cycles must remain permitted.');
        $module->SetTestProperty('DirectedEdges', false);
        $module->ApplyChanges();
        assertGatewayGauge(
            json_decode($module->GetGraphData(), true)['graph']['directed'] === false,
            'A valid graph must return to the original undirected model.'
        );
        $zigbee = new EChartsGraphZ2M();
        $zigbee->Create();
        assertGatewayGauge(!array_key_exists('DirectedEdges', $zigbee->GetTestProperties()), 'Z2M retains its separate directed export contract.');
    } finally {
        foreach ($nodes as $node) {
            unset($GLOBALS['symconTestTreeObjects'][$node['ObjectID']], $GLOBALS['symconTestTreeValues'][$node['ObjectID']]);
        }
    }
})();
