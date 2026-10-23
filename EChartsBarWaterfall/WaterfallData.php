<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

/** Ordered signed accumulation shared by the complete model and its preview. */
final class WaterfallData
{
    public const MAXIMUM_SOURCE_COUNT = 100;

    /** Optional row flags; absent and false preserve the old source contract. */
    public static function SourceOptions(array $source, int $index, ?callable $translate = null): array
    {
        $t = $translate ?? static fn (string $text): string => $text;
        $options = [];
        foreach (['SubtotalAfter', 'Restart'] as $name) {
            if (array_key_exists($name, $source) && !is_bool($source[$name])) {
                throw new InvalidArgumentException(sprintf($t('Source %d: subtotal and restart must be enabled or disabled.'), $index + 1));
            }
            if ($source[$name] ?? false) {
                $options[$name] = true;
            }
        }
        if ($index === 0 && ($options['Restart'] ?? false)) {
            throw new InvalidArgumentException($t('The first Waterfall source is already the start value; a restart is only allowed on later rows.'));
        }
        return $options;
    }

    /** Canonical complete steps for live outputs and source-backed SVG drafts. */
    public static function Steps(array $sources, array $currents, array $labels, string $totalLabel, ?callable $translate = null): array
    {
        $t = $translate ?? static fn (string $text): string => $text;
        if (!array_is_list($sources) || !array_is_list($currents) || count($sources) !== count($currents)) {
            throw new InvalidArgumentException('Invalid Waterfall source values.');
        }
        $options = array_map(static fn (array $source, int $index): array => self::SourceOptions($source, $index, $t), $sources, array_keys($sources));
        $restarts = array_map(static fn (array $option): bool => $option['Restart'] ?? false, $options);
        $hasRestarts = in_array(true, $restarts, true);
        $totals = self::Totals(array_column($currents, 'Value'), $restarts);
        $steps = [];
        $section = 1;
        $sectionLabel = '';
        foreach ($sources as $index => $source) {
            $id = 'variable-' . $source['VariableID'];
            $label = $labels[$source['VariableID']];
            $after = $totals[$index];
            $restart = $restarts[$index];
            if ($index === 0 || $restart) {
                $sectionLabel = $label;
                if ($restart) {
                    ++$section;
                }
            }
            $metadata = $hasRestarts ? ['section' => $section, 'sectionLabel' => $sectionLabel] : [];
            $steps[] = ['id' => $id, 'label' => $restart ? sprintf($t('Restart: %s'), $label) : $label,
                'kind'       => $index === 0 ? 'start' : ($restart ? 'restart' : 'change'),
                'change'     => (float) $currents[$index]['Value'], 'before' => $index === 0 || $restart ? 0.0 : $totals[$index - 1],
                'after'      => $after, 'timestamp' => $currents[$index]['Timestamp'],
                ...($restart ? ['previousTotal' => $totals[$index - 1]] : []), ...$metadata];
            if ($options[$index]['SubtotalAfter'] ?? false) {
                $steps[] = ['id' => 'subtotal-' . $id, 'label' => sprintf($t('Subtotal: %s'), $label),
                    'kind'       => 'subtotal', 'change' => $after, 'before' => 0.0, 'after' => $after,
                    'calculated' => true, 'afterSource' => $id, ...$metadata];
            }
        }
        $total = $totals[count($totals) - 1];
        $steps[] = ['id' => 'total', 'label' => $hasRestarts ? sprintf($t('Final section total: %s'), $totalLabel) : $totalLabel,
            'kind'       => 'total', 'change' => $total, 'before' => 0.0, 'after' => $total, ...$metadata];
        return $steps;
    }

    /** @return list<float> Running totals, including the absolute start value. */
    public static function Totals(array $values, array $restarts = []): array
    {
        if (!array_is_list($values) || $values === [] || count($values) > self::MAXIMUM_SOURCE_COUNT) {
            throw new InvalidArgumentException('Invalid Waterfall values.');
        }
        if ($restarts !== [] && (!array_is_list($restarts) || count($restarts) !== count($values)
            || array_any($restarts, static fn (mixed $value): bool => !is_bool($value)) || $restarts[0])) {
            throw new InvalidArgumentException('Invalid Waterfall restarts.');
        }
        $totals = [];
        $total = 0.0;
        foreach ($values as $index => $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                throw new InvalidArgumentException('Waterfall values must be finite numbers.');
            }
            $total = $index === 0 || ($restarts[$index] ?? false) ? (float) $value : $total + $value;
            if (!is_finite($total)) {
                throw new RuntimeException('The Waterfall total is not finite.');
            }
            $totals[] = $total;
        }
        return $totals;
    }
}
