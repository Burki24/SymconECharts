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
try {
    $boxplot->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($boxplot->GetIPSViewHTML(), '"orientation":"vertical"'), 'Explicit design copy must copy Boxplot settings.');
foreach (['24h', '7d', '30d'] as $range) {
    $boxplot->SetTestProperty('Range', $range);
    $rangeData = json_decode($boxplot->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge($rangeData['range']['endTimestamp'] - $rangeData['range']['startTimestamp'] + 1 === ['24h' => 86400, '7d' => 604800, '30d' => 2592000][$range], 'Boxplot ranges must remain complete and bounded.');
}
foreach (['', 'IPSView'] as $prefix) {
    foreach (['Orientation' => ['bad', 'vertical'], 'BoxWidth' => [81, 40], 'BorderWidth' => [-1, 2], 'BoxOpacityPercent' => [101, 70],
        'TitleColor'        => [-2, -1], 'AxisFontSizePercent' => [201, 100]] as $name => [$invalid, $valid]) {
        $boxplot->SetTestProperty($prefix . $name, $invalid);
        $boxplot->ApplyChanges();
        assertGatewayGauge($boxplot->GetTestStatus() === 202, 'Boxplot must reject invalid design ' . $prefix . $name);
        $boxplot->SetTestProperty($prefix . $name, $valid);
        $boxplot->ApplyChanges();
        assertGatewayGauge($boxplot->GetTestStatus() === IS_ACTIVE, 'Boxplot must recover after correcting design ' . $prefix . $name);
    }
}
foreach (['[]', '{}', 'bad', json_encode([$boxplotSources[0], $boxplotSources[0]]), '[{"VariableID":4712}]',
    '[{"VariableID":88200,"Color":16777216}]', json_encode([...$boxplotSources, ['VariableID' => 4711]])] as $invalid) {
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
date_default_timezone_set($boxplotZone);
echo "Boxplot integration verified.\n";
