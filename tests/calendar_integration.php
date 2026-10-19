<?php

declare(strict_types=1);

use SymconECharts\EChartsDataProtocol;

// Included by gateway_gauges.php: exercise the real module/Gateway with its existing kernel doubles.
require_once dirname(__DIR__) . '/EChartsCalendar/module.php';

final class TestableEChartsCalendar extends EChartsCalendar
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCalendar/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsCalendar/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return strtotime('2026-10-16 12:00:00');
    }
}

$calendarSavedZone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$calendarID = 88001;
$GLOBALS['symconTestVariables'][$calendarID] = [
    'VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 17.0, 'Name' => 'Outside <temperature>'
];
$GLOBALS['symconTestArchiveVariables'][$calendarID] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => [
    ['TimeStamp' => strtotime('2026-10-16'), 'Avg' => 999.0, 'Min' => 1.0, 'Max' => 999.0],
    ['TimeStamp' => strtotime('2026-10-15'), 'Avg' => 0.0, 'Min' => -1.0, 'Max' => 1.0],
    ['TimeStamp' => strtotime('2026-10-13'), 'Avg' => -7.5, 'Min' => -9.0, 'Max' => -4.0],
    ['TimeStamp' => strtotime('2026-10-10 09:25:10'), 'Avg' => 8.25, 'Min' => 7.0, 'Max' => 11.0],
    ['TimeStamp' => strtotime('2025-12-31'), 'Avg' => 5.0, 'Min' => 2.0, 'Max' => 7.0]
]];
$calendar = new TestableEChartsCalendar();
$calendar->Create();
$calendar->SetTestProperty('SourceVariableID', $calendarID);
$calendar->SetTestProperty('Title', 'Daily temperature');
$calendar->SetTestProperty('EnableIPSView', true);
$calendar->SetTestProperty('IPSViewUseTileDesign', false);
$calendar->SetTestProperty('IPSViewLowColor', 0x123456);
$calendar->SetTestProperty('IPSViewShowYearLabel', true);
$calendar->SetTestProperty('IPSViewYearColor', 0x654321);
$calendar->SetTestProperty('IPSViewMonthLineType', 'dashed');
$calendar->SetTestProperty('IPSViewMonthLineColor', 0xABCDEF);
$calendar->SetTestProperty('IPSViewUseFixedColorRange', true);
$calendar->SetTestProperty('IPSViewColorScaleMinimum', -20.5);
$calendar->SetTestProperty('IPSViewColorScaleMaximum', 40.5);
$calendar->ApplyChanges();
$calendarData = json_decode($calendar->GetCalendarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $calendar->GetTestStatus() === IS_ACTIVE && $calendar->GetTestReferences() === [$calendarID]
    && $calendarData['family'] === 'calendar' && $calendarData['calendar']['year'] === 2026
    && $calendarData['calendar']['data'] === [['date' => '2026-10-10', 'value' => 8.25], ['date' => '2026-10-13', 'value' => -7.5], ['date' => '2026-10-15', 'value' => 0.0]],
    'Calendar must show a partial-year archive beginning during a day, preserve negative/zero means and leave gaps empty.'
);
$calendarQueries = $GLOBALS['symconTestArchiveQueryCount'];
$calendarParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json): string
{
    throw new RuntimeException('No parent request is allowed while editing Calendar previews.');
};
$calendarForm = json_decode($calendar->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$calendar->UpdateBarPreviewFromForm(json_encode(['LowColor' => 0xABCDEF, 'Title' => 'Unsaved calendar'], JSON_THROW_ON_ERROR));
IPSModuleStrict::$ParentResponder = $calendarParent;
$calendarDraftSVG = base64_decode(substr(array_slice($calendar->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($calendarDraftSVG) && str_contains($calendarDraftSVG, '#ABCDEF')
    && str_contains($calendarDraftSVG, 'Unsaved calendar')
    && json_decode($calendar->GetCalendarData(), true)['calendar']['title'] === 'Daily temperature',
    'Calendar draft previews must reflect changes without persisting them.'
);
assertGatewayGauge(
    $calendarQueries === $GLOBALS['symconTestArchiveQueryCount']
    && findGaugeFormElement($calendarForm['elements'], 'SourceVariableID')['type'] === 'SelectVariable'
    && findGaugeFormElement($calendarForm['elements'], 'IPSViewCellBorderColor')['type'] === 'SelectColor',
    'Calendar form previews must not query archives and must expose independent designers.'
);
$calendarIndependentSVG = base64_decode(substr(findGaugeFormElement($calendarForm['elements'], 'IPSViewBarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($calendarIndependentSVG) && str_contains($calendarIndependentSVG, '#654321')
    && str_contains($calendarIndependentSVG, '#ABCDEF') && str_contains($calendarIndependentSVG, 'stroke-dasharray="6 4"')
    && str_contains($calendarIndependentSVG, '-20,5') && str_contains($calendarIndependentSVG, '40,5')
    && findGaugeFormElement($calendarForm['elements'], 'IPSViewMonthLabelPosition')['type'] === 'Select',
    'Independent Calendar previews must reflect year labels, separators and fixed color ranges.'
);
$calendarPreview = base64_decode(substr(findGaugeFormElement($calendarForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    is_string($calendarPreview) && str_contains($calendarPreview, 'Example data')
    && str_contains($calendarPreview, '<rect') && !str_contains($calendarPreview, '999'),
    'Calendar preview must identify synthetic example data.'
);
if (class_exists(DOMDocument::class)) {
    $calendarXML = new DOMDocument();
    assertGatewayGauge($calendarXML->loadXML($calendarPreview, LIBXML_NONET), 'Calendar preview must be valid SVG XML.');
}
$calendarTile = $calendar->GetVisualizationTile();
$calendarIPSView = $calendar->GetIPSViewHTML();
assertGatewayGauge(
    str_contains($calendarTile, '"lowColor":"#5070DD"')
    && str_contains($calendarIPSView, '"lowColor":"#123456"')
    && strlen($calendarTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($calendarIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($calendar->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Calendar documents/form must fit Symcon output limits and keep separate output designs.'
);
assertGatewayGauge(is_string($calendar->GetTestVariableValue('IPSViewCalendar'))
    && $calendar->GetTestVariableValue('IPSViewCalendar') !== '', 'Calendar must publish nonempty IPSView WebContent.');
$calendar->SetTestProperty('AnimationEnabled', false);
$calendar->SetTestProperty('UseFixedColorRange', true);
$calendar->SetTestProperty('ColorScaleMinimum', -10.25);
$calendar->SetTestProperty('ColorScaleMaximum', 35.75);
$calendar->SetTestProperty('MonthLabelPosition', 'end');
$GLOBALS['symconTestCopyTarget'] = $calendar;
try {
    $calendar->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(
    str_contains($calendar->GetIPSViewHTML(), '"lowColor":"#5070DD"')
    && str_contains($calendar->GetIPSViewHTML(), '"animationEnabled":false')
    && str_contains($calendar->GetIPSViewHTML(), '"colorScaleMinimum":-10.25')
    && str_contains($calendar->GetIPSViewHTML(), '"colorScaleMaximum":35.75')
    && str_contains($calendar->GetIPSViewHTML(), '"monthLabelPosition":"end"'),
    'Calendar must copy tile color and animation settings into its independent IPSView designer.'
);

$calendarBootstrap = [];
preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $calendarTile, $calendarBootstrap);
$calendarBootstrap = json_decode($calendarBootstrap[1], true, 512, JSON_THROW_ON_ERROR);
$calendarPath = $calendarBootstrap['options']['calendarStatePath'];
preg_match('~/state/(\d+)/([a-f0-9]+)~', $calendarPath, $calendarChannel);
$calendarRequest = ['DataID' => '{E4749B72-912B-E3E3-1C57-D19019FFDD84}'] + EChartsDataProtocol::CreateRequest(EChartsDataProtocol::OPERATION_IPSVIEW_STATE, [
    'InstanceID' => (int) $calendarChannel[1], 'Channel' => $calendarChannel[2], 'WindowOffset' => 1, 'Output' => 'tile'
]);
$calendar->SetTestProperty('EnableIPSView', false);
$calendarResponse = EChartsDataProtocol::DecodeResponse($calendar->ReceiveData(json_encode($calendarRequest, JSON_THROW_ON_ERROR)), EChartsDataProtocol::OPERATION_IPSVIEW_STATE);
assertGatewayGauge(
    $calendarResponse['Success'] && $calendarResponse['Payload']['State']['chart']['calendar']['year'] === 2025
    && $calendarResponse['Payload']['State']['chart']['calendar']['data'] === [['date' => '2025-12-31', 'value' => 5.0]]
    && json_decode($calendar->GetCalendarData(), true)['calendar']['year'] === 2026,
    'Native year navigation must be read-only and work without enabling IPSView.'
);

assertTransientRetry($calendar, 'Calendar');
foreach (['', 'IPSView'] as $calendarPrefix) {
    foreach (['MonthLabelMargin' => [41, 8], 'CellOpacityPercent' => [101, 100],
        'YearLabelPosition'      => ['unknown', 'auto'], 'MonthLineType' => ['unknown', 'solid'],
        'MonthLabelFormat'       => ['function(){}', 'name'], 'ColorScaleMaximum' => [-100.0, 35.75]] as $setting => [$invalid, $valid]) {
        $calendar->SetTestProperty($calendarPrefix . $setting, $invalid);
        $calendar->ApplyChanges();
        assertGatewayGauge($calendar->GetTestStatus() === 202, 'Calendar must reject invalid design: ' . $calendarPrefix . $setting);
        $calendar->SetTestProperty($calendarPrefix . $setting, $valid);
        $calendar->ApplyChanges();
        assertGatewayGauge($calendar->GetTestStatus() === IS_ACTIVE, 'Calendar must recover after correcting design: ' . $calendarPrefix . $setting);
    }
}
$calendarMalformedParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static fn (string $json): string => EChartsDataProtocol::EncodeSuccessResponse(
    EChartsDataProtocol::OPERATION_ARCHIVE_READ,
    ['VariableID' => $calendarID, 'Points' => [], 'Truncated' => true]
);
try {
    $calendar->GetCalendarData();
    throw new LogicException('Truncated Calendar data was accepted.');
} catch (RuntimeException) {
    assertGatewayGauge($calendar->GetTestStatus() === 204, 'Truncated Calendar data must be rejected.');
} finally {
    IPSModuleStrict::$ParentResponder = $calendarMalformedParent;
}
$calendar->SetTestProperty('Year', 2027);
$calendar->ApplyChanges();
assertGatewayGauge($calendar->GetTestStatus() === 202, 'Calendar must reject future years.');
$calendar->SetTestProperty('Year', 0);
$calendar->SetTestProperty('SourceVariableID', 4713);
$calendar->ApplyChanges();
assertGatewayGauge(
    $calendar->GetTestStatus() === 204 && str_contains($calendar->GetVisualizationTile(), 'not a counter'),
    'Calendar must reject counter variables rather than render consumption as daily means.'
);
require __DIR__ . '/calendar_metrics.php';
$calendar->SetTestProperty('SourceVariableID', 0);
$calendar->ApplyChanges();
assertGatewayGauge(
    $calendar->GetTestStatus() === 201 && $calendar->GetTestReferences() === [],
    'Removing the Calendar source must unregister references and invalidate the source.'
);
$calendarLocale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCalendar/locale.json'), true)['translations']['de'];
$calendarStaticForm = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCalendar/form.json'), true);
$calendarCaptions = function (array $items) use (&$calendarCaptions, $calendarLocale): void
{
    foreach ($items as $key => $item) {
        if ($key === 'caption' && is_string($item)) {
            assertGatewayGauge(isset($calendarLocale[$item]), 'Calendar form caption needs German translation: ' . $item);
        } elseif (is_array($item)) {
            $calendarCaptions($item);
        }
    }
};
$calendarCaptions($calendarStaticForm);
unset($GLOBALS['symconTestVariables'][$calendarID], $GLOBALS['symconTestArchiveVariables'][$calendarID]);
date_default_timezone_set($calendarSavedZone);
echo "Calendar Gateway, output and form integration verified.\n";
