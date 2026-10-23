<?php

declare(strict_types=1);

use SymconECharts\EChartsVariablePresentation;

// Only Kernel boundaries are synthetic; module, presentation resolver and Gateway are real.
(static function (): void
{
    $variables = $GLOBALS['symconTestVariables'];
    $profiles = $GLOBALS['symconTestProfiles'];
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
        $sources = [['Source' => 'a', 'Target' => 'b', 'VariableID' => 99710],
            ['Source' => 'b', 'Target' => 'c', 'VariableID' => 99711],
            ['Source' => 'a', 'Target' => 'c', 'VariableID' => 99710],
            ['Source' => 'c', 'Target' => 'd', 'VariableID' => 99712]];
        foreach ([20.5, 10.0, 0.0] as $i => $value) {
            $GLOBALS['symconTestVariables'][99710 + $i] = ['VariableType' => 2, 'Value' => $value,
                'VariableUpdated'                                         => 1780000300, 'Name' => 'Same source name', 'Presentation' => ['SUFFIX' => ' W ']];
        }
        $GLOBALS['symconTestProfiles']['Test.Power'] = ['Suffix' => ' W ', 'MinValue' => 0, 'MaxValue' => 1000, 'Digits' => 2];
        $GLOBALS['symconTestVariables'][99711]['Presentation'] = ['PROFILE' => 'Test.Power'];
        $module = new EChartsSankey();
        $module->Create();
        assertGatewayGauge($module->GetTestProperties()['UnitMode'] === 'manual', 'Legacy manual mode must remain the compatible default.');
        $module->SetTestProperty('Nodes', json_encode($nodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Unit', ' Display ');
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewOrientation', 'vertical');
        if (($GLOBALS['argv'][2] ?? '') === 'browser') {
            $case = $GLOBALS['argv'][3] ?? 'automatic';
            $module->SetTestProperty('Title', 'Synthetische Mengenflüsse');
            $module->SetTestProperty('UnitMode', in_array($case, ['manual', 'checked'], true) ? $case : 'automatic');
            $module->SetTestProperty('Unit', $case === 'manual' ? 'Anzeige' : 'W');
            $module->SetTestProperty('ShowValues', true);
            $module->SetTestProperty('IPSViewShowValues', true);
            if ($case === 'mismatch') {
                $GLOBALS['symconTestVariables'][99712]['Presentation'] = ['SUFFIX' => 'kWh'];
            }
            if ($case === 'unitless') {
                foreach ([99710, 99711, 99712] as $id) {
                    $GLOBALS['symconTestVariables'][$id]['Presentation'] = [];
                }
            }
            $module->ApplyChanges();
            if (($GLOBALS['argv'][4] ?? '') === 'preview') {
                $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
                $image = findGaugeFormElement($form['elements'], 'BarPreview')['image'];
                echo base64_decode(substr($image, strlen('data:image/svg+xml;base64,')), true);
            } else {
                echo ($GLOBALS['argv'][4] ?? '') === 'ipsview' ? $module->GetIPSViewHTML() : $module->GetVisualizationTile();
            }
            return;
        }
        $module->ApplyChanges();
        $legacy = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge($legacy['unit'] === 'Display', 'Manual display units must not be silently validated or replaced.');
        $module->SetTestProperty('UnitMode', 'automatic');
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        $requests = [];
        $model = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $model['unit'] === 'W'
            && $model['links'] === $legacy['links'] && $model['data'] === $legacy['data']
            && $module->GetTestProperties() === $saved && $module->GetTestReferences() === [99710, 99711, 99712],
            'Automatic units must retain raw values, topology, identities, subscriptions and all designs.'
        );
        assertGatewayGauge(
            array_column(array_column($requests, 'Payload'), 'VariableID') === [99710, 99711, 99712],
            'Repeated variables must still be read once, including zero-flow sources.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetTestVariableValue('IPSViewSankey')] as $i => $html) {
            assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                && preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m) === 1, 'Every output must remain complete and bounded.');
            $flow = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state']['chart']['sankey'];
            assertGatewayGauge($flow['unit'] === 'W' && $flow['links'] === $model['links']
                && $flow['style']['orientation'] === ($i === 0 ? 'horizontal' : 'vertical'), 'Both independent designers must use the same checked unit and original values.');
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'Unit')['visible'] === false
            && str_contains(findGaugeFormElement($form['elements'], 'UnitMode')['onChange'], 'ECSA_UpdateUnitModeForm')
            && str_contains(findGaugeFormElement($form['elements'], 'UnitMode')['onChange'], "'UnitMode' => \$UnitMode"),
            'Mode controls must update visibility and both draft previews.'
        );
        $module->UpdateUnitModeForm('checked');
        assertGatewayGauge(array_slice($module->GetTestFormUpdates(), -1)[0]['Value'] === true, 'Checked manual mode must reveal the expected unit.');
        $module->UpdateBarPreviewFromForm(json_encode(['UnitMode' => 'automatic', 'ShowValues' => true,
            'IPSViewShowValues'                                   => true], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                simplexml_load_string($svg) !== false && str_contains($svg, ' W') && !str_contains($svg, 'Display'),
                'Both preview adapters must show the effective common unit, not the inactive stored label.'
            );
        }
        $status = $module->GetTestStatus();
        $module->UpdateBarPreviewFromForm('{"UnitMode":"checked","Unit":"kWh"}');
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(str_contains($svg, 'Connection 1:') && str_contains($svg, 'not match'), 'Draft mismatches must report the source row rather than draw misleading flows.');
        }
        assertGatewayGauge(
            $module->GetTestProperties() === $saved && $module->GetTestStatus() === $status,
            'Draft checks must never persist properties or modify instance state.'
        );

        // Native suffix overrides the profile suffix in both strict and existing lenient resolution.
        $GLOBALS['symconTestVariables'][99711]['Presentation']['SUFFIX'] = 'W';
        $GLOBALS['symconTestProfiles']['Test.Power']['Suffix'] = 'kWh';
        assertGatewayGauge(
            EChartsVariablePresentation::Resolve(99711, 0.0, 100.0, '', 2, true, true)['unit'] === 'W',
            'Strict metadata reading must retain native-over-profile precedence.'
        );
        $module->SetTestProperty('UnitMode', 'checked');
        $module->SetTestProperty('Unit', ' W ');
        $module->ApplyChanges();
        $checked = json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey'];
        assertGatewayGauge($checked['unit'] === 'W' && $checked['links'] === $model['links'], 'Checked expected units must preserve unchanged raw flows.');
        $module->SetTestProperty('UnitMode', 'automatic');
        foreach ([['SUFFIX' => 'kW'], [], ['PROFILE' => 'missing'], ['SUFFIX' => 17], ['SUFFIX' => str_repeat('x', 25)]] as $bad) {
            $GLOBALS['symconTestVariables'][99712]['Presentation'] = $bad;
            $module->ApplyChanges();
            $requests = [];
            $diagnostic = json_decode($module->GetSankeyDiagnostic(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge($module->GetTestStatus() === 201 && !$diagnostic['valid']
                && str_contains($diagnostic['lastError'], 'Connection 4:'), 'Even a hidden zero flow must reject incompatible or unreadable units at its own row.');
            try {
                $module->GetSankeyData();
                throw new RuntimeException('Invalid units reached the Gateway.');
            } catch (InvalidArgumentException | RuntimeException $e) {
                assertGatewayGauge(str_contains($e->getMessage(), 'Connection 4:'), 'Invalid units must fail before Gateway reads.');
            }
            assertGatewayGauge($requests === [], 'Invalid unit metadata must not read partial current flows.');
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
                preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m);
                $state = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state'];
                assertGatewayGauge(
                    $state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Connection 4:'),
                    'Both outputs must expose a precise checked-mode diagnostic instead of retaining misleading flows.'
                );
            }
        }
        $GLOBALS['symconTestVariables'][99712]['Presentation'] = ['SUFFIX' => 'W'];
        $GLOBALS['symconTestPresentationFailures'][99712] = true;
        assertGatewayGauge(
            EChartsVariablePresentation::Resolve(99712, 0.0, 100.0, 'fallback', 2, true)['unit'] === 'fallback',
            'Existing consumers must retain their lenient metadata fallback.'
        );
        $module->SetTestProperty('UnitMode', 'manual');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Manual legacy mode must not inspect unavailable metadata.');
        $module->SetTestProperty('UnitMode', 'automatic');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'Automatic validation must not mistake an API failure for unitless data.');
        unset($GLOBALS['symconTestPresentationFailures']);
        foreach ([99710, 99711, 99712] as $id) {
            $GLOBALS['symconTestVariables'][$id]['Presentation'] = [];
        }
        $module->ApplyChanges();
        assertGatewayGauge(json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['unit'] === '', 'All-unitless flows remain valid.');
        $module->SetTestProperty('UnitMode', 'checked');
        $module->SetTestProperty('Unit', '');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Checked unitless values remain valid.');
        $module->SetTestProperty('UnitMode', 'invalid');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 201, 'Unknown unit modes must fail closed.');

        $manyNodes = [];
        $manySources = [];
        for ($i = 0; $i < 100; ++$i) {
            $manyNodes[] = ['Key' => 'n' . $i, 'Label' => str_repeat('N', 80)];
        }
        for ($from = 0; count($manySources) < 200; ++$from) {
            for ($to = $from + 1; $to < 100 && count($manySources) < 200; ++$to) {
                $manySources[] = ['Source' => 'n' . $from, 'Target' => 'n' . $to, 'VariableID' => 99710];
            }
        }
        $module->SetTestProperty('Nodes', json_encode($manyNodes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('UnitMode', 'automatic');
        $GLOBALS['symconTestVariables'][99710]['Presentation'] = ['SUFFIX' => str_repeat('U', 24)];
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE && count(json_decode($module->GetSankeyData(), true, 512, JSON_THROW_ON_ERROR)['sankey']['links']) === 200
            && strlen($module->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($module->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Maximum checked source lists must preserve every existing protection budget.');
        echo "Sankey unit modes, metadata, compatibility, drafts and maximum outputs verified.\n";
    } finally {
        $GLOBALS['symconTestVariables'] = $variables;
        $GLOBALS['symconTestProfiles'] = $profiles;
        unset($GLOBALS['symconTestPresentationFailures']);
        IPSModuleStrict::$ParentResponder = $parent;
    }
})();
