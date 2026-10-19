<?php

declare(strict_types=1);

(static function (): void
{
    $chart = new TestableEChartsBoxplot();
    $chart->Create();
    $chart->SetTestProperty('Sources', '[{"VariableID":88200}]');
    $chart->SetTestProperty('EnableIPSView', true);
    $chart->SetTestProperty('Range', 'custom');
    $chart->SetTestProperty('CustomRangeValue', 90);
    $chart->SetTestProperty('CustomRangeUnit', 'minute');
    $chart->ApplyChanges();
    assertGatewayGauge($chart->GetTestStatus() === IS_ACTIVE, 'Boxplot must accept a custom 90-minute archive range.');

    $parent = IPSModuleStrict::$ParentResponder;
    $queries = [];
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent, &$queries): string
    {
        $request = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.read') {
            $queries[] = $request['Payload'];
        }
        return $parent($json);
    };
    try {
        $end = strtotime('2026-10-16 12:00:00') - 1;
        foreach ([[90, 'minute', 5400], [12, 'hour', 43200], [3, 'day', 259200], [2, 'week', 1209600],
            [1, 'minute', 60], [1000, 'week', 604800000]] as [$value, $unit, $duration]) {
            $chart->SetTestProperty('CustomRangeValue', $value);
            $chart->SetTestProperty('CustomRangeUnit', $unit);
            $queries = [];
            $model = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $model['range']['key'] === 'custom'
                && $model['range']['startTimestamp'] === $end - $duration + 1 && $model['range']['endTimestamp'] === $end
                && count($queries) === 1 && $queries[0]['StartTimestamp'] === $end - $duration + 1
                && $queries[0]['EndTimestamp'] === $end && $queries[0]['AggregationLevel'] === 0 && $queries[0]['Limit'] === 1000,
                'Custom Boxplot ranges must retain exact requested durations and completed-hour archive requests: ' . $unit
            );
            if ($unit === 'minute' && $value === 90) {
                assertGatewayGauge(
                    $model['boxplot']['data'][0]['count'] === 1
                    && $model['boxplot']['data'][0]['statistics'] === [8.0, 8.0, 8.0, 8.0, 8.0],
                    'A 90-minute Boxplot range must omit both the ongoing hour and the hourly sample before its exact start.'
                );
            }
            if ($unit === 'minute' && $value === 1) {
                assertGatewayGauge(
                    $model['boxplot']['data'][0]['statistics'] === null,
                    'A custom Boxplot range without a completed hourly timestamp must stay empty, not synthesize minute samples.'
                );
            }
            $rangeJson = json_encode($model['range'], JSON_THROW_ON_ERROR);
            assertGatewayGauge(str_contains($chart->GetVisualizationTile(), $rangeJson)
                && str_contains($chart->GetIPSViewHTML(), $rangeJson), 'Boxplot outputs must use the same custom archive window.');
        }
        foreach ([[-1, 'day'], [0, 'day'], [1001, 'day'], [1, 'invalid']] as [$value, $unit]) {
            $chart->SetTestProperty('CustomRangeValue', $value);
            $chart->SetTestProperty('CustomRangeUnit', $unit);
            $queries = [];
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202 && $queries === [], 'Invalid custom Boxplot ranges must fail before archive access.');
        }
        foreach (['24h' => 86400, '7d' => 604800, '30d' => 2592000] as $range => $duration) {
            $chart->SetTestProperty('Range', $range);
            $chart->ApplyChanges();
            $model = json_decode($chart->GetBoxplotData(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                $chart->GetTestStatus() === IS_ACTIVE
                && $model['range']['startTimestamp'] === $end - $duration + 1 && $model['range']['endTimestamp'] === $end,
                'Preset Boxplot ranges must remain unchanged and ignore inactive custom values: ' . $range
            );
        }
        foreach (['1h', 'today', 'invalid'] as $range) {
            $chart->SetTestProperty('Range', $range);
            $chart->ApplyChanges();
            assertGatewayGauge($chart->GetTestStatus() === 202, 'Boxplot must not accidentally allow additional range modes.');
        }
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    $chart->SetTestProperty('Range', 'custom');
    $chart->SetTestProperty('CustomRangeValue', 1000);
    $chart->SetTestProperty('CustomRangeUnit', 'week');
    IPSModuleStrict::$ParentResponder = static function (string $json) use ($parent): string
    {
        $response = json_decode($parent($json), true, 512, JSON_THROW_ON_ERROR);
        if (($response['Operation'] ?? '') === 'archive.read') {
            $response['Payload']['Truncated'] = true;
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    };
    try {
        $chart->RefreshArchive();
        assertGatewayGauge(
            $chart->GetTestStatus() === 204
            && str_contains($chart->GetVisualizationTile(), 'Too many Boxplot archive samples. Choose a shorter time range.'),
            'Long custom Boxplot ranges must retain visible budget rejection, never statistics from truncated samples.'
        );
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    $chart->SetTestProperty('Range', 'custom');
    $chart->SetTestProperty('CustomRangeValue', 90);
    $chart->SetTestProperty('CustomRangeUnit', 'minute');
    $chart->ApplyChanges();
    $properties = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($chart);
    IPSModuleStrict::$ParentResponder = static function (string $json): string
    { throw new RuntimeException('Custom-range form drafts must not access archives.'); };
    try {
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $range = findGaugeFormElement($form['elements'], 'Range');
        assertGatewayGauge(
            in_array('custom', array_column($range['options'], 'value'), true)
            && str_contains($range['onChange'], 'ECBX_UpdateTimeRangeForm($id, $Range);')
            && str_contains($range['onChange'], 'ECBX_UpdateBarPreviewFromForm')
            && str_contains($range['onChange'], "'CustomRangeValue' => $" . 'CustomRangeValue')
            && str_contains($range['onChange'], "'CustomRangeUnit' => $" . 'CustomRangeUnit'),
            'Custom Boxplot range changes must update visibility and retain the complete unsaved preview draft.'
        );
        foreach (['CustomRangeValue', 'CustomRangeUnit'] as $name) {
            $field = findGaugeFormElement($form['elements'], $name);
            assertGatewayGauge($field['visible'] === true && str_contains($field['onChange'], 'ECBX_UpdateBarPreviewFromForm'), 'Custom range controls must be visible and preview-wired: ' . $name);
        }
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], 'CustomRangeValue')['minimum'] === 1
            && findGaugeFormElement($form['elements'], 'CustomRangeValue')['maximum'] === 1000
            && array_column(findGaugeFormElement($form['elements'], 'CustomRangeUnit')['options'], 'value') === ['minute', 'hour', 'day', 'week'],
            'Boxplot custom range controls must offer the validated 1–1000 minute/hour/day/week contract.'
        );
        $chart->UpdateTimeRangeForm('7d');
        $hidden = array_slice($chart->GetTestFormUpdates(), -2);
        $chart->UpdateTimeRangeForm('custom');
        $shown = array_slice($chart->GetTestFormUpdates(), -2);
        assertGatewayGauge(array_column($hidden, 'Value') === [false, false]
            && array_column($shown, 'Value') === [true, true], 'Changing Boxplot range must immediately toggle both custom controls.');
        $chart->UpdateBarPreviewFromForm('{"Range":"custom","CustomRangeValue":12,"CustomRangeUnit":"hour"}');
        assertGatewayGauge((new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($chart) === $properties
            && $chart->GetTestStatus() === IS_ACTIVE, 'Range form actions must preserve properties, status and archives.');
        $chart->SetTestProperty('Range', '7d');
        $presetForm = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(findGaugeFormElement($presetForm['elements'], 'CustomRangeValue')['visible'] === false
            && findGaugeFormElement($presetForm['elements'], 'CustomRangeUnit')['visible'] === false, 'Initial preset forms must hide custom range controls.');
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
})();
