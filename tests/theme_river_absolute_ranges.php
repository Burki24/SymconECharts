<?php

declare(strict_types=1);

// Uses the existing ThemeRiver fixture, real Gateway and synthetic Kernel archives.
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
    $responder = static function (string $json) use ($previousParent, &$queries): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.read') {
            $queries[] = $request['Payload'];
        }
        return $previousParent($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    try {
        $sources = [];
        foreach (range(0, 15) as $index) {
            $id = 99600 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Same synthetic source', 'InitiallyVisible' => $index === 0];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => 999999.0, 'Name' => 'Synthetic archive source', 'Presentation' => ['SUFFIX' => ' kWh', 'DIGITS' => 2]];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
        }
        foreach ([[8 => 900.0, 9 => 0.0, 10 => 2.0, 11 => 4.0, 12 => 8.0, 13 => 900.0],
            [8 => 800.0, 9 => 1.0, 11 => 0.0, 12 => 12.0, 13 => 800.0]] as $index => $values) {
            foreach (array_reverse($values, true) as $hour => $value) {
                $GLOBALS['symconTestArchiveVariables'][99600 + $index]['AggregatedValues'][] = [
                    'TimeStamp' => strtotime('2026-10-12 ' . $hour . ':00:00'), 'Avg' => $value, 'Min' => -9999.0, 'Max' => 9999.0];
            }
        }
        $chart = new TestableConsumptionThemeRiver();
        $chart->Create();
        $chart->now = strtotime('2026-10-20 12:34:00');
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 2), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('EnableBandSelection', true);
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewBandOpacityPercent', 37);
        $chart->SetTestProperty('Range', 'absolute');
        $start = $dateValue('2026-10-12 08:30:00');
        $end = $dateValue('2026-10-12 13:30:00');
        $chart->SetTestProperty('AbsoluteStart', $start);
        $chart->SetTestProperty('AbsoluteEnd', $end);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'ThemeRiver must accept fixed historical From/Until periods.');
        $model = static fn (): array => json_decode($chart->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
        $data = $model();
        assertGatewayGauge(
            array_column($data['themeRiver']['rows'], 'values') === [[0.0, 1.0], null, [4.0, 0.0], [8.0, 12.0]]
            && $data['themeRiver']['missingIntervals'] === 1 && !$data['themeRiver']['sources'][1]['initiallyVisible'],
            'Hidden-source gaps stay shared gaps; true zeros remain values, never interpolation or current values.'
        );
        assertGatewayGauge($data['range'] === ['key' => 'absolute', 'startTimestamp' => strtotime('2026-10-12 09:00:00'),
            'endTimestamp'                           => strtotime('2026-10-12 13:00:00') - 1, 'aggregationLevel' => 0, 'timezone' => 'Europe/Berlin',
            'loadedAggregates'                       => 7, 'loadBudget' => 16000, 'requestedStartTimestamp' => strtotime('2026-10-12 08:30:00'),
            'requestedEndTimestamp'                  => strtotime('2026-10-12 13:30:00')], 'Requested and effective period metadata must be explicit.');
        assertGatewayGauge(array_unique(array_column($queries, 'Reducer')) === ['average']
            && array_unique(array_column($queries, 'Limit')) === [1000], 'Fixed periods must retain explicit average reducer and per-source load limits.');
        $chart->now = strtotime('2026-11-01 12:34:00');
        assertGatewayGauge($model() === $data, 'Historical periods and original values must stay stable as time advances.');
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                str_contains($html, '"requestedStartTimestamp":') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both outputs must embed fixed historical metadata within their output limit.'
            );
        }
        assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), '"bandOpacityPercent":37'), 'Independent IPSView design remains unchanged.');
        $setPeriod = static function (string $from, string $until, string $aggregation = 'hour') use ($chart, $dateValue): void
        {
            $chart->SetTestProperty('Range', 'absolute');
            $chart->SetTestProperty('Aggregation', $aggregation);
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $chart->ApplyChanges();
        };
        $counterSources = [];
        foreach (range(0, 15) as $index) {
            $id = 99620 + $index;
            $counterSources[] = ['VariableID' => $id, 'Label' => 'Same synthetic source', 'InitiallyVisible' => $index === 0];
            $GLOBALS['symconTestVariables'][$id] = $GLOBALS['symconTestVariables'][99600 + $index];
            $GLOBALS['symconTestArchiveVariables'][$id] = $GLOBALS['symconTestArchiveVariables'][99600 + $index];
            $GLOBALS['symconTestArchiveVariables'][$id]['AggregationType'] = 1;
        }
        $chart->SetTestProperty('Sources', json_encode(array_slice($counterSources, 0, 2), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('Metric', 'consumption');
        $chart->ApplyChanges();
        $queries = [];
        $consumption = $model();
        assertGatewayGauge($consumption['themeRiver']['rows'] === $data['themeRiver']['rows']
            && $consumption['themeRiver']['metric'] === 'consumption' && $consumption['range'] === $data['range']
            && array_column($queries, 'Reducer') === ['sum', 'sum'], 'Historical consumption uses explicit counter sums with unchanged gaps, units and zeros.');
        $chart->SetTestProperty('Metric', 'average');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 204, 'A mismatched historical archive type must still fail.');
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 2), JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge($model() === $data, 'Returning to a compatible metric recovers the original historical model.');

        $before = count($queries);
        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00', 'day');
        assertGatewayGauge(
            $model()['themeRiver']['rows'] === [] && count($queries) === $before && $chart->GetTestStatus() === IS_ACTIVE,
            'No complete day means an empty valid model without any archive query, including publication.'
        );
        foreach (range(0, 1) as $index) {
            $GLOBALS['symconTestArchiveVariables'][99600 + $index]['AggregatedValues'][] = [
                'TimeStamp' => strtotime('2026-09-13 00:00:00'), 'Avg' => 3.0 + $index];
            $GLOBALS['symconTestArchiveVariables'][99600 + $index]['AggregatedValues'][] = [
                'TimeStamp' => strtotime('2026-09-12 00:00:00'), 'Avg' => 1.0 + $index];
        }
        $setPeriod('2026-09-11 08:30:00', '2026-09-14 13:30:00', 'day');
        $daily = $model();
        assertGatewayGauge(array_column($daily['themeRiver']['rows'], 'label') === ['2026-09-12', '2026-09-13']
            && array_column($daily['themeRiver']['rows'], 'values') === [[1.0, 2.0], [3.0, 4.0]]
            && $daily['range']['aggregationLevel'] === 1, 'Only fully contained local calendar days enter daily means.');
        foreach ([['2026-03-29', '2026-03-30', 23], ['2026-10-25', '2026-10-26', 25]] as [$from, $until, $hours]) {
            $setPeriod($from . ' 00:00:00', $until . ' 00:00:00', 'day');
            $dst = $model();
            assertGatewayGauge(count($dst['themeRiver']['rows']) === 1
                && $dst['range']['endTimestamp'] - $dst['range']['startTimestamp'] + 1 === $hours * 3600, 'Historical DST days retain 23/25 elapsed hours.');
            $setPeriod($from . ' 00:00:00', $until . ' 00:00:00');
            $labels = array_column($model()['themeRiver']['rows'], 'label');
            assertGatewayGauge(
                count($labels) === $hours && count(array_unique($labels)) === $hours,
                'DST hourly labels remain unique and cover actual elapsed hours without filling missing archives.'
            );
        }
        foreach ([['{}', $end], [$start, '{}'], [$end, $start], [$start, $start],
            [$start, $dateValue('2026-11-02 00:00:00')],
            [$dateValue('2026-03-29 02:30:00'), $end], [$dateValue('2026-10-25 02:30:00'), $end],
            ['{"year":2026,"month":2,"day":30,"hour":0,"minute":0,"second":0}', $end]] as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $from);
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $before = count($queries);
            $chart->ApplyChanges();
            $diagnostic = json_decode($chart->GetThemeRiverDiagnostic(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge($chart->GetTestStatus() === 202 && !$diagnostic['valid'] && count($queries) === $before
                && str_contains($chart->GetVisualizationTile(), $diagnostic['lastError']), 'Invalid historical dates fail visibly before archive access.');
        }
        date_default_timezone_set('Asia/Kathmandu');
        $setPeriod('2026-10-12 00:30:00', '2026-10-12 03:30:00');
        assertGatewayGauge(array_column($model()['themeRiver']['rows'], 'label') === [
            '2026-10-12 01:00 +05:45', '2026-10-12 02:00 +05:45'], 'Fixed fractional UTC offsets remain supported.');
        date_default_timezone_set('Australia/Lord_Howe');
        $before = count($queries);
        $setPeriod('2026-10-04 00:00:00', '2026-10-04 04:00:00');
        assertGatewayGauge($chart->GetTestStatus() === 202 && count($queries) === $before, 'Non-hour offset changes cannot invent full hourly aggregates.');
        date_default_timezone_set('Europe/Berlin');
        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00');
        $chart->SetTestProperty('CustomRangeValue', 0);
        $chart->SetTestProperty('CustomRangeUnit', 'invalid');
        assertGatewayGauge($model() === $data, 'Inactive custom settings must not affect absolute windows.');
        $chart->SetTestProperty('CustomRangeValue', 7);
        $chart->SetTestProperty('CustomRangeUnit', 'day');
        foreach (['24h', '7d', '30d', 'custom'] as $range) {
            $chart->SetTestProperty('Range', $range);
            $chart->SetTestProperty('AbsoluteStart', 'invalid');
            $chart->SetTestProperty('AbsoluteEnd', '{}');
            $chart->ApplyChanges();
            $legacy = $model();
            $expected = SymconECharts\ThemeRiverData::Window($range, 'hour', $chart->now, 'Europe/Berlin');
            assertGatewayGauge(array_keys($legacy['range']) === ['key', 'startTimestamp', 'endTimestamp', 'aggregationLevel', 'timezone', 'loadedAggregates', 'loadBudget']
                && $legacy['range']['startTimestamp'] === $expected['StartTimestamp'] && $legacy['range']['endTimestamp'] === $expected['EndTimestamp']
                && count($legacy['themeRiver']['rows']) === count($expected['Intervals']), 'Legacy window model and inactive dates remain backward compatible.');
        }
        $setPeriod('2026-10-12 08:30:00', '2026-10-12 13:30:00');
        $saved = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $before = count($queries);
        IPSModuleStrict::$ParentResponder = static function (): never
        {
            throw new LogicException('Historical previews must not contact the Gateway.');
        };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            assertGatewayGauge(
                $field['type'] === 'SelectDateTime' && $field['visible'] && str_contains($field['onChange'], 'ECRV_UpdateBarPreviewFromForm'),
                'Both native date controls update the live previews.'
            );
        }
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'AbsoluteTimeZone')['caption'], 'Europe/Berlin'), 'The form identifies the installation timezone.');
        $chart->UpdateTimeRangeForm('absolute');
        assertGatewayGauge(array_column(array_slice($chart->GetTestFormUpdates(), -6), 'Value') === [true, true, true, true, false, false], 'Date and relative duration controls switch without persistence.');
        foreach (['average', 'consumption'] as $metric) {
            $chart->UpdateBarPreviewFromForm(json_encode(['Range' => 'absolute', 'AbsoluteStart' => json_decode($start), 'AbsoluteEnd' => $end,
                'Metric'                                          => $metric, 'Title' => 'Unsaved <historical>', 'LegendPosition' => 'top', 'IPSViewLegendPosition' => 'top'], JSON_THROW_ON_ERROR));
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-range="absolute"')
                    && str_contains($svg, '2026-10-12 08:30:00 +02:00') && str_contains($svg, 'Unsaved &lt;historical&gt;')
                    && str_contains($svg, 'data-metric="' . $metric . '"') && str_contains($svg, 'data-band="0"'), 'Both synthetic metric previews preserve fixed dates, escaping and example gaps.');
                $xpath = new DOMXPath($xml);
                $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
                foreach ($xpath->query('//s:text[@data-legend-label]') as $label) {
                    assertGatewayGauge((float) $label->getAttribute('y') > 80, 'Top legends must remain below the new period caption.');
                }
            }
        }
        $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":{}}');
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(str_contains($svg, 'historical date and time selection is invalid') && !str_contains($svg, '<polygon'), 'Invalid dates show a hint instead of plausible bands.');
        }
        assertGatewayGauge(
            $chart->GetTestProperties() === $saved && $chart->GetTestStatus() === $status && count($queries) === $before,
            'Form edits remain pure: no property writes, status changes or archive requests.'
        );
        IPSModuleStrict::$ParentResponder = $responder;

        // All 16 sources count, even hidden ones, for each historical metric.
        foreach (['average' => $sources, 'consumption' => $counterSources] as $metric => $allSources) {
            foreach ($allSources as $index => $source) {
                $aggregates = [];
                for ($point = 719; $point >= 0; --$point) {
                    $aggregates[] = ['TimeStamp' => strtotime('2026-09-01 00:00:00') + $point * 3600, 'Avg' => 1.2345678901234567e100 + $index * 1e90];
                }
                $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregatedValues'] = $aggregates;
            }
            $chart->SetTestProperty('Sources', json_encode($allSources, JSON_THROW_ON_ERROR));
            $chart->SetTestProperty('Metric', $metric);
            $setPeriod('2026-09-01 00:00:00', '2026-10-01 00:00:00');
            $queries = [];
            $stressJSON = $chart->GetThemeRiverData();
            $stress = json_decode($stressJSON, true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge($stress['range']['loadedAggregates'] === 11520 && $stress['range']['loadBudget'] === 16000
                && count($stress['themeRiver']['rows']) === 720 && $stress['themeRiver']['missingIntervals'] === 0
                && count($queries) === 16 && array_unique(array_column($queries, 'Limit')) === [1000]
                && strlen($stressJSON) < 900000, 'Both metrics retain 16 sources, 720 intervals and 16000/1000 aggregate/model budgets.');
            foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
                assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($html, 'compressedStateGzipBase64'), 'Large historical output remains losslessly compressed and bounded.');
            }
            $before = count($queries);
            $setPeriod('2026-09-01 00:00:00', '2026-10-01 01:00:00');
            assertGatewayGauge($chart->GetTestStatus() === 202 && count($queries) === $before, '721 intervals fail before loading any source.');
        }
        $setPeriod('2026-09-01 00:00:00', '2026-10-01 00:00:00');
        foreach (['truncated', 'oversized', 'negative', 'type'] as $failure) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder, $failure): string
            {
                $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
                if (($response['Payload']['VariableID'] ?? null) === 99635) {
                    if ($failure === 'truncated') {
                        $response['Payload']['Truncated'] = true;
                    } elseif ($failure === 'oversized') {
                        $response['Payload']['Points'] = array_fill(0, 1001, $response['Payload']['Points'][0]);
                    } elseif ($failure === 'negative') {
                        $response['Payload']['Points'][0][1] = -1.0;
                    } else {
                        $response['Payload']['ArchiveAggregationType'] = 'standard';
                    }
                }
                return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
            };
            $failed = false;
            try {
                $model();
            } catch (Throwable) {
                $failed = true;
            }
            assertGatewayGauge($failed && $chart->GetTestStatus() === 204, 'Malformed hidden responses fail without partial models: ' . $failure);
        }
        IPSModuleStrict::$ParentResponder = $responder;
        assertGatewayGauge(
            $model()['range']['loadedAggregates'] === 11520 && $chart->GetTestStatus() === IS_ACTIVE,
            'Historical reads recover after transient invalid responses without changing saved configuration.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        date_default_timezone_set($previousZone);
    }
    echo "Fixed historical ThemeRiver periods verified.\n";
})();
