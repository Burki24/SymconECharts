<?php

declare(strict_types=1);

// Synthetic sources only; run with the existing real Gateway/Kernel doubles.
(static function (): void
{
    $existingVariables = array_keys($GLOBALS['symconTestVariables']);
    $existingArchives = array_keys($GLOBALS['symconTestArchiveVariables']);
    $parent = IPSModuleStrict::$ParentResponder;
    $sources = [];
    for ($index = 0; $index < 33; ++$index) {
        $id = 89800 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1780000000,
            'Value'                                            => 99999.0, 'Name' => 'Equal room', 'Presentation' => ['SUFFIX' => ' °C', 'DIGITS' => 1]];
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
            'AggregatedValues'                                           => $index === 8 ? [] : [
                ['TimeStamp' => strtotime('2026-10-16 11:00:00'), 'Avg' => $index === 31 ? -80.0 : (float) $index, 'Min' => -999, 'Max' => 999],
                ['TimeStamp' => strtotime('2026-10-16 10:00:00'), 'Avg' => (float) ($index + 1), 'Min' => -999, 'Max' => 999]
            ]];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'Color' => -1];
    }
    $large = new TestableEChartsBoxplot();
    $large->Create();
    $large->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 32), JSON_THROW_ON_ERROR));
    $large->SetTestProperty('EnableIPSView', true);
    $large->ApplyChanges();
    assertGatewayGauge($large->GetTestStatus() === IS_ACTIVE, 'Boxplot must accept 32 archive sources.');
    $model = json_decode($large->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
    $rows = $model['boxplot']['data'];
    assertGatewayGauge(
        count($rows) === 32 && count($large->GetTestReferences()) === 32
        && count(array_unique(array_column($rows, 'id'))) === 32 && count(array_unique(array_column($rows, 'label'))) === 1,
        '32 equally named Boxplot sources must retain every source identity and reference.'
    );
    assertGatewayGauge(
        $rows[8]['statistics'] === null && $rows[31]['statistics'][0] === -80.0
        && $rows[0]['statistics'] === [0.0, 0.25, 0.5, 0.75, 1.0],
        'Large Boxplots preserve independent empty sources, offscreen signed values and R7 summaries.'
    );
    foreach ([$large->GetVisualizationTile(), $large->GetIPSViewHTML()] as $html) {
        assertGatewayGauge(
            strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && str_contains($html, 'variable-89831'),
            '32 source summaries must reach both real HTML outputs below 1 MiB.'
        );
    }
    IPSModuleStrict::$ParentResponder = static function (): string
    {throw new RuntimeException('Preview must not read archives.'); };
    try {
        $form = json_decode($large->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(strlen(json_encode($form)) < SYMCON_OUTPUT_BUFFER_LIMIT, '32-source form outputs remain bounded.');
        $large->UpdateBarPreviewFromForm('{"Orientation":"horizontal","AxisFontSizePercent":200}');
        foreach (array_slice($large->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge(
                str_contains($svg, 'Equal room') && str_contains($svg, 'Example data')
                && preg_match('/data-preview-sources="([1-9][0-9]*)\/32"/', $svg, $shown)
                && substr_count($svg, 'data-box=') === (int) $shown[1] && (int) $shown[1] <= 10,
                '32-source previews must disclose their bounded configured-name/synthetic-value subset.'
            );
            $xml = new DOMDocument();
            assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET), 'Large Boxplot previews must be valid SVG.');
        }
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    $large->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
    $large->ApplyChanges();
    assertGatewayGauge($large->GetTestStatus() === 201, 'Boxplot must reject 33 sources.');
    $large->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 32), JSON_THROW_ON_ERROR));
    $large->SetTestProperty('Range', '30d');
    $limits = [];
    $sampleHours = 720;
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$limits, &$sampleHours): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $response = json_decode($parent($json), true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? null) === 'archive.read' && ($response['Success'] ?? false)) {
            $query = $request['Payload'];
            $limits[] = $query['Limit'];
            $response['Payload']['Points'] = array_map(static fn (int $hour): array => [$query['StartTimestamp'] + $hour * 3600, (float) $hour], range(0, min($sampleHours, $query['Limit']) - 1));
            $response['Payload']['Truncated'] = $query['Limit'] < $sampleHours;
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    try {
        $html = $large->GetVisualizationTile();
        assertGatewayGauge(
            str_contains($html, '"status":"error"') && str_contains($html, 'Choose a shorter time range.')
            && $large->GetTestStatus() === 204 && count($limits) === 14 && end($limits) === 640,
            'The shared 10000-sample budget must limit the last request and fail visibly instead of computing truncated quartiles. Limits: '
                . json_encode($limits) . '; status: ' . $large->GetTestStatus()
        );
        $large->SetTestProperty('Range', '7d');
        $sampleHours = 168;
        $limits = [];
        $complete = json_decode($large->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR)['boxplot']['data'];
        assertGatewayGauge(
            count($complete) === 32 && array_sum(array_column($complete, 'count')) === 5376
            && count($limits) === 32 && $complete[31]['statistics'] === [0.0, 41.75, 83.5, 125.25, 167.0],
            '32 complete seven-day archives must retain all 5376 hourly samples and their exact R7 summaries.'
        );
        $stressSources = array_slice($sources, 0, 32);
        foreach ($stressSources as &$source) {
            $source['Label'] = str_repeat('&<"', 40);
        }
        unset($source);
        $large->SetTestProperty('Sources', json_encode($stressSources, JSON_THROW_ON_ERROR));
        foreach ([$large->GetVisualizationTile(), $large->GetIPSViewHTML(), $large->GetConfigurationForm()] as $output) {
            assertGatewayGauge(
                strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT,
                '32 maximum-length HTML-expanding source labels must fit every output with the new runtime.'
            );
        }
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    $large->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 32), JSON_THROW_ON_ERROR));
    $large->SetTestProperty('Range', '7d');
    $large->ApplyChanges();
    assertGatewayGauge($large->GetTestStatus() === IS_ACTIVE, 'A shorter valid Boxplot window must recover from a shared-budget failure.');
    foreach (array_diff(array_keys($GLOBALS['symconTestVariables']), $existingVariables) as $id) {
        unset($GLOBALS['symconTestVariables'][$id]);
    }
    foreach (array_diff(array_keys($GLOBALS['symconTestArchiveVariables']), $existingArchives) as $id) {
        unset($GLOBALS['symconTestArchiveVariables'][$id]);
    }
})();
