<?php

declare(strict_types=1);

if (!isset($gateway)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/gateway_gauges.php') . ' --state-timeline', $exitCode);
    exit($exitCode);
}
require_once dirname(__DIR__) . '/EChartsStateTimeline/module.php';

final class TestableEChartsStateTimeline extends EChartsStateTimeline
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsStateTimeline/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsStateTimeline/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return 1792238400;
    }
}

$end = 1792238400;
$start = $end - 86400;
$ids = [88901, 88902, 88903];
$timelineSources = [];
foreach ($ids as $index => $id) {
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => $index === 2 ? 1 : 0,
        'VariableUpdated'                                  => $end, 'Value' => $index === 2 ? 99 : true, 'Name' => 'Same <contact>'];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => $index === 2 ? [] : [
        ['TimeStamp' => $end, 'Value' => false],
        ['TimeStamp' => $start + 1000, 'Value' => true],
        ['TimeStamp' => $start + 500, 'Value' => false],
        ...($index === 0 ? [['TimeStamp' => $start - 1, 'Value' => true]] : [])
    ]];
    $timelineSources[] = ['VariableID' => $id, 'Label' => '', 'States' => [
        ['Value' => 0, 'Label' => 'Closed <safe>', 'Color' => 0x22AA44],
        ['Value' => 1, 'Label' => 'Open & known', 'Color' => 0xAA2233]
    ]];
}
$timeline = new TestableEChartsStateTimeline();
$timeline->Create();
$timeline->SetTestProperty('Title', 'Archived states');
$timeline->SetTestProperty('Sources', json_encode($timelineSources, JSON_THROW_ON_ERROR));
$timeline->SetTestProperty('Range', '24h');
$timeline->SetTestProperty('EnableIPSView', true);
$timeline->ApplyChanges();
$model = json_decode($timeline->GetStateTimelineData(), true, 512, JSON_THROW_ON_ERROR);
$rows = $model['stateTimeline']['rows'];
assertGatewayGauge($timeline->GetTestStatus() === IS_ACTIVE && $timeline->GetTestReferences() === $ids
    && $timeline->GetTestTimerInterval('ArchiveRefresh') === 60000, 'StateTimeline must initialize sources and bounded refresh.');
assertGatewayGauge(
    count($rows) === 3 && $rows[0]['id'] !== $rows[1]['id'] && $rows[0]['label'] === $rows[1]['label'],
    'Equal names must preserve distinct ID-based rows.'
);
assertGatewayGauge($rows[0]['segments'][0] === ['startTimestamp' => $start, 'endTimestamp' => $start + 500,
    'value'                                                      => 1, 'label' => 'Open & known', 'color' => '#AA2233'], 'The predecessor and source mapping must initialize the first state.');
assertGatewayGauge($rows[1]['segments'][0]['value'] === null && $rows[1]['segments'][0]['endTimestamp'] === $start + 500
    && $rows[2]['segments'][0]['value'] === null, 'Partial and empty archives must retain unknown states, not current values.');
assertGatewayGauge(
    $rows[0]['segments'][2]['endTimestamp'] === $end && count($rows[0]['segments']) === 3,
    'Sample exactly at right boundary must not create a zero-width segment.'
);

$html = $timeline->GetVisualizationTile();
$ipsHtml = $timeline->GetIPSViewHTML();
assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($ipsHtml) < SYMCON_OUTPUT_BUFFER_LIMIT
    && !str_contains($html, '{{') && !str_contains($ipsHtml, '{{')
    && $timeline->GetTestVariableValue('IPSViewStateTimeline') !== '', 'Both real HTML outputs must fit and have no unresolved placeholders.');
$savedHTML = $timeline->GetTestVariableValue('IPSViewStateTimeline');
$timeline->RefreshArchive();
assertGatewayGauge(
    $timeline->GetTestVariableValue('IPSViewStateTimeline') === $savedHTML,
    'Refreshing must push state, not rewrite WebContent.'
);
assertGatewayGauge(str_contains($html, '"tileHeaderVisible":true'), 'Tile must reserve native header space.');
$GLOBALS['symconTestHiddenTitles'][$timeline->InstanceID] = true;
assertGatewayGauge(str_contains($timeline->GetVisualizationTile(), '"tileHeaderVisible":false'), 'Hidden native title must release its inset.');
unset($GLOBALS['symconTestHiddenTitles'][$timeline->InstanceID]);

$timeline->SetTestProperty('IPSViewUseTileDesign', false);
$timeline->SetTestProperty('IPSViewRowHeightPercent', 35);
$timeline->SetTestProperty('IPSViewUnknownColor', 0x123456);
$timeline->ApplyChanges();
assertGatewayGauge(str_contains($timeline->GetIPSViewHTML(), '"rowHeightPercent":35')
    && str_contains($timeline->GetIPSViewHTML(), '"unknownColor":"#123456"')
    && str_contains($timeline->GetVisualizationTile(), '"rowHeightPercent":65'), 'IPSView design must be independent.');
$form = json_decode($timeline->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$extendedDesign = ['BorderType' => 'dashed', 'BorderRadius' => 12, 'LabelPosition' => 'right',
    'FontFamily'                => 'Georgia', 'TitleBold' => false, 'TitleItalic' => true, 'AxisLabelBold' => true,
    'AxisLabelItalic'           => true, 'LabelBold' => true, 'LabelItalic' => true, 'ShowTimeLabels' => false,
    'ShowSourceLabels'          => false, 'ShowAxisLine' => false, 'ShowAxisTicks' => true, 'AxisLineWidth' => 3,
    'AxisLineType'              => 'dotted', 'AxisLabelMargin' => 20, 'TimeLabelFormat' => 'dateTime',
    'GridColor'                 => 0x123ABC, 'GridOpacityPercent' => 45, 'GridLineWidth' => 2, 'GridLineType' => 'dashed',
    'EmphasisEnabled'           => false, 'EmphasisFocus' => 'self', 'BlurOpacityPercent' => 15];
foreach ($extendedDesign as $name => $value) {
    foreach (['', 'IPSView'] as $prefix) {
        $control = findGaugeFormElement($form['elements'], $prefix . $name);
        assertGatewayGauge(
            $control !== null && str_contains($control['onChange'] ?? '', 'ECST_UpdateBarPreviewFromForm'),
            'Every extended StateTimeline design field needs mirrored live preview controls: ' . $prefix . $name
        );
    }
    $timeline->SetTestProperty($name, $value);
}
$extended = json_decode($timeline->GetStateTimelineData(), true, 512, JSON_THROW_ON_ERROR);
$independentHTML = $timeline->GetIPSViewHTML();
foreach ($extendedDesign as $name => $value) {
    assertGatewayGauge(
        $extended['stateTimeline']['style'][lcfirst($name)] === ($name === 'GridColor' ? '#123ABC' : $value),
        'Extended StateTimeline design must reach the renderer: ' . $name
    );
}
assertGatewayGauge(
    str_contains($independentHTML, '"fontFamily":"Arial"')
    && str_contains($independentHTML, '"borderRadius":0') && str_contains($independentHTML, '"showTimeLabels":true'),
    'An independent IPSView designer must not inherit the changed tile design.'
);
assertGatewayGauge($extended['stateTimeline']['rows'] === $rows, 'Design changes must not change state intervals or mappings.');
// Restore tile defaults before the existing independent-design and draft checks.
$defaults = (new ReflectionClass(EChartsStateTimeline::class))->getConstant('DESIGN_DEFAULTS');
foreach ($extendedDesign as $name => $value) {
    $timeline->SetTestProperty($name, $defaults[$name]);
}
$translations = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsStateTimeline/locale.json'), true)['translations']['de'];
$helperTranslations = json_decode((string) file_get_contents(dirname(__DIR__) . '/libs/helper/translations/IPSViewHTMLPageHelper.json'), true);
foreach ($helperTranslations['translations'] as $translation) {
    $translations[$translation['en']] = $translation['de'];
}
$verifyCaptions = static function (array $entries) use (&$verifyCaptions, $translations): void
{
    foreach ($entries as $key => $entry) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($entry)) {
            assertGatewayGauge(isset($translations[$entry]) || $entry === '', 'Missing German StateTimeline caption: ' . $entry);
        } elseif (is_array($entry)) {
            $verifyCaptions($entry);
        }
    }
};
$verifyCaptions($form['elements']);
$sourceControl = findGaugeFormElement($form['elements'], 'Sources');
assertGatewayGauge($sourceControl['columns'][2]['edit']['type'] === 'List' && $sourceControl['columns'][2]['save'] === true
    && $sourceControl['columns'][2]['add'] === [], 'Nested state definitions need an array-preserving List column and empty add default.');
foreach (['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'] as $event) {
    assertGatewayGauge(str_contains($sourceControl[$event], 'ECST_UpdateBarPreviewFromForm'), 'Source edits must refresh both previews.');
}
$properties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($timeline);
$queryCount = $GLOBALS['symconTestArchiveQueryCount'];
$draftSources = $timelineSources;
$draftSources[0]['States'][0] = ['Value' => 0, 'Label' => 'Draft label', 'Color' => 0xABCDEF];
$timeline->UpdateBarPreviewFromForm(json_encode(['Sources' => $draftSources, 'Title' => 'Draft <title>',
    'ShowLabels'                                           => true, 'RowHeightPercent' => 100, 'LabelFontSizePercent' => 50], JSON_THROW_ON_ERROR));
$updates = array_slice($timeline->GetTestFormUpdates(), -2);
foreach ($updates as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    $xml = new DOMDocument();
    assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-state-interval="true"'), 'Draft previews must be valid SVG.');
}
$tileSVG = base64_decode(substr($updates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($tileSVG, '#ABCDEF') && str_contains($tileSVG, 'Draft label') && str_contains($tileSVG, 'Draft &lt;title&gt;'),
    'Draft nested states, colors and escaped titles must reach preview.'
);
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($timeline) === $properties
    && $GLOBALS['symconTestArchiveQueryCount'] === $queryCount, 'Preview must not persist or query archives.');

$previewDesign = [...$extendedDesign, 'ShowTimeLabels' => true, 'ShowSourceLabels' => true,
    'ShowAxisLine'                                     => true, 'BorderWidth' => 2, 'EnableZoom' => true, 'LabelFontSizePercent' => 50];
$timeline->UpdateBarPreviewFromForm(json_encode($previewDesign, JSON_THROW_ON_ERROR));
$styledUpdates = array_slice($timeline->GetTestFormUpdates(), -2);
$svg = base64_decode(substr($styledUpdates[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$xml = new DOMDocument();
assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET), 'Extended preview must remain valid SVG.');
$xpath = new DOMXPath($xml);
$xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
assertGatewayGauge(
    $xml->documentElement->getAttribute('font-family') === 'Georgia'
    && $xpath->query('//s:text[@x="24" and @font-weight="normal" and @font-style="italic"]')->length === 1
    && $xpath->query('//s:rect[@data-state-interval and @rx="12" and @stroke-dasharray="8 4"]')->length > 0
    && $xpath->query('//s:text[@data-state-label and @text-anchor="end" and @font-weight="bold" and @font-style="italic"]')->length > 0
    && $xpath->query('//s:text[@data-state-label and @clip-path]')->length === $xpath->query('//s:text[@data-state-label]')->length
    && $xpath->query('//s:path[@data-state-grid and @stroke="#123ABC" and @opacity="0.45" and @stroke-width="2"]')->length === 5
    && $xpath->query('//s:text[@data-time-label]/s:tspan')->length === 5
    && $xpath->query('//s:path[@data-time-axis and @stroke-dasharray="3 3"]')->length === 1
    && $xpath->query('//s:path[@data-time-tick]')->length === 5,
    'Preview must visibly apply typography, contour, alignment, grid, axis and two-line time format.'
);
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($timeline) === $properties
    && $GLOBALS['symconTestArchiveQueryCount'] === $queryCount, 'Extended design drafts must remain read-only.');
$independentDraft = ['IPSViewUseTileDesign' => false, 'IPSViewFontFamily' => 'Verdana', 'IPSViewBorderRadius' => 5,
    'IPSViewBorderType'                     => 'dotted', 'IPSViewBorderWidth' => 3, 'IPSViewLabelPosition' => 'left',
    'IPSViewShowTimeLabels'                 => false, 'IPSViewShowSourceLabels' => false, 'IPSViewLabelFontSizePercent' => 50];
$timeline->UpdateBarPreviewFromForm(json_encode([...$previewDesign, ...$independentDraft], JSON_THROW_ON_ERROR));
$independentUpdates = array_slice($timeline->GetTestFormUpdates(), -2);
$independentSVG = base64_decode(substr($independentUpdates[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($independentSVG, 'font-family="Verdana"') && str_contains($independentSVG, 'rx="5"')
    && str_contains($independentSVG, 'stroke-dasharray="3 3"') && str_contains($independentSVG, 'text-anchor="start"')
    && !str_contains($independentSVG, 'data-time-label') && !str_contains($independentSVG, 'data-source-label'),
    'Independent IPSView preview must use its own typography, contour, alignment and axis visibility.'
);
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($timeline) === $properties
    && $GLOBALS['symconTestArchiveQueryCount'] === $queryCount, 'Independent IPSView drafts must remain read-only.');
$timeline->UpdateBarPreviewFromForm(json_encode(['ShowTimeLabels' => false, 'ShowSourceLabels' => false,
    'ShowAxisLine'                                                => false, 'ShowAxisTicks' => false, 'FontFamily' => 'invalid" onload="alert(1)'], JSON_THROW_ON_ERROR));
$hiddenSVG = base64_decode(substr(array_slice($timeline->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    !str_contains($hiddenSVG, 'data-time-label') && !str_contains($hiddenSVG, 'data-source-label')
    && !str_contains($hiddenSVG, 'data-time-axis') && !str_contains($hiddenSVG, 'data-time-tick')
    && str_contains($hiddenSVG, 'font-family="Arial"') && !str_contains($hiddenSVG, 'onload'),
    'Preview visibility toggles and the font whitelist must work without unsafe SVG attributes.'
);

$GLOBALS['symconTestCopyTarget'] = $timeline;
$timeline->SetTestProperty('RowHeightPercent', 47);
foreach ($extendedDesign as $name => $value) {
    $timeline->SetTestProperty($name, $value);
}
$timeline->CopyTileDesignToIPSView();
assertGatewayGauge(str_contains($timeline->GetIPSViewHTML(), '"rowHeightPercent":47'), 'Copy action must preserve all effective design fields.');
$copied = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($timeline);
assertGatewayGauge(str_contains($timeline->GetIPSViewHTML(), '"fontFamily":"Georgia"')
    && str_contains($timeline->GetIPSViewHTML(), '"borderRadius":12'), 'Copied typography and contour must reach independent IPSView.');
foreach ($extendedDesign as $name => $value) {
    assertGatewayGauge($copied['IPSView' . $name] === $value, 'Copy must include extended design: ' . $name);
    $timeline->SetTestProperty($name, $defaults[$name]);
    $timeline->SetTestProperty('IPSView' . $name, $defaults[$name]);
}
unset($GLOBALS['symconTestCopyTarget']);

$parent = IPSModuleStrict::$ParentResponder;
foreach ([
    static fn (array $data): array => array_replace($data, ['VariableID' => 99999]),
    static fn (array $data): array => array_replace($data, ['Truncated' => true]),
    static fn (array $data): array => array_replace($data, ['Points' => [[$start + 1, 1], [$start + 1, 0]]]),
    static fn (array $data): array => array_replace($data, ['Points' => [[$start, 2]]]),
    static fn (array $data): array => array_replace($data, ['PreviousPoint' => [$start - 1, 2]])
] as $alter) {
    IPSModuleStrict::$ParentResponder = static function (string $request) use ($parent, $alter): string
    {
        $response = json_decode($parent($request), true, 512, JSON_THROW_ON_ERROR);
        if (($response['Operation'] ?? null) === 'archive.states.read' && ($response['Success'] ?? false)) {
            $response['Payload'] = $alter($response['Payload']);
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    assertGatewayGauge(
        str_contains($timeline->GetVisualizationTile(), '"status":"error"') && $timeline->GetTestStatus() === 204,
        'Wrong identity, truncated and conflicting data must fail visibly.'
    );
}
IPSModuleStrict::$ParentResponder = $parent;
assertGatewayGauge(str_contains($timeline->GetVisualizationTile(), '"status":"ready"'), 'Valid data must recover after transport errors.');

$timeline->SetTestParentActive(false);
$timeline->ApplyChanges();
assertGatewayGauge(
    $timeline->GetTestStatus() === 203 && $timeline->GetTestTimerInterval('ArchiveRefresh') === 0,
    'Missing parent must stop refresh without deleting sources.'
);
$timeline->SetTestParentActive(true);
$timeline->ApplyChanges();
assertGatewayGauge($timeline->GetTestStatus() === IS_ACTIVE, 'Restored Gateway must recover.');

$manyPoints = [];
for ($index = 0; $index < 2000; ++$index) {
    $manyPoints[] = [$start + $index, $index % 2];
}
IPSModuleStrict::$ParentResponder = static function (string $request) use ($parent, $manyPoints): string
{
    $response = json_decode($parent($request), true, 512, JSON_THROW_ON_ERROR);
    if (($response['Operation'] ?? null) === 'archive.states.read' && ($response['Success'] ?? false)) {
        $response['Payload']['Points'] = $manyPoints;
    }
    return json_encode($response, JSON_THROW_ON_ERROR);
};
$boundedHTML = $timeline->GetVisualizationTile();
assertGatewayGauge(
    str_contains($boundedHTML, 'Too many state changes. Choose a shorter time range.')
    && str_contains($boundedHTML, '"status":"error"') && strlen($boundedHTML) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Large state payloads must fail visibly inside the output buffer, never be sampled or silently truncated.'
);
IPSModuleStrict::$ParentResponder = $parent;

foreach ([['Range', 'invalid'], ['RowHeightPercent', 101], ['UnknownColor', -2], ['AnimationDuration', 99999],
    ['BorderType', 'invalid'], ['BorderRadius', 21], ['LabelPosition', 'outside'], ['FontFamily', 'unsafe'],
    ['AxisLineType', 'invalid'], ['AxisLineWidth', 6], ['AxisLabelMargin', 31], ['TimeLabelFormat', 'invalid'],
    ['GridColor', 0x1000000], ['GridLineType', 'invalid'], ['GridLineWidth', -1], ['GridOpacityPercent', 101],
    ['EmphasisFocus', 'series'], ['BlurOpacityPercent', -1], ['IPSViewBorderRadius', 21]] as [$name, $value]) {
    $original = $properties[$name];
    $timeline->SetTestProperty($name, $value);
    $timeline->ApplyChanges();
    assertGatewayGauge(
        $timeline->GetTestStatus() === 202 && $timeline->GetTestTimerInterval('ArchiveRefresh') === 0,
        'Invalid settings must stop refresh with configuration status.'
    );
    $timeline->SetTestProperty($name, $original);
}
foreach ([
    [['VariableID' => 4711]],
    [['VariableID' => 4712]],
    [$timelineSources[0], $timelineSources[0]],
    [['VariableID' => $ids[0], 'States' => [['Value' => 2, 'Label' => 'Invalid Boolean']]]],
    [['VariableID' => $ids[0], 'States' => [['Value' => 0, 'Label' => 'One'], ['Value' => 0, 'Label' => 'Two']]]]
] as $invalidSources) {
    $timeline->SetTestProperty('Sources', json_encode($invalidSources, JSON_THROW_ON_ERROR));
    $timeline->ApplyChanges();
    assertGatewayGauge($timeline->GetTestStatus() === 201, 'Bad source types, duplicate IDs and state mappings must be rejected.');
}
$timeline->SetTestProperty('Sources', json_encode([$timelineSources[0]], JSON_THROW_ON_ERROR));
$timeline->ApplyChanges();
assertGatewayGauge($timeline->GetTestReferences() === [$ids[0]], 'Removed sources must release references.');
$GLOBALS['symconTestVariables'][$ids[0]]['Name'] = 'Renamed contact';
assertGatewayGauge(
    json_decode($timeline->GetStateTimelineData(), true)['stateTimeline']['rows'][0]['label'] === 'Renamed contact',
    'Source labels must follow rename without changing ID identity.'
);
assertGatewayGauge(str_contains($timeline->GetCompatibleParents(), 'connect'), 'StateTimeline must use the existing selectable shared Gateway.');
$adversarial = [$timelineSources[0]];
$adversarial[0]['States'][0]['Label'] = str_repeat('&', 80);
$adversarial[0]['States'][1]['Label'] = str_repeat('&', 80);
$timeline->SetTestProperty('Sources', json_encode($adversarial, JSON_THROW_ON_ERROR));
IPSModuleStrict::$ParentResponder = static function (string $request) use ($parent, $manyPoints): string
{
    $response = json_decode($parent($request), true, 512, JSON_THROW_ON_ERROR);
    if (($response['Operation'] ?? null) === 'archive.states.read' && ($response['Success'] ?? false)) {
        $response['Payload']['Points'] = array_slice($manyPoints, 0, 900);
        $response['Payload']['PreviousPoint'] = null;
    }
    return json_encode($response, JSON_THROW_ON_ERROR);
};
$safeHTML = $timeline->GetIPSViewHTML();
assertGatewayGauge(
    strlen($safeHTML) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($safeHTML, '"status":"error"'),
    'HTML-safe escaping of valid state labels must not exceed the output buffer.'
);
IPSModuleStrict::$ParentResponder = $parent;
echo "StateTimeline Gateway, forms, previews and outputs verified.\n";
