<?php

declare(strict_types=1);

use eCharts\EChartsDataProtocol;

/** Exercise both chart families via the authenticated read-only transport with synthetic archives. */
function verifyCartesianPeriodSelection(IPSModuleStrict $module, int $now, array &$requests): void
{
    $beforeRequests = $requests;
    $module->SetTestProperty('EnableIPSView', true);
    $module->SetTestProperty('IPSViewUseTileDesign', false);
    $module->SetTestProperty('IPSViewUseTileTimeSettings', false);
    $module->SetTestProperty('IPSViewRange', 'today');
    $module->SetTestProperty('EnablePeriodSelection', true);
    $module->ApplyChanges();
    $tile = $module->GetVisualizationTile();
    $ips = $module->GetIPSViewHTML();
    assertGatewayGauge(
        str_contains($tile, 'id="echarts-period-picker"') && !str_contains($ips, 'id="echarts-period-picker"'),
        'The period menu must respect the independent IPSView design.'
    );
    $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $preview = $module instanceof EChartsTimeSeries ? 'TimeSeriesPreview' : 'BarPreview';
    foreach ([$preview => true, 'IPSView' . $preview => false] as $name => $expected) {
        $image = findGaugeFormElement($form['elements'], $name)['image'];
        $svg = base64_decode(substr($image, strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            str_contains($svg, 'data-preview-period-selection="true"') === $expected,
            'Designer previews must match each output and keep space for plotted values.'
        );
    }
    assertGatewayGauge(preg_match('#/hook/eCharts/state/\d+/([a-f0-9]{32})#', $tile, $match) === 1, 'Tile period requests need the targeted channel.');
    $state = static function (string $output, string $period) use ($module, $match): array
    {
        $operation = EChartsDataProtocol::OPERATION_IPSVIEW_STATE;
        $wire = $module->ReceiveData(json_encode(['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
            + EChartsDataProtocol::CreateRequest($operation, [
                'InstanceID' => $module->InstanceID, 'Channel' => $match[1], 'Output' => $output, 'Period' => $period
            ]), JSON_THROW_ON_ERROR));
        return $wire === '' ? [] : EChartsDataProtocol::DecodeResponse($wire, $operation)['Payload']['State'];
    };
    $current = $module->GetTestProperties();
    $year = $state('tile', 'current-year');
    $today = (new DateTimeImmutable('@' . $now))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0);
    $jan = $today->setDate((int) $today->format('Y'), 1, 1)->getTimestamp();
    assertGatewayGauge(
        ($year['status'] ?? null) === 'ready'
        && $year['chart']['range']['startTimestamp'] === $jan
        && $year['chart']['range']['pointBudget'] === 200
        && $year['chart']['range']['key'] === 'current-year'
        && end($requests)['Payload']['StartTimestamp'] === $jan,
        'The calendar year must query the archive from local January 1 with the existing point budget.'
    );
    $queries = count($requests);
    assertGatewayGauge(
        $state('ipsview', 'current-year') === [] && $state('tile', 'unknown') === [] && count($requests) === $queries,
        'Disabled outputs and unsupported periods must not read any archives.'
    );
    assertGatewayGauge(
        $state('tile', '')['chart']['range']['key'] === '24h' && $module->GetTestProperties() === $current,
        'Returning to the configured period must preserve every property.'
    );
    $module->SetTestProperty('IPSViewEnablePeriodSelection', true);
    $ipsRange = $state('ipsview', '6h')['chart']['range'];
    assertGatewayGauge($ipsRange['key'] === '6h' && $ipsRange['startTimestamp'] === $now - 21600
        && $state('ipsview', '')['chart']['range']['key'] === 'today', 'IPSView must use its own start period and share the bounded query planner.');
    $responder = IPSModuleStrict::$ParentResponder;
    try {
        IPSModuleStrict::$ParentResponder = static fn (string $json): string => '';
        $status = $module->GetTestStatus();
        assertGatewayGauge(($state('tile', 'current-year')['status'] ?? '') === 'error'
            && $module->GetTestStatus() === $status, 'A failed selected range must preserve the instance status.');
    } finally {
        IPSModuleStrict::$ParentResponder = $responder;
    }
    if ($module instanceof EChartsTimeSeries) {
        $module->SetTestProperty('DataMode', 'realtime');
        assertGatewayGauge(!str_contains($module->GetVisualizationTile(), 'id="echarts-period-picker"')
            && $state('tile', '6h') === [], 'Archive-free realtime charts must not offer or accept historical views.');
    }
    $requests = $beforeRequests;
}
