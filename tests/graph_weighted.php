<?php

declare(strict_types=1);

(static function (): void
{
    $oldSource = $GLOBALS['symconTestVariables'][4711];
    $oldFormatted = $GLOBALS['symconTestTreeValues'][4711] ?? null;
    $responder = IPSModuleStrict::$ParentResponder;
    try {
        $GLOBALS['symconTestVariables'][4711]['Presentation'] = ['SUFFIX' => ' W', 'DIGITS' => 1];
        $GLOBALS['symconTestTreeValues'][4711] = '9999 W'; // A second, later read must not overwrite the Gateway snapshot.
        $module = new EChartsGraph();
        $module->Create();
        $nodes = [['ObjectID' => 9000], ['ObjectID' => 9002], ['ObjectID' => 9003]];
        $edges = [
            ['SourceObjectID' => 9000, 'TargetObjectID' => 9002, 'WeightMode' => 'manual', 'Weight' => 25, 'WeightUnit' => 'kWh'],
            ['SourceObjectID' => 9002, 'TargetObjectID' => 9000, 'WeightMode' => 'variable', 'WeightVariableID' => 4711],
            ['SourceObjectID' => 9000, 'TargetObjectID' => 9003],
            ['SourceObjectID' => 9003, 'TargetObjectID' => 9002, 'WeightMode' => 'variable', 'WeightVariableID' => 4711]
        ];
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Edges', json_encode($edges, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('DirectedEdges', true);
        $module->SetTestProperty('MaximumEdgeWidth', 9);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewMaximumEdgeWidth', 17);
        $module->SetTestProperty('EnableIPSView', true);
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $module->GetTestProperties() === $saved,
            'Weighted Graphs must apply idempotently without rewriting old fields or designs.'
        );
        assertGatewayGauge(
            $module->GetTestReferences() === [4711]
            && in_array(['SenderID' => 4711, 'Message' => VM_UPDATE], $module->GetTestMessages(), true),
            'Weight IDs outside the node list need deduplicated source references and updates.'
        );
        $reads = 0;
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder, &$reads): string
        {
            $message = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (($message['Operation'] ?? null) === \SymconECharts\EChartsDataProtocol::OPERATION_CURRENT_READ) {
                ++$reads;
            }
            return $responder($json);
        };
        $data = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge($reads === 1, 'A repeated weight source must be read once per model through the Gateway.');
        assertGatewayGauge(
            $data['links'][0]['width'] === 3
            && abs($data['links'][1]['width'] - 4.4) < 1e-10
            && !isset($data['links'][2]['width']) && $data['links'][1]['weight']['value'] === 42.5
            && $data['links'][1]['weight']['displayValue'] === '42.5 W'
            && $data['links'][1]['weight']['unit'] === 'W'
            && $data['links'][1]['weight']['timestamp'] === 1780000000
            && $data['links'][1]['weight']['variableID'] === 4711,
            'Live width must retain original source identity, timestamp, unit and formatting.'
        );
        $html = $module->GetIPSViewHTML();
        preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $html, $match);
        $ips = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state']['chart']['graph'];
        assertGatewayGauge(
            $ips['links'][0]['width'] === 5 && abs($ips['links'][1]['width'] - 7.8) < 1e-10
            && $ips['links'][1]['weight'] === $data['links'][1]['weight'],
            'Independent IPSView widths must preserve the common raw model and scale.'
        );
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $columns = findGaugeFormElement($form['elements'], 'Edges')['columns'];
        $rowForm = findGaugeFormElement($form['elements'], 'Edges')['form'];
        $edit = static function (array $row) use ($rowForm): array
        {
            $Edges = $row;
            return array_column(eval($rowForm), 'value', 'name');
        };
        assertGatewayGauge($edit($edges[1])['WeightVariableID'] === 4711
            && $edit($edges[2])['WeightMode'] === 'none' && $edit($edges[2])['WeightVariableID'] === 0
            && $edit([])['Weight'] === 1, 'Editing an old or new row must reset missing fields instead of retaining a previous weight.');
        foreach (['Weight', 'WeightUnit', 'WeightDecimals', 'WeightVariableID'] as $name) {
            $column = array_values(array_filter($columns, static fn (array $c): bool => ($c['name'] ?? '') === $name))[0];
            assertGatewayGauge(
                $column['save'] && array_key_exists('add', $column),
                'Hidden weight row fields must be saved and initialized for each new row.'
            );
        }
        $properties = $module->GetTestProperties();
        $status = $module->GetTestStatus();
        IPSModuleStrict::$ParentResponder = static fn (string $json): string => throw new RuntimeException('Offline');
        $module->UpdateBarPreviewFromForm(json_encode(['WeightMaximum' => 50], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $index => $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(str_contains($svg, 'stroke-width="' . ($index === 0 ? '5' : '9') . '"')
                && !str_contains($svg, 'sample-1'), 'Both previews must share real weight scaling without a Gateway.');
        }
        assertGatewayGauge(
            $module->GetTestProperties() === $properties && $module->GetTestStatus() === $status,
            'Unsaved weight previews must not modify saved settings or module status.'
        );
        IPSModuleStrict::$ParentResponder = $responder;
        $before = count($module->GetTestVisualizationUpdates());
        $GLOBALS['symconTestVariables'][4711]['Value'] = 0;
        $GLOBALS['symconTestTreeValues'][4711] = '0 W';
        $module->MessageSink(1780000005, 4711, VM_UPDATE, []);
        $zero = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
        assertGatewayGauge(
            count($module->GetTestVisualizationUpdates()) > $before
            && $zero['links'][1]['width'] === 1 && $zero['links'][1]['weight']['value'] === 0,
            'Source updates must republish real zero weights as visible minimum-width links.'
        );
        $GLOBALS['symconTestVariables'][4711]['Value'] = -1;
        try {
            $module->GetGraphData();
            throw new LogicException('Negative current Graph weight accepted.');
        } catch (InvalidArgumentException) {
            assertGatewayGauge($module->GetTestStatus() === 204, 'Invalid current weights remain read failures.');
        }
        $GLOBALS['symconTestVariables'][4711] = $oldSource;
        $bad = $edges;
        $bad[1]['WeightVariableID'] = 4712;
        $module->SetTestProperty('Edges', json_encode($bad, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'String weights cannot be coerced to numbers.');
        $module->SetTestProperty('Edges', json_encode($edges, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('WeightMaximum', 0.0);
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'An active fixed weight range must be strictly increasing.');
        $module->SetTestProperty('WeightMaximum', 100.0);
        $module->ApplyChanges();
        $GLOBALS['symconTestPresentationFailures'][4711] = true;
        try {
            $module->GetGraphData();
            throw new LogicException('Unreadable weight unit metadata accepted.');
        } catch (RuntimeException $exception) {
            assertGatewayGauge(
                $module->GetTestStatus() === 204 && !($exception instanceof LogicException),
                'Unreadable metadata must remain a visible read failure.'
            );
        } finally {
            unset($GLOBALS['symconTestPresentationFailures'][4711]);
        }
        $module->SetTestProperty('Edges', json_encode(array_map(static function (array $edge): array
        {
            $edge['WeightMode'] = 'none';
            return $edge;
        }, $edges), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestReferences() === []
            && !in_array(['SenderID' => 4711, 'Message' => VM_UPDATE], $module->GetTestMessages(), true),
            'Disabling weighting removes only its active subscriptions.'
        );
    } finally {
        $GLOBALS['symconTestVariables'][4711] = $oldSource;
        unset($GLOBALS['symconTestPresentationFailures'][4711]);
        if ($oldFormatted === null) {
            unset($GLOBALS['symconTestTreeValues'][4711]);
        } else {
            $GLOBALS['symconTestTreeValues'][4711] = $oldFormatted;
        }
        IPSModuleStrict::$ParentResponder = $responder;
    }
})();
