<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsPictorialBar/module.php';

function IPS_GetKernelDirEx(): string
{
    return __DIR__ . '/fixtures/symcon-icons/';
}

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
$pictColumnSources = array_slice($pictSources, 0, 2);
foreach ($pictColumnSources as &$columnSource) {
    $columnSource['Label'] = 'Area / sensor with a deliberately long description / battery';
}
unset($columnSource);
$pict->UpdateBarPreviewFromForm(json_encode(['Sources' => $pictColumnSources, 'Orientation' => 'horizontal',
    'IPSViewOrientation'                               => 'horizontal', 'LabelColumnWidthPercent' => 20, 'IPSViewLabelColumnWidthPercent' => 50,
    'IPSViewUseTileDesign'                             => false], JSON_THROW_ON_ERROR));
$columnLabels = [];
foreach (array_slice($pict->GetTestFormUpdates(), -2) as $index => $update) {
    $columnSvg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    $columnDoc = new DOMDocument();
    assertGatewayGauge($columnDoc->loadXML($columnSvg, LIBXML_NONET), 'Label-column preview must remain valid SVG.');
    $labels = (new DOMXPath($columnDoc))->query('//*[local-name()="text"][@transform and @text-anchor="end"]');
    assertGatewayGauge(
        $labels->length === 2 && (float) $labels->item(0)->getAttribute('x') === ($index === 0 ? 134.0 : 350.0),
        'Independent preview columns must reflect 20% and 50% of the 720px preview.'
    );
    $columnLabels[] = $labels->item(0)->textContent;
}
assertGatewayGauge(mb_strlen($columnLabels[1]) > mb_strlen($columnLabels[0]), 'Wider columns must expose more of a long label.');
$pict->SetTestProperty('LabelColumnWidthPercent', 35);
$pict->SetTestProperty('IPSViewLabelColumnWidthPercent', 55);
assertGatewayGauge(json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['style']['labelColumnWidthPercent'] === 35
    && str_contains($pict->GetIPSViewHTML(), '"labelColumnWidthPercent":55'), 'Each output must carry its own column width.');
$pict->SetTestProperty('LabelColumnWidthPercent', 0);
$pict->SetTestProperty('IPSViewLabelColumnWidthPercent', 0);
$pictExtended = ['ColorMode' => 'value', 'ColorMinimum' => 0.0, 'ColorMaximum' => 100.0,
    'LowColor'               => 0xFF0000, 'MiddleColor' => 0xFFFF00, 'HighColor' => 0x00FF00,
    'FontFamily'             => 'Georgia, serif', 'ValuePosition' => 'insideEnd', 'ValueDistance' => 8,
    'ValueBold'              => true, 'HideOverlap' => false, 'CategoryLabelRotate' => 25, 'LabelColumnWidthPercent' => 40,
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
        ['ValueDistance', 31, 5], ['CategoryLabelRotate', 91, 0], ['LabelColumnWidthPercent', -1, 0],
        ['LabelColumnWidthPercent', 81, 0], ['GridWidth', -1, 1], ['GridType', 'invalid', 'solid'],
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
$pict->SetTestProperty('LabelColumnWidthPercent', 45);
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"symbol":"rect"'), 'Copy Tile design includes symbol settings.');
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"labelColumnWidthPercent":45'), 'Design copying must include the label column.');
$pict->SetTestProperty('LabelColumnWidthPercent', 0);
$pict->SetTestProperty('IPSViewLabelColumnWidthPercent', 0);
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
$pict->SetTestProperty('Sources', json_encode(array_slice($pictSources, 0, 2), JSON_THROW_ON_ERROR));
$GLOBALS['symconTestVariables'][88600]['Value'] = 16.0;
$GLOBALS['symconTestVariables'][88601]['Value'] = 100.0;
$pict->SetTestProperty('ColorMode', 'gradient');
$pict->SetTestProperty('ColorMinimum', 0.0);
$pict->SetTestProperty('ColorMaximum', 100.0);
$pict->SetTestProperty('IPSViewUseTileDesign', false);
$pict->SetTestProperty('IPSViewColorMode', 'value');
$pict->ApplyChanges();
assertGatewayGauge(json_decode($pict->GetPictorialBarDiagnostic(), true)['valid']
    && json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['style']['colorMode'] === 'gradient'
    && str_contains($pict->GetIPSViewHTML(), '"colorMode":"value"'), 'Both coloring modes must coexist in independent designs.');
$pictGradientBefore = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
foreach (['horizontal', 'vertical'] as $orientation) {
    foreach ([true, false] as $repeat) {
        $pict->UpdateBarPreviewFromForm(json_encode(['Orientation' => $orientation, 'SymbolRepeat' => $repeat,
            'ScaleMaximum'                                         => 255, 'ColorMaximum' => 255.0, 'LowColor' => 0xFF0000, 'MiddleColor' => 0xFFFF00, 'HighColor' => 0x00FF00], JSON_THROW_ON_ERROR));
        $svgOutputs = array_slice($pict->GetTestFormUpdates(), -2);
        $tileSvg = base64_decode(substr($svgOutputs[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
        $ipsSvg = base64_decode(substr($svgOutputs[1]['Value'], strlen('data:image/svg+xml;base64,')), true);
        $doc = new DOMDocument();
        assertGatewayGauge($doc->loadXML($tileSvg, LIBXML_NONET) && str_contains($tileSvg, '<linearGradient')
            && str_contains($tileSvg, 'gradientUnits="userSpaceOnUse"') && str_contains($tileSvg, 'offset="0.5" stop-color="#ffff00"')
            && str_contains($tileSvg, 'fill="url(#scale-gradient)"') && !str_contains($tileSvg, '<pattern')
            && !str_contains($ipsSvg, '<linearGradient'), 'Gradient preview must span the scale instead of repeating per glyph, independently of IPSView.');
        assertGatewayGauge(!$repeat || str_contains($tileSvg, '<clipPath'), 'Repeated groups must clip at the current value.');
    }
}
assertGatewayGauge(
    (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictGradientBefore,
    'Gradient previews must preserve saved properties.'
);
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"colorMode":"gradient"')
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Copying must preserve the gradient and output budget.');
$pictIconBefore = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
$pictIconForm = json_decode($pict->GetConfigurationForm(), true);
assertGatewayGauge(
    substr_count(json_encode($pictIconForm), 'Path-only SVG with viewBox; colors come from the designer.') === 2,
    'Both designers must include the SVG preparation guidance.'
);
foreach (['', 'IPSView'] as $prefix) {
    $picker = findGaugeFormElement($pictIconForm['elements'], $prefix . 'SymbolIcon');
    assertGatewayGauge(
        $picker['type'] === 'SelectIcon' && str_contains($picker['onChange'], 'ECPB_UpdateBarPreviewFromForm'),
        'Both designers need a native visual icon picker with draft preview.'
    );
    $upload = findGaugeFormElement($pictIconForm['elements'], $prefix . 'CustomSymbolSVG');
    assertGatewayGauge(
        is_array($upload) && $upload['type'] === 'SelectFile' && $upload['extensions'] === '.svg'
        && str_contains($upload['onChange'], 'ECPB_UpdateBarPreviewFromForm'),
        'Both designers need a native SVG upload with draft preview.'
    );
}
foreach (['horizontal', 'vertical'] as $orientation) {
    foreach ([true, false] as $repeat) {
        foreach (['category', 'value', 'gradient'] as $mode) {
            $pict->UpdateBarPreviewFromForm(json_encode(['Symbol' => 'icon', 'SymbolIcon' => 'cell', 'SymbolRepeat' => $repeat,
                'Orientation'                                     => $orientation, 'ColorMode' => $mode], JSON_THROW_ON_ERROR));
            $iconSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
            $doc = new DOMDocument();
            assertGatewayGauge($doc->loadXML($iconSvg, LIBXML_NONET) && str_contains($iconSvg, 'id="symcon-icon-path"')
                && str_contains($iconSvg, '<use href="#symcon-icon-path"') && !str_contains($iconSvg, 'NaN')
                && strlen($iconSvg) < 500000, 'Icon drafts must be safe, bounded SVG in all modes.');
            assertGatewayGauge(
                $mode !== 'gradient' || str_contains($iconSvg, 'gradientTransform="matrix('),
                'Icon gradients must undo the local glyph transform instead of restarting.'
            );
        }
    }
}
$pict->UpdateBarPreviewFromForm('{"Symbol":"icon","SymbolIcon":"unknown"}');
$badIconSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($badIconSvg, 'The selected Symcon icon is unavailable or unsupported.')
    && !str_contains($badIconSvg, 'id="symcon-icon-path"')
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictIconBefore,
    'Unavailable icon drafts must explain the problem without changing configuration.'
);
$pict->SetTestProperty('Symbol', 'icon');
$pict->SetTestProperty('SymbolIcon', 'cell');
$pict->SetTestProperty('IPSViewSymbol', 'icon');
$pict->SetTestProperty('IPSViewSymbolIcon', 'mark');
$pict->ApplyChanges();
$iconData = json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['style'];
assertGatewayGauge($pict->GetTestStatus() === IS_ACTIVE && $iconData['symbolWidth'] === 24 && $iconData['symbolHeight'] === 12
    && str_contains($iconData['symbolPath'], 'M22 4') && str_contains($pict->GetIPSViewHTML(), '"symbolIcon":"mark"')
    && strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Independent selected paths must reach both outputs within budget.');
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
assertGatewayGauge(str_contains($pict->GetIPSViewHTML(), '"symbolIcon":"cell"'), 'Design copy must include the selected icon.');
$pict->SetTestProperty('SymbolIcon', '../unsafe');
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === 202, 'Unsafe selected icon must fail as a design error.');
$pict->SetTestProperty('Symbol', 'rect');
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === IS_ACTIVE, 'Unused icon selection must not invalidate standard shapes.');
// These large form snapshots have been checked; release them before upload limits.
unset($pictForm, $pictIconForm);
$pictSvgUpload = base64_encode(file_get_contents(__DIR__ . '/fixtures/pictorial-symbol-custom.svg'));
$pictSvgOther = base64_encode('<svg viewBox="2 3 16 16"><path d="M2 3L18 19L2 19Z"/></svg>');
$pictSvgBefore = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
$pictSvgStatus = $pict->GetTestStatus();
foreach (['horizontal', 'vertical'] as $orientation) {
    foreach ([true, false] as $repeat) {
        foreach (['category', 'value', 'gradient'] as $mode) {
            $pict->UpdateBarPreviewFromForm(json_encode(['Sources' => [], 'Symbol' => 'svg', 'CustomSymbolSVG' => $pictSvgUpload,
                'IPSViewSymbol'                                    => 'svg', 'IPSViewCustomSymbolSVG' => $pictSvgOther, 'IPSViewUseTileDesign' => false,
                'Orientation'                                      => $orientation, 'SymbolRepeat' => $repeat, 'ColorMode' => $mode,
                'IPSViewOrientation'                               => $orientation, 'IPSViewSymbolRepeat' => $repeat, 'IPSViewColorMode' => $mode], JSON_THROW_ON_ERROR));
            $updates = array_slice($pict->GetTestFormUpdates(), -2);
            foreach ($updates as $output => $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                $doc = new DOMDocument();
                assertGatewayGauge($doc->loadXML($svg, LIBXML_NONET) && str_contains($svg, '<use href="#symcon-icon-path"')
                    && str_contains($svg, $output === 0 ? 'M-12 -4 l16 0' : 'M2 3 L18 19')
                    && !str_contains($svg, 'upload-only-marker') && !str_contains($svg, '#123456')
                    && !str_contains($svg, 'NaN'), 'Both SVG drafts must use only their own validated geometry.');
                assertGatewayGauge(
                    $mode !== 'gradient' || str_contains($svg, 'gradientTransform="matrix('),
                    'Uploaded SVGs need the same chart-wide gradient as icons.'
                );
            }
        }
    }
}
$largeViewBoxSvg = '<svg viewBox="0 0 1000000 500000"><path d="M0 0L1000000 0L1000000 500000L0 500000Z"/></svg>';
$pict->UpdateBarPreviewFromForm(json_encode(['Sources' => [], 'Symbol' => 'svg', 'CustomSymbolSVG' => base64_encode($largeViewBoxSvg)], JSON_THROW_ON_ERROR));
$largeViewBoxPreview = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($largeViewBoxPreview, '<use href=') && !str_contains($largeViewBoxPreview, 'scale(0 0)'),
    'Large valid viewBoxes must not collapse to zero scale in the SVG preview.'
);
$pict->UpdateBarPreviewFromForm('{"Symbol":"svg","CustomSymbolSVG":"invalid"}');
$badSvg = base64_decode(substr(array_slice($pict->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
assertGatewayGauge(
    str_contains($badSvg, 'The custom SVG shape is invalid or unsupported.')
    && !str_contains($badSvg, '<use href=') && $pict->GetTestStatus() === $pictSvgStatus
    && (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict) === $pictSvgBefore,
    'SVG draft validation must not persist, change status or substitute another shape.'
);
$pict->SetTestProperty('Symbol', 'svg');
$pict->SetTestProperty('CustomSymbolSVG', $pictSvgUpload);
$pict->SetTestProperty('IPSViewSymbol', 'svg');
$pict->SetTestProperty('IPSViewCustomSymbolSVG', $pictSvgOther);
$pict->ApplyChanges();
$svgStyle = json_decode($pict->GetPictorialBarData(), true)['pictorialBar']['style'];
assertGatewayGauge(
    $pict->GetTestStatus() === IS_ACTIVE && $svgStyle['symbol'] === 'svg'
    && $svgStyle['symbolMinimumX'] === -12.0 && $svgStyle['symbolMinimumY'] === -6.0
    && $svgStyle['symbolWidth'] === 24.0 && !isset($svgStyle['customSymbolSVG'])
    && str_contains($pict->GetIPSViewHTML(), '"symbolMinimumX":2')
    && !str_contains($pict->GetVisualizationTile(), 'upload-only-marker')
    && !str_contains($pict->GetVisualizationTile(), $pictSvgUpload),
    'Only derived SVG geometry may be transported, with independent viewBox origins.'
);
$GLOBALS['symconTestCopyTarget'] = $pict;
try {
    $pict->CopyTileDesignToIPSView();
} finally {
    unset($GLOBALS['symconTestCopyTarget']);
}
$copiedSvg = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($pict);
assertGatewayGauge($copiedSvg['IPSViewCustomSymbolSVG'] === $pictSvgUpload
    && str_contains($pict->GetIPSViewHTML(), '"symbolMinimumX":-12'), 'Design copy must retain the uploaded form.');
$largeSvg = base64_encode('<svg viewBox="0 0 10 10"><!--' . str_repeat('x', 120000)
    . '--><path d="M0 0' . str_repeat('L1 1 ', 2000) . 'Z"/></svg>');
foreach (['CustomSymbolSVG', 'IPSViewCustomSymbolSVG'] as $name) {
    $pict->SetTestProperty($name, $largeSvg);
}
$pict->ApplyChanges();
assertGatewayGauge(
    $pict->GetTestStatus() === IS_ACTIVE && strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Large bounded uploads with 16 sources must stay below all output limits.'
);
$pict->SetTestProperty('CustomSymbolSVG', $pictSvgUpload);
$pict->SetTestProperty('IPSViewCustomSymbolSVG', 'invalid');
$pict->SetTestProperty('IPSViewUseTileDesign', true);
$pict->ApplyChanges();
assertGatewayGauge(
    $pict->GetTestStatus() === IS_ACTIVE && str_contains($pict->GetIPSViewHTML(), '"symbolMinimumX":-12'),
    'Inherited design must ignore unused independent uploads.'
);
$pict->SetTestProperty('IPSViewUseTileDesign', false);
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === 202, 'An active invalid SVG must fail as a configuration error.');
$pict->SetTestProperty('IPSViewSymbol', 'rect');
$pict->SetTestProperty('Symbol', 'rect');
$pict->SetTestProperty('CustomSymbolSVG', 'invalid');
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === IS_ACTIVE, 'Unused SVG uploads must not invalidate standard forms.');
$manyPictorialSources = $pictSources;
for ($index = 16; $index <= 100; ++$index) {
    $id = 88600 + $index;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
        'Value'                                            => (float) ($index % 101), 'Name' => 'Sensor <same>', 'Presentation' => ['SUFFIX' => ' %', 'DIGITS' => 0]];
    $manyPictorialSources[] = ['VariableID' => $id, 'Label' => 'Battery ' . ($index + 1), 'Color' => -1];
}
foreach ([72, 100] as $sourceCount) {
    $pict->SetTestProperty('Sources', json_encode(array_slice($manyPictorialSources, 0, $sourceCount), JSON_THROW_ON_ERROR));
    $pict->ApplyChanges();
    assertGatewayGauge($pict->GetTestStatus() === IS_ACTIVE, 'PictorialBar must accept 72 and 100 battery sources.');
    $largeModel = json_decode($pict->GetPictorialBarData(), true, 512, JSON_THROW_ON_ERROR)['pictorialBar'];
    assertGatewayGauge(
        count($largeModel['data']) === $sourceCount && count($pict->GetTestReferences()) === $sourceCount
        && $largeModel['data'][$sourceCount - 1]['id'] === 'variable-' . (88600 + $sourceCount - 1),
        'Large PictorialBar source sets must retain all IDs and active references without truncation.'
    );
    assertGatewayGauge(
        strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
        && strlen($pict->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
        'Both outputs and both previews must remain below the output limit with 100 sources.'
    );
}
$pict->SetTestProperty('Symbol', 'svg');
$pict->SetTestProperty('CustomSymbolSVG', $largeSvg);
$pict->SetTestProperty('ColorMode', 'gradient');
$pict->SetTestProperty('IPSViewSymbol', 'svg');
$pict->SetTestProperty('IPSViewCustomSymbolSVG', $largeSvg);
$pict->SetTestProperty('IPSViewColorMode', 'gradient');
$pict->ApplyChanges();
assertGatewayGauge(
    $pict->GetTestStatus() === IS_ACTIVE
    && strlen($pict->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT
    && strlen($pict->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
    'Large SVG uploads plus 100 gradient sources must stay inside all output budgets.'
);
$pict->UpdateBarPreviewFromForm(json_encode(['Sources' => array_slice($manyPictorialSources, 0, 100)], JSON_THROW_ON_ERROR));
foreach (array_slice($pict->GetTestFormUpdates(), -2) as $update) {
    $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
    assertGatewayGauge(
        substr_count($svg, 'data-source=') === 8 && str_contains($svg, 'first 8 of 100 categories')
        && !str_contains($svg, 'Example data') && !str_contains($svg, 'Preview requires'),
        'Both large-model previews must show a disclosed real source subset, never replacement example data.'
    );
}
$pict->SetTestProperty('Sources', json_encode($manyPictorialSources, JSON_THROW_ON_ERROR));
$pict->ApplyChanges();
assertGatewayGauge($pict->GetTestStatus() === 201, 'A 101st PictorialBar source must fail visibly, not be silently omitted.');
try {
    SymconECharts\EChartsCurrentComparisonSources::Resolve(json_encode(array_slice($manyPictorialSources, 0, 17), JSON_THROW_ON_ERROR), 'Funnel');
    throw new RuntimeException('The PictorialBar extension must not silently raise Funnel limits.');
} catch (InvalidArgumentException) {
}
$pict->SetTestProperty('Sources', json_encode($pictSources, JSON_THROW_ON_ERROR));
$pict->SetTestProperty('Symbol', 'rect');
$pict->SetTestProperty('IPSViewSymbol', 'rect');
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
preg_match_all("/->Translate\\(\\s*'([^']+)'/", file_get_contents(__DIR__ . '/../EChartsPictorialBar/module.php')
    . file_get_contents(__DIR__ . '/../EChartsPictorialBar/PictorialBarPreview.php'), $pictTexts);
foreach ($pictTexts[1] as $text) {
    assertGatewayGauge(isset($pictLocale[$text]), 'Missing runtime translation: ' . $text);
}
require __DIR__ . '/pictorial_bar_normalization.php';

echo "PictorialBar integration verified.\n";
