<?php

declare(strict_types=1);

(static function (): void
{
    $zone = date_default_timezone_get();
    $parent = IPSModuleStrict::$ParentResponder;
    date_default_timezone_set('Europe/Berlin');
    $date = static function (string $label): string
    {
        $d = new DateTimeImmutable($label);
        return json_encode(['year' => (int) $d->format('Y'), 'month' => (int) $d->format('n'),
            'day'                  => (int) $d->format('j'), 'hour' => (int) $d->format('G'),
            'minute'               => (int) $d->format('i'), 'second' => (int) $d->format('s')], JSON_THROW_ON_ERROR);
    };
    $queries = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$queries): string
    {
        $r = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($r['Operation'] ?? '') === 'archive.read') {
            $queries[] = $r['Payload'];
        }
        return $parent($json);
    };
    try {
        $id = 99600;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
            'Value'                                            => 999999.0, 'Name' => 'Synthetic minute/week temperature', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
        foreach ([['08:30:00', 0], ['08:15:00', 11], ['08:14:59', 3], ['08:02:00', 9], ['08:01:00', -4], ['08:00:00', 2], ['07:59:59', 900]] as [$time, $value]) {
            $GLOBALS['symconTestArchiveVariables'][$id]['LoggedValues'][] = ['TimeStamp' => strtotime('2026-10-12 ' . $time), 'Value' => $value];
        }
        $chart = new TestableEChartsCandlestick();
        $chart->Create();
        $chart->now = strtotime('2026-11-01 12:43:27');
        $chart->SetTestProperty('VariableID', $id);
        $chart->SetTestProperty('Range', 'absolute');
        $chart->SetTestProperty('AbsoluteStart', $date('2026-10-12 08:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-12 10:00:00'));
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewRiseColor', 0x123456);
        $chart->SetTestProperty('EnableZoom', true);
        $chart->SetTestProperty('IPSViewEnableZoom', true);
        $read = static fn (): array => json_decode($chart->GetCandlestickData(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['fifteen-minutes' => [8, [2.0, 3.0, -4.0, 9.0]], 'thirty-minutes' => [4, [2.0, 11.0, -4.0, 11.0]]] as $interval => [$count, $first]) {
            $chart->SetTestProperty('Interval', $interval);
            $chart->ApplyChanges();
            $data = $read();
            assertGatewayGauge(
                $chart->GetTestStatus() === IS_ACTIVE && $data['candlestick']['interval'] === $interval
                && count($data['candlestick']['data']) === $count && $data['candlestick']['data'][0]['statistics'] === $first
                && $data['candlestick']['data'][$count - 1]['statistics'] === null,
                'Minute modes must reach the actual raw Gateway path, preserve true OCLH and leave gaps.'
            );
            $calls = count($queries);
            foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $html) {
                assertGatewayGauge(str_contains($html, '"interval":"' . $interval . '"') && str_contains($html, '"enableZoom":true')
                    && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both output adapters retain interval metadata and zoom under their byte budget.');
            }
            assertGatewayGauge(
                count($queries) === $calls && str_contains($chart->GetIPSViewHTML(), '"riseColor":"#123456"'),
                'Designs remain independent and outputs share the bounded raw cache.'
            );
        }
        assertGatewayGauge(count($queries) === 2 && array_unique(array_column($queries, 'Mode')) === ['raw']
            && array_unique(array_column($queries, 'Limit')) === [2000], 'Each changed interval uses bounded raw reads, never native averages.');
        $chart->SetTestProperty('Interval', 'week');
        $chart->SetTestProperty('AbsoluteStart', $date('2026-10-05 00:00:00'));
        $chart->SetTestProperty('AbsoluteEnd', $date('2026-10-26 00:00:00'));
        $chart->ApplyChanges();
        $weekly = $read()['candlestick']['data'];
        assertGatewayGauge(
            array_column($weekly, 'label') === ['2026-W41', '2026-W42', '2026-W43']
            && $weekly[1]['statistics'] === [900.0, 0.0, -4.0, 900.0]
            && $weekly[0]['statistics'] === null && $weekly[2]['statistics'] === null,
            'Weekly candles summarize all actual samples in each Monday-to-Monday window without predecessor filling.'
        );
        foreach (['fifteen-minutes', 'thirty-minutes', 'week'] as $interval) {
            $chart->SetTestProperty('Interval', $interval);
            $chart->SetTestProperty('Range', '7d');
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE && count($read()['candlestick']['data']) === match ($interval) {
                'fifteen-minutes' => 672, 'thirty-minutes' => 336, 'week' => 1
            }, 'New intervals also work in unchanged relative presets.');
        }
        $chart->SetTestProperty('Interval', 'fifteen-minutes');
        $chart->SetTestProperty('Range', '30d');
        $before = count($queries);
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 202 && count($queries) === $before,
            'A too-large minute preset must fail before any archive request, not silently shorten or coarsen it.'
        );
        $chart->SetTestProperty('Range', 'custom');
        $chart->SetTestProperty('CustomRangeValue', 1);
        $chart->SetTestProperty('CustomRangeUnit', 'minute');
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $read()['candlestick']['data'] === [] && count($queries) === $before,
            'Empty short minute windows are ready without any archive access.'
        );
        $saved = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Drafts must not query the Gateway.'); };
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $options = findGaugeFormElement($form['elements'], 'Interval');
        assertGatewayGauge(array_column($options['options'], 'value') === ['fifteen-minutes', 'thirty-minutes', 'hour', 'day', 'week']
            && str_contains($options['onChange'], 'ECCA_UpdateBarPreviewFromForm'), 'All interval choices update both previews.');
        foreach (['fifteen-minutes', 'thirty-minutes', 'week'] as $interval) {
            foreach (['horizontal', 'vertical'] as $orientation) {
                $chart->UpdateBarPreviewFromForm(json_encode(['Range' => '7d', 'Interval' => $interval,
                    'Orientation'                                     => $orientation, 'IPSViewOrientation' => $orientation], JSON_THROW_ON_ERROR));
                foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                    $xml = new DOMDocument();
                    assertGatewayGauge(
                        $xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-interval="' . $interval . '"')
                        && str_contains($svg, 'Example data') && str_contains($svg, 'data-time-slider="true"'),
                        'Both SVG previews reflect the selected interval and keep independently configured zoom.'
                    );
                }
            }
        }
        foreach (['{"Range":"30d","Interval":"fifteen-minutes"}' => 'Too many archive intervals',
            '{"Range":"24h","Interval":"week"}'                  => 'no complete interval'] as $draft => $hint) {
            $chart->UpdateBarPreviewFromForm($draft);
            foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(str_contains($svg, $hint) && !str_contains($svg, 'data-candle='), 'Invalid or empty drafts cannot show plausible invented candles.');
            }
        }
        assertGatewayGauge(
            $chart->GetTestProperties() === $saved && $chart->GetTestStatus() === $status,
            'New interval previews never mutate saved settings or status.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
        date_default_timezone_set($zone);
    }
    echo "Candlestick minute/week integration, drafts, outputs and compatibility verified.\n";
})();
