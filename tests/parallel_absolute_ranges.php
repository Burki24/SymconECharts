<?php

declare(strict_types=1);

// Isolated route in gateway_gauges.php: real module, planner, pairing and Gateway.
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
        $sources = [];
        foreach (range(0, 10) as $index) {
            $id = 99400 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Same name'];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => 999999.0, 'Name' => 'Synthetic axis',
                'Presentation'                                     => ['SUFFIX' => [' °C', ' %', ' kWh'][$index % 3], 'DIGITS' => 1]];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index === 2 ? 1 : 0,
                'LoggedValues'                                               => [], 'AggregatedValues' => []];
        }
        foreach ([[8 => 900.0, 9 => -4.0, 10 => 0.0, 11 => 4.0, 12 => 8.0, 13 => 900.0],
            [8 => 800.0, 9 => 100.0, 11 => 0.0, 12 => 12.0, 13 => 800.0],
            [8 => 700.0, 9 => 1.0, 10 => 2.0, 11 => 3.0, 12 => 0.0, 13 => 700.0]] as $index => $values) {
            foreach (array_reverse($values, true) as $hour => $value) {
                $GLOBALS['symconTestArchiveVariables'][99400 + $index]['AggregatedValues'][] = [
                    'TimeStamp' => strtotime('2026-10-12 ' . $hour . ':00:00'), 'Avg' => $value,
                    'Min'       => -9999.0, 'Max' => 9999.0];
            }
        }
        $chart = new TestableEChartsParallel();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(strtotime('2026-10-20 12:34:00'));
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 3), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewLineColor', 0x123456);
        $chart->SetTestProperty('Range', 'absolute');
        $start = $dateValue('2026-10-12 08:30:00');
        $end = $dateValue('2026-10-12 13:30:00');
        $chart->SetTestProperty('AbsoluteStart', $start);
        $chart->SetTestProperty('AbsoluteEnd', $end);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Parallel must accept a fixed historical From/Until period.');
        $queries = [];
        $data = json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge($data['parallel']['rows'] === [
            ['timestamp' => strtotime('2026-10-12 09:00:00'), 'values' => [-4.0, 100.0, 1.0]],
            ['timestamp' => strtotime('2026-10-12 11:00:00'), 'values' => [4.0, 0.0, 3.0]],
            ['timestamp' => strtotime('2026-10-12 12:00:00'), 'values' => [8.0, 12.0, 0.0]]
        ], 'Fixed periods retain exact common timestamps, averages/counter sums, zeros, negatives and gaps without interpolation.');
        assertGatewayGauge($data['range']['startTimestamp'] === strtotime('2026-10-12 09:00:00')
            && $data['range']['endTimestamp'] === strtotime('2026-10-12 13:00:00') - 1
            && $data['range']['requestedStartTimestamp'] === strtotime('2026-10-12 08:30:00')
            && $data['range']['requestedEndTimestamp'] === strtotime('2026-10-12 13:30:00')
            && $data['range']['timezone'] === 'Europe/Berlin', 'Requested and effective historical boundaries must be explicit.');
        assertGatewayGauge(count($queries) === 3 && array_column($queries, 'Limit') === [750, 750, 750]
            && array_unique(array_column($queries, 'Reducer')) === ['auto'], 'Fixed ranges must preserve the per-axis query limit and automatic archive reducer.');
        $chart->SetTestCurrentTimestamp(strtotime('2026-11-01 12:34:00'));
        $again = json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $again['range'] === $data['range'] && $again['parallel']['rows'] === $data['parallel']['rows'],
            'Refreshing later must not shift historical boundaries or replace archive values with current values.'
        );
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                str_contains($html, '"requestedStartTimestamp":') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both output adapters must embed the fixed historical model within their existing output budget.'
            );
        }
        assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), '"lineColor":"#123456"'), 'Independent IPSView design must remain intact.');
        $model = static fn (): array => json_decode($chart->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        $setPeriod = static function (string $from, string $until, string $aggregation = 'hour') use ($chart, $dateValue): void
        {
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $chart->SetTestProperty('Aggregation', $aggregation);
        };
        $chart->SetTestProperty('LineBudget', 1);
        $queries = [];
        $limited = $model();
        assertGatewayGauge(count($limited['parallel']['rows']) === 1 && $limited['parallel']['truncated']
            && $limited['parallel']['rows'][0]['timestamp'] === strtotime('2026-10-12 12:00:00')
            && array_column($queries, 'Limit') === [1, 1, 1], 'The unchanged line budget keeps the newest common row and reports truncation.');
        $chart->SetTestProperty('LineBudget', 750);
        foreach ([['2026-10-12 09:15:00', '2026-10-12 09:45:00', 'hour'],
            ['2026-10-12 08:00:00', '2026-10-13 08:00:00', 'day']] as [$from, $until, $aggregation]) {
            $setPeriod($from, $until, $aggregation);
            $queries = [];
            $empty = $model();
            assertGatewayGauge($empty['parallel']['rows'] === [] && $queries === []
                && !$empty['parallel']['truncated'], 'A range without a whole interval stays empty without archive reads or synthetic zero rows.');
        }
        $chart->SetTestCurrentTimestamp(strtotime('2026-10-20 12:34:00'));
        foreach (['hour' => '2026-10-20 12:00:00', 'day' => '2026-10-20 00:00:00'] as $aggregation => $boundary) {
            $setPeriod('2026-10-19 00:00:00', '2026-10-20 12:34:00', $aggregation);
            assertGatewayGauge(
                $model()['range']['endTimestamp'] === strtotime($boundary) - 1,
                'An end equal to now excludes the running hour/day instead of reading partial intervals.'
            );
        }
        $setPeriod('2026-10-12 08:30:00', '2026-10-15 13:30:00', 'day');
        foreach (range(0, 2) as $index) {
            $GLOBALS['symconTestArchiveVariables'][99400 + $index]['AggregatedValues'] = [
                ['TimeStamp' => strtotime('2026-10-15 00:00:00'), 'Avg' => 999.0],
                ['TimeStamp' => strtotime('2026-10-14 00:00:00'), 'Avg' => 20.0 + $index],
                ['TimeStamp' => strtotime('2026-10-13 00:00:00'), 'Avg' => 10.0 + $index],
                ['TimeStamp' => strtotime('2026-10-12 00:00:00'), 'Avg' => 999.0]];
        }
        $queries = [];
        $daily = $model();
        assertGatewayGauge(
            array_column($daily['parallel']['rows'], 'timestamp')
            === [strtotime('2026-10-13 00:00:00'), strtotime('2026-10-14 00:00:00')]
            && $daily['parallel']['rows'][0]['values'] === [10.0, 11.0, 12.0]
            && $daily['range']['aggregationLevel'] === 1
            && $daily['range']['endTimestamp'] === strtotime('2026-10-15 00:00:00') - 1,
            'Daily fixed periods include only fully contained local calendar days, retaining mixed units and counter sums.'
        );
        $chart->SetTestCurrentTimestamp(strtotime('2026-11-01 12:34:00'));
        foreach ([['2026-03-29', 23, 3], ['2026-10-25', 25, 5]] as [$day, $dayHours, $windowHours]) {
            $from = $day . ' 00:00:00';
            $until = (new DateTimeImmutable($from))->modify('+1 day')->format('Y-m-d H:i:s');
            foreach (['hour', 'day'] as $aggregation) {
                $endLabel = $aggregation === 'hour' ? $day . ' 04:00:00' : $until;
                $setPeriod($from, $endLabel, $aggregation);
                $timestamps = $aggregation === 'day' ? [strtotime($from)]
                    : array_map(static fn (int $hour): int => strtotime($from) + $hour * 3600, range(0, $windowHours - 1));
                foreach (range(0, 2) as $index) {
                    $GLOBALS['symconTestArchiveVariables'][99400 + $index]['AggregatedValues'] = array_reverse(array_map(
                        static fn (int $t): array => ['TimeStamp' => $t, 'Avg' => (float) ($index * 10)],
                        $timestamps
                    ));
                }
                $dst = $model();
                assertGatewayGauge(
                    array_column($dst['parallel']['rows'], 'timestamp') === $timestamps
                    && $dst['range']['endTimestamp'] - $dst['range']['startTimestamp'] + 1
                    === ($aggregation === 'day' ? $dayHours : $windowHours) * 3600,
                    'DST transitions preserve 23/25-hour calendar days and every distinct actual shared hour.'
                );
            }
        }
        date_default_timezone_set('Asia/Kathmandu');
        $setPeriod('2026-10-16 08:30:00', '2026-10-16 13:30:00');
        $quarterOffset = $model()['range'];
        assertGatewayGauge(
            $quarterOffset['timezone'] === 'Asia/Kathmandu'
            && $quarterOffset['startTimestamp'] === strtotime('2026-10-16 09:00:00')
            && $quarterOffset['endTimestamp'] === strtotime('2026-10-16 13:00:00') - 1,
            'The Symcon installation timezone, including quarter-hour offsets, defines interval boundaries.'
        );
        date_default_timezone_set('Australia/Lord_Howe');
        $setPeriod('2026-10-04 00:00:00', '2026-10-04 04:00:00');
        $queries = [];
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 202 && $queries === [],
            'The shared planner must reject unsupported non-hour transitions before reading archives.'
        );
        date_default_timezone_set('Europe/Berlin');
        foreach ([['{}', $end], [$start, '{}'], [$end, $start], [$start, $start],
            [$start, $dateValue('2026-11-02 00:00:00')],
            ['{"year":2026,"month":3,"day":29,"hour":2,"minute":30,"second":0}', $end],
            ['{"year":2026,"month":10,"day":25,"hour":2,"minute":30,"second":0}', $end],
            ['{"year":2026,"month":2,"day":30,"hour":8,"minute":0,"second":0}', $end],
            [str_replace('"year":2026', '"year":"2026"', $start), $end]] as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $from);
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $queries = [];
            $before = $chart->GetTestProperties();
            $chart->ApplyChanges();
            assertGatewayGauge(
                $chart->GetTestStatus() === 202 && $queries === [] && $chart->GetTestProperties() === $before,
                'Unset, reversed, equal, future, nonexistent, ambiguous and malformed dates must fail without reads or configuration writes.'
            );
            assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), '"status":"error"'), 'Invalid fixed periods must produce an explicit error output.');
        }
        $chart->SetTestProperty('AbsoluteStart', 'broken');
        $chart->SetTestProperty('AbsoluteEnd', '{}');
        foreach (['24h' => 86400, '7d' => 604800, '30d' => 2592000, 'custom' => 604800] as $key => $duration) {
            foreach (['hour', 'day'] as $aggregation) {
                $chart->SetTestProperty('Range', $key);
                $chart->SetTestProperty('Aggregation', $aggregation);
                $chart->ApplyChanges();
                $legacy = $model()['range'];
                assertGatewayGauge(
                    $chart->GetTestStatus() === IS_ACTIVE
                    && array_keys($legacy) === ['key', 'startTimestamp', 'endTimestamp', 'aggregationLevel', 'lineBudget']
                    && $legacy['endTimestamp'] - $legacy['startTimestamp'] + 1 === $duration,
                    'Legacy presets/custom intervals keep their exact model shape and elapsed duration, ignoring inactive dates.'
                );
            }
        }
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', $start);
        $chart->SetTestProperty('AbsoluteEnd', $end);
        $chart->SetTestProperty('Aggregation', 'hour');
        $chart->SetTestProperty('CustomRangeValue', 0);
        $chart->SetTestProperty('CustomRangeUnit', 'invalid');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Inactive custom fields must not break valid absolute ranges.');
        $properties = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $archiveCount = $GLOBALS['symconTestArchiveQueryCount'];
        $formParent = IPSModuleStrict::$ParentResponder;
        IPSModuleStrict::$ParentResponder = static function (): string
        { throw new RuntimeException('Draft forms must not contact the Gateway.'); };
        try {
            $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
                $field = findGaugeFormElement($form['elements'], $name);
                assertGatewayGauge($field['visible'] && $field['type'] === 'SelectDateTime'
                    && str_contains($field['onChange'], 'UpdateBarPreviewFromForm'), 'Both native dates must be visible and immediately update draft previews.');
            }
            assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'AbsoluteTimeZone')['caption'], 'Europe/Berlin')
                && !findGaugeFormElement($form['elements'], 'CustomRangeValue')['visible'], 'The form must explain the installation timezone and hide inactive custom duration fields.');
            $chart->UpdateTimeRangeForm('24h');
            assertGatewayGauge(
                array_column(array_slice($chart->GetTestFormUpdates(), -6), 'Value') === array_fill(0, 6, false),
                'Draft visibility changes hide inactive date and custom fields without persisting anything.'
            );
            foreach ([$start, json_decode($start, true, 512, JSON_THROW_ON_ERROR)] as $draftStart) {
                $chart->UpdateBarPreviewFromForm(json_encode(['Range' => 'absolute', 'AbsoluteStart' => $draftStart,
                    'AbsoluteEnd'                                     => $end, 'Aggregation' => 'day'], JSON_THROW_ON_ERROR));
                foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                    $xml = new DOMDocument();
                    assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-range="absolute"')
                        && str_contains($svg, 'Europe/Berlin') && str_contains($svg, 'Example data'), 'Both synthetic previews must accept unsaved JSON/object dates and disclose the requested period.');
                }
            }
            $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":"{}","AbsoluteEnd":"{}"}');
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(!str_contains($svg, '<polyline') && str_contains($svg, 'historical date'), 'Invalid drafts must explain the date problem rather than draw plausible lines.');
            }
            assertGatewayGauge($properties === $chart->GetTestProperties() && $status === $chart->GetTestStatus()
                && $archiveCount === $GLOBALS['symconTestArchiveQueryCount'], 'Form generation and drafts must not change properties, status or archives.');
        } finally {
            IPSModuleStrict::$ParentResponder = $formParent;
        }
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 10), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('LineBudget', 1000);
        $setPeriod('2026-09-01 00:00:00', '2026-10-20 00:00:00');
        foreach (range(0, 9) as $index) {
            $GLOBALS['symconTestArchiveVariables'][99400 + $index]['AggregatedValues'] = array_reverse(array_map(
                static fn (int $hour): array => ['TimeStamp' => strtotime('2026-09-01 00:00:00') + $hour * 3600,
                    'Avg'                                    => (float) ($hour + $index)],
                range(0, 1000)
            ));
        }
        $queries = [];
        $large = $model();
        assertGatewayGauge(
            count($queries) === 10 && array_column($queries, 'Limit') === array_fill(0, 10, 1000)
            && count($large['parallel']['axes']) === 10 && count($large['parallel']['rows']) === 1000
            && $large['parallel']['truncated'] && $large['parallel']['rows'][0]['timestamp'] === strtotime('2026-09-01 01:00:00'),
            'Ten axes retain at most 1000 aggregates each (10000 jointly), the newest 1000 common rows and the existing truncation warning.'
        );
        foreach ([0, 1001] as $invalidBudget) {
            $chart->SetTestProperty('LineBudget', $invalidBudget);
            $queries = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202 && $queries === [], 'Absolute periods do not widen the line budget.');
        }
        $chart->SetTestProperty('LineBudget', 1000);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $queries = [];
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 201 && $queries === [], 'Absolute periods do not widen the ten-axis bound.');
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        date_default_timezone_set($previousZone);
        foreach (range(99400, 99410) as $id) {
            unset($GLOBALS['symconTestVariables'][$id], $GLOBALS['symconTestArchiveVariables'][$id]);
        }
    }
})();

echo "Parallel fixed historical periods, common intervals and unchanged budgets verified.\n";
