<?php

declare(strict_types=1);

// Actual Sankey/Gateway collaborators; synthetic data only at the Kernel boundary.
(static function (): void
{
    $variables = $GLOBALS['symconTestVariables'];
    $parent = IPSModuleStrict::$ParentResponder;
    $requests = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use (&$requests): string
    {
        $requests[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        return $gateway->ForwardData($json);
    };
    try {
        $nodes = [['Key' => 'grid', 'Label' => '<Grid>'], ['Key' => 'house', 'Label' => 'Same'],
            ['Key' => 'load', 'Label' => 'Same'], ['Key'         => 'idle', 'Label' => 'Idle']];
        $sources = [['Source' => 'grid', 'Target' => 'house', 'VariableID' => 99910],
            ['Source' => 'house', 'Target' => 'load', 'VariableID' => 99911],
            ['Source' => 'grid', 'Target' => 'load', 'VariableID' => 99912]];
        foreach ([1000.0, 750.0, 0.0] as $i => $value) {
            $GLOBALS['symconTestVariables'][99910 + $i] = ['VariableType' => 2, 'Value' => $value,
                'VariableUpdated'                                         => 1780000300, 'Name' => 'Same source', 'Presentation' => ['SUFFIX' => ' W ']];
        }
        $module = new EChartsSankey();
        $module->Create();
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('UnitMode', 'automatic');
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewOrientation', 'vertical');
        $module->ApplyChanges();
        $legacy = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $nodeField = findGaugeFormElement($form['elements'], 'Nodes');
        $balance = array_values(array_filter($nodeField['columns'], static fn (array $column): bool => $column['name'] === 'Balance'))[0];
        assertGatewayGauge($balance['add'] === false && $balance['edit']['type'] === 'CheckBox', 'Node balances must be explicitly selected with compatible false defaults.');
        $nodes[1]['Balance'] = true;
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $saved = $module->GetTestProperties();
        $requests = [];
        $model = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge(
            $model['links'][2]['value'] === 250.0 && $model['links'][2]['calculated'] === true
            && !isset($model['links'][2]['variableID'], $model['links'][2]['timestamp'])
            && array_slice($model['links'], 0, 2) === $legacy['links'] && array_slice($model['data'], 0, 3) === $legacy['data']
            && $module->GetTestReferences() === [99910, 99911, 99912] && $module->GetTestProperties() === $saved
            && array_column(array_column($requests, 'Payload'), 'VariableID') === [99910, 99911, 99912],
            'Balances must preserve measured values, hidden zero validation, references and unique reads without inventing source objects.'
        );

        if (($GLOBALS['argv'][2] ?? '') === 'browser') {
            $case = $GLOBALS['argv'][3] ?? 'balance';
            $module->SetTestProperty('Title', 'Synthetische Mengenbilanz');
            $module->SetTestProperty('ShowValues', true);
            $module->SetTestProperty('IPSViewShowValues', true);
            if ($case === 'balance-inflow') {
                $GLOBALS['symconTestVariables'][99911]['Value'] = 1200.0;
            } elseif ($case === 'balance-equal') {
                $GLOBALS['symconTestVariables'][99911]['Value'] = 1000.0;
            } elseif ($case === 'balance-budget') {
                for ($n = 4; $n < 100; ++$n) {
                    $nodes[] = ['Key' => 'n' . $n, 'Label' => 'Idle'];
                }
                $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
            }
            $module->ApplyChanges();
            if (($GLOBALS['argv'][4] ?? '') === 'preview') {
                $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
                echo base64_decode(substr(findGaugeFormElement($form['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
            } else {
                echo ($GLOBALS['argv'][4] ?? '') === 'ipsview' ? $module->GetIPSViewHTML() : $module->GetVisualizationTile();
            }
            return;
        }

        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetTestVariableValue('IPSViewSankey')] as $i => $html) {
            preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m);
            $flow = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state']['chart']['sankey'];
            assertGatewayGauge(
                $flow['links'] === $model['links'] && $flow['unit'] === 'W'
                && $flow['style']['orientation'] === ($i === 0 ? 'horizontal' : 'vertical')
                && str_contains($html, 'echarts-sankey-balance-note') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both outputs and published WebContent must use one balance model and retain independent designs.'
            );
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $svg = base64_decode(substr(findGaugeFormElement($form['elements'], $name)['image'], strlen('data:image/svg+xml;base64,')), true);
            $doc = new DOMDocument();
            assertGatewayGauge($doc->loadXML($svg, LIBXML_NONET), 'Balanced previews must be valid XML.');
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
            $title = $xpath->query('//s:path[@data-calculated="true" and @data-source="house" and @data-target="@balance:house"]/s:title')->item(0);
            assertGatewayGauge($title !== null && str_contains($title->textContent, 'not a measurement or proven loss')
                && preg_match('/Measured inflow: 1[,.\s]?000 W/u', $title->textContent) === 1 && str_contains($title->textContent, 'Measured outflow: 750 W')
                && str_contains($svg, 'Calculated flows · not measured'), 'Both previews must distinguish computed flows and show the measured basis.');
        }
        // Exercise the real list callback, not only a manually constructed draft payload.
        $status = $module->GetTestStatus();
        (static function () use ($module, $nodeField, $nodes): void
        {
            foreach ($module->GetTestProperties() as $name => $value) {
                ${$name} = in_array($name, ['Sources', 'Nodes', 'NodeColors', 'IPSViewNodeColors'], true)
                    ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;
            }
            $Nodes = $nodes;
            $Nodes[1]['Balance'] = false;
            eval(str_replace('ECSA_UpdateBarPreviewFromForm($id, ', '$module->UpdateBarPreviewFromForm(', $nodeField['onEdit']));
        })();
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(!str_contains($svg, 'data-calculated="true"'), 'The unsaved node selection must control both previews.');
        }
        assertGatewayGauge(
            $module->GetTestProperties() === $saved && $module->GetTestStatus() === $status,
            'Draft balances must not persist configuration or change instance status.'
        );
        $GLOBALS['symconTestVariables'][99911]['Value'] = 1200.0;
        $module->MessageSink(1780000400, 99911, VM_UPDATE, [1200.0, true]);
        $updates = $module->GetTestVisualizationUpdates();
        $state = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge($state['chart']['sankey']['links'][2]['source'] === '@balance:house'
            && $state['chart']['sankey']['links'][2]['value'] == 200.0, 'Updates must recompute the direction and magnitude from measured values.');
        $GLOBALS['symconTestVariables'][99911]['Value'] = 1000.0;
        $module->MessageSink(1780000401, 99911, VM_UPDATE, [1000.0, true]);
        assertGatewayGauge(
            count(json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['links']) === 2,
            'A later exact balance must remove the generated terminal and flow.'
        );
        $GLOBALS['symconTestVariables'][99912]['Presentation']['SUFFIX'] = 'kW';
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'Balancing must not bypass the hidden zero source unit check.');
        $GLOBALS['symconTestVariables'][99912]['Presentation']['SUFFIX'] = 'W';

        // Exactly 99 configured nodes + 1 generated node, 199 measured edges + 1 calculated edge.
        $manyNodes = [];
        $manySources = [];
        for ($i = 0; $i < 99; ++$i) {
            $manyNodes[] = ['Key' => 'n' . str_pad((string) $i, 39, 'x'), 'Label' => str_repeat('N', 80), ...($i === 1 ? ['Balance' => true] : [])];
        }
        for ($from = 0; count($manySources) < 199; ++$from) {
            for ($to = $from + 1; $to < 99 && count($manySources) < 199; ++$to) {
                $manySources[] = ['Source' => $manyNodes[$from]['Key'], 'Target' => $manyNodes[$to]['Key'], 'VariableID' => 99910];
            }
        }
        $module->SetTestProperty('Nodes', json_encode($manyNodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $max = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge(count($max['data']) === 100 && count($max['links']) === 200, 'Generated objects must count in the same 100/200 budgets.');
        $sizes = [];
        foreach (['tile' => $module->GetVisualizationTile(), 'ipsview' => $module->GetIPSViewHTML(), 'form' => $module->GetConfigurationForm()] as $name => $output) {
            $sizes[$name] = strlen($output);
            assertGatewayGauge(strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Maximum balanced ' . $name . ' exceeds 1 MiB: ' . strlen($output));
        }
        $manySources[] = ['Source' => $manyNodes[2]['Key'], 'Target' => $manyNodes[7]['Key'], 'VariableID' => 99910];
        $module->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m);
            $state = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state'];
            assertGatewayGauge(
                $module->GetTestStatus() === 205 && $state['chart'] === null && str_contains($state['error'], '100 nodes and 200 connections'),
                'Dynamic budget overflows must show a precise error, never silently omit or truncate a balance.'
            );
        }
        echo 'Sankey calculated balances, live/draft parity, identity, updates, unit checks and maximum output bytes verified: ' . json_encode($sizes) . ".\n";
    } finally {
        $GLOBALS['symconTestVariables'] = $variables;
        IPSModuleStrict::$ParentResponder = $parent;
    }
})();
