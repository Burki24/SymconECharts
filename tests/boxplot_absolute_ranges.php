<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsBoxplot/module.php';

final class TestableAbsoluteEChartsBoxplot extends EChartsBoxplot
{
    public int $Now = 0;

    protected function CurrentTimestamp(): int
    {
        return $this->Now;
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsBoxplot/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsBoxplot/visualization/' . $filename);
    }
}

// Run via gateway_gauges.php: only Kernel calls are doubled; planning and Gateway are real.
(static function (): void
{
    $previousZone = date_default_timezone_get();
    $previousParent = IPSModuleStrict::$ParentResponder;
    $originalVariables = array_keys($GLOBALS['symconTestVariables']);
    $originalArchives = array_keys($GLOBALS['symconTestArchiveVariables']);
    date_default_timezone_set('Europe/Berlin');
    $dateValue = static function (string $local): string
    {
        $date = new DateTimeImmutable($local, new DateTimeZone('Europe/Berlin'));
        return json_encode(['year' => (int) $date->format('Y'), 'month' => (int) $date->format('n'),
            'day'                  => (int) $date->format('j'), 'hour' => (int) $date->format('G'),
            'minute'               => (int) $date->format('i'), 'second' => (int) $date->format('s')], JSON_THROW_ON_ERROR);
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
        for ($index = 0; $index < 33; ++$index) {
            $id = 99300 + $index;
            $sources[] = ['VariableID' => $id, 'Label' => 'Room <same>', 'Color' => -1];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
                'Value'                                            => 123456.0, 'Name' => 'Room <same>', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
        }
        // A partial leading/trailing hour and misleading raw extrema cannot enter the summary.
        foreach ([8 => 1000.0, 9 => -4.0, 10 => 0.0, 11 => 4.0, 12 => 8.0, 13 => -1000.0] as $hour => $value) {
            $GLOBALS['symconTestArchiveVariables'][99300]['AggregatedValues'][] = [
                'TimeStamp' => strtotime('2026-10-12 ' . $hour . ':00:00'), 'Avg' => $value,
                'Min'       => -99999.0, 'Max' => 99999.0];
        }
        // AC_GetAggregatedValues returns newest first; the real Gateway reverses it.
        $GLOBALS['symconTestArchiveVariables'][99300]['AggregatedValues'] = array_reverse($GLOBALS['symconTestArchiveVariables'][99300]['AggregatedValues']);
        $GLOBALS['symconTestArchiveVariables'][99300]['LoggedValues'][] = [
            'TimeStamp' => strtotime('2026-10-12 10:00:00'), 'Value' => 999999.0, 'Duration' => 60];
        $chart = new TestableAbsoluteEChartsBoxplot();
        $chart->Now = strtotime('2026-10-20 12:34:00');
        $chart->Create();
        $defaults = $chart->GetTestProperties();
        assertGatewayGauge($defaults['Range'] === '7d' && $defaults['CustomRangeValue'] === 7
            && $defaults['CustomRangeUnit'] === 'day' && ($defaults['AbsoluteStart'] ?? null) === '{}'
            && ($defaults['AbsoluteEnd'] ?? null) === '{}', 'Absolute Boxplot properties must be additive and unselected by default.');
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 2), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewOrientation', 'horizontal');
        $chart->SetTestProperty('Range', 'absolute');
        $startValue = $dateValue('2026-10-12 08:30:00');
        $endValue = $dateValue('2026-10-12 13:30:00');
        $chart->SetTestProperty('AbsoluteStart', $startValue);
        $chart->SetTestProperty('AbsoluteEnd', $endValue);
        $properties = $chart->GetTestProperties();
        $chart->ApplyChanges();
        $initialError = '';
        if ($chart->GetTestStatus() !== IS_ACTIVE) {
            try {
                $chart->GetBoxplotData();
            } catch (Throwable $exception) {
                $initialError = $exception->getMessage();
            }
        }
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Boxplot must accept a fixed historical local From/Until range. Status: '
            . $chart->GetTestStatus() . '; diagnostic: ' . $chart->GetBoxplotDiagnostic() . '; error: ' . $initialError);
        $queries = [];
        $model = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
        $range = $model['range'];
        assertGatewayGauge(
            $range == ['key'              => 'absolute', 'startTimestamp' => strtotime('2026-10-12 09:00:00'),
                'endTimestamp'            => strtotime('2026-10-12 13:00:00') - 1, 'aggregationLevel' => 0,
                'requestedStartTimestamp' => strtotime('2026-10-12 08:30:00'),
                'requestedEndTimestamp'   => strtotime('2026-10-12 13:30:00'), 'timezone' => 'Europe/Berlin'],
            'Fixed ranges must retain requested inclusive/exclusive endpoints and query only whole contained hours.'
        );
        assertGatewayGauge(
            count($queries) === 2 && $queries[0]['StartTimestamp'] === $range['startTimestamp']
            && $queries[0]['EndTimestamp'] === $range['endTimestamp'] && $queries[0]['AggregationLevel'] === 0
            && $queries[0]['Mode'] === 'aggregated' && $queries[0]['Reducer'] === 'average' && $queries[0]['Limit'] === 1000,
            'Fixed ranges must use the existing completed hourly mean Gateway contract and unchanged per-source budget.'
        );
        assertGatewayGauge(
            $model['boxplot']['data'][0]['statistics'] === [-4.0, -1.0, 2.0, 5.0, 8.0]
            && $model['boxplot']['data'][0]['count'] === 4 && $model['boxplot']['data'][1]['statistics'] === null
            && $model['boxplot']['data'][1]['count'] === 0 && $model['boxplot']['sampling'] === 'completed-hourly-means'
            && $model['boxplot']['quantile'] === 'R7' && $model['boxplot']['whiskers'] === 'minmax',
            'Absolute dates must preserve R7 hourly-mean summaries, gaps, empty categories and raw/current-value exclusion.'
        );
        $chart->Now += 30 * 86400;
        $later = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $later['range'] === $range && $later['boxplot']['data'] === $model['boxplot']['data'],
            'A fixed historical Boxplot must not slide when the clock advances.'
        );
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(str_contains($html, json_encode($range, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
                && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both Boxplot outputs must embed the same fixed time window within 1 MiB.');
        }
        assertGatewayGauge(str_contains($chart->GetVisualizationTile(), '"orientation":"vertical"')
            && str_contains($chart->GetIPSViewHTML(), '"orientation":"horizontal"'), 'Fixed dates must preserve independent output designs.');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestProperties() === $properties, 'Repeated fixed-range ApplyChanges must not rewrite configuration.');

        foreach ([['2026-10-12 09:00:00', '2026-10-12 10:00:00', 1],
            ['2026-10-12 09:00:01', '2026-10-12 10:59:59', 0],
            ['2026-10-12 09:20:00', '2026-10-12 09:40:00', 0]] as [$from, $until, $count]) {
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $queries = [];
            $small = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $small['boxplot']['data'][0]['count'] === $count
                && ($count !== 0 || $queries === []) && $small['range']['endTimestamp'] >= $small['range']['startTimestamp'],
                'Only completely contained hours may be queried; empty hour windows stay valid without an archive read: ' . $from
            );
        }
        $chart->Now = strtotime('2026-10-20 12:34:00');
        $chart->SetTestProperty('AbsoluteStart', $dateValue('2026-10-20 10:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $dateValue('2026-10-20 12:34:00'));
        $currentEnd = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['range'];
        assertGatewayGauge($currentEnd['endTimestamp'] === strtotime('2026-10-20 12:00:00') - 1
            && $currentEnd['requestedEndTimestamp'] === $chart->Now, 'A historical endpoint equal to now is valid but must not admit the running hour.');
        $validStart = $dateValue('2026-10-12 08:00:00');
        $validEnd = $dateValue('2026-10-12 13:00:00');
        $invalid = [
            ['{}', $validEnd], [$validStart, '{}'], ['not JSON', $validEnd], ['[]', $validEnd],
            [str_replace('"year":2026', '"year":"2026"', $validStart), $validEnd],
            ['{"year":2026,"month":2,"day":30,"hour":8,"minute":0,"second":0}', $validEnd],
            ['{"year":2026,"month":10,"day":12,"hour":24,"minute":0,"second":0}', $validEnd],
            [$validEnd, $validStart], [$validStart, $validStart],
            [$validStart, $dateValue('2026-10-20 12:34:01')],
            ['{"year":2026,"month":3,"day":29,"hour":2,"minute":30,"second":0}', $validEnd]
        ];
        foreach ($invalid as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $from);
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $before = $chart->GetTestProperties();
            $queries = [];
            $chart->ApplyChanges();
            assertGatewayGauge(
                $chart->GetTestStatus() === 202 && $queries === [] && $chart->GetTestProperties() === $before,
                'Invalid/unselected/reversed/future/normalized local dates must fail before reading and without configuration writes: ' . $from
            );
        }
        $chart->Now = strtotime('2026-11-01 12:34:00');
        foreach ([['2026-10-25 01:00:00', '{"year":2026,"month":10,"day":25,"hour":2,"minute":30,"second":0}'],
            ['2026-10-25 02:30:00', $dateValue('2026-10-25 04:00:00')]] as [$from, $until]) {
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $until);
            $queries = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202 && $queries === [], 'Ambiguous autumn local times must not silently pick a UTC offset.');
        }
        foreach ([['2026-03-29 00:00:00', '2026-03-29 04:00:00', 3],
            ['2026-10-25 00:00:00', '2026-10-25 04:00:00', 5]] as [$from, $until, $hours]) {
            $chart->SetTestProperty('AbsoluteStart', $dateValue($from));
            $chart->SetTestProperty('AbsoluteEnd', $dateValue($until));
            $chart->ApplyChanges();
            $dst = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['range'];
            assertGatewayGauge(
                $chart->GetTestStatus() === IS_ACTIVE && $dst['endTimestamp'] - $dst['startTimestamp'] + 1 === $hours * 3600,
                'Valid historical windows crossing DST retain actual completed hours, including both repeated autumn hours.'
            );
        }
        date_default_timezone_set('Australia/Lord_Howe');
        $chart->SetTestProperty('AbsoluteStart', $dateValue('2026-10-04 00:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $dateValue('2026-10-04 04:00:00'));
        $queries = [];
        $before = $chart->GetTestProperties();
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 202 && $queries === [] && $chart->GetTestProperties() === $before
            && str_contains($chart->GetVisualizationTile(), 'Hourly historical periods across non-hour timezone changes are not supported.'),
            'Unsupported non-hour transitions must produce a clear configuration error without archive reads or property writes.'
        );
        date_default_timezone_set('Asia/Kathmandu');
        $chart->SetTestProperty('AbsoluteStart', $dateValue('2026-10-12 08:30:00'));
        $chart->SetTestProperty('AbsoluteEnd', $dateValue('2026-10-12 13:30:00'));
        $localZone = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['range'];
        assertGatewayGauge(
            $localZone['timezone'] === 'Asia/Kathmandu'
            && $localZone['startTimestamp'] === strtotime('2026-10-12 09:00:00')
            && $localZone['endTimestamp'] === strtotime('2026-10-12 13:00:00') - 1,
            'Fixed ranges and full hours must use the configured runtime timezone, not UTC or whole-hour timezone assumptions.'
        );
        date_default_timezone_set('Europe/Berlin');

        // Legacy windows ignore inactive malformed absolute fields and retain their exact model keys.
        $chart->SetTestProperty('AbsoluteStart', 'broken');
        $chart->SetTestProperty('AbsoluteEnd', '{}');
        foreach (['24h' => 86400, '7d' => 604800, '30d' => 2592000, 'custom' => 604800] as $key => $seconds) {
            $chart->SetTestProperty('Range', $key);
            $chart->ApplyChanges();
            $legacy = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['range'];
            assertGatewayGauge(
                $chart->GetTestStatus() === IS_ACTIVE
                && array_keys($legacy) === ['key', 'startTimestamp', 'endTimestamp', 'aggregationLevel']
                && $legacy['endTimestamp'] - $legacy['startTimestamp'] + 1 === $seconds,
                'Legacy presets/custom durations must remain byte-contract compatible and ignore inactive absolute fields: ' . $key
            );
        }

        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', $validStart);
        $chart->SetTestProperty('AbsoluteEnd', $validEnd);
        $chart->ApplyChanges();
        $properties = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        $archiveCount = $GLOBALS['symconTestArchiveQueryCount'];
        $formParent = IPSModuleStrict::$ParentResponder;
        IPSModuleStrict::$ParentResponder = static function (): string
        { throw new RuntimeException('Form and draft preview must not read the Gateway.'); };
        try {
            $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $rangeField = findGaugeFormElement($form['elements'], 'Range');
            assertGatewayGauge(in_array('absolute', array_column($rangeField['options'], 'value'), true), 'Range selector must offer fixed historical dates.');
            foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
                $field = findGaugeFormElement($form['elements'], $name);
                assertGatewayGauge(
                    $field['type'] === 'SelectDateTime' && $field['visible'] === true
                    && str_contains($field['onChange'], 'ECBX_UpdateBarPreviewFromForm')
                    && str_contains($rangeField['onChange'], "'" . $name . "' => $" . $name),
                    'Both native date selectors must be visible and included in the complete unsaved draft: ' . $name
                );
            }
            assertGatewayGauge(
                findGaugeFormElement($form['elements'], 'CustomRangeValue')['visible'] === false,
                'Fixed and rolling custom date controls must not be shown together.'
            );
            $chart->UpdateTimeRangeForm('7d');
            $hidden = array_slice($chart->GetTestFormUpdates(), -6);
            $chart->UpdateTimeRangeForm('absolute');
            $shown = array_slice($chart->GetTestFormUpdates(), -6);
            foreach (['AbsoluteStart', 'AbsoluteEnd'] as $name) {
                $fieldUpdate = static fn (array $updates): array => array_values(array_filter($updates, static fn (array $item): bool => $item['Field'] === $name));
                assertGatewayGauge(($fieldUpdate($hidden)[0]['Value'] ?? null) === false
                    && ($fieldUpdate($shown)[0]['Value'] ?? null) === true, 'Date selector visibility must react immediately: ' . $name);
            }
            $chart->UpdateBarPreviewFromForm(json_encode(['Range' => 'absolute', 'AbsoluteStart' => $validStart,
                'AbsoluteEnd'                                     => $validEnd, 'Title' => 'Historical draft'], JSON_THROW_ON_ERROR));
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(
                    is_string($svg) && str_contains($svg, 'Example data') && str_contains($svg, 'Historical draft'),
                    'Both date-aware draft outputs must remain explicitly synthetic examples.'
                );
                assertGatewayGauge(str_contains($svg, 'data-range="absolute"')
                    && str_contains($svg, 'data-requested-start="' . strtotime('2026-10-12 08:00:00') . '"')
                    && str_contains($svg, 'data-requested-end="' . strtotime('2026-10-12 13:00:00') . '"')
                    && str_contains($svg, 'Europe/Berlin'), 'A valid historical draft must disclose its unsaved local date window and timezone.');
                $xml = new DOMDocument();
                assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET), 'Date-aware previews must remain valid SVG.');
            }
            $chart->UpdateBarPreviewFromForm('{"Range":"absolute","AbsoluteStart":"{}","AbsoluteEnd":"{}"}');
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $xml = new DOMDocument();
                assertGatewayGauge(
                    is_string($svg) && str_contains($svg, 'Example data')
                    && !str_contains($svg, 'data-box=') && $xml->loadXML($svg, LIBXML_NONET),
                    'An invalid/unselected historical draft must disclose the date problem instead of drawing plausible example boxes.'
                );
            }
            assertGatewayGauge($chart->GetTestProperties() === $properties && $chart->GetTestStatus() === $status
                && $GLOBALS['symconTestArchiveQueryCount'] === $archiveCount, 'Valid and invalid date drafts must never change configuration, status or archives.');
        } finally {
            IPSModuleStrict::$ParentResponder = $formParent;
        }

        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 32), JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && count($chart->GetTestReferences()) === 32
            && count(json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['boxplot']['data']) === 32,
            'Absolute windows must preserve all 32 sources and independent empty categories.'
        );
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $queries = [];
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 201 && $queries === [], 'Absolute windows must not widen the 32-source guard.');
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 32), JSON_THROW_ON_ERROR));
        $chart->SetTestProperty('AbsoluteStart', $dateValue('2026-09-01 00:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $dateValue('2026-10-01 00:00:00'));
        $budgetParent = IPSModuleStrict::$ParentResponder;
        $limits = [];
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($budgetParent, &$limits): string
        {
            $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $response = json_decode($budgetParent($json), true, 512, JSON_THROW_ON_ERROR);
            if (($request['Operation'] ?? '') === 'archive.read' && ($response['Success'] ?? false)) {
                $query = $request['Payload'];
                $limits[] = $query['Limit'];
                $response['Payload']['Points'] = array_map(static fn (int $hour): array => [$query['StartTimestamp'] + $hour * 3600, (float) $hour], range(0, min(720, $query['Limit']) - 1));
                $response['Payload']['Truncated'] = $query['Limit'] < 720;
            }
            return json_encode($response, JSON_THROW_ON_ERROR);
        };
        try {
            $html = $chart->GetVisualizationTile();
            assertGatewayGauge(
                $chart->GetTestStatus() === 204 && str_contains($html, '"status":"error"')
                && str_contains($html, 'Choose a shorter time range.') && count($limits) === 14 && end($limits) === 640,
                'Absolute ranges retain the shared 10000-hour budget, never publish statistics from truncated series.'
            );
        } finally {
            IPSModuleStrict::$ParentResponder = $budgetParent;
        }
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 1), JSON_THROW_ON_ERROR));
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($budgetParent): string
        {
            $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $response = json_decode($budgetParent($json), true, 512, JSON_THROW_ON_ERROR);
            if (($request['Operation'] ?? '') === 'archive.read' && ($response['Success'] ?? false)) {
                $response['Payload']['Truncated'] = true;
            }
            return json_encode($response, JSON_THROW_ON_ERROR);
        };
        try {
            $html = $chart->GetIPSViewHTML();
            assertGatewayGauge($chart->GetTestStatus() === 204 && str_contains($html, '"status":"error"')
                && str_contains($html, 'Choose a shorter time range.'), 'Per-source truncation must still invalidate the whole absolute Boxplot.');
        } finally {
            IPSModuleStrict::$ParentResponder = $budgetParent;
        }
        $chart->SetTestProperty('AbsoluteStart', $validStart);
        $chart->SetTestProperty('AbsoluteEnd', $validEnd);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'A valid shorter fixed range must recover after resource rejection.');
    } finally {
        IPSModuleStrict::$ParentResponder = $previousParent;
        date_default_timezone_set($previousZone);
        foreach (array_diff(array_keys($GLOBALS['symconTestVariables']), $originalVariables) as $id) {
            unset($GLOBALS['symconTestVariables'][$id]);
        }
        foreach (array_diff(array_keys($GLOBALS['symconTestArchiveVariables']), $originalArchives) as $id) {
            unset($GLOBALS['symconTestArchiveVariables'][$id]);
        }
    }
})();

echo "Boxplot fixed historical periods, drafts, budgets and legacy windows verified.\n";
