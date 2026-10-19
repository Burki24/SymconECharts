<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsThemeRiver/module.php';

final class TestableConsumptionThemeRiver extends EChartsThemeRiver
{
    public int $now = 1792233296;

    protected function LoadConfigurationForm(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function VisualizationAsset(string $filename): string
    {
        return (string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/visualization/' . $filename);
    }

    protected function CurrentTimestamp(): int
    {
        return $this->now;
    }
}

// Synthetic archive data deliberately separates counter totals from raw/current readings.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedArchives = $GLOBALS['symconTestArchiveVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $savedZone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $requests = [];
    $gateway = new EChartsGateway();
    $newGateway = static function () use (&$gateway): void
    {
        $gateway = new EChartsGateway();
        $gateway->Create();
        $gateway->ApplyChanges();
    };
    $newGateway();
    $responder = static function (string $json) use (&$gateway, &$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (($request['Operation'] ?? '') === 'archive.read') {
            $requests[] = $request['Payload'];
        }
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    $now = strtotime('2026-10-17 12:34:56');
    $lastHour = strtotime('2026-10-17 11:00:00');
    $sources = [];
    for ($index = 0; $index < 16; ++$index) {
        $id = 98600 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => $now,
            'Value'                                            => 12345.0 + $index, 'Name' => 'Same synthetic energy', 'Presentation' => ['SUFFIX' => ' kWh', 'DIGITS' => 2]];
        $aggregates = [];
        for ($point = 0; $point < 720; ++$point) {
            $aggregates[] = ['TimeStamp' => $lastHour - $point * 3600, 'Avg' => $index * 2.0 + $point / 4,
                'Min'                    => -999.0, 'Max' => 99999.0, 'Duration' => 3600];
        }
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 1,
            'LoggedValues'                                               => [['TimeStamp' => $lastHour, 'Value' => 2.0], ['TimeStamp' => $lastHour - 3600, 'Value' => 999999.0]],
            'AggregatedValues'                                           => $aggregates];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'InitiallyVisible' => $index < 4, 'Color' => -1];
    }
    $GLOBALS['symconTestVariables'][98500] = [...$GLOBALS['symconTestVariables'][98600], 'Name' => 'Synthetic standard archive'];
    $GLOBALS['symconTestArchiveVariables'][98500] = [...$GLOBALS['symconTestArchiveVariables'][98600], 'AggregationType' => 0];
    $configure = static function (array $rows, string $metric = 'consumption') use ($now): TestableConsumptionThemeRiver
    {
        $module = new TestableConsumptionThemeRiver();
        $module->Create();
        $module->now = $now;
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Metric', $metric);
        $module->SetTestProperty('Range', '24h');
        return $module;
    };
    $decode = static fn (TestableConsumptionThemeRiver $module): array => json_decode($module->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
    $startup = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'ThemeRiver consumption documents must fit 1 MiB.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'ThemeRiver startup must be accessible.');
        $bootstrap = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $compressed = $bootstrap['options']['compressedStateGzipBase64'] ?? null;
        return is_string($compressed) ? json_decode(gzdecode(base64_decode($compressed, true)), true, 512, JSON_THROW_ON_ERROR) : $bootstrap['state'];
    };

    try {
        $defaults = new TestableConsumptionThemeRiver();
        $defaults->Create();
        assertGatewayGauge(($defaults->GetTestProperties()['Metric'] ?? null) === 'average', 'Existing ThemeRiver configurations must default to standard archive means.');
        $standard = $configure([['VariableID' => 98500]], 'average');
        $saved = $standard->GetTestProperties();
        $standard->ApplyChanges();
        $standard->ApplyChanges();
        $standardModel = $decode($standard);
        assertGatewayGauge(
            $standard->GetTestStatus() === IS_ACTIVE && $standard->GetTestProperties() === $saved
            && $standardModel['themeRiver']['metric'] === 'average' && $standardModel['themeRiver']['rows'][23]['values'] === [0.0],
            'The average default must preserve source/design properties and existing mean semantics.'
        );

        $module = $configure($sources);
        $module->SetTestProperty('EnableBandSelection', true);
        $module->SetTestProperty('EnableIPSView', true);
        $module->SetTestProperty('BandOpacityPercent', 37);
        $saved = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        $requests = [];
        $model = $decode($module);
        $river = $model['themeRiver'];
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $module->GetTestProperties() === $saved
            && $river['metric'] === 'consumption' && count($river['sources']) === 16
            && count(array_unique(array_column($river['sources'], 'id'))) === 16
            && array_column($river['sources'], 'label') === array_fill(0, 16, 'Same synthetic energy')
            && count(array_filter(array_column($river['sources'], 'initiallyVisible'))) === 4,
            'Counter consumption must support sixteen independently identified sources with unchanged optional selection.'
        );
        assertGatewayGauge(
            count($river['rows']) === 24 && $river['missingIntervals'] === 0
            && $river['rows'][23]['values'][0] === 0.0 && $river['rows'][23]['values'][15] === 30.0
            && $river['rows'][0]['values'][0] === 5.75 && $river['unit'] === 'kWh',
            'Consumption must use exact Symcon Avg delta totals, retain real zeros and units, and ignore raw resets/current readings.'
        );
        assertGatewayGauge(
            count($requests) === 16 && array_unique(array_column($requests, 'Mode')) === ['aggregated']
            && array_unique(array_column($requests, 'Reducer')) === ['sum']
            && array_unique(array_column($requests, 'AggregationLevel')) === [0]
            && array_sum(array_column($requests, 'Limit')) === 16000
            && $model['range']['loadBudget'] === 16000 && $model['range']['loadedAggregates'] === 16 * 24
            && $module->GetTestReferences() === range(98600, 98615),
            'Consumption must request explicit counter sums and preserve all load, interval and hidden-reference limits.'
        );
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            assertGatewayGauge($startup($html)['chart'] === $model, 'Both output adapters must preserve the consumption metric and all original delta totals exactly.');
        }
        $module->SetTestProperty('Range', '30d');
        $maximum = $decode($module);
        assertGatewayGauge(
            count($maximum['themeRiver']['rows']) === 720
            && $maximum['range']['loadedAggregates'] === 16 * 720 && $maximum['range']['loadBudget'] === 16000
            && count($maximum['themeRiver']['rows'][0]['values']) === 16
            && $maximum['themeRiver']['style']['bandOpacityPercent'] === 37,
            'Counter consumption must retain the 720-interval maximum for every visible and hidden source without changing design.'
        );
        unset($maximum);
        $module->SetTestProperty('Range', '24h');

        // Missing hidden sources never become complete by hiding their band.
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder): string
        {
            $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
            if (($response['Operation'] ?? '') === 'archive.read' && ($response['Payload']['VariableID'] ?? null) === 98615) {
                array_splice($response['Payload']['Points'], 7, 1);
            }
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        };
        $gapped = $decode($module)['themeRiver'];
        assertGatewayGauge(
            $gapped['missingIntervals'] === 1 && $gapped['rows'][7]['values'] === null
            && count($gapped['rows'][6]['values']) === 16 && count($gapped['rows'][8]['values']) === 16,
            'A missing hidden counter must leave the entire consumption interval null, without zero filling or partial summation.'
        );
        IPSModuleStrict::$ParentResponder = $responder;

        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $control = findGaugeFormElement($form['elements'], 'Metric');
        assertGatewayGauge($control !== null && array_column($control['options'], 'value') === ['average', 'consumption']
            && ($control['onChange'] ?? '') !== '', 'The data-basis selector must expose average and consumption with live preview.');
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('Metric preview drafts must not read archives.');
        };
        foreach (['average', 'consumption'] as $metric) {
            $module->UpdateBarPreviewFromForm(json_encode(['Metric' => $metric, 'Title' => '<Unsaved>', 'Sources' => $sources], JSON_THROW_ON_ERROR));
            $images = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
            foreach (array_slice($images, -2) as $image) {
                $svg = base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true);
                $document = new DOMDocument();
                assertGatewayGauge(
                    is_string($svg) && $document->loadXML($svg, LIBXML_NONET)
                    && str_contains($svg, 'data-preview-sources="4/16"') && str_contains($svg, '&lt;Unsaved&gt;')
                    && str_contains($svg, 'data-metric="' . $metric . '"')
                    && str_contains($svg, $metric === 'consumption' ? 'Example data: consumption' : 'Example data'),
                    'Both bounded previews must disclose their synthetic metric without reading archives or losing selection.'
                );
            }
        }
        assertGatewayGauge($module->GetTestProperties() === $saved, 'Draft metric changes must never persist configuration.');
        IPSModuleStrict::$ParentResponder = $responder;

        foreach (['type', 'reducer', 'negative', 'truncated', 'oversized'] as $failure) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder, $failure): string
            {
                $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
                if (($response['Operation'] ?? '') === 'archive.read' && ($response['Payload']['VariableID'] ?? null) === 98615) {
                    if ($failure === 'type') {
                        $response['Payload']['ArchiveAggregationType'] = 'standard';
                    } elseif ($failure === 'reducer') {
                        $response['Payload']['EffectiveReducer'] = 'average';
                    } elseif ($failure === 'negative') {
                        $response['Payload']['Points'][0][1] = -1.0;
                    } elseif ($failure === 'truncated') {
                        $response['Payload']['Truncated'] = true;
                    } else {
                        $response['Payload']['Points'] = array_fill(0, 1001, $response['Payload']['Points'][0]);
                    }
                }
                return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
            };
            $exception = null;
            try {
                $module->GetThemeRiverData();
            } catch (Throwable $caught) {
                $exception = $caught;
            }
            assertGatewayGauge(
                $exception !== null && $module->GetTestStatus() === 204,
                'Invalid hidden counter responses must fail without returning a partial consumption model: ' . $failure
            );
        }
        IPSModuleStrict::$ParentResponder = $responder;
        $module->RefreshArchive();
        assertGatewayGauge(
            $module->GetTestStatus() === IS_ACTIVE && $decode($module)['themeRiver']['rows'] === $river['rows'],
            'Valid counter requests must recover after invalid responses without modifying configuration.'
        );

        // Type compatibility is enforced by the real Gateway, not inferred from units.
        foreach ([[$sources[0]], [['VariableID' => 98500]]] as $rows) {
            $mismatch = $configure($rows, $rows[0]['VariableID'] === 98500 ? 'consumption' : 'average');
            $mismatch->ApplyChanges();
            $error = $startup($mismatch->GetVisualizationTile());
            assertGatewayGauge(
                $mismatch->GetTestStatus() === 204 && $error['status'] === 'error'
                && $error['chart'] === null && str_contains(strtolower($error['error']), $mismatch->GetTestProperties()['Metric'] === 'consumption' ? 'counter' : 'standard'),
                'Selecting a metric incompatible with the real archive type must show its required archive type explicitly.'
            );
            $mismatch->SetTestProperty('Metric', $mismatch->GetTestProperties()['Metric'] === 'consumption' ? 'average' : 'consumption');
            $mismatch->RefreshArchive();
            assertGatewayGauge($mismatch->GetTestStatus() === IS_ACTIVE, 'Choosing a compatible metric must recover without rebuilding sources.');
        }

        $module->SetTestProperty('Metric', 'sum');
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === 202, 'Unsupported metric values must fail configuration validation.');
        $module->SetTestProperty('Metric', 'consumption');
        $module->RefreshArchive();

        // A completed local day across the autumn change is 25 hours, not 24.
        $day = $configure([$sources[0]]);
        $day->now = strtotime('2026-10-26 12:34:56');
        $day->SetTestProperty('Aggregation', 'day');
        $GLOBALS['symconTestArchiveVariables'][98600]['AggregatedValues'] = [
            ['TimeStamp' => strtotime('2026-10-26 00:00:00'), 'Avg' => 999.0],
            ['TimeStamp' => strtotime('2026-10-25 00:00:00'), 'Avg' => 12.5],
        ];
        $newGateway();
        $requests = [];
        $day->ApplyChanges();
        $daily = $decode($day);
        assertGatewayGauge(
            count($daily['themeRiver']['rows']) === 1 && $daily['themeRiver']['rows'][0]['values'] === [12.5]
            && $daily['range']['endTimestamp'] - $daily['range']['startTimestamp'] + 1 === 25 * 3600
            && array_unique(array_column($requests, 'AggregationLevel')) === [1],
            'Daily counter consumption must use the completed 25-hour local archive total and exclude the running day.'
        );
        $day->SetTestProperty('Range', 'custom');
        $day->SetTestProperty('CustomRangeValue', 1);
        $day->SetTestProperty('CustomRangeUnit', 'day');
        $requests = [];
        $custom = $decode($day);
        assertGatewayGauge(
            $custom['themeRiver']['rows'] === [] && $custom['range']['loadedAggregates'] === 0 && $requests === [],
            'A custom elapsed 24-hour duration must not invent the 25-hour completed day or issue empty-window archive requests.'
        );

        $locale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
        foreach ([$control['caption'], ...array_column($control['options'], 'caption')] as $caption) {
            assertGatewayGauge(isset($locale[$caption]), 'New metric choices must have German translations: ' . $caption);
        }
        echo "ThemeRiver counter consumption integration verified.\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
        date_default_timezone_set($savedZone);
    }
})();
