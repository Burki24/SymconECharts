<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsBoxplot/module.php';

final class TestableEChartsBoxplot extends EChartsBoxplot
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsBoxplot/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsBoxplot/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return strtotime('2026-10-16 12:34:00');
    }
}

$boxplotZone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$boxplotSources = [];
for ($index = 0; $index < 10; ++$index) {
    $id = 88200 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                            => 12345.0, 'Name' => 'Same <name>', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
    $boxplotSources[] = ['VariableID' => $id, 'Label' => 'Room <one>', 'Color' => $index === 0 ? 0xABCDEF : -1];
}
foreach ([8.0, -4.0, 0.0, 4.0] as $index => $value) {
    $GLOBALS['symconTestArchiveVariables'][88200]['AggregatedValues'][] = [
        'TimeStamp' => strtotime('2026-10-16 11:00:00') - $index * 3600, 'Avg' => $value, 'Min' => -999, 'Max' => 999];
}
$GLOBALS['symconTestArchiveVariables'][88200]['AggregatedValues'][] = [
    'TimeStamp' => strtotime('2026-10-16 12:00:00'), 'Avg' => 777.0, 'Min' => 777.0, 'Max' => 777.0];
$GLOBALS['symconTestArchiveVariables'][88201]['AggregatedValues'] = [
    ['TimeStamp' => strtotime('2026-10-15 23:00:00'), 'Avg' => 7.0, 'Min' => 7.0, 'Max' => 7.0]];
$boxplot = new TestableEChartsBoxplot();
$boxplot->Create();
$boxplot->SetTestProperty('Sources', json_encode($boxplotSources, JSON_THROW_ON_ERROR));
$boxplot->SetTestProperty('Title', 'Hourly temperatures');
$boxplot->SetTestProperty('EnableIPSView', true);
$boxplot->SetTestProperty('IPSViewUseTileDesign', false);
$boxplot->SetTestProperty('IPSViewOrientation', 'horizontal');
$boxplot->SetTestProperty('IPSViewBorderColor', 0x123456);
$boxplot->ApplyChanges();
$boxplotData = json_decode($boxplot->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $boxplot->GetTestStatus() === IS_ACTIVE && $boxplot->GetTestReferences() === range(88200, 88209)
    && $boxplot->GetTestTimerInterval('ArchiveRefresh') === 60000
    && $boxplotData['family'] === 'boxplot' && $boxplotData['boxplot']['unit'] === '°C'
    && $boxplotData['boxplot']['sampling'] === 'completed-hourly-means' && $boxplotData['boxplot']['whiskers'] === 'minmax'
    && $boxplotData['boxplot']['data'][0]['statistics'] === [-4.0, -1.0, 2.0, 5.0, 8.0]
    && $boxplotData['boxplot']['data'][0]['count'] === 4
    && $boxplotData['boxplot']['data'][1]['statistics'] === [7.0, 7.0, 7.0, 7.0, 7.0]
    && $boxplotData['boxplot']['data'][2]['statistics'] === null
    && $boxplotData['boxplot']['data'][0]['id'] !== $boxplotData['boxplot']['data'][1]['id']
    && $boxplotData['boxplot']['data'][0]['label'] === $boxplotData['boxplot']['data'][1]['label']
    && $boxplotData['range']['aggregationLevel'] === 0 && $boxplotData['range']['endTimestamp'] === strtotime('2026-10-16 12:00:00') - 1,
    'Boxplot must use all completed hourly averages per source, R7 quartiles, independent gaps and ID identity, never raw extrema or current values.'
);
$boxplotTile = $boxplot->GetVisualizationTile();
$boxplotIPSView = $boxplot->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($boxplotTile, '"orientation":"vertical"') && str_contains($boxplotIPSView, '"orientation":"horizontal"')
    && str_contains($boxplotIPSView, '"borderColor":"#123456"') && str_contains($boxplotTile, '"color":"#ABCDEF"')
    && strlen($boxplotTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($boxplotIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
    && is_string($boxplot->GetTestVariableValue('IPSViewBoxplot')) && $boxplot->GetTestVariableValue('IPSViewBoxplot') !== '',
    'Boxplot must fit the output buffer at ten sources and publish independent IPSView WebContent.'
);
$boxplotParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json): string
{ throw new RuntimeException('Preview must not query the archive.'); };
try {
    $boxplotForm = json_decode($boxplot->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $boxplot->UpdateBarPreviewFromForm(json_encode(['Title' => 'Unsaved title', 'BorderColor' => 0xFEDCBA, 'Orientation' => 'horizontal'], JSON_THROW_ON_ERROR));
} finally {
    IPSModuleStrict::$ParentResponder = $boxplotParent;
}
$boxplotSVG = base64_decode(substr(findGaugeFormElement($boxplotForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
$boxplotDraftSVG = base64_decode(substr(array_slice($boxplot->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($boxplotSVG) && str_contains($boxplotSVG, 'Example data') && substr_count($boxplotSVG, 'data-box=') === 10
    && str_contains($boxplotDraftSVG, 'Unsaved title') && str_contains($boxplotDraftSVG, '#FEDCBA') && str_contains($boxplotDraftSVG, '&lt;one&gt;')
    && $boxplot->GetTestStatus() === IS_ACTIVE && strlen(json_encode($boxplotForm, JSON_THROW_ON_ERROR)) < SYMCON_OUTPUT_BUFFER_LIMIT
    && json_decode($boxplot->GetBoxplotData(), true)['boxplot']['title'] === 'Hourly temperatures'
    && findGaugeFormElement($boxplotForm['elements'], 'IPSViewOrientation')['enabled'] === true
    && !isset(findGaugeFormElement($boxplotForm['elements'], 'Sources')['onChange']),
    'Synthetic bounded previews must respect unsaved designs, escape labels and leave saved properties/status unchanged.'
);
if (class_exists(DOMDocument::class)) {
    $boxplotXML = new DOMDocument();
    assertGatewayGauge($boxplotXML->loadXML($boxplotSVG, LIBXML_NONET) && $boxplotXML->loadXML($boxplotDraftSVG, LIBXML_NONET), 'Boxplot previews must be valid XML.');
}
$GLOBALS['symconTestCopyTarget'] = $boxplot;
// New controls must reach each output and the drafts, without changing source contracts.
$boxplotSavedProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($boxplot);
$boxplotDecorations = ['MinBoxWidth' => 12, 'BorderType' => 'dashed', 'ShadowBlur' => 8, 'ShadowOffsetX' => 3, 'ShadowOffsetY' => -2,
    'ShadowColor'                    => 0x123456, 'HoverFocus' => 'none', 'BlurOpacityPercent' => 15, 'AxisLineWidth' => 3, 'ShowAxisTicks' => false,
    'AxisLabelRotate'                => 45, 'AxisLabelMaxWidth' => 100, 'SplitNumber' => 8, 'GridLineType' => 'dotted', 'GridLineWidth' => 2,
    'GridOpacityPercent'             => 60, 'GridColor' => 0xFEDCBA, 'TitleBold' => false, 'AxisLabelBold' => true];
foreach ($boxplotDecorations as $name => $value) {
    $boxplot->SetTestProperty($name, $value);
    $field = findGaugeFormElement($boxplotForm['elements'], $name);
    $independent = findGaugeFormElement($boxplotForm['elements'], 'IPSView' . $name);
    assertGatewayGauge(
        isset($field['onChange']) && isset($independent['onChange']) && $independent['enabled'] === true,
        'Every new control must be wired into both designers and live previews: ' . $name
    );
}
$boxplot->ApplyChanges();
assertGatewayGauge(str_contains($boxplot->GetVisualizationTile(), '"borderType":"dashed"')
    && str_contains($boxplot->GetIPSViewHTML(), '"borderType":"solid"'), 'New Boxplot design properties must remain independent.');
$boxplotPropertiesBeforeDraft = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($boxplot);
IPSModuleStrict::$ParentResponder = static function (string $json): string
{ throw new RuntimeException('Designer drafts must never query archives.'); };
try {
    $boxplot->UpdateBarPreviewFromForm(json_encode(['IPSViewUseTileDesign' => true, ...$boxplotDecorations], JSON_THROW_ON_ERROR));
} finally {
    IPSModuleStrict::$ParentResponder = $boxplotParent;
}
$boxplotDecoratedSVG = base64_decode(substr(array_slice($boxplot->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($boxplotDecoratedSVG, 'stroke-dasharray="6 4"') && str_contains($boxplotDecoratedSVG, 'stroke-dasharray="2 3"')
    && str_contains($boxplotDecoratedSVG, 'filter="url(#box-shadow)"') && str_contains($boxplotDecoratedSVG, 'stdDeviation="4"')
    && str_contains($boxplotDecoratedSVG, 'dx="3" dy="-2"') && str_contains($boxplotDecoratedSVG, '#FEDCBA')
    && str_contains($boxplotDecoratedSVG, 'rotate(-45 ') && str_contains($boxplotDecoratedSVG, 'font-weight="normal" x="24"')
    && str_contains($boxplotDecoratedSVG, 'font-weight="bold"')
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($boxplot) === $boxplotPropertiesBeforeDraft,
    'Designer previews must show effects, rotation, grid and typography without saving drafts.'
);
$boxplotDecoratedDocument = new DOMDocument();
assertGatewayGauge($boxplotDecoratedDocument->loadXML($boxplotDecoratedSVG, LIBXML_NONET), 'Decorated Boxplot previews must remain valid XML.');
try {
    $boxplot->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($boxplot->GetIPSViewHTML(), '"orientation":"vertical"'), 'Explicit design copy must copy Boxplot settings.');
assertGatewayGauge(str_contains($boxplot->GetIPSViewHTML(), '"borderType":"dashed"')
    && str_contains($boxplot->GetIPSViewHTML(), '"shadowBlur":8'), 'Explicit copy must include every new Boxplot design option.');
foreach ($boxplotSavedProperties as $name => $value) {
    $boxplot->SetTestProperty($name, $value);
}
$boxplot->SetTestProperty('IPSViewUseTileDesign', true);
$boxplotInheritedForm = json_decode($boxplot->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
foreach (array_keys($boxplotDecorations) as $name) {
    assertGatewayGauge(findGaugeFormElement($boxplotInheritedForm['elements'], 'IPSView' . $name)['enabled'] === false, 'Inherited Boxplot controls must be disabled: ' . $name);
}
$boxplot->SetTestProperty('IPSViewUseTileDesign', false);
$boxplot->ApplyChanges();
foreach (['24h', '7d', '30d'] as $range) {
    $boxplot->SetTestProperty('Range', $range);
    $rangeData = json_decode($boxplot->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge($rangeData['range']['endTimestamp'] - $rangeData['range']['startTimestamp'] + 1 === ['24h' => 86400, '7d' => 604800, '30d' => 2592000][$range], 'Boxplot ranges must remain complete and bounded.');
}
foreach (['', 'IPSView'] as $prefix) {
    foreach (['Orientation' => ['bad', 'vertical'], 'BoxWidth' => [81, 40], 'BorderWidth' => [-1, 2], 'BoxOpacityPercent' => [101, 70],
        'TitleColor'        => [-2, -1], 'AxisFontSizePercent' => [201, 100],
        'MinBoxWidth'       => [41, 1], 'BorderType' => ['invalid', 'solid'], 'ShadowBlur' => [21, 0], 'ShadowColor' => [-2, -1],
        'ShadowOffsetX'     => [-21, 0], 'ShadowOffsetY' => [21, 0], 'HoverFocus' => ['series', 'self'], 'BlurOpacityPercent' => [101, 10],
        'AxisLineWidth'     => [6, 1], 'AxisLabelRotate' => [91, 0], 'AxisLabelMaxWidth' => [301, 0], 'SplitNumber' => [1, 5],
        'GridLineType'      => ['invalid', 'solid'], 'GridLineWidth' => [-1, 1], 'GridOpacityPercent' => [101, 25], 'GridColor' => [-2, -1]] as $name => [$invalid, $valid]) {
        $boxplot->SetTestProperty($prefix . $name, $invalid);
        $boxplot->ApplyChanges();
        assertGatewayGauge($boxplot->GetTestStatus() === 202, 'Boxplot must reject invalid design ' . $prefix . $name);
        $boxplot->SetTestProperty($prefix . $name, $valid);
        $boxplot->ApplyChanges();
        assertGatewayGauge($boxplot->GetTestStatus() === IS_ACTIVE, 'Boxplot must recover after correcting design ' . $prefix . $name);
    }
}
foreach (['[]', '{}', 'bad', json_encode([$boxplotSources[0], $boxplotSources[0]]), '[{"VariableID":4712}]',
    '[{"VariableID":88200,"Color":16777216}]'] as $invalid) {
    $boxplot->SetTestProperty('Sources', $invalid);
    $boxplot->ApplyChanges();
    assertGatewayGauge($boxplot->GetTestStatus() === 201, 'Boxplot must reject invalid sources.');
}
$GLOBALS['symconTestVariables'][88201]['Presentation']['SUFFIX'] = ' %';
$boxplot->SetTestProperty('Sources', json_encode(array_slice($boxplotSources, 0, 2), JSON_THROW_ON_ERROR));
$boxplot->ApplyChanges();
assertGatewayGauge($boxplot->GetTestStatus() === 201, 'Boxplot must reject mixed units on its shared value axis.');
$GLOBALS['symconTestVariables'][88201]['Presentation']['SUFFIX'] = ' °C';
$boxplot->ApplyChanges();
assertGatewayGauge($boxplot->GetTestReferences() === [88200, 88201], 'Removed sources must unregister their references.');
foreach (['Truncated' => true, 'EffectiveReducer' => 'sum', 'ArchiveAggregationType' => 'counter', 'VariableID' => 1,
    'StartTimestamp'  => 1, 'EndTimestamp' => 1, 'Points' => [[1, 999]]] as $field => $bad) {
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($boxplotParent, $field, $bad): string
    {
        $response = json_decode($boxplotParent($json), true, 512, JSON_THROW_ON_ERROR);
        $response['Payload'][$field] = $bad;
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    try {
        $boxplot->RefreshArchive();
        assertGatewayGauge($boxplot->GetTestStatus() === 204, 'Boxplot must reject an unsafe archive response: ' . $field);
    } finally {
        IPSModuleStrict::$ParentResponder = $boxplotParent;
    }
}
$boxplot->RefreshArchive();
assertTransientRetry($boxplot, 'Boxplot');
$boxplot->SetTestProperty('Sources', '[{"VariableID":4713}]');
$boxplot->ApplyChanges();
assertGatewayGauge($boxplot->GetTestStatus() === 204 && str_contains($boxplot->GetVisualizationTile(), 'Boxplot requires standard archive variables, not counters.'), 'Counters must be rejected visibly, never summarized as standard measurements.');
$boxplot->SetTestProperty('Sources', '[{"VariableID":88202}]');
$boxplot->ApplyChanges();
assertGatewayGauge($boxplot->GetTestStatus() === IS_ACTIVE && json_decode($boxplot->GetBoxplotData(), true)['boxplot']['data'][0]['statistics'] === null, 'An empty archive is valid and must not create zero-valued statistics.');
$boxplot->SetTestParentActive(false);
$boxplot->ApplyChanges();
assertGatewayGauge($boxplot->GetTestStatus() === 203 && $boxplot->GetTestTimerInterval('ArchiveRefresh') === 0, 'A missing gateway must disable archive refresh.');
$boxplotLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsBoxplot/locale.json'), true)['translations']['de'];
$boxplotCaptionCheck = static function (array $items) use (&$boxplotCaptionCheck, $boxplotLocale): void
{
    foreach ($items as $key => $item) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($item)) {
            assertGatewayGauge(isset($boxplotLocale[$item]), 'Boxplot must localize its caption: ' . $item);
        } elseif (is_array($item)) {
            $boxplotCaptionCheck($item);
        }
    }
};
$boxplotCaptionCheck(json_decode(file_get_contents(__DIR__ . '/../EChartsBoxplot/form.json'), true));
foreach (['EChartsAnimationDesign.php', 'EChartsIPSViewBackground.php', 'EChartsBarPreviewForm.php'] as $sharedFormFile) {
    $sharedFormSource = file_get_contents(__DIR__ . '/../libs/' . $sharedFormFile);
    preg_match_all("/'caption'\\s*=>\\s*'([^']+)'/", $sharedFormSource, $sharedCaptions);
    foreach ($sharedCaptions[1] as $caption) {
        assertGatewayGauge(isset($boxplotLocale[$caption]), 'Boxplot must translate shared designer captions: ' . $caption);
    }
}
preg_match_all("/(?:Translate|translate)\\(\\s*'([^']+)'/", file_get_contents(__DIR__ . '/../EChartsBoxplot/module.php')
    . file_get_contents(__DIR__ . '/../EChartsBoxplot/visualization/app.js'), $boxplotRuntimeTexts);
foreach ($boxplotRuntimeTexts[1] as $text) {
    assertGatewayGauge(isset($boxplotLocale[$text]), 'Boxplot must translate visible runtime text: ' . $text);
}
require __DIR__ . '/boxplot_large_sources.php';
date_default_timezone_set($boxplotZone);
echo "Boxplot integration verified.\n";
