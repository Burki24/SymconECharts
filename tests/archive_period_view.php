<?php

declare(strict_types=1);

use eCharts\EChartsDataProtocol;

/** Exercise viewer-local ranges on an existing valid fixed-period family fixture. */
function verifyArchivePeriodView(IPSModuleStrict $chart): void
{
    $chart = clone $chart;
    $chart->SetTestProperty('EnableIPSView', true);
    $chart->SetTestProperty('IPSViewUseTileDesign', false);
    $chart->SetTestProperty('EnablePeriodSelection', true);
    $chart->SetTestProperty('IPSViewEnablePeriodSelection', false);
    $html = $chart->GetVisualizationTile();
    assertGatewayGauge(preg_match('~id="echarts-period-picker" data-path="[^"]*/state/(\d+)/([0-9a-f]{32})~', $html, $channel) === 1
        && !str_contains($chart->GetIPSViewHTML(), 'id="echarts-period-picker"'), 'The period control follows each output designer.');
    $chart->SetTestProperty('IPSViewEnablePeriodSelection', true);
    assertGatewayGauge(str_contains($chart->GetIPSViewHTML(), 'id="echarts-period-picker"'), 'IPSView can opt into its own period control.');
    $send = static function (string $output, string $period) use ($chart, $channel): ?array
    {
        $request = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}'] + EChartsDataProtocol::CreateRequest(
            EChartsDataProtocol::OPERATION_IPSVIEW_STATE,
            ['InstanceID' => (int) $channel[1], 'Channel' => $channel[2], 'WindowOffset' => 0, 'Output' => $output, 'Period' => $period]
        );
        $reply = $chart->ReceiveData(json_encode($request, JSON_THROW_ON_ERROR));
        return $reply === '' ? null : EChartsDataProtocol::DecodeResponse($reply, EChartsDataProtocol::OPERATION_IPSVIEW_STATE)['Payload']['State'];
    };
    $properties = $chart->GetTestProperties();
    $tile = $send('tile', '24h');
    $ips = $send('ipsview', '7d');
    $configured = $send('tile', '');
    assertGatewayGauge($tile['status'] === 'ready' && $ips['status'] === 'ready'
        && $tile['chart']['range']['key'] === '24h' && $ips['chart']['range']['key'] === '7d'
        && $ips['chart']['range']['startTimestamp'] < $tile['chart']['range']['startTimestamp']
        && $configured['chart']['range']['key'] === 'absolute' && $send('tile', 'unexpected') === null
        && $chart->GetTestProperties() === $properties, 'Separate period requests use distinct archive windows and preserve the configured dates.');
    $parent = IPSModuleStrict::$ParentResponder;
    $status = $chart->GetTestStatus();
    try {
        IPSModuleStrict::$ParentResponder = static fn (): string => '';
        $failed = $send('ipsview', '30d');
        assertGatewayGauge(
            $failed['status'] === 'error' && $chart->GetTestStatus() === $status,
            'A failed temporary period must not change the regular chart status.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    if ($chart instanceof EChartsCandlestick) {
        $chart->SetTestProperty('Interval', 'fifteen-minutes');
        assertGatewayGauge(!str_contains($chart->GetVisualizationTile(), '<option value="30d">')
            && $send('tile', '30d') === null, 'The 720-candle ceiling is honored when listing and requesting presets.');
        $chart->SetTestProperty('Interval', 'week');
        assertGatewayGauge(!str_contains($chart->GetVisualizationTile(), '<option value="24h">')
            && $send('tile', '24h') === null, 'A 24-hour window cannot offer a completed ISO week.');
    }
}
