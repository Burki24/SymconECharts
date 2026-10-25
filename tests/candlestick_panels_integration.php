<?php

declare(strict_types=1);

$panels = new TestableEChartsCandlestick();
$panels->Create();
$panels->now = strtotime('2026-10-17 12:34:00');
$panels->SetTestProperty('VariableID', 88500);
$panels->SetTestProperty('UseComparisonPanels', true);
$panels->SetTestProperty('Sources', json_encode([
    ['VariableID' => 88500, 'Label' => 'Room <one>'],
    ['VariableID' => 88501, 'Label' => 'Room <one>']
]));
$GLOBALS['symconTestVariables'][88501] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 9999, 'Name' => 'Humidity',
    'Presentation'                                       => ['SUFFIX' => ' %', 'DIGITS' => 0]];
$GLOBALS['symconTestArchiveVariables'][88501] = ['AggregationType' => 0, 'LoggedValues' => [
    ['TimeStamp' => strtotime('2026-10-16 09:00:00'), 'Value' => 80, 'Duration' => 3600],
    ['TimeStamp' => strtotime('2026-10-16 08:00:00'), 'Value' => 40, 'Duration' => 3600]
], 'AggregatedValues' => []];
$panels->SetTestProperty('EnableIPSView', true);
$panels->ApplyChanges();
$panelForm = json_decode($panels->GetConfigurationForm(), true);
assertGatewayGauge(
    findGaugeFormElement($panelForm['elements'], 'Sources') !== null,
    'Named Candlestick panels need an editable source list in the real form.'
);
$panelData = json_decode($panels->GetCandlestickData(), true)['candlestick']['panels'];
assertGatewayGauge(
    count($panelData) === 2 && array_column($panelData, 'variableID') === [88500, 88501]
    && array_column($panelData, 'unit') === ['°C', '%'] && $panels->GetTestReferences() === [88500, 88501]
    && $panelData[1]['data'][6]['statistics'] === [40.0, 80.0, 40.0, 80.0]
    && $panelData[0]['data'][6]['statistics'] === [2.0, 3.0, -4.0, 9.0],
    'Panels retain distinct ID identities even with equal labels, original units and raw statistics.'
);
$panelQueries = $GLOBALS['symconTestArchiveQueryCount'];
$panels->GetVisualizationTile();
$panels->GetIPSViewHTML();
assertGatewayGauge($GLOBALS['symconTestArchiveQueryCount'] === $panelQueries, 'Both outputs reuse all cached panels.');
$panels->UpdateBarPreviewFromForm(json_encode(['UseComparisonPanels' => true, 'Sources' => [
    ['VariableID' => 88500, 'Label' => 'Draft <panel>'], ['VariableID' => 88501, 'Label' => 'Humidity']
]]));
foreach (array_slice($panels->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')));
    $document = new DOMDocument();
    assertGatewayGauge($document->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'Draft &lt;panel&gt;')
        && str_contains($svg, 'data-panel-count="2"'), 'Both previews show escaped names and all panels.');
}
assertGatewayGauge($GLOBALS['symconTestArchiveQueryCount'] === $panelQueries, 'Panel previews never read archives.');
foreach ([[], array_fill(0, 5, ['VariableID' => 88500]), [['VariableID' => '88500']],
    [['VariableID' => 88500, 'Label' => str_repeat('x', 121)]], [null]] as $badSources) {
    $panels->SetTestProperty('Sources', json_encode($badSources));
    assertGatewayGauge(!json_decode($panels->GetCandlestickDiagnostic(), true)['valid'], 'Invalid panel drafts must be rejected.');
}
$four = [];
for ($index = 0; $index < 4; ++$index) {
    $id = 88500 + $index;
    if ($index > 1) {
        $GLOBALS['symconTestVariables'][$id] = $GLOBALS['symconTestVariables'][88500];
        $GLOBALS['symconTestArchiveVariables'][$id] = $GLOBALS['symconTestArchiveVariables'][88500];
    }
    $four[] = ['VariableID' => $id, 'Label' => str_repeat('Long name ', 12)];
}
$panels->SetTestProperty('Sources', json_encode($four));
$panels->SetTestProperty('Interval', 'hour');
$panels->SetTestProperty('Range', '30d');
$panels->ApplyChanges();
assertGatewayGauge(
    count(json_decode($panels->GetCandlestickData(), true)['candlestick']['panels'][3]['data']) === 720
    && strlen($panels->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($panels->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Four complete 720-interval panels and long labels remain inside both output budgets.'
);
$panels->SetTestProperty('Range', 'custom');
$panels->SetTestProperty('CustomRangeValue', 1);
$panels->SetTestProperty('CustomRangeUnit', 'minute');
$panels->SetTestProperty('Interval', 'day');
$panels->ApplyChanges();
$beforeEmpty = $GLOBALS['symconTestArchiveQueryCount'];
$emptyPanels = json_decode($panels->GetCandlestickData(), true)['candlestick']['panels'];
assertGatewayGauge(count($emptyPanels) === 4 && $emptyPanels[3]['data'] === []
    && $GLOBALS['symconTestArchiveQueryCount'] === $beforeEmpty, 'Empty common periods never query any panel.');
$panels->SetTestProperty('Range', '7d');
$panels->SetTestProperty('Sources', json_encode([['VariableID' => 88500], ['VariableID' => 88500]]));
assertGatewayGauge(!json_decode($panels->GetCandlestickDiagnostic(), true)['valid'], 'Duplicate IDs must be rejected.');
$panels->SetTestProperty('UseComparisonPanels', false);
$panels->ApplyChanges();
assertGatewayGauge(
    $panels->GetTestReferences() === [88500]
    && !isset(json_decode($panels->GetCandlestickData(), true)['candlestick']['panels']),
    'Turning panels off restores the unchanged legacy source without migration.'
);
echo "Candlestick comparison panels verified.\n";
