<?php

declare(strict_types=1);

(static function (): void
{
    $savedArchive = $GLOBALS['symconTestArchiveVariables'][4711];
    $savedParent = IPSModuleStrict::$ParentResponder;
    $savedTimezone = date_default_timezone_get();
    $metricParent = static function (string $json): string
    {
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $metricParent;
    date_default_timezone_set('Europe/Berlin');
    $time = static fn (string $date): int => (new DateTimeImmutable($date, new DateTimeZone('Europe/Berlin')))->getTimestamp();
    $rows = [
        ['TimeStamp' => $time('2026-09-14 08:00:00'), 'Avg' => 10, 'Min' => -5, 'Max' => 18],
        ['TimeStamp' => $time('2026-09-07 08:00:00'), 'Avg' => 20, 'Min' => 0, 'Max' => 30]
    ];
    try {
        $GLOBALS['symconTestArchiveVariables'][4711] = ['AggregationType' => 0,
            'LoggedValues'                                                => [], 'AggregatedValues' => $rows];
        $chart = new TestableEChartsHeatmap();
        $chart->Create();
        $chart->SetTestCurrentTimestamp($time('2026-10-13 12:34:00'));
        $chart->SetTestProperty('SourceVariableID', 4711);
        $chart->SetTestProperty('Range', '1m');
        $chart->SetTestProperty('UseVariablePresentation', false);
        $chart->SetTestProperty('Unit', 'kWh');
        $chart->ApplyChanges();
        $read = static fn (): array => json_decode($chart->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(($read()['heatmap']['metric'] ?? null) === 'average', 'Heatmap must default explicitly to average.');
        foreach (['average' => 15.0, 'minimum' => -5.0, 'maximum' => 30.0] as $metric => $expected) {
            $chart->SetTestProperty('Metric', $metric);
            $model = $read();
            assertGatewayGauge(
                $model['heatmap']['metric'] === $metric
                && $model['cells'] === [['hour' => 8, 'weekday' => 0, 'value' => $expected, 'count' => 2]],
                'Heatmap must use the actual selected gateway field and metric-specific long-period combination.'
            );
        }
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = 1;
        $chart->SetTestProperty('Metric', 'consumption');
        $counter = $read();
        assertGatewayGauge(
            $counter['cells'][0]['value'] === 30.0 && $counter['cells'][0]['count'] === 2,
            'Counter consumption must sum hourly positive deltas, not average them.'
        );
        foreach (['average', 'minimum', 'maximum'] as $metric) {
            $chart->SetTestProperty('Metric', $metric);
            try {
                $read();
                throw new LogicException('A counter must not be treated as a standard archive.');
            } catch (RuntimeException $exception) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'Counter/metric mismatch must be a visible loading error.');
            }
        }
        $chart->SetTestProperty('Metric', 'consumption');
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = 0;
        try {
            $read();
            throw new LogicException('Standard archive consumption must fail.');
        } catch (RuntimeException) {
            assertGatewayGauge($chart->GetTestStatus() === 204, 'Consumption must require a counter.');
        }
        $chart->SetTestProperty('Metric', 'minimum');
        $valid = $read();
        foreach (['VariableType' => 1, 'ArchiveAggregationType' => 'counter', 'EffectiveReducer' => 'average',
            'StartTimestamp'     => 1, 'EndTimestamp' => 1, 'Truncated' => true, 'Points' => [[1, 2]]] as $key => $value) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($metricParent, $key, $value): string
            {
                $response = json_decode($metricParent($json), true, 512, JSON_THROW_ON_ERROR);
                $response['Payload'][$key] = $value;
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            try {
                $read();
                throw new LogicException('Invalid Heatmap metadata must be rejected.');
            } catch (RuntimeException) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'Malformed response must fail with gateway status.');
            }
        }
        IPSModuleStrict::$ParentResponder = $metricParent;
        assertGatewayGauge($read() === $valid, 'Heatmap must recover without stale metric data.');
        foreach ([[[1, 2]], [[$time('2026-09-07 08:00:00'), 0], [$time('2026-09-07 08:00:00'), 1]],
            [[$time('2026-09-14 08:00:00'), 0], [$time('2026-09-07 08:00:00'), 1]],
            array_fill(0, 2001, [$time('2026-09-07 08:00:00'), 0])] as $points) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($metricParent, $points): string
            {
                $response = json_decode($metricParent($json), true, 512, JSON_THROW_ON_ERROR);
                $response['Payload']['Points'] = $points;
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            try {
                $read();
                throw new LogicException('Invalid Heatmap points must fail.');
            } catch (RuntimeException) {
                assertGatewayGauge($chart->GetTestStatus() === 204, 'Invalid points must be a visible loading failure.');
            }
        }
        IPSModuleStrict::$ParentResponder = $metricParent;
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = 1;
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'][0]['Avg'] = -1;
        $chart->SetTestProperty('Metric', 'consumption');
        try {
            $read();
            throw new LogicException('Negative consumption must fail.');
        } catch (RuntimeException) {
            assertGatewayGauge($chart->GetTestStatus() === 204, 'Negative counter consumption must not be silently clamped.');
        }
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregationType'] = 0;
        $GLOBALS['symconTestArchiveVariables'][4711]['AggregatedValues'] = $rows;
        $chart->SetTestProperty('Metric', 'minimum');
        $formChart = new EChartsHeatmap();
        $formChart->Create();
        $form = json_decode($formChart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $select = findGaugeFormElement($form['elements'], 'Metric');
        assertGatewayGauge(array_column($select['options'] ?? [], 'value') === ['average', 'minimum', 'maximum', 'consumption']
            && str_contains($select['onChange'] ?? '', '$Metric'), 'Metric selector must support all four read-only draft modes.');
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new RuntimeException('Preview must not query archives.'); };
        $formChart->UpdateBarPreviewFromForm(json_encode(['Metric' => 'consumption'], JSON_THROW_ON_ERROR));
        $preview = base64_decode(substr((string) array_slice($formChart->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')));
        assertGatewayGauge(is_string($preview) && str_contains($preview, 'Sum of hourly consumption')
            && str_contains($preview, 'Example data'), 'Draft consumption preview must disclose its synthetic metric.');
        IPSModuleStrict::$ParentResponder = $metricParent;
        $formChart->SetTestProperty('SourceVariableID', 4711);
        $formChart->SetTestProperty('Metric', 'minimum');
        foreach ([$formChart->GetVisualizationTile(), $formChart->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                str_contains($html, '"metric":"minimum"') && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both output routes must carry the selected metric within the buffer limit.'
            );
        }
        $chart->SetTestProperty('Metric', 'consumption');
        $chart->SetTestProperty('ColorScalePreset', 'tempera12');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 202, 'Consumption must not use a fixed Celsius palette.');
        $chart->SetTestProperty('Metric', 'unknown');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 202, 'Unknown metrics must not silently fall back to average.');
    } finally {
        IPSModuleStrict::$ParentResponder = $savedParent;
        $GLOBALS['symconTestArchiveVariables'][4711] = $savedArchive;
        date_default_timezone_set($savedTimezone);
    }
})();
