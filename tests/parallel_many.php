<?php

declare(strict_types=1);

// Real module/planner/pairing/Gateway; synthetic Kernel archives only.
(static function (): void
{
    $parent = IPSModuleStrict::$ParentResponder;
    $timezone = date_default_timezone_get();
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedArchives = $GLOBALS['symconTestArchiveVariables'];
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
    $date = static fn (string $value): string => json_encode(array_combine(
        ['year', 'month', 'day', 'hour', 'minute', 'second'],
        array_map('intval', preg_split('/[- :]/', $value))
    ), JSON_THROW_ON_ERROR);
    try {
        $sources = [];
        $start = strtotime('2026-09-01 00:00:00');
        foreach (range(0, 16) as $index) {
            $id = 99500 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Repeated axis'];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => 999999.0, 'Name' => 'Synthetic axis',
                'Presentation'                                     => ['SUFFIX' => $index === 15 ? ' kWh' : ($index % 2 ? ' %' : ' °C'), 'DIGITS' => 1]];
            $rows = [];
            foreach (range(1000, 0) as $hour) {
                $rows[] = ['TimeStamp' => $start + $hour * 3600,
                    'Avg'              => (float) ($hour % 37 + $index - ($index === 15 ? 0 : 18)), 'Min' => -9000.0, 'Max' => 9000.0];
            }
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index === 15 ? 1 : 0,
                'LoggedValues'                                               => [], 'AggregatedValues' => $rows];
        }
        $chart = new TestableEChartsParallel();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(strtotime('2026-10-20 12:34:00'));
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 16), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('LineBudget', 1000);
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', $date('2026-09-01 00:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-20 00:00:00'));
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewLineColor', 0x123456);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Sixteen distinct numeric archive axes must be accepted.');
        $properties = $chart->GetTestProperties();
        $read = static fn (): array => json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        $requests = [];
        $model = $read();
        assertGatewayGauge(count($model['parallel']['axes']) === 16 && count($model['parallel']['rows']) === 625
            && count($requests) === 16 && array_column($requests, 'Limit') === array_fill(0, 16, 625)
            && array_sum(array_column($requests, 'Limit')) === 10000, 'Sixteen axes share exactly 10000 aggregates, at most 625 each.');
        assertGatewayGauge(
            $model['parallel']['rows'][0]['timestamp'] === $start + 376 * 3600
            && $model['parallel']['rows'][624]['timestamp'] === $start + 1000 * 3600
            && count($model['parallel']['rows'][0]['values']) === 16 && $model['parallel']['truncated'],
            'The newest complete sixteen-dimensional rows and truncation warning must be retained.'
        );
        assertGatewayGauge($model['range']['lineBudget'] === 1000 && $model['range']['effectiveLineBudget'] === 625
            && $model['range']['aggregateBudget'] === 10000, 'Configured and effective line limits must be explicit, never silently saved back.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewParallel')] as $index => $html) {
            assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($html, 'echarts-parallel-viewport')
                && str_contains($html, 'SymconEChartsCategoryNavigation'), 'Both bounded outputs must load the complete chart and shared viewport anchor logic.');
            preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $match);
            $state = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
            assertGatewayGauge(
                $state['status'] === 'ready' && $state['chart']['parallel']['rows'] === $model['parallel']['rows'],
                'Both outputs and published WebContent must retain all axes, values and common rows.'
            );
            if ($index > 0) {
                assertGatewayGauge($state['chart']['parallel']['style']['lineColor'] === '#123456', 'Independent IPSView design remains intact.');
            }
        }
        array_shift($GLOBALS['symconTestArchiveVariables'][99515]['AggregatedValues']);
        $sparse = $read();
        assertGatewayGauge(
            count($sparse['parallel']['rows']) === 624 && end($sparse['parallel']['rows'])['timestamp'] === $start + 999 * 3600,
            'An absent value on the sixteenth axis must still remove that common interval, even if the axis is off screen.'
        );
        $chart->RefreshArchive();
        assertGatewayGauge($chart->GetTestProperties() === $properties, 'Refresh and output must not alter configured sources, budgets or designs.');
        foreach ([2 => 1000, 10 => 1000, 11 => 909, 13 => 769, 15 => 666, 16 => 625] as $count => $limit) {
            $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, $count), JSON_THROW_ON_ERROR));
            foreach (['absolute', 'custom', '30d'] as $range) {
                $chart->SetTestProperty('Range', $range);
                $chart->SetTestProperty('CustomRangeValue', 70);
                $chart->SetTestProperty('CustomRangeUnit', 'day');
                $requests = [];
                $data = $read();
                assertGatewayGauge(
                    count($requests) === $count && array_unique(array_column($requests, 'Limit')) === [$limit]
                    && array_sum(array_column($requests, 'Limit')) <= 10000 && count($data['parallel']['rows']) <= $limit,
                    'Old and new axis counts share the same capped planner in every range mode.'
                );
            }
        }
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 16), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('LineBudget', 12);
        $requests = [];
        $read();
        assertGatewayGauge(array_column($requests, 'Limit') === array_fill(0, 16, 12), 'Smaller user line budgets must not be enlarged.');
        foreach ([$sources, [$sources[0]], [$sources[0], $sources[0]]] as $badSources) {
            $chart->SetTestProperty('Sources', json_encode($badSources, JSON_THROW_ON_ERROR));
            $requests = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 201 && $requests === [], 'Seventeen, single or duplicate axes must fail before archive access.');
        }
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 16), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('LineBudget', 1000);
        $chart->ApplyChanges();
        $goodResponder = IPSModuleStrict::$ParentResponder;
        $call = 0;
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($goodResponder, &$call): string
        {
            $r = json_decode($goodResponder($json), true, 512, JSON_THROW_ON_ERROR);
            if (++$call === 16) {
                $r['Payload']['Points'] = array_fill(0, 626, [1790000000, 1.0]);
            }
            return json_encode($r, JSON_THROW_ON_ERROR);
        };
        try {
            $read();
            throw new LogicException('The final axis exceeded its reserved archive budget.');
        } catch (RuntimeException) {
            assertGatewayGauge($chart->GetTestStatus() === 204, 'An oversized final response must not produce a plausible partial model.');
        }
        IPSModuleStrict::$ParentResponder = $goodResponder;
        $chart->ApplyChanges();
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('Aggregation', 'day');
        $chart->SetTestProperty('AbsoluteStart', $date('2026-10-12 08:30:00'));
        $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-17 12:30:00'));
        foreach (range(99500, 99515) as $id) {
            $GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'] = [];
            foreach (range(16, 13) as $day) {
                if ($id === 99515 && $day === 14) {
                    continue;
                }
                $GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'][] = ['TimeStamp' => strtotime('2026-10-' . $day . ' 00:00:00'),
                    'Avg'                                                                        => (float) ($day + $id - 99500), 'Min' => 0.0, 'Max' => 100.0];
            }
        }
        $requests = [];
        $daily = $read();
        assertGatewayGauge(array_column($daily['parallel']['rows'], 'timestamp') === [strtotime('2026-10-13 00:00:00'),
            strtotime('2026-10-15 00:00:00'), strtotime('2026-10-16 00:00:00')]
            && array_unique(array_column($requests, 'AggregationLevel')) === [1], 'Sixteen daily axes keep complete calendar days and the common-intersection rule.');
        $chart->SetTestProperty('Aggregation', 'hour');
        $chart->SetTestProperty('AbsoluteStart', $date('2026-09-01 00:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-20 00:00:00'));
        $hugeSources = array_slice($sources, 0, 16);
        foreach ($hugeSources as &$entry) {
            $entry['Label'] = str_repeat('🔋', 120);
            $GLOBALS['symconTestArchiveVariables'][$entry['VariableID']]['AggregatedValues'] = array_map(
                static fn (int $hour): array => ['TimeStamp' => $start + $hour * 3600,
                    'Avg'                                    => 1.2345678901234567e150, 'Min' => 0.0, 'Max' => 1.3e150],
                range(1000, 0)
            );
        }
        unset($entry);
        $chart->SetTestProperty('Sources', json_encode($hugeSources, JSON_THROW_ON_ERROR));
        $large = $read();
        assertGatewayGauge(count($large['parallel']['rows']) === 625, 'The full aggregate workload remains usable with long Unicode labels and large finite values.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Worst-case Unicode sources and 10000 large finite aggregates fit both output budgets.');
        }
        $before = $chart->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Preview queried archives.'); };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            $svg = base64_decode(substr($field['image'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(str_contains($svg, 'data-axis-count="16"') && str_contains($svg, 'data-preview-axis-count="6"')
                && str_contains($svg, 'Axis excerpt') && str_contains($svg, '16'), 'Both schematic previews must disclose the six-axis excerpt, not imply a complete six-axis chart.');
        }
        assertGatewayGauge($chart->GetTestProperties() === $before, 'Opening the form must not save an excerpt as the configured source list.');
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
        date_default_timezone_set($timezone);
    }
})();
