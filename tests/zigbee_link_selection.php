<?php

declare(strict_types=1);

// Real module/export-reader path; Kernel and saved exports are entirely synthetic.
function IPS_GetKernelDir(): string
{
    return $GLOBALS['symconTestZigbeeKernel'];
}

(static function (): void
{
    $kernel = sys_get_temp_dir() . '/echarts-zigbee-selection-' . bin2hex(random_bytes(8));
    $directory = $kernel . '/user/IPSZigbee2MQTT/networkmaps';
    mkdir($directory, 0700, true);
    $file = $directory . '/zigbee-network-map-20261023-120000.json';
    $GLOBALS['symconTestZigbeeKernel'] = $kernel;
    $GLOBALS['symconTestModuleInstances']['{C07834E7-9821-218C-C3EC-A4A87FFB8D7D}'] = [99700];
    $GLOBALS['symconTestInstances'][99700] = ['ModuleInfo' => ['ModuleID' => '{C07834E7-9821-218C-C3EC-A4A87FFB8D7D}']];
    $a = '0x0000000000000001';
    $b = '0x0000000000000002';
    $c = '0x0000000000000003';
    $topology = ['captured_at' => 1800000000, 'nodes' => [
        ['ieeeAddr' => $a, 'friendlyName' => '<Bridge>', 'type' => 'Coordinator'],
        ['ieeeAddr' => $b, 'friendlyName' => 'Router', 'type' => 'Router'],
        ['ieeeAddr' => $c, 'friendlyName' => 'Sensor', 'type' => 'EndDevice']
    ], 'links' => [
        ['sourceIeeeAddr' => $a, 'targetIeeeAddr' => $b, 'lqi' => 70],
        ['sourceIeeeAddr' => $a, 'targetIeeeAddr' => $b, 'lqi' => 240],
        ['sourceIeeeAddr' => $b, 'targetIeeeAddr' => $a, 'lqi' => 128],
        ['sourceIeeeAddr' => $b, 'targetIeeeAddr' => $c, 'lqi' => 0]
    ]];
    file_put_contents($file, json_encode($topology, JSON_THROW_ON_ERROR));
    $hash = hash_file('sha256', $file);
    try {
        $module = new EChartsGraphZ2M();
        $module->Create();
        $defaults = $module->GetTestProperties();
        assertGatewayGauge($defaults['LinkSelection'] === 'strongest' && $defaults['MaximumLQI'] === 255
            && $defaults['MinimumLQI'] === 0 && $defaults['MaximumLinks'] === 300, 'Legacy selection defaults changed.');
        $module->SetTestProperty('NetworkMapInstanceID', 99700);
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewGraphLayout', 'circular');
        $module->SetTestProperty('IPSViewLqiLowColor', 0x102030);
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $module->GetTestProperties() === $saved,
            'Repeated updates must preserve existing configuration and independent designs.'
        );
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'MaximumLQI')['visible'] === false,
            'Inactive range controls must be hidden on reopening.'
        );
        foreach (['LinkSelection', 'MinimumLQI', 'MaximumLQI', 'MaximumLinks'] as $field) {
            $action = findGaugeFormElement($form['elements'], $field)['onChange'] ?? '';
            assertGatewayGauge(
                str_contains($action, 'ECGZ_UpdateBarPreviewFromForm') && str_contains($action, "'LinkSelection' => \$LinkSelection"),
                'Every filter draft must refresh both previews with the complete selection.'
            );
        }
        $module->UpdateLinkSelectionForm('range');
        assertGatewayGauge(
            array_slice($module->GetTestFormUpdates(), -1)[0]['Value'] === true,
            'Range selection must immediately reveal the upper bound without saving.'
        );
        $identities = null;
        foreach ([['strongest', 0, 255, [240]], ['weakest', 0, 255, [0]],
            ['range', 0, 128, [128]], ['range', 1, 127, []], ['range', 128, 128, [128]]] as [$selection, $minimum, $maximum, $expected]) {
            $module->SetTestProperty('LinkSelection', $selection);
            $module->SetTestProperty('MinimumLQI', $minimum);
            $module->SetTestProperty('MaximumLQI', $maximum);
            $module->SetTestProperty('MaximumLinks', 1);
            $properties = $module->GetTestProperties();
            $model = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
            $identities ??= $model['nodes'];
            assertGatewayGauge(
                $model['nodes'] === $identities && array_column($model['links'], 'lqi') === $expected,
                'Filter/cap ordering or stable complete device identities changed.'
            );
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $index => $html) {
                assertGatewayGauge(
                    strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                    && preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $html, $match) === 1,
                    'Both outputs must remain complete and below the native output budget.'
                );
                $bootstrap = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                $state = $bootstrap['state'] ?? json_decode(gzdecode(base64_decode(
                    $bootstrap['options']['compressedStateGzipBase64'],
                    true
                )), true, 512, JSON_THROW_ON_ERROR);
                assertGatewayGauge(
                    $state['status'] === 'ready' && $state['chart']['graph']['links'] === $model['links']
                    && $state['chart']['graph']['nodes'] === $identities
                    && $state['chart']['graph']['style']['graphLayout'] === ($index === 0 ? 'force' : 'circular'),
                    'Kachel and independent IPSView output must share exactly the selected model, not designs.'
                );
            }
            assertGatewayGauge($module->GetTestProperties() === $properties, 'Reading a filtered export must never rewrite properties.');
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'MaximumLQI')['visible'] === true,
            'An active range must retain its upper-bound control on reopening.'
        );
        foreach ([['strongest', 0, 255, 1, 1], ['weakest', 0, 255, 1, 1], ['range', 128, 128, 300, 2],
            ['range', 1, 127, 300, 0], ['range', 129, 128, 300, -1]] as [$selection, $minimum, $maximum, $limit, $lines]) {
            $module->UpdateBarPreviewFromForm(json_encode(['LinkSelection' => $selection,
                'MinimumLQI'                                               => $minimum, 'MaximumLQI' => $maximum, 'MaximumLinks' => $limit], JSON_THROW_ON_ERROR));
            foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(simplexml_load_string($svg) !== false, 'Filtered preview SVG must be well formed.');
                assertGatewayGauge(
                    substr_count($svg, '<line ') === max(0, $lines)
                    && ($lines < 0 ? str_contains($svg, 'The Zigbee connection filter is invalid.') : substr_count($svg, '<circle ') === 4),
                    'Both synthetic previews must apply the real model filter and retain isolated nodes.'
                );
                if ($selection === 'weakest') {
                    assertGatewayGauge(
                        str_contains($svg, 'stroke="#D9534F"') || str_contains($svg, 'stroke="#102030"'),
                        'Weakest-first preview must display the lowest LQI, not recolor the strongest edge.'
                    );
                }
            }
        }
        $module->SetTestProperty('MaximumLQI', 127);
        assertGatewayGauge(
            !json_decode($module->GetGraphDiagnostic(), true, 512, JSON_THROW_ON_ERROR)['valid'],
            'Reversed active bounds must fail closed.'
        );
        assertGatewayGauge(
            hash_file('sha256', $file) === $hash && $GLOBALS['symconTestArchiveQueryCount'] === 0,
            'Selection must neither alter the saved export nor query archives or start scans.'
        );
        $large = ['nodes' => [], 'links' => []];
        for ($index = 0; $index < 500; ++$index) {
            $large['nodes'][] = ['ieeeAddr' => sprintf('0x%016x', $index + 1),
                'friendlyName'              => str_repeat('<&', 60), 'type' => 'Router'];
        }
        for ($distance = 1; count($large['links']) < 10000; ++$distance) {
            for ($index = 0; $index < 500 && count($large['links']) < 10000; ++$index) {
                $large['links'][] = ['sourceIeeeAddr' => $large['nodes'][$index]['ieeeAddr'],
                    'targetIeeeAddr'                  => $large['nodes'][($index + $distance) % 500]['ieeeAddr'],
                    'lqi'                             => count($large['links']) % 256];
            }
        }
        file_put_contents($file, json_encode($large, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('MinimumLQI', 0);
        $module->SetTestProperty('MaximumLQI', 255);
        $module->SetTestProperty('MaximumLinks', 2000);
        foreach (['strongest', 'weakest', 'range'] as $selection) {
            $module->SetTestProperty('LinkSelection', $selection);
            $properties = $module->GetTestProperties();
            $model = json_decode($module->GetGraphData(), true, 512, JSON_THROW_ON_ERROR)['graph'];
            assertGatewayGauge(
                count($model['nodes']) === 500 && count($model['links']) === 2000
                && $model['filteredLinks'] === 10000 && $model['totalLinks'] === 10000,
                'All selection modes must honor the unchanged raw and displayed budgets.'
            );
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
                assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                    && str_contains($html, 'compressedStateGzipBase64'), 'Maximum exports must fit both embedded documents.');
            }
            assertGatewayGauge($module->GetTestProperties() === $properties, 'Maximum exports must not rewrite designs.');
        }
        echo "Zigbee selection defaults, filters, drafts, independent outputs and read-only exports verified.\n";
    } finally {
        unlink($file);
        rmdir($directory);
        rmdir(dirname($directory));
        rmdir($kernel . '/user');
        rmdir($kernel);
        unset($GLOBALS['symconTestZigbeeKernel'], $GLOBALS['symconTestModuleInstances'], $GLOBALS['symconTestInstances'][99700]);
    }
})();
