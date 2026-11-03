<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsThemeRiver/module.php';

final class TestableEChartsThemeRiver extends EChartsThemeRiver
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return strtotime('2026-10-17 12:34:56');
    }
}

$riverZone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$riverSources = [];
for ($index = 0; $index < 8; ++$index) {
    $id = 88500 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                            => -9999.0, 'Name' => 'Same <name>', 'Presentation' => ['SUFFIX' => ' W', 'DIGITS' => 1]];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => [
        ['TimeStamp' => strtotime('2026-10-17 12:00:00'), 'Avg' => 777.0, 'Min' => 0, 'Max' => 999],
        ['TimeStamp' => strtotime('2026-10-17 11:00:00'), 'Avg' => $index === 0 ? 0.0 : 5.0, 'Min' => -99, 'Max' => 999],
        ['TimeStamp' => strtotime('2026-10-17 09:00:00'), 'Avg' => 15.0 + $index, 'Min' => -99, 'Max' => 999]]];
    $riverSources[] = ['VariableID' => $id, 'Label' => '', 'Color' => $index === 0 ? 0xABCDEF : -1];
}
$river = new TestableEChartsThemeRiver();
$river->Create();
$river->SetTestProperty('Sources', json_encode($riverSources, JSON_THROW_ON_ERROR));
$river->SetTestProperty('Title', 'Archived power');
$river->SetTestProperty('EnableIPSView', true);
$river->SetTestProperty('IPSViewUseTileDesign', false);
$river->SetTestProperty('IPSViewBandOpacityPercent', 37);
$river->SetTestProperty('Range', '24h');
$river->ApplyChanges();
$riverData = json_decode($river->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
$riverRows = $riverData['themeRiver']['rows'];
assertGatewayGauge(($riverData['themeRiver']['style']['bandHeightPercent'] ?? null) === 84, 'ThemeRiver must expose the configurable band height with its existing default.');
assertGatewayGauge(
    $river->GetTestStatus() === IS_ACTIVE && $river->GetTestTimerInterval('ArchiveRefresh') === 60000
    && $river->GetTestReferences() === range(88500, 88507)
    && count($riverRows) === 24 && $riverRows[23]['values'][0] === 0.0 && $riverRows[23]['values'][1] === 5.0
    && $riverRows[22]['values'] === null && $riverRows[21]['values'][0] === 15.0
    && $riverData['themeRiver']['missingIntervals'] === 22
    && $riverData['themeRiver']['sources'][0]['id'] !== $riverData['themeRiver']['sources'][1]['id']
    && $riverData['themeRiver']['sources'][0]['label'] === $riverData['themeRiver']['sources'][1]['label'],
    'ThemeRiver must preserve same-name sources by ID, current negative values must not replace archive means, and missing intervals must remain null.'
);
assertGatewayGauge(
    str_contains($river->GetVisualizationTile(), '"bandOpacityPercent":85')
    && str_contains($river->GetIPSViewHTML(), '"bandOpacityPercent":37')
    && is_string($river->GetTestVariableValue('IPSViewThemeRiver')) && $river->GetTestVariableValue('IPSViewThemeRiver') !== '',
    'ThemeRiver must publish independent Tile and nonempty IPSView outputs.'
);
$riverSaved = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($river);
$riverParent = IPSModuleStrict::$ParentResponder;
$riverDesignChanges = [
    'BandHeightPercent' => 50, 'BorderType' => 'dotted', 'ShadowBlur' => 8, 'ShadowColor' => 0x123456,
    'ShadowOffsetX'     => 3, 'ShadowOffsetY' => -4, 'EmphasisEnabled' => false, 'EmphasisFocus' => 'none', 'BlurOpacityPercent' => 20,
    'ShowAxisLine'      => false, 'AxisLineWidth' => 3, 'AxisLineType' => 'dashed', 'AxisLabelMargin' => 22, 'TimeLabelFormat' => 'dateTime',
    'ShowGrid'          => true, 'GridLineWidth' => 2, 'GridLineType' => 'dotted', 'GridColor' => 0x334455, 'GridOpacityPercent' => 35,
    'LegendPosition'    => 'top', 'LegendAlignment' => 'center', 'LegendIcon' => 'diamond',
    'LegendItemWidth'   => 30, 'LegendItemHeight' => 20, 'LegendItemGap' => 16, 'FontFamily' => 'Georgia, serif',
    'TitleBold'         => false, 'AxisLabelBold' => true, 'LabelBold' => true, 'LegendBold' => true,
    'TitleItalic'       => true, 'AxisLabelItalic' => true, 'LabelItalic' => true, 'LegendItalic' => true
];
foreach (['', 'IPSView'] as $prefix) {
    foreach ($riverDesignChanges as $name => $value) {
        $river->SetTestProperty($prefix . $name, $value);
    }
}
$river->ApplyChanges();
$extendedData = json_decode($river->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge($extendedData['themeRiver']['rows'] === $riverRows, 'Design changes must not change archive rows.');
$extendedForm = json_decode($river->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
foreach ($riverDesignChanges as $name => $value) {
    $encoded = str_ends_with($name, 'Color') ? SymconECharts\EChartsAsset::ColorToHex($value) : $value;
    assertGatewayGauge($extendedData['themeRiver']['style'][lcfirst($name)] === $encoded, 'Missing ThemeRiver design payload: ' . $name);
    foreach (['', 'IPSView'] as $prefix) {
        $control = findGaugeFormElement($extendedForm['elements'], $prefix . $name);
        assertGatewayGauge($control !== null && ($control['onChange'] ?? '') !== '', 'Design control must have live preview: ' . $prefix . $name);
    }
}
assertGatewayGauge(str_contains($river->GetIPSViewHTML(), '"legendIcon":"diamond"'), 'Independent IPSView design must reach the renderer.');
$river->UpdateBarPreviewFromForm(json_encode($riverDesignChanges, JSON_THROW_ON_ERROR));
foreach (array_slice($river->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    $xml = new DOMDocument();
    assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET), 'Extended ThemeRiver previews must remain valid SVG.');
    $xp = new DOMXPath($xml);
    assertGatewayGauge(
        str_contains($svg, 'font-family="Georgia, serif"') && str_contains($svg, 'filter="url(#river-shadow)"')
        && str_contains($svg, 'stroke-dasharray="2 3"') && $xp->query('//*[@data-grid]')->length === 5
        && $xp->query('//*[@data-axis-line]')->length === 0 && $xp->query('//*[@data-axis-label]/*')->length === 10
        && (float) $xp->query('//*[@data-legend-label]')->item(0)->getAttribute('y') < 100,
        'Preview must reflect shadows, font, dashed bands, grid, hidden axis, multiline dates and top legend.'
    );
}
foreach (['BandHeightPercent' => 19, 'BorderType' => 'invalid', 'ShadowBlur' => 21, 'ShadowColor' => -2,
    'ShadowOffsetX'           => -21, 'ShadowOffsetY' => 21, 'EmphasisFocus' => 'invalid', 'BlurOpacityPercent' => 101,
    'AxisLineWidth'           => 6, 'AxisLineType' => 'invalid', 'AxisLabelMargin' => 31, 'TimeLabelFormat' => 'invalid',
    'GridLineWidth'           => -1, 'GridLineType' => 'invalid', 'GridColor' => 0x1000000, 'GridOpacityPercent' => -1,
    'LegendPosition'          => 'invalid', 'LegendAlignment' => 'invalid', 'LegendIcon' => 'invalid',
    'LegendItemWidth'         => 41, 'LegendItemHeight' => 31, 'LegendItemGap' => -1, 'FontFamily' => '<script>'] as $name => $bad) {
    foreach (['', 'IPSView'] as $prefix) {
        $river->SetTestProperty($prefix . $name, $bad);
        $river->ApplyChanges();
        assertGatewayGauge($river->GetTestStatus() === 202, 'Invalid extended design must be rejected: ' . $prefix . $name);
        $river->SetTestProperty($prefix . $name, $riverDesignChanges[$name]);
    }
}
$GLOBALS['symconTestCopyTarget'] = $river;
try {
    $river->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$copied = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($river);
foreach ($riverDesignChanges as $name => $value) {
    assertGatewayGauge($copied['IPSView' . $name] === $value, 'Design copy must include ' . $name);
}
$river->SetTestProperty('IPSViewUseTileDesign', true);
$inheritedForm = json_decode($river->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
foreach (array_keys($riverDesignChanges) as $name) {
    assertGatewayGauge(findGaugeFormElement($inheritedForm['elements'], 'IPSView' . $name)['enabled'] === false, 'Inherited new controls must be disabled: ' . $name);
}
foreach ($riverSaved as $name => $value) {
    $river->SetTestProperty($name, $value);
}
$river->ApplyChanges();
IPSModuleStrict::$ParentResponder = static function (string $json): string
{ throw new RuntimeException('Previews must not request archives.'); };
try {
    $riverForm = json_decode($river->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $river->UpdateBarPreviewFromForm(json_encode(['Title' => '<Draft>', 'ShowLabels' => true,
        'BorderColor'                                     => 0x123456, 'BorderWidth' => 3, 'Sources' => [['VariableID' => 88500, 'Label' => '<Unsaved>']]], JSON_THROW_ON_ERROR));
} finally {
    IPSModuleStrict::$ParentResponder = $riverParent;
}
$riverSvg = base64_decode(substr(array_slice($river->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$riverXML = new DOMDocument();
assertGatewayGauge(
    $riverXML->loadXML($riverSvg, LIBXML_NONET) && str_contains($riverSvg, '&lt;Draft&gt;')
    && str_contains($riverSvg, '&lt;Unsaved&gt;') && str_contains($riverSvg, '#123456') && str_contains($riverSvg, 'Example data')
    && findGaugeFormElement($riverForm['elements'], 'IPSViewBandOpacityPercent')['enabled'] === true
    && findGaugeFormElement($riverForm['elements'], 'Sources')['onAdd'] !== ''
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($river) === $riverSaved,
    'ThemeRiver drafts must be escaped, synthetic, live and nonmutating in both designers.'
);
foreach (['24h', '7d', '30d'] as $previewRange) {
    $river->UpdateBarPreviewFromForm(json_encode(['Range' => $previewRange, 'ShowLegend' => true,
        'AxisFontSizePercent'                             => 200, 'LegendFontSizePercent' => 200], JSON_THROW_ON_ERROR));
    $riverSvg = base64_decode(substr(array_slice($river->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge($riverXML->loadXML($riverSvg, LIBXML_NONET), 'ThemeRiver footer preview must remain valid SVG.');
    $xpath = new DOMXPath($riverXML);
    $axisText = $xpath->query('//*[@data-axis-label]')->item(0);
    $legendText = $xpath->query('//*[@data-legend-label]')->item(0);
    assertGatewayGauge(
        $axisText !== null && $legendText !== null
        && preg_match($previewRange === '24h' ? '/^\d{2}:\d{2}$/' : '/^\d{2}\.\d{2}\.$/', $axisText->textContent) === 1
        && (float) $legendText->getAttribute('y') - (float) $legendText->getAttribute('font-size')
            - (float) $axisText->getAttribute('y') >= 20,
        'Preview axis labels must follow the window and keep large legend text separate.'
    );
}
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($river) === $riverSaved, 'Layout previews must never change saved configuration.');
$GLOBALS['symconTestCopyTarget'] = $river;
try {
    $river->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($river->GetIPSViewHTML(), '"bandOpacityPercent":85'), 'Design copy must include all ThemeRiver fields.');
$river->SetTestProperty('IPSViewUseTileDesign', true);
$riverInheritedForm = json_decode($river->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(findGaugeFormElement($riverInheritedForm['elements'], 'IPSViewBandOpacityPercent')['enabled'] === false, 'Inherited designer fields must be disabled.');
unset($riverInheritedForm);
foreach (['[]', '{}', 'invalid', json_encode([$riverSources[0], $riverSources[0]]), json_encode([...$riverSources, ['VariableID' => 4711]])] as $bad) {
    $river->SetTestProperty('Sources', $bad);
    $river->ApplyChanges();
    assertGatewayGauge($river->GetTestStatus() === 201, 'Invalid ThemeRiver sources must fail without archive writes.');
}
$river->SetTestProperty('Sources', json_encode($riverSources, JSON_THROW_ON_ERROR));
$GLOBALS['symconTestVariables'][88500]['Name'] = 'Renamed source';
assertGatewayGauge(json_decode($river->GetThemeRiverData(), true)['themeRiver']['sources'][0]['label'] === 'Renamed source', 'Renaming must preserve ID identity and refresh only the visible label.');
$river->SetTestProperty('Range', '30d');
$river->SetTestProperty('Title', str_repeat('<測>', 60));
$river->SetTestProperty('Sources', json_encode(array_map(static fn (array $s): array => [...$s, 'Label' => str_repeat('<>&測', 30)], $riverSources), JSON_THROW_ON_ERROR));
IPSModuleStrict::$ParentResponder = static function (string $json) use ($riverParent): string
{
    $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if ($request['Operation'] !== 'archive.read') {
        return $riverParent($json);
    }
    $p = $request['Payload'];
    $points = [];
    for ($t = $p['StartTimestamp']; $t <= $p['EndTimestamp']; $t += 3600) {
        $points[] = [$t, 1.2345678901234567e250];
    }
    return SymconECharts\EChartsDataProtocol::EncodeSuccessResponse('archive.read', [
        'VariableID'     => $p['VariableID'], 'VariableType' => 2, 'ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'average',
        'StartTimestamp' => $p['StartTimestamp'], 'EndTimestamp' => $p['EndTimestamp'], 'Points' => $points, 'Truncated' => false]);
};
try {
    $riverMaximum = json_decode($river->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(count($riverMaximum['themeRiver']['rows']) === 720 && $riverMaximum['themeRiver']['missingIntervals'] === 0
        && strlen($river->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($river->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($river->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Maximum 8-by-720 means, long UTF-8 labels and both designers must fit the output buffer.');
    unset($riverMaximum);
} finally {
    IPSModuleStrict::$ParentResponder = $riverParent;
}
$river->SetTestProperty('Sources', json_encode($riverSources, JSON_THROW_ON_ERROR));
$river->SetTestProperty('Title', 'Archived power');
$river->SetTestProperty('Range', '24h');
$GLOBALS['symconTestVariables'][88501]['Presentation']['SUFFIX'] = ' %';
$river->ApplyChanges();
assertGatewayGauge($river->GetTestStatus() === 201, 'Mixed units must not be combined.');
$GLOBALS['symconTestVariables'][88501]['Presentation']['SUFFIX'] = ' W';
foreach (['Truncated' => true, 'EffectiveReducer' => 'sum', 'ArchiveAggregationType' => 'counter', 'VariableID' => 1,
    'StartTimestamp'  => 1, 'EndTimestamp' => 1, 'Points' => [[strtotime('2026-10-17 11:00:00'), -1.0]]] as $field => $bad) {
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($riverParent, $field, $bad): string
    {
        $response = json_decode($riverParent($json), true, 512, JSON_THROW_ON_ERROR);
        $response['Payload'][$field] = $bad;
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    try {
        $river->RefreshArchive();
        assertGatewayGauge($river->GetTestStatus() === 204, 'Unsafe ThemeRiver archive responses must fail: ' . $field);
    } finally {
        IPSModuleStrict::$ParentResponder = $riverParent;
    }
}
$river->RefreshArchive();
assertTransientRetry($river, 'ThemeRiver');
foreach (['hour', 'day'] as $aggregation) {
    $river->SetTestProperty('Aggregation', $aggregation);
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($aggregation): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $payload = $request['Payload'];
        assertGatewayGauge($payload['Reducer'] === 'average' && $payload['AggregationLevel'] === ($aggregation === 'day' ? 1 : 0), 'ThemeRiver must request explicit standard means.');
        return SymconECharts\EChartsDataProtocol::EncodeSuccessResponse(SymconECharts\EChartsDataProtocol::OPERATION_ARCHIVE_READ, [
            'VariableID'     => $payload['VariableID'], 'VariableType' => 2, 'ArchiveAggregationType' => 'standard', 'EffectiveReducer' => 'average',
            'StartTimestamp' => $payload['StartTimestamp'], 'EndTimestamp' => $payload['EndTimestamp'], 'Points' => [], 'Truncated' => false]);
    };
    try {
        $riverEmpty = json_decode($river->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR)['themeRiver'];
        assertGatewayGauge(count($riverEmpty['rows']) === ($aggregation === 'day' ? 1 : 24) && $riverEmpty['rows'][0]['values'] === null, 'Empty archives remain valid and do not invent zeros.');
    } finally {
        IPSModuleStrict::$ParentResponder = $riverParent;
    }
}
$river->SetTestProperty('Aggregation', 'hour');
$river->SetTestProperty('Sources', '[{"VariableID":4713}]');
$river->ApplyChanges();
assertGatewayGauge($river->GetTestStatus() === 204 && str_contains($river->GetVisualizationTile(), 'ThemeRiver requires standard archive variables, not counters.'), 'Counter archives must be rejected visibly.');
$river->SetTestProperty('Sources', json_encode($riverSources, JSON_THROW_ON_ERROR));
foreach (['Aggregation'  => ['minute', 'hour'], 'Range' => ['invalid', '24h'], 'Title' => [str_repeat('X', 201), 'Archived power'],
    'BandOpacityPercent' => [101, 85], 'BorderWidth' => [-1, 0], 'TitleColor' => [-2, -1], 'LabelFontSizePercent' => [201, 100]] as $field => [$bad, $good]) {
    $river->SetTestProperty($field, $bad);
    $river->ApplyChanges();
    assertGatewayGauge($river->GetTestStatus() === 202, 'Invalid ThemeRiver settings must fail: ' . $field);
    $river->SetTestProperty($field, $good);
}
$river->ApplyChanges();
$river->SetTestProperty('IPSViewUseTileDesign', false);
$river->SetTestProperty('IPSViewBandOpacityPercent', 101);
$river->ApplyChanges();
assertGatewayGauge($river->GetTestStatus() === 202, 'An invalid independent IPSView design must be rejected.');
$river->SetTestProperty('IPSViewUseTileDesign', true);
$river->ApplyChanges();
assertGatewayGauge($river->GetTestStatus() === IS_ACTIVE, 'Unused independent draft values must not invalidate inherited design.');
$river->SetTestProperty('Sources', json_encode([$riverSources[0]], JSON_THROW_ON_ERROR));
$river->ApplyChanges();
assertGatewayGauge($river->GetTestReferences() === [88500], 'Removed ThemeRiver sources must unregister references.');
(require __DIR__ . '/archive_custom_interval_integration.php')($river, 'ThemeRiver', 'Aggregation');
$river->SetTestParentActive(false);
$river->ApplyChanges();
assertGatewayGauge($river->GetTestStatus() === 203 && $river->GetTestTimerInterval('ArchiveRefresh') === 0, 'Missing parent disables archive refresh.');
$riverLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsThemeRiver/locale.json'), true)['translations']['de'];
$riverCaptionCheck = static function (array $items) use (&$riverCaptionCheck, $riverLocale): void
{
    foreach ($items as $key => $item) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($item)) {
            assertGatewayGauge(isset($riverLocale[$item]), 'Missing German ThemeRiver caption: ' . $item);
        } elseif (is_array($item)) {
            $riverCaptionCheck($item);
        }
    }
};
$riverCaptionCheck(json_decode(file_get_contents(__DIR__ . '/../EChartsThemeRiver/form.json'), true));
preg_match_all("/(?:->Translate|(?<![\"\\w])translate)\\(\\s*'([^']+)'/", file_get_contents(__DIR__ . '/../EChartsThemeRiver/module.php')
    . file_get_contents(__DIR__ . '/../EChartsThemeRiver/ThemeRiverPreview.php')
    . file_get_contents(__DIR__ . '/../EChartsThemeRiver/visualization/app.js'), $riverTexts);
foreach ($riverTexts[1] as $text) {
    assertGatewayGauge(isset($riverLocale[$text]), 'Missing German ThemeRiver runtime text: ' . $text);
}
unset($riverForm, $riverSvg, $riverXML, $riverData, $riverRows, $river);
date_default_timezone_set($riverZone);
echo "ThemeRiver integration verified.\n";
