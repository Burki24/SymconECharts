<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

if (!isset($gateway)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/gateway_gauges.php') . ' --calendar-pie', $exitCode);
    exit($exitCode);
}
require_once dirname(__DIR__) . '/EChartsCalendarPie/module.php';

// Complete circles need two half-arcs: rounding a tiny endpoint gap can otherwise erase a single-state day.
foreach ([true, false] as $clockwise) {
    foreach ([0.0, 4.0] as $innerRadius) {
        $path = SymconECharts\EChartsBarPreview::Sector(
            20.0,
            30.0,
            $innerRadius,
            10.0,
            -M_PI / 2,
            -M_PI / 2 + ($clockwise ? 1 : -1) * 2 * M_PI,
            $clockwise
        );
        preg_match_all('/ A ([\d.]+) ([\d.]+) 0 ([01]) ([01]) (-?[\d.]+) (-?[\d.]+)/', $path, $arcs, PREG_SET_ORDER);
        assertGatewayGauge(
            !str_contains($path, ' L ') && substr_count($path, 'M ') === ($innerRadius > 0 ? 2 : 1),
            'Complete circles and rings must not show an artificial radial seam.'
        );
        assertGatewayGauge(count($arcs) === ($innerRadius > 0 ? 4 : 2)
            && !preg_match('/(?:nan|inf)/i', $path), 'Full-circle and donut previews must use finite two-half-arc contours in both directions.');
        foreach ($arcs as $index => $arc) {
            $expectedSweep = $index < 2 ? (int) $clockwise : (int) !$clockwise;
            assertGatewayGauge(
                (int) $arc[4] === $expectedSweep && $arc[3] === '0'
                && abs(hypot((float) $arc[5] - 20.0, (float) $arc[6] - 30.0) - (float) $arc[1]) < 0.02,
                'Outer and inner full-circle arcs must follow opposite sweep directions and retain their radii.'
            );
        }
    }
    $quarter = SymconECharts\EChartsBarPreview::Sector(
        20.0,
        30.0,
        0.0,
        10.0,
        0.0,
        ($clockwise ? 1 : -1) * M_PI / 2,
        $clockwise
    );
    assertGatewayGauge(
        str_contains($quarter, $clockwise ? ' A 10 10 0 0 1 20 40' : ' A 10 10 0 0 0 20 20'),
        'Partial preview sectors must reach the independently calculated clockwise/counterclockwise endpoints.'
    );
}

final class TestableEChartsCalendarPie extends EChartsCalendarPie
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCalendarPie/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsCalendarPie/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return strtotime('2026-10-18 12:00:00');
    }
}

$savedTimezone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$ids = [88701, 88702];
$counterIDs = [88703, 88704];
$stateID = 88705;
$sources = [];
foreach ([...$ids, ...$counterIDs] as $index => $id) {
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792317600,
        'Value'                                            => 999999.0, 'Name' => 'Equal <source>', 'Presentation' => ['SUFFIX' => ' kWh', 'DIGITS' => 2]];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => $index < 2 ? 0 : 1,
        'LoggedValues'                                               => [], 'AggregatedValues' => [
            ['TimeStamp' => strtotime('2026-10-18 00:00:00'), 'Avg' => 9999.0, 'Min' => 0.0, 'Max' => 9999.0],
            ['TimeStamp' => strtotime('2026-10-17 00:00:00'), 'Avg' => 0.0, 'Min' => 0.0, 'Max' => 0.0],
            ...($index % 2 === 0 ? [['TimeStamp' => strtotime('2026-10-16 09:00:00'), 'Avg' => 4.0, 'Min' => 4.0, 'Max' => 4.0]] : []),
            ['TimeStamp' => strtotime('2026-10-15 00:00:00'), 'Avg' => $index % 2 === 0 ? 6.0 : 3.0, 'Min' => 1.0, 'Max' => 8.0],
            ['TimeStamp' => strtotime('2026-09-30 00:00:00'), 'Avg' => 2.0, 'Min' => 1.0, 'Max' => 4.0]
        ]];
    if ($index < 2) {
        $sources[] = ['VariableID' => $id, 'Label' => '', 'Color' => $index === 0 ? 0x3366AA : -1];
    }
}
$GLOBALS['symconTestVariables'][$stateID] = ['VariableType' => 0, 'VariableUpdated' => 1792317600,
    'Value'                                                 => false, 'Name' => 'Archived <status>'];
$GLOBALS['symconTestArchiveVariables'][$stateID] = ['AggregationType' => 0, 'LoggedValues' => [
    ['TimeStamp' => strtotime('2026-10-18 00:00:00'), 'Value' => true],
    ['TimeStamp' => strtotime('2026-10-02 00:00:00'), 'Value' => false],
    ['TimeStamp' => strtotime('2026-10-01 12:00:00'), 'Value' => true]
]];
$definitions = [['Value' => 0, 'Label' => 'Stopped <safe>', 'Color' => 0xAABBCC],
    ['Value' => 1, 'Label' => 'Running & active', 'Color' => 0x11AA44]];
$calendarPie = new TestableEChartsCalendarPie();
$calendarPie->Create();
$calendarPie->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
$calendarPie->SetTestProperty('Title', 'Daily source comparison');
$calendarPie->SetTestProperty('StateVariableID', $stateID);
$calendarPie->SetTestProperty('States', json_encode($definitions, JSON_THROW_ON_ERROR));
$calendarPie->SetTestProperty('EnableIPSView', true);
$requests = [];
$parent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$requests): string
{
    $requests[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return $parent($json);
};
$calendarPie->ApplyChanges();
$model = json_decode($calendarPie->GetCalendarPieData(), true, 512, JSON_THROW_ON_ERROR);
$chart = $model['calendarPie'];
$days = array_column($chart['days'], null, 'date');
assertGatewayGauge($calendarPie->GetTestStatus() === IS_ACTIVE && $calendarPie->GetTestReferences() === $ids
    && $calendarPie->GetTestTimerInterval('ArchiveRefresh') === 60000, 'CalendarPie must initialize only active source references and bounded refresh.');
assertGatewayGauge(
    $model['family'] === 'calendarPie' && $chart['mode'] === 'average' && $chart['month'] === '2026-10'
    && $chart['unit'] === 'kWh' && $chart['decimals'] === 2 && count($chart['days']) === 31,
    'CalendarPie must resolve the local month and common effective variable presentation.'
);
assertGatewayGauge($chart['categories'][0]['id'] !== $chart['categories'][1]['id']
    && $chart['categories'][0]['label'] === $chart['categories'][1]['label'], 'Duplicate source names must retain distinct variable IDs.');
assertGatewayGauge(
    $days['2026-10-15']['status'] === 'complete'
    && array_column($days['2026-10-15']['slices'], 'value') === [6.0, 3.0]
    && $days['2026-10-16']['status'] === 'partial' && $days['2026-10-16']['missing'] === ['variable-' . $ids[1]]
    && $days['2026-10-17']['status'] === 'zero' && $days['2026-10-14']['status'] === 'missing'
    && $days['2026-10-18']['status'] === 'pending' && $days['2026-10-18']['slices'] === [],
    'Daily means must preserve complete, partial, zero, missing and unfinished days without current-value or zero fallback.'
);
foreach ($requests as $request) {
    if (($request['Operation'] ?? '') === 'archive.read') {
        $payload = $request['Payload'];
        assertGatewayGauge(
            $payload['Reducer'] === 'average' && $payload['AggregationLevel'] === 1 && $payload['Limit'] === 31
            && $payload['EndTimestamp'] === strtotime('2026-10-18') - 1,
            'Numeric reads must use explicit daily mean aggregation and exclude the unfinished local day.'
        );
    }
}
IPSModuleStrict::$ParentResponder = $parent;

$tile = $calendarPie->GetVisualizationTile();
$ipsView = $calendarPie->GetIPSViewHTML();
assertGatewayGauge(strlen($tile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($ipsView) < SYMCON_OUTPUT_BUFFER_LIMIT
    && !str_contains($tile, '{{') && !str_contains($ipsView, '{{')
    && $calendarPie->GetTestVariableValue('IPSViewCalendarPie') !== ''
    && str_contains($tile, '"tileHeaderVisible":true'), 'Both real HTML outputs must fit, resolve assets, protect the native header and publish IPSView WebContent.');
$savedHTML = $calendarPie->GetTestVariableValue('IPSViewCalendarPie');
$calendarPie->RefreshArchive();
assertGatewayGauge($calendarPie->GetTestVariableValue('IPSViewCalendarPie') === $savedHTML, 'Refresh must push data without rewriting WebContent.');
$GLOBALS['symconTestHiddenTitles'][$calendarPie->InstanceID] = true;
assertGatewayGauge(str_contains($calendarPie->GetVisualizationTile(), '"tileHeaderVisible":false'), 'A hidden native title must release header space.');
unset($GLOBALS['symconTestHiddenTitles'][$calendarPie->InstanceID]);
assertTransientRetry($calendarPie, 'CalendarPie');

$calendarPie->SetTestProperty('IPSViewUseTileDesign', false);
$calendarPie->SetTestProperty('IPSViewPieRadiusPercent', 45);
$calendarPie->SetTestProperty('IPSViewInnerRadiusPercent', 30);
$calendarPie->SetTestProperty('IPSViewTitleColor', 0x123456);
$calendarPie->ApplyChanges();
assertGatewayGauge(str_contains($calendarPie->GetIPSViewHTML(), '"pieRadiusPercent":45')
    && str_contains($calendarPie->GetIPSViewHTML(), '"titleColor":"#123456"')
    && str_contains($calendarPie->GetVisualizationTile(), '"pieRadiusPercent":70'), 'Independent IPSView design must not alter the tile.');
$formQueries = $GLOBALS['symconTestArchiveQueryCount'];
$form = json_decode($calendarPie->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $GLOBALS['symconTestArchiveQueryCount'] === $formQueries,
    'Opening both CalendarPie designers must not read any archives.'
);
$defaults = (new ReflectionClass(EChartsCalendarPie::class))->getConstant('DESIGN_DEFAULTS');
foreach ($defaults as $name => $default) {
    foreach (['', 'IPSView'] as $prefix) {
        $control = findGaugeFormElement($form['elements'], $prefix . $name);
        assertGatewayGauge(
            $control !== null && str_contains($control['onChange'] ?? '', 'ECCP_UpdateBarPreviewFromForm'),
            'Every CalendarPie design field needs both live preview controls: ' . $prefix . $name
        );
    }
}
$translations = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCalendarPie/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
$helperTranslations = json_decode((string) file_get_contents(dirname(__DIR__) . '/libs/helper/translations/IPSViewHTMLPageHelper.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (array_keys((new ReflectionMethod(EChartsCalendarPie::class, 'RendererTranslations'))->invoke($calendarPie)) as $key) {
    assertGatewayGauge(isset($translations[$key]), 'Missing German CalendarPie renderer text: ' . $key);
}
foreach ($helperTranslations['translations'] as $translation) {
    $translations[$translation['en']] = $translation['de'];
}
$verifyCaptions = static function (array $entries) use (&$verifyCaptions, $translations): void
{
    foreach ($entries as $key => $entry) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($entry)) {
            assertGatewayGauge(isset($translations[$entry]) || $entry === '', 'Missing German CalendarPie caption: ' . $entry);
        } elseif (is_array($entry)) {
            $verifyCaptions($entry);
        }
    }
};
$verifyCaptions($form['elements']);
foreach (['Sources', 'States'] as $name) {
    $control = findGaugeFormElement($form['elements'], $name);
    foreach (['onAdd', 'onEdit', 'onDelete'] as $event) {
        assertGatewayGauge(str_contains($control[$event] ?? '', 'ECCP_UpdateBarPreviewFromForm'), 'Both source/state List edits must refresh previews.');
    }
}
assertGatewayGauge(
    findGaugeFormElement($form['elements'], 'Sources')['visible'] === true
    && findGaugeFormElement($form['elements'], 'States')['visible'] === false
    && str_contains(findGaugeFormElement($form['elements'], 'DataMode')['onChange'], 'ECCP_UpdateDataMode'),
    'The initial numeric form and dynamic mode visibility must expose the correct input controls.'
);
$properties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie);
$queries = $GLOBALS['symconTestArchiveQueryCount'];
$calendarPie->UpdateDataMode('states');
$visibility = array_slice($calendarPie->GetTestFormUpdates(), -8);
assertGatewayGauge(
    count($visibility) === 8 && $visibility[0]['Value'] === false && $visibility[7]['Value'] === true,
    'Mode changes must only update active-control visibility.'
);
foreach (['average', 'counter', 'states'] as $mode) {
    $calendarPie->UpdateBarPreviewFromForm(json_encode(['DataMode' => $mode, 'Sources' => $sources,
        'StateVariableID'                                          => $stateID, 'States' => $definitions, 'Title' => 'Draft <calendar>',
        'Month'                                                    => '2026-09', 'InnerRadiusPercent' => 35, 'PieRadiusPercent' => 90,
        'SliceBorderWidth'                                         => 5, 'SliceBorderColor' => 0xABCDEF,
        'IPSViewUseTileDesign'                                     => false, 'IPSViewTitleColor' => 0x123456], JSON_THROW_ON_ERROR));
    foreach (array_slice($calendarPie->GetTestFormUpdates(), -2) as $output => $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        $xml = new DOMDocument();
        assertGatewayGauge(is_string($svg) && $xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'Draft &lt;calendar&gt;')
            && str_contains($svg, 'Example data'), 'Every mode needs valid escaped synthetic SVG previews.');
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
        assertGatewayGauge(
            $xpath->query('//s:rect[@data-calendar-day]')->length === 30
            && str_contains($svg, '2026-09') && str_contains($svg, $mode === 'states' ? '#11AA44' : '#3366AA')
            && ($output !== 0 || str_contains($svg, '#ABCDEF'))
            && ($output !== 1 || str_contains($svg, '#123456')),
            'Draft month, source/state colors and independent output design must visibly reach preview.'
        );
        if ($mode === 'states') {
            assertGatewayGauge(
                str_contains($svg, 'Stopped &lt;safe&gt;') && str_contains($svg, 'Running &amp; active')
                && $xpath->query('//s:path[@data-calendar-pie and @fill="#AABBCC"]')->length > 0
                && $xpath->query('//s:path[@data-calendar-pie and @fill="#11AA44"]')->length > 0,
                'State previews must use the unsaved mapping labels and colors, not generic numeric categories.'
            );
        }
        foreach ($xpath->query('//s:path[@data-calendar-pie]') as $sector) {
            $path = $sector->getAttribute('d');
            assertGatewayGauge(
                !preg_match('/(?:nan|inf)/i', $path) && preg_match('/^M (-?[\d.]+) (-?[\d.]+)/', $path, $firstPoint) === 1,
                'Synthetic pie contours must contain finite absolute coordinates.'
            );
            $containingCell = null;
            foreach ($xpath->query('//s:rect[@data-calendar-day]') as $cell) {
                $x = (float) $cell->getAttribute('x');
                $y = (float) $cell->getAttribute('y');
                $width = (float) $cell->getAttribute('width');
                $height = (float) $cell->getAttribute('height');
                if ((float) $firstPoint[1] > $x && (float) $firstPoint[1] < $x + $width
                    && (float) $firstPoint[2] > $y && (float) $firstPoint[2] < $y + $height) {
                    $containingCell = [$x, $y, $width, $height];
                    break;
                }
            }
            assertGatewayGauge($containingCell !== null, 'Every preview circle must remain inside one calendar day.');
            [$x, $y, $width, $height] = $containingCell;
            preg_match_all('/ A ([\d.]+) ([\d.]+) 0 [01] [01] (-?[\d.]+) (-?[\d.]+)/', $path, $arcs, PREG_SET_ORDER);
            foreach ($arcs as $arc) {
                $radius = (float) $arc[1];
                $stroke = (float) $sector->getAttribute('stroke-width') / 2;
                assertGatewayGauge(
                    $radius + $stroke <= min($width, $height) / 2 + 0.02
                    && abs(hypot((float) $arc[3] - ($x + $width / 2), (float) $arc[4] - ($y + $height / 2)) - $radius) < 0.03,
                    'Preview arc radii and borders must not overlap neighboring day cells.'
                );
            }
        }
    }
}
foreach ([0, 60] as $innerRadius) {
    $calendarPie->UpdateBarPreviewFromForm(json_encode(['DataMode' => 'average', 'Sources' => [$sources[0]],
        'Month'                                                    => '2026-09', 'InnerRadiusPercent' => $innerRadius], JSON_THROW_ON_ERROR));
    $singleSVG = base64_decode(substr(array_slice($calendarPie->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    $singleXML = new DOMDocument();
    assertGatewayGauge($singleXML->loadXML($singleSVG, LIBXML_NONET), 'Single-category preview must produce valid SVG.');
    $singleXPath = new DOMXPath($singleXML);
    $singleXPath->registerNamespace('s', 'http://www.w3.org/2000/svg');
    $sectors = $singleXPath->query('//s:path[@data-calendar-pie]');
    assertGatewayGauge($sectors->length > 0, 'A valid single-source month must still draw daily circles.');
    foreach ($sectors as $sector) {
        assertGatewayGauge(
            substr_count($sector->getAttribute('d'), ' A ') === ($innerRadius > 0 ? 4 : 2),
            'Real single-source SVG previews must not collapse complete circles or donut contours.'
        );
    }
}
$calendarPie->UpdateBarPreviewFromForm(json_encode(['DataMode' => 'states', 'StateVariableID' => $stateID,
    'States'                                                   => $definitions, 'Month' => '2026-09', 'EChartsTheme' => 'dark', 'UnknownColor' => -1], JSON_THROW_ON_ERROR));
$autoUnknownSVG = base64_decode(substr(array_slice($calendarPie->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($autoUnknownSVG, SymconECharts\EChartsAsset::ThemePreviewPalette('dark')['muted']),
    'Automatic unknown duration color must use the same theme-muted fallback as the native renderer.'
);
foreach ([0, 80] as $innerRadius) {
    $calendarPie->UpdateBarPreviewFromForm(json_encode(['DataMode' => 'average', 'Sources' => [$sources[0]],
        'Month'                                                    => '2026-09', 'ShowDayNumbers' => false, 'ShowValues' => true, 'ValueFontSizePercent' => 50,
        'PieRadiusPercent'                                         => 90, 'InnerRadiusPercent' => $innerRadius], JSON_THROW_ON_ERROR));
    $valueSVG = base64_decode(substr(array_slice($calendarPie->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        str_contains($valueSVG, 'data-slice-value') === ($innerRadius === 0),
        'Preview must show fitting numbers but suppress values in a ring too thin to hold the complete text.'
    );
}
date_default_timezone_set('Pacific/Apia');
$calendarPie->UpdateBarPreviewFromForm(json_encode(['DataMode' => 'average', 'Sources' => [$sources[0]], 'Month' => '2011-12'], JSON_THROW_ON_ERROR));
$skippedSVG = base64_decode(substr(array_slice($calendarPie->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$skippedXML = new DOMDocument();
assertGatewayGauge($skippedXML->loadXML($skippedSVG, LIBXML_NONET), 'Date-line preview must remain valid SVG.');
$skippedXPath = new DOMXPath($skippedXML);
$skippedXPath->registerNamespace('s', 'http://www.w3.org/2000/svg');
$lastDay = $skippedXPath->query('//s:rect[@data-calendar-day="2011-12-31"]')->item(0);
assertGatewayGauge(
    $skippedXPath->query('//s:rect[@data-calendar-day="2011-12-30"]')->length === 0
    && $lastDay !== null && abs((float) $lastDay->getAttribute('x') - (16 + 5 * 688 / 7)) < 0.02,
    'A skipped civil date must not move the following real date into the wrong calendar column.'
);
date_default_timezone_set('Europe/Berlin');
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie) === $properties
    && $GLOBALS['symconTestArchiveQueryCount'] === $queries, 'Mode and designer drafts must not persist properties or query archives.');
$GLOBALS['symconTestCopyTarget'] = $calendarPie;
try {
    $calendarPie->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$copied = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie);
foreach ($defaults as $name => $default) {
    assertGatewayGauge($copied[$name] === $copied['IPSView' . $name], 'Copy must include CalendarPie design: ' . $name);
}

preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $tile, $bootstrapMatch);
$bootstrap = json_decode($bootstrapMatch[1], true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(is_string($bootstrap['options']['calendarPieStatePath'] ?? null)
    && $bootstrap['options']['calendarPieStatePath'] !== '', 'The actual tile bootstrap must expose its authenticated month navigation path.');
preg_match('~/state/(\d+)/([a-f0-9]+)~', $bootstrap['options']['calendarPieStatePath'], $channel);
$request = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}'] + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
    'InstanceID' => (int) $channel[1], 'Channel' => $channel[2], 'WindowOffset' => 1, 'Output' => 'tile'
]);
$calendarPie->SetTestProperty('EnableIPSView', false);
$beforeNavigation = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie);
$response = EChartsDataProtocol::DecodeResponse($calendarPie->ReceiveData(json_encode($request, JSON_THROW_ON_ERROR)), EChartsDataProtocol::OPERATION_IPSVIEW_STATE);
assertGatewayGauge(
    $response['Success'] && $response['Payload']['State']['chart']['calendarPie']['month'] === '2026-09'
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie) === $beforeNavigation
    && json_decode($calendarPie->GetCalendarPieData(), true)['calendarPie']['month'] === '2026-10',
    'Native historical month navigation must work without IPSView and without persisting the month.'
);

$requests = [];
IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$requests): string
{
    $requests[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return $parent($json);
};
$counterSources = array_map(static fn (int $id): array => ['VariableID' => $id, 'Label' => '', 'Color' => -1], $counterIDs);
$calendarPie->SetTestProperty('DataMode', 'counter');
$calendarPie->SetTestProperty('Sources', json_encode($counterSources, JSON_THROW_ON_ERROR));
$calendarPie->SetTestProperty('States', 'invalid inactive state JSON');
$calendarPie->ApplyChanges();
$counter = json_decode($calendarPie->GetCalendarPieData(), true, 512, JSON_THROW_ON_ERROR)['calendarPie'];
assertGatewayGauge(
    $calendarPie->GetTestReferences() === $counterIDs && $counter['mode'] === 'counter'
    && array_column($counter['days'][14]['slices'], 'value') === [6.0, 3.0],
    'Counter mode must use archive daily consumption, ignore inactive state definitions and replace active references.'
);
foreach ($requests as $request) {
    if (($request['Operation'] ?? '') === 'archive.read') {
        assertGatewayGauge($request['Payload']['Reducer'] === 'sum', 'Counter consumption must explicitly request sum, never average or raw cumulative values.');
    }
}
IPSModuleStrict::$ParentResponder = $parent;

$calendarPie->SetTestProperty('DataMode', 'states');
$calendarPie->SetTestProperty('States', json_encode($definitions, JSON_THROW_ON_ERROR));
$calendarPie->SetTestProperty('Sources', 'invalid inactive numeric JSON');
$calendarPie->SetTestProperty('Unit', str_repeat('x', 30));
$calendarPie->SetTestProperty('Decimals', 99);
$calendarPie->ApplyChanges();
$stateChart = json_decode($calendarPie->GetCalendarPieData(), true, 512, JSON_THROW_ON_ERROR)['calendarPie'];
assertGatewayGauge(
    $calendarPie->GetTestStatus() === IS_ACTIVE && $calendarPie->GetTestReferences() === [$stateID]
    && $stateChart['mode'] === 'states' && $stateChart['unit'] === 'h' && $stateChart['decimals'] === 1,
    'State mode must use only its active Boolean/Integer source and ignore retained numeric-only inputs.'
);
$firstDay = $stateChart['days'][0];
assertGatewayGauge(
    $firstDay['status'] === 'partial' && array_column($firstDay['slices'], 'value', 'id') === ['state-1' => 43200, 'unknown' => 43200]
    && $stateChart['days'][1]['status'] === 'complete' && $stateChart['days'][1]['slices'][0]['id'] === 'state-0'
    && $stateChart['days'][17]['status'] === 'pending',
    'State durations must preserve unknown prefixes, exact seconds and mapped states without filling from the current value.'
);
$stateForm = json_decode($calendarPie->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    findGaugeFormElement($stateForm['elements'], 'Sources')['visible'] === false
    && findGaugeFormElement($stateForm['elements'], 'States')['visible'] === true,
    'The state form must hide numeric-only inputs without deleting their saved configuration.'
);

foreach ([
    static fn (array $data): array => array_replace($data, ['VariableID' => 99999]),
    static fn (array $data): array => array_replace($data, ['Truncated' => true]),
    static fn (array $data): array => array_replace($data, ['ArchiveAggregationType' => 'counter']),
    static fn (array $data): array => array_replace($data, ['Points' => [[strtotime('2026-10-01'), 2]]]),
    static fn (array $data): array => array_replace($data, ['PreviousPoint' => [strtotime('2026-09-30'), 2]])
] as $alter) {
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, $alter): string
    {
        $response = json_decode($parent($json), true, 512, JSON_THROW_ON_ERROR);
        if (($response['Operation'] ?? null) === 'archive.states.read' && ($response['Success'] ?? false)) {
            $response['Payload'] = $alter($response['Payload']);
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    assertGatewayGauge(
        str_contains($calendarPie->GetVisualizationTile(), '"status":"error"') && $calendarPie->GetTestStatus() === 204,
        'Invalid metadata, truncation and Boolean state values must fail visibly.'
    );
}
IPSModuleStrict::$ParentResponder = $parent;
assertGatewayGauge(str_contains($calendarPie->GetVisualizationTile(), '"status":"ready"'), 'CalendarPie must recover from transient archive faults.');

$calendarPie->SetTestProperty('DataMode', 'average');
$calendarPie->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
$calendarPie->SetTestProperty('Unit', '');
$calendarPie->SetTestProperty('Decimals', 1);
$calendarPie->ApplyChanges();
foreach ([
    static fn (array $data): array => array_replace($data, ['VariableType' => 1]),
    static fn (array $data): array => array_replace($data, ['EffectiveReducer' => 'sum']),
    static fn (array $data): array => array_replace($data, ['ArchiveAggregationType' => 'counter']),
    static fn (array $data): array => array_replace($data, ['Points' => [[strtotime('2026-10-15'), -1.0]]]),
    static fn (array $data): array => array_replace($data, ['Points' => [[strtotime('2026-10-15'), 1.0], [strtotime('2026-10-15'), 2.0]]])
] as $alter) {
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, $alter): string
    {
        $response = json_decode($parent($json), true, 512, JSON_THROW_ON_ERROR);
        if (($response['Operation'] ?? null) === 'archive.read' && ($response['Success'] ?? false)) {
            $response['Payload'] = $alter($response['Payload']);
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    assertGatewayGauge(
        str_contains($calendarPie->GetVisualizationTile(), '"status":"error"') && $calendarPie->GetTestStatus() === 204,
        'Numeric metadata, negative and conflicting daily values must fail visibly.'
    );
}
IPSModuleStrict::$ParentResponder = $parent;
foreach ([['average', $counterSources], ['counter', $sources]] as [$mode, $invalidKindSources]) {
    $calendarPie->SetTestProperty('DataMode', $mode);
    $calendarPie->SetTestProperty('Sources', json_encode($invalidKindSources, JSON_THROW_ON_ERROR));
    $calendarPie->ApplyChanges();
    assertGatewayGauge($calendarPie->GetTestStatus() === 204, 'Daily means and consumption must reject the opposite archive kind.');
}
$calendarPie->SetTestProperty('DataMode', 'average');
$calendarPie->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
$calendarPie->SetTestParentActive(false);
$calendarPie->ApplyChanges();
assertGatewayGauge(
    $calendarPie->GetTestStatus() === 203 && $calendarPie->GetTestTimerInterval('ArchiveRefresh') === 0,
    'Missing parent must stop refresh without deleting configuration.'
);
$calendarPie->SetTestParentActive(true);
$calendarPie->ApplyChanges();
assertGatewayGauge($calendarPie->GetTestStatus() === IS_ACTIVE, 'Restoring the Gateway must recover CalendarPie.');

foreach (['', 'IPSView'] as $prefix) {
    foreach (['FirstWeekday'   => 7, 'PieRadiusPercent' => 91, 'InnerRadiusPercent' => 81, 'StartAngle' => 361,
        'SliceBorderWidth'     => 6, 'CellBorderWidth' => 5, 'CellOpacityPercent' => 101, 'UnknownColor' => -2,
        'TitleFontSizePercent' => 201, 'AxisFontSizePercent' => 49, 'ValueFontSizePercent' => 201,
        'AnimationDuration'    => 99999, 'EChartsTheme' => 'unsafe'] as $name => $invalid) {
        $original = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie)[$prefix . $name];
        $calendarPie->SetTestProperty($prefix . $name, $invalid);
        $calendarPie->ApplyChanges();
        assertGatewayGauge($calendarPie->GetTestStatus() === 202, 'Invalid independent design must fail configuration: ' . $prefix . $name);
        $calendarPie->SetTestProperty($prefix . $name, $original);
    }
}
foreach (['DataMode' => 'invalid', 'Month' => '2026-13', 'Decimals' => 7] as $name => $invalid) {
    $original = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($calendarPie)[$name];
    $calendarPie->SetTestProperty($name, $invalid);
    $calendarPie->ApplyChanges();
    assertGatewayGauge($calendarPie->GetTestStatus() === 202, 'Invalid mode, month and decimal settings must fail validation.');
    $calendarPie->SetTestProperty($name, $original);
}
foreach ([[], [$sources[0], $sources[0]], [['VariableID' => 4712]], [['VariableID' => $stateID]],
    [['VariableID' => $ids[0], 'Color' => 0x1000000]]] as $invalidSources) {
    $calendarPie->SetTestProperty('Sources', json_encode($invalidSources, JSON_THROW_ON_ERROR));
    $calendarPie->ApplyChanges();
    assertGatewayGauge($calendarPie->GetTestStatus() === 201, 'Invalid numeric source sets must fail with source status.');
}
$calendarPie->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
$GLOBALS['symconTestVariables'][$ids[1]]['Presentation']['SUFFIX'] = ' W';
$calendarPie->ApplyChanges();
assertGatewayGauge($calendarPie->GetTestStatus() === 201, 'Unlike effective units must not be combined as pie portions.');
$GLOBALS['symconTestVariables'][$ids[1]]['Presentation']['SUFFIX'] = ' kWh';
$calendarPie->SetTestProperty('Sources', json_encode([$sources[0]], JSON_THROW_ON_ERROR));
$calendarPie->ApplyChanges();
$GLOBALS['symconTestVariables'][$ids[0]]['Name'] = 'Renamed archive';
assertGatewayGauge(
    $calendarPie->GetTestReferences() === [$ids[0]]
    && json_decode($calendarPie->GetCalendarPieData(), true)['calendarPie']['categories'][0]['label'] === 'Renamed archive',
    'Removing sources must release references; rename must follow the same variable identity.'
);
$calendarPie->SetTestProperty('DataMode', 'states');
foreach ([[['Value' => 2, 'Label' => 'Bad Boolean']], [['Value' => 0, 'Label' => 'A'], ['Value' => 0, 'Label' => 'B']]] as $invalidStates) {
    $calendarPie->SetTestProperty('States', json_encode($invalidStates, JSON_THROW_ON_ERROR));
    $calendarPie->ApplyChanges();
    assertGatewayGauge($calendarPie->GetTestStatus() === 201, 'Invalid Boolean and duplicate state mappings must fail source validation.');
}
$calendarPie->SetTestProperty('States', json_encode($definitions, JSON_THROW_ON_ERROR));
$calendarPie->ApplyChanges();
assertGatewayGauge(str_contains($calendarPie->GetCompatibleParents(), 'connect'), 'CalendarPie must select the existing shared Gateway.');
$flexible = new TestableEChartsCalendarPie();
$flexible->Create();
$flexible->SetTestProperty('EnableIPSView', true);
$flexibleSources = [];
foreach (range(1, 6) as $count) {
    $id = 88710 + $count;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792317600,
        'Value'                                            => 999999.0, 'Name' => 'Flexible ' . $count];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
        'AggregatedValues'                                           => [['TimeStamp' => strtotime('2026-10-15'), 'Avg' => (float) $count, 'Min' => 0.0, 'Max' => 10.0]]];
    $flexibleSources[] = ['VariableID' => $id, 'Label' => 'Flexible ' . $count, 'Color' => -1];
}
foreach (['average', 'counter'] as $mode) {
    $flexible->SetTestProperty('DataMode', $mode);
    foreach ($flexibleSources as $source) {
        $GLOBALS['symconTestArchiveVariables'][$source['VariableID']]['AggregationType'] = $mode === 'counter' ? 1 : 0;
    }
    foreach (range(1, 5) as $count) {
        $selected = array_slice($flexibleSources, 0, $count);
        $flexible->SetTestProperty('Sources', json_encode($selected, JSON_THROW_ON_ERROR));
        $flexible->ApplyChanges();
        $flexibleChart = json_decode($flexible->GetCalendarPieData(), true, 512, JSON_THROW_ON_ERROR)['calendarPie'];
        assertGatewayGauge(
            $flexible->GetTestStatus() === 102 && count($flexibleChart['categories']) === $count
            && count($flexibleChart['days'][14]['slices']) === $count
            && array_column($flexibleChart['days'][14]['slices'], 'value') === array_map(floatval(...), range(1, $count)),
            'Both numeric modes must publish exactly the selected one to five archived sources, not current values.'
        );
        foreach ([$flexible->GetVisualizationTile(), $flexible->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($html, '"label":"Flexible ' . $count . '"')
                && !str_contains($html, '"label":"Flexible ' . ($count + 1) . '"'),
                'Both output bootstraps must contain all and only selected sources.'
            );
        }
        $flexible->UpdateBarPreviewFromForm(json_encode(['DataMode' => $mode, 'Sources' => $selected], JSON_THROW_ON_ERROR));
        foreach (array_slice($flexible->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            foreach (range(1, 6) as $index) {
                assertGatewayGauge(
                    str_contains($svg, '>Flexible ' . $index . '</text>') === ($index <= $count),
                    'Both SVG previews must follow the configured source count rather than a fixed sample count.'
                );
            }
        }
    }
    $flexible->SetTestProperty('Sources', json_encode($flexibleSources, JSON_THROW_ON_ERROR));
    $flexible->ApplyChanges();
    assertGatewayGauge($flexible->GetTestStatus() === 201, 'A sixth numeric source must visibly fail validation without changing the source configuration.');
    $flexible->UpdateBarPreviewFromForm(json_encode(['DataMode' => $mode, 'Sources' => $flexibleSources], JSON_THROW_ON_ERROR));
    foreach (array_slice($flexible->GetTestFormUpdates(), -2) as $update) {
        $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(
            str_contains($svg, 'Select one to five numeric archive variables.') && !str_contains($svg, 'data-calendar-day'),
            'An over-limit draft must warn in both previews instead of silently truncating its sources.'
        );
    }
}

$normalization = new TestableEChartsCalendarPie();
$normalization->Create();
$normalizedSources = [];
foreach ([88731 => ['°C', 1, 20.0, 40], 88732 => ['%', 0, 60.0, 100]] as $id => [$unit, $digits, $value, $maximum]) {
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792317600, 'Value' => 999999.0,
        'Name'                                             => 'Equal <source>', 'Presentation' => ['SUFFIX' => ' ' . $unit, 'DIGITS' => $digits, 'MIN' => 999, 'MAX' => 1000]];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
        'AggregatedValues'                                           => [['TimeStamp' => strtotime('2026-10-15'), 'Avg' => $value, 'Min' => 0.0, 'Max' => 100.0]]];
    $normalizedSources[] = ['VariableID' => $id, 'Label' => '', 'Color' => -1, 'Minimum' => 0, 'Maximum' => $maximum];
}
$normalization->SetTestProperty('Sources', json_encode($normalizedSources, JSON_THROW_ON_ERROR));
$normalization->ApplyChanges();
assertGatewayGauge($normalization->GetTestStatus() === 201, 'Mixed units must still be rejected without explicit normalization.');
$normalization->SetTestProperty('NormalizeValues', true);
$normalization->SetTestProperty('UseVariablePresentation', false);
$normalization->SetTestProperty('Unit', str_repeat('inactive global unit', 3));
$normalization->SetTestProperty('Decimals', 7);
$normalization->SetTestProperty('EnableIPSView', true);
$normalization->ApplyChanges();
$normalizedChart = json_decode($normalization->GetCalendarPieData(), true, 512, JSON_THROW_ON_ERROR)['calendarPie'];
assertGatewayGauge(
    $normalization->GetTestStatus() === 102 && $normalizedChart['normalized'] === true
    && array_column($normalizedChart['days'][14]['slices'], 'value') === [50.0, 60.0]
    && array_column($normalizedChart['days'][14]['slices'], 'originalValue') === [20.0, 60.0]
    && array_column($normalizedChart['days'][14]['slices'], 'unit') === ['°C', '%']
    && array_column($normalizedChart['days'][14]['slices'], 'decimals') === [1, 0]
    && count(array_unique(array_column($normalizedChart['categories'], 'id'))) === 2,
    'Normalized mixed-unit archive sources retain original metadata, fixed explicit ranges and separate identities even with equal labels.'
);
foreach ([$normalization->GetVisualizationTile(), $normalization->GetIPSViewHTML()] as $html) {
    assertGatewayGauge(
        strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($html, '"normalized":true')
        && str_contains($html, '"originalValue":20.0') && str_contains($html, '"unit":"%"'),
        'Both output bootstraps must publish original and normalized values within the output limit.'
    );
}
$normalizationForm = json_decode($normalization->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    findGaugeFormElement($normalizationForm['elements'], 'NormalizeValues')['visible'] === true
    && str_contains(findGaugeFormElement($normalizationForm['elements'], 'NormalizeValues')['onChange'], 'ECCP_UpdateNormalizationForm')
    && str_contains(findGaugeFormElement($normalizationForm['elements'], 'NormalizeValues')['onChange'], 'ECCP_UpdateBarPreviewFromForm')
    && findGaugeFormElement($normalizationForm['elements'], 'Unit')['enabled'] === false,
    'Normalization must be an explicit live-preview control and make its inactive global formatting unambiguous.'
);
$normalization->UpdateNormalizationForm(false);
assertGatewayGauge(
    count(array_filter(array_slice($normalization->GetTestFormUpdates(), -3), static fn (array $update): bool => $update['Value'] === true)) === 3,
    'Disabling normalization must immediately re-enable all global presentation controls.'
);
$savedProperties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($normalization);
$queriesBefore = $GLOBALS['symconTestArchiveQueryCount'];
$normalization->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $normalizedSources], JSON_THROW_ON_ERROR));
foreach (array_slice($normalization->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        str_contains($svg, 'Normalized comparison (0–100)') && str_contains($svg, 'data-weight="25" data-original-value="10"'),
        'Both SVG previews must use explicit normalization limits and mark their synthetic normalized comparison.'
    );
}
$badRange = $normalizedSources;
$zeroDraft = array_map(static fn (array $source): array => [...$source, 'Minimum' => 1000, 'Maximum' => 2000], $normalizedSources);
$normalization->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $zeroDraft], JSON_THROW_ON_ERROR));
foreach (array_slice($normalization->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        !str_contains($svg, 'data-calendar-pie') && str_contains($svg, 'data-status="zero"'),
        'Both previews must mark all-clipped weights as zero instead of drawing equal or invisible complete pies.'
    );
}
$badRange[0]['Maximum'] = 0;
$normalization->UpdateBarPreviewFromForm(json_encode(['NormalizeValues' => true, 'Sources' => $badRange], JSON_THROW_ON_ERROR));
foreach (array_slice($normalization->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        str_contains($svg, 'minimum below maximum') && !str_contains($svg, 'data-calendar-day'),
        'An invalid normalization draft must warn instead of drawing misleading circles.'
    );
}
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($normalization) === $savedProperties
    && $queriesBefore === $GLOBALS['symconTestArchiveQueryCount'], 'Normalization previews must not save properties or read live/archive values.');
$normalization->SetTestProperty('DataMode', 'counter');
foreach ([88731, 88732] as $id) {
    $GLOBALS['symconTestArchiveVariables'][$id]['AggregationType'] = 1;
}
$normalization->ApplyChanges();
assertGatewayGauge(
    $normalization->GetTestStatus() === 102
    && array_column(json_decode($normalization->GetCalendarPieData(), true)['calendarPie']['days'][14]['slices'], 'value') === [50.0, 60.0],
    'Counter daily consumption supports the same optional normalization without changing archive semantics.'
);
$normalization->SetTestProperty('Sources', json_encode($badRange, JSON_THROW_ON_ERROR));
$normalization->ApplyChanges();
assertGatewayGauge($normalization->GetTestStatus() === 201, 'Invalid enabled normalization limits must fail source validation.');
$normalization->SetTestProperty('DataMode', 'states');
$normalization->SetTestProperty('StateVariableID', $stateID);
$normalization->SetTestProperty('States', json_encode($definitions, JSON_THROW_ON_ERROR));
$normalization->ApplyChanges();
assertGatewayGauge(
    $normalization->GetTestStatus() === 102
    && json_decode($normalization->GetCalendarPieData(), true)['calendarPie']['normalized'] === false,
    'State durations ignore inactive normalization and numeric source limits without changing their model.'
);

date_default_timezone_set($savedTimezone);
echo "CalendarPie modes, Gateway, forms, previews and outputs verified.\n";
