<?php

declare(strict_types=1);

use eCharts\EChartsAsset;
use eCharts\EChartsSvgImage;

$wind = new EChartsGaugeWind();
$wind->InstanceID = 64872;
$wind->Create();
$wind->ApplyChanges();
assertGatewayGauge($wind->GetTestStatus() === 201, 'A wind compass needs a numeric bearing.');
$directionID = 8410;
$speedID = 8411;
$GLOBALS['symconTestVariables'][$directionID] = [
    'VariableType' => 2, 'VariableUpdated' => 1780000040, 'Value' => -45.5, 'Name' => 'Direction'
];
$GLOBALS['symconTestVariables'][$speedID] = [
    'VariableType' => 2, 'VariableUpdated' => 1780000060, 'Value' => 12.75, 'Name' => 'Speed'
];
$wind->SetTestProperty('DirectionVariableID', $directionID);
$wind->SetTestProperty('SpeedVariableID', $speedID);
$wind->SetTestProperty('SpeedUnit', 'm/s');
$wind->SetTestProperty('SpeedDecimals', 3);
$wind->SetTestProperty('EnableIPSView', true);
$wind->SetTestProperty('IPSViewUseTileDesign', false);
$wind->SetTestProperty('IPSViewShowRotor', true);
$wind->SetTestProperty('IPSViewRotateCompass', true);
$wind->SetTestProperty('IPSViewPointerColor', 0xABCDEF);
$wind->ApplyChanges();

$readState = static function () use ($wind): array
{
    $updates = $wind->GetTestVisualizationUpdates();
    return json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
};
$chart = json_decode($wind->GetWindData(), true, 512, JSON_THROW_ON_ERROR);
assertGatewayGauge(
    $wind->GetTestVisualizationType() === 1 && $wind->GetTestStatus() === IS_ACTIVE
    && $wind->GetTestReferences() === [$directionID, $speedID]
    && $chart['direction'] === 314.5 && $chart['speed'] === 12.75 && $chart['speedUnit'] === 'm/s'
    && $chart['speedDecimals'] === 3 && $chart['directionTimestamp'] === 1780000040
    && $chart['speedTimestamp'] === 1780000060 && $chart['labels'] === ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'],
    'Both current.read results must retain original precision, unit and timestamps while direction wraps to [0,360).'
);

$tile = $wind->GetVisualizationTile();
$ipsView = $wind->GetTestVariableValue('IPSViewWind');
foreach ([[$tile, false], [$ipsView, true]] as [$html, $independent]) {
    assertGatewayGauge(
        is_string($html) && strlen($html) <= SYMCON_OUTPUT_BUFFER_LIMIT
        && preg_match('/window\.SYMC_VISUALIZATION = (.+?);<\/script>/s', $html, $matches) === 1,
        'Both gauge wind pages must supply a bounded native ECharts bootstrap.'
    );
    $bootstrap = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    $shown = $bootstrap['state']['chart'];
    assertGatewayGauge(
        $shown['direction'] === 314.5 && $shown['style']['showRotor'] === $independent
        && $shown['style']['rotateCompass'] === $independent
        && strtoupper($shown['style']['pointerColor']) === ($independent ? '#ABCDEF' : '')
        && ($independent ? str_contains($html, EChartsAsset::RendererScript('gauge', true)) : str_contains($html, EChartsAsset::JavaScript())),
        'Tile and IPSView must use the dedicated wind chart and their effective design with the shipped Gauge runtime.'
    );
}
$form = json_decode($wind->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
foreach (['RotateCompass', 'IPSViewRotateCompass'] as $name) {
    assertGatewayGauge(
        findGaugeFormElement($form['elements'], $name)['type'] === 'CheckBox',
        'Both compass designers must offer the rotating wind rose.'
    );
}
foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
    assertGatewayGauge(
        str_starts_with(findGaugeFormElement($form['elements'], $name)['image'] ?? '', 'data:image/svg+xml'),
        'Wind designers must offer two SVG previews.'
    );
}
$wind->UpdateBarPreviewFromForm(json_encode(['Title' => 'Draft only', 'SpeedDecimals' => 25, 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
$previewURI = $wind->GetTestFormUpdates()[0]['Value'];
$previewSVG = base64_decode(substr($previewURI, strpos($previewURI, ',') + 1), true);
assertGatewayGauge(array_column($wind->GetTestFormUpdates(), 'Field') === ['BarPreview', 'IPSViewBarPreview']
    && is_string($previewSVG) && preg_match('/12[,.]750000 m\/s/', $previewSVG) === 1
    && $wind->GetTestProperties()['Title'] === '', 'Wind preview edits affect both draft images only and respect precision bounds.');
$GLOBALS['symconTestVariables'][$directionID]['Value'] = 135;
$wind->UpdateBarPreviewFromForm(json_encode(['RotateCompass' => true, 'IPSViewUseTileDesign' => false,
    'IPSViewRotateCompass'                                   => false], JSON_THROW_ON_ERROR));
foreach (array_slice($wind->GetTestFormUpdates(), -2) as $update) {
    $rotate = $update['Field'] === 'BarPreview';
    $image = $update['Value'];
    $svg = new DOMDocument();
    $svg->loadXML(base64_decode(substr($image, strpos($image, ',') + 1), true));
    $xpath = new DOMXPath($svg);
    $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
    $pointer = $xpath->query('//s:path[starts-with(@d, "M0 -66")]')->item(0);
    $southEast = $xpath->query('//s:text[text()="SE"]')->item(0);
    assertGatewayGauge(
        $pointer->getAttribute('transform') === ($rotate ? 'rotate(0)' : 'rotate(135)')
        && ($rotate ? ((float) $southEast->getAttribute('x') === 0.0 && (float) $southEast->getAttribute('y') < 0)
            : ((float) $southEast->getAttribute('x') > 0 && (float) $southEast->getAttribute('y') > 0)),
        'At 135 degrees the rotating preview must place SE under the fixed pointer while the standard preview shows SE at bottom right.'
    );
}
assertTransientRetry($wind, 'Wind Gauge');

foreach ([[360, 0], [725, 5], [-360, 0], [-1e-16, 0]] as [$input, $expected]) {
    $GLOBALS['symconTestVariables'][$directionID]['Value'] = $input;
    $wind->MessageSink(time(), $directionID, VM_UPDATE, []);
    assertGatewayGauge(
        $readState()['chart']['direction'] === $expected && $wind->GetTestVariableValue('IPSViewWind') === $ipsView,
        'VM_UPDATE must wrap the current bearing and push data without rewriting IPSView WebContent.'
    );
}
$GLOBALS['symconTestVariables'][$speedID]['Value'] = -0.25;
$wind->MessageSink(time(), $speedID, VM_UPDATE, []);
assertGatewayGauge($wind->GetTestStatus() === 205 && $readState()['error'] === 'Wind speed must be non-negative.'
    && $wind->GetTestTimerInterval('TransientRetry') === 0, 'A negative measurement must not appear as calm or request retries.');
$GLOBALS['symconTestVariables'][$speedID]['Value'] = 0;
$wind->MessageSink(time(), $speedID, VM_UPDATE, []);
assertGatewayGauge(
    $wind->GetTestStatus() === IS_ACTIVE && $readState()['chart']['speed'] === 0,
    'A measured zero is calm with its correct displayed value.'
);

$wind->SetTestProperty('SpeedVariableID', 0);
$wind->ApplyChanges();
assertGatewayGauge(
    $wind->GetTestReferences() === [$directionID]
    && !in_array(['SenderID' => $speedID, 'Message' => VM_UPDATE], $wind->GetTestMessages(), true)
    && $readState()['chart']['speed'] === null && $readState()['chart']['speedTimestamp'] === null,
    'Clearing speed must remove its subscription and report unavailable instead of inventing calm.'
);
$wind->ReloadTestModule();
$wind->MessageSink(time(), 0, IPS_KERNELSTARTED, []);
assertGatewayGauge(
    $wind->GetTestStatus() === IS_ACTIVE
    && in_array(['SenderID' => $directionID, 'Message' => VM_UPDATE], $wind->GetTestMessages(), true),
    'The wind gauge must reinitialize on kernel startup with the selected sources.'
);
foreach ([$directionID, 4712, -1, 789123] as $invalidSpeed) {
    $wind->SetTestProperty('SpeedVariableID', $invalidSpeed);
    $wind->ApplyChanges();
    assertGatewayGauge(
        $wind->GetTestStatus() === 201 && $readState()['chart'] === null,
        'Duplicate, nonnumeric, negative and missing speed IDs are invalid.'
    );
}
$wind->SetTestProperty('SpeedVariableID', 0);
$wind->SetTestProperty('SpeedDecimals', 7);
$wind->ApplyChanges();
assertGatewayGauge($wind->GetTestStatus() === 202, 'Wind formatting bounds are enforced.');
$wind->SetTestProperty('SpeedDecimals', 2);
foreach (['CompassSvg', 'IPSViewCompassSvg'] as $name) {
    $upload = findGaugeFormElement($form['elements'], $name);
    assertGatewayGauge(
        ($upload['type'] ?? '') === 'SelectFile' && $upload['extensions'] === '.svg'
        && str_contains($upload['onChange'], 'ECWI_UpdateBarPreviewFromForm'),
        'Both wind designers must wire SVG file selection into the live form preview.'
    );
}
$tileFile = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="9" fill="#175395"/></svg>');
$ipsFile = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="9" fill="#D15624"/></svg>');
$readPageImage = static function (string $html): string
{
    assertGatewayGauge(
        strlen($html) <= SYMCON_OUTPUT_BUFFER_LIMIT
        && preg_match('/window\.SYMC_VISUALIZATION = (.+?);<\/script>/s', $html, $matches) === 1,
        'A custom wind rose must fit the visualization bootstrap.'
    );
    $model = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    assertGatewayGauge($model['status'] === 'ready', 'Both pages must load a valid custom wind rose.');
    return $model['chart']['style']['compassImage'];
};
$wind->UpdateBarPreviewFromForm(json_encode([
    'CompassSvg' => $tileFile, 'IPSViewCompassSvg' => $ipsFile, 'IPSViewUseTileDesign' => false
], JSON_THROW_ON_ERROR));
$draftImages = array_column(array_slice($wind->GetTestFormUpdates(), -2), 'Value', 'Field');
assertGatewayGauge(
    $wind->GetTestProperties()['CompassSvg'] === '' && $wind->GetTestProperties()['IPSViewCompassSvg'] === '',
    'Selecting wind roses must only stage draft previews before Apply.'
);
$wind->SetTestProperty('CompassSvg', $tileFile);
$wind->SetTestProperty('IPSViewCompassSvg', $ipsFile);
$wind->ApplyChanges();
$uris = [
    'BarPreview'        => $readPageImage($wind->GetVisualizationTile()),
    'IPSViewBarPreview' => $readPageImage($wind->GetTestVariableValue('IPSViewWind'))
];
$savedForm = json_decode($wind->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
foreach ($uris as $field => $uri) {
    $preview = base64_decode(explode(',', $draftImages[$field], 2)[1], true);
    assertGatewayGauge(
        str_starts_with($uri, 'data:image/svg+xml;base64,')
        && str_contains(base64_decode(explode(',', $uri, 2)[1], true), $field === 'BarPreview' ? '#175395' : '#D15624')
        && str_contains($preview, $uri) && findGaugeFormElement($savedForm['elements'], $field)['image'] === $draftImages[$field],
        'Independent drafts, saved previews and both output pages must show the same selected image for each channel.'
    );
}
$wind->SetTestProperty('IPSViewUseTileDesign', true);
$badFile = base64_encode('<svg viewBox="0 0 10 10"><script>untrusted()</script></svg>');
$wind->SetTestProperty('IPSViewCompassSvg', $badFile);
$wind->ApplyChanges();
assertGatewayGauge(
    $readPageImage($wind->GetIPSViewHTML()) === $uris['BarPreview'],
    'An inherited IPSView design must show the tile image even if an unused independent file is invalid.'
);
$wind->UpdateBarPreviewFromForm(json_encode(['CompassSvg' => $badFile], JSON_THROW_ON_ERROR));
foreach (array_slice($wind->GetTestFormUpdates(), -2) as $update) {
    $preview = base64_decode(explode(',', $update['Value'], 2)[1], true);
    assertGatewayGauge(
        !str_contains($preview, '<script') && str_contains($preview, 'Select a supported SVG compass rose or clear the file.'),
        'An invalid draft must report the file error in both inherited previews.'
    );
}
$wind->SetTestProperty('IPSViewUseTileDesign', false);
$wind->ApplyChanges();
assertGatewayGauge(
    $wind->GetTestStatus() === 202 && $readState()['status'] === 'error',
    'An invalid active compass file must report a design configuration error.'
);
$wind->SetTestProperty('CompassSvg', '');
$wind->SetTestProperty('IPSViewCompassSvg', '');
$wind->ApplyChanges();
assertGatewayGauge(
    $readPageImage($wind->GetVisualizationTile()) === '' && $readPageImage($wind->GetIPSViewHTML()) === '',
    'Clearing both uploads must recover the built-in compass without changing the sources.'
);

$presetDirectory = dirname(__DIR__) . '/EChartsGaugeWind/roses/';
$presets = [];
foreach (glob($presetDirectory . '*.svg') as $file) {
    $presets[pathinfo($file, PATHINFO_FILENAME)] = EChartsSvgImage::ImportCompass(file_get_contents($file))['dataUri'];
}
assertGatewayGauge(count(array_unique($presets)) === 37, 'The module must ship 37 distinct, valid compass rose images.');
foreach (['01_lilienrose', '02_windstern', '03_nautischer_ring', '04_kompasssiegel', '05_graduierte_rose'] as $id) {
    $svg = new DOMDocument();
    $svg->loadXML(base64_decode(explode(',', $presets[$id], 2)[1], true));
    $xpath = new DOMXPath($svg);
    $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
    $bearings = [];
    foreach ($xpath->query('//s:g[starts-with(@transform, "translate(")]') as $label) {
        assertGatewayGauge(
            preg_match('/^translate\(([-\d.]+) ([-\d.]+)\) rotate\((\d+)\) scale\(/', $label->getAttribute('transform'), $parts) === 1,
            "A direction label in $id must rotate around its own center."
        );
        $bearing = ((int) round(rad2deg(atan2((float) $parts[1], -(float) $parts[2]))) + 360) % 360;
        assertGatewayGauge((int) $parts[3] === $bearing, "The label at {$bearing}° in $id must read upright under the fixed pointer.");
        $bearings[] = $bearing;
    }
    sort($bearings);
    assertGatewayGauge($bearings === range(0, 315, 45), "The wind rose $id must label all eight directions.");
}
$presetIDs = array_keys($presets);
$expectedOptions = ['auto', 'standard', ...$presetIDs];
sort($expectedOptions);
foreach (['CompassPreset', 'IPSViewCompassPreset'] as $name) {
    $selector = findGaugeFormElement($form['elements'], $name);
    $options = array_column($selector['options'], 'value');
    sort($options);
    assertGatewayGauge(
        $selector['type'] === 'Select' && $options === $expectedOptions && $wind->GetTestProperties()[$name] === 'auto'
        && str_contains($selector['onChange'], 'ECWI_UpdateBarPreviewFromForm'),
        'Both wind designers must offer every shipped rose and update previews when the selection changes.'
    );
}

// The shifted second selection exercises each preset in each channel and detects accidental inheritance.
$wind->SetTestProperty('CompassSvg', $badFile);
$wind->SetTestProperty('IPSViewCompassSvg', $badFile);
foreach ($presetIDs as $i => $tileID) {
    $ipsID = $presetIDs[($i + 1) % count($presetIDs)];
    $wind->SetTestProperty('CompassPreset', $tileID);
    $wind->SetTestProperty('IPSViewCompassPreset', $ipsID);
    assertGatewayGauge(
        $readPageImage($wind->GetVisualizationTile()) === $presets[$tileID]
        && $readPageImage($wind->GetIPSViewHTML()) === $presets[$ipsID],
        "Selected wind roses must reach the independent tile ($tileID) and IPSView ($ipsID) pages."
    );
    $presetForm = $wind->GetConfigurationForm();
    assertGatewayGauge(strlen($presetForm) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both preset previews must fit the configuration form.');
    $elements = json_decode($presetForm, true, 512, JSON_THROW_ON_ERROR)['elements'];
    foreach (['BarPreview' => $tileID, 'IPSViewBarPreview' => $ipsID] as $field => $presetID) {
        $image = findGaugeFormElement($elements, $field)['image'];
        assertGatewayGauge(
            str_contains(base64_decode(explode(',', $image, 2)[1], true), $presets[$presetID]),
            "$field must embed the selected wind rose $presetID."
        );
    }
}

$wind->SetTestProperty('IPSViewUseTileDesign', true);
$wind->SetTestProperty('IPSViewCompassPreset', 'retired-rose');
assertGatewayGauge($readPageImage($wind->GetIPSViewHTML()) === $presets[$tileID], 'Inheritance must ignore the unused preset ID.');
$wind->UpdateBarPreviewFromForm(json_encode(['CompassPreset' => 'retired-rose'], JSON_THROW_ON_ERROR));
foreach (array_slice($wind->GetTestFormUpdates(), -2) as $update) {
    $preview = base64_decode(explode(',', $update['Value'], 2)[1], true);
    assertGatewayGauge(
        str_contains($preview, 'Select an available compass rose preset.') && !str_contains($preview, $presets[$tileID]),
        'An invalid draft selection must show a preset error in both inherited previews.'
    );
}
$wind->SetTestProperty('CompassPreset', 'standard');
assertGatewayGauge(
    $readPageImage($wind->GetVisualizationTile()) === '' && $readPageImage($wind->GetIPSViewHTML()) === ''
    && $wind->GetTestProperties()['CompassSvg'] === $badFile,
    'Explicitly using the standard dial must ignore a stored upload and retain the file.'
);
$wind->SetTestProperty('IPSViewUseTileDesign', false);
$wind->ApplyChanges();
assertGatewayGauge(
    $wind->GetTestStatus() === 202 && $readState()['error'] === 'Select an available compass rose preset.',
    'An unknown active preset ID must report a design error.'
);
$wind->SetTestProperty('IPSViewCompassPreset', 'auto');
$wind->SetTestProperty('IPSViewCompassSvg', $ipsFile);
$wind->SetTestProperty('CompassPreset', 'auto');
$wind->SetTestProperty('CompassSvg', $tileFile);
assertGatewayGauge(
    $readPageImage($wind->GetVisualizationTile()) === $uris['BarPreview']
    && $readPageImage($wind->GetIPSViewHTML()) === $uris['IPSViewBarPreview'],
    'Returning to automatic mode must restore each previously selected upload.'
);
$wind->SetTestParentActive(false);
$wind->ApplyChanges();
assertGatewayGauge($wind->GetTestStatus() === 203, 'Missing wind gateway is reported explicitly.');

echo "Wind gauge values, references, designs, previews, IPSView delivery and lifecycle verified.\n";
