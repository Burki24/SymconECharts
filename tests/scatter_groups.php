<?php

declare(strict_types=1);

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
        $sources = [];
        foreach (range(0, 47) as $index) {
            $id = 99600 + $index;
            $role = ['x', 'y', 'size'][$index % 3];
            $sources[] = ['VariableID' => $id, 'Role' => $role, 'Group' => 'Room ' . intdiv($index, 3),
                'Label'                => 'Same label', 'UseVariablePresentation' => false,
                'Unit'                 => $role === 'size' ? '%' : ($role === 'x' ? '°C' : 'm/s'), 'Decimals' => 1];
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'Value' => 99999.0,
                'VariableUpdated'                                  => 1780000100, 'Name' => 'Same label'];
            $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0, 'LoggedValues' => [],
                'AggregatedValues'                                           => array_map(static fn (int $i): array => [
                    'TimeStamp' => 1780000100 + $i * 60, 'Avg' => 10.0 + $i + $index,
                    'Min'       => 0.0, 'Max' => 100.0, 'Duration' => 60
                ], $index === 4 ? [2, 0] : [2, 1, 0])];
        }
        $chart = new TestableEChartsScatter();
        $chart->Create();
        $chart->SetTestCurrentTimestamp(1780000400);
        $chart->SetTestProperty('Range', '1h');
        $chart->SetTestProperty('DataMode', 'minute');
        $chart->SetTestProperty('EnableIPSView', true);
        $chart->SetTestProperty('Sources', json_encode(array_slice($sources, 0, 6), JSON_THROW_ON_ERROR));
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Two complete named comparison groups must be accepted.');
        $data = json_decode($chart->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($data['groups']) === 2 && count($data['groups'][0]['points']) === 3
            && count($data['groups'][1]['points']) === 2,
            'Missing intervals in one group must not drop valid points of another group.'
        );
        $read = static fn (): array => json_decode($chart->GetScatterData(), true, 512, JSON_THROW_ON_ERROR);
        $set = static function (array $rows) use ($chart): void
        {
            $chart->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
            $chart->ApplyChanges();
        };
        $requests = [];
        $read();
        assertGatewayGauge(
            count($requests) === 6 && array_unique(array_column($requests, 'Limit')) === [166],
            'The existing 1000-point budget must be shared across all six assignments, not per group.'
        );
        $renamed = array_slice($sources, 0, 6);
        foreach ($renamed as &$row) {
            if ($row['Group'] === 'Room 0') {
                $row['Group'] = 'Renamed';
            }
        }
        unset($row);
        $set(array_merge(array_slice($renamed, 3), array_slice($renamed, 0, 3)));
        $reordered = $read();
        assertGatewayGauge(
            $reordered['groups'][1]['id'] === $data['groups'][0]['id']
            && $reordered['groups'][1]['colorIndex'] === $data['groups'][0]['colorIndex']
            && $reordered['groups'][1]['points'] === $data['groups'][0]['points'],
            'Group identity, palette index and values follow role IDs, never label or source ordering.'
        );
        $shared = array_slice($sources, 0, 6);
        $shared[3]['VariableID'] = $shared[0]['VariableID'];
        $set($shared);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $read()['groups'][1]['roles']['x']['variableID'] === 99600,
            'A measurement may be reused in different comparisons without losing role metadata.'
        );
        $metrics = array_slice($sources, 0, 6);
        foreach (range(3, 5) as $i) {
            $metrics[$i]['VariableID'] = $metrics[$i - 3]['VariableID'];
            $metrics[$i]['Reducer'] = 'maximum';
        }
        $set($metrics);
        $metricData = $read();
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE
            && $metricData['groups'][0]['id'] !== $metricData['groups'][1]['id']
            && $metricData['groups'][0]['points'] !== $metricData['groups'][1]['points'],
            'The same variables with explicitly different archive reducers are distinct valid comparisons.'
        );
        $set($sources);
        $requests = [];
        $many = $read();
        assertGatewayGauge(
            count($many['groups']) === 16 && count($requests) === 48
            && array_unique(array_column($requests, 'Limit')) === [20]
            && count($chart->GetTestReferences()) === 48,
            'Sixteen complete groups retain all 48 source references and a single bounded archive plan.'
        );
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $sourceField = findGaugeFormElement($form['elements'], 'Sources');
        assertGatewayGauge(
            findGaugeFormElement($sourceField['form'], 'Group') !== null,
            'Both list and individual row form must expose the optional comparison group.'
        );
        foreach (['BarPreview', 'IPSViewBarPreview'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            $svg = base64_decode(substr($field['image'], strlen('data:image/svg+xml;base64,')));
            $xml = new DOMDocument();
            assertGatewayGauge($xml->loadXML($svg, LIBXML_NONET) && substr_count($svg, 'data-comparison-id=') === 6
                && str_contains($svg, '6 of 16'), 'Both previews validate all groups and explicitly mark their six-group excerpt.');
        }
        foreach ([200 => 4, 1000 => 20, 2000 => 41] as $budget => $limit) {
            $chart->SetTestProperty('PointBudget', $budget);
            $requests = [];
            $chart->ApplyChanges();
            $read();
            assertGatewayGauge(
                count($requests) >= 48 && array_unique(array_column($requests, 'Limit')) === [$limit],
                'Every allowed budget remains a total across all group assignments.'
            );
        }
        $chart->SetTestProperty('IPSViewUseTileDesign', false);
        $chart->SetTestProperty('IPSViewBubbleRangeMode', 'manual');
        $chart->SetTestProperty('IPSViewBubbleMaximum', 255.0);
        $chart->SetTestProperty('BubbleRangeMode', 'manual');
        $chart->SetTestProperty('BubbleMaximum', 100.0);
        $chart->ApplyChanges();
        foreach ([$chart->GetVisualizationTile(), $chart->GetIPSViewHTML(), $chart->GetTestVariableValue('IPSViewScatter')] as $index => $html) {
            preg_match('/window.SYMC_VISUALIZATION = (.*?);<\/script>/s', $html, $match);
            $boot = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $boot['state']['status'] === 'ready' && count($boot['state']['chart']['groups']) === 16
                && (float) $boot['state']['chart']['scatter']['style']['bubbleMaximum'] === ($index === 0 ? 100.0 : 255.0)
                && strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Both outputs and published WebContent retain groups and independent original designs under 1 MiB.'
            );
        }
        $badCases = [];
        $bad = $sources;
        $bad[47]['Role'] = 'y';
        $badCases[] = $bad;
        $bad = $sources;
        $bad[47]['VariableID'] = 123;
        $badCases[] = $bad;
        $bad = $sources;
        $bad[47]['Unit'] = 'other';
        $badCases[] = $bad;
        $bad = $sources;
        $bad[47]['Group'] = str_repeat('x', 81);
        $badCases[] = $bad;
        $bad = array_slice($sources, 0, 6);
        foreach (range(3, 5) as $i) {
            $bad[$i]['VariableID'] = $bad[$i - 3]['VariableID'];
        }
        $badCases[] = $bad;
        $bad = [];
        foreach (range(0, 33) as $i) {
            $row = $sources[$i];
            $row['Group'] = 'Group ' . intdiv($i, 2);
            $row['Role'] = $i % 2 === 0 ? 'x' : 'y';
            $row['Unit'] = $i % 2 === 0 ? '°C' : 'm/s';
            $bad[] = $row;
        }
        $badCases[] = $bad;
        $badCases[] = [...$sources, $sources[0]];
        foreach ($badCases as $bad) {
            $requests = [];
            $set($bad);
            assertGatewayGauge(
                $chart->GetTestStatus() === 201 && $requests === [],
                'Invalid or excessive groups, units and hidden assignments must fail before archive I/O.'
            );
            $badForm = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
            $svg = base64_decode(substr(findGaugeFormElement($badForm['elements'], 'BarPreview')['image'], strlen('data:image/svg+xml;base64,')));
            assertGatewayGauge(
                !str_contains($svg, 'data-comparison-id='),
                'An invalid late group must not be concealed by a valid preview excerpt.'
            );
        }
        $set($sources);
        $mixed = array_values(array_filter(array_slice($sources, 0, 6), static fn (array $s): bool => !($s['Group'] === 'Room 1' && $s['Role'] === 'size')));
        $set($mixed);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && !isset($read()['groups'][1]['roles']['size']),
            'Ordinary Scatter and Bubble comparisons can coexist.'
        );
        $chart->SetTestProperty('ColorMapEnabled', true);
        $chart->SetTestProperty('ColorMapDimension', 'size');
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 202, 'Size colors require a size source in every displayed comparison.');
        $chart->SetTestProperty('ColorMapEnabled', false);
        $legacy = array_map(static function (array $row): array
        {
            unset($row['Group']);
            return $row;
        }, array_slice($sources, 0, 3));
        $set($legacy);
        $old = $read();
        $blank = array_map(static fn (array $row): array => $row + ['Group' => ''], $legacy);
        $set($blank);
        assertGatewayGauge(
            $read() === $old && !isset($old['groups']) && $old['variant'] === 'bubble',
            'Absent or blank Group preserves the exact legacy model shape without migration.'
        );
        $properties = $chart->GetTestProperties();
        $chart->ApplyChanges();
        $chart->RefreshArchive();
        assertGatewayGauge(
            $chart->GetTestProperties() === $properties,
            'Repeated apply and refresh never rewrite source configuration or design.'
        );
        $dense = [];
        foreach (range(0, 15) as $group) {
            foreach (['x', 'y'] as $offset => $role) {
                $row = $sources[$group * 3 + $offset];
                $row['Label'] = str_repeat('温度', 100);
                $dense[] = $row;
                $id = $row['VariableID'];
                $GLOBALS['symconTestArchiveVariables'][$id]['AggregatedValues'] = array_map(static fn (int $i): array => [
                    'TimeStamp' => 1780000300 - $i * 60, 'Avg' => 10.0 + $i, 'Min' => 0.0, 'Max' => 100.0, 'Duration' => 60
                ], range(0, 89));
            }
        }
        $chart->SetTestProperty('Range', '7d');
        $chart->SetTestProperty('PointBudget', 2000);
        $set($dense);
        $stress = $read();
        assertGatewayGauge(
            count($stress['groups']) === 16 && $stress['truncated'] === true
            && array_sum(array_map(static fn (array $g): int => count($g['points']), $stress['groups'])) === 992,
            'The 16-group two-role model remains bounded to 32 times 62 aggregates before joining.'
        );
        $tileBytes = strlen($chart->GetVisualizationTile());
        $ipsBytes = strlen($chart->GetIPSViewHTML());
        assertGatewayGauge(
            $tileBytes < SYMCON_OUTPUT_BUFFER_LIMIT && $ipsBytes < SYMCON_OUTPUT_BUFFER_LIMIT,
            'The maximum comparison population with UTF-8 labels fits both final outputs.'
        );
        echo 'Scatter groups stress bytes: model=' . strlen($chart->GetScatterData()) . ', Tile=' . $tileBytes . ', IPSView=' . $ipsBytes . ".\n";
        $savedLastArchive = $GLOBALS['symconTestArchiveVariables'][99646];
        unset($GLOBALS['symconTestArchiveVariables'][99646]);
        $chart->ApplyChanges();
        assertGatewayGauge($chart->GetTestStatus() === 204, 'A late or hidden archive error must not become an apparently valid partial comparison.');
        $GLOBALS['symconTestArchiveVariables'][99646] = $savedLastArchive;
        $large = array_map(static fn (array $s): array => array_replace($s, ['Label' => str_repeat('L', 12000)]), $dense);
        $set($large);
        try {
            $read();
            throw new RuntimeException('Oversized model accepted.');
        }
        catch (RuntimeException $exception) {
            assertGatewayGauge(
                str_contains($exception->getMessage(), 'output budget') && $chart->GetTestStatus() === 202,
                'The named model byte guard rejects oversized metadata independently of the point budget.'
            );
        }
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
        $GLOBALS['symconTestVariables'] = $variables;
        $GLOBALS['symconTestArchiveVariables'] = $archives;
    }
})();
