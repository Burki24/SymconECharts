<?php

declare(strict_types=1);

/** Symcon passes List controls as iterators in the form callback. */
function designCopyInputs(array $items, array $properties): array
{
    $inputs = [];
    foreach ($items as $item) {
        $name = $item['name'] ?? '';
        if ($name !== '' && $item['type'] === 'List') {
            $rows = $item['values'] ?? json_decode($properties[$name] ?? '[]', true, 64, JSON_THROW_ON_ERROR);
            $inputs[$name] = new ArrayIterator($rows);
        } elseif ($name !== '' && array_key_exists($name, $properties)) {
            $inputs[$name] = $properties[$name];
        }
        if (isset($item['items'])) {
            $inputs += designCopyInputs($item['items'], $properties);
        }
    }
    return $inputs;
}

/** Run the delivered onClick script. Bind only the kernel's generated module function wrappers. */
function clickDesignCopy(IPSModuleStrict $instance, string $prefix, string $script, array $inputs): void
{
    $script = preg_replace('/\b' . preg_quote($prefix, '/') . '_(\w+)\(\$id(?:,\s*)?/', '\$instance->$1(', $script);
    extract($inputs, EXTR_SKIP);
    eval($script);
}

$families = [];
foreach (glob(dirname(__DIR__) . '/ECharts*/visualization/index.html') as $template) {
    $directory = dirname($template, 2);
    require_once $directory . '/module.php';
    $family = basename($directory);
    $instance = new $family();
    $instance->Create();
    $saved = $instance->GetTestProperties();
    $form = json_decode($instance->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    $button = findGaugeFormElement($form['elements'], 'CopyTileDesignToIPSViewButton');
    assertGatewayGauge(isset($button['onClick']), "$family must expose a wired copy button.");

    $inputs = designCopyInputs($form['elements'], $saved);
    $inputs['IPSViewUseTileDesign'] = false;
    $inputs['IPSViewAdaptToBackground'] = false;
    $inputs['ChartBackgroundColor'] = 0x174C62;
    $inputs['IPSViewChartBackgroundColor'] = 0xD90413;
    $groupColors = [['Group' => 'Group A', 'Color' => 0], ['Group' => 'Group B', 'Color' => 0xCC7744]];
    if ($family === 'EChartsBarCategory') {
        $inputs['BarFillMode'] = 'gradient';
        $inputs['AnimationEnabled'] = false;
    }
    if ($family === 'EChartsGaugeTacho') {
        $inputs['CustomPointerSVG'] = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 6 6"><path d="M3 0L6 6H0Z"/></svg>');
    }
    if ($family === 'EChartsGaugeWind') {
        $inputs['CompassPreset'] = '01_portolan';
        $inputs['CompassSvg'] = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M5 1L8 9H2Z" fill="#559933"/></svg>');
    }
    if ($family === 'EChartsTreemap') {
        $inputs['GroupColors'] = new ArrayIterator($groupColors);
        $inputs['Title'] = 'Unsaved tree title';
    }
    $before = count($instance->GetTestFormUpdates());
    $prefix = json_decode(file_get_contents($directory . '/module.json'), true, 32, JSON_THROW_ON_ERROR)['prefix'];
    clickDesignCopy($instance, $prefix, $button['onClick'], $inputs);
    $edits = [];
    $images = [];
    foreach (array_slice($instance->GetTestFormUpdates(), $before) as $update) {
        if (in_array($update['Parameter'], ['value', 'values'], true)) {
            $edits[$update['Field']] = $update['Value'];
        } elseif ($update['Parameter'] === 'image') {
            $images[$update['Field']] = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
        }
    }
    assertGatewayGauge($instance->GetTestProperties() === $saved, "$family copy must leave active configuration intact.");
    assertGatewayGauge(
        ($edits['IPSViewUseTileDesign'] ?? null) === false
        && ($edits['IPSViewChartBackgroundColor'] ?? null) === 0x174C62,
        "$family must stage the unsaved Tile color and independent mode."
    );
    foreach (array_keys($edits) as $name) {
        assertGatewayGauge(
            str_starts_with($name, 'IPSView')
            && !in_array($name, ['IPSViewAdaptToBackground', 'IPSViewBackgroundColor', 'IPSViewBackgroundOpacityPercent', 'IPSViewRange'], true),
            "$family copy must preserve the rest of the form ($name)."
        );
    }
    assertGatewayGauge(
        count(array_filter(array_keys($images), static fn (string $name): bool => str_starts_with($name, 'IPSView'))) > 0,
        "$family copy must refresh the independent preview immediately."
    );
    if ($family === 'EChartsBarCategory') {
        $updates = array_slice($instance->GetTestFormUpdates(), $before);
        $visible = array_column(array_filter($updates, static fn (array $row): bool => $row['Parameter'] === 'visible'), 'Value', 'Field');
        assertGatewayGauge(
            $visible['IPSViewLowColor'] === true && $visible['IPSViewHighColor'] === true
            && $visible['IPSViewBarSVG'] === false && $visible['IPSViewAnimationDuration'] === false,
            'Copy must immediately show gradient controls and hide unused SVG and animation controls.'
        );

        $beforeInvalid = $instance->GetTestFormUpdates();
        try {
            $instance->CopyTileDesignDraftToIPSView(['ChartBackgroundColor' => 0, 'Range' => '24h']);
            throw new RuntimeException('A draft mixing a design color and a time setting must be rejected.');
        } catch (InvalidArgumentException $exception) {
            assertGatewayGauge(
                $instance->GetTestFormUpdates() === $beforeInvalid && $instance->GetTestProperties() === $saved,
                'Reject the entire invalid copy before changing even the first valid field.'
            );
        }

        $beforeScript = count($instance->GetTestFormUpdates());
        assertGatewayGauge(
            $saved['IPSViewUseTileDesign'] === true && $button['enabled'] === false,
            'The script-entry test must start with a saved inherited design and disabled independent controls.'
        );
        $instance->CopyTileDesignToIPSView();
        $updates = array_slice($instance->GetTestFormUpdates(), $beforeScript);
        $enabled = array_column(array_filter($updates, static fn (array $row): bool => $row['Parameter'] === 'enabled'), 'Value', 'Field');
        assertGatewayGauge(
            $enabled['IPSViewChartBackgroundColor'] === true && $enabled['CopyTileDesignToIPSViewButton'] === true
            && $instance->GetTestProperties() === $saved,
            'The script entry point must enable editing in the inherited form without persisting the switch.'
        );
    }
    if ($family === 'EChartsGaugeTacho') {
        assertGatewayGauge(
            ($edits['IPSViewCustomPointerSVG'] ?? null) === $inputs['CustomPointerSVG'],
            'Copy the selected SVG contents into the independent file field.'
        );
    }
    if ($family === 'EChartsGaugeWind') {
        $rose = \eCharts\EChartsSvgImage::ImportCompass(file_get_contents($directory . '/roses/01_portolan.svg'));
        assertGatewayGauge(
            ($edits['IPSViewCompassSvg'] ?? null) === $inputs['CompassSvg']
            && ($edits['IPSViewCompassPreset'] ?? null) === '01_portolan'
            && str_contains($images['IPSViewBarPreview'], $rose['dataUri'])
            && $images['IPSViewBarPreview'] === $images['BarPreview'],
            'Copying a draft wind rose must stage both the selection and stored file and immediately preview the selected motif.'
        );
    }
    if ($family === 'EChartsTreemap') {
        assertGatewayGauge(
            json_decode($edits['IPSViewGroupColors'], true, 64, JSON_THROW_ON_ERROR) === $groupColors,
            'The independent list must receive the unsaved rows in order, including RGB zero.'
        );
        $svg = $images['IPSViewBarPreview'];
        assertGatewayGauge(
            str_contains($svg, '#174C62') && str_contains($svg, '#CC7744') && str_contains($svg, 'Unsaved tree title'),
            'The same click must preview the copied background, group list and other unsaved inputs.'
        );

        // Closing the form drops its edits. Reopening must build both previews from active properties.
        $reopened = json_decode($instance->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $oldPreview = findGaugeFormElement($form['elements'], 'IPSViewBarPreview')['image'];
        $reopenedPreview = findGaugeFormElement($reopened['elements'], 'IPSViewBarPreview')['image'];
        assertGatewayGauge(
            $reopenedPreview === $oldPreview && !str_contains(base64_decode(explode(',', $reopenedPreview, 2)[1]), 'Unsaved tree title'),
            'Discarding the form must reopen the original preview.'
        );

        // Emulate the console applying both the user's existing edits and the copied fields.
        foreach ($edits + ['Title' => $inputs['Title'], 'ChartBackgroundColor' => $inputs['ChartBackgroundColor'],
            'GroupColors'          => json_encode($groupColors, JSON_THROW_ON_ERROR), 'IPSViewAdaptToBackground' => false] as $name => $value) {
            $instance->SetTestProperty($name, $value);
        }
        $instance->ApplyChanges();
        $reopened = json_decode($instance->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $preview = findGaugeFormElement($reopened['elements'], 'IPSViewBarPreview')['image'];
        assertGatewayGauge(
            base64_decode(explode(',', $preview, 2)[1]) === $svg,
            'Opening the form after Apply must reproduce the preview from the copied draft.'
        );
    }
    $families[] = $family;
}
echo count($families) . " chart design-copy buttons exercised through their generated form scripts.\n";
