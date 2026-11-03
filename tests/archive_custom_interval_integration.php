<?php

declare(strict_types=1);

/** Shared public-behavior checks for the two completed-interval families. */
return static function (IPSModuleStrict $chart, string $family, string $intervalField): void
{
    $saved = (new ReflectionProperty(IPSModuleStrict::class, 'properties'))->getValue($chart);
    $method = 'Get' . $family . 'Data';
    $modelKey = lcfirst($family);
    $rowsKey = $family === 'Candlestick' ? 'data' : 'rows';
    try {
        $chart->SetTestProperty('Range', 'custom');
        $chart->SetTestProperty('CustomRangeValue', 2);
        $chart->SetTestProperty('CustomRangeUnit', 'hour');
        $chart->SetTestProperty($intervalField, 'hour');
        $chart->ApplyChanges();
        $model = json_decode($chart->$method(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $model['range']['key'] === 'custom'
            && count($model[$modelKey][$rowsKey]) === 2
            && $model['range']['endTimestamp'] - $model['range']['startTimestamp'] + 1 === 7200,
            $family . ' must resolve a custom duration as completed intervals.'
        );
        assertGatewayGauge(
            strlen($chart->GetVisualizationTile()) < SYMCON_OUTPUT_BUFFER_LIMIT
            && strlen($chart->GetIPSViewHTML()) < SYMCON_OUTPUT_BUFFER_LIMIT,
            $family . ' custom windows must reach both bounded outputs.'
        );
        $count = $GLOBALS['symconTestArchiveQueryCount'];
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['CustomRangeValue', 'CustomRangeUnit'] as $name) {
            $control = findGaugeFormElement($form['elements'], $name);
            assertGatewayGauge(
                $control['visible'] === true && str_contains($control['onChange'], 'UpdateBarPreviewFromForm'),
                $family . ' custom controls must be initially visible and update the preview.'
            );
        }
        assertGatewayGauge(
            str_contains(findGaugeFormElement($form['elements'], 'Range')['onChange'], 'UpdateTimeRangeForm'),
            $family . ' range drafts must immediately update visibility.'
        );
        $chart->UpdateTimeRangeForm('24h');
        $updates = array_slice($chart->GetTestFormUpdates(), -2);
        assertGatewayGauge(
            $updates[0]['Value'] === false && $updates[1]['Value'] === false,
            $family . ' preset drafts must hide custom controls.'
        );
        $chart->UpdateTimeRangeForm('custom');
        $updates = array_slice($chart->GetTestFormUpdates(), -2);
        assertGatewayGauge(
            $updates[0]['Value'] === true && $updates[1]['Value'] === true,
            $family . ' custom drafts must reveal custom controls.'
        );
        $chart->UpdateBarPreviewFromForm('{"Range":"custom","CustomRangeValue":4,"CustomRangeUnit":"week"}');
        assertGatewayGauge(
            $GLOBALS['symconTestArchiveQueryCount'] === $count
            && count(json_decode($chart->$method(), true)[$modelKey][$rowsKey]) === 2,
            $family . ' form drafts must not query or persist archive settings.'
        );

        // Keep the same clock: changing custom arguments must not reuse the old window cache.
        $chart->SetTestProperty('CustomRangeValue', 3);
        assertGatewayGauge(
            count(json_decode($chart->$method(), true)[$modelKey][$rowsKey]) === 3,
            $family . ' data cache must include the resolved custom window.'
        );
        $chart->SetTestProperty('CustomRangeValue', 1);
        $chart->SetTestProperty('CustomRangeUnit', 'minute');
        $chart->SetTestProperty($intervalField, 'day');
        $count = $GLOBALS['symconTestArchiveQueryCount'];
        $chart->ApplyChanges();
        $empty = json_decode($chart->$method(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && $empty[$modelKey][$rowsKey] === []
            && $GLOBALS['symconTestArchiveQueryCount'] === $count
            && str_contains($chart->GetVisualizationTile(), '"status":"ready"')
            && str_contains($chart->GetIPSViewHTML(), '"status":"ready"'),
            $family . ' sub-interval windows must be empty and never read or invent archive values.'
        );
        foreach ([['CustomRangeValue', 0], ['CustomRangeValue', 1001], ['CustomRangeUnit', 'month']] as [$field, $invalid]) {
            $chart->SetTestProperty('CustomRangeValue', 7);
            $chart->SetTestProperty('CustomRangeUnit', 'day');
            $chart->SetTestProperty($field, $invalid);
            $chart->ApplyChanges();
            assertGatewayGauge(
                $chart->GetTestStatus() === 202 && $GLOBALS['symconTestArchiveQueryCount'] === $count,
                $family . ' invalid custom fields must fail before archive reads.'
            );
        }
        $chart->SetTestProperty('CustomRangeValue', 1000);
        $chart->SetTestProperty('CustomRangeUnit', 'week');
        $chart->ApplyChanges();
        assertGatewayGauge(
            $chart->GetTestStatus() === 202 && $GLOBALS['symconTestArchiveQueryCount'] === $count
            && str_contains($chart->GetVisualizationTile(), 'Too many archive intervals. Choose a shorter range or a longer interval.')
            && str_contains($chart->GetIPSViewHTML(), 'Too many archive intervals. Choose a shorter range or a longer interval.'),
            $family . ' interval budget must fail visibly before any archive reads.'
        );
        $chart->SetTestProperty('Range', '24h');
        $chart->SetTestProperty('CustomRangeValue', 0);
        $chart->SetTestProperty('CustomRangeUnit', 'unused');
        $chart->ApplyChanges();
        $preset = json_decode($chart->$method(), true, 512, JSON_THROW_ON_ERROR);
        $form = json_decode($chart->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $chart->GetTestStatus() === IS_ACTIVE && count($preset[$modelKey][$rowsKey]) === 1
            && findGaugeFormElement($form['elements'], 'CustomRangeValue')['visible'] === false,
            $family . ' presets must ignore inactive custom drafts and preserve existing local-day behavior.'
        );
    } finally {
        foreach ($saved as $name => $value) {
            $chart->SetTestProperty($name, $value);
        }
        $chart->ApplyChanges();
    }
};
