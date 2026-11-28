<?php

declare(strict_types=1);

// Exercise the generated forms so fields added or mirrored in PHP are included.
(static function (): void
{
    $colorFields = static function (array $items) use (&$colorFields): array
    {
        $fields = [];
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'SelectColor') {
                $fields[$item['name']] = $item;
            }
            if (isset($item['items'])) {
                $fields += $colorFields($item['items']);
            }
        }
        return $fields;
    };

    foreach (glob(dirname(__DIR__) . '/ECharts*/visualization/index.html') as $template) {
        $directory = dirname($template, 2);
        require_once $directory . '/module.php';
        $class = basename($directory);
        $module = new $class();
        $module->Create();
        $properties = $module->GetTestProperties();
        assertGatewayGauge(
            $properties['ChartBackgroundColor'] === -1 && $properties['IPSViewChartBackgroundColor'] === -1,
            "$class must keep automatic backgrounds by default."
        );
        $module->SetTestProperty('ChartBackgroundColor', 0);
        $module->SetTestProperty('IPSViewChartBackgroundColor', 0x512B67);
        $module->SetTestProperty('IPSViewUseTileDesign', false);
        $module->SetTestProperty('IPSViewAdaptToBackground', false);
        $json = $module->GetConfigurationForm();
        assertGatewayGauge(strlen($json) < SYMCON_OUTPUT_BUFFER_LIMIT, "$class must fit both designers within the form budget.");
        $form = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        unset($json);
        $panels = array_column($form['elements'], 'items', 'caption');
        $tile = $colorFields($panels['Tile designer']);
        $ipsView = $colorFields($panels['IPSView design']);
        unset($ipsView['IPSViewBackgroundColor']); // Widget tint belongs to its embedding.
        foreach ($tile as $name => $field) {
            $peer = 'IPSView' . $name;
            assertGatewayGauge(
                isset($ipsView[$peer]) && $field['caption'] === $ipsView[$peer]['caption'],
                "$class must expose matching $name color selectors in both designers."
            );
            unset($ipsView[$peer]);
        }
        assertGatewayGauge(
            $ipsView === [] && isset($tile['ChartBackgroundColor']),
            "$class must expose every independent chart color in the Tile designer as well."
        );

        if (method_exists($module, 'UpdateBarPreviewFromForm')) {
            foreach (['ChartBackgroundColor', 'IPSViewChartBackgroundColor'] as $name) {
                $field = findGaugeFormElement($form['elements'], $name);
                assertGatewayGauge(
                    str_contains($field['onChange'] ?? '', 'UpdateBarPreviewFromForm'),
                    "$class must refresh both previews on a $name draft change."
                );
            }
            foreach (['BarPreview' => '#000000', 'IPSViewBarPreview' => '#512B67'] as $name => $hex) {
                $image = findGaugeFormElement($form['elements'], $name);
                $svg = base64_decode(substr($image['image'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(
                    str_contains($svg, 'fill="' . $hex . '" fill-opacity="1"'),
                    "$class must paint the selected $name background."
                );
            }
            $module->UpdateBarPreviewFromForm(json_encode(['ChartBackgroundColor' => 0x143558, 'IPSViewUseTileDesign' => true], JSON_THROW_ON_ERROR));
            foreach (array_slice($module->GetTestFormUpdates(), -2) as $update) {
                $svg = base64_decode(substr($update['Value'], strlen('data:image/svg+xml;base64,')), true);
                assertGatewayGauge(
                    str_contains($svg, 'fill="#143558" fill-opacity="1"'),
                    "$class must use an unsaved inherited color in both drafts."
                );
            }
        }

        copyDesignAndApplyForm($module);
        assertGatewayGauge(
            $module->GetTestProperties()['IPSViewChartBackgroundColor'] === 0,
            "$class must copy pure black into an independent IPSView design."
        );
        $module->SetTestProperty('ChartBackgroundColor', 0x1000000);
        $error = (new ReflectionMethod($module, 'GetConfigurationError'))->invoke($module);
        assertGatewayGauge($error['Message'] === 'Select a valid chart background color.', "$class must validate RGB bounds.");
        unset($module, $form, $panels);
    }
})();
