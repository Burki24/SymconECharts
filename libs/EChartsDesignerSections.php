<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/** Wraps existing designer controls without changing their names or callbacks. */
final class EChartsDesignerSections
{
    /**
     * @param array<string, mixed> $form
     * @param list<array{caption: string, start: string}> $sections Chart-specific section titles and first fields.
     * @return array<string, mixed>
     */
    public static function Group(array $form, array $sections): array
    {
        $found = ['Tile designer' => false, 'IPSView design' => false];
        foreach ($form['elements'] as &$element) {
            $caption = $element['caption'] ?? null;
            if (($element['type'] ?? null) !== 'ExpansionPanel' || !isset($found[$caption])) {
                continue;
            }
            $found[$caption] = true;
            $prefix = $caption === 'IPSView design' ? 'IPSView' : '';
            $items = $element['items'] ?? [];
            $positions = [];
            foreach ($sections as $section) {
                $start = $section['start'];
                if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $start) === 1) {
                    $start = $prefix . $start;
                }
                $position = self::FindDirectItem($items, $start);
                if ($position === null || ($positions !== [] && $position <= $positions[array_key_last($positions)])) {
                    throw new RuntimeException('The ' . $caption . ' section is missing or out of order: ' . $start);
                }
                $positions[] = $position;
            }
            $end = count($items);
            for ($index = $positions[0]; $index < count($items); $index++) {
                if (in_array($items[$index]['type'] ?? null, ['ExpansionPanel', 'Image'], true)) {
                    $end = $index;
                    break;
                }
            }
            if ($positions[array_key_last($positions)] >= $end) {
                throw new RuntimeException('The ' . $caption . ' section boundaries are invalid.');
            }
            $grouped = array_slice($items, 0, $positions[0]);
            foreach ($sections as $index => $section) {
                $next = $positions[$index + 1] ?? $end;
                $grouped[] = [
                    'type'     => 'ExpansionPanel',
                    'caption'  => $section['caption'],
                    'expanded' => false,
                    'items'    => array_slice($items, $positions[$index], $next - $positions[$index])
                ];
            }
            array_push($grouped, ...array_slice($items, $end));
            $element['items'] = $grouped;
        }
        unset($element);

        if (in_array(false, $found, true)) {
            throw new RuntimeException('The Tile or IPSView designer section is missing.');
        }

        return $form;
    }

    /** @param list<array<string, mixed>> $items */
    private static function FindDirectItem(array $items, string $start): ?int
    {
        foreach ($items as $index => $item) {
            if (($item['caption'] ?? null) === $start || ($item['name'] ?? null) === $start) {
                return $index;
            }
            if (($item['type'] ?? null) === 'RowLayout'
                && in_array($start, array_column($item['items'] ?? [], 'name'), true)) {
                return $index;
            }
        }

        return null;
    }
}
