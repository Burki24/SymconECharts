<?php

declare(strict_types=1);

// Validate the final, dynamically expanded controls (including editable list columns) in both output modes.
(static function (): void
{
    $expected = static function (array $items, array $catalog) use (&$expected): array
    {
        $entries = [];
        foreach ($items as $item) {
            $field = preg_replace('/^IPSView/', '', $item['name'] ?? '');
            $caption = $item['caption'] ?? '';
            $label = $catalog[$caption] ?? $caption;
            if (($item['type'] ?? '') === 'SelectColor') {
                $entries[$field] = $label;
            }
            foreach ($item['columns'] ?? [] as $column) {
                if (($column['edit']['type'] ?? '') === 'SelectColor') {
                    $entries[$field . '-' . $column['name']] = $label . ' / ' . ($catalog[$column['caption']] ?? $column['caption']);
                }
            }
            $entries += $expected($item['items'] ?? [], $catalog);
        }

        return $entries;
    };
    $motifs = [];
    foreach (glob(dirname(__DIR__) . '/ECharts*/visualization/index.html') as $template) {
        $dir = dirname($template, 2);
        require_once $dir . '/module.php';
        $class = basename($dir);
        $device = new $class();
        $device->Create();
        $form = json_decode($device->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
        $motif = (string) file_get_contents($dir . '/ColorGuide.svg');
        assertGatewayGauge(!isset($motifs[$motif]), "$class must illustrate its own diagram geometry.");
        $motifs[$motif] = true;
        $locale = json_decode(file_get_contents($dir . '/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
        $labels = designerGermanCaptions();
        foreach (['Tile designer', 'IPSView design'] as $caption) {
            $items = array_column($form['elements'], 'items', 'caption')[$caption];
            $topics = array_column($items, null, 'caption');
            $guide = $topics['Schematic color key'];
            $base = array_values(array_filter($items, static fn (array $item): bool => $item !== $guide));
            // Exercise the same final form adapter with its real module and shared German captions.
            $translate = static fn (string $text): string => $locale[$text] ?? $labels[$text] ?? $text;
            $de = \eCharts\EChartsDesignerColorGuide::WithGuides(
                ['elements' => [['caption' => $caption, 'items' => $base]]],
                $dir,
                $translate,
                $labels['Schematic color key'],
                'Deutsch'
            );
            $deGuide = array_column($de['elements'][0]['items'], null, 'caption')[$labels['Schematic color key']];
            foreach ([[$guide, []], [$deGuide, $locale + $labels]] as [$localized, $catalog]) {
                $fields = $expected($base, $catalog);
                $seen = [];
                foreach ($localized['items'] as $item) {
                    if (($item['type'] ?? '') !== 'Image') {
                        continue;
                    }
                    $svg = base64_decode(substr($item['image'], strlen('data:image/svg+xml;base64,')), true);
                    $xml = new DOMDocument();
                    assertGatewayGauge(
                        is_string($svg) && $xml->loadXML($svg, LIBXML_NONET)
                        && !preg_match('/<(script|foreignObject|image)\b|(?:href|src)="(?!#)/i', $svg),
                        "$class must supply a self-contained SVG color guide."
                    );
                    $path = new DOMXPath($xml);
                    $path->registerNamespace('s', 'http://www.w3.org/2000/svg');
                    foreach ($path->query('/s:svg/s:g/s:use') as $paint) {
                        $style = $paint->getAttribute('style');
                        preg_match('/^--([\w-]+):#c34800/', $style, $match);
                        $role = $match[1] ?? '';
                        assertGatewayGauge(
                            isset($fields[$role]) && str_contains($motif, 'var(--' . $role . ','),
                            "$class must paint the editable $role role on its own diagram."
                        );
                        if (preg_match('/stop-color="var\(--' . $role . ',/', $motif)) {
                            assertGatewayGauge(
                                preg_match('/url\(#([\w-]+)\)/', $style, $variant)
                                && $path->query('//s:linearGradient[@id="' . $variant[1] . '"]/s:stop[@stop-color="#c34800"]')->length > 0,
                                "$class must highlight $role inside its own gradient."
                            );
                        }
                        $node = $paint->parentNode->nextSibling;
                        $lines = [];
                        while ($node instanceof DOMElement && $node->localName === 'text') {
                            $lines[] = $node->textContent;
                            $node = $node->nextSibling;
                        }
                        assertGatewayGauge(
                            implode(' ', $lines) === $fields[$role]
                            && in_array($fields[$role], array_column($localized['items'], 'caption'), true),
                            "$class must use the localized $role field name in the image and the text legend."
                        );
                        $seen[$role] = true;
                    }
                }
                assertGatewayGauge(array_keys($seen) === array_keys($fields), "$class must explain every color of $caption.");
            }
        }
    }
    echo "All native designers provide self-contained, localized color maps and text legends.\n";
})();
