<?php

declare(strict_types=1);

// Included by gateway_gauges.php; IDs, labels, values and timestamps are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $requests = [];
    $gateway = new EChartsGateway();
    $gateway->Create();
    $gateway->ApplyChanges();
    $responder = static function (string $json) use ($gateway, &$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'current.read') {
            $requests[] = $request['Payload']['VariableID'];
        }
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    $sources = [];
    $values = [];
    for ($index = 0; $index < 101; ++$index) {
        $id = 98800 + $index;
        // Cross zero early; create a large omitted intermediate peak that later cancels.
        $value = match ($index) {
            0       => 100.0, 1 => -150.0, 20 => 10000.0, 21 => -10000.0,
            default => $index % 2 === 0 ? 7.5 : -2.5,
        };
        $values[] = $value;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296,
            'Value'                                            => $value, 'Name' => 'Same synthetic step'];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'UseVariablePresentation' => false,
            'Unit'                 => 'kWh', 'Decimals' => $index === 99 ? 6 : 1];
    }
    $configure = static function (array $rows): EChartsBarWaterfall
    {
        $module = new EChartsBarWaterfall();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsBarWaterfall $module): array => json_decode($module->GetWaterfallData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'One-hundred-source Waterfall documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Waterfall startup must be accessible.');
        $configuration = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $compressed = $configuration['options']['compressedStateGzipBase64'] ?? null;
        return is_string($compressed) ? json_decode(gzdecode(base64_decode($compressed, true)), true, 512, JSON_THROW_ON_ERROR) : $configuration['state'];
    };

    try {
        $rows = array_slice($sources, 0, 100);
        $module = $configure($rows);
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('IncreaseColor', 0x123456);
        $module->SetTestProperty('TotalLabel', 'Complete synthetic total');
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Waterfall must accept a start and ninety-nine signed changes.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        $steps = $model['steps'];
        assertGatewayGauge(
            $module->GetTestProperties() === $properties && count($steps) === 101
            && count(array_unique(array_column($steps, 'id'))) === 101
            && array_column(array_slice($steps, 0, 100), 'label') === array_fill(0, 100, 'Same synthetic step')
            && $model['bar']['unit'] === 'kWh' && $model['bar']['decimals'] === 6,
            'A hundred identically named sources must retain their IDs, complete ordered steps and highest source precision.'
        );
        assertGatewayGauge(
            $requests === range(98800, 98899) && $module->GetTestReferences() === range(98800, 98899),
            'The actual Gateway must read every source and register all one hundred references.'
        );
        foreach (range(98800, 98899) as $id) {
            assertGatewayGauge(
                in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true),
                'Every Waterfall source must remain subscribed by ID: ' . $id
            );
        }
        assertGatewayGauge(
            $steps[0]['before'] === 0.0 && $steps[0]['after'] === 100.0 && $steps[0]['kind'] === 'start'
            && $steps[1]['before'] === 100.0 && $steps[1]['change'] === -150.0 && $steps[1]['after'] === -50.0
            && $steps[20]['before'] === -5.0 && $steps[20]['after'] === 9995.0
            && $steps[21]['before'] === 9995.0 && $steps[21]['after'] === -5.0
            && $steps[99]['after'] === 190.0 && $steps[100]['kind'] === 'total'
            && $steps[100]['change'] === 190.0 && $steps[100]['before'] === 0.0 && $steps[100]['after'] === 190.0,
            'Signed changes, zero crossings, omitted intermediate peaks and the true all-source total must remain exact.'
        );
        foreach (array_slice($steps, 1, 99) as $index => $step) {
            assertGatewayGauge($step['before'] === $steps[$index]['after'] && $step['change'] === $values[$index + 1]
                && $step['kind'] === 'change', 'Every signed transition must connect to its original predecessor without truncation.');
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both output adapters must retain all one hundred changes and the full computed total.');
        }
        assertGatewayGauge(
            is_string($module->GetTestVariableValue('IPSViewBarWaterfall')) && $module->GetTestVariableValue('IPSViewBarWaterfall') !== '',
            'The persistent IPSView document must remain nonempty.'
        );
        $GLOBALS['symconTestVariables'][98899]['Value'] += 1.0;
        $module->MessageSink(1792233297, 98899, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            count($updated['chart']['steps']) === 101 && (float) $updated['chart']['steps'][100]['after'] === 191.0,
            'An update to the last configured source must recalculate and publish the complete waterfall.'
        );
        $GLOBALS['symconTestVariables'][98899]['Value'] = $values[99];

        $old = $configure(array_slice($rows, 0, 16));
        $oldProperties = $old->GetTestProperties();
        $old->ApplyChanges();
        $old->ApplyChanges();
        assertGatewayGauge(
            $old->GetTestProperties() === $oldProperties && count($decode($old)['steps']) === 17,
            'Existing sixteen-source configurations must remain byte-identical across repeated ApplyChanges.'
        );
        $minimum = $configure(array_slice($rows, 0, 2));
        $minimum->ApplyChanges();
        assertGatewayGauge(
            $minimum->GetTestStatus() === IS_ACTIVE && $decode($minimum)['steps'][2]['after'] === -50.0,
            'The established minimum of a start plus one signed change must remain accepted.'
        );

        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $sourceControl = findGaugeFormElement($form['elements'], 'Sources');
        assertGatewayGauge(
            $sourceControl['changeOrder'] === true && ($sourceControl['onAdd'] ?? '') !== '',
            'The ordered one-hundred-source editor must retain immediate preview callbacks.'
        );
        $draftRows = $rows;
        foreach ($draftRows as $index => &$row) {
            $row['Label'] = 'S' . $index;
        }
        unset($row);
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Waterfall preview must not request Gateway data.');
        };
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $draftRows, 'Title' => '<Unsaved>', 'TotalLabel' => '<All sources>', 'ShowValues' => true], JSON_THROW_ON_ERROR));
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both bounded Waterfall previews must remain available.');
        foreach (array_slice($images, -2) as $image) {
            $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            $document = new DOMDocument();
            assertGatewayGauge(is_string($svg) && $document->loadXML($svg, LIBXML_NONET), 'The many-source preview must remain valid SVG.');
            $xpath = new DOMXPath($document);
            $root = $document->documentElement;
            assertGatewayGauge(
                str_contains($svg, 'data-preview-sources="5/100"')
                && (float) $root->getAttribute('data-waterfall-total') === 190.0
                && (float) $root->getAttribute('data-waterfall-min') === -50.0
                && (float) $root->getAttribute('data-waterfall-max') === 9995.0
                && str_contains($svg, '190.000000 kWh') && str_contains($svg, '&lt;Unsaved&gt;')
                && str_contains($svg, '&lt;All sources&gt;') && str_contains($svg, 'data-omitted-steps="true"') && str_contains($svg, '…'),
                'The preview must disclose five of one hundred sources, omitted steps and the true complete total/range/unit/precision.'
            );
            assertGatewayGauge(
                $xpath->query('//*[local-name()="rect"]')->length <= 8,
                'The preview must remain a bounded sample instead of squeezing all one hundred bars.'
            );
        }
        assertGatewayGauge($module->GetTestProperties() === $properties, 'Draft previews must not persist source ordering, titles, precision or design changes.');
        IPSModuleStrict::$ParentResponder = $responder;

        foreach ([$sources, array_slice($rows, 0, 1), [...array_slice($rows, 0, 99), $rows[0]],
            [...array_slice($rows, 0, 99), [...$rows[99], 'Unit' => '%']]] as $invalidRows) {
            $invalid = $configure($invalidRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'One/101 sources, duplicate IDs and mixed units must fail validation.');
        }
        $GLOBALS['symconTestVariables'][98899]['VariableType'] = 0;
        $invalid = $configure($rows);
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'A nonnumeric source must remain rejected even at the last configured position.');
        $GLOBALS['symconTestVariables'][98899]['VariableType'] = 2;
        foreach ([NAN, INF, -INF] as $invalidValue) {
            $GLOBALS['symconTestVariables'][98899]['Value'] = $invalidValue;
            $failure = null;
            try {
                $module->GetWaterfallData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge(
                $failure !== null && $module->GetTestStatus() === 204,
                'Nonfinite native Kernel values must fail without returning a partial waterfall.'
            );
        }
        $GLOBALS['symconTestVariables'][98899]['Value'] = $values[99];
        $module->RetryTransient();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $decode($module)['steps'][100]['after'] === 190.0,
            'A finite source retry must restore the original all-source waterfall.'
        );
        $overflow = $configure(array_slice($rows, 0, 2));
        $GLOBALS['symconTestVariables'][98800]['Value'] = 1e308;
        $GLOBALS['symconTestVariables'][98801]['Value'] = 1e308;
        $failure = null;
        try {
            $overflow->GetWaterfallData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $overflow->GetTestStatus() === 204
            && str_contains($failure->getMessage(), 'not finite'), 'A nonfinite cumulative total must fail even when individual Kernel values are finite.');
        $GLOBALS['symconTestVariables'][98800]['Value'] = $values[0];
        $GLOBALS['symconTestVariables'][98801]['Value'] = $values[1];
        $overflow->RetryTransient();
        assertGatewayGauge($overflow->GetTestStatus() === IS_ACTIVE, 'A corrected cumulative overflow must recover on retry.');

        $GLOBALS['symconTestVariables'][98899]['Name'] = 'Renamed final synthetic step';
        $renamed = $decode($module);
        assertGatewayGauge(
            array_column($renamed['steps'], 'id') === array_column($steps, 'id')
            && $renamed['steps'][99]['label'] === 'Renamed final synthetic step' && $renamed['steps'][100]['after'] === 190.0,
            'Renaming the last source must update only its displayed label, not identity, position or total.'
        );

        $longRows = array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<>&測', 30), 'Decimals' => 6], $rows);
        $stress = $configure($longRows);
        $stress->SetTestProperty('EnableIPSView', true);
        $stress->SetTestProperty('Title', str_repeat('<&測>', 30));
        $stress->ApplyChanges();
        $stressModel = $decode($stress);
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(
                $startup($html)['chart'] === $stressModel && count($stressModel['steps']) === 101,
                'Long UTF-8 labels and precision metadata must retain all steps in both bounded output documents.'
            );
        }
        assertGatewayGauge(
            strlen($stress->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'The hundred-source form including both previews must remain below the output limit.'
        );
        $huge = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('X', 3000)], $rows));
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetWaterfallData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $huge->GetTestStatus() === 202 && str_contains(strtolower($failure->getMessage()), 'large')
            && $huge->GetTestProperties() === $hugeProperties, 'Oversized metadata must fail explicitly without truncating labels or rewriting sources.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge(
                $state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'),
                'Oversized Waterfall models must produce bounded actionable error documents in both adapters.'
            );
        }
        // Valid model bytes can expand beyond the HTML buffer after safe JSON escaping.
        $escaped = $configure(array_map(static fn (array $row): array => [...$row, 'Label' => str_repeat('<', 1700)], $rows));
        $escapedProperties = $escaped->GetTestProperties();
        $escapedModelJSON = $escaped->GetWaterfallData();
        assertGatewayGauge(
            strlen($escapedModelJSON) < 200000 && count(json_decode($escapedModelJSON, true, 512, JSON_THROW_ON_ERROR)['steps']) === 101,
            'The final HTML guard test must begin with a complete valid model below the model-byte limit.'
        );
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge(
                $state['status'] === 'error' && $state['chart'] === null && str_contains($state['error'], 'Reduce source labels'),
                'HTML escaping beyond 1 MiB must yield bounded actionable errors instead of an oversized partial document.'
            );
        }
        assertGatewayGauge(
            $escaped->GetTestStatus() === 202 && $escaped->GetTestProperties() === $escapedProperties,
            'The final HTML byte guard must not truncate labels or persist changes to recover.'
        );
        $module->SetTestProperty('Sources', json_encode(array_slice($rows, 0, 2), JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge(
            $module->GetTestReferences() === [98800, 98801]
            && !in_array(['SenderID' => 98899, 'Message' => VM_UPDATE], $module->GetTestMessages(), true),
            'Removing sources must unregister stale references and subscriptions without retaining the former last source.'
        );
        echo 'Waterfall hundred-source bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR))
            . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
    }
})();
