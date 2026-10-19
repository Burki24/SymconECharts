<?php

declare(strict_types=1);

// Included by gateway_gauges.php; source IDs, names and archives are synthetic.
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
        if (($request['Operation'] ?? '') === 'archive.read') {
            $requests[] = $request;
        }
        return $gateway->ForwardData($json);
    };
    $sources = [];
    for ($index = 0; $index < 33; ++$index) {
        $id = 97000 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => $now,
            'Value'                                            => (float) $index, 'Name' => 'Same synthetic sensor'];
        $logged = [];
        $aggregated = [];
        for ($point = 0; $point < 1001; ++$point) {
            // Different raw timestamps are deliberately not a common sample grid.
            $timestamp = $now - 60 - $point * 60 - ($index % 2) * 11;
            $logged[] = ['TimeStamp' => $timestamp, 'Value' => $index + $point / 10, 'Duration' => 60];
            $aggregated[] = ['TimeStamp' => $now - 60 - $point * 60,
                'Avg'                    => $index + $point / 10, 'Min' => $index + $point / 10 - 1,
                'Max'                    => $index + $point / 10 + 1, 'Duration' => 60];
        }
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index === 31 ? 1 : 0,
            'LoggedValues'                                               => $logged, 'AggregatedValues' => $aggregated];
        $sources[] = ['VariableID'    => $id, 'Label' => 'Same synthetic sensor',
            'UseVariablePresentation' => false, 'Unit' => '%', 'Decimals' => 1,
            'Color'                   => -1, 'Reducer' => 'auto'];
    }
    $configure = static function (array $rows, string $mode = 'raw', int $budget = 32000) use ($now): TestableEChartsBarHistory
    {
        $module = new TestableEChartsBarHistory();
        $module->Create();
        $module->SetTestCurrentTimestamp($now);
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('PointBudget', $budget);
        $module->SetTestProperty('DataMode', $mode);
        $module->SetTestProperty('Range', '24h');
        return $module;
    };
    $decode = static fn (TestableEChartsBarHistory $module): array => json_decode(
        $module->GetBarHistoryData(),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Historical Bar documents must fit 1 MiB.');
        assertGatewayGauge(
            preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1,
            'The Historical Bar startup bootstrap must remain accessible.'
        );
        $bootstrap = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $bootstrap['state'] === null && is_string($bootstrap['options']['compressedStateGzipBase64'] ?? null),
            'Large Historical Bar model states must use the shared compressed startup transport.'
        );
        if (is_string($bootstrap['options']['compressedStateGzipBase64'] ?? null)) {
            $state = gzdecode(base64_decode($bootstrap['options']['compressedStateGzipBase64'], true));
            assertGatewayGauge(is_string($state), 'Compressed Historical Bar startup must decode losslessly.');
            return json_decode($state, true, 512, JSON_THROW_ON_ERROR);
        }
        return $bootstrap['state'];
    };

    try {
        $defaults = new TestableEChartsBarHistory();
        $defaults->Create();
        assertGatewayGauge(
            $defaults->GetTestProperties()['PointBudget'] === 1000,
            'The established Historical Bar point-budget default must remain 1000.'
        );
        $old = $configure(array_slice($sources, 0, 16), 'raw', 1000);
        $oldProperties = $old->GetTestProperties();
        $old->ApplyChanges();
        $old->ApplyChanges();
        $oldModel = $decode($old);
        assertGatewayGauge(
            $old->GetTestProperties() === $oldProperties && count($oldModel['series']) === 16
            && count(array_filter(array_column($oldModel['series'], 'initiallyVisible'))) === 16,
            'Old sixteen-source configurations must remain byte-identical and initially show every row.'
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
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Historical Bar must accept 32 sources and 32000 points.');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestProperties() === $properties, 'Repeated Apply must not persist normalized sources or selection.');
        $modelJSON = $module->GetBarHistoryData();
        $model = json_decode($modelJSON, true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($model['series']) === 32 && count($model['axes']) === 1
            && count(array_unique(array_column($model['series'], 'id'))) === 32
            && array_column($model['series'], 'label') === array_fill(0, 32, 'Same synthetic sensor')
            && count(array_filter(array_column($model['series'], 'initiallyVisible'))) === 4,
            'Equal display names must retain 32 distinct source IDs and independent initial visibility.'
        );
        assertGatewayGauge(
            $model['range']['pointBudget'] === 32000 && $model['range']['pointLimitPerSeries'] === 1000
            && array_sum(array_map(static fn (array $series): int => count($series['points']), $model['series'])) === 32000
            && $model['truncated'] === true && strlen($modelJSON) < 900000,
            'All configured sources, including hidden ones, must share the complete 32000-point budget.'
        );
        assertGatewayGauge(
            array_column($model['series'][0]['points'], 0) !== array_column($model['series'][1]['points'], 0)
            && $module->GetTestReferences() === range(97000, 97031),
            'Independent raw timestamps and references for hidden sources must remain intact.'
        );
        $documentBytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $documentBytes[] = strlen($html);
            $restored = $startup($html)['chart'];
            assertGatewayGauge(
                $restored['series'] === $model['series'] && $restored['range'] === $model['range'],
                'Both output adapters must retain every hidden source and all 32000 points exactly.'
            );
        }
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'PointBudget')['maximum'] === 32000,
            'The Historical Bar form must expose the expanded total budget.'
        );
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => array_reverse($rows),
            'Title'                                              => 'Unsaved synthetic draft', 'IPSViewUseTileDesign' => false], JSON_THROW_ON_ERROR));
        assertGatewayGauge($module->GetTestProperties() === $properties, 'Preview drafts must not persist sources or designs.');
        $GLOBALS['symconTestVariables'][97031]['Name'] = 'Renamed synthetic sensor';
        $renamed = $decode($module);
        assertGatewayGauge(
            array_column($renamed['series'], 'id') === array_column($model['series'], 'id')
            && array_column($renamed['series'], 'label') === array_column($model['series'], 'label'),
            'Variable renaming must not replace source identities or configured labels.'
        );

        $hiddenRows = array_map(static fn (array $row): array => array_merge($row, ['InitiallyVisible' => false]), $rows);
        $allHidden = $configure($hiddenRows);
        $allHidden->ApplyChanges();
        $hiddenModel = $decode($allHidden);
        assertGatewayGauge(
            count($hiddenModel['series']) === 32 && array_filter(array_column($hiddenModel['series'], 'initiallyVisible')) === []
            && array_sum(array_map(static fn (array $series): int => count($series['points']), $hiddenModel['series'])) === 32000,
            'A fully hidden initial selection must retain all archive data and the same budget allocation.'
        );
        $allHidden->UpdateBarPreviewFromForm('{}');
        $hiddenImages = array_values(array_filter($allHidden->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($hiddenImages) >= 2, 'Both previews must remain available when all sources are hidden.');
        foreach (array_slice($hiddenImages, -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(is_string($svg) && str_contains($svg, 'data-preview-sources="0/32"'), 'Fully hidden previews must disclose 0/32 sources.');
        }

        $minimum = $configure($rows, 'raw', 200);
        $minimum->ApplyChanges();
        assertGatewayGauge($decode($minimum)['range']['pointLimitPerSeries'] === 6, 'The minimum budget must divide over all 32 sources.');
        $cap = $configure(array_slice($rows, 0, 2));
        $cap->ApplyChanges();
        assertGatewayGauge($decode($cap)['range']['pointLimitPerSeries'] === 2000, 'The per-source archive cap must remain 2000.');
        foreach (['auto', 'minute', 'five-minutes', 'fifteen-minutes', 'hour', 'day'] as $mode) {
            $aggregate = $configure($rows, $mode);
            $aggregate->ApplyChanges();
            $data = $decode($aggregate);
            assertGatewayGauge(
                $data['range']['aggregationLevel'] !== null && $data['range']['pointLimitPerSeries'] === 1000
                && $data['series'][0]['effectiveReducer'] === 'average' && $data['series'][31]['effectiveReducer'] === 'sum'
                && $data['range']['endTimestamp'] < $now,
                'Aggregate modes must preserve completed intervals and automatic standard/counter reducers.'
            );
        }
        $reducedRows = $rows;
        $reducedRows[0]['Reducer'] = 'minimum';
        $reducedRows[1]['Reducer'] = 'maximum';
        $reducers = $configure($reducedRows, 'minute');
        $reducers->ApplyChanges();
        $reduced = $decode($reducers);
        assertGatewayGauge(
            $reduced['series'][0]['effectiveReducer'] === 'minimum' && $reduced['series'][1]['effectiveReducer'] === 'maximum'
            && $reduced['series'][31]['effectiveReducer'] === 'sum',
            'Explicit minimum/maximum and automatic counter sums must remain distinct with 32 sources.'
        );
        assertGatewayGauge($requests !== [], 'The expanded fixture must exercise the real Gateway archive path.');
        foreach ($requests as $request) {
            assertGatewayGauge($request['Payload']['Limit'] <= 2000, 'Expanded total budgets must not raise Gateway request limits.');
        }
        $tooMany = $configure($sources);
        $tooMany->ApplyChanges();
        assertGatewayGauge($tooMany->GetTestStatus() === 201, 'A 33rd source must remain an explicit source error.');
        $duplicates = $rows;
        $duplicates[31]['VariableID'] = $duplicates[0]['VariableID'];
        $duplicateModule = $configure($duplicates);
        $duplicateModule->ApplyChanges();
        assertGatewayGauge($duplicateModule->GetTestStatus() === 201, 'The same variable ID must not be accepted twice.');
        foreach ([199, 32001] as $invalidBudget) {
            $invalid = $configure($rows, 'raw', $invalidBudget);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 202, 'Budgets outside 200–32000 must remain configuration errors.');
        }
        $badVisible = $rows;
        $badVisible[0]['InitiallyVisible'] = 'false';
        $invalid = $configure($badVisible);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() !== IS_ACTIVE, 'InitiallyVisible must remain a strict optional boolean.');
        $units = $rows;
        foreach ($units as $index => &$row) {
            $row['Unit'] = 'unit-' . ($index % 16);
        }
        unset($row);
        $sixteenAxes = $configure($units);
        $sixteenAxes->ApplyChanges();
        assertGatewayGauge(count($decode($sixteenAxes)['axes']) === 16, 'All sixteen legacy effective unit groups must remain accepted.');
        $lateVisibleUnits = $units;
        foreach ($lateVisibleUnits as $index => &$row) {
            $row['InitiallyVisible'] = in_array($index, [14, 15], true);
        }
        unset($row);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $lateVisibleUnits,
            'IPSViewUseTileDesign'                               => false], JSON_THROW_ON_ERROR));
        $lateImages = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($lateImages) >= 2, 'Both previews must support late visible unit groups.');
        foreach (array_slice($lateImages, -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge(is_string($svg) && $xml->loadXML($svg), 'Late-unit previews must remain valid SVG.');
            $xpath = new DOMXPath($xml);
            $axes = $xpath->query('//*[@data-preview-axis]');
            $captions = $xpath->query('//*[@data-preview-axis-unit]');
            assertGatewayGauge(
                $axes->length === 2 && $captions->length === 2 && str_contains($svg, 'data-preview-sources="2/32"')
                && str_contains($svg, 'unit-14') && str_contains($svg, 'unit-15'),
                'Both previews must show only the two visible late unit axes and captions.'
            );
            foreach ($axes as $axis) {
                $x1 = (float) $axis->getAttribute('x1');
                $x2 = (float) $axis->getAttribute('x2');
                assertGatewayGauge(
                    $x1 >= 0.0 && $x1 <= 720.0 && $x2 >= 0.0 && $x2 <= 720.0,
                    'Visible late preview axes must stay inside the 720-pixel SVG; got ' . $x1 . '/' . $x2 . '.'
                );
            }
            foreach ($captions as $caption) {
                $x = (float) $caption->getAttribute('x');
                assertGatewayGauge(
                    $x >= 0.0 && $x <= 720.0,
                    'Visible late preview captions must stay inside the 720-pixel SVG; got ' . $x . '.'
                );
            }
        }
        assertGatewayGauge($module->GetTestProperties() === $properties, 'Late-unit preview compaction must not persist draft source changes.');
        $units[31]['Unit'] = 'unit-17';
        $seventeenAxes = $configure($units);
        $seventeenAxes->ApplyChanges();
        assertGatewayGauge($seventeenAxes->GetTestStatus() !== IS_ACTIVE, 'More rows must not silently create a seventeenth unit axis.');

        // Different samples and a missing interval remain absent, not zero-filled or interpolated.
        $gapID = 97032;
        $GLOBALS['symconTestArchiveVariables'][$gapID]['LoggedValues'] = [
            ['TimeStamp' => $now - 180, 'Value' => 7.5, 'Duration' => 60],
            ['TimeStamp' => $now - 600, 'Value' => 4.5, 'Duration' => 60]
        ];
        $gap = $configure([$sources[32]]);
        $gap->ApplyChanges();
        assertGatewayGauge(
            $decode($gap)['series'][0]['points'] === [[$now - 600, 4.5], [$now - 180, 7.5]],
            'Raw archive lacunae must not be filled, aligned, interpolated or replaced by current values.'
        );

        $svg = '<svg viewBox="0 0 100 100">';
        for ($path = 0; $path < 4; ++$path) {
            $svg .= '<path d="M0 0 ' . str_repeat('l1 1 ', 13080) . 'Z" fill="#AABBCC"/>';
        }
        $svg .= '</svg>';
        assertGatewayGauge(strlen($svg) > 260000 && strlen($svg) < 262144, 'SVG stress input must approach its import-size boundary.');
        $stressRows = $rows;
        foreach ($stressRows as &$row) {
            $row['Label'] = str_repeat('Synthetic <&> long sensor ', 16);
        }
        unset($row);
        $stress = $configure($stressRows, 'raw', 200);
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('BarFillMode', 'svg');
        $stress->SetTestProperty('BarSVG', $svg);
        $stress->ApplyChanges();
        assertGatewayGauge($stress->GetTestStatus() === IS_ACTIVE, 'A near-limit valid SVG design must remain accepted.');
        $svgDocumentBytes = [];
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            $svgDocumentBytes[] = strlen($html);
            $chart = $startup($html)['chart'];
            assertGatewayGauge(
                count($chart['series']) === 32 && strlen($chart['bar']['style']['barPatternImage']) > 340000,
                'Startup compression must retain valid large SVG patterns, labels and every source.'
            );
        }
        $stressForm = $stress->GetConfigurationForm();
        assertGatewayGauge(strlen($stressForm) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both form previews must remain within 1 MiB with a large SVG design.');

        // Exercise the final HTML guard through its stable renderer seam.
        $outputGuard = new class() extends EChartsBarHistory {
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
        $outputGuard->SetTestProperty('DataMode', 'raw');
        $guardProperties = $outputGuard->GetTestProperties();
        foreach ([$outputGuard->GetVisualizationTile(), $outputGuard->GetIPSViewHTML()] as $guardHTML) {
            $guardState = json_decode($guardHTML, true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                strlen($guardHTML) < SYMCON_OUTPUT_BUFFER_LIMIT && $guardState['state']['status'] === 'error'
                && $guardState['state']['chart'] === null && str_contains($guardState['state']['error'], 'Reduce the point budget')
                && $guardState['compressed'] === null,
                'Both HTML outputs must replace oversized documents with bounded explicit resource errors.'
            );
        }
        assertGatewayGauge(
            $outputGuard->renderCount === 4 && $outputGuard->GetTestStatus() === 202
            && $outputGuard->GetTestProperties() === $guardProperties,
            'The final HTML guard must rerender once per output without changing stored configuration.'
        );

        $huge = $configure($rows);
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($gateway): string
        {
            $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            $response = json_decode($gateway->ForwardData($json), true, 512, JSON_THROW_ON_ERROR);
            if (($request['Operation'] ?? '') === 'archive.read' && ($response['Success'] ?? false)) {
                foreach ($response['Payload']['Points'] as &$point) {
                    $point[1] = 1.2345678901234567E+123;
                }
                unset($point);
            }
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        };
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetBarHistoryData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge(
            $failure !== null && preg_match('/(budget|size|large|byte)/i', $failure->getMessage()) === 1
            && $huge->GetTestStatus() === 202,
            'Models beyond 900000 bytes must explicitly fail without silently rounding archive values.'
        );
        $diagnostic = $huge->GetBarHistoryDiagnostic();
        $error = json_decode($diagnostic, true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            strlen($diagnostic) < SYMCON_OUTPUT_BUFFER_LIMIT && preg_match('/(budget|size|large|byte)/i', $error['lastError'] ?? '') === 1,
            'Model-size failures must leave a bounded diagnostic with an actionable resource hint.'
        );
        echo 'BarHistory stress bytes: 32000-point model=' . strlen($modelJSON)
            . ', tile=' . $documentBytes[0] . ', IPSView=' . $documentBytes[1]
            . '; large-SVG tile=' . $svgDocumentBytes[0] . ', IPSView=' . $svgDocumentBytes[1]
            . ', form=' . strlen($stressForm) . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
    }
})();
