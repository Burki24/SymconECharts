<?php

declare(strict_types=1);

// Uses the real modules and Gateway; only the synthetic Symcon kernel is replaced.
(static function (): void
{
    $sources = [
        ['VariableID' => 4711, 'GroupPath' => 'House / Climate', 'Label' => 'Same',
            'Minimum' => -20, 'Maximum' => 60, 'UseVariablePresentation' => false, 'Unit' => '°C', 'Decimals' => 1],
        ['VariableID' => 4717, 'GroupPath' => 'House / Climate', 'Label' => 'Same',
            'Minimum' => 0, 'Maximum' => 100, 'UseVariablePresentation' => false, 'Unit' => '%', 'Decimals' => 0]
    ];
    $saved = $GLOBALS['symconTestVariables'];
    try {
        $GLOBALS['symconTestVariables'][4711]['Value'] = 20.0;
        $GLOBALS['symconTestVariables'][4717]['Value'] = 80.0;
        foreach ([[EChartsTreemap::class, 'GetTreemapData', 'treemap'], [EChartsSunburst::class, 'GetSunburstData', 'sunburst']] as [$class, $method, $key]) {
            $module = new $class();
            $module->Create();
            $stored = json_encode($sources, JSON_THROW_ON_ERROR);
            $module->SetTestProperty('Sources', $stored);
            $module->SetTestProperty('NormalizeValues', true);
            $module->SetTestProperty('EnableIPSView', true);
            $module->SetTestProperty('IPSViewUseTileDesign', false);
            $module->SetTestProperty('IPSViewLabelFontSizePercent', 150);
            $module->ApplyChanges();
            $model = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key];
            $leaves = $model['data'][0]['children'][0]['children'];
            assertGatewayGauge(
                $module->GetTestStatus() === IS_ACTIVE && ($model['normalized'] ?? false) === true
                && $model['total'] === 130.0 && array_column($leaves, 'value') === [50.0, 80.0]
                && array_column($leaves, 'originalValue') === [20.0, 80.0]
                && array_column($leaves, 'unit') === ['°C', '%'] && array_column($leaves, 'decimals') === [1, 0],
                $class . ' must project per-source weights while retaining independent original formats.'
            );
            foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetTestVariableValue('IPSView' . ucfirst($key))] as $index => $html) {
                assertGatewayGauge(
                    preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $match) === 1,
                    'Each hierarchy output must contain a usable bootstrap.'
                );
                $boot = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                assertGatewayGauge(
                    $boot['state']['chart'][$key]['normalized'] === true && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT
                    && $boot['state']['chart'][$key]['style']['labelFontSizePercent'] === ($index === 0 ? 100 : 150),
                    'Both normalized adapters and the published document retain independent designs and output limits.'
                );
            }
            $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $list = findGaugeFormElement($form['elements'], 'Sources');
            assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'NormalizeValues')['onChange'] ?? '', 'UpdateBarPreviewFromForm')
                && findGaugeFormElement($list['form'], 'UseVariablePresentation') !== null
                && findGaugeFormElement($list['form'], 'Minimum') !== null
                && findGaugeFormElement($list['form'], 'Decimals') !== null, 'Hierarchy forms expose ranges, original formatting and draft preview updates.');
            $module->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $sources], JSON_THROW_ON_ERROR));
            foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-normalized="true"')
                    && str_contains($svg, '°C') && str_contains($svg, '%'), 'Both previews show original-format examples and identify normalized weights.');
            }
            foreach (['Minimum' => 100, 'Decimals' => 7, 'Unit' => [], 'UseVariablePresentation' => 'false'] as $field => $value) {
                $bad = $sources;
                $bad[1][$field] = $value;
                $module->SetTestProperty('Sources', json_encode($bad, JSON_THROW_ON_ERROR));
                $module->ApplyChanges();
                assertGatewayGauge($module->GetTestStatus() === 201, 'Invalid late source ranges or formatting must reject the entire hierarchy.');
            }
            $module->SetTestProperty('Sources', $stored);
            $module->ApplyChanges();
            $module->ApplyChanges();
            $read = new ReflectionMethod($module, 'ReadPropertyString');
            assertGatewayGauge($read->invoke($module, 'Sources') === $stored, 'Hierarchy normalization must not rewrite source configuration.');

            // Native formatting does not choose the geometry's explicit range.
            $GLOBALS['symconTestVariables'][4711]['Presentation'] = ['SUFFIX' => 'native', 'DIGITS' => 2, 'MIN' => 0, 'MAX' => 10000];
            $native = $sources;
            $native[0]['UseVariablePresentation'] = true;
            $module->SetTestProperty('Sources', json_encode($native, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            $leaf = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key]['data'][0]['children'][0]['children'][0];
            assertGatewayGauge($leaf['value'] === 50.0 && $leaf['minimum'] === -20.0 && $leaf['maximum'] === 60.0
                && $leaf['unit'] === 'native' && $leaf['decimals'] === 2, 'Native units/digits must not override explicit normalization limits.');
            unset($GLOBALS['symconTestVariables'][4711]['Presentation']);

            foreach ([[-1000.0, 0.0], [1000.0, 100.0], [0.0, 25.0]] as [$original, $weight]) {
                $GLOBALS['symconTestVariables'][4711]['Value'] = $original;
                $module->SetTestProperty('Sources', $stored);
                $module->ApplyChanges();
                $leaf = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key]['data'][0]['children'][0]['children'][0];
                assertGatewayGauge(
                    $leaf['value'] === $weight && $leaf['originalValue'] === $original,
                    'Only geometry is clamped, including negative and zero original values.'
                );
            }
            $GLOBALS['symconTestVariables'][4711]['Value'] = 20.0;

            $legacy = [['VariableID' => 4711, 'Group' => 'Literal / old', 'Label' => 'Same'],
                ['VariableID' => 4717, 'Group' => 'Literal / old', 'Label' => 'Same']];
            $module->SetTestProperty('NormalizeValues', false);
            $module->SetTestProperty('Sources', json_encode($legacy, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            $old = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key];
            assertGatewayGauge(
                !isset($old['normalized']) && !isset($old['data'][0]['children'][0]['originalValue'])
                && $old['data'][0]['name'] === 'Literal / old' && $old['total'] === 100.0,
                'Old rows retain raw geometry and their original unformatted model without automatic metadata migration.'
            );
            $module->SetTestProperty('Sources', $stored);
            $module->ApplyChanges();
            $formatted = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key];
            assertGatewayGauge(!isset($formatted['normalized']) && $formatted['data'][0]['children'][0]['children'][0]['value'] === 20.0
                && $formatted['data'][0]['children'][0]['children'][0]['unit'] === '°C', 'Per-leaf formatting is independently usable with raw weights.');

            // A full edited draft is validated, but neither properties nor Gateway reads change.
            $badDraft = $sources;
            $badDraft[1]['Maximum'] = 0;
            $module->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $badDraft], JSON_THROW_ON_ERROR));
            foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(
                    !str_contains($svg, 'data-node=') && str_contains($svg, 'minimum below maximum'),
                    'Invalid draft ranges produce a diagnostic, not a plausible example tree.'
                );
            }
            assertGatewayGauge($read->invoke($module, 'Sources') === $stored, 'Draft ranges must never persist into live configuration.');

            $maximum = [];
            for ($n = 0; $n < 100; ++$n) {
                $id = 87000 + $n;
                $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000300, 'Value' => 50.0, 'Name' => 'Same'];
                $maximum[] = ['VariableID' => $id, 'GroupPath' => implode(' / ', array_map(static fn (int $d): string => 'Branch ' . $n . '-' . $d, range(1, 7))),
                    'Label'                => 'Same', 'Minimum' => 0, 'Maximum' => 100, 'Unit' => '%', 'Decimals' => 1, 'UseVariablePresentation' => false];
            }
            $module->SetTestProperty('NormalizeValues', true);
            $module->SetTestProperty('Sources', json_encode($maximum, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            $large = json_decode($module->$method(), true, 512, JSON_THROW_ON_ERROR)[$key];
            assertGatewayGauge(
                $large['nodeCount'] === 800 && $large['depth'] === 8 && $large['total'] === 5000.0
                && $large['positiveCount'] === 100 && count($module->GetTestReferences()) === 100,
                'All 100 normalized sources retain their eight levels, 800 nodes, values and subscriptions.'
            );
            $sizes = array_map('strlen', [$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetConfigurationForm()]);
            assertGatewayGauge(max($sizes) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both normalized documents and full previews must fit their original output budget.');
            echo $class . ' normalized 100/800 bytes: ' . implode(', ', $sizes) . ".\n";
            $maximum[] = $maximum[0];
            $module->SetTestProperty('Sources', json_encode($maximum, JSON_THROW_ON_ERROR));
            $module->ApplyChanges();
            assertGatewayGauge($module->GetTestStatus() === 201, 'The hierarchy source limit remains 100, not 101.');
        }
    } finally {
        $GLOBALS['symconTestVariables'] = $saved;
    }
})();
echo "Normalized hierarchy integration verified.\n";
