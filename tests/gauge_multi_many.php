<?php

declare(strict_types=1);

// Included by gateway_gauges.php; all identities, values and labels are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
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
        $id = 98500 + $index;
        $minimum = -20.0 - $index * 10;
        $maximum = 80.0 + $index * 10;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296 + $index,
            'Value'                                            => ($minimum + $maximum) / 2, 'Name' => 'Same synthetic source'];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'Minimum' => $minimum, 'Maximum' => $maximum,
            'Unit'                 => $index % 2 === 0 ? '°C' : 'hPa', 'Decimals' => $index % 7, 'UseVariablePresentation' => false];
    }
    $configure = static function (array $rows): EChartsGaugeMulti
    {
        $module = new EChartsGaugeMulti();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsGaugeMulti $module): array => json_decode($module->GetGaugeData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Thirty-two-source Gauge Multi documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Gauge Multi startup must remain accessible.');
        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    $previews = static function (EChartsGaugeMulti $module): array
    {
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['GaugePreview', 'IPSViewGaugePreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both Gauge Multi draft previews must remain available.');
        return array_map(static function (array $image): DOMDocument
        {
            $svg = (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Thirty-two-source Gauge Multi previews must remain valid SVG.');
            return $document;
        }, array_slice($images, -2));
    };
    try {
        $rows = array_slice($sources, 0, 32);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE && count($decode($module)['items']) === 32
            && $module->GetTestSummary() === '32 sources', 'Gauge Multi must accept thirty-two distinct numeric sources with individual ranges and mixed units.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        assertGatewayGauge($model['schemaVersion'] === 1 && $model['family'] === 'gauge' && $model['variant'] === 'multi'
            && $properties['EnableSourceSelection'] === false && $model['gauge']['enableSourceSelection'] === false
            && array_column($model['items'], 'initiallyVisible') === array_fill(0, 32, true)
            && array_column($model['items'], 'id') === array_map(static fn (int $id): string => 'variable-' . $id, range(98500, 98531))
            && array_column(array_column($model['items'], 'gauge'), 'label') === array_fill(0, 32, 'Same synthetic source')
            && $module->GetTestProperties() === $properties, 'Default selection metadata must be additive and preserve all same-name source identities and persisted properties.');
        foreach ($model['items'] as $index => $item) {
            assertGatewayGauge($item['source'] === ['variableID' => 98500 + $index, 'timestamp' => 1792233296 + $index]
                && $item['gauge']['minimum'] === $rows[$index]['Minimum'] && $item['gauge']['maximum'] === $rows[$index]['Maximum']
                && $item['gauge']['unit'] === $rows[$index]['Unit'] && $item['gauge']['decimals'] === $rows[$index]['Decimals']
                && $item['value'] === 30.0, 'Each of thirty-two sources must retain its independent range, unit, precision, value and timestamp.');
        }
        assertGatewayGauge($requests === range(98500, 98531) && $module->GetTestReferences() === range(98500, 98531), 'The real Gateway must read and reference every configured Gauge Multi source.');
        foreach (range(98500, 98531) as $id) {
            assertGatewayGauge(in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Every Gauge Multi source must remain subscribed by ID: ' . $id);
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both adapters must retain every Gauge Multi source without truncation.');
        }
        assertGatewayGauge(is_string($module->GetTestVariableValue('IPSViewGauge')) && $module->GetTestVariableValue('IPSViewGauge') !== '', 'The persistent Gauge Multi IPSView document must remain nonempty.');

        $selectedRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $selectedRows[31]['InitiallyVisible'] = true;
        $module->SetTestProperty('Sources', json_encode($selectedRows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableSourceSelection', true);
        $module->ApplyChanges();
        $requests = [];
        $selected = $decode($module);
        assertGatewayGauge($selected['gauge']['enableSourceSelection'] === true && count($selected['items']) === 32
            && array_column($selected['items'], 'initiallyVisible') === [...array_fill(0, 31, false), true]
            && $requests === range(98500, 98531), 'Selection must never filter reads or the complete per-source model.');
        $GLOBALS['symconTestVariables'][98530]['Value'] = -250.0;
        $module->MessageSink(1792233400, 98530, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(count($updated['chart']['items']) === 32 && $updated['chart']['items'][30]['value'] === -250.0
            && $updated['chart']['items'][30]['initiallyVisible'] === false && $updated['chart']['items'][30]['gauge'] === $model['items'][30]['gauge'], 'A hidden source update must publish fresh complete data without changing its scale or identity.');
        $GLOBALS['symconTestVariables'][98530]['Value'] = 30.0;

        $legacy = $configure(array_slice($rows, 0, 16));
        $legacyProperties = $legacy->GetTestProperties();
        $legacy->ApplyChanges();
        $legacy->ApplyChanges();
        assertGatewayGauge($legacy->GetTestProperties() === $legacyProperties && count($decode($legacy)['items']) === 16, 'Existing sixteen-source JSON without selection fields must remain unchanged after repeated ApplyChanges.');
        $minimum = $configure(array_slice($rows, 0, 2));
        $minimum->ApplyChanges();
        assertGatewayGauge($minimum->GetTestStatus() === IS_ACTIVE && count($decode($minimum)['items']) === 2, 'The existing two-source minimum must remain valid.');
        $GLOBALS['symconTestVariables'][98531]['Presentation'] = ['MIN' => 10.0, 'MAX' => 50.0, 'DIGITS' => 2, 'SUFFIX' => ' m/s'];
        $presentedRows = $selectedRows;
        $presentedRows[31]['UseVariablePresentation'] = true;
        $presented = $configure($presentedRows);
        $presented->SetTestProperty('EnableSourceSelection', true);
        $presentedProperties = $presented->GetTestProperties();
        $presented->ApplyChanges();
        $presentedLast = $decode($presented)['items'][31];
        assertGatewayGauge($presentedLast['gauge']['minimum'] === 10.0 && $presentedLast['gauge']['maximum'] === 50.0
            && $presentedLast['gauge']['unit'] === 'm/s' && $presentedLast['gauge']['decimals'] === 2
            && $presented->GetTestProperties() === $presentedProperties, 'Native presentation overrides and manual fallback metadata must remain per source without rewriting the persisted thirty-two-source configuration.');
        unset($GLOBALS['symconTestVariables'][98531]['Presentation']);
        $GLOBALS['symconTestVariables'][98531]['Name'] = 'Renamed synthetic source';
        assertGatewayGauge($decode($module)['items'][31]['id'] === 'variable-98531' && $decode($module)['items'][31]['gauge']['label'] === 'Renamed synthetic source', 'A rename changes only the visible name, never source identity.');
        $GLOBALS['symconTestVariables'][98531]['Name'] = 'Same synthetic source';
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'EnableSourceSelection')['onChange'] ?? '', 'UpdateGaugePreviewFromForm')
            && in_array('InitiallyVisible', array_column(findGaugeFormElement($form['elements'], 'Sources')['columns'], 'name'), true), 'The selection switch and initial visibility must be editable and refresh live draft previews.');

        foreach (['multi-title', 'ring-grid', 'ring-concentric', 'weather-station'] as $preset) {
            $module->SetTestProperty('GaugePreset', $preset);
            $module->SetTestProperty('IPSViewUseTileDesign', false);
            $module->SetTestProperty('IPSViewGaugePreset', 'ring-grid');
            $module->SetTestProperty('IPSViewEChartsTheme', 'dark');
            $tile = $startup($module->GetVisualizationTile())['chart'];
            $ipsView = $startup($module->GetIPSViewHTML())['chart'];
            assertGatewayGauge($tile['gauge']['preset'] === $preset && $ipsView['gauge']['preset'] === 'ring-grid'
                && $ipsView['theme'] === 'dark' && $tile['items'] === $ipsView['items'] && count($tile['items']) === 32
                && $ipsView['gauge']['enableSourceSelection'] === true, 'All four presets and an independent IPSView design must share complete, independently scaled sources and selection metadata.');
        }
        $beforeDraft = $module->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Gauge Multi draft previews must not request Gateway data.');
        };
        foreach (['multi-title', 'ring-grid', 'ring-concentric', 'weather-station'] as $preset) {
            $module->UpdateGaugePreviewFromForm(json_encode(['Sources' => $rows, 'EnableSourceSelection' => false, 'GaugePreset' => $preset,
                'IPSViewUseTileDesign'                                 => true, 'Title' => '<Unsaved>'], JSON_THROW_ON_ERROR));
            foreach ($previews($module) as $document) {
                $svg = $document->saveXML();
                assertGatewayGauge(str_contains($svg, 'data-preview-sources="4/32"') && str_contains($svg, '&lt;Unsaved&gt;')
                    && $document->documentElement->getAttribute('data-gauge-source-count') === '32'
                    && $document->documentElement->getAttribute('data-gauge-visible-count') === '32', 'Every supported preview must disclose its bounded four-of-thirty-two sample and escape unsaved titles.');
            }
            $module->UpdateGaugePreviewFromForm(json_encode(['Sources' => $selectedRows, 'EnableSourceSelection' => true, 'GaugePreset' => $preset,
                'IPSViewUseTileDesign'                                 => true], JSON_THROW_ON_ERROR));
            foreach ($previews($module) as $document) {
                $xpath = new DOMXPath($document);
                assertGatewayGauge($document->documentElement->getAttribute('data-gauge-source-count') === '32'
                    && $document->documentElement->getAttribute('data-gauge-visible-count') === '1'
                    && str_contains($document->saveXML(), 'data-preview-sources="1/32"')
                    && $xpath->query('//*[@data-gauge-id="variable-98531"]')->length === 1, 'Initial selection must precede preview sampling so a selected thirty-second source remains visible.');
                $instrument = $xpath->query('//*[@data-gauge-id="variable-98531"]')->item(0);
                assertGatewayGauge((float) $instrument->getAttribute('data-gauge-minimum') === -330.0
                    && (float) $instrument->getAttribute('data-gauge-maximum') === 390.0
                    && (float) $instrument->getAttribute('data-gauge-value') === 30.0
                    && abs((float) $instrument->getAttribute('data-gauge-ratio') - 0.5) < 1e-9, 'A selected late source must preserve its own signed range and midpoint rather than receive a global or page scale.');
                if (in_array($preset, ['ring-grid', 'ring-concentric'], true)) {
                    $arc = $xpath->query('.//*[local-name()="circle" and @stroke-dasharray]', $instrument)->item(0);
                    assertGatewayGauge($arc !== null, 'A selected midpoint source must have a real ring arc.');
                    $dash = array_map('floatval', preg_split('/\\s+/', $arc->getAttribute('stroke-dasharray')));
                    assertGatewayGauge(abs($dash[0] / $dash[1] - 0.5) < 0.001 && $arc->getAttribute('stroke') === '#64B8A4', 'A selected late midpoint must draw half a ring and retain its actual runtime source-order color, independent of selection and page.');
                }
            }
        }
        $noneRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $module->UpdateGaugePreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-gauge-visible-count') === '0'
                && $document->documentElement->getAttribute('data-gauge-source-count') === '32'
                && (new DOMXPath($document))->query('//*[@data-gauge-id]')->length === 0, 'An empty selection must render an explicit empty state, never synthetic sample instruments.');
        }
        $module->UpdateGaugePreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-gauge-visible-count') === '32', 'Disabled selection must ignore saved initial visibility.');
        }
        assertGatewayGauge($module->GetTestProperties() === $beforeDraft, 'Unsaved source, selection, title, preset and independent IPSView drafts must not persist.');
        IPSModuleStrict::$ParentResponder = $responder;

        foreach ([$sources, [], array_slice($rows, 0, 1), [...array_slice($rows, 0, 31), $rows[0]],
            [...array_slice($rows, 0, 31), [...$rows[31], 'InitiallyVisible' => 'false']],
            [...array_slice($rows, 0, 31), [...$rows[31], 'InitiallyVisible' => 0]]] as $invalidRows) {
            $invalid = $configure($invalidRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Empty, one or thirty-three rows, duplicate IDs and nonboolean visibility must fail source validation.');
        }
        foreach ([['Minimum' => 100.0, 'Maximum' => 100.0], ['Minimum' => 100.0, 'Maximum' => 0.0], ['Decimals' => 7]] as $badRange) {
            $invalid = $configure([...array_slice($selectedRows, 0, 31), [...$selectedRows[31], ...$badRange, 'InitiallyVisible' => false]]);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 202, 'Invalid scales or precision must be rejected even for a hidden source.');
        }
        $GLOBALS['symconTestVariables'][98530]['VariableType'] = 0;
        $invalid = $configure($selectedRows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A nonnumeric hidden source must remain invalid.');
        $GLOBALS['symconTestVariables'][98530]['VariableType'] = 2;
        foreach ([NAN, INF, -INF, 'not numeric'] as $value) {
            $GLOBALS['symconTestVariables'][98530]['Value'] = $value;
            assertGatewayGauge(strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Nonfinite sources must not prevent opening the configuration form.');
            $failure = null;
            try {
                $module->GetGaugeData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge($failure !== null && $module->GetTestStatus() === 204, 'A nonnumeric or nonfinite hidden source must fail the complete model rather than silently disappear.');
        }
        $GLOBALS['symconTestVariables'][98530]['Value'] = -400.0;
        assertGatewayGauge($decode($module)['items'][30]['value'] === -400.0 && $module->GetTestStatus() === IS_ACTIVE, 'Corrected out-of-range values remain original values with their configured scale.');
        $GLOBALS['symconTestVariables'][98530]['Value'] = 30.0;

        $stress = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<>&測', 30), 'Decimals' => 6], $rows));
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('Title', str_repeat('<&測>', 30));
        $stress->ApplyChanges();
        $stressModel = $decode($stress);
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $stressModel, 'Long UTF-8 labels and high precision must survive both complete bounded outputs.');
        }
        assertGatewayGauge(strlen($stress->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The complete thirty-two-source form and both previews must fit the buffer.');
        $backgroundPath = 'M0 0 ' . str_repeat('L1 1 ', 9500);
        $backgroundSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . str_repeat('<path d="' . $backgroundPath . '" fill="none" stroke="#123456"/>', 5) . '</svg>';
        $largeDesign = $configure($rows);
        $largeDesign->SetTestProperty('PlateDesignMode', 'custom');
        $largeDesign->SetTestProperty('PlateBackgroundEnabled', true);
        $largeDesign->SetTestProperty('PlateBackgroundSVG', base64_encode($backgroundSvg));
        $largeDesign->ApplyChanges();
        $largeDesignModel = $decode($largeDesign);
        assertGatewayGauge(strlen($backgroundSvg) > 230000 && strlen($backgroundSvg) < 262144 && strlen($largeDesign->GetGaugeData()) > 200000
            && $largeDesign->GetTestStatus() === IS_ACTIVE, 'An established near-limit validated SVG background must remain valid with thirty-two sources.');
        foreach ([$largeDesign->GetVisualizationTile(), $largeDesign->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $largeDesignModel, 'Large valid SVG imports must retain their complete original model in both bounded output documents.');
        }
        $huge = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('X', 30000)], $rows));
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetGaugeData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $huge->GetTestStatus() === 205 && str_contains(strtolower($failure->getMessage()), 'large')
            && $huge->GetTestProperties() === $hugeProperties, 'Oversized Gauge Multi metadata must fail explicitly without truncation or persistent changes.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'Oversized models must produce bounded actionable errors in both adapters.');
        }
        $escaped = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<', 6000)], $rows));
        $escapedProperties = $escaped->GetTestProperties();
        assertGatewayGauge(strlen($escaped->GetGaugeData()) < 900000 && count($decode($escaped)['items']) === 32, 'The final HTML guard must be exercised with a complete model below its own JSON budget.');
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'Escaping beyond the final HTML buffer must yield a bounded actionable error, never a partial chart.');
        }
        assertGatewayGauge($escaped->GetTestStatus() === 205 && $escaped->GetTestProperties() === $escapedProperties, 'Output-size recovery must not modify persisted configuration or design.');
        $module->SetTestProperty('Sources', json_encode(array_slice($rows, 0, 2), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestReferences() === [98500, 98501] && !in_array(['SenderID' => 98531, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Removing sources must unregister stale references and subscriptions.');
        echo 'Gauge Multi thirty-two-source bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR)) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
    }
})();
