<?php

declare(strict_types=1);

// Included by gateway_gauges.php with synthetic Kernel boundaries.
(static function (): void
{
    $module = new EChartsTreeSystemHealth();
    $module->Create();
    $defaults = $module->GetTestProperties();
    assertGatewayGauge(
        $defaults['MaxDepth'] === 3 && $defaults['InitialDepth'] === 2
        && $defaults['MaxNodes'] === 80 && $defaults['StatusRules'] === '[]'
        && $defaults['ShowOnlyRuleVariables'] === false,
        'Health Tree defaults and rule storage must remain compatible.'
    );
    for ($level = 0; $level <= 11; ++$level) {
        $id = 86100 + $level;
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => $level % 2,
            'ObjectName'                                       => 'Health level ' . $level, 'ObjectIsHidden' => false];
        $GLOBALS['symconTestTreeChildren'][$id] = [$id + 1];
    }
    $GLOBALS['symconTestTreeChildren'][86111] = [86112, 86113, 86114];
    $GLOBALS['symconTestTreeObjects'][86114] = ['ObjectType' => 0, 'ObjectName' => 'Too deep', 'ObjectIsHidden' => false];
    $GLOBALS['symconTestTreeChildren'][86114] = [86115];
    foreach ([86112, 86113, 86115, 86116] as $id) {
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Battery', 'ObjectIsHidden' => $id === 86113];
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 1, 'VariableUpdated' => 100,
            'Value'                                            => $id === 86112 ? 1 : 'Must never be evaluated'];
        $GLOBALS['symconTestTreeValues'][$id] = '1 %';
    }
    $rules = json_encode(array_map(static fn (int $id): array => [
        'VariableID' => $id, 'Direction' => 'high', 'WarningAt' => 0.5, 'CriticalAt' => 1
    ], [86112, 86113, 86115, 86116]), JSON_THROW_ON_ERROR);
    try {
        $module->SetTestProperty('RootObjectID', 86100);
        $module->SetTestProperty('MaxDepth', 12);
        $module->SetTestProperty('InitialDepth', 12);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewInitialDepth', 12);
        $module->SetTestProperty('StatusRules', $rules);
        $before = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Health Tree must accept twelve descendant levels.');
        assertGatewayGauge($module->GetTestProperties() === $before, 'Repeated ApplyChanges must not modify Health Tree properties or rules.');
        $model = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
        assertGatewayGauge(
            $model['root']['health'] === 'critical'
            && $model['healthScope'] === ['ruleCount' => 4, 'evaluatedRuleCount' => 1]
            && $model['count'] === 14,
            'Only the visible twelfth-level rule must propagate; hidden, deeper and external rules must remain unevaluated.'
        );
        $form = json_decode($module->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
        foreach (['MaxDepth', 'InitialDepth', 'IPSViewInitialDepth'] as $name) {
            assertGatewayGauge(
                findGaugeFormElement($form['elements'], $name)['maximum'] === 12,
                'Health depth controls must support twelve consistently.'
            );
        }
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                && str_contains($html, '"id":"object-86112"')
                && str_contains($html, '"evaluatedRuleCount":1')
                && str_contains($html, 'Status applies only to the selected subtree.')
                && str_contains($html, 'SYMC_ECHARTS_TREE'),
                'Both Health outputs must carry the deep hierarchy, shared renderer and explicit evaluation scope.'
            );
        }
        $module->UpdateBarPreviewFromForm(json_encode(['InitialDepth' => 12, 'IPSViewInitialDepth' => 12], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && str_contains($svg, 'data-node="object-86112"')
                && str_contains($svg, 'data-depth="12"') && !str_contains($svg, 'object-86113')
                && !str_contains($svg, 'object-86115') && simplexml_load_string($svg) !== false,
                'Both Health previews must show twelve levels with the same visibility boundaries.'
            );
        }
        assertGatewayGauge($module->GetTestProperties() === $before, 'Health preview drafts must not persist settings.');
        $module->SearchStatusRuleCandidates('Battery', 'numeric', false, 86100, 12, 80, false, '', '[]');
        $candidateUpdates = array_slice($module->GetTestFormUpdates(), -2);
        $candidateRows = json_decode((string) $candidateUpdates[0]['Value'], true, 64, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($candidateRows) === 1 && $candidateRows[0]['VariableID'] === 86112,
            'Status-rule search must reach twelve levels without including hidden, deeper or external sources.'
        );
        assertGatewayGauge($module->GetTestProperties() === $before, 'Candidate searches must not persist status-rule drafts.');
        $module->SetTestProperty('MaxDepth', 11);
        $module->ApplyChanges();
        $shallow = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
        assertGatewayGauge(
            !isset($shallow['root']['health']) && $shallow['healthScope']['evaluatedRuleCount'] === 0,
            'Rules excluded by depth must not produce a misleading healthy or critical ancestor.'
        );
        $module->SetTestProperty('MaxDepth', 12);
        $module->SetTestProperty('VariableNameFilter', 'battery');
        $module->SetTestProperty('ShowOnlyRuleVariables', true);
        $module->ApplyChanges();
        $filtered = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
        assertGatewayGauge(
            $filtered['count'] === 13 && $filtered['root']['health'] === 'critical'
            && $filtered['healthScope']['evaluatedRuleCount'] === 1,
            'Deep rule and name filtering must intersect without evaluating omitted rule sources.'
        );
        $module->SetTestProperty('MaxNodes', 10);
        $module->SetTestProperty('VariableNameFilter', '');
        $module->SetTestProperty('ShowOnlyRuleVariables', false);
        $module->ApplyChanges();
        $limited = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
        assertGatewayGauge(
            $limited['count'] === 10 && $limited['truncated']
            && $limited['healthScope']['evaluatedRuleCount'] === 0,
            'The existing node cap must continue to exclude deeper health rules visibly.'
        );
        foreach (['MaxDepth' => 201, 'InitialDepth' => 202, 'IPSViewInitialDepth' => 202] as $name => $status) {
            $module->SetTestProperty('MaxDepth', 12);
            $module->SetTestProperty('InitialDepth', 12);
            $module->SetTestProperty('IPSViewInitialDepth', 12);
            $module->SetTestProperty($name, 13);
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === $status, 'The thirteenth Health depth must stay invalid.');
        }
        assertGatewayGauge($module->GetTestProperties()['StatusRules'] === $rules, 'Health rule JSON must remain byte-identical throughout updates.');
        $GLOBALS['symconTestTreeChildren'][86111] = range(87000, 89988);
        $manyRules = [];
        foreach ($GLOBALS['symconTestTreeChildren'][86111] as $id) {
            $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => str_repeat('N', 120),
                'ObjectIsHidden'                                   => false];
            $GLOBALS['symconTestTreeValues'][$id] = str_repeat('V', 100);
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 1, 'VariableUpdated' => 100, 'Value' => 1,
                'Name'                                             => str_repeat('N', 120)];
            $manyRules[] = ['VariableID' => $id, 'Direction' => 'high', 'WarningAt' => 0.5, 'CriticalAt' => 1];
        }
        try {
            $module->SetTestProperty('MaxNodes', 3000);
            $module->SetTestProperty('MaxDepth', 12);
            $module->SetTestProperty('InitialDepth', 2);
            $module->SetTestProperty('IPSViewInitialDepth', 2);
            $module->SetTestProperty('StatusRules', json_encode($manyRules, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            $large = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
            assertGatewayGauge(
                $large['count'] === 3000 && $large['truncated']
                && $large['root']['health'] === 'critical'
                && $large['healthScope'] === ['ruleCount' => 2989, 'evaluatedRuleCount' => 2988],
                'Maximum deep Health Trees must retain node limits and exact partial-evaluation metadata.'
            );
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
                assertGatewayGauge(
                    strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                    'Maximum deep Health Trees must remain within both HTML output buffers.'
                );
                assertGatewayGauge(
                    preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $html, $match) === 1,
                    'Large Health documents must retain the startup bootstrap.'
                );
                $bootstrap = json_decode($match[1], true, 64, JSON_THROW_ON_ERROR);
                $restored = json_decode(gzdecode(base64_decode($bootstrap['options']['compressedStateGzipBase64'], true)), true, 64, JSON_THROW_ON_ERROR);
                assertGatewayGauge(
                    $restored['chart']['tree']['count'] === 3000,
                    'Compressed Health startup data must retain the complete bounded hierarchy.'
                );
            }
            assertGatewayGauge(
                strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Maximum deep Health rules and both previews must fit the form output buffer.'
            );
        } finally {
            foreach (range(87000, 89988) as $id) {
                unset($GLOBALS['symconTestTreeObjects'][$id], $GLOBALS['symconTestTreeValues'][$id], $GLOBALS['symconTestVariables'][$id]);
            }
        }
    } finally {
        foreach (range(86100, 86116) as $id) {
            unset($GLOBALS['symconTestTreeObjects'][$id], $GLOBALS['symconTestTreeChildren'][$id],
                $GLOBALS['symconTestVariables'][$id], $GLOBALS['symconTestTreeValues'][$id]);
        }
    }
})();
