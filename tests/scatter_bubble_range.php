<?php

declare(strict_types=1);

// Real module and Gateway, synthetic Kernel archive storage only.
(static function (): void
{
    $parent = IPSModuleStrict::$ParentResponder;
    $variables = $GLOBALS['symconTestVariables'];
    $archives = $GLOBALS['symconTestArchiveVariables'];
    $requests = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use (&$requests): string
    {
        $r = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if ($r['Operation'] === 'archive.read') {
            $requests[] = $r['Payload'];
        }
        $gateway = new TestableEChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
        return $gateway->ForwardData($json);
    };
    try {
        $start = 1780000100;
        $sources = [];
        foreach (['x', 'y', 'size'] as $index => $role) {
            $id = 99760 + $index;
            $sources[] = ['VariableID'    => $id, 'Role' => $role, 'Label' => 'Same label',
                'UseVariablePresentation' => false, 'Unit' => $role === 'size' ? 'm/s' : '°C', 'Decimals' => 1];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => 99999.0,
                'VariableUpdated'                                  => $start, 'Name' => 'Synthetic measurement'];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
                'AggregatedValues'                                           => array_map(static fn (int $i): array => [
                    'TimeStamp' => $start + $i * 60, 'Avg' => $role === 'size' ? [-10.0, 50.0, 150.0][$i] : 10.0 + $i,
                    'Min'       => 0.0, 'Max' => 200.0, 'Duration' => 60
                ], [2, 1, 0])];
        }
        $chart = new TestableEChartsScatter();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(1780000400);
        $chart->SetTestProperty('Range', '1h');
        $chart->SetTestProperty('DataMode', 'minute');
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('Sources', json_encode($sources, JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        $read = static fn (): array => json_decode($chart->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
        $legacy = $read();
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $legacy['scatter']['style']['bubbleRangeMode'] === 'data'
            && $legacy['scatter']['style']['bubbleMinimum'] === 0.0 && $legacy['scatter']['style']['bubbleMaximum'] === 100.0,
            'Existing Bubble configurations must default to automatic observed extrema.'
        );
        $chart->SetTestProperty('BubbleRangeMode', 'manual');
        $requests = [];
        $chart->ApplyChanges();
        $fixed = $read();
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $fixed['points'] === $legacy['points']
            && array_column($fixed['points'], 3) === [-10.0, 50.0, 150.0]
            && $fixed['roles'] === $legacy['roles'] && $fixed['range'] === $legacy['range']
            && $fixed['truncated'] === $legacy['truncated'],
            'Fixed Bubble bounds affect geometry only, not original data, units, joins or truncation.'
        );
        foreach ($requests as $request) {
            assertGatewayGauge(
                $request['Limit'] === 333 && $request['AggregationLevel'] === 6,
                'The default 1000-point archive budget must still be split across all three roles.'
            );
        }
        assertGatewayGauge(count($requests) >= 3, 'Exercise actual archive requests, not only a design model.');
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewBubbleRangeMode', 'manual');
        $chart->SetTestProperty('IPSViewBubbleMinimum', -50.0);
        $chart->SetTestProperty('IPSViewBubbleMaximum', 150.0);
        $chart->ApplyChanges();
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML()] as $index => $html) {
            preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $matches);
            $bootstrap = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $bootstrap['state']['status'] === 'ready'
                && (float) $bootstrap['state']['chart']['scatter']['style']['bubbleMinimum'] === ($index === 0 ? 0.0 : -50.0),
                'Tile and independent IPSView must retain separate size references.'
            );
        }
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['', 'IPSView'] as $prefix) {
            $mode = findGaugeFormElement($form['elements'], $prefix . 'BubbleRangeMode');
            assertGatewayGauge(
                array_column($mode['options'], 'value') === ['data', 'manual']
                && str_contains($mode['onChange'], 'UpdateBubbleRangeForm($id, ' . ($prefix === '' ? 'false' : 'true'))
                && str_contains($mode['onChange'], 'UpdateBarPreviewFromForm')
                && findGaugeFormElement($form['elements'], $prefix . 'BubbleMinimum')['visible'] === true,
                'Both designers offer correctly targeted live controls and visible manual limits.'
            );
        }
        $chart->UpdateBubbleRangeForm(true, 'data');
        assertGatewayGauge(array_slice($chart->GetTestFormUpdates(), -2) === [
            ['Field' => 'IPSViewBubbleMinimum', 'Parameter' => 'visible', 'Value' => false],
            ['Field' => 'IPSViewBubbleMaximum', 'Parameter' => 'visible', 'Value' => false]
        ], 'Draft changes must update only the corresponding designer visibility.');
        $beforeQueries = count($requests);
        $chart->UpdateBarPreviewFromForm(json_encode(['Sources' => $sources, 'PointSize' => 10, 'BubbleMaxSize' => 40,
            'BubbleRangeMode'                                   => 'manual', 'BubbleMinimum' => 0.0, 'BubbleMaximum' => 12.0,
            'IPSViewUseTileDesign'                              => false, 'IPSViewPointSize' => 10, 'IPSViewBubbleMaxSize' => 40,
            'IPSViewBubbleRangeMode'                            => 'manual', 'IPSViewBubbleMinimum' => 0.0, 'IPSViewBubbleMaximum' => 24.0
        ], JSON_THROW_ON_ERROR));
        $svgs = [];
        foreach (array_slice($chart->GetTestFormUpdates(), -2) as $update) {
            $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')));
            preg_match_all('/<circle[^>]* r="([^"]+)"/', $svg, $matches);
            $svgs[] = array_map('floatval', $matches[1]);
        }
        assertGatewayGauge(
            count($requests) === $beforeQueries && count($svgs[0]) === 13
            && $svgs[0][6] === 12.5 && $svgs[1][6] === 8.75 && $svgs[0][12] === 20.0 && $svgs[1][12] === 12.5,
            'Both archive-free previews must project the same sample through their independent fixed bounds.'
        );
        assertGatewayGauge(
            $chart->GetTestProperties()['BubbleMaximum'] === 100.0,
            'Unsaved preview bounds must not persist configuration.'
        );
        $chart->UpdateBarPreviewFromForm(json_encode(['BubbleRangeMode' => 'manual',
            'BubbleMinimum'                                             => 10.0, 'BubbleMaximum' => 10.0], JSON_THROW_ON_ERROR));
        $badPreview = array_slice($chart->GetTestFormUpdates(), -2)[0]['Value'];
        assertGatewayGauge(
            str_contains(
                base64_decode(substr($badPreview, strlen('data:image/svg+xml;base64,'))),
                'Bubble limits must be finite with minimum below maximum.'
            ),
            'Invalid draft limits must be visible instead of silently showing an automatic scale.'
        );
        foreach (['', 'IPSView'] as $prefix) {
            foreach ([[0.0, 0.0], [2.0, 1.0], [NAN, 100.0], [0.0, INF], [-PHP_FLOAT_MAX, PHP_FLOAT_MAX]] as [$min, $max]) {
                $chart->SetTestProperty($prefix . 'BubbleMinimum', $min);
                $chart->SetTestProperty($prefix . 'BubbleMaximum', $max);
                $requests = [];
                $chart->ApplyChanges();
                assertGatewayGauge(
                    $chart->GetTestStatus() === 202 && $requests === [],
                    'Invalid, nonfinite or overflowing active fixed bounds must fail before archive reads.'
                );
            }
            $chart->SetTestProperty($prefix . 'BubbleMinimum', 0.0);
            $chart->SetTestProperty($prefix . 'BubbleMaximum', 100.0);
            $chart->SetTestProperty($prefix . 'BubbleRangeMode', 'unknown');
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202, 'Unknown Bubble scale modes must be rejected.');
            $chart->SetTestProperty($prefix . 'BubbleRangeMode', 'manual');
        }
        $chart->SetTestProperty('IPSViewBubbleRangeMode', 'data');
        $chart->SetTestProperty('BubbleRangeMode', 'data');
        $chart->ApplyChanges();
        assertGatewayGauge($read() === $legacy, 'Restoring automatic mode preserves the exact legacy tile model.');
        $chart->SetTestProperty('BubbleRangeMode', 'manual');
        $chart->SetTestProperty('BubbleMinimum', -10.5);
        $chart->SetTestProperty('BubbleMaximum', 255.5);
        $GLOBALS['symconTestCopyTarget'] = $chart;
        try {
            $chart->CopyTileDesignToIPSView();
        } finally {
            unset($GLOBALS['symconTestCopyTarget']);
        }
        assertGatewayGauge(
            $chart->GetTestProperties()['IPSViewBubbleRangeMode'] === 'manual'
            && $chart->GetTestProperties()['IPSViewBubbleMinimum'] === -10.5
            && $chart->GetTestProperties()['IPSViewBubbleMaximum'] === 255.5,
            'Copying Tile design must include the floating-point Bubble references.'
        );
        foreach ([200 => 66, 2000 => 666] as $budget => $limit) {
            $chart->SetTestProperty('PointBudget', $budget);
            $requests = [];
            $chart->ApplyChanges();
            $read();
            assertGatewayGauge(
                count($requests) >= 3 && array_unique(array_column($requests, 'Limit')) === [$limit],
                'Both allowed archive budget boundaries must still divide across all three roles.'
            );
        }
        $properties = $chart->GetTestProperties();
        $chart->ApplyChanges();
        $chart->RefreshArchive();
        assertGatewayGauge(
            $chart->GetTestProperties() === $properties,
            'Repeated apply and refresh must not rewrite design, sources or archive settings.'
        );
        assertGatewayGauge(
            strlen($chart->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($chart->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'Both complete outputs must remain below the existing HTML budget.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
        $GLOBALS['symconTestVariables'] = $variables;
        $GLOBALS['symconTestArchiveVariables'] = $archives;
    }
})();
