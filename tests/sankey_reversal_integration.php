<?php

declare(strict_types=1);

// Real module and Gateway; only Kernel variables and presentation metadata are synthetic.
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
        $nodes = [['Key' => 'a', 'Label' => '<Supply>'], ['Key' => 'b', 'Label' => 'Same'],
            ['Key' => 'c', 'Label' => 'Same'], ['Key'           => 'd', 'Label' => 'Zero']];
        $sources = [['Source' => 'a', 'Target' => 'b', 'VariableID' => 99810],
            ['Source' => 'b', 'Target' => 'c', 'VariableID' => 99811],
            ['Source' => 'a', 'Target' => 'c', 'VariableID' => 99810],
            ['Source' => 'c', 'Target' => 'd', 'VariableID' => 99812]];
        foreach ([20.5, 10.0, 0.0] as $i => $value) {
            $GLOBALS['symconTestVariables'][99810 + $i] = ['VariableType' => 2, 'Value' => $value,
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
        $list = findGaugeFormElement($form['elements'], 'Sources');
        $reversal = array_values(array_filter($list['columns'], static fn (array $column): bool => $column['name'] === 'ReverseNegative'))[0];
        assertGatewayGauge($reversal['add'] === false && $reversal['edit']['type'] === 'CheckBox'
            && str_contains($list['onEdit'], "'Sources' => \$barPreviewSources"), 'Each connection must explicitly opt in through existing read-only preview callbacks.');
        $sources[0]['ReverseNegative'] = true;
        $sources[2]['ReverseNegative'] = true;
        $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'] === $legacy,
            'Enabling reversal on positive sources must not alter the model, formatting or designs.'
        );
        $GLOBALS['symconTestVariables'][99810]['Value'] = -20.5;
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $requests = [];
        $signed = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge(
            $signed['links'][0]['source'] === 'b' && $signed['links'][0]['target'] === 'a'
            && $signed['links'][0]['value'] === 20.5 && $signed['links'][0]['originalValue'] === -20.5
            && $signed['links'][2]['source'] === 'c' && $signed['links'][2]['target'] === 'a'
            && $signed['links'][2]['value'] === 20.5 && $signed['data'][0]['value'] === 41.0
            && $module->GetTestProperties() === $saved && $module->GetTestReferences() === [99810, 99811, 99812]
            && array_column(array_column($requests, 'Payload'), 'VariableID') === [99810, 99811, 99812],
            'Reused IDs retain full absolute widths, signed metadata, subscriptions and one read per ID.'
        );

        if (($GLOBALS['argv'][2] ?? '') === 'browser') {
            $case = $GLOBALS['argv'][3] ?? 'reverse';
            $module->SetTestProperty('Title', 'Synthetische vorzeichenbehaftete Flüsse');
            $module->SetTestProperty('ShowValues', true);
            $module->SetTestProperty('IPSViewShowValues', true);
            if ($case === 'reverse-cycle') {
                $sources[0]['VariableID'] = 99811;
                $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
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
                $flow['links'] === $signed['links'] && $flow['unit'] === 'W'
                && $flow['style']['orientation'] === ($i === 0 ? 'horizontal' : 'vertical') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both outputs and published WebContent must share signed metadata and independent designs.'
            );
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $svg = base64_decode(substr(findGaugeFormElement($form['elements'], $name)['image'], strlen('data:image/svg+xml;base64,')), true);
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Signed previews must remain valid XML.');
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
            $flow = $xpath->query('//s:path[@data-source="b" and @data-target="a"]/s:title');
            assertGatewayGauge($flow->length === 1 && str_contains($flow->item(0)->textContent, 'Original value: -20.5 W')
                && str_contains($flow->item(0)->textContent, 'Configured connection: <Supply> → Same'), 'SVG must use reversed geometry and safely preserve signed originals.');
        }
        $missingOpt = $sources;
        unset($missingOpt[2]['ReverseNegative']);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $missingOpt], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -3) as $update) {
            $text = $update['Parameter'] === 'image' ? base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true) : $update['Value'];
            assertGatewayGauge(str_contains($text, 'Connection 3:'), 'Reusing a signed variable must not implicitly opt in another connection.');
        }
        $draft = $sources;
        $draft[0]['VariableID'] = 99811; // a -> b -> c -> a after reversing only the shortcut.
        $status = $module->GetTestStatus();
        // Execute the actual compact form callback, including copied list iterators and booleans.
        (static function () use ($module, $draft, $list): void
        {
            foreach ($module->GetTestProperties() as $name => $value) {
                ${$name} = in_array($name, ['Sources', 'Nodes', 'NodeColors', 'IPSViewNodeColors'], true)
                    ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;
            }
            $Sources = $draft;
            eval(str_replace('ECSA_UpdateBarPreviewFromForm($id, ', '$module->UpdateBarPreviewFromForm(', $list['onEdit']));
        })();
        foreach (array_slice($module->GetTestFormUpdates(), -3) as $update) {
            $text = $update['Parameter'] === 'image' ? base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true) : $update['Value'];
            assertGatewayGauge(str_contains($text, 'cycle after reversing'), 'Draft diagnostics and both previews must reject the same effective cycle.');
        }
        assertGatewayGauge($module->GetTestProperties() === $saved && $module->GetTestStatus() === $status, 'Signed draft errors must not mutate saved configuration or status.');
        $module->SetTestProperty('Sources', json_encode($draft, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m);
            $state = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state'];
            assertGatewayGauge($module->GetTestStatus() === 205 && $state['status'] === 'error' && $state['chart'] === null
                && str_contains($state['error'], 'cycle after reversing'), 'Effective cycles must clear both outputs with precise diagnostics, not retain partial flows.');
        }
        // Correcting a sign must recover without removing sources or their reversal choices.
        $GLOBALS['symconTestVariables'][99810]['Value'] = 20.5;
        $module->MessageSink(1780000400, 99810, VM_UPDATE, [20.5, true]);
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'A later positive value must recover the same instance.');
        $updates = $module->GetTestVisualizationUpdates();
        $recovered = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge($recovered['status'] === 'ready' && $recovered['chart']['sankey']['links'][2]['source'] === 'a'
            && !isset($recovered['chart']['sankey']['links'][2]['originalValue']), 'Recovery must push the restored configured direction without stale negative metadata.');
        $GLOBALS['symconTestVariables'][99812]['Presentation']['SUFFIX'] = 'kW';
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === 201 && str_contains($module->GetSankeyDiagnostic(), 'Connection 4:'),
            'Reversal must not bypass the unit validation of hidden zero flows.'
        );
        $GLOBALS['symconTestVariables'][99812]['Presentation']['SUFFIX'] = 'W';

        $manyNodes = [];
        $manySources = [];
        for ($i = 0; $i < 100; ++$i) {
            $manyNodes[] = ['Key' => 'n' . str_pad((string) $i, 39, 'x'), 'Label' => str_repeat('N', 80)];
        }
        for ($from = 0; count($manySources) < 200; ++$from) {
            for ($to = $from + 1; $to < 100 && count($manySources) < 200; ++$to) {
                $manySources[] = ['Source' => $manyNodes[$from]['Key'], 'Target' => $manyNodes[$to]['Key'], 'VariableID' => 99810, 'ReverseNegative' => true];
            }
        }
        $GLOBALS['symconTestVariables'][99810]['Value'] = -20.5;
        $module->SetTestProperty('Nodes', json_encode($manyNodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            count(json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['links']) === 200,
            'Maximum signed model must retain all 200 flows.'
        );
        foreach (['tile' => $module->GetVisualizationTile(), 'ipsview' => $module->GetIPSViewHTML(), 'form' => $module->GetConfigurationForm()] as $name => $output) {
            assertGatewayGauge(
                strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Maximum signed ' . $name . ' must fit 1 MiB, actual bytes: ' . strlen($output)
            );
        }
        $module->SetTestProperty('Sources', str_repeat(' ', 65537));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'The unchanged 65536-byte source guard must also reject signed source lists.');
        echo "Sankey signed flows, both outputs/previews, recovery, source identity and maximum budgets verified.\n";
    } finally {
        $GLOBALS['symconTestVariables'] = $variables;
        IPSModuleStrict::$ParentResponder = $parent;
    }
})();
