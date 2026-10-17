<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsPictorialBar/module.php';

final class TestableEChartsPictorialBar extends EChartsPictorialBar
{
    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsPictorialBar/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsPictorialBar/visualization/' . $filename);
    }
}

$pict = new TestableEChartsPictorialBar();
$pict->Create();
$pictSources = [];
for ($index = 0; $index < 16; ++$index) {
    $id = 88600 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                            => (float) ($index * 7), 'Name' => 'Sensor <same>', 'Presentation' => ['SUFFIX' => ' %', 'DIGITS' => 0]];
    $pictSources[] = ['VariableID' => $id, 'Label' => 'Battery <same>', 'Color' => $index === 1 ? 0x123456 : -1];
}
$pict->SetTestProperty('Sources', json_encode($pictSources, JSON_THROW_ON_ERROR));
$pict->SetTestProperty('Title', 'Current batteries');
$pict->SetTestProperty('EnableIPSView', true);
$pict->SetTestProperty('IPSViewUseTileDesign', false);
$pict->SetTestProperty('IPSViewSymbol', 'circle');
$pict->ApplyChanges();
$pictData = json_decode($pict->GetPictorialBarData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $pict->GetTestStatus() === IS_ACTIVE && $pict->GetTestReferences() === range(88600, 88615)
    && count($pictData['pictorialBar']['data']) === 16 && $pictData['pictorialBar']['unit'] === '%'
    && $pictData['pictorialBar']['data'][0]['value'] === 0.0 && $pictData['pictorialBar']['data'][1]['value'] === 7.0
    && $pictData['pictorialBar']['data'][0]['label'] === $pictData['pictorialBar']['data'][1]['label']
    && $pictData['pictorialBar']['data'][0]['id'] !== $pictData['pictorialBar']['data'][1]['id']
    && in_array(['SenderID' => 88600, 'Message' => VM_UPDATE], $pict->GetTestMessages(), true),
    'PictorialBar must use current numeric values with ID identity and update subscriptions.'
);
assertGatewayGauge(
    strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT && $pict->GetTestVariableValue('IPSViewPictorialBar') !== ''
    && str_contains($pict->GetIPSViewHTML(), '"symbol":"circle"')
    && str_contains($pict->GetVisualizationTile(), '"symbol":"rect"'),
    'Maximum-size PictorialBar must publish bounded independent tile and IPSView outputs.'
);
$pictForm = json_decode($pict->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    str_contains(findGaugeFormElement($pictForm['elements'], 'Sources')['onEdit'], 'ECPB_UpdateBarPreviewFromForm')
    && findGaugeFormElement($pictForm['elements'], 'IPSViewSymbol')['enabled'] === true,
    'Both PictorialBar designers must have initialized live previews and supported list edit callbacks.'
);
$pictBefore = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
$pictQueries = $GLOBALS['symconTestArchiveQueryCount'];
$pictExtended = ['ColorMode' => 'value', 'ColorMinimum' => 0.0, 'ColorMaximum' => 100.0,
    'LowColor'               => 0xFF0000, 'MiddleColor' => 0xFFFF00, 'HighColor' => 0x00FF00,
    'FontFamily'             => 'Georgia, serif', 'ValuePosition' => 'insideEnd', 'ValueDistance' => 8,
    'ValueBold'              => true, 'HideOverlap' => false, 'CategoryLabelRotate' => 25,
    'ShowAxisValues'         => false, 'ShowAxisTicks' => true, 'GridWidth' => 2, 'GridType' => 'dotted',
    'GridOpacityPercent'     => 60, 'BorderType' => 'dashed', 'ShadowBlur' => 8, 'ShadowColor' => 0x123456,
    'ShadowOffsetX'          => 2, 'ShadowOffsetY' => 3, 'EmphasisDisabled' => true, 'Focus' => 'none', 'BlurOpacityPercent' => 30];
foreach ($pictExtended as $name => $value) {
    assertGatewayGauge(str_contains(findGaugeFormElement($pictForm['elements'], $name)['onChange'], 'ECPB_UpdateBarPreviewFromForm')
        && findGaugeFormElement($pictForm['elements'], 'IPSView' . $name)['enabled'], 'New controls must update both live previews: ' . $name);
}
assertGatewayGauge(findGaugeFormElement($pictForm['elements'], 'ColorMinimum')['digits'] === 6
    && findGaugeFormElement($pictForm['elements'], 'IPSViewColorMaximum')['digits'] === 6, 'Both color-range controls must accept fractional values.');
$pictDraft = [...$pictExtended, 'Sources' => array_slice($pictSources, 0, 6), 'BorderWidth' => 1];
$pictOriginalValues = [];
foreach ([0, 25, 50, 75, 100, 150] as $i => $value) {
    $pictOriginalValues[$i] = $GLOBALS['symconTestVariables'][88600 + $i]['Value'];
    $GLOBALS['symconTestVariables'][88600 + $i]['Value'] = (float) $value;
}
foreach (['horizontal', 'vertical'] as $orientation) {
    $pict->UpdateBarPreviewFromForm(json_encode([...$pictDraft, 'Orientation' => $orientation], JSON_THROW_ON_ERROR));
    $extendedSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
    $doc = new DOMDocument();
    assertGatewayGauge($doc->loadXML($extendedSvg, LIBXML_NONET), 'Extended preview must be valid SVG.');
    foreach (['#ff8000', '#ffff00', '#80ff00', '#00ff00', 'Georgia, serif', 'stroke-dasharray', 'feDropShadow', 'rotate(-25'] as $token) {
        assertGatewayGauge(str_contains($extendedSvg, $token), 'Extended preview must show ' . $token);
    }
    assertGatewayGauge(str_contains($extendedSvg, '150 %'), 'Preview must retain original values above the drawing maximum.');
}
foreach ($pictOriginalValues as $i => $value) {
    $GLOBALS['symconTestVariables'][88600 + $i]['Value'] = $value;
}
assertGatewayGauge(
    (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictBefore,
    'New draft design settings must not persist.'
);
foreach ($pictExtended as $name => $value) {
    $pict->SetTestProperty($name, $value);
}
$extendedModel = json_decode($pict->GetPictorialBarData(), true)['pictorialBar'];
assertGatewayGauge(
    $extendedModel['style']['colorMode'] === 'value' && $extendedModel['style']['colorMaximum'] === 100.0
    && $extendedModel['style']['lowColor'] === '#FF0000' && $extendedModel['data'][1]['color'] === '#123456'
    && str_contains($pict->GetIPSViewHTML(), '"colorMode":"category"'),
    'Value coloring must be an independent design mode, not overwrite per-source colors or IPSView design.'
);
$pictExtendedBefore = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
$pict->ApplyChanges();
$pict->ApplyChanges();
assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictExtendedBefore
    && strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Repeated ApplyChanges must preserve sources/design and bounded extended outputs.');
$pict->UpdateBarPreviewFromForm('{"Title":"Draft <title>","Symbol":"circle","SymbolRepeat":false,"ScaleMaximum":100}');
$pictSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$pictDocument = new DOMDocument();
assertGatewayGauge($pictDocument->loadXML($pictSvg, LIBXML_NONET) && str_contains($pictSvg, '<ellipse')
    && str_contains($pictSvg, 'Draft &lt;title&gt;') && str_contains($pictSvg, 'Battery &lt;same&gt;')
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === [...$pictBefore, ...$pictExtended]
    && $GLOBALS['symconTestArchiveQueryCount'] === $pictQueries, 'Previews must use escaped current values without persistence or archives.');
$GLOBALS['symconTestVariables'][88601]['Value'] = 52.0;
$pict->MessageSink(1780000001, 88601, VM_UPDATE, [52.0, true]);
assertGatewayGauge(json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['data'][1]['value'] === 52.0, 'Value updates must refresh PictorialBar.');
$GLOBALS['symconTestVariables'][88601]['Value'] = -1.0;
$pict->MessageSink(1780000002, 88601, VM_UPDATE, [-1.0, true]);
assertGatewayGauge($pict->GetTestStatus() === 205, 'Negative values must fail visibly.');
$pict->UpdateBarPreviewFromForm('{}');
$pictBadSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(str_contains($pictBadSvg, 'Preview requires valid non-negative values.') && !str_contains($pictBadSvg, '<pattern'), 'Invalid values must not become example glyphs.');
$GLOBALS['symconTestVariables'][88601]['Value'] = 0.0;
$pict->MessageSink(1780000003, 88601, VM_UPDATE, [0.0, true]);
assertGatewayGauge($pict->GetTestStatus() === IS_ACTIVE, 'Zero must recover without a configuration reset.');
assertTransientRetry($pict, 'PictorialBar');
$pictParent = IPSModuleStrict::$ParentResponder;
IPSModuleStrict::$ParentResponder = static function (string $json) use ($pictParent): string
{
    $result = json_decode($pictParent($json), true, 512, JSON_THROW_ON_ERROR);
    $result['Payload']['VariableID'] = 1;
    return json_encode($result, JSON_THROW_ON_ERROR);
};
try {
    $pict->RetryTransient();
    assertGatewayGauge($pict->GetTestStatus() === 204, 'Mismatched Gateway source identity must be rejected.');
} finally {
    IPSModuleStrict::$ParentResponder = $pictParent;
}
$pict->RetryTransient();
foreach (['', 'IPSView'] as $prefix) {
    foreach ([['Symbol', 'image://https://example.invalid', 'rect'], ['SymbolSize', 3, 12], ['SymbolGap', 21, 2],
        ['BarWidth', 81, 40], ['ScaleMaximum', -1, 0], ['TitleFontSizePercent', 201, 100],
        ['OpacityPercent', 101, 90], ['AxisColor', -2, -1], ['SortOrder', 'invalid', 'configured'],
        ['FontFamily', 'url(https://example.invalid)', 'Arial, sans-serif'], ['ValuePosition', 'invalid', 'outside'],
        ['ValueDistance', 31, 5], ['CategoryLabelRotate', 91, 0], ['GridWidth', -1, 1], ['GridType', 'invalid', 'solid'],
        ['GridOpacityPercent', 101, 25], ['ColorMode', 'invalid', 'category'], ['LowColor', -1, 0xFF0000],
        ['MiddleColor', 0x1000000, 0xFFFF00], ['HighColor', -2, 0x00FF00], ['BorderType', 'invalid', 'solid'],
        ['ShadowBlur', 21, 0], ['ShadowColor', -2, -1], ['ShadowOffsetX', -21, 0], ['ShadowOffsetY', 21, 0],
        ['Focus', 'invalid', 'self'], ['BlurOpacityPercent', 101, 10],
        ['ColorMinimum', -0.5, 0.0], ['ColorMaximum', INF, 100.0], ['ColorMaximum', NAN, 100.0],
        ['ColorMinimum', 100.0, 0.0], ['ColorMaximum', 0.0, 100.0], ['ColorMaximum', 1.0e13, 100.0]] as [$name,$bad,$restore]) {
        $pict->SetTestProperty($prefix . $name, $bad);
        assertGatewayGauge(json_decode($pict->GetPictorialBarDiagnostic(), true)['valid'] === false, 'Reject invalid PictorialBar design: ' . $prefix . $name);
        $pict->SetTestProperty($prefix . $name, $restore);
    }
}
$pict->SetTestProperty('ColorMinimum', 0.5);
$pict->SetTestProperty('ColorMaximum', 100.5);
$pict->SetTestProperty('ColorMode', 'value');
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"symbol":"rect"'), 'Copy Tile design includes symbol settings.');
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"colorMinimum":0.5')
    && str_contains($pict->GetIPSViewHTML(), '"colorMaximum":100.5')
    && str_contains($pict->GetIPSViewHTML(), '"colorMode":"value"'), 'Copy must preserve fractional color ranges and coloring mode.');
$pict->SetTestProperty('IPSViewUseTileDesign', true);
$pict->SetTestProperty('FontFamily', 'monospace');
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"fontFamily":"monospace"'), 'Inherited IPSView design must use new tile properties.');
foreach (['[]', '[{"VariableID":88600},{"VariableID":88600}]', '[{"VariableID":4712}]'] as $invalidSources) {
    $pict->SetTestProperty('Sources', $invalidSources);
    assertGatewayGauge(json_decode($pict->GetPictorialBarDiagnostic(), true)['valid'] === false, 'Reject empty, duplicate or nonnumeric sources.');
}
$pict->SetTestProperty('Sources', '[{"VariableID":88600},{"VariableID":4711}]');
assertGatewayGauge(json_decode($pict->GetPictorialBarDiagnostic(), true)['valid'] === false, 'Mixed units must be rejected.');
$pict->SetTestProperty('Sources', '[{"VariableID":88600}]');
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestReferences() === [88600]
    && !in_array(['SenderID'=>88601, 'Message'=>VM_UPDATE], $pict->GetTestMessages(), true), 'Removing sources must clean references and subscriptions.');
$GLOBALS['symconTestVariables'][88600]['Name'] = 'Renamed battery';
$renamedPict = json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['data'][0];
assertGatewayGauge(
    $renamedPict['label'] === 'Renamed battery' && $renamedPict['id'] === 'variable-88600',
    'Renaming a source must update the fallback label, not its technical identity.'
);
$pict->SetTestParentActive(false);
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === 203, 'Missing parent must fail cleanly.');
$pictLocale = json_decode(file_get_contents(__DIR__ . '/../EChartsPictorialBar/locale.json'), true)['translations']['de'];
$pictCheckCaptions = static function (array $items) use (&$pictCheckCaptions, $pictLocale): void
{
    foreach ($items as $key=>$item) {
        if (in_array($key, ['caption', 'transparentCaption'], true) && is_string($item)) {
            assertGatewayGauge(isset($pictLocale[$item]), 'Missing PictorialBar translation: ' . $item);
        } elseif (is_array($item)) {
            $pictCheckCaptions($item);
        }
    }
};
$pictCheckCaptions(json_decode(file_get_contents(__DIR__ . '/../EChartsPictorialBar/form.json'), true));
preg_match_all("/(?:Translate|translate)\\(\\s*'([^']+)'/", file_get_contents(__DIR__ . '/../EChartsPictorialBar/module.php')
    . file_get_contents(__DIR__ . '/../EChartsPictorialBar/PictorialBarPreview.php'), $pictTexts);
foreach ($pictTexts[1] as $text) {
    assertGatewayGauge(isset($pictLocale[$text]), 'Missing runtime translation: ' . $text);
}
echo "PictorialBar integration verified.\n";
