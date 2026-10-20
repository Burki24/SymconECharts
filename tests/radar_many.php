<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsRadar/module.php';

// Included by gateway_gauges.php; names, identities and measurements are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $savedArchiveQueries = $GLOBALS['symconTestArchiveQueryCount'];
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
    $axes = [];
    $sources = [];
    $names = [];
    for ($axis = 0; $axis < 10; ++$axis) {
        $axes[] = ['Key' => 'axis' . $axis, 'Label' => 'Synthetic axis ' . $axis, 'Minimum' => -20,
            'Maximum'    => 80, 'Unit' => $axis === 0 ? '°C' : ($axis === 1 ? '%' : 'u' . $axis), 'Decimals' => $axis % 7];
    }
    for ($group = 0; $group < 17; ++$group) {
        $names[] = 'Room ' . str_pad((string) ($group + 1), 2, '0', STR_PAD_LEFT);
        for ($axis = 0; $axis < 10; ++$axis) {
            $id = 98000 + $group * 10 + $axis;
            $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => 1792233296 + $id,
                'Value'                                            => $group + $axis / 10, 'Name' => 'Same synthetic measurement'];
            $sources[] = ['Group' => $names[$group], 'Axis' => 'axis' . $axis, 'VariableID' => $id];
        }
    }
    $configure = static function (array $configuredAxes, array $rows): EChartsRadar
    {
        $module = new EChartsRadar();
        $module->Create();
        $module->SetTestProperty('Axes', json_encode($configuredAxes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        return $module;
    };
    $decode = static fn (EChartsRadar $module): array => json_decode($module->GetRadarData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'Sixteen-group Radar documents must fit the output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'Radar startup must remain accessible.');
        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['state'];
    };
    $previews = static function (EChartsRadar $module): array
    {
        $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($images) >= 2, 'Both Radar draft previews must remain available.');
        return array_map(static function (array $image): DOMDocument
        {
            $document = new DOMDocument();
            $svg = (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
            assertGatewayGauge($document->loadXML($svg, LIBXML_NONET), 'Radar preview must remain valid SVG.');
            return $document;
        }, array_slice($images, -2));
    };
    try {
        $rows = array_slice($sources, 0, 160);
        $module = $configure($axes, $rows);
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Radar must accept sixteen complete comparison groups on ten explicit axes.');
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        assertGatewayGauge($model['schemaVersion'] === 1 && $model['family'] === 'radar'
            && count($model['radar']['axes']) === 10 && count($model['radar']['data']) === 16
            && $properties['EnableGroupSelection'] === false && $properties['GroupVisibility'] === '[]'
            && $model['radar']['enableGroupSelection'] === false
            && array_column($model['radar']['data'], 'initiallyVisible') === array_fill(0, 16, true)
            && $module->GetTestProperties() === $properties, 'Selection metadata must be additive; repeated ApplyChanges must not rewrite Radar sources, axes or design.');
        foreach ($model['radar']['data'] as $group => $item) {
            assertGatewayGauge($item['id'] === 'group-' . hash('sha256', $names[$group]) && $item['name'] === $names[$group]
                && $item['variableIDs'] === range(98000 + $group * 10, 98009 + $group * 10)
                && $item['timestamps'] === array_map(static fn (int $id): int => 1792233296 + $id, $item['variableIDs']), 'Every Radar group must preserve its existing name-based ID and distinct current variable identities.');
            foreach ($item['values'] as $axis => $value) {
                assertGatewayGauge(
                    $value === (float) ($group + $axis / 10) && abs($item['percentValues'][$axis] - ($value + 20)) < 1e-10,
                    'The explicit unchanged axis range must determine geometry while preserving each original value.'
                );
            }
        }
        assertGatewayGauge($requests === range(98000, 98159) && $module->GetTestReferences() === range(98000, 98159), 'All 160 assignments must be read through the real Gateway and referenced.');
        foreach (range(98000, 98159) as $id) {
            assertGatewayGauge(in_array(['SenderID' => $id, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'All Radar variables must remain subscribed by ID: ' . $id);
        }
        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both Radar adapters must retain all sixteen groups and ten original axes.');
        }
        assertGatewayGauge(is_string($module->GetTestVariableValue('IPSViewRadar')) && $module->GetTestVariableValue('IPSViewRadar') !== '', 'The persistent Radar IPSView document must remain nonempty.');

        $visibility = array_map(static fn (string $name): array => ['Group' => $name, 'InitiallyVisible' => false], array_slice($names, 0, 16));
        $visibility[15]['InitiallyVisible'] = true;
        $module->SetTestProperty('EnableGroupSelection', true);
        $module->SetTestProperty('GroupVisibility', json_encode($visibility, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        $requests = [];
        $selected = $decode($module);
        assertGatewayGauge($selected['radar']['enableGroupSelection'] === true && count($selected['radar']['data']) === 16
            && array_column($selected['radar']['data'], 'initiallyVisible') === [...array_fill(0, 15, false), true]
            && $requests === range(98000, 98159), 'Initial visibility must never filter complete model validation or Gateway reads.');
        $GLOBALS['symconTestVariables'][98140]['Value'] = 1000.0;
        $module->MessageSink(1792233400, 98140, VM_UPDATE, []);
        $updates = $module->GetTestVisualizationUpdates();
        $updated = json_decode(end($updates), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge((float) $updated['chart']['radar']['data'][14]['values'][0] === 1000.0
            && (float) $updated['chart']['radar']['data'][14]['percentValues'][0] === 100.0
            && $updated['chart']['radar']['data'][14]['initiallyVisible'] === false
            && $updated['chart']['radar']['clippedCount'] === 1, 'A hidden group must publish fresh raw values and full-model clipping warnings without changing scales.');
        $GLOBALS['symconTestVariables'][98140]['Value'] = 14.0;
        $GLOBALS['symconTestVariables'][98150]['Name'] = 'Renamed measurement';
        assertGatewayGauge($decode($module)['radar']['data'][15]['variableIDs'][0] === 98150
            && $decode($module)['radar']['data'][15]['id'] === 'group-' . hash('sha256', $names[15]), 'Renaming a variable must not change its assignment or the explicit group identity.');

        $legacy = $configure($axes, array_slice($rows, 0, 80));
        $legacyProperties = $legacy->GetTestProperties();
        $legacy->ApplyChanges();
        $legacy->ApplyChanges();
        assertGatewayGauge($legacy->GetTestProperties() === $legacyProperties && count($decode($legacy)['radar']['data']) === 8, 'Existing eight-group configurations must remain unchanged and valid.');
        $threeAxes = array_slice($axes, 0, 3);
        $threeRows = array_slice($rows, 0, 3);
        $minimum = $configure($threeAxes, $threeRows);
        $minimum->ApplyChanges();
        assertGatewayGauge($minimum->GetTestStatus() === IS_ACTIVE && count($decode($minimum)['radar']['data']) === 1, 'The existing minimum of one complete group and three axes must remain valid.');
        $shared = $configure($axes, array_map(static fn (array $row): array => [...$row, 'VariableID' => 98000], $rows));
        $shared->ApplyChanges();
        $requests = [];
        assertGatewayGauge(count($decode($shared)['radar']['data']) === 16 && $shared->GetTestReferences() === [98000]
            && $requests === [98000], 'Repeated use of one variable across all assignments must remain allowed and be read and subscribed only once.');

        $colors = array_map(static fn (string $name, int $index): array => ['Group' => $name, 'Color' => 0x110000 + $index], array_slice($names, 0, 16), range(0, 15));
        $ipsColors = array_map(static fn (array $row): array => [...$row, 'Color' => $row['Color'] + 0x1100], $colors);
        $module->SetTestProperty('GroupColors', json_encode($colors, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('IPSViewGroupColors', json_encode($ipsColors, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewShape', 'circle');
        $module->SetTestProperty('IPSViewAxisColor', 0x123456);
        $module->ApplyChanges();
        $tile = $startup($module->GetVisualizationTile())['chart'];
        $ipsView = $startup($module->GetIPSViewHTML())['chart'];
        assertGatewayGauge($tile['radar']['data'] === $ipsView['radar']['data'] && $tile['radar']['axes'] === $ipsView['radar']['axes']
            && count($tile['radar']['style']['groupColors']) === 16 && count($ipsView['radar']['style']['groupColors']) === 16
            && $tile['radar']['style']['shape'] === 'polygon' && $ipsView['radar']['style']['shape'] === 'circle'
            && $tile['radar']['style']['groupColors'][$names[15]] === '#11000F'
            && $ipsView['radar']['style']['groupColors'][$names[15]] === '#11110F', 'Both designers must support sixteen separate group colors without modifying the common axis or selection model.');
        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(str_contains(findGaugeFormElement($form['elements'], 'EnableGroupSelection')['onChange'] ?? '', 'UpdateBarPreviewFromForm')
            && str_contains(findGaugeFormElement($form['elements'], 'GroupVisibility')['onEdit'] ?? '', 'UpdateBarPreviewFromForm'), 'Selection and visibility changes must refresh live draft previews.');
        foreach (['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'] as $callback) {
            $script = findGaugeFormElement($form['elements'], 'GroupVisibility')[$callback] ?? '';
            assertGatewayGauge(str_contains($script, 'UpdateBarPreviewFromForm') && str_contains($script, 'foreach ($GroupVisibility as'), 'Group visibility must use structured List callbacks: ' . $callback);
        }
        $callbackValues = [...$module->GetTestProperties(), 'Title' => '<Unsaved callback title>', 'ShowLegend' => false,
            'AxisLineWidth'                                         => 0, 'EnableGroupSelection' => true, 'IPSViewUseTileDesign' => false,
            'IPSViewShape'                                          => 'circle', 'IPSViewShowLegend' => true, 'IPSViewAxisLineWidth' => 7];
        $callbackLists = ['Axes' => $axes, 'Sources' => $rows, 'GroupColors' => $colors,
            'IPSViewGroupColors' => $ipsColors, 'GroupVisibility' => $visibility];
        foreach ($callbackLists as $name => $list) {
            $callbackValues[$name] = new ArrayIterator(array_combine(range(10, count($list) + 9), $list));
        }
        $callbackScript = findGaugeFormElement($form['elements'], 'GroupVisibility')['onEdit'];
        $captured = (static function (array $formValues, string $script, int $instanceID): array
        {
            extract($formValues, EXTR_SKIP);
            $id = $instanceID;
            $received = [];
            $capture = static function (int $target, string $payload) use (&$received): void
            {
                $received = [$target, json_decode($payload, true, 512, JSON_THROW_ON_ERROR)];
            };
            // Execute only our generated local form callback, replacing its external action boundary.
            eval(str_replace('ECRA_UpdateBarPreviewFromForm(', '$capture(', $script));
            return $received;
        })($callbackValues, $callbackScript, $module->InstanceID);
        assertGatewayGauge($captured[0] === $module->InstanceID, 'Preview callbacks must retain the target instance identity.');
        foreach ($callbackLists as $name => $list) {
            assertGatewayGauge($captured[1][$name] === $list, 'Preview callbacks must copy iterable list values into a JSON list: ' . $name);
        }
        foreach (['Title', 'ShowLegend', 'AxisLineWidth', 'EnableGroupSelection', 'IPSViewUseTileDesign',
            'IPSViewShape', 'IPSViewShowLegend', 'IPSViewAxisLineWidth', 'AnimationEnabled', 'IPSViewAnimationEnabled'] as $name) {
            assertGatewayGauge(array_key_exists($name, $captured[1]) && $captured[1][$name] === $callbackValues[$name], 'Compact preview callbacks must retain false, zero and independent unsaved scalar values: ' . $name);
        }
        foreach (['GroupColors', 'IPSViewGroupColors', 'GroupVisibility'] as $fieldName) {
            $field = findGaugeFormElement($form['elements'], $fieldName);
            assertGatewayGauge(in_array($names[15], array_column($field['form'][0]['options'], 'value'), true), 'Group selectors must include groups beyond the former 80-assignment slice: ' . $fieldName);
        }

        $beforeDraft = $module->GetTestProperties();
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Radar draft previews must not request Gateway data.');
        };
        $GLOBALS['symconTestVariables'][98140]['Value'] = 1000.0;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableGroupSelection' => false,
            'Title'                                              => '<Unsaved>', 'GroupColors' => [], 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            $polygons = (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-group]');
            assertGatewayGauge($document->documentElement->getAttribute('data-group-count') === '16'
                && $document->documentElement->getAttribute('data-selected-count') === '16'
                && $document->documentElement->getAttribute('data-rendered-count') === '8' && $polygons->length === 8
                && $polygons->item(0)->getAttribute('data-group') === $names[0] && $polygons->item(7)->getAttribute('data-group') === $names[7]
                && str_contains($document->saveXML(), '&lt;Unsaved&gt;')
                && str_contains($document->saveXML(), 'Some values exceed their axis range and are visually limited.'), 'Large Radar previews must disclose the sample, retain configured order and include clipping warnings from off-sample groups.');
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'EnableGroupSelection' => true,
            'GroupVisibility'                                    => $visibility, 'GroupColors' => [], 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            $polygons = (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-group]');
            $palette = SymconECharts\EChartsBarPreview::Palette('auto');
            assertGatewayGauge($document->documentElement->getAttribute('data-selected-count') === '1'
                && $document->documentElement->getAttribute('data-rendered-count') === '1' && $polygons->length === 1
                && $polygons->item(0)->getAttribute('data-group') === $names[15]
                && $polygons->item(0)->getAttribute('fill') === $palette['seriesColors'][15 % count($palette['seriesColors'])], 'Initial selection must precede preview sampling, and late groups must keep their full-model palette position.');
        }
        $none = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $visibility);
        $module->UpdateBarPreviewFromForm(json_encode(['EnableGroupSelection' => true, 'GroupVisibility' => $none], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge($document->documentElement->getAttribute('data-selected-count') === '0'
                && (new DOMXPath($document))->query('//*[local-name()="polygon" and @data-group]')->length === 0
                && str_contains($document->saveXML(), 'No groups selected'), 'An empty group selection must show an honest explicit empty state.');
        }
        $GLOBALS['symconTestVariables'][98140]['Value'] = 'not numeric';
        $module->UpdateBarPreviewFromForm(json_encode(['EnableGroupSelection' => true, 'GroupVisibility' => $none], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge(str_contains($document->saveXML(), 'Radar values must be finite numbers with timestamps.')
                && !str_contains($document->saveXML(), 'No groups selected'), 'Draft previews must validate hidden current values before selection instead of disguising errors as an empty view.');
        }
        $GLOBALS['symconTestVariables'][98140]['Value'] = 1000.0;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => array_slice($rows, 0, 80), 'EnableGroupSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previews($module) as $document) {
            assertGatewayGauge((new DOMXPath($document))->query('//*[local-name()="polygon" and @data-group]')->length === 8, 'Old eight-group previews with disabled selection must retain all polygons.');
        }
        assertGatewayGauge($module->GetTestProperties() === $beforeDraft && $module->GetTestStatus() === IS_ACTIVE
            && $GLOBALS['symconTestArchiveQueryCount'] === $savedArchiveQueries, 'Unsaved Radar sources, visibility, title and design must not persist, alter status or request archives.');
        IPSModuleStrict::$ParentResponder = $responder;
        $GLOBALS['symconTestVariables'][98140]['Value'] = 14.0;

        foreach ([[], [['Group'                 => '  ' . $names[0] . '  ', 'InitiallyVisible' => null]],
            [['Group' => $names[0]]], [['Group' => 'Stale group', 'InitiallyVisible' => false]]] as $validVisibility) {
            $valid = $configure($threeAxes, $threeRows);
            $valid->SetTestProperty('GroupVisibility', json_encode($validVisibility, JSON_THROW_ON_ERROR));
            $valid->ApplyChanges();
            assertGatewayGauge(
                $valid->GetTestStatus() === IS_ACTIVE && $decode($valid)['radar']['data'][0]['initiallyVisible'] === true,
                'Missing/null visibility defaults and valid stale overrides must not hide a configured group.'
            );
        }
        $disabled = $configure($threeAxes, $threeRows);
        $disabled->SetTestProperty('GroupVisibility', json_encode([['Group' => $names[0], 'InitiallyVisible' => false]], JSON_THROW_ON_ERROR));
        $disabled->ApplyChanges();
        assertGatewayGauge($decode($disabled)['radar']['enableGroupSelection'] === false
            && $decode($disabled)['radar']['data'][0]['initiallyVisible'] === false, 'Disabled selection must retain false metadata in the complete backend model for the view to ignore.');
        foreach (['not JSON', '{}', '[null]', json_encode([['Group' => '', 'InitiallyVisible' => false]], JSON_THROW_ON_ERROR),
            json_encode([['Group' => str_repeat('G', 81)]], JSON_THROW_ON_ERROR),
            json_encode([['Group' => 'same'], ['Group' => ' same ']], JSON_THROW_ON_ERROR),
            json_encode([['Group' => 'Stale group', 'InitiallyVisible' => 'false']], JSON_THROW_ON_ERROR),
            json_encode([['Group' => $names[0], 'InitiallyVisible' => 0]], JSON_THROW_ON_ERROR),
            json_encode(array_map(static fn (int $n): array => ['Group' => 'Stale ' . $n], range(1, 17)), JSON_THROW_ON_ERROR)] as $invalidVisibility) {
            $invalid = $configure($threeAxes, $threeRows);
            $invalid->SetTestProperty('GroupVisibility', $invalidVisibility);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Invalid group visibility must fail independently of whether selection is enabled.');
        }
        $seventeenShortGroups = array_values(array_filter($sources, static fn (array $row): bool => in_array($row['Axis'], ['axis0', 'axis1', 'axis2'], true)));
        $invalidConfigurations = [[$axes, $sources], [$threeAxes, $seventeenShortGroups], [array_slice($axes, 0, 2), array_slice($rows, 0, 2)],
            [[...$axes, [...$axes[0], 'Key' => 'axis10']], $rows], [$axes, array_slice($rows, 0, 159)],
            [$axes, [...array_slice($rows, 0, 159), $rows[0]]], [$axes, [...array_slice($rows, 0, 159), [...$rows[159], 'Axis' => 'unknown']]]];
        foreach ($invalidConfigurations as [$invalidAxes, $invalidRows]) {
            $invalid = $configure($invalidAxes, $invalidRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Seventeen groups, two/eleven axes, incomplete groups and duplicate/unknown axis assignments must remain invalid.');
        }
        foreach (['GroupColors', 'IPSViewGroupColors'] as $property) {
            $invalid = $configure($axes, $rows);
            $invalid->SetTestProperty('IPSViewUseTileDesign', false);
            $invalid->SetTestProperty($property, json_encode([...$colors, ['Group' => 'Stale', 'Color' => -1]], JSON_THROW_ON_ERROR));
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 202, 'Seventeen group-color rows must fail the explicit sixteen-color design bound.');
        }
        $GLOBALS['symconTestVariables'][98140]['VariableType'] = 3;
        $invalid = $configure($axes, $rows);
        $invalid->SetTestProperty('GroupVisibility', json_encode($none, JSON_THROW_ON_ERROR));
        $invalid->ApplyChanges();
        assertGatewayGauge($invalid->GetTestStatus() === 201, 'Hidden nonnumeric variables must remain invalid configuration.');
        $GLOBALS['symconTestVariables'][98140]['VariableType'] = 2;
        foreach ([NAN, INF, -INF, 'not numeric'] as $value) {
            $GLOBALS['symconTestVariables'][98140]['Value'] = $value;
            $failure = null;
            try {
                $module->GetRadarData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge($failure !== null && $module->GetTestStatus() === 204, 'Invalid current values in hidden groups must not silently disappear from Gateway validation.');
        }
        $GLOBALS['symconTestVariables'][98140]['Value'] = -1000.0;
        assertGatewayGauge($decode($module)['radar']['clippedCount'] === 1
            && $decode($module)['radar']['data'][14]['values'][0] === -1000.0
            && $decode($module)['radar']['data'][14]['percentValues'][0] === 0.0, 'Negative values remain allowed and are only visually clipped by the explicit axis.');
        $GLOBALS['symconTestVariables'][98140]['Value'] = 14.0;
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder): string
        {
            $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            if (($request['Payload']['VariableID'] ?? null) === 98140) {
                throw new RuntimeException('Synthetic hidden-source transport failure.');
            }
            return $responder($json);
        };
        try {
            $module->GetRadarData();
            throw new RuntimeException('Expected hidden-source failure.');
        } catch (RuntimeException $exception) {
            assertGatewayGauge($module->GetTestStatus() === 204, 'Hidden sources remain part of transport failure handling.');
        } finally {
            IPSModuleStrict::$ParentResponder = $responder;
        }
        assertGatewayGauge(count($decode($module)['radar']['data']) === 16 && $module->GetTestStatus() === IS_ACTIVE, 'Corrected hidden sources must recover the complete model.');

        $sizeError = 'The Radar model is too large. Reduce the title or group metadata.';
        $huge = $configure($axes, $rows);
        $huge->SetTestProperty('Title', str_repeat('T', 200000));
        $hugeProperties = $huge->GetTestProperties();
        $huge->ApplyChanges();
        $failure = null;
        try {
            $huge->GetRadarData();
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        assertGatewayGauge($failure !== null && $failure->getMessage() === $sizeError && $huge->GetTestStatus() === 202
            && json_decode($huge->GetRadarDiagnostic(), true)['lastError'] === 'MODEL_TOO_LARGE'
            && $huge->GetTestProperties() === $hugeProperties, 'Models beyond the JSON budget must fail explicitly without truncation or configuration writes.');
        foreach ([$huge->GetVisualizationTile(), $huge->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && $state['error'] === $sizeError, 'Oversized Radar models must return bounded actionable errors in both adapters.');
        }
        $escaped = $configure($axes, $rows);
        $escaped->SetTestProperty('Title', str_repeat('<', 100000));
        $escapedProperties = $escaped->GetTestProperties();
        assertGatewayGauge(strlen($escaped->GetRadarData()) < 200000 && count($decode($escaped)['radar']['data']) === 16, 'The HTML escaping guard must be tested from a complete valid Radar model below its JSON budget.');
        foreach ([$escaped->GetVisualizationTile(), $escaped->GetIPSViewHTML()] as $html) {
            $state = $startup($html);
            assertGatewayGauge($state['status'] === 'error' && $state['chart'] === null && $state['error'] === $sizeError, 'Final oversized HTML must return an explicit bounded error instead of truncated groups.');
        }
        assertGatewayGauge($escaped->GetTestStatus() === 202 && $escaped->GetTestProperties() === $escapedProperties, 'The final HTML limit must not modify persistent settings.');
        $escaped->SetTestProperty('Title', 'Recovered Radar');
        assertGatewayGauge($startup($escaped->GetVisualizationTile())['status'] === 'ready' && $escaped->GetTestStatus() === IS_ACTIVE, 'Correcting excessive content must immediately restore normal rendering.');
        $stressAxes = array_map(static fn (array $axis): array => [...$axis, 'Label' => str_repeat('🌡', 80), 'Unit' => str_repeat('℃', 24)], $axes);
        $stressRows = array_map(static fn (array $row): array => [...$row,
            'Group' => str_repeat('🔋', 78) . substr($row['Group'], -2)], $rows);
        $stress = $configure($stressAxes, []);
        $stress->SetTestProperty('Sources', json_encode($stressRows, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $stress->ApplyChanges();
        assertGatewayGauge($stress->GetTestStatus() === IS_ACTIVE, 'Maximum-length UTF-8 Radar configuration must remain active: ' . $stress->GetRadarDiagnostic());
        assertGatewayGauge(count($decode($stress)['radar']['data']) === 16, 'Maximum-length UTF-8 labels must retain all groups.');
        $stressFormBytes = strlen($stress->GetConfigurationForm());
        assertGatewayGauge($stressFormBytes < SYMCON_OUTPUT_BUFFER_LIMIT, 'Maximum-length UTF-8 Radar configuration form must fit its output buffer: ' . $stressFormBytes . ' bytes.');
        foreach ([$stress->GetVisualizationTile(), $stress->GetIPSViewHTML()] as $html) {
            assertGatewayGauge(count($startup($html)['chart']['radar']['data']) === 16, 'Bounded UTF-8 Radar documents must retain the complete maximum model.');
        }
        $module->SetTestProperty('Axes', json_encode($threeAxes, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Sources', json_encode($threeRows, JSON_THROW_ON_ERROR));
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestReferences() === [98000, 98001, 98002]
            && !in_array(['SenderID' => 98159, 'Message' => VM_UPDATE], $module->GetTestMessages(), true), 'Removed Radar assignments must unregister stale references and subscriptions.');
        echo 'Radar sixteen-group bytes: model=' . strlen(json_encode($model, JSON_THROW_ON_ERROR)) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveQueryCount'] = $savedArchiveQueries;
    }
})();
