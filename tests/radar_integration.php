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
$radar->SetTestProperty('GroupColors', '[{"Group":"Room <one>","Color":16711680}]');
$radar->SetTestProperty('IPSViewGroupColors', '[{"Group":"Room <one>","Color":65280}]');
$radar->SetTestProperty('LineType', 'dashed');
$radar->SetTestProperty('Symbol', 'diamond');
$radar->SetTestProperty('SymbolRotate', 30);
$radar->SetTestProperty('SymbolBorderWidth', 2);
$radar->SetTestProperty('ShowValues', true);
$radar->SetTestProperty('ShowAxisRange', false);
$radar->SetTestProperty('AreaShadowBlur', 8);
$radar->SetTestProperty('LegendPosition', 'top');
$radar->ApplyChanges();
$radarDecorated = json_decode($radar->GetRadarData(), true)['radar'];
assertGatewayGauge(
    $radarDecorated['style']['groupColors'] === ['Room <one>' => '#FF0000']
    && $radarDecorated['style']['lineType'] === 'dashed' && $radarDecorated['style']['showValues'] === true
    && $radarDecorated['data'][0]['values'] === [-5.0, 0.0, 2500.0]
    && str_contains($radar->GetIPSViewHTML(), '#00FF00'),
    'Radar design extensions must preserve raw values and independent group colors.'
);
$radarDecoratedForm = json_decode($radar->GetConfigurationForm(), true);
$radarDecoratedSVG = base64_decode(substr(findGaugeFormElement($radarDecoratedForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($radarDecoratedSVG, '#FF0000') && str_contains($radarDecoratedSVG, 'data-value="2500"')
    && str_contains($radarDecoratedSVG, 'stroke-dasharray="6 4"') && str_contains($radarDecoratedSVG, 'rotate(-30')
    && str_contains($radarDecoratedSVG, 'filter="url(#area-shadow-0)"'),
    'Radar SVG previews must show individual colors, original values, line types, symbol rotation and area shadows.'
);
$radarDraftSources = array_map(static fn (array $row): array => [...$row, 'Group' => 'Unsaved group'], $radarSources);
$radar->UpdateBarPreviewFromForm(json_encode(['Sources' => $radarDraftSources,
    'GroupColors'                                       => [['Group' => 'Unsaved group', 'Color' => 0xABCDEF]], 'LineType' => 'dotted', 'Symbol' => 'triangle'], JSON_THROW_ON_ERROR));
$radarColorUpdates = array_values(array_filter($radar->GetTestFormUpdates(), static fn (array $update): bool => $update['Field'] === 'GroupColors' && $update['Parameter'] === 'form'));
$radarColorDraftSVG = base64_decode(substr(array_slice($radar->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    json_decode(end($radarColorUpdates)['Value'], true)[0]['options'][1]['value'] === 'Unsaved group'
    && str_contains($radarColorDraftSVG, '#ABCDEF') && str_contains($radarColorDraftSVG, 'stroke-dasharray="2 3"')
    && json_decode($radar->GetRadarData(), true)['radar']['style']['groupColors'] === ['Room <one>' => '#FF0000'],
    'Both color selectors and actual SVG design must reflect unsaved groups without persisting draft changes.'
);
if (class_exists(DOMDocument::class)) {
    assertGatewayGauge($radarXML->loadXML($radarDecoratedSVG, LIBXML_NONET) && $radarXML->loadXML($radarColorDraftSVG, LIBXML_NONET), 'Decorated Radar previews must remain valid XML.');
}
foreach (['', 'IPSView'] as $radarPrefix) {
    foreach (['LineType'          => ['invalid', 'dashed'], 'Symbol' => ['path://unsafe', 'diamond'], 'SymbolRotate' => [181, 30],
        'SymbolBorderWidth'       => [9, 2], 'SymbolOpacityPercent' => [-1, 100], 'AxisLineWidth' => [9, 1],
        'AxisLineOpacityPercent'  => [101, 50], 'SplitLineWidth' => [-1, 1], 'SplitLineType' => ['invalid', 'solid'],
        'SplitLineOpacityPercent' => [101, 40], 'SplitAreaColor1' => [-2, -1], 'SplitAreaColor2' => [0x1000000, -1],
        'SplitAreaOpacityPercent' => [101, 100], 'ValueFontSizePercent' => [49, 100], 'ValueColor' => [-2, -1],
        'ValueLabelPosition'      => ['invalid', 'top'], 'ValueLabelDistance' => [31, 5], 'LegendFontSizePercent' => [201, 100],
        'LegendPosition'          => ['left', 'top'], 'LegendColor' => [-2, -1], 'HoverFocus' => ['invalid', 'self'],
        'AreaShadowBlur'          => [21, 8], 'AreaShadowColor' => [-2, -1],
        'GroupColors'             => ['[{"Group":"same","Color":1},{"Group":"same","Color":2}]', '[]']] as $name => [$invalid, $valid]) {
        $radar->SetTestProperty($radarPrefix . $name, $invalid);
        $radar->ApplyChanges();
        assertGatewayGauge($radar->GetTestStatus() === 202, 'Radar must reject invalid extension: ' . $radarPrefix . $name);
        $radar->SetTestProperty($radarPrefix . $name, $valid);
        $radar->ApplyChanges();
        assertGatewayGauge($radar->GetTestStatus() === IS_ACTIVE, 'Radar must recover after correcting extension: ' . $radarPrefix . $name);
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
assertGatewayGauge(str_contains($radar->GetIPSViewHTML(), '"symbol":"diamond"')
    && str_contains($radar->GetIPSViewHTML(), '"showValues":true'), 'Copying Radar design must include the new options.');
$radarRepeatedSources = [...$radarSources, ...array_map(static fn (array $row): array => [...$row, 'Group' => 'Same values'], $radarSources)];
foreach ([true, false] as $hide) {
    $radar->UpdateBarPreviewFromForm(json_encode(['Sources' => $radarRepeatedSources, 'ShowValues' => true,
        'HideOverlappingValueLabels'                        => $hide, 'GroupColors' => []], JSON_THROW_ON_ERROR));
    $svg = base64_decode(substr(array_slice($radar->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(substr_count($svg, 'data-value=') === ($hide ? 3 : 6), 'Radar preview must honor the overlap switch while retaining both groups.');
    assertGatewayGauge(substr_count($svg, 'data-group=') === 2, 'Suppressing labels must not remove Radar groups.');
}
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
$radarMaximum->SetTestProperty('GroupColors', json_encode(array_map(static fn (int $group): array => ['Group' => str_repeat('G', 79) . $group, 'Color' => $group], range(0, 7)), JSON_THROW_ON_ERROR));
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
