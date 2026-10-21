<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

// Real Heatmap/Gateway with synthetic Kernel archives. No installation or archive writes.
(static function (): void
{
    $savedArchive = $GLOBALS['symconTestArchiveVariables'][4711];
    $savedParent = IPSModuleStrict::$ParentResponder;
    $savedTimezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $time = static fn (string $s): int => (new DateTimeImmutable($s, new DateTimeZone('Europe/Berlin')))->getTimestamp();
    $date = static function (string $s): string
    {
        return json_encode(array_combine(['year', 'month', 'day', 'hour', 'minute', 'second'], array_map('intval', preg_split('/[- :]/', $s))), JSON_THROW_ON_ERROR);
    };
    $requests = [];
    $parent = static function (string $json) use (&$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if ($request['Operation'] === 'archive.read') {
            $requests[] = $request['Payload'];
        }
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $parent;
    $chart = new TestableEChartsHeatmap();
    $chart->Create();
    $chart->SetTestCurrentTimestamp($time('2026-10-13 12:34:00'));
    $chart->SetTestProperty('SourceVariableID', 4711);
    $chart->SetTestProperty('UseVariablePresentation', false);
    $chart->SetTestProperty('Unit', 'kWh');
    $chart->SetTestProperty('EnableIPSView', true);
    $chart->SetTestProperty('IPSViewUseTileDesign', false);
    $chart->SetTestProperty('IPSViewLowColor', 0x123456);
    $read = static fn (): array => json_decode($chart->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
    $bootstrap = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Every Heatmap period must retain bounded HTML.');
        assertGatewayGauge(preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $m) === 1, 'Heatmap bootstrap required.');
        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    try {
        $rows = [];
        foreach ([['2026-10-12 08:00:00', 30, 3, 50], ['2026-10-05 08:00:00', 20, 2, 40], ['2026-09-28 08:00:00', 10, 1, 30]] as [$stamp, $avg, $min, $max]) {
            $rows[] = ['TimeStamp' => $time($stamp), 'Avg' => $avg, 'Min' => $min, 'Max' => $max];
        }
        $GLOBALS['symconTestArchiveVariables'][4711] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => $rows];
        foreach (['custom', 'absolute'] as $range) {
            $chart->SetTestProperty('Range', $range);
            $chart->SetTestProperty('CustomRangeValue', 2);
            $chart->SetTestProperty('CustomRangeUnit', 'week');
            $chart->SetTestProperty('AbsoluteStart', $date('2026-09-28 00:00:00'));
            $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-13 12:00:00'));
            foreach (['average', 'minimum', 'maximum', 'consumption'] as $metric) {
                $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = $metric === 'consumption' ? 1 : 0;
                $chart->SetTestProperty('Metric', $metric);
                $properties = $chart->GetTestProperties();
                $chart->ApplyChanges();
                $chart->ApplyChanges();
                $result = $read();
                $expected = $range === 'custom' ? ['average' => 25.0, 'minimum' => 2.0, 'maximum' => 50.0, 'consumption' => 50.0]
                    : ['average' => 20.0, 'minimum' => 1.0, 'maximum' => 50.0, 'consumption' => 60.0];
                assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE && $result['cells'] === [
                    ['hour' => 8, 'weekday' => 0, 'value' => $expected[$metric], 'count' => $range === 'custom' ? 2 : 3]
                ] && $result['heatmap']['days'] === [], 'New periods must retain the four native metrics and weekday/hour reduction.');
                foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewHeatmap')] as $index => $html) {
                    $state = $bootstrap($html);
                    assertGatewayGauge(
                        $state['status'] === 'ready' && $state['chart']['cells'] === $result['cells']
                        && $state['chart']['heatmap']['requestedStartTimestamp'] === $result['heatmap']['requestedStartTimestamp'],
                        'Both outputs and published WebContent must retain original metrics and fixed/relative bounds.'
                    );
                    if ($index > 0) {
                        assertGatewayGauge($state['chart']['heatmap']['style']['lowColor'] === '#123456', 'Independent IPSView design must remain intact.');
                    }
                }
                $tile = $chart->GetVisualizationTile();
                preg_match('#/hook/SymconECharts/state/5000/([a-f0-9]{32})#', $tile, $channel);
                foreach (['tile', 'ipsview'] as $output) {
                    foreach ([1, 0] as $offset) {
                        $request = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}'] + EChartsDataProtocol::CreateRequest('ipsview.state', [
                            'InstanceID' => 5000, 'Channel' => $channel[1], 'WindowOffset' => $offset, 'Output' => $output
                        ]);
                        $response = EChartsDataProtocol::DecodeResponse($chart->ReceiveData(json_encode($request, JSON_THROW_ON_ERROR)), 'ipsview.state');
                        $navigated = $response['Payload']['State']['chart'];
                        assertGatewayGauge(
                            $response['Success'] && $navigated['heatmap']['windowOffset'] === $offset
                            && $navigated['heatmap']['metric'] === $metric && $chart->GetTestProperties() === $properties,
                            'Authenticated previous/reset navigation is read-only in both outputs.'
                        );
                        if ($offset === 1) {
                            assertGatewayGauge(
                                $navigated['heatmap']['requestedEndTimestamp'] === $result['heatmap']['requestedStartTimestamp'],
                                'Previous windows must be adjacent before full-hour rounding.'
                            );
                        }
                    }
                }
                $chart->RefreshArchive();
                assertGatewayGauge($read() === $result && $chart->GetTestProperties() === $properties, 'Refresh must retain selection, designs and stable view identity.');
            }
        }
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = 0;
        $chart->SetTestProperty('Metric', 'average');
        $chart->SetTestProperty('Range', 'custom');
        $chart->SetTestProperty('CustomRangeValue', 15);
        $chart->SetTestProperty('CustomRangeUnit', 'minute');
        $before = count($requests);
        assertGatewayGauge($read()['cells'] === [] && count($requests) === $before, 'No contained hour means no Gateway query and no fabricated values.');
        foreach ([['Range' => 'custom', 'CustomRangeValue' => 367, 'CustomRangeUnit' => 'day'],
            ['Range' => 'absolute', 'AbsoluteStart' => '{}'],
            ['Range' => 'absolute', 'AbsoluteStart' => $date('2026-10-13 12:00:00'), 'AbsoluteEnd' => $date('2026-10-13 12:00:00')],
            ['Range' => 'absolute', 'AbsoluteStart' => $date('2026-10-01 00:00:00'), 'AbsoluteEnd' => $date('2027-01-01 00:00:00')]] as $bad) {
            foreach ($bad as $name => $value) {
                $chart->SetTestProperty($name, $value);
            }
            $before = count($requests);
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202 && count($requests) === $before
                && $bootstrap($chart->GetVisualizationTile())['status'] === 'error', 'Invalid windows fail before any archive read.');
        }
        $chart->SetTestProperty('Range', 'custom');
        $chart->SetTestProperty('CustomRangeValue', 366);
        $chart->SetTestProperty('CustomRangeUnit', 'day');
        $dense = [];
        $end = $time('2026-10-13 12:00:00');
        for ($i = 1; $i <= 366 * 24; $i++) {
            $dense[] = ['TimeStamp' => $end - $i * 3600, 'Avg' => 1.0, 'Min' => 0.0, 'Max' => 2.0];
        }
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'] = $dense;
        $before = count($requests);
        $full = $read();
        assertGatewayGauge(
            array_sum(array_column($full['cells'], 'count')) === 8784 && count($requests) - $before === 7,
            'A full former twelve-month workload must survive all seven archive chunks without truncation.'
        );
        foreach (array_slice($requests, $before) as $request) {
            assertGatewayGauge(
                $request['Limit'] === 2000 && $request['AggregationLevel'] === 0
                && $request['EndTimestamp'] - $request['StartTimestamp'] + 1 <= 60 * 86400,
                'Chunk count, size, daily source count and hourly precision budgets must not grow.'
            );
        }
        foreach (['Truncated' => true, 'Points' => array_fill(0, 2001, [$end - 1, 1.0])] as $field => $value) {
            $call = 0;
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$call, $field, $value): string
            {
                $response = json_decode($parent($json), true, 512, JSON_THROW_ON_ERROR);
                if (++$call === 7) {
                    $response['Payload'][$field] = $value;
                }
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            try {
                $read();
                throw new LogicException('A malformed final archive chunk produced partial statistics.');
            } catch (RuntimeException) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'The final chunk has the same completeness/budget checks as the first.');
            }
        }
        IPSModuleStrict::$ParentResponder = $parent;
        assertGatewayGauge($read() === $full, 'Recovery must restore the whole population, not a cached partial result.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($bootstrap($html)['status'] === 'ready', 'The maximum archive workload remains within both HTML budgets.');
        }
        $properties = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $before = count($requests);
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Draft preview queried the archive.'); };
        foreach (['custom', 'absolute'] as $range) {
            $chart->UpdateTimeRangeForm($range);
            $chart->UpdateBarPreviewFromForm(json_encode(['Range' => $range, 'CustomRangeValue' => 2, 'CustomRangeUnit' => 'week',
                'AbsoluteStart'                                   => json_decode($date('2026-09-28 00:00:00')), 'AbsoluteEnd' => $date('2026-10-13 12:00:00')], JSON_THROW_ON_ERROR));
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $image) {
                $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-range="' . $range . '"')
                    && str_contains($svg, 'Europe/Berlin') && str_contains($svg, '2026-'), 'Both previews use actual draft windows and escaped labeled synthetic data.');
            }
        }
        $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":{}}');
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(!str_contains($svg, 'data-range=') && substr_count($svg, '<rect') <= 1, 'Invalid drafts show no plausible heatmap cells.');
        }
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'CustomRangeValue')['visible'] === true
            && findGaugeFormElement($form['elements'], 'AbsoluteStart')['type'] === 'SelectDateTime'
            && findGaugeFormElement($form['elements'], 'AbsoluteStart')['visible'] === false,
            'Native fields and initial visibility follow the stored mode.'
        );
        assertGatewayGauge(
            $chart->GetTestProperties() === $properties && $chart->GetTestStatus() === $status && count($requests) === $before,
            'Draft changes and form opening must not save properties, change status or query archives.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $savedParent;
        $GLOBALS['symconTestArchiveVariables'][4711] = $savedArchive;
        date_default_timezone_set($savedTimezone);
    }
})();
