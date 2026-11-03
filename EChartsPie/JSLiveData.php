<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/../libs/EChartsJSLiveSourceReader.php';

/** Current original magnitudes, original ring/order identities and frozen per-slice formatting. */
final class JSLivePieData
{
    public static function Build(array $model, Closure $read): array
    {
        $queries = array_map(static fn (array $source): array => EChartsJSLiveSourceReader::Query($source), $model['sources']);
        $reader = new EChartsJSLiveSourceReader($queries, $read);
        $rings = $model['settings']['Datasets'];
        $slices = [];
        foreach ($model['bindings'] as $binding) {
            $source = $model['sources'][$binding['sourceKey']];
            $data = $reader->Read(EChartsJSLiveSourceReader::Query($source));
            if (!is_numeric($data['Value']) || !is_finite((float) $data['Value']) || $data['Value'] < 0) {
                throw new RuntimeException('INVALID_VARIABLE_VALUE: Pie requires nonnegative current magnitudes.');
            }
            $row = $binding['settings'];
            $slices[$row['ring']][] = ['key' => $binding['key'], 'variableID' => $source['variableID'],
                'value'                      => round($data['Value'], $model['settings']['data_precision']), 'originalValue' => $data['Value'],
                'timestamp'                  => $data['Timestamp'], 'label' => ($row['Label'] ?? '') ?: $source['label'], 'settings' => $row,
                'native'                     => $binding['native'], 'effective' => $binding['effective']];
        }
        $result = [];
        foreach ($rings as $index => $ring) {
            $items = $slices[$index] ?? [];
            usort($items, static fn (array $a, array $b): int => ($a['settings']['Order'] <=> $b['settings']['Order']));
            if (count(array_unique(array_column($items, 'label'))) !== count($items)) {
                throw new RuntimeException('CALCULATION_UNSUPPORTED: duplicate labels within a source ring require an explicit legacy overwrite rule.');
            }
            $result[] = ['key' => $index, 'settings' => $ring, 'items' => $items];
        }
        usort($result, static fn (array $a, array $b): int => $a['settings']['Order'] <=> $b['settings']['Order']);
        return ['rings' => $result];
    }
}
