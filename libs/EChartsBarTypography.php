<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/** Shared property and form mechanics for optional bar-chart typography. */
trait EChartsBarTypography
{
    /** @param list<string> $fonts @param list<string> $colors */
    private function RegisterBarTypography(array $fonts, array $colors, string $prefix = ''): void
    {
        foreach ($fonts as $name) {
            $this->RegisterPropertyInteger($prefix . $name, 100);
        }
        foreach ($colors as $name) {
            $this->RegisterPropertyInteger($prefix . $name, -1);
        }
    }

    /** @param list<string> $fonts @param list<string> $colors */
    private function BarTypographyIsValid(array $fonts, array $colors, string $prefix = ''): bool
    {
        foreach ($fonts as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < 50 || $value > 200) {
                return false;
            }
        }
        foreach ($colors as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            if ($value < -1 || $value > 0xFFFFFF) {
                return false;
            }
        }

        return true;
    }

    /** @param list<string> $fonts @param list<string> $colors @return array<string, int|string> */
    private function ReadBarTypography(array $fonts, array $colors, string $prefix = ''): array
    {
        $style = [];
        foreach ($fonts as $name) {
            $style[lcfirst($name)] = $this->ReadPropertyInteger($prefix . $name);
        }
        foreach ($colors as $name) {
            $value = $this->ReadPropertyInteger($prefix . $name);
            $style[lcfirst($name)] = $value < 0 ? '' : EChartsAsset::ColorToHex($value);
        }

        return $style;
    }

    /**
     * Append chart-selected text controls to both designers before their sections and previews are built.
     *
     * @param array<string, mixed> $form
     * @param array<string, string> $fonts
     * @param array<string, string> $colors
     * @return array<string, mixed>
     */
    private function WithBarTypographyForm(array $form, array $fonts, array $colors): array
    {
        $found = ['Tile designer' => false, 'IPSView design' => false];
        foreach ($form['elements'] as &$element) {
            $caption = $element['caption'] ?? null;
            if (($element['type'] ?? null) !== 'ExpansionPanel' || !isset($found[$caption])) {
                continue;
            }
            $found[$caption] = true;
            $prefix = $caption === 'IPSView design' ? 'IPSView' : '';
            $rows = [];
            foreach (array_chunk($fonts, 3, true) as $chunk) {
                $items = [];
                foreach ($chunk as $name => $label) {
                    $items[] = [
                        'type'    => 'NumberSpinner', 'name' => $prefix . $name,
                        'caption' => $label, 'minimum' => 50, 'maximum' => 200, 'suffix' => '%'
                    ];
                }
                $rows[] = ['type' => 'RowLayout', 'items' => $items];
            }
            foreach (array_chunk($colors, 2, true) as $chunk) {
                $items = [];
                foreach ($chunk as $name => $label) {
                    $items[] = [
                        'type'               => 'SelectColor', 'name' => $prefix . $name,
                        'caption'            => $label, 'allowTransparent' => true,
                        'transparentCaption' => 'Automatic (theme)'
                    ];
                }
                $rows[] = ['type' => 'RowLayout', 'items' => $items];
            }
            if ($prefix === '') {
                array_push($element['items'], ...$rows);
                continue;
            }
            $independent = false;
            foreach ($element['items'] as &$item) {
                if (($item['type'] ?? null) === 'ExpansionPanel'
                    && ($item['caption'] ?? null) === 'Independent IPSView designer') {
                    array_push($item['items'], ...$rows);
                    $independent = true;
                    break;
                }
            }
            unset($item);
            if (!$independent) {
                throw new RuntimeException('The independent IPSView designer is missing.');
            }
        }
        unset($element);
        if (in_array(false, $found, true)) {
            throw new RuntimeException('The Tile or IPSView designer is missing.');
        }

        return $form;
    }
}
