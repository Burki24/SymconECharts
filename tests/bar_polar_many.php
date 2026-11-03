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
        $id = 98600 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296,
            'Value'                                            => 1.0, 'Name' => 'Same synthetic source'];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'UseVariablePresentation' => false,
            'Unit'                 => 'W', 'Decimals' => $index === 99 ? 6 : 1];
    }
    $configure = static function (array $rows): EChartsBarPolar
    {
        $module = new EChartsBarPolar();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsBarPolar $module): array => json_decode($module->GetPolarData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Hundred-source Polar documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Polar startup must remain accessible.');
        $configuration = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $compressed = $configuration['options']['compressedStateGzipBase64'] ?? null;
        return is_string($compressed) ? json_decode(gzdecode(base64_decode($compressed, true)), true, 512, JSON_THROW_ON_ERROR) : $configuration['state'];
    };
    $previewDocuments = static function (EChartsBarPolar $module): array
    {
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both Polar draft previews must remain available.');
        return array_map(static function (array $image): DOMDocument
        {
            $svg = (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $document = new DOMDocument();
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Hundred-source Polar previews must remain valid SVG.');
            return $document;
        }, array_slice($images, -2));
    };

    try {
        $rows = array_slice($sources, 0, 100);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'BarPolar must accept one hundred distinct numeric sources.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        assertGatewayGauge(
            $model['schemaVersion'] === 1 && $model['family'] === 'bar' && $model['variant'] === 'polar'
            && count($model['items']) === 100
            && array_column($model['items'], 'id') === array_map(static fn (int $id): string => 'variable-' . $id, range(98600, 98699))
            && array_column($model['items'], 'label') === array_fill(0, 100, 'Same synthetic source')
            && array_column($model['items'], 'value') === array_fill(0, 100, 1.0)
            && array_column($model['items'], 'initiallyVisible') === array_fill(0, 100, true)
            && $properties['EnableSourceSelection'] === false && $model['polar']['enableSourceSelection'] === false
            && $module->GetTestProperties() === $properties,
            'One hundred same-name sources must retain identity, raw values and configuration, with selection disabled by default.'
        );
        assertGatewayGauge($requests === range(98600, 98699) && $module->GetTestReferences() === range(98600, 98699), 'The real Gateway must read and reference all hundred Polar sources.');
        foreach (range(98600, 98699) as $id) {
            assertGatewayGauge(in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Every Polar source must be subscribed by ID: ' . $id);
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both adapters must carry every Polar source, not just the visible page.');
        }
        assertGatewayGauge(is_string($module->GetTestVariableValue('IPSViewBarPolar')) && $module->GetTestVariableValue('IPSViewBarPolar') !== '', 'The published IPSView Polar document must remain nonempty.');

        $selectedRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $selectedRows[0]['InitiallyVisible'] = true;
        $selectedRows[99]['InitiallyVisible'] = true;
        $module->SetTestProperty('Sources', json_encode($selectedRows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableSourceSelection', true);
        $module->ApplyChanges();
        $requests = [];
        $selection = $decode($module);
        assertGatewayGauge($selection['polar']['enableSourceSelection'] === true
            && array_column($selection['items'], 'initiallyVisible') === [true, ...array_fill(0, 98, false), true]
            && $requests === range(98600, 98699) && $module->GetTestReferences() === range(98600, 98699), 'Initial selection must be metadata only; hidden sources remain part of all Gateway reads and references.');
        $GLOBALS['symconTestVariables'][98698]['Value'] = -250.0;
        $GLOBALS['symconTestVariables'][98699]['Value'] = 500.0;
        $module->MessageSink(1792233297, 98698, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($updated['chart']['items']) === 100 && (float) $updated['chart']['items'][98]['value'] === -250.0
            && $updated['chart']['items'][98]['initiallyVisible'] === false
            && (float) $updated['chart']['items'][99]['value'] === 500.0,
            'A hidden late-source update must publish signed current values and the complete model, preserving its selection identity.'
        );
        $GLOBALS['symconTestVariables'][98698]['Value'] = 1.0;
        $GLOBALS['symconTestVariables'][98699]['Value'] = 1.0;

        $legacy = $configure(array_slice($rows, 0, 16));
        $legacyProperties = $legacy->GetTestProperties();
        $legacy->ApplyChanges();
        $legacy->ApplyChanges();
        assertGatewayGauge($legacy->GetTestProperties() === $legacyProperties && count($decode($legacy)['items']) === 16, 'Legacy sixteen-source JSON and design defaults must survive repeated ApplyChanges without rewriting.');
        $one = $configure(array_slice($rows, 0, 1));
        $one->ApplyChanges();
        assertGatewayGauge($one->GetTestStatus() === IS_ACTIVE && count($decode($one)['items']) === 1, 'The existing single-source minimum must remain valid.');
        $GLOBALS['symconTestVariables'][98699]['Name'] = 'Renamed last source';
        $renamed = $decode($module);
        assertGatewayGauge(array_column($renamed['items'], 'id') === array_column($model['items'], 'id')
            && $renamed['items'][99]['label'] === 'Renamed last source', 'Renaming must not change identity or merge sources with identical names.');
        $GLOBALS['symconTestVariables'][98699]['Name'] = 'Same synthetic source';
        $independent = $configure($selectedRows);
        $independent->SetTestProperty('EnableSourceSelection', true);
        $independent->SetTestProperty('IPSViewUseTileDesign', false);
        $independent->SetTestProperty('IPSViewPolarMode', 'tangential');
        $independent->SetTestProperty('IPSViewEChartsTheme', 'dark');
        $independent->SetTestProperty('IPSViewValueAxisRangeMode', 'manual');
        $independent->SetTestProperty('IPSViewValueAxisMinimum', -50.0);
        $independent->SetTestProperty('IPSViewValueAxisMaximum', 200.0);
        $independent->ApplyChanges();
        $tile = $startup($independent->GetVisualizationTile())['chart'];
        $ipsView = $startup($independent->GetIPSViewHTML())['chart'];
        assertGatewayGauge(
            $tile['items'] === $ipsView['items'] && count($ipsView['items']) === 100
            && $tile['polar']['style']['mode'] === 'radial' && $ipsView['polar']['style']['mode'] === 'tangential'
            && $ipsView['theme'] === 'dark' && $ipsView['polar']['style']['valueAxisMinimum'] === -50.0
            && $ipsView['polar']['style']['valueAxisMaximum'] === 200.0
            && $ipsView['polar']['enableSourceSelection'] === true,
            'Independent IPSView style must share all sources and initial selection while preserving its own complete manual scale.'
        );

        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'EnableSourceSelection')['onChange'] ?? '', 'UpdateBarPreviewFromForm')
            && in_array('InitiallyVisible', array_column(findGaugeFormElement($form['elements'], 'Sources')['columns'], 'name'), true), 'Selection and initial visibility must be editable and refresh the draft preview.');
        $beforeDraft = $module->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Polar draft previews must not request Gateway data.');
        };
        $GLOBALS['symconTestVariables'][98698]['Value'] = -100.0;
        $GLOBALS['symconTestVariables'][98699]['Value'] = 1000.0;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableSourceSelection' => false, 'Title' => '<Unsaved>'], JSON_THROW_ON_ERROR));
        foreach ($previewDocuments($module) as $document) {
            $xpath = new DOMXPath($document);
            $paths = $xpath->query('//*[local-name()="path" and @data-polar-id]');
            assertGatewayGauge($document->documentElement->getAttribute('data-polar-source-count') === '100'
                && $document->documentElement->getAttribute('data-polar-visible-count') === '100'
                && (float) $document->documentElement->getAttribute('data-polar-min') === -100.0
                && (float) $document->documentElement->getAttribute('data-polar-max') === 1000.0
                && $paths->length === 6 && str_contains($document->saveXML(), 'data-preview-sources="6/100"')
                && str_contains($document->saveXML(), '&lt;Unsaved&gt;'), 'The bounded preview must disclose its six-source sample and retain complete offscreen extrema.');
            foreach ($paths as $path) {
                assertGatewayGauge(abs((float) $path->getAttribute('data-polar-fraction') - 101 / 1100) < 1e-5, 'Sample geometry must use the full scale, not recompute extrema from the first six items.');
            }
        }
        $lateRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $lateRows[99]['InitiallyVisible'] = true;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $lateRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previewDocuments($module) as $document) {
            $xpath = new DOMXPath($document);
            $paths = $xpath->query('//*[local-name()="path" and @data-polar-id]');
            assertGatewayGauge(
                $document->documentElement->getAttribute('data-polar-visible-count') === '1'
                && $paths->length === 1 && $paths->item(0)->getAttribute('data-polar-id') === 'variable-98699'
                && (float) $paths->item(0)->getAttribute('data-polar-fraction') === 1.0
                && (float) $document->documentElement->getAttribute('data-polar-min') === -100.0
                && (float) $document->documentElement->getAttribute('data-polar-max') === 1000.0,
                'Initial selection must precede sampling, even for only the hundredth source, without losing hidden scale extrema.'
            );
            $palette = SymconECharts\EChartsAsset::ThemePreviewPalette('auto');
            assertGatewayGauge($paths->item(0)->getAttribute('fill') === $palette['seriesColors'][99 % count($palette['seriesColors'])], 'A late-only selected source must retain its original full-model palette position.');
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableSourceSelection' => false,
            'PolarMode'                                          => 'radial', 'IPSViewUseTileDesign' => false, 'IPSViewPolarMode' => 'tangential',
            'IPSViewValueAxisRangeMode'                          => 'manual', 'IPSViewValueAxisMinimum' => -50, 'IPSViewValueAxisMaximum' => 200], JSON_THROW_ON_ERROR));
        [$tilePreview, $ipsViewPreview] = $previewDocuments($module);
        assertGatewayGauge(
            (float) $tilePreview->documentElement->getAttribute('data-polar-min') === -100.0
            && (float) $tilePreview->documentElement->getAttribute('data-polar-max') === 1000.0
            && (float) $ipsViewPreview->documentElement->getAttribute('data-polar-min') === -50.0
            && (float) $ipsViewPreview->documentElement->getAttribute('data-polar-max') === 200.0,
            'Radial automatic and independent tangential manual previews must preserve their complete distinct scale contracts.'
        );
        foreach ((new DOMXPath($ipsViewPreview))->query('//*[local-name()="path" and @data-polar-id]') as $path) {
            assertGatewayGauge(abs((float) $path->getAttribute('data-polar-fraction') - 51 / 250) < 1e-5, 'Independent tangential draft geometry must respect its manual scale.');
        }
        $noneRows = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => true], JSON_THROW_ON_ERROR));
        foreach ($previewDocuments($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-polar-visible-count') === '0'
                && (new DOMXPath($document))->query('//*[local-name()="path" and @data-polar-id]')->length === 0, 'An empty selection must remain empty rather than display synthetic example bars.');
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $noneRows, 'EnableSourceSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previewDocuments($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-polar-visible-count') === '100', 'Disabled selection must ignore initial visibility without erasing row metadata.');
        }
        assertGatewayGauge($module->GetTestProperties() === $beforeDraft, 'Unsaved selection, title, style and source drafts must not alter persisted properties.');
        IPSModuleStrict::$ParentResponder = $responder;
        $GLOBALS['symconTestVariables'][98698]['Value'] = 1.0;
        $GLOBALS['symconTestVariables'][98699]['Value'] = 1.0;

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
        assertGatewayGauge($normalized->GetTestStatus() === IS_ACTIVE && $normalizedModel['polar']['normalized'] === true
            && array_column($normalizedModel['items'], 'value') === array_fill(0, 100, 50.0)
            && $normalizedModel['items'][0]['originalValue'] === -10.0 && $normalizedModel['items'][0]['unit'] === '°C'
            && $normalizedModel['items'][99]['originalValue'] === 50.0 && $normalizedModel['items'][99]['unit'] === '%'
            && $normalizedModel['items'][99]['decimals'] === 6, 'Hundred-source normalization must preserve all original values, units, ranges and precision.');
        foreach ([$normalized->GetVisualizationTile(), $normalized->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $normalizedModel, 'Both outputs must retain normalized originals for every source, including those initially hidden.');
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
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Empty/101 rows, duplicate IDs, mixed units and nonboolean visibility must fail validation.');
        }
        $GLOBALS['symconTestVariables'][98699]['VariableType'] = 0;
        $invalid = $configure($rows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A nonnumeric hundredth source must be rejected.');
        $GLOBALS['symconTestVariables'][98699]['VariableType'] = 2;
        foreach ([NAN, INF, -INF] as $invalidValue) {
            $GLOBALS['symconTestVariables'][98698]['Value'] = $invalidValue;
            assertGatewayGauge(
                strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'A nonfinite source must not prevent editing the configuration form.'
            );
            $failure = null;
            try {
                $module->GetPolarData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge($failure !== null && $module->GetTestStatus() === 204, 'A nonfinite hidden Kernel value must fail without returning a partial Polar model.');
        }
        $GLOBALS['symconTestVariables'][98698]['Value'] = -1.0;
        $recovered = $decode($module);
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE && $recovered['items'][98]['value'] === -1.0, 'Corrected signed data must restore the complete raw Polar model.');
        foreach ($rows as $row) {
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = 0.0;
        }
        assertGatewayGauge(array_column($decode($module)['items'], 'value') === array_fill(0, 100, 0.0), 'One hundred real zeros must remain real zero sources.');
        foreach ($rows as $row) {
            $GLOBALS['symconTestVariables'][$row['VariableID']]['Value'] = 1.0;
        }

        $stress = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<>&測', 30), 'Decimals' => 6], $rows));
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('Title', str_repeat('<&測>', 30));
        $stress->ApplyChanges();
        $stressModel = $decode($stress);
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $stressModel, 'Long UTF-8 labels and high precision must survive both complete bounded documents.');
        }
        assertGatewayGauge(strlen($stress->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The hundred-source form and both previews must fit the output buffer.');
        $huge = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('X', 3000)], $rows));
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetPolarData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $huge->GetTestStatus() === 202 && str_contains(strtolower($failure->getMessage()), 'large')
            && $huge->GetTestProperties() === $hugeProperties, 'Oversized Polar metadata must fail explicitly without truncating sources or modifying configuration.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'Oversized models must produce bounded actionable error documents in both adapters.');
        }
        $escaped = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<', 1700)], $rows));
        $escapedProperties = $escaped->GetTestProperties();
        $escapedJSON = $escaped->GetPolarData();
        assertGatewayGauge(strlen($escapedJSON) < 200000 && count(json_decode($escapedJSON, true, 512, JSON_THROW_ON_ERROR)['items']) === 100, 'The final HTML guard must be exercised from a complete model below the JSON size limit.');
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'), 'HTML escaping beyond the final buffer must yield a bounded actionable error, never a partial Polar model.');
        }
        assertGatewayGauge($escaped->GetTestStatus() === 202 && $escaped->GetTestProperties() === $escapedProperties, 'Final HTML size recovery must not modify persisted sources or design.');
        $module->SetTestProperty('Sources', json_encode(array_slice($rows, 0, 1), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestReferences() === [98600] && !in_array(['SenderID' => 98699, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Removing sources must unregister stale references and subscriptions.');
        echo 'Polar hundred-source bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR)) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
    }
})();
