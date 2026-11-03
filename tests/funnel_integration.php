<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsFunnel/module.php';

final class TestableEChartsFunnel extends EChartsFunnel
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsFunnel/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsFunnel/visualization/' . $filename);
    }
}

$funnelSources = [];
for ($index = 0; $index < 16; ++$index) {
    $id = 88300 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                            => 160.0 - $index * 10, 'Name' => 'Same <name>', 'Presentation' => ['SUFFIX' => ' W', 'DIGITS' => 1]];
    $funnelSources[] = ['VariableID' => $id, 'Label' => str_repeat('Stufe ', 19) . '<one>', 'Color' => $index === 0 ? 0xABCDEF : -1];
}
$funnel = new TestableEChartsFunnel();
$funnel->Create();
$funnel->SetTestProperty('Sources', json_encode($funnelSources, JSON_THROW_ON_ERROR));
$funnel->SetTestProperty('Title', 'Current power');
$funnel->SetTestProperty('EnableIPSView', true);
$funnel->SetTestProperty('IPSViewUseTileDesign', false);
$funnel->SetTestProperty('IPSViewOrientation', 'horizontal');
$funnel->SetTestProperty('IPSViewLabelColor', 0x123456);
$funnel->ApplyChanges();
$funnelData = json_decode($funnel->GetFunnelData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $funnel->GetTestStatus() === IS_ACTIVE && $funnel->GetTestReferences() === range(88300, 88315)
    && $funnelData['family'] === 'funnel' && $funnelData['funnel']['unit'] === 'W'
    && count($funnelData['funnel']['data']) === 16 && $funnelData['funnel']['data'][0]['value'] === 160.0
    && $funnelData['funnel']['data'][0]['timestamp'] === 1780000000
    && $funnelData['funnel']['style']['sortOrder'] === 'configured'
    && $funnelData['funnel']['data'][0]['id'] !== $funnelData['funnel']['data'][1]['id']
    && $funnelData['funnel']['data'][0]['label'] === $funnelData['funnel']['data'][1]['label']
    && in_array(['SenderID' => 88300, 'Message' => VM_UPDATE], $funnel->GetTestMessages(), true),
    'Funnel must use current Gateway values with source-ID identity, same units and update subscriptions.'
);
$funnelTile = $funnel->GetVisualizationTile();
$funnelIPSView = $funnel->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($funnelTile, '"orientation":"vertical"') && str_contains($funnelIPSView, '"orientation":"horizontal"')
    && str_contains($funnelIPSView, '"labelColor":"#123456"') && str_contains($funnelTile, '"color":"#ABCDEF"')
    && strlen($funnelTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($funnelIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
    && is_string($funnel->GetTestVariableValue('IPSViewFunnel')) && $funnel->GetTestVariableValue('IPSViewFunnel') !== '',
    'Maximum Funnel documents must stay below 1 MB and publish independent IPSView WebContent.'
);
$funnelParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json): string
{ throw new RuntimeException('Preview must not query the Gateway.'); };
try {
    $funnelForm = json_decode($funnel->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $funnel->UpdateBarPreviewFromForm(json_encode(['Title' => 'Unsaved title', 'BorderColor' => 0xFEDCBA,
        'Orientation'                                      => 'horizontal', 'Sources' => array_slice($funnelSources, 0, 3)], JSON_THROW_ON_ERROR));
} finally {
    IPSModuleStrict::$ParentResponder = $funnelParent;
}
$funnelSVG = base64_decode(substr(findGaugeFormElement($funnelForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
$funnelDraftSVG = base64_decode(substr(array_slice($funnel->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($funnelSVG) && !str_contains($funnelSVG, 'Example data') && substr_count($funnelSVG, 'data-stage=') === 16
    && str_contains($funnelDraftSVG, 'Unsaved title') && str_contains($funnelDraftSVG, '#FEDCBA') && str_contains($funnelDraftSVG, '&lt;one&gt;')
    && $funnel->GetTestStatus() === IS_ACTIVE && strlen(json_encode($funnelForm, JSON_THROW_ON_ERROR)) < SYMCON_OUTPUT_BUFFER_LIMIT
    && json_decode($funnel->GetFunnelData(), true)['funnel']['title'] === 'Current power'
    && findGaugeFormElement($funnelForm['elements'], 'IPSViewOrientation')['enabled'] === true,
    'Draft previews must use bounded current values, escape labels, respect unsaved sources/designs and leave configuration/status unchanged.'
);
if (class_exists(DOMDocument::class)) {
    $funnelXML = new DOMDocument();
    assertGatewayGauge($funnelXML->loadXML($funnelSVG, LIBXML_NONET) && $funnelXML->loadXML($funnelDraftSVG, LIBXML_NONET), 'Funnel previews must be valid XML.');
}
$funnelReflection = new ReflectionClass(EChartsFunnel::class);
$funnelDefaults = $funnelReflection->getConstant('DESIGN_DEFAULTS');
foreach (array_keys($funnelDefaults) as $name) {
    $field = findGaugeFormElement($funnelForm['elements'], $name);
    $independent = findGaugeFormElement($funnelForm['elements'], 'IPSView' . $name);
    assertGatewayGauge(
        isset($field['onChange']) && isset($independent['onChange']) && $independent['enabled'] === true,
        'Every Funnel design field must reach both live previews: ' . $name
    );
}
$funnelLocale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsFunnel/locale.json'), true)['translations']['de'];
foreach (glob(dirname(__DIR__) . '/libs/helper/translations/*.json') as $file) {
    $catalog = json_decode((string) file_get_contents($file), true);
    foreach ($catalog['translations'] ?? [] as $entry) {
        if (is_array($entry) && isset($entry['en'], $entry['de'])) {
            $funnelLocale[$entry['en']] = $entry['de'];
        }
    }
}
$funnelCaptionCheck = function (array $items) use (&$funnelCaptionCheck, $funnelLocale): void
{
    foreach ($items as $key => $item) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($item)) {
            assertGatewayGauge(isset($funnelLocale[$item]), 'Funnel caption needs a German translation: ' . $item);
        } elseif (is_array($item)) {
            $funnelCaptionCheck($item);
        }
    }
};
$funnelCaptionCheck($funnelForm);
$funnelSavedProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($funnel);
$funnelDecorations = ['MinSizePercent' => 20, 'MaxSizePercent' => 85, 'BorderType' => 'dashed',
    'ShadowBlur'                       => 8, 'ShadowColor' => 0x123456, 'ShadowOffsetX' => 3, 'ShadowOffsetY' => -2,
    'HoverFocus'                       => 'none', 'BlurOpacityPercent' => 15, 'HideOverlappingLabels' => false,
    'LabelMaxWidth'                    => 70, 'ShowLabelLines' => true, 'LabelLineLength' => 24, 'LabelLineWidth' => 2,
    'LabelLineType'                    => 'dotted', 'LabelLineColor' => 0xFEDCBA, 'LabelLineOpacityPercent' => 60,
    'OutsideLabelSide'                 => 'start', 'FontFamily' => 'Georgia, serif'];
$funnel->UpdateBarPreviewFromForm(json_encode([...$funnelDecorations, 'Sources' => array_slice($funnelSources, 0, 3),
    'LabelPosition'                                                             => 'outside'], JSON_THROW_ON_ERROR));
$funnelDecoratedSVG = base64_decode(substr(array_slice($funnel->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($funnelDecoratedSVG, 'stroke-dasharray="6 4"')
    && str_contains($funnelDecoratedSVG, 'Georgia, serif') && str_contains($funnelDecoratedSVG, 'filter="url(#funnel-shadow)"')
    && str_contains($funnelDecoratedSVG, '#FEDCBA') && str_contains($funnelDecoratedSVG, 'stroke-opacity="0.6"')
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($funnel) === $funnelSavedProperties,
    'New Funnel styles must reach a draft preview without changing saved properties.'
);
foreach ($funnelDecorations as $name => $value) {
    $funnel->SetTestProperty($name, $value);
}
$funnel->ApplyChanges();
$funnelDecoratedData = json_decode($funnel->GetFunnelData(), true, 512, JSON_THROW_ON_ERROR);
foreach ($funnelDecorations as $name => $value) {
    $expected = str_ends_with($name, 'Color') ? sprintf('#%06X', $value) : $value;
    assertGatewayGauge(
        $funnelDecoratedData['funnel']['style'][lcfirst($name)] === $expected,
        'New Funnel property must reach the model: ' . $name
    );
}
assertGatewayGauge(
    str_contains($funnel->GetIPSViewHTML(), '"minSizePercent":0')
    && str_contains($funnel->GetIPSViewHTML(), '"fontFamily":"Arial, sans-serif"'),
    'Tile design changes must not leak into an independent IPSView design.'
);
$GLOBALS['symconTestCopyTarget'] = $funnel;
try {
    $funnel->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$funnelCopiedProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($funnel);
foreach ($funnelDecorations as $name => $value) {
    assertGatewayGauge($funnelCopiedProperties['IPSView' . $name] === $value, 'Copy action must include: ' . $name);
}
foreach ($funnelSavedProperties as $name => $value) {
    $funnel->SetTestProperty($name, $value);
}
foreach (['', 'IPSView'] as $funnelPrefix) {
    foreach (['Orientation'    => ['invalid', 'vertical'], 'SortOrder' => ['invalid', 'configured'],
        'Alignment'            => ['left', 'center'], 'LabelPosition' => ['invalid', 'inside'],
        'Gap'                  => [31, 3], 'BorderWidth' => [-1, 1], 'OpacityPercent' => [101, 90],
        'TitleFontSizePercent' => [49, 100], 'LabelFontSizePercent' => [201, 100],
        'LabelColor'           => [-2, -1], 'TitleColor' => [0x1000000, -1], 'BorderColor' => [-2, -1],
        'MinSizePercent'       => [101, 0], 'MaxSizePercent' => [0, 100], 'BorderType' => ['invalid', 'solid'],
        'ShadowBlur'           => [21, 0], 'ShadowColor' => [-2, -1], 'ShadowOffsetX' => [-21, 0], 'ShadowOffsetY' => [21, 0],
        'HoverFocus'           => ['adjacency', 'self'], 'BlurOpacityPercent' => [-2, -1], 'LabelMaxWidth' => [301, 0],
        'LabelLineLength'      => [61, 10], 'LabelLineWidth' => [-1, 1], 'LabelLineType' => ['invalid', 'solid'],
        'LabelLineColor'       => [0x1000000, -1], 'LabelLineOpacityPercent' => [101, 100], 'OutsideLabelSide' => ['center', 'end'],
        'FontFamily'           => ['url(unsafe)', 'Arial, sans-serif']] as $name => [$invalid, $valid]) {
        $funnel->SetTestProperty($funnelPrefix . $name, $invalid);
        $funnel->ApplyChanges();
        assertGatewayGauge($funnel->GetTestStatus() === 202, 'Funnel must reject invalid design: ' . $funnelPrefix . $name);
        $funnel->SetTestProperty($funnelPrefix . $name, $valid);
        $funnel->ApplyChanges();
        assertGatewayGauge($funnel->GetTestStatus() === IS_ACTIVE, 'Funnel must recover after correcting design.');
    }
    $funnel->SetTestProperty($funnelPrefix . 'MinSizePercent', 80);
    $funnel->SetTestProperty($funnelPrefix . 'MaxSizePercent', 70);
    $funnel->ApplyChanges();
    assertGatewayGauge($funnel->GetTestStatus() === 202, 'Visual minimum size must not exceed maximum size.');
    $funnel->SetTestProperty($funnelPrefix . 'MinSizePercent', 0);
    $funnel->SetTestProperty($funnelPrefix . 'MaxSizePercent', 100);
    $funnel->ApplyChanges();
}
$funnel->SetTestProperty('BorderWidth', 0);
$funnel->SetTestProperty('OpacityPercent', 0);
$GLOBALS['symconTestCopyTarget'] = $funnel;
try {
    $funnel->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(
    str_contains($funnel->GetIPSViewHTML(), '"borderWidth":0') && str_contains($funnel->GetIPSViewHTML(), '"opacityPercent":0'),
    'Design copy must include explicit zeros.'
);
$funnelUpdates = count($funnel->GetTestVisualizationUpdates());
$GLOBALS['symconTestVariables'][88300]['Value'] = 0.0;
$funnel->MessageSink(time(), 88300, VM_UPDATE, []);
assertGatewayGauge(count($funnel->GetTestVisualizationUpdates()) > $funnelUpdates
    && json_decode($funnel->GetFunnelData(), true)['funnel']['data'][0]['value'] === 0.0, 'VM_UPDATE must retain legitimate zeros without reloading HTML.');
$GLOBALS['symconTestVariables'][88300]['Value'] = -1.0;
$funnel->ApplyChanges();
assertGatewayGauge(
    $funnel->GetTestStatus() === 205 && str_contains($funnel->GetVisualizationTile(), '"error":"Funnel requires non-negative values."'),
    'Negative values must produce a domain error, not a fabricated zero or stale successful chart.'
);
$funnel->UpdateBarPreviewFromForm('{}');
$funnelInvalidSVG = base64_decode(substr(array_slice($funnel->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(!str_contains($funnelInvalidSVG, 'data-stage=') && $funnel->GetTestStatus() === 205, 'Invalid preview values must not fabricate a valid chart or reset module status.');
$GLOBALS['symconTestVariables'][88300]['Value'] = 10.0;
IPSModuleStrict::$ParentResponder = static function (string $json): string
{ throw new RuntimeException('Temporary disconnect after negative value.'); };
try {
    $funnel->ApplyChanges();
    assertGatewayGauge(
        $funnel->GetTestStatus() === 204 && str_contains($funnel->GetVisualizationTile(), '"error":"The Funnel values could not be loaded."'),
        'A new transport error must not inherit the previous negative-value error.'
    );
} finally {
    IPSModuleStrict::$ParentResponder = $funnelParent;
}
$funnel->ApplyChanges();
assertTransientRetry($funnel, 'Funnel');
foreach (range(88300, 88315) as $id) {
    $GLOBALS['symconTestVariables'][$id]['Value'] = 0.0;
}
$funnel->ApplyChanges();
assertGatewayGauge(
    $funnel->GetTestStatus() === IS_ACTIVE
    && array_sum(array_column(json_decode($funnel->GetFunnelData(), true)['funnel']['data'], 'value')) === 0.0,
    'All-zero Funnel sources are valid and remain zeros in the model.'
);
$funnel->SetTestParentActive(false);
$funnel->ApplyChanges();
assertGatewayGauge($funnel->GetTestStatus() === 203, 'A missing gateway must be reported.');
$funnel->SetTestParentActive(true);
foreach (['not JSON', '{}', '[]', json_encode([$funnelSources[0], $funnelSources[0]]),
    json_encode([...$funnelSources, $funnelSources[0]]), '[{"VariableID":"88300"}]'] as $invalid) {
    $funnel->SetTestProperty('Sources', $invalid);
    $funnel->ApplyChanges();
    assertGatewayGauge($funnel->GetTestStatus() === 201, 'Invalid Funnel sources must be rejected.');
}
$GLOBALS['symconTestVariables'][88301]['Presentation']['SUFFIX'] = ' °C';
$funnel->SetTestProperty('Sources', json_encode(array_slice($funnelSources, 0, 2), JSON_THROW_ON_ERROR));
$funnel->ApplyChanges();
assertGatewayGauge($funnel->GetTestStatus() === 201, 'Mixed Funnel units must be rejected.');
$GLOBALS['symconTestVariables'][88301]['Presentation']['SUFFIX'] = ' W';
$funnel->SetTestProperty('Sources', '[]');
$funnel->ApplyChanges();
assertGatewayGauge(
    $funnel->GetTestReferences() === [] && !in_array(['SenderID' => 88300, 'Message' => VM_UPDATE], $funnel->GetTestMessages(), true),
    'Removing Funnel sources must unregister references and subscriptions.'
);
foreach (range(88300, 88315) as $id) {
    unset($GLOBALS['symconTestVariables'][$id]);
}
echo "Funnel Gateway, output and form integration verified.\n";
