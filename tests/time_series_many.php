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
        require_once __DIR__ . '/cartesian_period_selection.php';
        verifyCartesianPeriodSelection($configure([$sources[0]], 'raw', 200), $now, $requests);
        $temperature = array_replace($sources[0], ['Unit' => '°C', 'Label' => 'Temperatur']);
        $humidity = array_replace($sources[1], ['Unit' => '%', 'Label' => 'Feuchtigkeit']);
        $GLOBALS['symconTestVariables'][96999] = ['VariableType' => 3, 'VariableUpdated' => $now, 'Value' => '55 %', 'Name' => 'Feuchtigkeit als Text'];
        $output = $configure([$temperature], 'hour', 200);
        $output->SetTestLanguage('de');
        $output->SetTestProperty('EnableIPSView', true);
        foreach ([
            [[$temperature, $humidity], [], null],
            [[$temperature + ['StackGroup' => 1], $humidity + ['StackGroup' => 1]], [], 'Alle Quellen einer Stapelgruppe müssen dieselbe Einheit verwenden.'],
            [[$temperature, $temperature], [], 'Eine Variable ist mehrfach in der Quellenliste eingetragen.'],
            [[$temperature, false], [], 'Zeitreihenquelle 2 ist ungültig.'],
            [[$temperature, array_replace($humidity, ['VariableID' => 0])], [], 'Für Quelle 2 eine vorhandene Variable auswählen.'],
            [[$temperature, array_replace($humidity, ['VariableID' => 96999])], [], 'Für Quelle 2 eine Variable vom Typ Integer oder Float auswählen.'],
            [[], [], 'Zwischen 1 und 32 Zeitreihenquellen konfigurieren.'],
            [[$temperature, $humidity], [['VariableID' => $temperature['VariableID'], 'Value' => 25], ['VariableID' => $humidity['VariableID'], 'Type' => 'area', 'Value' => 40, 'Maximum' => 60]], null],
            [[$temperature], [['VariableID' => $temperature['VariableID'], 'Value' => 15], ['VariableID' => $humidity['VariableID'], 'Value' => 50]], 'Referenzlinien und Wertebereiche, Zeile 2: Eine Variable aus „Zeitreihenquellen“ auswählen oder diese Markierung löschen.'],
            [[$temperature], [], null]
        ] as [$rows, $annotations, $expectedError]) {
            $output->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
            $output->SetTestProperty('Annotations', json_encode($annotations, JSON_THROW_ON_ERROR));
            $before = $output->GetTestProperties();
            $output->ApplyChanges();
            assertGatewayGauge($output->GetTestProperties() === $before, 'Configuration errors and recovery must preserve the submitted properties.');
            foreach ([$output->GetVisualizationTile(), $output->GetTestVariableValue('IPSViewTimeSeries')] as $html) {
                $state = $startup($html);
                if ($expectedError === null) {
                    assertGatewayGauge(
                        $state['status'] === 'ready' && count($state['chart']['series']) === count($rows),
                        'Both outputs must show the configured independent series after applying valid settings.'
                    );
                    assertGatewayGauge(count($state['chart']['annotations']) === count($annotations), 'Markers must remain optional.');
                    if ($annotations !== []) {
                        [$line, $area] = $state['chart']['annotations'];
                        assertGatewayGauge(
                            $line['seriesIndex'] === 0 && $line['type'] === 'line' && $line['value'] == 25
                                && $area['seriesIndex'] === 1 && $area['type'] === 'area'
                                && $area['value'] == 40 && $area['maximum'] == 60,
                            'Line and range must use the fixed thresholds on the assigned temperature and humidity series.'
                        );
                    }
                } else {
                    assertGatewayGauge(
                        $state['status'] === 'error' && $state['chart'] === null && $state['error'] === $expectedError,
                        'Both outputs must show the specific German configuration error: ' . $expectedError
                    );
                }
            }
        }
        unset($output);

        $outdoor = array_replace($temperature, ['AxisPosition' => 'left', 'AxisRangeMode' => 'manual',
            'AxisMinimum'                                      => -20, 'AxisMaximum' => 40]);
        $hotWater = array_replace($sources[1], ['Unit' => '°C', 'OwnAxis' => true,
            'AxisPosition'                             => 'right', 'AxisRangeMode' => 'manual', 'AxisMinimum' => 30, 'AxisMaximum' => 70]);
        $independent = $configure([$outdoor, $hotWater], 'realtime', 200);
        $independent->ApplyChanges();
        $axisModel = $decode($independent);
        assertGatewayGauge(
            array_column($axisModel['series'], 'axisIndex') === [0, 1]
            && array_column($axisModel['axes'], 'position') === ['left', 'right']
            && array_column($axisModel['axes'], 'minimum') === [-20.0, 30.0]
            && array_column($axisModel['axes'], 'maximum') === [40.0, 70.0],
            'Two temperature sources must retain distinct sides, explicit ranges and series assignments.'
        );

        $spacingRows = [$outdoor, array_replace($humidity, ['AxisPosition' => 'left', 'AxisRangeMode' => 'manual',
            'AxisMinimum'                                                  => 0, 'AxisMaximum' => 100]),
            array_replace($hotWater, ['VariableID' => $sources[2]['VariableID']])];
        $spacing = $configure($spacingRows, 'realtime', 200);
        $spacing->SetTestProperty('EnableIPSView', true);
        $spacing->SetTestProperty('ValueAxisSpacing', 24);
        $spacing->SetTestProperty('IPSViewValueAxisSpacing', 48);
        $spacing->SetTestProperty('ZoomSliderSize', 30);
        $spacing->SetTestProperty('IPSViewZoomSliderSize', 45);
        foreach ([true, false] as $inherited) {
            $spacing->SetTestProperty('IPSViewUseTileDesign', $inherited);
            $spacing->ApplyChanges();
            $form = json_decode($spacing->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            foreach ([['', 24], ['IPSView', $inherited ? 24 : 48]] as [$prefix, $expected]) {
                $html = $prefix === '' ? $spacing->GetVisualizationTile() : $spacing->GetIPSViewHTML();
                $axisField = findGaugeFormElement($form['elements'], $prefix . 'ValueAxisSpacing');
                $preview = findGaugeFormElement($form['elements'], $prefix . 'TimeSeriesPreview')['image'];
                $svg = base64_decode(substr($preview, strlen('data:image/svg+xml;base64,')), true);
                $slider = findGaugeFormElement($form['elements'], $prefix . 'ZoomSliderSize');
                $size = $prefix === '' || $inherited ? 30 : 45;
                assertGatewayGauge(
                    $slider['minimum'] === 0 && $slider['maximum'] === 64
                    && str_contains($slider['onChange'], '$' . $prefix . 'ZoomSliderSize')
                    && $startup($html)['chart']['chart']['design']['zoomSliderSize'] === $size
                    && preg_match('/data-preview-zoom="horizontal"[^>]+height="' . $size . '"/', $svg) === 1,
                    'Both outputs and previews must use their effective slider thickness.'
                );
                assertGatewayGauge(
                    $startup($html)['chart']['chart']['design']['valueAxisSpacing'] === $expected
                    && $axisField['minimum'] === 0 && $axisField['maximum'] === 120
                    && str_contains($axisField['onChange'], '$' . $prefix . 'ValueAxisSpacing')
                    && preg_match_all('/data-preview-axis="left" x1="([\d.]+)"/', $svg, $matches) === 2
                    && (float) $matches[1][0] - (float) $matches[1][1] === (float) $expected
                    && preg_match_all('/data-preview-axis-range="left"[^>]* y="([\d.]+)"/', $svg, $labels) === 2
                    && (float) $labels[1][1] - (float) $labels[1][0] >= 11,
                    'Both outputs and form previews must respect inherited and independent axis spacing.'
                );
            }
        }
        $saved = $spacing->GetTestProperties();
        $spacing->UpdateTimeSeriesPreviewFromForm('{"ValueAxisSpacing":18,"IPSViewValueAxisSpacing":30,"ZoomSliderSize":18,"IPSViewZoomSliderSize":30}');
        foreach ([['TimeSeriesPreview', 18], ['IPSViewTimeSeriesPreview', 30]] as [$field, $expected]) {
            $updates = array_values(array_filter($spacing->GetTestFormUpdates(), static fn (array $row): bool => $row['Field'] === $field));
            $svg = base64_decode(substr(end($updates)['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                preg_match_all('/data-preview-axis="left" x1="([\d.]+)"/', $svg, $matches) === 2
                && (float) $matches[1][0] - (float) $matches[1][1] === (float) $expected
                && str_contains($svg, '<path d="M ' . $matches[1][0] . ' ')
                && preg_match('/data-preview-zoom="horizontal"[^>]+height="' . $expected . '"/', $svg) === 1
                && $spacing->GetTestProperties() === $saved,
                'Each unsaved spacing must move its preview axes and the plotted samples without persisting the draft.'
            );
        }
        copyDesignAndApplyForm($spacing);
        assertGatewayGauge(
            $spacing->GetTestProperties()['IPSViewValueAxisSpacing'] === 24
            && $spacing->GetTestProperties()['IPSViewZoomSliderSize'] === 30,
            'Copying the tile design must include axis spacing and slider thickness.'
        );

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
            && count($oldModel['axes']) === 1 && array_unique(array_column($oldModel['series'], 'axisIndex')) === [0]
            && count(array_filter(array_column($oldModel['series'], 'initiallyVisible'))) === 8,
            'Old eight-source configurations must remain byte-identical and initially show every row.'
        );

        $rows = array_slice($sources, 0, 32);
        $stackRows = [];
        foreach ([[6, 5, 2, 1], [6, 2, 1], [6, 5, 4, 3, 2, 1]] as $index => $minutes) {
            $id = 97001 + $index;
            $stackRows[] = array_merge($sources[$index], [
                'VariableID'       => $id,
                'Unit'             => 'kW',
                'StackGroup'       => $index < 2 ? 32 : 1,
                'InitiallyVisible' => $index !== 1
            ]);
            $GLOBALS['symconTestVariables'][$id] = $GLOBALS['symconTestVariables'][$sources[$index]['VariableID']];
            $samples = [];
            foreach (array_reverse($minutes) as $minute) {
                $samples[] = ['TimeStamp' => $now - 60 * $minute, 'Avg' => (float) ($minute - 1)];
            }
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => $samples];
        }
        $stacked = $configure($stackRows, 'minute', 300);
        $stacked->SetTestProperty('EnableIPSView', true);
        $stacked->ApplyChanges();
        $stackModel = $decode($stacked);
        assertGatewayGauge(
            array_column($stackModel['series'], 'stackGroup') === [32, 32, 1]
            && array_column($stackModel['series'], 'style') === ['area', 'area', 'area']
            && $stackModel['range']['stackGapTimestamps'] === [32 => [$now - 240]]
            && $stackModel['series'][0]['points'] === [[$now - 360, 5.0], [$now - 300, 4.0], [$now - 120, 1.0], [$now - 60, 0.0]]
            && $stackModel['series'][1]['initiallyVisible'] === false
            && $stackModel['range']['pointLimitPerSeries'] === 100
            && $stacked->GetTestProperties()['Sources'] === json_encode($stackRows, JSON_THROW_ON_ERROR),
            'Stacks preserve original archive points, zeros, hidden contributors, group bounds and configured styles; only a fully missing span gets a break marker.'
        );
        $mixedUnits = $stackRows;
        $mixedUnits[1]['Unit'] = 'W';
        foreach ([[$mixedUnits, 'auto'], [$stackRows, 'raw'], [$stackRows, 'realtime']] as [$invalidRows, $mode]) {
            $invalidStack = $configure($invalidRows, $mode);
            $invalidStack->ApplyChanges();
            assertGatewayGauge($invalidStack->GetTestStatus() === 202, 'Mixed units and unsynchronized raw or realtime stacks must be rejected.');
        }
        foreach ([-1, 33, '1'] as $invalidGroup) {
            $invalidRows = $stackRows;
            $invalidRows[0]['StackGroup'] = $invalidGroup;
            $invalidStack = $configure($invalidRows, 'auto');
            $invalidStack->ApplyChanges();
            assertGatewayGauge($invalidStack->GetTestStatus() === 202, 'Stack groups must be integers between 0 and 32.');
        }
        $berlin = new DateTimeZone('Europe/Berlin');
        $localDay = static fn (string $date): int => (new DateTimeImmutable($date, $berlin))->getTimestamp();
        foreach ([['2026-03-29', '2026-03-30', '2026-03-31', '2026-04-01'], ['2026-10-25', '2026-10-26', '2026-10-27', '2026-10-28']] as [$first, $second, $missing, $last]) {
            $dailyStack = [['stackGroup' => 1, 'points' => [[$localDay($first), 2], [$localDay($second), 0], [$localDay($last), 3]]]];
            assertGatewayGauge(
                \eCharts\EChartsArchiveStack::GapTimestamps($dailyStack, 1, 'Europe/Berlin') === [1 => [$localDay($missing)]],
                'Daily stack breaks must follow local calendar days across both DST boundaries.'
            );
        }
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
