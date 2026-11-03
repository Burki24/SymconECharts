<?php

declare(strict_types=1);

// Real module/Gateway with synthetic Kernel archives and the existing test fixture.
(static function (): void
{
    $previousZone = date_default_timezone_get();
    $previousParent = IPSModuleStrict::$ParentResponder;
    date_default_timezone_set('Europe/Berlin');
    $dateValue = static function (string $label): string
    {
        // Preserve wall-clock fields, including invalid/ambiguous local DST selections.
        $d = new DateTimeImmutable($label, new DateTimeZone('UTC'));
        return json_encode(['year' => (int) $d->format('Y'), 'month' => (int) $d->format('n'),
            'day'                  => (int) $d->format('j'), 'hour' => (int) $d->format('G'),
            'minute'               => (int) $d->format('i'), 'second' => (int) $d->format('s')], JSON_THROW_ON_ERROR);
    };
    $requests = [];
    $responder = static function (string $json) use ($previousParent, &$requests): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.states.read') {
            $requests[] = $request['Payload'];
        }
        return $previousParent($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    try {
        $start = strtotime('2026-10-12 08:30:17');
        $end = strtotime('2026-10-12 09:45:29');
        $sources = [];
        foreach (range(0, 3) as $index) {
            $id = 99700 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Same source', 'States' => [
                ['Value' => 0, 'Label' => 'Off <safe>', 'Color' => 0x22AA44],
                ['Value' => 1, 'Label' => 'On & known', 'Color' => 0xAA2233]]];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => $index === 3 ? 1 : 0,
                'VariableUpdated'                                  => 1780000000, 'Value' => $index === 3 ? 999 : false, 'Name' => 'Synthetic state'];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => []];
        }
        $GLOBALS['symconTestArchiveVariables'][99700]['LoggedValues'] = [
            ['TimeStamp' => $end + 1, 'Value' => true], ['TimeStamp'      => $end, 'Value' => true],
            ['TimeStamp' => $start + 120, 'Value' => false], ['TimeStamp' => $start + 60, 'Value' => false],
            ['TimeStamp' => $start - 1, 'Value' => true]];
        $GLOBALS['symconTestArchiveVariables'][99701]['LoggedValues'] = [
            ['TimeStamp' => $start + 60, 'Value' => true]];
        $GLOBALS['symconTestArchiveVariables'][99703]['LoggedValues'] = [
            ['TimeStamp' => $end, 'Value' => -2], ['TimeStamp' => $start, 'Value' => 0], ['TimeStamp' => $start - 1, 'Value' => 1]];
        $chart = new TestableEChartsStateTimeline();
        $chart->Create();
        $chart->now = strtotime('2026-10-20 12:34:00');
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewRowHeightPercent', 37);
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', $dateValue('2026-10-12 08:30:17'));
        $chart->SetTestProperty('AbsoluteEnd', $dateValue('2026-10-12 09:45:29'));
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'StateTimeline must accept exact fixed historical From/Until periods.');
        $model = static fn (): array => json_decode($chart->GetStateTimelineData(), true, 512, JSON_THROW_ON_ERROR);
        $data = $model();
        $segments = $data['stateTimeline']['rows'][0]['segments'];
        assertGatewayGauge(
            array_column($segments, 'value') === [1, 0]
            && array_column($segments, 'startTimestamp') === [$start, $start + 60]
            && array_column($segments, 'endTimestamp') === [$start + 60, $end]
            && $segments[0]['label'] === 'On & known' && $segments[0]['color'] === '#AA2233',
            'A historical predecessor must carry into exact boundaries; repeated values merge and a right-edge event creates no zero-width state.'
        );
        assertGatewayGauge(
            array_column($data['stateTimeline']['rows'][1]['segments'], 'value') === [null, 1]
            && array_column($data['stateTimeline']['rows'][2]['segments'], 'value') === [null]
            && array_column($data['stateTimeline']['rows'][3]['segments'], 'value') === [0],
            'Unknown beginnings remain unknown, current values are ignored and a left-edge event supersedes its predecessor.'
        );
        assertGatewayGauge(
            $data['range'] === ['key' => 'absolute', 'startTimestamp' => $start, 'endTimestamp' => $end,
                'timezone'            => 'Europe/Berlin', 'requestedStartTimestamp' => $start, 'requestedEndTimestamp' => $end],
            'Historical state windows retain exact requested seconds, with the existing exclusive segment end.'
        );
        foreach ($requests as $request) {
            assertGatewayGauge(
                $request['StartTimestamp'] === $start && $request['EndTimestamp'] === $end && $request['Limit'] === 2000,
                'Raw state queries and predecessor requests retain the existing protocol and point budget.'
            );
        }
        $chart->now = strtotime('2026-11-01 12:34:00');
        assertGatewayGauge($model() === $data, 'A later refresh must not move historical state boundaries or replace archive states.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                str_contains($html, '"requestedStartTimestamp":' . $start) && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both bounded outputs must carry the same exact historical period.'
            );
        }
        assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), '"rowHeightPercent":37'), 'Independent IPSView design remains unchanged.');
        $setPeriod = static function (string $from, string $until) use ($chart, $dateValue): void
        {
            $chart->SetTestProperty('Range', 'absolute');
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $chart->ApplyChanges();
        };
        $setPeriod('2026-10-12 08:30:17', '2026-10-12 08:30:18');
        assertGatewayGauge($model()['stateTimeline']['rows'][0]['segments'][0]['endTimestamp'] === $start + 1
            && $model()['stateTimeline']['rows'][0]['segments'][0]['value'] === 1, 'Even a one-second window retains its predecessor and full duration without rounding.');
        foreach ([['2026-03-29 00:00:00', '2026-03-30 00:00:00', 23 * 3600],
            ['2026-10-25 00:00:00', '2026-10-26 00:00:00', 25 * 3600]] as [$from, $until, $seconds]) {
            $setPeriod($from, $until);
            $dst = $model();
            assertGatewayGauge(
                $dst['range']['endTimestamp'] - $dst['range']['startTimestamp'] === $seconds,
                'Exact historical states preserve elapsed DST duration, not rounded 24-hour days.'
            );
        }
        date_default_timezone_set('Australia/Lord_Howe');
        $setPeriod('2026-10-04 01:45:17', '2026-10-04 03:00:29');
        assertGatewayGauge(
            $model()['range']['endTimestamp'] - $model()['range']['startTimestamp'] === 2712,
            'Exact states need no hourly aggregation restriction across half-hour offset changes.'
        );
        date_default_timezone_set('Europe/Berlin');
        foreach ([['{}', $dateValue('2026-10-12 09:45:29')],
            [$dateValue('2026-10-12 08:30:17'), '{}'],
            [$dateValue('2026-10-12 09:45:29'), $dateValue('2026-10-12 08:30:17')],
            [$dateValue('2026-10-12 08:30:17'), $dateValue('2026-10-12 08:30:17')],
            [$dateValue('2026-10-12 08:30:17'), $dateValue('2026-11-02 00:00:00')],
            [$dateValue('2026-03-29 02:30:00'), $dateValue('2026-10-12 09:45:29')],
            [$dateValue('2026-10-25 02:30:00'), $dateValue('2026-10-26 00:00:00')],
            ['{"year":2026,"month":2,"day":30,"hour":0,"minute":0,"second":0}', $dateValue('2026-10-12 09:45:29')]] as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $from);
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $before = count($requests);
            $chart->ApplyChanges();
            $diagnostic = json_decode($chart->GetStateTimelineDiagnostic(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge($chart->GetTestStatus() === 202 && !$diagnostic['valid'] && count($requests) === $before
                && str_contains($chart->GetVisualizationTile(), $diagnostic['lastError']), 'Invalid dates stop refresh and fail visibly before raw or predecessor reads: '
                    . json_encode([$chart->GetTestStatus(), $diagnostic, $before, count($requests)], JSON_THROW_ON_ERROR));
        }
        $setPeriod('2026-10-12 08:30:17', '2026-10-12 09:45:29');
        $chart->SetTestProperty('CustomRangeValue', 0);
        $chart->SetTestProperty('CustomRangeUnit', 'invalid');
        assertGatewayGauge($model() === $data, 'Absolute periods ignore inactive custom duration fields.');
        $chart->SetTestProperty('CustomRangeValue', 90);
        $chart->SetTestProperty('CustomRangeUnit', 'minute');
        foreach (['24h' => 86400, '7d' => 604800, '30d' => 2592000, 'custom' => 5400] as $range => $seconds) {
            $chart->SetTestProperty('Range', $range);
            $chart->SetTestProperty('AbsoluteStart', 'invalid');
            $chart->SetTestProperty('AbsoluteEnd', '{}');
            $chart->ApplyChanges();
            assertGatewayGauge($model()['range'] === ['key' => $range, 'startTimestamp' => $chart->now - $seconds,
                'endTimestamp'                              => $chart->now, 'timezone' => 'Europe/Berlin'], 'Legacy presets/custom retain exact model shape and ignore inactive dates.');
        }
        $setPeriod('2026-10-12 08:30:17', '2026-10-12 09:45:29');
        $saved = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $before = count($requests);
        IPSModuleStrict::$ParentResponder = static function (): never
        {
            throw new LogicException('Date drafts must never contact the Gateway.');
        };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            assertGatewayGauge($field['type'] === 'SelectDateTime' && $field['visible']
                && str_contains($field['onChange'], 'ECST_UpdateBarPreviewFromForm'), 'Native date fields must update both previews.');
        }
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'AbsoluteTimeZone')['caption'], 'Europe/Berlin'), 'The form discloses the installation timezone.');
        $chart->UpdateTimeRangeForm('absolute');
        assertGatewayGauge(array_column(array_slice($chart->GetTestFormUpdates(), -6), 'Value') === [true, true, true, true, false, false], 'Unsaved time mode changes switch only form visibility.');
        $chart->UpdateBarPreviewFromForm(json_encode(['Range' => 'absolute', 'AbsoluteStart' => json_decode($dateValue('2026-10-12 08:30:17')),
            'AbsoluteEnd'                                     => $dateValue('2026-10-12 09:45:29'), 'Title' => 'Unsaved <historical>',
            'TimeLabelFormat'                                 => 'time', 'IPSViewTimeLabelFormat' => 'time'], JSON_THROW_ON_ERROR));
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-range="absolute"')
                && str_contains($svg, '2026-10-12 08:30:17 +02:00') && str_contains($svg, 'data-state-interval="true"')
                && str_contains($svg, 'Unsaved &lt;historical&gt;') && str_contains($svg, '>08:30</text>')
                && str_contains($svg, '>09:45</text>') && str_contains($svg, 'Example data'), 'Both previews use exact requested dates for metadata and the time axis, with only synthetic states.');
        }
        $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":{}}');
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(str_contains($svg, 'historical date and time selection is invalid') && !str_contains($svg, 'data-state-interval='), 'Invalid dates show a hint instead of invented state geometry.');
        }
        assertGatewayGauge(
            $chart->GetTestProperties() === $saved && $chart->GetTestStatus() === $status && count($requests) === $before,
            'Opening forms and editing date drafts cannot write properties, query archives or change status.'
        );
        IPSModuleStrict::$ParentResponder = $responder;

        $manySources = [];
        foreach (range(0, 31) as $index) {
            $id = 99720 + $index;
            $manySources[] = ['VariableID' => $id, 'Label' => 'Same contact', 'States' => []];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 0, 'VariableUpdated' => 1780000000, 'Value' => false, 'Name' => 'Synthetic budget contact'];
            $logged = [['TimeStamp' => $start - 1, 'Value' => false]];
            for ($i = 0; $i < 500; ++$i) {
                array_unshift($logged, ['TimeStamp' => $start + $i, 'Value' => true]);
            }
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => $logged];
        }
        $chart->SetTestProperty('Sources', json_encode($manySources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $requests = [];
        $stressJSON = $chart->GetStateTimelineData();
        $stress = json_decode($stressJSON, true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(count($stress['stateTimeline']['rows']) === 32 && count($requests) === 32
            && array_slice(array_column($requests, 'Limit'), -3) === [1500, 1000, 500]
            && strlen($stressJSON) < 250000 && count($chart->GetTestReferences()) === 32, 'All 32 historical sources fit within 16000 raw points and the existing encoded-output budget.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetConfigurationForm()] as $output) {
            assertGatewayGauge(strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both outputs and the 32-source preview remain bounded.');
        }
        foreach (['truncated', 'oversized', 'previous', 'conflict'] as $failure) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder, $failure, $start): string
            {
                $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
                if (($response['Payload']['VariableID'] ?? null) === 99751) {
                    if ($failure === 'truncated') {
                        $response['Payload']['Truncated'] = true;
                    } elseif ($failure === 'oversized') {
                        $response['Payload']['Points'][] = [$start + 500, 1];
                    } elseif ($failure === 'previous') {
                        $response['Payload']['PreviousPoint'] = [$start, 0];
                    } else {
                        $response['Payload']['Points'] = [[$start, 0], [$start, 1]];
                    }
                }
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            assertGatewayGauge(
                str_contains($chart->GetVisualizationTile(), '"status":"error"') && $chart->GetTestStatus() === 204,
                'Invalid final-source histories cannot escape budgets or return partial models: ' . $failure
            );
        }
        IPSModuleStrict::$ParentResponder = $responder;
        assertGatewayGauge($model() === $stress && $chart->GetTestStatus() === IS_ACTIVE, 'Valid historical responses recover without changing mappings or design.');
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        date_default_timezone_set($previousZone);
    }
    echo "Fixed historical StateTimeline periods verified.\n";
})();
