<?php

declare(strict_types=1);

// Real Calendar/Gateway with the existing synthetic Kernel archives; no installation data.
(static function () use ($calendar, $calendarID, $calendarRequest): void
{
    $saved = $calendar->GetTestProperties();
    $parent = IPSModuleStrict::$ParentResponder;
    $queries = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$queries): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.read') {
            $queries[] = $request['Payload'];
        }
        return $parent($json);
    };
    $date = static fn (int $year, int $month, int $day): string => json_encode(compact('year', 'month', 'day'), JSON_THROW_ON_ERROR);
    $model = static fn (): array => json_decode($calendar->GetCalendarData(), true, 512, JSON_THROW_ON_ERROR)['calendar'];
    $bootstrap = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'All Calendar views must retain the output budget.');
        assertGatewayGauge(preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $match) === 1, 'Calendar bootstrap is required.');
        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    try {
        $calendar->SetTestProperty('EnableIPSView', true);
        $calendar->SetTestProperty('IPSViewUseTileDesign', false);
        $calendar->SetTestProperty('IPSViewLowColor', 0x123456);
        foreach (['year', 'quarter', 'month', 'absolute'] as $period) {
            $calendar->SetTestProperty('Period', $period);
            $calendar->SetTestProperty('Year', 2026);
            $calendar->SetTestProperty('Month', 10);
            $calendar->SetTestProperty('Quarter', 4);
            $calendar->SetTestProperty('AbsoluteStart', $date(2025, 12, 20));
            $calendar->SetTestProperty('AbsoluteEnd', $date(2026, 10, 16));
            $properties = $calendar->GetTestProperties();
            $calendar->ApplyChanges();
            $calendar->ApplyChanges();
            $data = $model();
            assertGatewayGauge(
                $calendar->GetTestStatus() === IS_ACTIVE && $data['period'] === $period
                && count($data['data']) === ($period === 'absolute' ? 4 : 3)
                && $data['endTimestamp'] === strtotime('2026-10-16 00:00:00') - 1,
                'Every view must preserve completed native daily aggregates, sparse days and current-day exclusion: ' . $period
            );
            $lastQuery = end($queries);
            assertGatewayGauge(
                $lastQuery['Mode'] === 'aggregated' && $lastQuery['AggregationLevel'] === 1
                && $lastQuery['Reducer'] === 'average' && $lastQuery['Limit'] === 366,
                'View size must never increase the existing 366-point daily archive budget.'
            );
            foreach (['minimum' => [7.0, -9.0, -1.0], 'maximum' => [11.0, -4.0, 1.0]] as $metric => $expected) {
                $calendar->SetTestProperty('Metric', $metric);
                $points = array_column($model()['data'], 'value');
                assertGatewayGauge(
                    array_slice($points, -3) === $expected,
                    'Every view must preserve native daily extrema without re-aggregation: ' . $period . '/' . $metric
                );
            }
            $calendar->SetTestProperty('Metric', 'average');
            foreach ([$calendar->GetVisualizationTile(), $calendar->GetIPSViewHTML(), $calendar->GetTestVariableValue('IPSViewCalendar')] as $index => $html) {
                $state = $bootstrap($html);
                assertGatewayGauge(
                    $state['status'] === 'ready' && $state['chart']['calendar']['data'] === $data['data']
                    && $state['chart']['calendar']['calendarRange'] === $data['calendarRange']
                    && $state['chart']['calendar']['periodLabel'] === $data['periodLabel'],
                    'Tile, independent IPSView HTML and WebContent must contain identical period bounds and source values.'
                );
                if ($index > 0) {
                    assertGatewayGauge($state['chart']['calendar']['style']['lowColor'] === '#123456', 'Period changes must retain independent IPSView colors.');
                }
            }
            $calendar->RefreshArchive();
            assertGatewayGauge(
                $calendar->GetTestProperties() === $properties && $model() === $data,
                'Repeated ApplyChanges and refresh must not persist navigation or change source/design properties.'
            );
            foreach (['tile', 'ipsview'] as $output) {
                $request = $calendarRequest;
                $request['Payload']['Output'] = $output;
                $response = SymconECharts\EChartsDataProtocol::DecodeResponse($calendar->ReceiveData(json_encode($request, JSON_THROW_ON_ERROR)), 'ipsview.state');
                $navigated = $response['Payload']['State']['chart']['calendar'];
                assertGatewayGauge(
                    $response['Success'] && $navigated['windowOffset'] === 1 && $navigated['period'] === $period
                    && $navigated['endDate'] < $data['startDate'] && $calendar->GetTestProperties() === $properties,
                    'Authenticated, read-only navigation must follow the selected view in both outputs.'
                );
            }
            $form = json_decode($calendar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                findGaugeFormElement($form['elements'], 'Period')['type'] === 'Select'
                && findGaugeFormElement($form['elements'], 'AbsoluteStart')['type'] === 'SelectDate'
                && findGaugeFormElement($form['elements'], 'Month')['visible'] === ($period === 'month')
                && findGaugeFormElement($form['elements'], 'Quarter')['visible'] === ($period === 'quarter')
                && findGaugeFormElement($form['elements'], 'AbsoluteEnd')['visible'] === ($period === 'absolute'),
                'Native date-only controls and active-only fields must match the selected period.'
            );
        }
        $counterID = 88003;
        $GLOBALS['symconTestVariables'][$counterID] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 1234.0, 'Name' => 'Calendar view counter'];
        $GLOBALS['symconTestArchiveVariables'][$counterID] = ['AggregationType' => 1, 'LoggedValues' => [], 'AggregatedValues' => [
            ['TimeStamp' => strtotime('2026-10-15'), 'Avg' => 0.0, 'Min' => 100.0, 'Max' => 200.0],
            ['TimeStamp' => strtotime('2026-10-13'), 'Avg' => 12.5, 'Min' => 0.0, 'Max' => 100.0]
        ]];
        $calendar->SetTestProperty('SourceVariableID', $counterID);
        $calendar->SetTestProperty('Metric', 'consumption');
        foreach (['year', 'quarter', 'month', 'absolute'] as $period) {
            $calendar->SetTestProperty('Period', $period);
            assertGatewayGauge(
                array_column($model()['data'], 'value') === [12.5, 0.0],
                'Every view must retain native counter consumption including zero, never raw counter endpoints.'
            );
        }
        $calendar->SetTestProperty('SourceVariableID', $calendarID);
        $calendar->SetTestProperty('Metric', 'average');
        $calendar->SetTestProperty('Period', 'month');
        $calendar->SetTestProperty('Year', 2026);
        $calendar->SetTestProperty('Month', 10);
        $oldKey = $model()['viewKey'];
        $calendar->SetTestProperty('Month', 9);
        assertGatewayGauge($oldKey !== $model()['viewKey'], 'A changed configured month must invalidate an in-flight navigation response.');

        $calendar->SetTestProperty('Period', 'absolute');
        foreach ([['{}', $date(2026, 10, 16)], [$date(2025, 1, 1), $date(2026, 10, 16)],
            [$date(2026, 10, 16), $date(2026, 10, 16)], [$date(2026, 10, 10), $date(2026, 10, 20)]] as [$from, $until]) {
            $calendar->SetTestProperty('AbsoluteStart', $from);
            $calendar->SetTestProperty('AbsoluteEnd', $until);
            $before = count($queries);
            $calendar->ApplyChanges();
            assertGatewayGauge(
                $calendar->GetTestStatus() === 202 && count($queries) === $before
                && $bootstrap($calendar->GetVisualizationTile())['status'] === 'error',
                'Missing, oversized, reversed/empty and future date windows must fail before reading any archive.'
            );
        }
        $calendar->SetTestProperty('AbsoluteStart', $date(2026, 10, 15));
        $calendar->SetTestProperty('AbsoluteEnd', $date(2026, 10, 16));
        $calendar->ApplyChanges();
        $properties = $calendar->GetTestProperties();
        $status = $calendar->GetTestStatus();
        $before = count($queries);
        IPSModuleStrict::$ParentResponder = static function (): never
        {
            throw new LogicException('Calendar draft previews must never read archives.');
        };
        foreach (['month', 'quarter', 'absolute'] as $period) {
            $calendar->UpdateCalendarPeriodForm($period);
            $calendar->UpdateBarPreviewFromForm(json_encode(['Period' => $period, 'Year' => 2024, 'Month' => 2, 'Quarter' => 1,
                'AbsoluteStart'                                       => json_decode($date(2024, 12, 20)), 'AbsoluteEnd' => $date(2025, 1, 10)], JSON_THROW_ON_ERROR));
            foreach (array_slice($calendar->GetTestFormUpdates(), -2) as $image) {
                $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge(
                    $xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-period="' . $period . '"')
                    && str_contains($svg, 'data-start="2024-') && str_contains($svg, '<rect'),
                    'Both read-only draft previews must show actual selected days, not an annual placeholder.'
                );
            }
        }
        $calendar->UpdateBarPreviewFromForm('{"Period":"absolute","AbsoluteStart":{}}');
        foreach (array_slice($calendar->GetTestFormUpdates(), -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                !str_contains($svg, 'data-period=') && substr_count($svg, '<rect') <= 1,
                'Invalid date drafts must not paint calendar cells beyond the shared preview background.'
            );
        }
        assertGatewayGauge(
            $calendar->GetTestProperties() === $properties && $calendar->GetTestStatus() === $status && count($queries) === $before,
            'Period drafts must not save properties, alter status or contact the Gateway.'
        );
    } finally {
        unset($GLOBALS['symconTestVariables'][88003], $GLOBALS['symconTestArchiveVariables'][88003]);
        IPSModuleStrict::$ParentResponder = $parent;
        foreach ($saved as $name => $value) {
            $calendar->SetTestProperty($name, $value);
        }
        $calendar->ApplyChanges();
    }
    echo "Calendar month, quarter, fixed periods, previews and read-only navigation verified.\n";
})();
