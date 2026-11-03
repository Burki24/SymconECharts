<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsFunnel/module.php';

// Included by gateway_gauges.php; all identities, values and labels are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $savedArchiveQueries = $GLOBALS['symconTestArchiveQueryCount'];
    $requests = [];
    $gateway = new EChartsGateway();
    $gateway->Create();
    $gateway->ApplyChanges();
    $responder = static function (string $json) use ($gateway, &$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'current.read') {
            $requests[] = $request['Payload']['VariableID'];
        }
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    $sources = [];
    for ($index = 0; $index < 33; ++$index) {
        $id = 98700 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296 + $index,
            'Value'                                            => 10.0 + $index, 'Name' => 'Same synthetic stage',
            'Presentation'                                     => ['SUFFIX' => ' W', 'DIGITS' => $index % 7, 'MIN' => 0.0, 'MAX' => 1.0]];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'Color' => $index === 0 ? 0xABCDEF : -1];
    }
    $configure = static function (array $rows): EChartsFunnel
    {
        $module = new EChartsFunnel();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsFunnel $module): array => json_decode($module->GetFunnelData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Thirty-two-stage Funnel documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Funnel startup must remain accessible.');
        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    $previews = static function (EChartsFunnel $module): array
    {
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both Funnel draft previews must remain available.');
        return array_map(static function (array $image): DOMDocument
        {
            $document = new DOMDocument();
            $svg = (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Thirty-two-stage Funnel previews must remain valid SVG.');
            return $document;
        }, array_slice($images, -2));
    };
    $sizeError = 'The Funnel model is too large. Reduce source labels or units.';
    try {
        $rows = array_slice($sources, 0, 32);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Funnel must accept thirty-two distinct numeric current sources of the same unit.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        assertGatewayGauge($model['schemaVersion'] === 1 && $model['family'] === 'funnel'
            && count($model['funnel']['data']) === 32 && $model['funnel']['unit'] === 'W'
            && $properties['EnableSourceSelection'] === false && $model['funnel']['enableSourceSelection'] === false
            && array_column($model['funnel']['data'], 'initiallyVisible') === array_fill(0, 32, true)
            && array_column($model['funnel']['data'], 'id') === array_map(static fn (int $id): string => 'variable-' . $id, range(98700, 98731))
            && array_column($model['funnel']['data'], 'label') === array_fill(0, 32, 'Same synthetic stage')
            && $module->GetTestProperties() === $properties, 'Selection metadata must be additive and retain all same-name identities without rewriting source JSON or design.');
        foreach ($model['funnel']['data'] as $index => $item) {
            assertGatewayGauge($item['variableID'] === 98700 + $index && $item['timestamp'] === 1792233296 + $index
                && $item['unit'] === 'W' && $item['decimals'] === $index % 7 && $item['value'] === 10.0 + $index
                && $item['order'] === $index && $item['color'] === ($index === 0 ? '#ABCDEF' : ''), 'Every Funnel stage must preserve its raw value, native unit, precision, source order and explicit color.');
        }
        assertGatewayGauge($requests === range(98700, 98731) && $module->GetTestReferences() === range(98700, 98731), 'The real Gateway must read and reference every configured Funnel stage.');
        foreach (range(98700, 98731) as $id) {
            assertGatewayGauge(in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Every Funnel source must remain subscribed by ID: ' . $id);
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both output adapters must retain the complete Funnel model, not only a selected page.');
        }
        assertGatewayGauge(is_string($module->GetTestVariableValue('IPSViewFunnel')) && $module->GetTestVariableValue('IPSViewFunnel') !== '', 'The persistent Funnel IPSView document must remain nonempty.');

        $selectedRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $selectedRows[31]['InitiallyVisible'] = true;
        $module->SetTestProperty('Sources', json_encode($selectedRows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableSourceSelection', true);
        $module->ApplyChanges();
        $requests = [];
        $selected = $decode($module);
        assertGatewayGauge($selected['funnel']['enableSourceSelection'] === true && count($selected['funnel']['data']) === 32
            && array_column($selected['funnel']['data'], 'initiallyVisible') === [...array_fill(0, 31, false), true]
            && $requests === range(98700, 98731), 'Initial visibility must never filter complete model validation or Gateway reads.');
        $GLOBALS['symconTestVariables'][98730]['Value'] = 1000.0;
        $module->MessageSink(1792233400, 98730, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(count($updated['chart']['funnel']['data']) === 32
            && (float) $updated['chart']['funnel']['data'][30]['value'] === 1000.0
            && $updated['chart']['funnel']['data'][30]['initiallyVisible'] === false, 'Updates of hidden late sources must publish their fresh values, retaining the full-width scale input.');
        $GLOBALS['symconTestVariables'][98730]['Value'] = 40.0;

        $legacy = $configure(array_slice($rows, 0, 16));
        $legacyProperties = $legacy->GetTestProperties();
        $legacy->ApplyChanges();
        $legacy->ApplyChanges();
        assertGatewayGauge($legacy->GetTestProperties() === $legacyProperties && count($decode($legacy)['funnel']['data']) === 16, 'Legacy sixteen-stage configurations must survive repeated ApplyChanges without any persisted changes.');
        $one = $configure(array_slice($rows, 0, 1));
        $one->ApplyChanges();
        assertGatewayGauge($one->GetTestStatus() === IS_ACTIVE && count($decode($one)['funnel']['data']) === 1, 'The single-source minimum must remain valid.');
        $nullRows = [[...$rows[0], 'InitiallyVisible' => null]];
        $nullable = $configure($nullRows);
        $nullable->ApplyChanges();
        assertGatewayGauge($nullable->GetTestStatus() === IS_ACTIVE && $decode($nullable)['funnel']['data'][0]['initiallyVisible'] === true, 'Omitted and null optional initial visibility must retain the existing visible default.');
        $GLOBALS['symconTestVariables'][98731]['Name'] = 'Renamed stage';
        assertGatewayGauge($decode($module)['funnel']['data'][31]['id'] === 'variable-98731'
            && $decode($module)['funnel']['data'][31]['label'] === 'Renamed stage', 'Renaming a source changes its visible label, never its stable identity.');
        $GLOBALS['symconTestVariables'][98731]['Name'] = 'Same synthetic stage';
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewOrientation', 'horizontal');
        $module->SetTestProperty('IPSViewSortOrder', 'descending');
        $module->SetTestProperty('IPSViewEChartsTheme', 'dark');
        $tile = $startup($module->GetVisualizationTile())['chart'];
        $ipsView = $startup($module->GetIPSViewHTML())['chart'];
        assertGatewayGauge($tile['funnel']['data'] === $ipsView['funnel']['data']
            && $tile['funnel']['style']['orientation'] === 'vertical' && $ipsView['funnel']['style']['orientation'] === 'horizontal'
            && $tile['funnel']['style']['sortOrder'] === 'configured' && $ipsView['funnel']['style']['sortOrder'] === 'descending'
            && $ipsView['theme'] === 'dark' && $ipsView['funnel']['enableSourceSelection'] === true, 'Independent IPSView design must retain all original source and selection data while preserving its own geometry and sort order.');
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'EnableSourceSelection')['onChange'] ?? '', 'UpdateBarPreviewFromForm')
            && in_array('InitiallyVisible', array_column(findGaugeFormElement($form['elements'], 'Sources')['columns'], 'name'), true), 'Selection and per-source initial visibility must be editable and refresh live draft previews.');

        $beforeDraft = $module->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Funnel draft previews must not request Gateway data.');
        };
        $GLOBALS['symconTestVariables'][98730]['Value'] = 1000.0;
        $GLOBALS['symconTestVariables'][98731]['Value'] = 100.0;
        foreach (['configured', 'ascending', 'descending'] as $sort) {
            $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableSourceSelection' => false,
                'SortOrder'                                          => $sort, 'Title' => '<Unsaved>', 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
            foreach ($previews($module) as $document) {
                $polygons = (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-stage]');
                assertGatewayGauge($document->documentElement->getAttribute('data-source-count') === '32'
                    && $document->documentElement->getAttribute('data-selected-count') === '32'
                    && $document->documentElement->getAttribute('data-rendered-count') === '8'
                    && (float) $document->documentElement->getAttribute('data-maximum') === 1000.0
                    && $polygons->length === 8 && str_contains($document->saveXML(), '&lt;Unsaved&gt;'), 'Large Funnel previews must disclose their eight-stage sample and keep full-model maximum before filtering and sampling.');
                $expectedFirst = $sort === 'descending' ? 98730 : 98700;
                assertGatewayGauge($polygons->item(0)->getAttribute('data-source-id') === 'variable-' . $expectedFirst, 'Preview sampling must occur after stable sorting.');
            }
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $selectedRows, 'EnableSourceSelection' => true,
            'Orientation'                                        => 'vertical', 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            $polygons = (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-stage]');
            assertGatewayGauge($document->documentElement->getAttribute('data-selected-count') === '1'
                && $document->documentElement->getAttribute('data-rendered-count') === '1' && $polygons->length === 1
                && (float) $document->documentElement->getAttribute('data-maximum') === 1000.0
                && $polygons->item(0)->getAttribute('data-source-id') === 'variable-98731'
                && $polygons->item(0)->getAttribute('data-stage') === '31'
                && (float) $polygons->item(0)->getAttribute('data-value') === 100.0, 'A selected late stage must survive sampling, retaining full source order, original value and hidden maximum.');
            $palette = SymconECharts\EChartsBarPreview::Palette('auto');
            assertGatewayGauge($polygons->item(0)->getAttribute('fill') === $palette['seriesColors'][31 % count($palette['seriesColors'])], 'A late-only selected stage must retain its original full-model palette color.');
            $points = preg_split('/\s+/', trim($polygons->item(0)->getAttribute('points')));
            $left = array_map('floatval', explode(',', $points[0]));
            $right = array_map('floatval', explode(',', $points[1]));
            assertGatewayGauge(abs(abs($right[0] - $left[0]) - 68.4) < 0.01, 'A selected 100 W stage must remain one tenth of the complete 1000 W width scale, never expand to full width.');
        }
        $noneRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-selected-count') === '0'
                && (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-stage]')->length === 0
                && str_contains($document->saveXML(), 'No sources selected'), 'An empty selection must show an explicit empty state rather than fabricate stages.');
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-selected-count') === '32', 'Disabled source selection must ignore row visibility without removing that metadata.');
        }
        $GLOBALS['symconTestVariables'][98731]['Value'] = 0.0;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $selectedRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge((float) $document->documentElement->getAttribute('data-maximum') === 1000.0
                && (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-stage]')->length === 0
                && str_contains($document->saveXML(), 'All selected stage values are zero.'), 'A selected zero must not inherit a hidden nonzero stage or fabricate a filled chart.');
        }
        $GLOBALS['symconTestVariables'][98731]['Value'] = 100.0;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => array_slice($rows, 0, 16), 'EnableSourceSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge((new DOMXPath($document))->query('//*[local-name()="polygon" and @data-stage]')->length === 16, 'Small legacy previews with disabled selection must retain all original sixteen polygons.');
        }
        assertGatewayGauge($module->GetTestProperties() === $beforeDraft && $module->GetTestStatus() === IS_ACTIVE
            && $GLOBALS['symconTestArchiveQueryCount'] === $savedArchiveQueries, 'Unsaved source, selection, title and independent design drafts must not persist or read archives.');
        IPSModuleStrict::$ParentResponder = $responder;
        $GLOBALS['symconTestVariables'][98730]['Value'] = 40.0;
        $GLOBALS['symconTestVariables'][98731]['Value'] = 41.0;

        foreach ([$sources, [], [...array_slice($rows, 0, 31), $rows[0]],
            [...array_slice($rows, 0, 31), [...$rows[31], 'InitiallyVisible' => 'false']],
            [...array_slice($rows, 0, 31), [...$rows[31], 'InitiallyVisible' => 0]]] as $invalidRows) {
            $invalid = $configure($invalidRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Empty or thirty-three stages, duplicate IDs and nonboolean visibility must fail source validation.');
        }
        $GLOBALS['symconTestVariables'][98730]['Presentation']['SUFFIX'] = ' °C';
        $invalid = $configure($selectedRows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A hidden source with a different native unit must remain invalid.');
        $GLOBALS['symconTestVariables'][98730]['Presentation']['SUFFIX'] = ' W';
        $GLOBALS['symconTestVariables'][98730]['VariableType'] = 0;
        $invalid = $configure($selectedRows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A hidden nonnumeric source must remain invalid.');
        $GLOBALS['symconTestVariables'][98730]['VariableType'] = 2;
        foreach ([NAN, INF, -INF, 'not numeric', -1.0] as $value) {
            $GLOBALS['symconTestVariables'][98730]['Value'] = $value;
            assertGatewayGauge(strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Invalid hidden values must not prevent opening the Funnel configuration.');
            $failure = null;
            try {
                $module->GetFunnelData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge($failure !== null && $module->GetTestStatus() === ($value === -1.0 ? 205 : 204), 'Invalid hidden current values must fail the complete Funnel model, never silently disappear.');
        }
        $GLOBALS['symconTestVariables'][98730]['Value'] = 0.0;
        assertGatewayGauge($decode($module)['funnel']['data'][30]['value'] === 0.0 && $module->GetTestStatus() === IS_ACTIVE, 'A corrected zero remains an original zero without normalization.');
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder): string
        {
            $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            if (($request['Payload']['VariableID'] ?? null) === 98730) {
                throw new RuntimeException('Synthetic hidden-source disconnect.');
            }
            return $responder($json);
        };
        assertGatewayGauge($startup($module->GetVisualizationTile())['status'] === 'error' && $module->GetTestStatus() === 204, 'A hidden-source transport failure must fail the full current model rather than pretend to be ready.');
        IPSModuleStrict::$ParentResponder = $responder;
        $GLOBALS['symconTestVariables'][98730]['Value'] = 40.0;

        foreach (range(98700, 98731) as $id) {
            $GLOBALS['symconTestVariables'][$id]['Presentation']['SUFFIX'] = ' 測<&> W';
        }
        $stress = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<>&測', 29)], $rows));
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('Title', str_repeat('<&測>', 30));
        $stress->ApplyChanges();
        $stressModel = $decode($stress);
        assertGatewayGauge($stressModel['funnel']['unit'] === '測<&> W'
            && array_column($stressModel['funnel']['data'], 'unit') === array_fill(0, 32, '測<&> W'), 'Unicode units must remain original native units, not normalized percentages or presentation bounds.');
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $stressModel, 'Long valid UTF-8 labels must survive both complete bounded Funnel outputs.');
        }
        assertGatewayGauge(strlen($stress->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The complete thirty-two-source form and previews must fit the output buffer.');
        foreach (range(98700, 98731) as $id) {
            $GLOBALS['symconTestVariables'][$id]['Presentation']['SUFFIX'] = str_repeat('X', 7000);
        }
        $huge = $configure($rows);
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetFunnelData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $huge->GetTestStatus() === 202
            && $failure->getMessage() === $sizeError && json_decode($huge->GetFunnelDiagnostic(), true)['lastError'] === 'MODEL_TOO_LARGE'
            && $huge->GetTestProperties() === $hugeProperties, 'Models over the JSON budget must fail explicitly without truncating sources or rewriting design.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && $state['error'] === $sizeError, 'Oversized Funnel models must return bounded actionable errors in both adapters.');
        }
        foreach (range(98700, 98731) as $id) {
            $GLOBALS['symconTestVariables'][$id]['Presentation']['SUFFIX'] = str_repeat('<', 2800);
        }
        $escaped = $configure($rows);
        $escapedProperties = $escaped->GetTestProperties();
        assertGatewayGauge(strlen($escaped->GetFunnelData()) < 200000 && count($decode($escaped)['funnel']['data']) === 32, 'The final HTML guard must be exercised from a complete valid model below the JSON byte limit.');
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && $state['error'] === $sizeError, 'HTML escaping beyond the final buffer must produce a bounded actionable error rather than truncated data.');
        }
        assertGatewayGauge($escaped->GetTestStatus() === 202 && $escaped->GetTestProperties() === $escapedProperties, 'Final HTML guard recovery must not modify persisted sources or design.');
        foreach (range(98700, 98731) as $id) {
            $GLOBALS['symconTestVariables'][$id]['Presentation']['SUFFIX'] = ' W';
            $GLOBALS['symconTestVariables'][$id]['Value'] = 0.0;
        }
        assertGatewayGauge(array_sum(array_column($decode($module)['funnel']['data'], 'value')) === 0.0 && $module->GetTestStatus() === IS_ACTIVE, 'Thirty-two all-zero stages remain valid zeros, never invented widths or values.');
        $module->SetTestProperty('Sources', json_encode(array_slice($rows, 0, 1), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestReferences() === [98700]
            && !in_array(['SenderID' => 98731, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Removed Funnel stages must unregister stale references and subscriptions.');
        echo 'Funnel thirty-two-source bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR)) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveQueryCount'] = $savedArchiveQueries;
    }
})();
