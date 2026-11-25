<?php

declare(strict_types=1);

namespace eCharts;

use Burki24\SymconModuleHelper\SVGPreviewHelper;

require_once __DIR__ . '/helper/SVGPreviewHelper.php';

/** Read-only color keys for the actual editable fields in each chart designer. */
final class EChartsDesignerColorGuide
{
    public static function WithGuides(array $form, string $directory, callable $translate, string $title, string $help): array
    {
        $motif = (string) file_get_contents($directory . '/ColorGuide.svg');
        foreach ($form['elements'] as &$panel) {
            $caption = $panel['caption'] ?? '';
            if (!in_array($caption, ['Tile designer', 'IPSView design'], true)) {
                continue;
            }
            $fields = self::Fields($panel['items'], $translate);
            $guide = [
                'type'     => 'ExpansionPanel',
                'caption'  => $title,
                'expanded' => false,
                'items'    => [['type' => 'Label', 'caption' => $help]]
            ];
            foreach (array_chunk($fields, 5, true) as $chunk) {
                $guide['items'][] = ['type' => 'Image', 'width' => '100%', 'center' => true,
                    'image'                 => SVGPreviewHelper::dataUri(self::Sheet($motif, $chunk))];
            }
            foreach ($fields as $label) {
                $guide['items'][] = ['type' => 'Label', 'caption' => $label];
            }
            // Keep the existing draft preview last, with all explanatory topics above it.
            $index = count($panel['items']);
            foreach ($panel['items'] as $position => $item) {
                if (($item['type'] ?? '') === 'Image') {
                    $index = $position;
                    break;
                }
            }
            array_splice($panel['items'], $index, 0, [$guide]);
        }
        unset($panel);

        return $form;
    }

    /** @return array<string, string> Synthetic paint role paired with the localized field caption. */
    private static function Fields(array $items, callable $translate): array
    {
        $fields = [];
        foreach ($items as $item) {
            $name = preg_replace('/^IPSView/', '', $item['name'] ?? '');
            $type = $item['type'] ?? '';
            if ($type === 'SelectColor') {
                $fields[$name] = $translate($item['caption']);
            } elseif ($type === 'List') {
                foreach ($item['columns'] ?? [] as $column) {
                    if (($column['edit']['type'] ?? '') === 'SelectColor') {
                        $fields[$name . '-' . $column['name']] = $translate($item['caption']) . ' / ' . $translate($column['caption']);
                    }
                }
            } elseif (isset($item['items'])) {
                $fields += self::Fields($item['items'], $translate);
            }
        }

        return $fields;
    }

    private static function Sheet(string $motif, array $fields): string
    {
        $height = count($fields) * 76;
        // A referenced gradient uses the styles of its own definition, outside the SVG <use> instance.
        // Select a local paint server with the actual highlighted stop for each affected miniature.
        $gradients = '';
        $paints = [];
        preg_match_all('/<linearGradient id="([A-Za-z]+)"[^>]*>.*?<\/linearGradient>/s', $motif, $matches, PREG_SET_ORDER);
        foreach ($matches as [$definition, $id]) {
            $motif = str_replace('url(#' . $id . ')', 'var(--paint-' . $id . ',url(#' . $id . '))', $motif);
            foreach (array_keys($fields) as $name) {
                if (!str_contains($definition, 'var(--' . $name . ',')) {
                    continue;
                }
                $variant = $id . '-' . $name;
                $gradient = str_replace('id="' . $id . '"', 'id="' . $variant . '"', $definition);
                $gradients .= preg_replace('/var\(--' . $name . ',#[0-9a-fA-F]{6}\)/', '#c34800', $gradient);
                $paints[$name] = ($paints[$name] ?? '') . ';--paint-' . $id . ':url(#' . $variant . ')';
            }
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 380 ' . $height . '" role="img">'
            . '<defs>' . $gradients . '<g id="motif">' . $motif . '</g></defs><rect width="380" height="' . $height . '" fill="#fff"/>';
        $y = 0;
        foreach ($fields as $name => $label) {
            // Each miniature highlights exactly one role; the other paints use the neutral motif defaults.
            $svg .= '<g transform="translate(2 ' . ($y + 4) . ')"><use xlink:href="#motif" style="--' . $name . ':#c34800' . ($paints[$name] ?? '') . '"/></g>';
            $lines = explode("\n", wordwrap($label, 22));
            $lineY = $y + 40 - (count($lines) - 1) * 8;
            foreach ($lines as $line) {
                // German field names can contain a compound too long to wrap at a space.
                $fit = strlen($line) > 25 ? ' textLength="194" lengthAdjust="spacingAndGlyphs"' : '';
                $svg .= '<text x="178" y="' . $lineY . '" font-family="sans-serif" font-size="14" fill="#1b2730"' . $fit . '>'
                    . SVGPreviewHelper::escape($line) . '</text>';
                $lineY += 16;
            }
            $y += 76;
        }

        return $svg . '</svg>';
    }
}
