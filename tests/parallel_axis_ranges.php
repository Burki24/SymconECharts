<?php

declare(strict_types=1);

// Public module/Gateway seams with synthetic Kernel archives; isolated by tests/run.php.
(static function (): void
{
    $previousParent = IPSModuleStrict::$ParentResponder;
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedArchives = $GLOBALS['symconTestArchiveVariables'];
    $zone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $requests = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use (&$requests): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if ($request['Operation'] === 'archive.read') {
            $requests[] = $request['Payload'];
        }
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        return $gateway->ForwardData($json);
    };
    try {
        $start = strtotime('2026-10-19 00:00:00');
        $sources = [];
        foreach (range(0, 15) as $index) {
            $id = 99600 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Same axis'];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => $start,
                'Value'                                            => 12345.0, 'Name' => 'Synthetic axis',
                'Presentation'                                     => ['SUFFIX' => $index % 2 ? ' %' : ' °C', 'DIGITS' => 2]];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
                'AggregatedValues'                                           => array_map(static fn (int $hour): array => ['TimeStamp' => $start + $hour * 3600,
                    'Avg'                                                                                                              => [-5.0, 10.0, 45.0][$hour] + $index, 'Min' => -99.0, 'Max' => 199.0], [2, 1, 0])];
        }
        $chart = new TestableEChartsParallel();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(strtotime('2026-10-20 12:34:00'));
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('Range', '7d');
        $chart->SetTestProperty('LineBudget', 1000);
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewLineColor', 0x123456);
        $chart->ApplyChanges();
        $read = static fn (): array => json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        $legacy = $read();
        assertGatewayGauge(!array_key_exists('minimum', $legacy['parallel']['axes'][0])
            && !array_key_exists('outOfRangeCount', $legacy['parallel']), 'Legacy automatic axes keep their exact additive-free model.');
        $sources[0] += ['AxisRangeMode' => 'manual', 'AxisMinimum' => -10.0, 'AxisMaximum' => 40.0];
        $sources[15] += ['AxisRangeMode' => 'manual', 'AxisMinimum' => 0.0, 'AxisMaximum' => 50.0];
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $properties = $chart->GetTestProperties();
        $requests = [];
        $fixed = $read();
        assertGatewayGauge(
            ($fixed['parallel']['axes'][0]['minimum'] ?? null) === -10.0
            && ($fixed['parallel']['axes'][0]['maximum'] ?? null) === 40.0,
            'A manual Parallel axis must expose the configured limits, not silently remain automatic.'
        );
        assertGatewayGauge($fixed['parallel']['axes'][15]['maximum'] === 50.0
            && !array_key_exists('minimum', $fixed['parallel']['axes'][2]), 'Same-unit axes have independent fixed/automatic ranges.');
        assertGatewayGauge($fixed['parallel']['rows'] === $legacy['parallel']['rows']
            && $fixed['parallel']['outOfRangeCount'] === 2, 'Out-of-range originals and complete lines survive; only strict exceedances count.');
        assertGatewayGauge(count($requests) === 16 && array_column($requests, 'Limit') === array_fill(0, 16, 625)
            && $fixed['range'] === $legacy['range'], 'Axis ranges must not change archive queries, aggregation, intervals or the 10000-aggregate budget.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewParallel')] as $index => $html) {
            preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $match);
            $state = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
            assertGatewayGauge(
                $state['status'] === 'ready' && $state['chart']['parallel']['axes'] === $fixed['parallel']['axes']
                && $state['chart']['parallel']['rows'] === $legacy['parallel']['rows'] && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Tile, independent IPSView and published WebContent retain original values and identical ranges within output limits.'
            );
            if ($index > 0) {
                assertGatewayGauge($state['chart']['parallel']['style']['lineColor'] === '#123456', 'Independent IPSView design remains unchanged.');
            }
        }
        $chart->RefreshArchive();
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestProperties() === $properties, 'Repeated apply/refresh never saves defaults or modifies designs/source rows.');
        // Endpoint values are not violations; the final, off-screen axis participates too.
        $endpointSources = $sources;
        $endpointSources[0]['AxisMaximum'] = 45.0;
        $endpointSources[15]['AxisMaximum'] = 60.0;
        $chart->SetTestProperty('Sources', json_encode($endpointSources, JSON_THROW_ON_ERROR));
        assertGatewayGauge($read()['parallel']['outOfRangeCount'] === 0, 'Exact limits remain original, valid endpoint values.');
        $reordered = array_merge([$sources[15]], array_slice($sources, 0, 15));
        $chart->SetTestProperty('Sources', json_encode($reordered, JSON_THROW_ON_ERROR));
        $reorderedModel = $read();
        assertGatewayGauge(
            $reorderedModel['parallel']['axes'][0]['variableID'] === 99615
            && $reorderedModel['parallel']['axes'][0]['maximum'] === 50.0
            && $reorderedModel['parallel']['axes'][1]['minimum'] === -10.0
            && $reorderedModel['parallel']['rows'][0]['values'][0] === 10.0,
            'Reordering and identical labels keep limits, original values and variable IDs together.'
        );
        foreach ([['AxisRangeMode' => 'other'], ['AxisRangeMode' => null],
            ['AxisRangeMode' => 'manual'],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => '0', 'AxisMaximum' => 100],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => null, 'AxisMaximum' => 100],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => false, 'AxisMaximum' => 100],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => 5, 'AxisMaximum' => 5],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => 6, 'AxisMaximum' => 5],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => -PHP_FLOAT_MAX, 'AxisMaximum' => PHP_FLOAT_MAX],
            ['AxisRangeMode' => 'manual', 'AxisMinimum' => PHP_INT_MAX - 1, 'AxisMaximum' => PHP_INT_MAX]] as $invalid) {
            $bad = $sources;
            $bad[15] = ['VariableID' => 99615, 'Label' => 'Same axis'] + $invalid;
            $chart->SetTestProperty('Sources', json_encode($bad, JSON_THROW_ON_ERROR));
            $requests = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 201 && $requests === [], 'Invalid ranges on hidden axes must fail before archive I/O.');
        }
        $bad = $sources;
        $bad[15]['AxisMaximum'] = 'NONFINITE_LIMIT';
        $chart->SetTestProperty('Sources', str_replace('"NONFINITE_LIMIT"', '1e400', json_encode($bad, JSON_THROW_ON_ERROR)));
        $requests = [];
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 201 && $requests === [], 'Overflowing JSON numbers must not reach archive or renderer.');
        $auto = array_map(static fn (array $s): array => ['VariableID' => $s['VariableID'], 'Label' => $s['Label'],
            'AxisRangeMode'                                            => 'auto', 'AxisMinimum' => 'inactive', 'AxisMaximum' => null], $sources);
        $chart->SetTestProperty('Sources', json_encode($auto, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge($read() === $legacy, 'Returning to auto ignores dormant bounds and restores the legacy model exactly.');
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $before = $chart->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Preview read archives.'); };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $field = findGaugeFormElement($form['elements'], 'Sources');
        assertGatewayGauge(in_array('AxisRangeMode', array_column($field['columns'], 'name'), true)
            && in_array('AxisMinimum', array_column($field['form'], 'name'), true), 'Source editors expose persisted range mode and explicit limits.');
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            $svg = base64_decode(substr($field['image'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                str_contains($svg, 'data-axis-minimum="-10"') && str_contains($svg, 'data-axis-maximum="40"'),
                'Both schematic previews reflect configured limits without archive reads.'
            );
        }
        $draft = $sources;
        $draft[0]['AxisMinimum'] = -20.5;
        $draft[0]['AxisMaximum'] = 60.5;
        $chart->UpdateBarPreviewFromForm(json_encode(['Sources' => $draft], JSON_THROW_ON_ERROR));
        $updates = $chart->GetTestFormUpdates();
        $lastImage = end($updates)['Value'];
        assertGatewayGauge(
            str_contains(base64_decode(substr($lastImage, strlen('data:image/svg+xml;base64,'))), 'data-axis-minimum="-20.5"'),
            'Unsaved source limits update the preview immediately.'
        );
        assertGatewayGauge($chart->GetTestProperties() === $before, 'Form drafts must not persist range or design changes.');
        $draft[15]['AxisMaximum'] = $draft[15]['AxisMinimum'];
        $chart->UpdateBarPreviewFromForm(json_encode(['Sources' => $draft], JSON_THROW_ON_ERROR));
        $updates = $chart->GetTestFormUpdates();
        $svg = base64_decode(substr(end($updates)['Value'], strlen('data:image/svg+xml;base64,')));
        assertGatewayGauge(
            str_contains($svg, 'Parallel axis ranges require') && !str_contains($svg, '<polyline'),
            'An invalid draft on an omitted preview axis must show a validation message, not a plausible sample.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
        date_default_timezone_set($zone);
    }
})();
echo "Parallel fixed axis ranges, originals, validation, budgets and drafts verified.\n";
