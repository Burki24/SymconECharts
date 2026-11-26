<?php

declare(strict_types=1);

use eCharts\EChartsAsset;

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
        && strtoupper($shown['style']['pointerColor']) === ($independent ? '#ABCDEF' : '')
        && ($independent ? str_contains($html, EChartsAsset::RendererScript('gauge', true)) : str_contains($html, EChartsAsset::JavaScript())),
        'Tile and IPSView must use the dedicated wind chart and their effective design with the shipped Gauge runtime.'
    );
}
$form = json_decode($wind->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
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
$wind->SetTestParentActive(false);
$wind->ApplyChanges();
assertGatewayGauge($wind->GetTestStatus() === 203, 'Missing wind gateway is reported explicitly.');

echo "Wind gauge values, references, designs, previews, IPSView delivery and lifecycle verified.\n";
