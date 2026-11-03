<?php

declare(strict_types=1);

// Real module/Gateway collaborators; synthetic values only at the Kernel boundary.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $requests = [];
    $gateway = new EChartsGateway();
    $gateway->Create();
    $gateway->ApplyChanges();
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($gateway, &$requests): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'current.read') {
            $requests[] = $request['Payload']['VariableID'];
        }
        return $gateway->ForwardData($json);
    };
    $sources = [];
    foreach ([100.0, -20.0, -5.0, 2.0] as $index => $value) {
        $id = 98700 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => $value,
            'VariableUpdated'                                  => 1792233296 + $index, 'Name' => 'Same <source>'];
        $sources[] = ['VariableID'    => $id, 'Label' => '', 'Unit' => 'kWh', 'Decimals' => $index === 3 ? 6 : 1,
            'UseVariablePresentation' => false];
    }
    $decode = static fn (EChartsBarWaterfall $module): array => json_decode($module->GetWaterfallData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Waterfall section output exceeds 1 MiB.');
        preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $m);
        $b = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        return $b['state'];
    };
    $preview = static function (array $form, string $name): DOMDocument
    {
        $svg = base64_decode(substr(findGaugeFormElement($form['elements'], $name)['image'], strlen('data:image/svg+xml;base64,')), true);
        $doc = new DOMDocument();
        assertGatewayGauge($doc->loadXML($svg, LIBXML_NONET), 'Waterfall SVG must be well-formed XML.');
        return $doc;
    };
    try {
        $module = new EChartsBarWaterfall();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('TotalLabel', 'End');
        $module->SetTestProperty('StartColor', 0x123456);
        $module->SetTestProperty('IPSViewTotalColor', 0x654321);
        $module->SetTestProperty('Title', 'Waterfall: Zwischensumme und Neustart');
        $module->ApplyChanges();
        $old = $decode($module);
        $falseSources = array_map(static fn (array $row): array => [...$row, 'SubtotalAfter' => false, 'Restart' => false], $sources);
        $module->SetTestProperty('Sources', json_encode($falseSources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($decode($module) === $old, 'False row flags must preserve the exact old model and design.');
        $sources[1]['SubtotalAfter'] = true;
        $sources[2]['Restart'] = $sources[2]['SubtotalAfter'] = true;
        $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        $steps = $model['steps'];
        assertGatewayGauge(
            $requests === range(98700, 98703) && $module->GetTestReferences() === range(98700, 98703),
            'Calculated steps must not create reads, references or invented source objects.'
        );
        assertGatewayGauge(
            array_column($steps, 'kind') === ['start', 'change', 'subtotal', 'restart', 'subtotal', 'change', 'total']
            && $steps[2]['after'] === 80.0 && $steps[3]['previousTotal'] === 80.0 && $steps[3]['before'] === 0.0
            && $steps[3]['after'] === -5.0 && $steps[5]['before'] === -5.0 && $steps[6]['after'] === -3.0
            && !isset($steps[2]['timestamp']) && !isset($steps[4]['timestamp']),
            'Subtotals must not be added again; restarts are absolute and preserve the previous close.'
        );
        assertGatewayGauge(
            $model['bar']['unit'] === 'kWh' && $model['bar']['decimals'] === 6
            && $steps[3]['timestamp'] === 1792233298 && $steps[6]['section'] === 2,
            'Original units, maximum precision, timestamps and explicit sections must remain available.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetTestVariableValue('IPSViewBarWaterfall')] as $i => $html) {
            $state = $startup($html);
            assertGatewayGauge($state['chart']['steps'] === $steps && $state['chart']['bar']['style']['colors'][$i === 0 ? 'startColor' : 'totalColor']
                === ($i === 0 ? '#123456' : '#654321'), 'Both outputs and published WebContent must share complete steps, not shared designs.');
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $doc = $preview($form, $name);
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('s', 'http://www.w3.org/2000/svg');
            assertGatewayGauge(
                $doc->documentElement->getAttribute('data-waterfall-total') === '-3.0'
                && $doc->documentElement->getAttribute('data-waterfall-min') === '-5.0'
                && $doc->documentElement->getAttribute('data-waterfall-max') === '100.0'
                && $xp->query('//s:rect[@data-step-kind="subtotal"]')->length === 2
                && $xp->query('//s:rect[@data-step-kind="restart" and @data-step-before="0" and @data-step-after="-5"]')->length === 1
                && str_contains($doc->textContent, 'Separate sections; final total belongs to the last section.')
                && str_contains($doc->textContent, 'Previous section ended at'),
                'Both previews must use complete section totals/ranges and honest absolute restart geometry.'
            );
        }
        if (($GLOBALS['argv'][2] ?? '') === 'browser') {
            $adapter = $GLOBALS['argv'][3] ?? 'symcon';
            if ($adapter === 'preview') {
                echo $preview($form, 'BarPreview')->saveXML();
            }
            else {
                $html = $adapter === 'ipsview' ? $module->GetIPSViewHTML() : $module->GetVisualizationTile();
                // Local fixture has no state service: disable only its transport script, not the chart renderer.
                echo preg_replace("~<script>\\(function \\(\\) \\{\\s*'use strict';\\s*const bootstrap.*?</script>~s", '', $html);
            }
            return;
        }
        $field = findGaugeFormElement($form['elements'], 'Sources');
        foreach (['SubtotalAfter', 'Restart'] as $flag) {
            $column = array_values(array_filter($field['columns'], static fn (array $c): bool => $c['name'] === $flag))[0];
            assertGatewayGauge($column['add'] === false && $column['edit']['type'] === 'CheckBox', 'New row flags must default to false.');
        }
        $status = $module->GetTestStatus();
        (static function () use ($module, $field, $falseSources): void
        {
            foreach ($module->GetTestProperties() as $name => $value) {
                ${$name} = $name === 'Sources' ? $falseSources : $value;
            }
            eval(str_replace('ECBW_UpdateBarPreviewFromForm($id, ', '$module->UpdateBarPreviewFromForm(', $field['onEdit']));
        })();
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(!str_contains($svg, 'data-step-kind="subtotal"') && !str_contains($svg, 'data-step-kind="restart"')
                && str_contains($svg, 'data-waterfall-total="77.0"'), 'An unsaved list draft must control both previews without stale row flags.');
        }
        assertGatewayGauge(
            $module->GetTestProperties() === $properties && $module->GetTestStatus() === $status,
            'Reading or editing previews must never persist drafts or change live status/designs.'
        );
        $GLOBALS['symconTestVariables'][98703]['Value'] = -2.0;
        $module->MessageSink(1792233400, 98703, VM_UPDATE, [-2.0, true]);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge($updated['chart']['steps'][6]['after'] == -7.0
            && array_column($updated['chart']['steps'], 'id') === array_column($steps, 'id'), 'Late updates must preserve ID anchors and recompute the last section, not a full-list sum.');
        foreach ([['Restart' => true, 'index' => 0], ['SubtotalAfter' => 1, 'index' => 1], ['Restart' => 'true', 'index' => 2], ['Unit' => 'W', 'index' => 3]] as $bad) {
            $invalid = $sources;
            $index = $bad['index'];
            unset($bad['index']);
            $invalid[$index] = [...$invalid[$index], ...$bad];
            $module->SetTestProperty('Sources', json_encode($invalid, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            assertGatewayGauge(
                $module->GetTestStatus() === 201 && $startup($module->GetVisualizationTile())['chart'] === null,
                'Invalid flags, first-row restarts and mixed units must be rejected without partial chart output.'
            );
        }
        $many = [];
        for ($i = 0; $i < 100; ++$i) {
            $GLOBALS['symconTestVariables'][98700 + $i] = ['VariableType' => 2, 'Value' => (float) -$i, 'VariableUpdated' => 1792233400, 'Name' => 'Same'];
            $many[] = ['VariableID'       => 98700 + $i, 'Label' => str_repeat('<>&測', 20), 'Unit' => 'kWh', 'Decimals' => 6,
                'UseVariablePresentation' => false, 'SubtotalAfter' => true, 'Restart' => $i > 0];
        }
        $module->SetTestProperty('Sources', json_encode($many, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $requests = [];
        $max = $decode($module);
        assertGatewayGauge(count($max['steps']) === 201 && count(array_unique(array_column($max['steps'], 'id'))) === 201
            && $max['steps'][200]['after'] === -99.0 && $requests === range(98700, 98799)
            && count($module->GetTestReferences()) === 100, 'Maximum derived steps must remain bounded and read exactly the 100 configured sources.');
        $sizes = [];
        foreach (['tile' => $module->GetVisualizationTile(), 'ipsview' => $module->GetIPSViewHTML(), 'form' => $module->GetConfigurationForm()] as $name => $output) {
            $sizes[$name] = strlen($output);
            assertGatewayGauge(strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Maximum section output exceeds 1 MiB: ' . $name);
        }
        $huge = array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('X', 3000)], $many);
        $module->SetTestProperty('Sources', json_encode($huge, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            $startup($module->GetVisualizationTile())['chart'] === null && $module->GetTestStatus() === 202,
            'Subtotals and section metadata must count inside the unchanged 200000-byte model guard.'
        );
        echo 'Waterfall sections, original sources, drafts and maximum 201-step outputs verified: ' . json_encode($sizes) . ".\n";
    } finally {
        $GLOBALS['symconTestVariables'] = $savedVariables;
        IPSModuleStrict::$ParentResponder = $savedResponder;
    }
})();
