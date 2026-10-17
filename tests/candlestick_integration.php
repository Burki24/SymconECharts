<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsCandlestick/module.php';

final class TestableEChartsCandlestick extends EChartsCandlestick
{
    public int $now = 1792233240;

    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsCandlestick/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsCandlestick/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return $this->now;
    }
}

$candleZone = date_default_timezone_get();
date_default_timezone_set('Europe/Berlin');
$candle = new TestableEChartsCandlestick();
$candle->now = strtotime('2026-10-17 12:34:00');
$GLOBALS['symconTestVariables'][88500] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
    'Value'                                              => 999.0, 'Name' => 'Outdoor <temperature>', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
$GLOBALS['symconTestArchiveVariables'][88500] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
foreach ([[11, 3], [10, 9], [9, -4], [8, 2]] as [$hour, $value]) {
    $GLOBALS['symconTestArchiveVariables'][88500]['LoggedValues'][] = [
        'TimeStamp' => strtotime('2026-10-16 ' . $hour . ':00:00'), 'Value' => $value, 'Duration' => 3600];
}
$candle->Create();
$candle->SetTestProperty('VariableID', 88500);
$candle->SetTestProperty('Title', 'Temperature intervals');
$candle->SetTestProperty('EnableIPSView', true);
$candle->SetTestProperty('IPSViewUseTileDesign', false);
$candle->SetTestProperty('IPSViewRiseColor', 0xABCDEF);
$candle->ApplyChanges();
$candleData = json_decode($candle->GetCandlestickData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $candle->GetTestStatus() === IS_ACTIVE && $candle->GetTestReferences() === [88500]
    && count($candleData['candlestick']['data']) === 7 && $candleData['candlestick']['data'][6]['statistics'] === [2.0, 3.0, -4.0, 9.0]
    && $candleData['candlestick']['data'][5]['statistics'] === null && $candleData['candlestick']['unit'] === '°C',
    'Candlestick must summarize actual logged samples, never means or the current value.'
);
$candleCount = $GLOBALS['symconTestArchiveQueryCount'];
$candleTile = $candle->GetVisualizationTile();
$candleIPSView = $candle->GetIPSViewHTML();
assertGatewayGauge(
    $GLOBALS['symconTestArchiveQueryCount'] === $candleCount
    && str_contains($candleIPSView, '"riseColor":"#ABCDEF"') && !str_contains($candleTile, '"riseColor":"#ABCDEF"')
    && $candle->GetTestVariableValue('IPSViewCandlestick') !== ''
    && strlen($candleTile) < SYMCON_OUTPUT_BUFFER_LIMIT && strlen($candleIPSView) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Both outputs must reuse bounded data, preserve independent designs and publish bounded IPSView content.'
);
$candleForm = json_decode($candle->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains(findGaugeFormElement($candleForm['elements'], 'VariableID')['onChange'], 'ECCA_UpdateBarPreviewFromForm')
    && findGaugeFormElement($candleForm['elements'], 'IPSViewRiseColor')['enabled'] === true,
    'Source and independent design changes must update initialized previews.'
);
$beforeCandlePreview = $GLOBALS['symconTestArchiveQueryCount'];
$candle->UpdateBarPreviewFromForm('{"Title":"Unsaved <title>","RiseColor":1193046,"Orientation":"horizontal"}');
$preview = base64_decode(substr(array_slice($candle->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$xml = new DOMDocument();
assertGatewayGauge(
    $xml->loadXML($preview, LIBXML_NONET) && str_contains($preview, 'Unsaved &lt;title&gt;')
    && str_contains($preview, '#123456') && $GLOBALS['symconTestArchiveQueryCount'] === $beforeCandlePreview
    && json_decode($candle->GetCandlestickData(), true)['candlestick']['title'] === 'Temperature intervals',
    'Synthetic escaped previews must not query archives or persist drafts.'
);
$candle->SetTestProperty('Range', '30d');
$candle->SetTestProperty('Interval', 'hour');
$candle->ApplyChanges();
assertGatewayGauge(
    count(json_decode($candle->GetCandlestickData(), true)['candlestick']['data']) === 720
    && strlen($candle->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($candle->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Maximum hourly window must remain bounded below the Symcon output limit.'
);
$candleParent = IPSModuleStrict::$ParentResponder;
foreach (['VariableID' => 123, 'EffectiveReducer' => 'average', 'ArchiveAggregationType' => 'counter',
    'Points'           => [[1, 2]], 'Truncated' => null] as $field => $bad) {
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($candleParent, $field, $bad): string
    {
        $response = json_decode($candleParent($json), true, 512, JSON_THROW_ON_ERROR);
        $response['Payload'][$field] = $bad;
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    try {
        $candle->now += 61;
        $candle->RefreshArchive();
        assertGatewayGauge($candle->GetTestStatus() === 204, 'Unsafe Candlestick archive response must fail: ' . $field);
    } finally {
        IPSModuleStrict::$ParentResponder = $candleParent;
    }
}
$candle->now += 61;
$candle->RefreshArchive();
assertGatewayGauge($candle->GetTestStatus() === IS_ACTIVE, 'Candlestick recovers on a corrected response.');
$candle->now += 61;
assertTransientRetry($candle, 'Candlestick');
$candle->SetTestProperty('VariableID', 4713);
$candle->ApplyChanges();
assertGatewayGauge(
    $candle->GetTestStatus() === 204 && str_contains($candle->GetVisualizationTile(), 'Candlestick requires standard archive variables, not counters.'),
    'Counter archives must be rejected visibly.'
);
$candle->SetTestProperty('VariableID', 88500);
foreach ([['Interval', 'week', 'hour'], ['Range', '1h', '30d'], ['RiseColor', -2, 0x3FBE95],
    ['CandleWidth', 0, 40], ['MinCandleWidth', 81, 1], ['Orientation', 'invalid', 'vertical']] as [$field, $bad, $restore]) {
    $candle->SetTestProperty($field, $bad);
    $candle->ApplyChanges();
    assertGatewayGauge($candle->GetTestStatus() === 202, 'Reject invalid Candlestick setting: ' . $field);
    $candle->SetTestProperty($field, $restore);
}
$candle->ApplyChanges();
$GLOBALS['symconTestCopyTarget'] = $candle;
try {
    $candle->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($candle->GetIPSViewHTML(), '"riseColor":"#3FBE95"'), 'Copy design includes candle colors.');
$candleLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsCandlestick/locale.json'), true)['translations']['de'];
$candleValuesBeforeDesign = array_column(json_decode($candle->GetCandlestickData(), true)['candlestick']['data'], 'statistics');
$candleDesign = ['FixedCandleWidth' => 18, 'RiseHollow' => true, 'FallHollow' => true,
    'RiseBorderColor'               => 0x123456, 'FallBorderColor' => 0x654321, 'HoverBorderWidth' => 5,
    'HoverBorderColor'              => 0xFEDCBA, 'HoverOpacityPercent' => 95, 'AxisLineColor' => 0xABCDEF,
    'AxisLineType'                  => 'dashed', 'AxisLabelMargin' => 16, 'ReverseTimeAxis' => true, 'IncludeZero' => true,
    'FontFamily'                    => 'Georgia, serif', 'TitleItalic' => true, 'AxisLabelItalic' => true];
foreach (['', 'IPSView'] as $prefix) {
    foreach ($candleDesign as $name => $value) {
        $candle->SetTestProperty($prefix . $name, $value);
    }
}
$candle->SetTestProperty('IPSViewRiseBorderColor', 0xABCDEF);
$candle->ApplyChanges();
$candleStyle = json_decode($candle->GetCandlestickData(), true)['candlestick']['style'];
$candleExtendedForm = json_decode($candle->GetConfigurationForm(), true);
foreach ($candleDesign as $name => $value) {
    $expected = str_ends_with($name, 'Color') ? sprintf('#%06X', $value) : $value;
    assertGatewayGauge($candleStyle[lcfirst($name)] === $expected, 'Candlestick designer setting must reach model: ' . $name);
    foreach (['', 'IPSView'] as $prefix) {
        assertGatewayGauge(
            str_contains(findGaugeFormElement($candleExtendedForm['elements'], $prefix . $name)['onChange'], 'ECCA_UpdateBarPreviewFromForm'),
            'New fields must have live preview callbacks: ' . $prefix . $name
        );
    }
}
assertGatewayGauge(
    str_contains($candle->GetIPSViewHTML(), '"riseBorderColor":"#ABCDEF"')
    && array_column(json_decode($candle->GetCandlestickData(), true)['candlestick']['data'], 'statistics') === $candleValuesBeforeDesign
    && $candle->GetTestReferences() === [88500] && GetValue(88500) === 999.0,
    'Independent designs must not modify source references, archive results or current values.'
);
$candleSavedDesign = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($candle);
$candlePreviewQueries = $GLOBALS['symconTestArchiveQueryCount'];
$candle->UpdateBarPreviewFromForm(json_encode(['RiseBorderColor' => 0xDECADE, 'RiseHollow' => true,
    'FixedCandleWidth'                                           => 12, 'FontFamily' => 'monospace', 'TitleItalic' => true, 'AxisLabelItalic' => true], JSON_THROW_ON_ERROR));
$candleDesignPreview = base64_decode(substr(array_slice($candle->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$candlePreviewDocument = new DOMDocument();
assertGatewayGauge(
    $candlePreviewDocument->loadXML($candleDesignPreview, LIBXML_NONET)
    && str_contains($candleDesignPreview, 'font-family="monospace"') && str_contains($candleDesignPreview, 'font-style="italic"')
    && str_contains($candleDesignPreview, 'stroke="#DECADE"') && str_contains($candleDesignPreview, 'fill="none"')
    && $GLOBALS['symconTestArchiveQueryCount'] === $candlePreviewQueries
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($candle) === $candleSavedDesign,
    'Draft design must reach escaped synthetic previews without archive requests or persistence.'
);
foreach (['', 'IPSView'] as $prefix) {
    foreach ([['FixedCandleWidth', -1, 18], ['FixedCandleWidth', 41, 18],
        ['RiseBorderColor', -2, 0x123456], ['FallBorderColor', 0x1000000, 0x654321],
        ['HoverBorderWidth', 9, 5], ['HoverBorderColor', -2, 0xFEDCBA],
        ['HoverOpacityPercent', -2, 95], ['HoverOpacityPercent', 101, 95],
        ['AxisLabelMargin', 31, 16], ['AxisLineColor', -2, 0xABCDEF],
        ['AxisLineType', 'invalid', 'dashed'], ['FontFamily', 'url(https://example.invalid)', 'Georgia, serif']] as [$name, $invalid, $restore]) {
        $candle->SetTestProperty($prefix . $name, $invalid);
        assertGatewayGauge(json_decode($candle->GetCandlestickDiagnostic(), true)['valid'] === false, 'Reject invalid new Candlestick field: ' . $prefix . $name);
        $candle->SetTestProperty($prefix . $name, $restore);
        assertGatewayGauge(json_decode($candle->GetCandlestickDiagnostic(), true)['valid'] === true, 'Recover corrected Candlestick field: ' . $prefix . $name);
    }
}
$GLOBALS['symconTestCopyTarget'] = $candle;
try {
    $candle->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($candle->GetIPSViewHTML(), '"riseBorderColor":"#123456"')
    && str_contains($candle->GetIPSViewHTML(), '"fontFamily":"Georgia, serif"'), 'Copy design includes all new fields.');
$candleCopiedHTML = $candle->GetIPSViewHTML();
foreach ($candleDesign as $name => $value) {
    $expected = str_ends_with($name, 'Color') ? sprintf('#%06X', $value) : $value;
    assertGatewayGauge(
        str_contains($candleCopiedHTML, trim(json_encode([lcfirst($name) => $expected], JSON_THROW_ON_ERROR), '{}')),
        'Independent copied IPSView must preserve: ' . $name
    );
}
$candleCheckCaptions = static function (array $items) use (&$candleCheckCaptions, $candleLocale): void
{
    foreach ($items as $key => $value) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($value)) {
            assertGatewayGauge(isset($candleLocale[$value]), 'Missing Candlestick translation: ' . $value);
        } elseif (is_array($value)) {
            $candleCheckCaptions($value);
        }
    }
};
$candleCheckCaptions(json_decode(file_get_contents(__DIR__ . '/../EChartsCandlestick/form.json'), true));
preg_match_all("/(?:Translate|translate)\\(\\s*'([^']+)'/", file_get_contents(__DIR__ . '/../EChartsCandlestick/module.php')
    . file_get_contents(__DIR__ . '/../EChartsCandlestick/visualization/app.js'), $candleRuntimeTexts);
foreach ($candleRuntimeTexts[1] as $text) {
    assertGatewayGauge(isset($candleLocale[$text]), 'Missing Candlestick runtime translation: ' . $text);
}
$candle->SetTestParentActive(false);
$candle->ApplyChanges();
assertGatewayGauge($candle->GetTestStatus() === 203 && $candle->GetTestTimerInterval('ArchiveRefresh') === 0, 'No gateway disables archive refresh.');
$candle->SetTestProperty('VariableID', 4712);
$candle->ApplyChanges();
assertGatewayGauge($candle->GetTestStatus() === 201 && $candle->GetTestReferences() === [4712], 'Invalid type rejected and old reference removed.');
date_default_timezone_set($candleZone);
echo "Candlestick integration verified.\n";
