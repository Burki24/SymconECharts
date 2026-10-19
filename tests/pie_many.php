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
    for ($index = 0; $index < 101; ++$index) {
        $id = 98700 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296,
            'Value'                                            => 1.0, 'Name' => 'Same synthetic source'];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'UseVariablePresentation' => false,
            'Unit'                 => 'W', 'Decimals' => $index === 99 ? 6 : 1];
    }
    $configure = static function (array $rows): EChartsPie
    {
        $module = new EChartsPie();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsPie $module): array => json_decode($module->GetPieData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Hundred-source Pie documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Pie startup must remain accessible.');
        $configuration = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $compressed = $configuration['options']['compressedStateGzipBase64'] ?? null;
        return is_string($compressed) ? json_decode(gzdecode(base64_decode($compressed, true)), true, 512, JSON_THROW_ON_ERROR) : $configuration['state'];
    };
    $previewSVGs = static function (EChartsPie $module): array
    {
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both Pie previews must remain available.');
        return array_map(static fn (array $image): string => (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true), array_slice($images, -2));
    };

    try {
        $rows = array_slice($sources, 0, 100);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Pie must accept one hundred distinct numeric sources.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        assertGatewayGauge(
            $model['schemaVersion'] === 1 && $model['family'] === 'pie' && count($model['items']) === 100
            && array_column($model['items'], 'id') === array_map(static fn (int $id): string => 'variable-' . $id, range(98700, 98799))
            && array_column($model['items'], 'name') === array_fill(0, 100, 'Same synthetic source')
            && array_column($model['items'], 'value') === array_fill(0, 100, 1.0)
            && array_column($model['items'], 'initiallyVisible') === array_fill(0, 100, true)
            && $properties['EnableSourceSelection'] === false && $model['pie']['enableSourceSelection'] === false
            && $module->GetTestProperties() === $properties,
            'One hundred same-name sources must retain identity, values and unchanged configuration with selection disabled by default.'
        );
        assertGatewayGauge($requests === range(98700, 98799) && $module->GetTestReferences() === range(98700, 98799), 'The real Gateway must read and reference every configured Pie source.');
        foreach (range(98700, 98799) as $id) {
            assertGatewayGauge(in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Every Pie source must remain subscribed by ID: ' . $id);
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both output adapters must retain the complete hundred-source model without truncation.');
        }
        assertGatewayGauge(is_string($module->GetTestVariableValue('IPSViewPie')) && $module->GetTestVariableValue('IPSViewPie') !== '', 'The persistent Pie IPSView document must remain nonempty.');

        $selectedRows = $rows;
        foreach ($selectedRows as $index => &$row) {
            $row['InitiallyVisible'] = $index < 10;
        }
        unset($row);
        $module->SetTestProperty('Sources', json_encode($selectedRows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableSourceSelection', true);
        $module->ApplyChanges();
        $requests = [];
        $selection = $decode($module);
        assertGatewayGauge($selection['pie']['enableSourceSelection'] === true
            && array_column($selection['items'], 'initiallyVisible') === [...array_fill(0, 10, true), ...array_fill(0, 90, false)]
            && count($selection['items']) === 100 && $requests === range(98700, 98799), 'Initial visibility must be additive metadata, never a filter of Gateway reads or complete model values.');
        $GLOBALS['symconTestVariables'][98799]['Value'] = 101.0;
        $module->MessageSink(1792233297, 98799, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(count($updated['chart']['items']) === 100 && (float) $updated['chart']['items'][99]['value'] === 101.0
            && $updated['chart']['items'][99]['initiallyVisible'] === false, 'A hidden last-source update must publish fresh full data and retain its ID/default visibility.');
        $GLOBALS['symconTestVariables'][98799]['Value'] = 1.0;

        $legacy = $configure(array_slice($rows, 0, 16));
        $legacyProperties = $legacy->GetTestProperties();
        $legacy->ApplyChanges();
        $legacy->ApplyChanges();
        assertGatewayGauge($legacy->GetTestProperties() === $legacyProperties && count($decode($legacy)['items']) === 16, 'Existing sixteen-source JSON and all old defaults must remain byte-identical after repeated ApplyChanges.');
        $one = $configure(array_slice($rows, 0, 1));
        $one->ApplyChanges();
        assertGatewayGauge($one->GetTestStatus() === IS_ACTIVE && count($decode($one)['items']) === 1, 'The established single-source minimum must remain valid.');
        $GLOBALS['symconTestVariables'][98799]['Name'] = 'Renamed last source';
        $renamed = $decode($module);
        assertGatewayGauge(array_column($renamed['items'], 'id') === array_column($model['items'], 'id') && $renamed['items'][99]['name'] === 'Renamed last source', 'A rename must change the displayed name without changing identity or dropping same-name sources.');

        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'EnableSourceSelection')['onChange'] ?? '', 'UpdateBarPreviewFromForm')
            && in_array('InitiallyVisible', array_column(findGaugeFormElement($form['elements'], 'Sources')['columns'], 'name'), true), 'The selection toggle and initial visibility must be editable and participate in live draft previews.');
        $beforeDraft = $module->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Pie draft previews must not request Gateway data.');
        };
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableSourceSelection' => false, 'ShowPercent' => true, 'Title' => '<Unsaved>'], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Hundred-source Pie previews must remain valid SVG.');
            assertGatewayGauge(str_contains($svg, 'data-preview-sources="6/100"') && str_contains($svg, '&lt;Unsaved&gt;'), 'The bounded preview must disclose six of one hundred sources and escape unsaved labels.');
            $xpath = new DOMXPath($document);
            assertGatewayGauge($xpath->query('//*[local-name()="path" and @fill and @fill!="none"]')->length <= 6, 'The preview must draw at most six sectors rather than squeezing every source into the sample.');
            assertGatewayGauge($document->documentElement->getAttribute('data-pie-visible-count') === '100'
                && $document->documentElement->getAttribute('data-pie-source-count') === '100', 'The preview must retain full source and effective visibility counts.');
            $sectors = $xpath->query('//*[local-name()="path" and @data-pie-id]');
            assertGatewayGauge($sectors->length === 6, 'Six positive sampled sectors must expose their original IDs.');
            foreach ($sectors as $sector) {
                assertGatewayGauge(abs((float) $sector->getAttribute('data-pie-share') - 1.0) < 1e-9
                    && abs((float) $sector->getAttribute('data-pie-all-share') - 1.0) < 1e-9, 'Sampled percentages must use all hundred equal sources, never renormalize the six displayed sectors.');
            }
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $selectedRows, 'EnableSourceSelection' => true, 'ShowPercent' => true], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Selected Pie draft previews must remain valid SVG.');
            $xpath = new DOMXPath($document);
            assertGatewayGauge($document->documentElement->getAttribute('data-pie-visible-count') === '10'
                && str_contains($svg, 'data-preview-sources="6/100"'), 'The selected preview must distinguish all sources, the ten visible sources and its bounded six-sector sample.');
            foreach ($xpath->query('//*[local-name()="path" and @data-pie-id]') as $sector) {
                assertGatewayGauge(abs((float) $sector->getAttribute('data-pie-share') - 10.0) < 1e-9
                    && abs((float) $sector->getAttribute('data-pie-all-share') - 1.0) < 1e-9, 'Selected sample shares must use all ten selected weights and retain the independent complete-model one-percent share.');
            }
        }
        $lateRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $lateRows[99]['InitiallyVisible'] = true;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $lateRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'A late-only selected draft must remain valid SVG.');
            $xpath = new DOMXPath($document);
            $sectors = $xpath->query('//*[local-name()="path" and @data-pie-id]');
            assertGatewayGauge($document->documentElement->getAttribute('data-pie-visible-count') === '1'
                && $sectors->length === 1 && $sectors->item(0)->getAttribute('data-pie-id') === 'variable-98799'
                && (float) $sectors->item(0)->getAttribute('data-pie-share') === 100.0
                && (float) $sectors->item(0)->getAttribute('data-pie-all-share') === 1.0, 'Initial selection must be applied before preview sampling, including the hundredth source and its correct denominators.');
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $lateRows, 'EnableSourceSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET)
                && $document->documentElement->getAttribute('data-pie-visible-count') === '100', 'Disabled selection must ignore initial visibility without erasing saved row metadata.');
        }
        $noSelectionRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $noSelectionRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'An empty selection preview must remain valid SVG.');
            $xpath = new DOMXPath($document);
            assertGatewayGauge(
                $document->documentElement->getAttribute('data-pie-visible-count') === '0'
                && $document->documentElement->getAttribute('data-pie-source-count') === '100'
                && $xpath->query('//*[local-name()="path" and @data-pie-id]')->length === 0,
                'No selected sources must remain an explicit empty selection, not synthetic example sectors.'
            );
        }
        assertGatewayGauge($module->GetTestProperties() === $beforeDraft, 'Unsaved Pie selection, sources, title and styles must not persist.');
        IPSModuleStrict::$ParentResponder = $responder;

        $normalizedRows = $selectedRows;
        foreach ($normalizedRows as $index => &$row) {
            $row['Unit'] = $index % 2 === 0 ? '°C' : '%';
            $row['Minimum'] = $index % 2 === 0 ? -20 : 0;
            $row['Maximum'] = $index % 2 === 0 ? 0 : 100;
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = $index % 2 === 0 ? -10.0 : 50.0;
        }
        unset($row);
        $normalized = $configure($normalizedRows);
        $normalized->SetTestProperty('NormalizeValues', true);
        $normalized->SetTestProperty('EnableSourceSelection', true);
        $normalized->ApplyChanges();
        $normalizedModel = $decode($normalized);
        assertGatewayGauge($normalized->GetTestStatus() === IS_ACTIVE && $normalizedModel['pie']['normalized'] === true
            && array_column($normalizedModel['items'], 'value') === array_fill(0, 100, 50.0)
            && $normalizedModel['items'][0]['originalValue'] === -10.0 && $normalizedModel['items'][0]['unit'] === '°C'
            && $normalizedModel['items'][99]['originalValue'] === 50.0 && $normalizedModel['items'][99]['unit'] === '%'
            && $normalizedModel['items'][99]['decimals'] === 6, 'Normalization of a hundred mixed-unit sources must preserve every original value, unit, precision and source range.');
        foreach ([$normalized->GetVisualizationTile(), $normalized->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $normalizedModel, 'Both outputs must retain normalized original metadata even for initially hidden sources.');
        }
        foreach ($rows as $row) {
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = 1.0;
        }

        foreach ([$sources, [], [...array_slice($rows, 0, 99), $rows[0]],
            [...array_slice($rows, 0, 99), [...$rows[99], 'Unit' => '%']],
            [...array_slice($rows, 0, 99), [...$rows[99], 'InitiallyVisible' => 'false']],
            [...array_slice($rows, 0, 99), [...$rows[99], 'InitiallyVisible' => 0]]] as $invalidRows) {
            $invalid = $configure($invalidRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Empty/101 rows, duplicate IDs, mixed units and nonboolean initial visibility must fail validation.');
        }
        $GLOBALS['symconTestVariables'][98799]['VariableType'] = 0;
        $invalid = $configure($rows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A nonnumeric hundredth source must be rejected.');
        $GLOBALS['symconTestVariables'][98799]['VariableType'] = 2;
        $GLOBALS['symconTestVariables'][98799]['Value'] = -1.0;
        $negative = $startup($module->GetVisualizationTile());
        assertGatewayGauge($negative['status'] === 'error' && $negative['chart'] === null && $module->GetTestStatus() === 205, 'A hidden negative source must still invalidate the complete raw Pie, never disappear from validation.');
        $GLOBALS['symconTestVariables'][98799]['Value'] = 1.0;
        $module->RetryTransient();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Corrected current data must restore the complete Pie model.');
        foreach ([NAN, INF, -INF] as $invalidValue) {
            $GLOBALS['symconTestVariables'][98799]['Value'] = $invalidValue;
            $failure = null;
            try {
                $module->GetPieData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge($failure !== null && $module->GetTestStatus() === 204, 'A nonfinite hidden Kernel value must fail without returning a partial Pie model.');
        }
        foreach ($rows as $row) {
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = 0.0;
        }
        $zeroModel = $decode($module);
        assertGatewayGauge(count($zeroModel['items']) === 100 && array_column($zeroModel['items'], 'value') === array_fill(0, 100, 0.0), 'Real zero values must remain one hundred original sources, not equally sized synthetic weights.');
        foreach ($rows as $row) {
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = 1.0;
        }
        $GLOBALS['symconTestVariables'][98798]['Value'] = 1e308;
        $GLOBALS['symconTestVariables'][98799]['Value'] = 1e308;
        $largeFinite = $decode($module);
        assertGatewayGauge($largeFinite['items'][98]['value'] === 1e308 && $largeFinite['items'][99]['value'] === 1e308, 'Finite large raw sources must remain original even when a naive total would overflow.');
        $GLOBALS['symconTestVariables'][98798]['Value'] = 1.0;
        $GLOBALS['symconTestVariables'][98799]['Value'] = 1.0;

        $longRows = array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<>&測', 30), 'Decimals' => 6], $rows);
        $stress = $configure($longRows);
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('Title', str_repeat('<&測>', 30));
        $stress->ApplyChanges();
        $stressModel = $decode($stress);
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $stressModel, 'Long UTF-8 labels and high precision must survive both complete bounded outputs.');
        }
        assertGatewayGauge(strlen($stress->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The hundred-source form and both previews must fit the output buffer.');
        $huge = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('X', 3000)], $rows));
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetPieData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $huge->GetTestStatus() === 202 && str_contains(strtolower($failure->getMessage()), 'large')
            && $huge->GetTestProperties() === $hugeProperties, 'Oversized Pie metadata must fail explicitly without truncating sources or rewriting configuration.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'Oversized models must produce bounded actionable errors in both adapters.');
        }
        $escaped = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<', 1700)], $rows));
        $escapedProperties = $escaped->GetTestProperties();
        $escapedJSON = $escaped->GetPieData();
        assertGatewayGauge(strlen($escapedJSON) < 200000 && count(json_decode($escapedJSON, true, 512, JSON_THROW_ON_ERROR)['items']) === 100, 'The final HTML guard must be exercised from a complete model below its own JSON budget.');
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'HTML escaping beyond the final buffer must yield a bounded actionable error, not a partial Pie.');
        }
        assertGatewayGauge($escaped->GetTestStatus() === 202 && $escaped->GetTestProperties() === $escapedProperties, 'Final HTML size recovery must not modify persisted sources or design.');
        $module->SetTestProperty('Sources', json_encode(array_slice($rows, 0, 1), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestReferences() === [98700] && !in_array(['SenderID' => 98799, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Removing sources must unregister stale references and subscriptions.');
        echo 'Pie hundred-source bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR)) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
    }
})();
