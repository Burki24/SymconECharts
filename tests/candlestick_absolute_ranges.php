<?php

declare(strict_types=1);

// Uses the existing Candlestick fixture and real Gateway with Kernel doubles.
(static function (): void
{
    $previousZone = date_default_timezone_get();
    $previousParent = IPSModuleStrict::$ParentResponder;
    date_default_timezone_set('Europe/Berlin');
    $dateValue = static function (string $label): string
    {
        $d = new DateTimeImmutable($label);
        return json_encode(['year' => (int) $d->format('Y'), 'month' => (int) $d->format('n'),
            'day'                  => (int) $d->format('j'), 'hour' => (int) $d->format('G'),
            'minute'               => (int) $d->format('i'), 'second' => (int) $d->format('s')], JSON_THROW_ON_ERROR);
    };
    $queries = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($previousParent, &$queries): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.read') {
            $queries[] = $request['Payload'];
        }
        return $previousParent($json);
    };
    try {
        $GLOBALS['symconTestVariables'][99500] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
            'Value'                                              => 999999.0, 'Name' => 'Synthetic temperature', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
        $GLOBALS['symconTestArchiveVariables'][99500] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
        foreach ([['13:00:00', 900], ['12:00:00', 0], ['09:59:59', 3], ['09:30:00', 9],
            ['09:15:00', -4], ['09:00:00', 2], ['08:59:59', 900]] as [$time, $value]) {
            $GLOBALS['symconTestArchiveVariables'][99500]['LoggedValues'][] = [
                'TimeStamp' => strtotime('2026-10-12 ' . $time), 'Value' => $value, 'Duration' => 3600];
        }
        $chart = new TestableEChartsCandlestick();
        $chart->Create();
        $chart->now = strtotime('2026-10-20 12:34:00');
        $chart->SetTestProperty('VariableID', 99500);
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewRiseColor', 0x123456);
        $chart->SetTestProperty('Interval', 'hour');
        $chart->SetTestProperty('Range', 'absolute');
        $start = $dateValue('2026-10-12 08:30:00');
        $end = $dateValue('2026-10-12 13:30:00');
        $chart->SetTestProperty('AbsoluteStart', $start);
        $chart->SetTestProperty('AbsoluteEnd', $end);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Candlestick must accept fixed historical From/Until periods.');
        $model = static fn (): array => json_decode($chart->GetCandlestickData(), true, 512, JSON_THROW_ON_ERROR);
        $data = $model();
        assertGatewayGauge(array_column($data['candlestick']['data'], 'statistics') === [
            [2.0, 3.0, -4.0, 9.0], null, null, [0.0, 0.0, 0.0, 0.0]
        ], 'Historical candles use actual first/last/min/max samples, no predecessor, mean, current value or gap filling.');
        assertGatewayGauge($data['range'] === ['key' => 'absolute',
            'startTimestamp'                         => strtotime('2026-10-12 09:00:00'), 'endTimestamp' => strtotime('2026-10-12 13:00:00') - 1,
            'timezone'                               => 'Europe/Berlin', 'requestedStartTimestamp' => strtotime('2026-10-12 08:30:00'),
            'requestedEndTimestamp'                  => strtotime('2026-10-12 13:30:00')], 'Requested exclusive and effective inclusive boundaries must be explicit.');
        assertGatewayGauge(count($queries) === 1 && $queries[0]['Mode'] === 'raw' && $queries[0]['Limit'] === 2000
            && $queries[0]['Reducer'] === 'auto' && $queries[0]['StartTimestamp'] === $data['range']['startTimestamp']
            && $queries[0]['EndTimestamp'] === $data['range']['endTimestamp'], 'Historical windows must retain the bounded raw Gateway request.');
        $chart->now = strtotime('2026-11-01 12:34:00');
        assertGatewayGauge($model() === $data, 'Refreshing later must not shift historical candles.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                str_contains($html, '"requestedStartTimestamp":') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both output adapters must carry fixed historical metadata within their output budget.'
            );
        }
        assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), '"riseColor":"#123456"'), 'Independent IPSView design must remain unchanged.');
        $setPeriod = static function (string $from, string $until, string $interval = 'hour') use ($chart, $dateValue): void
        {
            $chart->SetTestProperty('Range', 'absolute');
            $chart->SetTestProperty('Interval', $interval);
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $chart->ApplyChanges();
        };
        $before = count($queries);
        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00', 'day');
        assertGatewayGauge($model()['candlestick']['data'] === [] && count($queries) === $before
            && $chart->GetTestStatus() === IS_ACTIVE, 'A valid window without a whole day is empty without archive queries.');
        $setPeriod('2026-10-11 08:30:00', '2026-10-14 13:30:00', 'day');
        $daily = $model();
        assertGatewayGauge(array_column($daily['candlestick']['data'], 'label') === ['2026-10-12', '2026-10-13']
            && $daily['candlestick']['data'][0]['statistics'] === [900.0, 900.0, -4.0, 900.0]
            && $daily['candlestick']['data'][1]['statistics'] === null, 'Only contained local days are summarized from raw samples.');
        foreach ([['2026-03-29', '2026-03-30', 23], ['2026-10-25', '2026-10-26', 25]] as [$from, $until, $hours]) {
            $setPeriod($from . ' 00:00:00', $until . ' 00:00:00', 'day');
            $dst = $model();
            assertGatewayGauge(
                count($dst['candlestick']['data']) === 1
                && $dst['range']['endTimestamp'] - $dst['range']['startTimestamp'] + 1 === $hours * 3600,
                'Historical calendar days retain 23/25-hour DST durations.'
            );
            $setPeriod($from . ' 00:00:00', $until . ' 00:00:00');
            $labels = array_column($model()['candlestick']['data'], 'label');
            assertGatewayGauge(
                count($labels) === $hours && count(array_unique($labels)) === $hours,
                'Elapsed hours remain distinct across DST, including repeated local times with offsets.'
            );
        }
        $setPeriod('2026-09-01 00:00:00', '2026-10-01 00:00:00');
        assertGatewayGauge(count($model()['candlestick']['data']) === 720
            && strlen($chart->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($chart->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'The existing 720-interval/output budgets are valid at their boundary.');
        $before = count($queries);
        $setPeriod('2026-09-01 00:00:00', '2026-10-01 01:00:00');
        assertGatewayGauge($chart->GetTestStatus() === 202 && count($queries) === $before
            && str_contains($chart->GetVisualizationTile(), 'Too many archive intervals'), '721 intervals fail before any archive request.');

        foreach ([['{}', $end], [$start, '{}'], [$end, $start], [$start, $start],
            [$start, $dateValue('2026-11-02 00:00:00')],
            [$dateValue('2026-03-29 02:30:00'), $end], [$dateValue('2026-10-25 02:30:00'), $end],
            ['{"year":2026,"month":2,"day":30,"hour":0,"minute":0,"second":0}', $end]] as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $from);
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $before = count($queries);
            $chart->ApplyChanges();
            $diagnostic = json_decode($chart->GetCandlestickDiagnostic(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $chart->GetTestStatus() === 202 && !$diagnostic['valid'] && count($queries) === $before
                && str_contains($chart->GetVisualizationTile(), $diagnostic['lastError']),
                'Missing, reversed, equal, future, impossible and ambiguous dates fail visibly without archive access.'
            );
        }
        date_default_timezone_set('Asia/Kathmandu');
        $setPeriod('2026-10-12 00:30:00', '2026-10-12 03:30:00');
        assertGatewayGauge(array_column($model()['candlestick']['data'], 'label') === [
            '2026-10-12 01:00 +05:45', '2026-10-12 02:00 +05:45'], 'Fixed non-integral UTC offsets remain valid.');
        date_default_timezone_set('Australia/Lord_Howe');
        $before = count($queries);
        $setPeriod('2026-10-04 00:00:00', '2026-10-04 04:00:00');
        assertGatewayGauge(
            $chart->GetTestStatus() === 202 && count($queries) === $before,
            'Non-hour offset changes must not invent whole hours.'
        );
        date_default_timezone_set('Europe/Berlin');
        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00');
        $chart->SetTestProperty('CustomRangeValue', 0);
        $chart->SetTestProperty('CustomRangeUnit', 'invalid');
        assertGatewayGauge($model() === $data, 'Inactive custom settings must not affect absolute periods.');
        $chart->SetTestProperty('CustomRangeValue', 7);
        $chart->SetTestProperty('CustomRangeUnit', 'day');
        foreach (['24h', '7d', '30d', 'custom'] as $range) {
            $chart->SetTestProperty('Range', $range);
            $chart->SetTestProperty('AbsoluteStart', 'invalid');
            $chart->SetTestProperty('AbsoluteEnd', '{}');
            $chart->ApplyChanges();
            $legacy = $model();
            $expected = SymconECharts\CandlestickData::Window($range, 'hour', $chart->now, 'Europe/Berlin');
            assertGatewayGauge(
                $legacy['range'] === ['key' => $range, 'startTimestamp' => $expected['StartTimestamp'],
                    'endTimestamp'          => $expected['EndTimestamp'], 'timezone' => 'Europe/Berlin']
                && count($legacy['candlestick']['data']) === count($expected['Intervals']),
                'Legacy presets/custom periods retain their exact model and ignore inactive date fields.'
            );
        }

        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00');
        $saved = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $before = count($queries);
        IPSModuleStrict::$ParentResponder = static function (): never
        {
            throw new LogicException('Preview must never contact the Gateway.');
        };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            assertGatewayGauge($field['type'] === 'SelectDateTime' && $field['visible']
                && str_contains($field['onChange'], 'ECCA_UpdateBarPreviewFromForm'), 'Native date controls update both previews.');
        }
        assertGatewayGauge(
            str_contains(findGaugeFormElement($form['elements'], 'AbsoluteTimeZone')['caption'], 'Europe/Berlin'),
            'The form identifies the installation timezone.'
        );
        $chart->UpdateTimeRangeForm('absolute');
        assertGatewayGauge(
            array_column(array_slice($chart->GetTestFormUpdates(), -6), 'Value') === [true, true, true, true, false, false],
            'Date controls and relative duration controls switch without persistence.'
        );
        foreach (['horizontal', 'vertical'] as $orientation) {
            $chart->UpdateBarPreviewFromForm(json_encode(['Range' => 'absolute', 'AbsoluteStart' => json_decode($start),
                'AbsoluteEnd'                                     => $end, 'Title' => 'Unsaved <historical>', 'Orientation' => $orientation,
                'IPSViewOrientation'                              => $orientation], JSON_THROW_ON_ERROR));
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge(
                    $xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-range="absolute"')
                    && str_contains($svg, '2026-10-12 08:30:00 +02:00') && str_contains($svg, 'Example data')
                    && str_contains($svg, 'Unsaved &lt;historical&gt;') && str_contains($svg, 'data-candle="0"'),
                    'Both synthetic SVG previews accept draft date objects/strings and retain escaping, timezone and orientation.'
                );
            }
        }
        $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":{}}');
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                str_contains($svg, 'historical date and time selection is invalid') && !str_contains($svg, 'data-candle='),
                'Invalid drafts show a date error instead of plausible candles.'
            );
        }
        assertGatewayGauge(
            $chart->GetTestProperties() === $saved && $chart->GetTestStatus() === $status && count($queries) === $before,
            'Forms and draft previews are pure: no writes, queries or status changes.'
        );
        IPSModuleStrict::$ParentResponder = $previousParent;

        // A dense but permitted raw window must be subdivided, never aggregated/truncated.
        foreach ([99501 => 1, 99502 => 0] as $id => $type) {
            $GLOBALS['symconTestVariables'][$id] = $GLOBALS['symconTestVariables'][99500];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $type, 'LoggedValues' => [], 'AggregatedValues' => []];
        }
        $denseStart = strtotime('2026-10-11 00:00:00');
        for ($i = 9999; $i >= 0; --$i) {
            $GLOBALS['symconTestArchiveVariables'][99502]['LoggedValues'][] = ['TimeStamp' => $denseStart + $i, 'Value' => $i];
        }
        $queries = [];
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($previousParent, &$queries): string
        {
            $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $queries[] = $request['Payload'];
            return $previousParent($json);
        };
        $chart->SetTestProperty('VariableID', 99502);
        $setPeriod('2026-10-11 00:00:00', '2026-10-11 03:00:00');
        $dense = $model()['candlestick']['data'];
        assertGatewayGauge(array_sum(array_column($dense, 'count')) === 10000
            && $dense[0]['statistics'] === [0.0, 3599.0, 0.0, 3599.0]
            && $dense[2]['statistics'] === [7200.0, 9999.0, 7200.0, 9999.0]
            && count($queries) > 1 && count($queries) <= 63 && array_unique(array_column($queries, 'Limit')) === [2000]
            && array_unique(array_column($queries, 'Mode')) === ['raw'], 'Historical bisection preserves all raw samples and OCLH endpoints under the existing budgets.');
        $chart->SetTestProperty('VariableID', 99501);
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 204
            && str_contains($chart->GetVisualizationTile(), 'Candlestick requires standard archive variables, not counters.'),
            'Historical mode must still reject counter archives.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        date_default_timezone_set($previousZone);
    }
    echo "Fixed historical Candlestick periods verified.\n";
})();
