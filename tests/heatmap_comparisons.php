<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

(static function (): void
{
    $savedParent = IPSModuleStrict::$ParentResponder;
    $savedZone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $stamp = static fn (string $date): int => (new DateTimeImmutable($date, new DateTimeZone('Europe/Berlin')))->getTimestamp();
    $requests = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use (&$requests): string
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
    $sources = [];
    foreach ([58001, 58002, 58003, 58004] as $index => $id) {
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
            'Value'                                            => 9999.0, 'Name' => 'Same source name', 'Presentation' => ['SUFFIX' => '°C', 'DIGITS' => 1]];
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => [
            ['TimeStamp' => $stamp('2026-09-07 08:00:00'), 'Avg' => $index * 10.0, 'Min' => $index * 10.0 - 2, 'Max' => $index * 10.0 + 3],
            ['TimeStamp' => $stamp('2026-09-14 08:00:00'), 'Avg' => $index * 10.0 + 4, 'Min' => $index * 10.0 - 1, 'Max' => $index * 10.0 + 8],
            ['TimeStamp' => $stamp('2026-08-03 08:00:00'), 'Avg' => 77.0, 'Min' => 66.0, 'Max' => 88.0]
        ]];
        usort($GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'], static fn (array $a, array $b): int => $b['TimeStamp'] <=> $a['TimeStamp']);
        $sources[] = ['VariableID' => $id, 'Label' => '<View & ' . $index . '>'];
    }
    $chart = new TestableEChartsHeatmap();
    $chart->Create();
    $chart->SetTestCurrentTimestamp($stamp('2026-10-13 12:34:00'));
    $chart->SetTestProperty('SourceVariableID', 58001);
    $chart->SetTestProperty('UseVariablePresentation', false);
    $chart->SetTestProperty('Unit', '°C');
    $chart->SetTestProperty('EnableIPSView', true);
    $chart->SetTestProperty('IPSViewUseTileDesign', false);
    $chart->SetTestProperty('IPSViewLowColor', 0x123456);
    $read = static fn (): array => json_decode($chart->GetHeatmapData(), true, 512, JSON_THROW_ON_ERROR);
    $bootstrap = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Comparison HTML must remain within one MiB.');
        assertGatewayGauge(preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $match) === 1, 'Heatmap bootstrap is required.');
        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    try {
        $chart->ApplyChanges();
        $legacy = $read();
        $chart->SetTestProperty('Sources', '{invalid dormant configuration');
        assertGatewayGauge($read() === $legacy && !isset($legacy['panels']), 'Disabled comparison data must not change the legacy model.');
        $chart->SetTestProperty('UseComparisonPanels', true);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        foreach (['average' => 2.0, 'minimum' => -2.0, 'maximum' => 8.0, 'consumption' => 4.0] as $metric => $firstValue) {
            foreach ($sources as $source) {
                $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregationType'] = $metric === 'consumption' ? 1 : 0;
            }
            $chart->SetTestProperty('Metric', $metric);
            $chart->ApplyChanges();
            $before = count($requests);
            $data = $read();
            assertGatewayGauge(
                count($data['panels']) === 4 && $data['cells'] === []
                && $data['panels'][0]['cells'][0]['value'] === $firstValue && count($requests) - $before === 4,
                'Each view must use the existing reducer and actual archive values, sharing at most seven calls.'
            );
            assertGatewayGauge(
                $data['panels'][0]['heatmap']['valueRange']['min'] === $firstValue
                && $data['panels'][3]['heatmap']['valueRange']['max'] > $firstValue
                && count(array_unique(array_column(array_column($data['panels'], 'heatmap'), 'sourceKey'))) === 4,
                'Shared colors use all values; duplicate display names never merge IDs.'
            );
            foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewHeatmap')] as $index => $html) {
                $state = $bootstrap($html);
                assertGatewayGauge($state['status'] === 'ready' && count($state['chart']['panels']) === 4, 'Both outputs publish all four views.');
                if ($index > 0) {
                    assertGatewayGauge($state['chart']['panels'][0]['heatmap']['style']['lowColor'] === '#123456', 'Independent IPSView designs remain intact.');
                }
            }
        }
        $chart->SetTestProperty('Metric', 'average');
        foreach ($sources as $source) {
            $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregationType'] = 0;
        }
        $comparison = [$sources[0], $sources[0] + ['PeriodOffset' => 1], $sources[0]];
        $chart->SetTestProperty('Sources', json_encode($comparison, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $properties = $chart->GetTestProperties();
        $before = count($requests);
        $data = $read();
        assertGatewayGauge(count($requests) - $before === 2 && $data['panels'][1]['cells'][0]['value'] === 77.0
            && $chart->GetTestReferences() === [58001], 'Repeated IDs compare different periods and deduplicate exact requests/references.');
        $tile = $chart->GetVisualizationTile();
        preg_match('#/hook/SymconECharts/state/5000/([a-f0-9]{32})#', $tile, $channel);
        foreach (['tile', 'ipsview'] as $output) {
            $baseKey = $output === 'tile' ? $data['heatmap']['viewKey'] : $bootstrap($chart->GetIPSViewHTML())['chart']['heatmap']['viewKey'];
            $request = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}'] + EChartsDataProtocol::CreateRequest('ipsview.state', [
                'InstanceID' => 5000, 'Channel' => $channel[1], 'WindowOffset' => 1, 'Output' => $output
            ]);
            $response = EChartsDataProtocol::DecodeResponse($chart->ReceiveData(json_encode($request, JSON_THROW_ON_ERROR)), 'ipsview.state');
            $prior = $response['Payload']['State']['chart'];
            assertGatewayGauge($response['Success'] && $prior['heatmap']['viewKey'] === $baseKey
                && $prior['panels'][0]['heatmap']['startDate'] === '2026-08-01'
                && $prior['panels'][1]['heatmap']['startDate'] === '2026-07-01'
                && $chart->GetTestProperties() === $properties, 'Authenticated navigation shifts all views without property writes.');
        }
        // Independent scales permit mixed native units, shared scales fail before any request.
        $GLOBALS['symconTestVariables'][58002]['Presentation']['SUFFIX'] = '%';
        $chart->SetTestProperty('UseVariablePresentation', true);
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 2), JSON_THROW_ON_ERROR));
        $before = count($requests);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 202 && count($requests) === $before, 'Different units must reject a shared scale before loading.');
        $chart->SetTestProperty('SharedColorScale', false);
        $mixed = $read();
        assertGatewayGauge($mixed['panels'][1]['heatmap']['unit'] === '%' && !isset($mixed['panels'][0]['heatmap']['valueRange']), 'Separate scales keep original native units.');
        $chart->SetTestProperty('Range', '12m');
        $before = count($requests);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 202 && count($requests) === $before, 'Fourteen chunks must fail upfront, never silently truncate.');
        $chart->SetTestProperty('Range', '1m');
        $chart->SetTestProperty('UseVariablePresentation', false);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $properties = $chart->GetTestProperties();
        $status = $chart->GetTestStatus();
        IPSModuleStrict::$ParentResponder = static function (): never
        { throw new LogicException('Previews must never load archives.'); };
        $chart->UpdateBarPreviewFromForm(json_encode(['UseComparisonPanels' => true, 'Sources' => $comparison, 'Title' => 'Compare'], JSON_THROW_ON_ERROR));
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && substr_count($svg, 'data-range=') === 3
                && str_contains($svg, '&lt;View &amp; 0&gt;') && !str_contains($svg, '<View'), 'Both synthetic previews retain all escaped view names and periods.');
        }
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $list = findGaugeFormElement($form['elements'], 'Sources');
        assertGatewayGauge(isset($list['form'], $list['onAdd'], $list['onEdit'], $list['onDelete'], $list['onChangeOrder'])
            && $chart->GetTestProperties() === $properties && $chart->GetTestStatus() === $status, 'Native row forms and draft preview events never persist changes.');
        IPSModuleStrict::$ParentResponder = $savedParent;
        $chart->SetTestProperty('UseComparisonPanels', false);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestReferences() === [58001] && !isset($read()['panels']), 'Returning to legacy mode restores its original source and removes comparison references.');
    } finally {
        IPSModuleStrict::$ParentResponder = $savedParent;
        date_default_timezone_set($savedZone);
        foreach ($sources as $source) {
            unset($GLOBALS['symconTestVariables'][$source['VariableID']], $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]);
        }
    }
})();
