<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/EChartsThemeRiver/module.php';

final class TestableManyThemeRiver extends EChartsThemeRiver
{
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
        return strtotime('2026-10-17 12:34:56');
    }
}

// All IDs, labels and archive records in this focused integration are synthetic.
(static function (): void
{
    $savedVariables = $GLOBALS['symconTestVariables'];
    $savedArchives = $GLOBALS['symconTestArchiveVariables'];
    $savedResponder = IPSModuleStrict::$ParentResponder;
    $savedZone = date_default_timezone_get();
    date_default_timezone_set('Europe/Berlin');
    $requests = [];
    $gateway = new EChartsGateway();
    $gateway->Create();
    $gateway->ApplyChanges();
    $responder = static function (string $json) use ($gateway, &$requests): string
    {
        $request = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        // Do not retain full visualization push states in the request log.
        if (($request['Operation'] ?? '') === 'archive.read') {
            $requests[] = $request['Payload'];
        }
        return $gateway->ForwardData($json);
    };
    IPSModuleStrict::$ParentResponder = $responder;
    $sources = [];
    $last = strtotime('2026-10-17 11:00:00');
    for ($index = 0; $index < 17; ++$index) {
        $id = 97500 + $index;
        $GLOBALS['symconTestVariables'][$id] = ['VariableType' => 2, 'VariableUpdated' => $last,
            'Value'                                            => -999.0, 'Name' => 'Same synthetic power', 'Presentation' => ['SUFFIX' => ' W', 'DIGITS' => 2]];
        $aggregates = [];
        for ($point = 0; $point < 720; ++$point) {
            $aggregates[] = ['TimeStamp' => $last - $point * 3600,
                'Avg'                    => $point === 0 ? (float) $index : 1.2345678901234567e100 + $index * 1e90,
                'Min'                    => -999.0, 'Max' => 999.0, 'Duration' => 3600];
        }
        $GLOBALS['symconTestArchiveVariables'][$id] = ['AggregationType' => 0,
            'LoggedValues'                                               => [], 'AggregatedValues' => $aggregates];
        $sources[] = ['VariableID' => $id, 'Label' => '', 'Color' => -1];
    }
    $configure = static function (array $rows): TestableManyThemeRiver
    {
        $module = new TestableManyThemeRiver();
        $module->Create();
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('Range', '30d');
        return $module;
    };
    $decode = static fn (TestableManyThemeRiver $module): array => json_decode($module->GetThemeRiverData(), true, 512, JSON_THROW_ON_ERROR);
    $bootstrap = static function (string $html): array
    {
        assertGatewayGauge(strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT, 'ThemeRiver documents must fit the 1 MiB output buffer.');
        assertGatewayGauge(preg_match('/window\\.SYMC_VISUALIZATION = ([^\\r\\n]+);<\\/script>/', $html, $match) === 1, 'ThemeRiver startup must remain accessible.');
        $configuration = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $compressed = $configuration['options']['compressedStateGzipBase64'] ?? null;
        assertGatewayGauge(is_string($compressed) && $configuration['state'] === null, 'Large ThemeRiver models must use the shared compressed startup.');
        $stateJSON = gzdecode(base64_decode($compressed, true));
        assertGatewayGauge(is_string($stateJSON), 'Compressed ThemeRiver startup must decode.');
        return json_decode($stateJSON, true, 512, JSON_THROW_ON_ERROR);
    };
    $previewSVGs = static function (TestableManyThemeRiver $module): array
    {
        $updates = array_values(array_filter($module->GetTestFormUpdates(), static fn (array $update): bool => in_array($update['Field'], ['BarPreview', 'IPSViewBarPreview'], true) && $update['Parameter'] === 'image'));
        assertGatewayGauge(count($updates) >= 2, 'Both ThemeRiver preview images must be available.');
        return array_map(static fn (array $image): string => (string) base64_decode(substr($image['Value'], strlen('data:image/svg+xml;base64,')), true), array_slice($updates, -2));
    };

    try {
        $rows = array_slice($sources, 0, 16);
        $module = $configure($rows);
        $module->ApplyChanges();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'ThemeRiver must accept sixteen numeric standard archive sources.');
        $old = $configure(array_slice($sources, 0, 8));
        $properties = $old->GetTestProperties();
        $old->ApplyChanges();
        $old->ApplyChanges();
        $oldModel = $decode($old);
        assertGatewayGauge(
            $old->GetTestProperties() === $properties && $properties['EnableBandSelection'] === false
            && $oldModel['themeRiver']['enableBandSelection'] === false
            && array_column($oldModel['themeRiver']['sources'], 'initiallyVisible') === array_fill(0, 8, true),
            'Old eight-source configurations must remain unchanged with selection disabled and every band initially visible.'
        );

        foreach ($rows as $index => &$row) {
            $row['InitiallyVisible'] = $index < 4;
        }
        unset($row);
        $module->SetTestProperty('Sources', json_encode($rows, JSON_THROW_ON_ERROR));
        $module->SetTestProperty('EnableBandSelection', true);

        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder): string
        {
            $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
            if (($response['Operation'] ?? '') === 'archive.read') {
                foreach ($response['Payload']['Points'] as &$point) {
                    $point[1] = 0.0;
                }
                unset($point);
            }
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        };
        $zero = $decode($module)['themeRiver'];
        assertGatewayGauge(
            $zero['missingIntervals'] === 0 && count($zero['rows']) === 720
            && $zero['rows'][0]['values'] === array_fill(0, 16, 0.0),
            'Real all-zero intervals must remain present and distinct from missing archive intervals.'
        );
        IPSModuleStrict::$ParentResponder = $responder;
        $module->SetTestProperty('EnableIPSView', true);
        $properties = $module->GetTestProperties();
        $module->ApplyChanges();
        $module->ApplyChanges();
        $requests = [];
        $modelJSON = $module->GetThemeRiverData();
        $model = json_decode($modelJSON, true, 512, JSON_THROW_ON_ERROR);
        $river = $model['themeRiver'];
        assertGatewayGauge(
            $module->GetTestProperties() === $properties && $module->GetTestReferences() === range(97500, 97515),
            'Repeated Apply must retain saved bytes and references for every source, including hidden bands.'
        );
        assertGatewayGauge(
            count($river['sources']) === 16 && count(array_unique(array_column($river['sources'], 'id'))) === 16
            && array_column($river['sources'], 'label') === array_fill(0, 16, 'Same synthetic power')
            && $river['enableBandSelection'] === true && count(array_filter(array_column($river['sources'], 'initiallyVisible'))) === 4,
            'Sixteen identical display names must retain distinct ID-based band identities and independent defaults.'
        );
        assertGatewayGauge(
            count($river['rows']) === 720 && $river['missingIntervals'] === 0
            && count($river['rows'][719]['values']) === 16 && $river['rows'][719]['values'][0] === 0.0
            && $river['rows'][719]['values'][15] === 15.0,
            'Every configured source must contribute its original archived means to all 720 completed intervals.'
        );
        assertGatewayGauge(
            count($requests) === 16 && array_sum(array_column($requests, 'Limit')) === 16000
            && max(array_column($requests, 'Limit')) === 1000
            && array_unique(array_column($requests, 'Mode')) === ['aggregated']
            && array_unique(array_column($requests, 'Reducer')) === ['average'],
            'All sixteen configured bands must share the 16000-aggregate request budget with the unchanged 1000 per-source cap.'
        );
        assertGatewayGauge(
            $model['range']['loadBudget'] === 16000 && $model['range']['loadedAggregates'] === 16 * 720,
            'The model must disclose its shared load budget and count every loaded aggregate, including hidden bands.'
        );

        $bytes = [];
        foreach ([$module->GetVisualizationTile(), $module->GetIPSViewHTML()] as $html) {
            $bytes[] = strlen($html);
            $restored = $bootstrap($html)['chart'];
            assertGatewayGauge($restored === $model, 'Tile and IPSView must retain every descriptor, hidden value, timestamp and floating-point mean exactly.');
        }
        assertGatewayGauge(
            is_string($module->GetTestVariableValue('IPSViewThemeRiver')) && $module->GetTestVariableValue('IPSViewThemeRiver') !== '',
            'Enabled IPSView output must publish a nonempty document.'
        );

        $module->SetTestProperty('EnableBandSelection', false);
        $disabled = $decode($module);
        assertGatewayGauge(
            $disabled['themeRiver']['rows'] === $river['rows'] && $disabled['themeRiver']['sources'] === $river['sources']
            && $disabled['themeRiver']['enableBandSelection'] === false,
            'Disabling band selection must not rewrite visibility flags, source order or archive data.'
        );
        $module->SetTestProperty('EnableBandSelection', true);

        // A missing interval on a hidden source remains a full-model gap.
        IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder): string
        {
            $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
            if (($response['Operation'] ?? '') === 'archive.read' && ($response['Payload']['VariableID'] ?? 0) === 97515) {
                array_splice($response['Payload']['Points'], 300, 1);
            }
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        };
        $gapped = $decode($module)['themeRiver'];
        assertGatewayGauge(
            $gapped['missingIntervals'] === 1 && $gapped['rows'][300]['values'] === null
            && count($gapped['rows'][299]['values']) === 16 && count($gapped['rows'][301]['values']) === 16,
            'A hidden missing band must leave the whole interval null without zero filling or changing neighboring runs.'
        );
        IPSModuleStrict::$ParentResponder = $responder;

        $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $selection = findGaugeFormElement($form['elements'], 'EnableBandSelection');
        $sourceForm = findGaugeFormElement($form['elements'], 'Sources');
        $initialColumns = array_values(array_filter($sourceForm['columns'], static fn (array $column): bool => ($column['name'] ?? '') === 'InitiallyVisible'));
        assertGatewayGauge(
            $selection !== null && ($selection['onChange'] ?? '') !== ''
            && count($initialColumns) === 1 && $initialColumns[0]['add'] === true,
            'The form must expose live optional selection and a default-true source visibility checkbox.'
        );
        assertGatewayGauge(
            strlen($module->GetConfigurationForm()) < SYMCON_OUTPUT_BUFFER_LIMIT,
            'The sixteen-source form and both bounded previews must fit the output buffer.'
        );
        IPSModuleStrict::$ParentResponder = static function (string $json): string
        {
            throw new RuntimeException('ThemeRiver previews must never request archive data.');
        };
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $rows, 'Title' => '<Unsaved>'], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            $document = new DOMDocument();
            assertGatewayGauge(
                $document->loadXML($svg, LIBXML_NONET) && str_contains($svg, 'data-preview-sources="4/16"')
                && substr_count($svg, 'data-band=') === 8 && str_contains($svg, '&lt;Unsaved&gt;'),
                'Both previews must show the selected bounded sample and disclose four of sixteen configured sources.'
            );
        }
        $allHidden = array_map(static fn (array $row): array => [...$row, 'InitiallyVisible' => false], $rows);
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $allHidden], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            assertGatewayGauge(
                str_contains($svg, 'data-preview-sources="0/16"') && !str_contains($svg, 'data-band='),
                'A fully hidden preview must show no dummy band and disclose zero of sixteen.'
            );
        }
        $late = $allHidden;
        $late[15]['InitiallyVisible'] = true;
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $late], JSON_THROW_ON_ERROR));
        $palette = SymconECharts\EChartsBarPreview::Palette($properties['EChartsTheme']);
        $originalColor = $palette['seriesColors'][15 % count($palette['seriesColors'])];
        foreach ($previewSVGs($module) as $svg) {
            assertGatewayGauge(
                str_contains($svg, 'data-preview-sources="1/16"') && substr_count($svg, 'data-band=') === 2
                && str_contains($svg, 'fill="' . $originalColor . '"'),
                'A late visible source must keep its original palette color rather than the first filtered color.'
            );
        }
        $module->UpdateBarPreviewFromForm(json_encode(['Sources' => $allHidden, 'EnableBandSelection' => false], JSON_THROW_ON_ERROR));
        foreach ($previewSVGs($module) as $svg) {
            assertGatewayGauge(
                str_contains($svg, 'data-preview-sources="8/16"') && substr_count($svg, 'data-band=') === 16,
                'With optional selection disabled the bounded first eight bands must render regardless of stored initial flags.'
            );
        }
        assertGatewayGauge($module->GetTestProperties() === $properties, 'Every preview draft must leave saved configuration unchanged.');
        IPSModuleStrict::$ParentResponder = $responder;

        $module->SetTestProperty('Sources', json_encode($allHidden, JSON_THROW_ON_ERROR));
        $hidden = $decode($module)['themeRiver'];
        assertGatewayGauge(
            $hidden['rows'] === $river['rows'] && count($hidden['sources']) === 16
            && array_filter(array_column($hidden['sources'], 'initiallyVisible')) === [],
            'Hiding every initial band must retain all archive rows without reallocating or dropping sources.'
        );
        $module->SetTestProperty('Sources', $properties['Sources']);

        foreach ([$sources, [...array_slice($sources, 0, 15), $sources[0]], [...array_slice($sources, 0, 15), [...$sources[15], 'InitiallyVisible' => 'false']]] as $badRows) {
            $invalid = $configure($badRows);
            $invalid->ApplyChanges();
            assertGatewayGauge($invalid->GetTestStatus() === 201, 'Seventeen sources, duplicate IDs and nonboolean visibility must be rejected.');
        }
        $GLOBALS['symconTestVariables'][97515]['Name'] = 'Renamed synthetic source';
        $renamed = $decode($module)['themeRiver']['sources'];
        assertGatewayGauge(
            array_column($renamed, 'id') === array_column($river['sources'], 'id') && $renamed[15]['label'] === 'Renamed synthetic source',
            'Renaming a hidden variable must update its display label without replacing its ID identity.'
        );

        foreach (['oversized', 'negative', 'counter'] as $failureMode) {
            IPSModuleStrict::$ParentResponder = static function (string $json) use ($responder, $failureMode): string
            {
                $response = json_decode($responder($json), true, 512, JSON_THROW_ON_ERROR);
                if (($response['Operation'] ?? '') === 'archive.read'
                    && ($failureMode === 'oversized' || ($response['Payload']['VariableID'] ?? 0) === 97515)) {
                    if ($failureMode === 'oversized') {
                        $response['Payload']['Points'] = array_fill(0, 1001, $response['Payload']['Points'][0]);
                    } elseif ($failureMode === 'negative') {
                        $response['Payload']['Points'][0][1] = -0.1;
                    } else {
                        $response['Payload']['ArchiveAggregationType'] = 'counter';
                    }
                }
                return json_encode($response, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
            };
            $failure = null;
            try {
                $module->GetThemeRiverData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge(
                $failure !== null && $module->GetTestStatus() === 204,
                'Unsafe archive responses must fail explicitly without returning a partial ThemeRiver model: ' . $failureMode
            );
            if ($failureMode === 'oversized') {
                assertGatewayGauge(
                    str_contains(strtolower($failure->getMessage()), 'budget'),
                    'An oversized untrusted response must expose the aggregate-budget failure explicitly.'
                );
            }
        }
        IPSModuleStrict::$ParentResponder = $responder;
        $module->RefreshArchive();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'A valid retry must recover after invalid archive data.');

        // Exercise the final document guard through the stable output-rendering seam.
        $outputGuard = new class() extends EChartsThemeRiver {
            public int $renderCount = 0;

            protected function RenderVisualizationHTMLPage(bool $ipsView, array $configuration = []): string
            {
                ++$this->renderCount;
                if ($this->renderCount % 2 === 1) {
                    return str_repeat('x', SYMCON_OUTPUT_BUFFER_LIMIT + 1);
                }
                return json_encode(['state' => $configuration['state'],
                    'compressed'            => $configuration['options']['compressedStateGzipBase64']], JSON_THROW_ON_ERROR);
            }
        };
        $outputGuard->Create();
        $outputGuard->SetTestProperty('Sources', json_encode([$sources[0]], JSON_THROW_ON_ERROR));
        $guardProperties = $outputGuard->GetTestProperties();
        foreach ([$outputGuard->GetVisualizationTile(), $outputGuard->GetIPSViewHTML()] as $html) {
            $guard = json_decode($html, true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                strlen($html) < SYMCON_OUTPUT_BUFFER_LIMIT && $guard['state']['status'] === 'error'
                && $guard['state']['chart'] === null && str_contains($guard['state']['error'], 'shorter range')
                && $guard['compressed'] === null,
                'Both oversized HTML outputs must be replaced by bounded, actionable resource-error documents.'
            );
        }
        assertGatewayGauge(
            $outputGuard->renderCount === 4 && $outputGuard->GetTestStatus() === 202
            && $outputGuard->GetTestProperties() === $guardProperties,
            'The HTML guard must rerender once per adapter without rewriting saved properties.'
        );

        // The model byte budget is independent of source and aggregate counts.
        $presentations = [];
        $oversizeUnit = ' ' . str_repeat('SyntheticUnit', 5000);
        try {
            for ($index = 0; $index < 16; ++$index) {
                $id = 97500 + $index;
                $presentations[$id] = $GLOBALS['symconTestVariables'][$id]['Presentation'];
                $GLOBALS['symconTestVariables'][$id]['Presentation']['SUFFIX'] = $oversizeUnit;
            }
            $failure = null;
            try {
                $module->GetThemeRiverData();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            assertGatewayGauge(
                $failure !== null && str_contains($failure->getMessage(), 'model is too large')
                && $module->GetTestStatus() === 202,
                'Models exceeding 900000 bytes must explicitly fail without silently removing source metadata.'
            );
            $errorState = json_decode($module->GetThemeRiverDiagnostic(), true, 512, JSON_THROW_ON_ERROR);
            assertGatewayGauge(
                strlen(json_encode($errorState, JSON_THROW_ON_ERROR)) < SYMCON_OUTPUT_BUFFER_LIMIT,
                'Resource diagnostics must remain bounded for oversized metadata.'
            );
        } finally {
            foreach ($presentations as $id => $presentation) {
                $GLOBALS['symconTestVariables'][$id]['Presentation'] = $presentation;
            }
        }
        $module->RefreshArchive();
        assertGatewayGauge($module->GetTestStatus() === IS_ACTIVE, 'Corrected source metadata must recover from the model-size error.');

        $locale = json_decode((string) file_get_contents(dirname(__DIR__) . '/EChartsThemeRiver/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
        foreach ([$selection['caption'], $initialColumns[0]['caption']] as $caption) {
            assertGatewayGauge(isset($locale[$caption]), 'New ThemeRiver selection captions must have German translations: ' . $caption);
        }
        echo 'ThemeRiver stress bytes: 16-by-720 model=' . strlen($modelJSON) . ', tile=' . $bytes[0] . ', IPSView=' . $bytes[1] . ".\n";
    } finally {
        IPSModuleStrict::$ParentResponder = $savedResponder;
        $GLOBALS['symconTestVariables'] = $savedVariables;
        $GLOBALS['symconTestArchiveVariables'] = $savedArchives;
        date_default_timezone_set($savedZone);
    }
})();
