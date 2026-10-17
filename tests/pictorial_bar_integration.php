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
$pict->UpdateBarPreviewFromForm('{"Title":"Draft <title>","Symbol":"circle","SymbolRepeat":false,"ScaleMaximum":100}');
$pictSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
$pictDocument = new DOMDocument();
assertGatewayGauge($pictDocument->loadXML($pictSvg, LIBXML_NONET) && str_contains($pictSvg, '<ellipse')
    && str_contains($pictSvg, 'Draft &lt;title&gt;') && str_contains($pictSvg, 'Battery &lt;same&gt;')
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictBefore
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
        ['OpacityPercent', 101, 90], ['AxisColor', -2, -1], ['SortOrder', 'invalid', 'configured']] as [$name,$bad,$restore]) {
        $pict->SetTestProperty($prefix . $name, $bad);
        assertGatewayGauge(json_decode($pict->GetPictorialBarDiagnostic(), true)['valid'] === false, 'Reject invalid PictorialBar design: ' . $prefix . $name);
        $pict->SetTestProperty($prefix . $name, $restore);
    }
}
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"symbol":"rect"'), 'Copy Tile design includes symbol settings.');
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
