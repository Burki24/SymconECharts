<?php

declare(strict_types=1);

// Included by gateway_gauges.php with synthetic Kernel boundaries.
(static function (): void
{
    $module = new EChartsTree();
    $module->Create();
    $defaults = $module->GetTestProperties();
    assertGatewayGauge(
        $defaults['MaxDepth'] === 3 && $defaults['InitialDepth'] === 2
        && $defaults['MaxNodes'] === 80 && $defaults['IPSViewInitialDepth'] === 2,
        'Existing Tree selection and design defaults must not change.'
    );
    $ids = range(86000, 86014);
    for ($level = 0; $level <= 11; ++$level) {
        $id = 86000 + $level;
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => $level % 2,
            'ObjectName'                                       => 'Level ' . $level, 'ObjectIsHidden' => false];
        $GLOBALS['symconTestTreeChildren'][$id] = [$id + 1];
    }
    $GLOBALS['symconTestTreeChildren'][86011] = [86012, 86013, 86014];
    foreach ([86012, 86013, 86014] as $id) {
        $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => 'Same variable',
            'ObjectIsHidden'                                   => $id === 86014];
        $GLOBALS['symconTestTreeValues'][$id] = ($id === 86012 ? '10' : '20') . ' %';
    }
    try {
        $module->SetTestProperty('RootObjectID', 86000);
        $module->SetTestProperty('MaxDepth', 12);
        $module->SetTestProperty('InitialDepth', 12);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewInitialDepth', 11);
        $module->SetTestProperty('IPSViewTreeLayout', 'radial');
        $before = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE,
            'Tree must accept twelve descendant levels and independent initial depths.'
        );
        assertGatewayGauge(
            $module->GetTestProperties() === $before,
            'Repeated ApplyChanges must not rewrite existing or deep Tree configuration.'
        );
        $model = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
        $node = $model['root'];
        for ($level = 0; $level < 12; ++$level) {
            assertGatewayGauge($node['id'] === 'object-' . (86000 + $level), 'Deep object IDs must stay stable.');
            $node = $node['children'][0];
        }
        assertGatewayGauge(
            $model['count'] === 14 && $node['id'] === 'object-86012'
            && $model['style']['initialDepth'] === 12 && !isset($model['root']['health']),
            'Tree must include the twelfth level and keep health annotation out of the plain family.'
        );
        $form = json_decode($module->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
        foreach (['MaxDepth', 'InitialDepth', 'IPSViewInitialDepth'] as $name) {
            assertGatewayGauge(
                findGaugeFormElement($form['elements'], $name)['maximum'] === 12,
                'All Tree form depth controls must permit twelve.'
            );
        }
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                && str_contains($html, '"id":"object-86012"')
                && str_contains($html, 'SYMC_ECHARTS_TREE'),
                'Both deep Tree outputs must include the data and shared navigation under the output limit.'
            );
        }
        $module->UpdateBarPreviewFromForm(json_encode(['InitialDepth' => 12, 'IPSViewInitialDepth' => 12], JSON_THROW_ON_ERROR));
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && str_contains($svg, 'data-depth="12"')
                && str_contains($svg, 'data-node="object-86012"') && !str_contains($svg, 'object-86014'),
                'Both Tree previews must support all twelve descendant levels and respect hidden branches.'
            );
            assertGatewayGauge(simplexml_load_string($svg) !== false, 'Deep previews must remain valid SVG.');
        }
        assertGatewayGauge($module->GetTestProperties() === $before, 'Tree preview drafts must not persist settings.');
        $module->SetTestProperty('VariableNameFilter', 'same');
        $module->ApplyChanges();
        assertGatewayGauge(
            json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree']['count'] === 14,
            'The name filter must find deep same-name variables without merging them.'
        );
        $module->SetTestProperty('MaxDepth', 6);
        $module->SetTestProperty('VariableNameFilter', '');
        $module->ApplyChanges();
        assertGatewayGauge(
            json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree']['count'] === 7,
            'Existing six-level selections must retain exactly their previous scope.'
        );
        foreach (['MaxDepth' => 201, 'InitialDepth' => 202, 'IPSViewInitialDepth' => 202] as $name => $status) {
            $module->SetTestProperty('MaxDepth', 12);
            $module->SetTestProperty('InitialDepth', 12);
            $module->SetTestProperty('IPSViewInitialDepth', 12);
            $module->SetTestProperty($name, 13);
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === $status, 'The thirteenth depth must remain invalid.');
        }
        $module->SetTestProperty('MaxDepth', 12);
        $module->SetTestProperty('InitialDepth', 2);
        $module->SetTestProperty('IPSViewInitialDepth', 2);
        $module->SetTestProperty('MaxNodes', 3000);
        $GLOBALS['symconTestTreeChildren'][86011] = range(87000, 89988);
        foreach ($GLOBALS['symconTestTreeChildren'][86011] as $id) {
            $GLOBALS['symconTestTreeObjects'][$id] = ['ObjectType' => 2, 'ObjectName' => str_repeat('N', 120),
                'ObjectIsHidden'                                   => false];
            $GLOBALS['symconTestTreeValues'][$id] = str_repeat('V', 100);
        }
        try {
            $module->ApplyChanges();
            $large = json_decode($module->GetTreeData(), true, 64, JSON_THROW_ON_ERROR)['tree'];
            assertGatewayGauge(
                $large['count'] === 3000 && $large['truncated'],
                'A twelve-level Tree must retain the existing 3000-node limit and visible truncation.'
            );
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
                assertGatewayGauge(
                    strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                    'Maximum deep Trees with long names and values must fit both output buffers.'
                );
                assertGatewayGauge(
                    preg_match('/window\.SYMC_VISUALIZATION = ([^\r\n]+);<\/script>/', $html, $match) === 1,
                    'Large Tree documents must retain the startup bootstrap.'
                );
                $bootstrap = json_decode($match[1], true, 64, JSON_THROW_ON_ERROR);
                $restored = json_decode(gzdecode(base64_decode($bootstrap['options']['compressedStateGzipBase64'], true)), true, 64, JSON_THROW_ON_ERROR);
                assertGatewayGauge(
                    $restored['chart']['tree']['count'] === 3000,
                    'Compression is not a new hard model limit or a silently truncated startup state.'
                );
            }
            assertGatewayGauge(
                strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both bounded deep previews must also fit the configuration form buffer.'
            );
            $module->SetTestProperty('MaxNodes', 3001);
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === 201, 'The existing node protection must not be relaxed.');
        } finally {
            foreach (range(87000, 89988) as $id) {
                unset($GLOBALS['symconTestTreeObjects'][$id], $GLOBALS['symconTestTreeValues'][$id]);
            }
        }
    } finally {
        foreach ($ids as $id) {
            unset($GLOBALS['symconTestTreeObjects'][$id], $GLOBALS['symconTestTreeChildren'][$id], $GLOBALS['symconTestTreeValues'][$id]);
        }
    }
})();
