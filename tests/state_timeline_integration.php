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

$GLOBALS['symconTestCopyTarget'] = $timeline;
$timeline->SetTestProperty('RowHeightPercent', 47);
$timeline->CopyTileDesignToIPSView();
assertGatewayGauge(str_contains($timeline->GetIPSViewHTML(), '"rowHeightPercent":47'), 'Copy action must preserve all effective design fields.');
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

foreach ([['Range', 'invalid'], ['RowHeightPercent', 101], ['UnknownColor', -2], ['AnimationDuration', 99999]] as [$name, $value]) {
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
