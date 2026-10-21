<?php

declare(strict_types=1);

// Real module/Gateway/planner; only Kernel archive storage is synthetic.
(static function (): void
{
    $parent = IPSModuleStrict::$ParentResponder;
    $variables = $GLOBALS['symconTestVariables'];
    $archives = $GLOBALS['symconTestArchiveVariables'];
    $timezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $requests = [];
    $tamper = null;
    IPSModuleStrict::$ParentResponder = static function (string $json) use (&$requests, &$tamper): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if ($request['Operation'] === 'archive.read') {
            $requests[] = $request['Payload'];
        }
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        $response = $gateway->ForwardData($json);
        return $tamper === null ? $response : $tamper($response);
    };
    try {
        $start = strtotime('2026-10-19 00:00:00');
        $sources = [];
        foreach (range(0, 15) as $index) {
            $id = 99700 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Same axis'];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => 99999.0,
                'VariableUpdated'                                  => $start, 'Name' => 'Synthetic axis',
                'Presentation'                                     => ['SUFFIX' => $index === 15 ? ' kWh' : ' °C', 'DIGITS' => 2]];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index === 15 ? 1 : 0,
                'LoggedValues'                                               => [], 'AggregatedValues' => array_map(static fn (int $hour): array => [
                    'TimeStamp' => $start + $hour * 3600, 'Avg' => 10.0 + $hour + $index,
                    'Min'       => -5.0 + $hour, 'Max' => 40.0 + $hour
                ], [2, 1, 0])];
        }
        $chart = new TestableEChartsParallel();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(strtotime('2026-10-20 12:34:00'));
        $chart->SetTestProperty('Range', '7d');
        $chart->SetTestProperty('LineBudget', 1000);
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewLineColor', 0x123456);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $read = static fn (): array => json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        $legacy = $read();
        assertGatewayGauge(!isset($legacy['parallel']['axes'][0]['metric'])
            && $legacy['parallel']['rows'][0]['values'][15] === 25.0, 'Legacy sources keep automatic mean/counter consumption and unchanged axis metadata.');
        $metrics = ['minimum', 'maximum', 'average'];
        foreach ($sources as $index => &$source) {
            $source['Metric'] = $index === 15 ? 'consumption' : $metrics[$index % 3];
        }
        unset($source);
        $sources[0] += ['AxisRangeMode' => 'manual', 'AxisMinimum' => -4.0, 'AxisMaximum' => 50.0];
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $properties = $chart->GetTestProperties();
        $requests = [];
        $data = $read();
        assertGatewayGauge(
            $data['parallel']['rows'][0]['values'][0] === -5.0
            && $data['parallel']['rows'][0]['values'][1] === 40.0
            && $data['parallel']['rows'][0]['values'][2] === 12.0
            && $data['parallel']['rows'][0]['values'][15] === 25.0,
            'Selected per-axis metrics must use real aggregate Min/Max/Avg and counter consumption, never the current value.'
        );
        assertGatewayGauge(array_column($data['parallel']['axes'], 'metric') === array_column($sources, 'Metric')
            && $data['parallel']['outOfRangeCount'] === 1, 'Metric metadata and fixed-range violations must describe selected values, not means.');
        assertGatewayGauge(count($requests) === 16 && array_column($requests, 'Reducer') === [
            'minimum', 'maximum', 'average', 'minimum', 'maximum', 'average', 'minimum', 'maximum',
            'average', 'minimum', 'maximum', 'average', 'minimum', 'maximum', 'average', 'sum'
        ] && array_column($requests, 'Limit') === array_fill(0, 16, 625)
            && $data['range'] === $legacy['range'], 'One query per axis shares the existing 10000-aggregate plan; only reducers change.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewParallel')] as $index => $html) {
            preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $m);
            $bootstrap = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge($bootstrap['state']['status'] === 'ready'
                && $bootstrap['state']['chart']['parallel']['axes'] === $data['parallel']['axes']
                && $bootstrap['state']['chart']['parallel']['rows'] === $data['parallel']['rows']
                && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both outputs and published IPSView WebContent retain metrics, originals and bounded output.');
            if ($index > 0) {
                assertGatewayGauge($bootstrap['state']['chart']['parallel']['style']['lineColor'] === '#123456', 'Metric selection preserves independent IPSView design.');
            }
        }
        $chart->RefreshArchive();
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestProperties() === $properties, 'Refresh and repeated apply must not rewrite sources, ranges or designs.');
        $reordered = array_reverse($sources);
        $chart->SetTestProperty('Sources', json_encode($reordered, JSON_THROW_ON_ERROR));
        $reversed = $read();
        assertGatewayGauge($reversed['parallel']['axes'][0]['variableID'] === 99715
            && $reversed['parallel']['axes'][0]['metric'] === 'consumption'
            && $reversed['parallel']['rows'][0]['values'][0] === 25.0, 'Equal names and reordering never detach metrics from variable IDs.');
        foreach (['sum', 'raw', '', null, false, 1, ['minimum']] as $invalid) {
            $bad = $sources;
            $bad[15]['Metric'] = $invalid;
            $chart->SetTestProperty('Sources', json_encode($bad, JSON_THROW_ON_ERROR));
            $requests = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 201 && $requests === [], 'Invalid metric on the hidden sixteenth axis must fail before archive I/O.');
        }
        // Gateway supports extrema on counters, but this family explicitly does not.
        foreach ([[15, 'minimum'], [15, 'maximum'], [15, 'average'], [0, 'consumption']] as [$index, $metric]) {
            $bad = $sources;
            $bad[$index]['Metric'] = $metric;
            $chart->SetTestProperty('Sources', json_encode($bad, JSON_THROW_ON_ERROR));
            $chart->ApplyChanges();
            try {
                $read();
                throw new LogicException('Archive type mismatch was accepted.');
            } catch (RuntimeException $exception) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'Explicit metric/archive mismatch must be a controlled load failure.');
            }
        }
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        foreach ([['EffectiveReducer', 'average'], ['ArchiveAggregationType', 'counter'], ['StartTimestamp', 1]] as [$field, $value]) {
            $tamper = static function (string $json) use ($field, $value): string
            {
                $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $response['Payload'][$field] = $value;
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            try {
                $read();
                throw new LogicException('Mismatched metric response was accepted.');
            } catch (RuntimeException) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'Incorrect Gateway metric/type/time metadata must not produce plausible data.');
            }
        }
        $tamper = null;
        $auto = array_map(static fn (array $s): array => ['VariableID' => $s['VariableID'], 'Label' => $s['Label'], 'Metric' => 'auto'], $sources);
        $chart->SetTestProperty('Sources', json_encode($auto, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge($read() === $legacy, 'Explicit automatic mode restores the exact legacy model, including counter behavior.');
        // Selected extrema still require the complete common timestamp intersection.
        array_splice($GLOBALS['symconTestArchiveVariables'][99715]['AggregatedValues'], 1, 1);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        assertGatewayGauge(count($read()['parallel']['rows']) === 2, 'Missing intervals on a nonvisible consumption axis remove the complete line, without filling.');
        $shortArchives = $GLOBALS['symconTestArchiveVariables'];
        $chart->SetTestProperty('Aggregation', 'day');
        foreach ($sources as $source) {
            $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregatedValues'] = [
                ['TimeStamp' => $start, 'Avg' => 12.0, 'Min' => -7.0, 'Max' => 32.0]
            ];
        }
        $requests = [];
        $daily = $read();
        assertGatewayGauge($daily['parallel']['rows'][0]['values'][0] === -7.0
            && $daily['parallel']['rows'][0]['values'][1] === 32.0
            && $daily['parallel']['rows'][0]['values'][15] === 12.0
            && array_column($requests, 'AggregationLevel') === array_fill(0, 16, 1), 'Daily metrics use the same source reducer and completed-day query, not means of hourly extrema.');
        $chart->SetTestProperty('Aggregation', 'hour');
        $chart->SetTestProperty('Range', 'custom');
        $chart->SetTestProperty('CustomRangeValue', 1000);
        $chart->SetTestProperty('CustomRangeUnit', 'hour');
        $loadStart = strtotime('2026-10-20 12:00:00') - 1001 * 3600;
        foreach ($sources as $index => $source) {
            $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregatedValues'] = array_map(
                static fn (int $hour): array => ['TimeStamp' => $loadStart + $hour * 3600,
                    'Avg'                                    => 12.0 + $index, 'Min' => -7.0, 'Max' => 32.0],
                range(1000, 0)
            );
        }
        $requests = [];
        $load = $read();
        assertGatewayGauge(
            count($requests) === 16 && array_sum(array_column($requests, 'Limit')) === 10000
            && count($load['parallel']['rows']) === 625 && $load['parallel']['truncated']
            && $load['parallel']['rows'][0]['timestamp'] === $loadStart + 376 * 3600,
            'All sixteen metric-bearing axes retain the newest 625 common lines within the unchanged 10000-aggregate budget.'
        );
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Full metric-bearing output stays below 1 MiB in both adapters.');
        }
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', json_encode(['year' => 2026, 'month' => 10, 'day' => 18, 'hour' => 0, 'minute' => 0, 'second' => 0]));
        $chart->SetTestProperty('AbsoluteEnd', json_encode(['year' => 2026, 'month' => 10, 'day' => 20, 'hour' => 0, 'minute' => 0, 'second' => 0]));
        $fixedPeriod = $read();
        assertGatewayGauge($fixedPeriod['parallel']['axes'] === $load['parallel']['axes']
            && $fixedPeriod['range']['key'] === 'absolute', 'Historical dates reuse the same metrics and per-source bounds.');
        $GLOBALS['symconTestArchiveVariables'] = $shortArchives;
        $chart->SetTestProperty('Range', '7d');
        $chart->ApplyChanges();
        $before = $chart->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Preview attempted archive I/O.'); };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $field = findGaugeFormElement($form['elements'], 'Sources');
        assertGatewayGauge(in_array('Metric', array_column($field['columns'], 'name'), true)
            && in_array('Metric', array_column($field['form'], 'name'), true), 'Source list and row editor must expose persisted metric selection.');
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            $svg = base64_decode(substr($field['image'], strlen('data:image/svg+xml;base64,')));
            assertGatewayGauge(
                str_contains($svg, 'data-axis-metric="minimum"') && str_contains($svg, 'data-axis-metric="maximum"'),
                'Both example-data previews must reflect selected metrics without archive reads.'
            );
        }
        $draft = $sources;
        $draft[0]['Metric'] = 'maximum';
        $chart->UpdateBarPreviewFromForm(json_encode(['Sources' => $draft], JSON_THROW_ON_ERROR));
        $updates = $chart->GetTestFormUpdates();
        $svg = base64_decode(substr(end($updates)['Value'], strlen('data:image/svg+xml;base64,')));
        assertGatewayGauge(
            str_contains($svg, 'data-axis-metric="maximum"') && $chart->GetTestProperties() === $before,
            'Unsaved metric drafts update both previews without saving configuration.'
        );
        $draft[15]['Metric'] = 'sum';
        $chart->UpdateBarPreviewFromForm(json_encode(['Sources' => $draft], JSON_THROW_ON_ERROR));
        $updates = $chart->GetTestFormUpdates();
        $svg = base64_decode(substr(end($updates)['Value'], strlen('data:image/svg+xml;base64,')));
        assertGatewayGauge(str_contains($svg, 'Parallel metrics must be') && !str_contains($svg, '<polyline'), 'Invalid metric drafts beyond the excerpt show an error instead of sample curves.');
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
        $GLOBALS['symconTestVariables'] = $variables;
        $GLOBALS['symconTestArchiveVariables'] = $archives;
        date_default_timezone_set($timezone);
    }
})();

echo "Parallel per-axis metrics, archive type separation, outputs, budgets and drafts verified.\n";
