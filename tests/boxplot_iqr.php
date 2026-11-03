<?php

declare(strict_types=1);

// Uses the existing real Gateway and Kernel doubles; never touches a live installation.
(static function (): void
{
    $id = 89700;
    $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000, 'Value' => 9999.0, 'Name' => 'Equal <room>',
        'Presentation'                                     => ['SUFFIX' => ' °C', 'DIGITS' => 2]];
    $samples = [-10, 1, 2, 3, 4, 5, 6, 20];
    $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
    foreach ($samples as $i => $value) {
        $GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'][] = [
            'TimeStamp' => strtotime('2026-10-16 03:00:00') + $i * 3600, 'Avg' => $value, 'Min' => -999, 'Max' => 999];
    }
    $GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'] = array_reverse($GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues']);
    $module = new TestableEChartsBoxplot();
    $module->Create();
    $module->SetTestProperty('Sources', json_encode([['VariableID' => $id]], JSON_THROW_ON_ERROR));
    $module->SetTestProperty('EnableIPSView', true);
    $module->SetTestProperty('IPSViewUseTileDesign', false);
    $module->SetTestProperty('IPSViewOrientation', 'horizontal');
    $module->ApplyChanges();
    $before = json_decode($module->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge($before['boxplot']['whiskers'] === 'minmax' && !isset($before['boxplot']['data'][0]['outliers']), 'Default models must retain the old schema.');
    $module->SetTestProperty('WhiskerMode', 'iqr');
    $module->ApplyChanges();
    $data = json_decode($module->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    $row = $data['boxplot']['data'][0];
    assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE && $data['boxplot']['whiskers'] === 'iqr'
        && $row['statistics'] === [1.0, 1.75, 3.5, 5.25, 6.0] && $row['outlierCount'] === 2
        && array_column($row['outliers'], 1) === [-10.0, 20.0] && $row['count'] === 8
        && $row['unit'] === '°C' && $row['decimals'] === 2, 'IQR must use complete hourly averages and retain original formatting/counts.');
    foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetTestVariableValue('IPSViewBoxplot')] as $html) {
        assertGatewayGauge(preg_match('~window.SYMC_VISUALIZATION = (.*?);</script>~s', $html, $match) === 1, 'Bootstrap required.');
        $boot = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge($boot['state']['chart']['boxplot']['data'][0] === $row
            && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Both outputs and published WebContent share the lossless IQR model.');
    }
    $properties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($module);
    $parent = IPSModuleStrict::$ParentResponder;
    IPSModuleStrict::$ParentResponder = static function (): string
    {throw new RuntimeException('Preview must not read archives.'); };
    try {
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(isset(findGaugeFormElement($form['elements'], 'WhiskerMode')['onChange'])
            && findGaugeFormElement($form['elements'], 'IPSViewWhiskerMode') === null, 'Whisker model is one shared data setting with live draft preview.');
        $module->UpdateBarPreviewFromForm('{"WhiskerMode":"iqr"}');
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            $xml = new DOMDocument();
            assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-whiskers="iqr"')
                && substr_count($svg, 'data-outlier=') === 2 && str_contains($svg, 'Example data'), 'Both previews must draw separate synthetic outliers.');
        }
        $module->UpdateBarPreviewFromForm('{"WhiskerMode":"minmax"}');
        $svg = base64_decode(substr(array_slice($module->GetTestFormUpdates(), -2)[0]['Value'], strlen('data:image/svg+xml;base64,')), true);
        assertGatewayGauge(!str_contains($svg, 'data-outlier='), 'Switching the draft back removes all synthetic outliers.');
        $module->UpdateBarPreviewFromForm('{"WhiskerMode":"invalid"}');
        foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                str_contains($svg, 'The Boxplot settings are invalid.') && !str_contains($svg, 'data-box='),
                'Invalid draft models must explain the error instead of drawing an unrelated distribution.'
            );
        }
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($module) === $properties, 'Drafts cannot change data settings or designs.');
    $module->SetTestProperty('WhiskerMode', 'invalid');
    $module->ApplyChanges();
    assertGatewayGauge($module->GetTestStatus() === 202, 'Unknown statistical models must fail configuration validation.');

    // Nearly half of 9984 loaded samples are outliers; no truncation or extra archive reads allowed.
    $sources = [];
    for ($i = 0; $i < 32; ++$i) {
        $variableID = 89710 + $i;
        $GLOBALS['symconTestVariables'][$variableID] = $GLOBALS['symconTestVariables'][$id];
        $GLOBALS['symconTestArchiveVariables'][$variableID] = ['AggregationType' => 0, 'LoggedValues' => [], 'AggregatedValues' => []];
        for ($hour = 0; $hour < 312; ++$hour) {
            $v = $hour < 76 ? -1.234567890123456e300 : ($hour >= 236 ? 1.234567890123456e300 : 0.0);
            $GLOBALS['symconTestArchiveVariables'][$variableID]['AggregatedValues'][] = [
                'TimeStamp' => strtotime('2026-10-16 11:00:00') - $hour * 3600, 'Avg' => $v, 'Min' => $v, 'Max' => $v];
        }
        $sources[] = ['VariableID' => $variableID, 'Label' => str_repeat('<>&"', 30)];
    }
    $module->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
    $module->SetTestProperty('Range', '30d');
    $module->SetTestProperty('WhiskerMode', 'iqr');
    $module->ApplyChanges();
    $data = json_decode($module->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE && array_sum(array_column($data['boxplot']['data'], 'count')) === 9984
        && array_sum(array_column($data['boxplot']['data'], 'outlierCount')) === 4864, 'All loaded outliers and samples must be retained within the unchanged 10000 budget.');
    $sizes = array_map(strlen(...), [$module->GetVisualizationTile(), $module->GetIPSViewHTML(), $module->GetConfigurationForm()]);
    assertGatewayGauge(max($sizes) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Large IQR models and forms must fit the existing output budget.');
    echo 'Boxplot IQR 32-source output/form bytes: ' . implode(', ', $sizes) . ".\n";
    foreach (array_merge([$id], range(89710, 89741)) as $variableID) {
        unset($GLOBALS['symconTestVariables'][$variableID], $GLOBALS['symconTestArchiveVariables'][$variableID]);
    }
    echo "Boxplot IQR integration verified.\n";
})();
