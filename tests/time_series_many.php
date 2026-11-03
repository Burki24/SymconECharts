<?php

declare(strict_types=1);

// Included by gateway_gauges.php; all source IDs and archives are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedArchives = $GLOBALS['symconTestArchiveVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $now = 1792404000;
    $requests = [];
    $gateway = new EChartsGateway();
    $gateway->Create();
    $gateway->ApplyChanges();
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($gateway, &$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (in_array($request['Operation'] ?? '', ['archive.read', 'current.read'], true)) {
            $requests[] = $request;
        }
        return $gateway->ForwardData($json);
    };
    $sources = [];
    for ($index = 0; $index < 33; ++$index) {
        $id = 96000 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => $now,
            'Value'                                            => (float) $index, 'Name' => 'Same synthetic sensor'];
        $logged = [];
        $aggregated = [];
        for ($point = 0; $point < 1001; ++$point) {
            $timestamp = $now - 60 - $point * 60;
            $logged[] = ['TimeStamp' => $timestamp, 'Value' => $index + $point / 10, 'Duration' => 60];
            $aggregated[] = ['TimeStamp' => $timestamp, 'Avg' => $index + $point / 10,
                'Min'                    => $index + $point / 10 - 1, 'Max' => $index + $point / 10 + 1, 'Duration' => 60];
        }
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index === 31 ? 1 : 0,
            'LoggedValues'                                               => $logged, 'AggregatedValues' => $aggregated];
        $sources[] = ['VariableID'    => $id, 'Label' => 'Same synthetic sensor',
            'UseVariablePresentation' => false, 'Unit' => '%', 'Decimals' => 1,
            'Color'                   => -1, 'Style' => 'line', 'Reducer' => 'auto'];
    }
    $configure = static function (array $rows, string $mode = 'raw', int $budget = 32000) use ($now): TestableEChartsTimeSeries
    {
        $module = new TestableEChartsTimeSeries();
        $module->Create();
        $module->SetTestCurrentTimestamp($now);
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('PointBudget', $budget);
        $module->SetTestProperty('DataMode', $mode);
        $module->SetTestProperty('Range', '24h');
        return $module;
    };
    $decode = static fn (TestableEChartsTimeSeries $module): array => json_decode(
        $module->GetTimeSeriesData(),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Time Series documents must fit 1 MiB.');
        assertGatewayGauge(
            preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1,
            'The Time Series startup bootstrap must remain accessible: ' . preg_last_error_msg()
                . ' / ' . strlen($html) . ' bytes / ' . substr($html, 0, 200)
                . ' / tail: ' . substr($html, -400)
        );
        $bootstrap = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        if (is_string($bootstrap['options']['compressedStateGzipBase64'] ?? null)) {
            $state = gzdecode(base64_decode($bootstrap['options']['compressedStateGzipBase64'], true));
            assertGatewayGauge(is_string($state), 'Compressed Time Series startup must decode losslessly.');
            return json_decode($state, true, 512, JSON_THROW_ON_ERROR);
        }
        return $bootstrap['state'];
    };

    try {
        $old = $configure(array_slice($sources, 0, 8), 'realtime', 2000);
        $defaults = (new TestableEChartsTimeSeries());
        $defaults->Create();
        assertGatewayGauge(
            $defaults->GetTestProperties()['PointBudget'] === 2000,
            'The established Time Series point-budget default must remain 2000.'
        );
        $oldProperties = $old->GetTestProperties();
        $old->ApplyChanges();
        $old->ApplyChanges();
        $oldModel = $decode($old);
        assertGatewayGauge(
            $old->GetTestProperties() === $oldProperties && count($oldModel['series']) === 8
            && count(array_filter(array_column($oldModel['series'], 'initiallyVisible'))) === 8,
            'Old eight-source configurations must remain byte-identical and initially show every row.'
        );

        $rows = array_slice($sources, 0, 32);
        foreach ($rows as $index => &$row) {
            $row['InitiallyVisible'] = $index < 4;
        }
        unset($row);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE,
            'Time Series must accept 32 sources and a 32000-point budget.'
        );
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestProperties() === $properties,
            'Applying larger Time Series configurations must not persist normalization or selection state.'
        );
        $modelJSON = $module->GetTimeSeriesData();
        $model = json_decode($modelJSON, true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($model['series']) === 32 && count($model['axes']) === 1
            && count(array_unique(array_column($model['series'], 'id'))) === 32
            && array_column($model['series'], 'label') === array_fill(0, 32, 'Same synthetic sensor')
            && count(array_filter(array_column($model['series'], 'initiallyVisible'))) === 4,
            'Series IDs must remain distinct even with identical labels and initially hidden rows.'
        );
        assertGatewayGauge(
            $model['range']['pointBudget'] === 32000
            && $model['range']['pointLimitPerSeries'] === 1000
            && array_sum(array_map(static fn (array $series): int => count($series['points']), $model['series'])) === 32000
            && $model['truncated'] === true && strlen($modelJSON) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'The full 32000-point budget must be shared by all configured rows, including hidden ones.'
        );
        assertGatewayGauge(
            $module->GetTestReferences() === range(96000, 96031),
            'Initially hidden rows must retain source references.'
        );
        foreach (range(96000, 96031) as $id) {
            assertGatewayGauge(
                in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true),
                'Initially hidden rows must retain VM_UPDATE subscriptions.'
            );
        }
        $documentBytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $documentBytes[] = strlen($html);
            $restored = $startup($html)['chart'];
            assertGatewayGauge(
                $restored['series'] === $model['series'] && $restored['range'] === $model['range'],
                'Both output adapters must retain every selected and hidden row and all 32000 points.'
            );
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'PointBudget')['maximum'] === 32000,
            'The Time Series form must expose the expanded total point budget.'
        );
        $module->UpdateTimeSeriesPreviewFromForm(json_encode(['Sources' => array_reverse($rows),
            'Title'                                                     => 'Unsaved synthetic draft', 'IPSViewUseTileDesign' => false], JSON_THROW_ON_ERROR));
        assertGatewayGauge(
            $module->GetTestProperties() === $properties,
            'Draft previews must not persist sources, selection or design changes.'
        );
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['TimeSeriesPreview', 'IPSViewTimeSeriesPreview'], true)
            && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both output previews must remain available for 32 rows.');

        $GLOBALS['symconTestVariables'][96031]['Name'] = 'Renamed synthetic sensor';
        $renamed = $decode($module);
        assertGatewayGauge(
            array_column($renamed['series'], 'id') === array_column($model['series'], 'id')
            && array_column($renamed['series'], 'label') === array_column($model['series'], 'label'),
            'Renaming an underlying variable must not replace its ID or configured display label.'
        );

        // Hidden raw/realtime sources still append without an archive reread.
        $queryCount = $GLOBALS['symconTestArchiveQueryCount'];
        $module->MessageSink($now + 1, 96031, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $append = json_decode((string) end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $append['messageType'] === 'append' && $append['variableID'] === 96031
            && $GLOBALS['symconTestArchiveQueryCount'] === $queryCount,
            'Hidden raw rows must receive ID-based incremental updates without rereading the archive.'
        );

        $hiddenRows = array_map(static fn (array $row): array => array_merge($row, ['InitiallyVisible' => false]), $rows);
        $allHidden = $configure($hiddenRows, 'realtime');
        $allHidden->ApplyChanges();
        $hiddenModel = $decode($allHidden);
        assertGatewayGauge(
            count($hiddenModel['series']) === 32
            && array_filter(array_column($hiddenModel['series'], 'initiallyVisible')) === []
            && $GLOBALS['symconTestArchiveQueryCount'] === $queryCount,
            'A fully hidden initial selection must retain 32 live sources without requiring an archive read.'
        );
        $allHidden->UpdateTimeSeriesPreviewFromForm('{}');
        $hiddenImages = array_filter($allHidden->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['TimeSeriesPreview', 'IPSViewTimeSeriesPreview'], true)
            && $update['Parameter'] === 'image');
        foreach ($hiddenImages as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge(
                is_string($svg) && $xml->loadXML($svg)
                && str_contains($svg, 'data-preview-sources="0/32"')
                && $xml->getElementsByTagName('path')->length === 0,
                'Both fully hidden previews must explicitly show 0/32 without invented example curves.'
            );
        }
        $lateVisible = $hiddenRows;
        $lateVisible[31]['InitiallyVisible'] = true;
        $lateVisible[31]['Color'] = 0x13579B;
        $allHidden->UpdateTimeSeriesPreviewFromForm(json_encode(['Sources' => $lateVisible, 'Annotations' => [
            ['VariableID' => 96031, 'Type' => 'line', 'Label' => 'Late visible reference', 'Value' => 40.0,
                'Maximum' => 0.0, 'Color' => -1, 'LineType' => 'solid', 'LineWidthPercent' => 100, 'OpacityPercent' => 20],
            ['VariableID' => 96000, 'Type' => 'line', 'Label' => 'Hidden reference', 'Value' => 20.0,
                'Maximum' => 0.0, 'Color' => -1, 'LineType' => 'solid', 'LineWidthPercent' => 100, 'OpacityPercent' => 20]
        ]], JSON_THROW_ON_ERROR));
        $lateImages = array_values(array_filter($allHidden->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['TimeSeriesPreview', 'IPSViewTimeSeriesPreview'], true)
            && $update['Parameter'] === 'image'));
        foreach (array_slice($lateImages, -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge(is_string($svg) && $xml->loadXML($svg), 'Selection previews must remain valid SVG.');
            $xpath = new DOMXPath($xml);
            $lines = $xpath->query('//*[@data-annotation-type="line"]');
            assertGatewayGauge(
                str_contains($svg, 'data-preview-sources="1/32"')
                && str_contains($svg, 'Late visible reference') && !str_contains($svg, 'Hidden reference')
                && $lines->length === 1 && $lines->item(0)->getAttribute('stroke') === '#13579B',
                'Late visible sources must keep their own reference line and color after preview index remapping.'
            );
        }

        $minimum = $configure($rows, 'realtime', 200);
        $minimum->ApplyChanges();
        assertGatewayGauge(
            $decode($minimum)['range']['pointLimitPerSeries'] === 6,
            'The existing minimum total budget must remain valid and divide deterministically over all 32 sources.'
        );

        $cap = $configure(array_slice($rows, 0, 2));
        $cap->ApplyChanges();
        assertGatewayGauge(
            $decode($cap)['range']['pointLimitPerSeries'] === 2000,
            'The per-series archive limit must remain 2000 even when the total budget is larger.'
        );
        foreach (['auto', 'minute', 'five-minutes', 'fifteen-minutes', 'hour', 'day'] as $mode) {
            $aggregate = $configure($rows, $mode);
            $aggregate->ApplyChanges();
            $data = $decode($aggregate);
            assertGatewayGauge(
                $data['range']['aggregationLevel'] !== null
                && $data['range']['acceptLiveUpdates'] === false
                && $data['range']['pointLimitPerSeries'] === 1000
                && $data['series'][0]['effectiveReducer'] === 'average'
                && $data['series'][31]['effectiveReducer'] === 'sum'
                && $data['range']['endTimestamp'] < $now,
                'All aggregate modes must retain completed-interval and standard/counter reducer semantics.'
            );
        }
        $reducedRows = $rows;
        $reducedRows[0]['Reducer'] = 'minimum';
        $reducedRows[1]['Reducer'] = 'maximum';
        $reducers = $configure($reducedRows, 'minute');
        $reducers->ApplyChanges();
        $reduced = $decode($reducers);
        assertGatewayGauge(
            $reduced['series'][0]['effectiveReducer'] === 'minimum'
            && $reduced['series'][1]['effectiveReducer'] === 'maximum'
            && $reduced['series'][31]['effectiveReducer'] === 'sum',
            'Explicit minimum/maximum reducers and automatic counter sums must remain distinct with 32 sources.'
        );
        $rawRequests = array_filter($requests, static fn (array $request): bool => ($request['Operation'] ?? '') === 'archive.read' && $request['Payload']['Mode'] === 'raw');
        assertGatewayGauge($rawRequests !== [], 'The expanded fixture must exercise real Gateway raw reads.');
        foreach ($requests as $request) {
            if (($request['Operation'] ?? '') === 'archive.read') {
                assertGatewayGauge(
                    $request['Payload']['Limit'] <= 2000,
                    'The expanded total budget must not raise Gateway per-request limits.'
                );
            }
        }

        $tooMany = $configure($sources, 'realtime');
        $tooMany->ApplyChanges();
        assertGatewayGauge($tooMany->GetTestStatus() === 201, 'A 33rd source must remain a clear source error.');
        $duplicates = $rows;
        $duplicates[31]['VariableID'] = $duplicates[0]['VariableID'];
        $duplicateModule = $configure($duplicates, 'realtime');
        $duplicateModule->ApplyChanges();
        assertGatewayGauge(
            $duplicateModule->GetTestStatus() === 201,
            'Larger source limits must not permit the same variable ID twice.'
        );
        $tooLarge = $configure($rows, 'realtime', 32001);
        $tooLarge->ApplyChanges();
        assertGatewayGauge($tooLarge->GetTestStatus() === 202, 'A 32001-point budget must remain a configuration error.');
        $badVisible = $rows;
        $badVisible[0]['InitiallyVisible'] = 'false';
        $invalid = $configure($badVisible, 'realtime');
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 202, 'InitiallyVisible must be a strict optional boolean.');
        $units = $rows;
        foreach ($units as $index => &$row) {
            $row['Unit'] = 'unit-' . ($index % 8);
        }
        unset($row);
        $eightAxes = $configure($units, 'realtime');
        $eightAxes->ApplyChanges();
        assertGatewayGauge(
            count($decode($eightAxes)['axes']) === 8,
            'The existing eight effective unit-group contract must still be accepted.'
        );
        $units[31]['Unit'] = 'unit-9';
        $nineAxes = $configure($units, 'realtime');
        $nineAxes->ApplyChanges();
        assertGatewayGauge(
            $nineAxes->GetTestStatus() === 202,
            'More rows must not silently create a ninth effective unit axis.'
        );

        // Long labels and a near-limit SVG pattern must not inflate the live documents.
        $svg = '<svg viewBox="0 0 100 100">';
        for ($path = 0; $path < 4; ++$path) {
            $svg .= '<path d="M0 0 ' . str_repeat('l1 1 ', 13080) . 'Z" fill="#AABBCC"/>';
        }
        $svg .= '</svg>';
        assertGatewayGauge(
            strlen($svg) > 260000 && strlen($svg) < 262144,
            'SVG stress input must reach the established import-size boundary.'
        );
        $stressRows = array_slice($rows, 0, 32);
        foreach ($stressRows as &$row) {
            $row['Label'] = str_repeat('Synthetic <&> long sensor ', 16);
        }
        unset($row);
        $stressRows[0] = array_merge($stressRows[0], ['Style' => 'area', 'UseIndividualDesign' => true,
            'SeriesAreaFillMode'                              => 'svg', 'SeriesAreaSVG' => $svg]);
        $stress = $configure($stressRows, 'realtime');
        $stress->ApplyChanges();
        assertGatewayGauge($stress->GetTestStatus() === IS_ACTIVE, 'Near-limit valid SVG designs must still be accepted.');
        $svgDocumentBytes = [];
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            $svgDocumentBytes[] = strlen($html);
            $chart = $startup($html)['chart'];
            assertGatewayGauge(
                count($chart['series']) === 32
                && strlen($chart['series'][0]['design']['areaPatternImage']) > 340000,
                'Startup compression must not omit valid large SVG designs or long labels.'
            );
        }
        $stressForm = $stress->GetConfigurationForm();
        assertGatewayGauge(
            strlen($stressForm) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'The configuration form with both bounded previews must fit 1 MiB even with a large valid SVG pattern; got '
                . strlen($stressForm) . ' bytes.'
        );
        $stressFormData = json_decode($stressForm, true, 512, JSON_THROW_ON_ERROR);
        foreach (['TimeSeriesPreview', 'IPSViewTimeSeriesPreview'] as $field) {
            $image = findGaugeFormElement($stressFormData['elements'], $field)['image'];
            $svg = base64_decode(substr($image, strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && str_contains($svg, 'data-preview-patterns-simplified="true"')
                && str_contains($svg, 'Large SVG patterns are simplified in the preview.'),
                'Both bounded SVG-pattern previews must visibly disclose their schematic simplification.'
            );
        }

        // The final HTML limit must also hold when a renderer expands an otherwise small model.
        $outputGuard = new class() extends EChartsTimeSeries {
            public int $renderCount = 0;

            protected function RenderVisualizationHTMLPage(bool $ipsView, array $configuration = []): string
            {
                ++$this->renderCount;
                if ($this->renderCount % 2 === 1) {
                    return str_repeat('x', SYMCON_OUTPUT_BUFFER_LIMIT + 1);
                }
                return json_encode(['state' => $configuration['state'],
                    'compressed'            => $configuration['options']['compressedStateGzipBase64']], JSON_THROW_ON_ERROR);
            }
        };
        $outputGuard->Create();
        $outputGuard->SetTestProperty('Sources', json_encode([$sources[0]], JSON_THROW_ON_ERROR));
        $outputGuard->SetTestProperty('DataMode', 'realtime');
        $guardProperties = $outputGuard->GetTestProperties();
        foreach ([$outputGuard->GetVisualizationTile(), $outputGuard->GetIPSViewHTML()] as $guardHTML) {
            $guardState = json_decode($guardHTML, true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                strlen($guardHTML) < SYMCON_OUTPUT_BUFFER_LIMIT
                && $guardState['state']['status'] === 'error'
                && $guardState['state']['chart'] === null
                && str_contains($guardState['state']['error'], 'Reduce the point budget')
                && $guardState['compressed'] === null,
                'Both HTML outputs must replace oversize documents with an explicit bounded resource error.'
            );
        }
        assertGatewayGauge(
            $outputGuard->renderCount === 4 && $outputGuard->GetTestStatus() === 202
            && $outputGuard->GetTestProperties() === $guardProperties,
            'The final HTML guard must rerender only once per output and leave stored configuration unchanged.'
        );

        // Byte safety is separate from point count: pathological valid numbers are never silently rounded.
        $huge = $configure($rows);
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($savedResponder): string
        {
            $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            $response = json_decode($savedResponder($json), true, 512, JSON_THROW_ON_ERROR);
            if (($request['Operation'] ?? '') === 'archive.read' && ($response['Success'] ?? false)) {
                foreach ($response['Payload']['Points'] as &$point) {
                    $point[1] = 1.2345678901234567E+123;
                }
                unset($point);
            }
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        };
        $huge->ApplyChanges();
        $diagnostic = $huge->GetTimeSeriesDiagnostic();
        $error = json_decode($diagnostic, true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            strlen($diagnostic) < SYMCON_OUTPUT_BUFFER_LIMIT
            && ($error['success'] ?? null) === false
            && preg_match('/(budget|size|large|byte)/i', $error['error'] ?? '') === 1,
            'Oversized models must produce a bounded explicit diagnostic, not truncated JSON or rounded archive values.'
        );
        echo 'TimeSeries stress bytes: 32000-point model=' . strlen($modelJSON)
            . ', tile=' . $documentBytes[0] . ', IPSView=' . $documentBytes[1]
            . '; large-SVG tile=' . $svgDocumentBytes[0] . ', IPSView=' . $svgDocumentBytes[1]
            . ', form=' . strlen($stressForm) . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
    }
})();
