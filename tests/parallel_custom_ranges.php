<?php

declare(strict_types=1);

(static function (): void
{
    $module = new TestableEChartsParallel();
    $module->Create();
    $module->SetTestCurrentTimestamp(1780004000);
    $module->SetTestProperty('Sources', json_encode([['VariableID' => 4711], ['VariableID' => 4713]], JSON_THROW_ON_ERROR));
    $module->SetTestProperty('Range', 'custom');
    $module->SetTestProperty('CustomRangeValue', 90);
    $module->SetTestProperty('CustomRangeUnit', 'minute');
    $module->SetTestProperty('EnableIPSView', true);
    $module->ApplyChanges();
    assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Parallel must accept custom rolling archive durations.');
    $data = json_decode($module->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
    $local = (new DateTimeImmutable('@1780004000'))->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $boundary = 1780004000 - (int) $local->format('i') * 60 - (int) $local->format('s');
    assertGatewayGauge(
        $data['range']['key'] === 'custom' && $data['range']['startTimestamp'] === $boundary - 5400
        && $data['range']['endTimestamp'] === $boundary - 1 && $data['range']['aggregationLevel'] === 0,
        'Custom minutes must reach the completed hourly query without reverting to seven days.'
    );
    $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    foreach (['CustomRangeValue', 'CustomRangeUnit'] as $name) {
        assertGatewayGauge(
            findGaugeFormElement($form['elements'], $name)['visible'] === true,
            'Custom Parallel fields must be visible on initial form load.'
        );
        assertGatewayGauge(
            str_contains(findGaugeFormElement($form['elements'], $name)['onChange'], 'UpdateBarPreviewFromForm'),
            'Custom Parallel controls must retain immediate draft-preview actions.'
        );
    }
    assertGatewayGauge(
        str_contains(findGaugeFormElement($form['elements'], 'Range')['onChange'], 'UpdateTimeRangeForm'),
        'Changing range must immediately update custom field visibility.'
    );
    $parent = IPSModuleStrict::$ParentResponder;
    IPSModuleStrict::$ParentResponder = static function (): string
    { throw new RuntimeException('Form changes must not read archives.'); };
    try {
        $module->UpdateTimeRangeForm('24h');
        $updates = array_slice($module->GetTestFormUpdates(), -2);
        assertGatewayGauge(
            $updates[0]['Value'] === false && $updates[1]['Value'] === false,
            'Fixed draft ranges must hide both custom controls without persisting the range.'
        );
        $module->UpdateBarPreviewFromForm('{"Range":"custom","CustomRangeValue":12,"CustomRangeUnit":"hour"}');
    } finally {
        IPSModuleStrict::$ParentResponder = $parent;
    }
    assertGatewayGauge(
        json_decode($module->GetParallelData(), true, 512, JSON_THROW_ON_ERROR)['range'] === $data['range'],
        'Draft custom durations must not overwrite saved settings.'
    );
    foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $output) {
        assertGatewayGauge(
            str_contains($output, '"key":"custom"') && strlen($output) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'Both Parallel outputs must embed the configured custom range within the output budget.'
        );
    }
    foreach ([[0, 'day'], [1001, 'hour'], [1, 'month']] as [$value, $unit]) {
        $module->SetTestProperty('CustomRangeValue', $value);
        $module->SetTestProperty('CustomRangeUnit', $unit);
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 202, 'Invalid active custom durations must be configuration errors.');
    }
    $module->SetTestProperty('Range', '24h');
    $module->ApplyChanges();
    assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Inactive invalid custom fields must not break existing presets.');
    $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertGatewayGauge(
        findGaugeFormElement($form['elements'], 'CustomRangeValue')['visible'] === false,
        'Preset forms must hide inactive custom fields.'
    );
    foreach (['hour', 'day'] as $aggregation) {
        $module->SetTestProperty('Range', 'custom');
        $module->SetTestProperty('CustomRangeValue', 2);
        $module->SetTestProperty('CustomRangeUnit', 'week');
        $module->SetTestProperty('Aggregation', $aggregation);
        $module->ApplyChanges();
        $data = json_decode($module->GetParallelData(), true, 512, JSON_THROW_ON_ERROR);
        assertGatewayGauge(
            $data['range']['endTimestamp'] - $data['range']['startTimestamp'] + 1 === 14 * 86400
            && $data['range']['aggregationLevel'] === ($aggregation === 'hour' ? 0 : 1),
            'Custom weeks preserve elapsed duration and the explicit aggregation level.'
        );
    }
})();
