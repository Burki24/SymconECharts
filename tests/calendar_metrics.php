<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

// Included by calendar_integration.php; all sources are synthetic.
(static function () use ($calendar, $calendarID, $calendarRequest): void
{
    $calendar->SetTestProperty('SourceVariableID', $calendarID);
    foreach (['average' => [8.25, -7.5, 0.0], 'minimum' => [7.0, -9.0, -1.0], 'maximum' => [11.0, -4.0, 1.0]] as $calendarMetric => $expected) {
        $calendar->SetTestProperty('Metric', $calendarMetric);
        $calendar->ApplyChanges();
        $result = json_decode($calendar->GetCalendarData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $calendar->GetTestStatus() === IS_ACTIVE
            && ($result['calendar']['metric'] ?? null) === $calendarMetric
            && array_column($result['calendar']['data'], 'value') === $expected,
            'Calendar must select distinct daily archive values for ' . $calendarMetric . '.'
        );
    }
    $calendarCounterID = 88002;
    $GLOBALS['symconTestVariables'][$calendarCounterID] = [
        'VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 9876.0, 'Name' => 'Example <counter>'
    ];
    $GLOBALS['symconTestArchiveVariables'][$calendarCounterID] = ['AggregationType' => 1, 'LoggedValues' => [], 'AggregatedValues' => [
        ['TimeStamp' => strtotime('2026-10-15'), 'Avg' => 0.0, 'Min' => 900.0, 'Max' => 940.0],
        ['TimeStamp' => strtotime('2026-10-13'), 'Avg' => 12.5, 'Min' => 810.0, 'Max' => 890.0],
        ['TimeStamp' => strtotime('2025-12-31'), 'Avg' => 7.25, 'Min' => 100.0, 'Max' => 155.0]
    ]];
    $calendar->SetTestProperty('Metric', 'consumption');
    $calendar->SetTestProperty('SourceVariableID', $calendarCounterID);
    $calendar->ApplyChanges();
    $result = json_decode($calendar->GetCalendarData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        $result['calendar']['metric'] === 'consumption'
        && array_column($result['calendar']['data'], 'value') === [12.5, 0.0],
        'Calendar consumption must use Symcon counter Avg (positive deltas), not raw counter Min/Max/endpoints.'
    );
    foreach ([$calendar->GetVisualizationTile(), $calendar->GetIPSViewHTML()] as $document) {
        assertGatewayGauge(
            str_contains($document, '"metric":"consumption"') && strlen($document) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'Calendar consumption must reach both bounded output documents.'
        );
    }
    $calendarNavigation = EChartsDataProtocol::DecodeResponse(
        $calendar->ReceiveData(json_encode($calendarRequest, JSON_THROW_ON_ERROR)),
        EChartsDataProtocol::OPERATION_IPSVIEW_STATE
    );
    assertGatewayGauge(
        $calendarNavigation['Success']
        && $calendarNavigation['Payload']['State']['chart']['calendar']['metric'] === 'consumption'
        && $calendarNavigation['Payload']['State']['chart']['calendar']['data'] === [['date' => '2025-12-31', 'value' => 7.25]],
        'Read-only year navigation must preserve the selected Calendar metric.'
    );
    foreach (['average', 'minimum', 'maximum'] as $calendarMetric) {
        $calendar->SetTestProperty('Metric', $calendarMetric);
        $calendar->ApplyChanges();
        assertGatewayGauge(
            $calendar->GetTestStatus() === 204
            && str_contains($calendar->GetVisualizationTile(), 'not a counter'),
            'Calendar standard metrics must visibly reject counter archives: ' . $calendarMetric
        );
    }
    $calendar->SetTestProperty('Metric', 'consumption');
    $calendar->SetTestProperty('SourceVariableID', $calendarID);
    $calendar->ApplyChanges();
    assertGatewayGauge(
        $calendar->GetTestStatus() === 204
        && str_contains($calendar->GetIPSViewHTML(), 'consumption requires a counter'),
        'Calendar consumption must visibly reject standard archives without changing the source archive.'
    );
    $calendar->SetTestProperty('SourceVariableID', $calendarCounterID);
    $calendar->ApplyChanges();
    $calendarMetricParent = IPSModuleStrict::$ParentResponder;
    try {
        foreach (['ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'auto', 'VariableType' => 0, 'Truncated' => true, 'Points' => [[strtotime('2026-10-13'), -1.0]]] as $field => $value) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($calendarMetricParent, $field, $value): string
            {
                $response = json_decode($calendarMetricParent($json), true, 512, JSON_THROW_ON_ERROR);
                $response['Payload'][$field] = $value;
                return json_encode($response, JSON_THROW_ON_ERROR);
            };
            try {
                $calendar->GetCalendarData();
                throw new LogicException('Invalid Calendar metric response was accepted: ' . $field);
            } catch (RuntimeException | InvalidArgumentException) {
                assertGatewayGauge(
                    $calendar->GetTestStatus() === 204,
                    'Calendar must reject malformed metric data: ' . $field
                );
            }
        }
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Calendar metric preview must not query the archive.');
        };
        $metricForm = json_decode($calendar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $calendar->UpdateBarPreviewFromForm(json_encode(['Metric' => 'maximum'], JSON_THROW_ON_ERROR));
        $updates = array_slice($calendar->GetTestFormUpdates(), -2);
        foreach ($updates as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                is_string($svg) && str_contains($svg, 'Example data · Daily maximum'),
                'Calendar preview must display its unsaved metric for both outputs.'
            );
        }
        $metricControl = findGaugeFormElement($metricForm['elements'], 'Metric');
        assertGatewayGauge(
            $metricControl['type'] === 'Select' && count($metricControl['options']) === 4
            && str_contains($metricControl['onChange'], '$Metric'),
            'Calendar metric needs one shared source-level selector with a live preview callback.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $calendarMetricParent;
    }
    $calendar->SetTestProperty('Metric', 'unknown');
    $calendar->ApplyChanges();
    assertGatewayGauge($calendar->GetTestStatus() === 202, 'Unknown Calendar metrics must invalidate configuration.');
    $calendar->SetTestProperty('SourceVariableID', $calendarID);
    $calendar->SetTestProperty('Metric', 'average');
    $calendar->ApplyChanges();
    assertGatewayGauge(
        $calendar->GetTestStatus() === IS_ACTIVE
        && json_decode($calendar->GetCalendarData(), true)['calendar']['data'][0]['value'] === 8.25,
        'Calendar must recover and not return cached consumption/minimum/maximum for a changed metric.'
    );
    unset($GLOBALS['symconTestVariables'][$calendarCounterID], $GLOBALS['symconTestArchiveVariables'][$calendarCounterID]);
})();
