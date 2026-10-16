<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsRadar/module.php';

// Real module and shared Gateway, isolated numeric current-value fixtures; no archive requirements.
$radarAxes = [
    ['Key' => 'temperature', 'Label' => '<Temperature>', 'Minimum' => -20, 'Maximum' => 40, 'Unit' => '°C', 'Decimals' => 1],
    ['Key' => 'humidity', 'Label' => 'Humidity', 'Minimum' => 0, 'Maximum' => 100, 'Unit' => '%', 'Decimals' => 0],
    ['Key' => 'co2', 'Label' => 'CO2', 'Minimum' => 0, 'Maximum' => 2000, 'Unit' => 'ppm', 'Decimals' => 0]
];
$radarSources = [];
foreach ([-5.0, 0.0, 2500.0] as $index => $value) {
    $id = 88100 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => $value, 'Name' => 'Same name'];
    $radarSources[] = ['Group' => 'Room <one>', 'Axis' => $radarAxes[$index]['Key'], 'VariableID' => $id];
}
$radar = new EChartsRadar();
$radar->Create();
$radar->SetTestProperty('Axes', json_encode($radarAxes, JSON_THROW_ON_ERROR));
$radar->SetTestProperty('Sources', json_encode($radarSources, JSON_THROW_ON_ERROR));
$radar->SetTestProperty('Title', 'Current room comparison');
$radar->SetTestProperty('EnableIPSView', true);
$radar->SetTestProperty('IPSViewUseTileDesign', false);
$radar->SetTestProperty('IPSViewShape', 'circle');
$radar->SetTestProperty('IPSViewAxisColor', 0x123456);
$radarQueries = $GLOBALS['symconTestArchiveQueryCount'];
$radar->ApplyChanges();
$radarData = json_decode($radar->GetRadarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $radar->GetTestStatus() === IS_ACTIVE && $radar->GetTestReferences() === [88100, 88101, 88102]
    && $radarData['family'] === 'radar' && $radarData['radar']['data'][0]['values'] === [-5.0, 0.0, 2500.0]
    && $radarData['radar']['data'][0]['percentValues'] === [25.0, 0.0, 100.0]
    && $radarData['radar']['clippedCount'] === 1 && $radarQueries === $GLOBALS['symconTestArchiveQueryCount'],
    'Radar must preserve negative/zero/raw values and normalize explicit axes without archive queries.'
);
$radarTile = $radar->GetVisualizationTile();
$radarIPSView = $radar->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($radarTile, '"shape":"polygon"') && str_contains($radarIPSView, '"shape":"circle"')
    && str_contains($radarIPSView, '"axisColor":"#123456"')
    && is_string($radar->GetTestVariableValue('IPSViewRadar')) && $radar->GetTestVariableValue('IPSViewRadar') !== ''
    && strlen($radarTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($radarIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Radar must publish bounded standalone WebContent and independent output designs.'
);
$radarParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json): string
{
    throw new RuntimeException('Radar previews must not call the parent or change transport status.');
};
try {
    $radarForm = json_decode($radar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $radarDraftAxes = $radarAxes;
    $radarDraftAxes[0]['Label'] = 'Unsaved axis';
    $radar->UpdateBarPreviewFromForm(json_encode(['Axes' => $radarDraftAxes, 'Title' => 'Unsaved title', 'AxisColor' => 0xABCDEF], JSON_THROW_ON_ERROR));
} finally {
    IPSModuleStrict::$ParentResponder = $radarParent;
}
$radarSVG = base64_decode(substr(findGaugeFormElement($radarForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
$radarDraftSVG = base64_decode(substr(array_slice($radar->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($radarSVG) && str_contains($radarSVG, 'data-group="Room &lt;one&gt;"')
    && str_contains($radarSVG, '&lt;Temperature&gt;') && !str_contains($radarSVG, 'Example data')
    && is_string($radarDraftSVG) && str_contains($radarDraftSVG, 'Unsaved axis') && str_contains($radarDraftSVG, '#ABCDEF')
    && $radar->GetTestStatus() === IS_ACTIVE
    && json_decode($radar->GetRadarData(), true)['radar']['axes'][0]['Label'] === '<Temperature>',
    'Radar previews must use actual escaped values, respect draft design and leave saved configuration/status untouched.'
);
if (class_exists(DOMDocument::class)) {
    $radarXML = new DOMDocument();
    assertGatewayGauge($radarXML->loadXML($radarSVG, LIBXML_NONET) && $radarXML->loadXML($radarDraftSVG, LIBXML_NONET), 'Radar preview SVG must be valid XML.');
}
$radarAxesField = findGaugeFormElement($radarForm['elements'], 'Axes');
$radarValuesField = findGaugeFormElement($radarForm['elements'], 'Sources');
assertGatewayGauge(
    !isset($radarAxesField['onChange']) && str_contains($radarAxesField['onEdit'], 'foreach ($Axes as')
    && $radarValuesField['form'][1]['options'][1]['value'] === 'temperature'
    && findGaugeFormElement($radarForm['elements'], 'IPSViewShape')['enabled'] === true,
    'Radar must use supported List callbacks, current axis selectors and a separate IPSView designer.'
);
$radar->UpdateAxisOptions(json_encode([...$radarAxes, ['Key' => 'draft', 'Label' => 'Unsaved']], JSON_THROW_ON_ERROR));
$radarSelectorUpdates = array_slice($radar->GetTestFormUpdates(), -2);
assertGatewayGauge(
    json_decode($radarSelectorUpdates[1]['Value'], true)[1]['options'][4]['value'] === 'draft',
    'Radar selectors must offer unsaved axes without saving them.'
);
foreach (['', 'IPSView'] as $radarPrefix) {
    foreach (['Shape'        => ['invalid', 'polygon'], 'SplitNumber' => [0, 5], 'LineWidth' => [9, 2],
        'AreaOpacityPercent' => [101, 25], 'AxisColor' => [-2, -1], 'AxisFontSizePercent' => [201, 100]] as $name => [$invalid, $valid]) {
        $radar->SetTestProperty($radarPrefix . $name, $invalid);
        $radar->ApplyChanges();
        assertGatewayGauge($radar->GetTestStatus() === 202, 'Radar must reject invalid design: ' . $radarPrefix . $name);
        $radar->SetTestProperty($radarPrefix . $name, $valid);
        $radar->ApplyChanges();
        assertGatewayGauge($radar->GetTestStatus() === IS_ACTIVE, 'Radar must recover after correcting design: ' . $radarPrefix . $name);
    }
}
$GLOBALS['symconTestCopyTarget'] = $radar;
try {
    $radar->SetTestProperty('LineWidth', 6);
    $radar->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($radar->GetIPSViewHTML(), '"lineWidth":6'), 'Radar copy action must initialize the independent IPSView design.');
$radarUpdateCount = count($radar->GetTestVisualizationUpdates());
$GLOBALS['symconTestVariables'][88100]['Value'] = 10.0;
$radar->MessageSink(time(), 88100, VM_UPDATE, []);
assertGatewayGauge(
    count($radar->GetTestVisualizationUpdates()) > $radarUpdateCount
    && json_decode($radar->GetRadarData(), true)['radar']['data'][0]['percentValues'][0] === 50.0,
    'Radar must update from VM_UPDATE without reloading the HTML document.'
);
assertTransientRetry($radar, 'Radar');
$radar->SetTestProperty('Sources', json_encode(array_slice($radarSources, 0, 2), JSON_THROW_ON_ERROR));
$radar->ApplyChanges();
assertGatewayGauge($radar->GetTestStatus() === 201, 'Radar must reject incomplete groups instead of injecting zero values.');
$radar->SetTestProperty('Axes', '[]');
$radar->SetTestProperty('Sources', '[]');
$radar->ApplyChanges();
assertGatewayGauge($radar->GetTestReferences() === [], 'Removing Radar sources must unregister references.');
$radarMaximum = new EChartsRadar();
$radarMaximum->Create();
$radarMaximumAxes = $radarMaximumSources = [];
for ($index = 0; $index < 10; ++$index) {
    $radarMaximumAxes[] = ['Key' => 'axis' . $index, 'Label' => str_repeat('A', 80), 'Minimum' => 0, 'Maximum' => 100];
    for ($group = 0; $group < 8; ++$group) {
        $radarMaximumSources[] = ['Group' => str_repeat('G', 79) . $group, 'Axis' => 'axis' . $index, 'VariableID' => 88100];
    }
}
$radarMaximum->SetTestProperty('Axes', json_encode($radarMaximumAxes, JSON_THROW_ON_ERROR));
$radarMaximum->SetTestProperty('Sources', json_encode($radarMaximumSources, JSON_THROW_ON_ERROR));
$radarMaximum->ApplyChanges();
assertGatewayGauge(
    $radarMaximum->GetTestStatus() === IS_ACTIVE && $radarMaximum->GetTestReferences() === [88100]
    && strlen($radarMaximum->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($radarMaximum->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($radarMaximum->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Maximum Radar configurations must deduplicate subscriptions and fit the output limit.'
);
$radarLocale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsRadar/locale.json'), true)['translations']['de'];
$radarStaticForm = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsRadar/form.json'), true);
$radarCaptions = function (array $items) use (&$radarCaptions, $radarLocale): void
{
    foreach ($items as $key => $item) {
        if ($key === 'caption' && is_string($item)) {
            assertGatewayGauge(isset($radarLocale[$item]), 'Radar form caption needs German translation: ' . $item);
        } elseif (is_array($item)) {
            $radarCaptions($item);
        }
    }
};
$radarCaptions($radarStaticForm);
foreach ([88100, 88101, 88102] as $id) {
    unset($GLOBALS['symconTestVariables'][$id]);
}
echo "Radar Gateway, output and form integration verified.\n";
