<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/../libs/EChartsJSLiveSourceReader.php';
require_once __DIR__ . '/../libs/EChartsJSLiveTime.php';

/** Historical class frequencies/reference assignment; deliberately independent of current-value Radar. */
final class RadarHistoryData
{
    public static function Labels(array $settings): array
    {
        $custom = $settings['customScale'];
        usort($custom, static fn (array $a, array $b): int => $a['Order'] <=> $b['Order']);
        if ($custom !== [] && $settings['customScale_mode']) {
            return ['labels' => array_column($custom, 'Value'), 'alias' => array_column($custom, 'Alias')];
        }
        $jump = round(($settings['data_maxValue'] - $settings['data_minValue']) / $settings['data_sections'], $settings['data_precision']);
        $labels = [round($settings['data_minValue'], $settings['data_precision'])];
        for ($i = 1; $i < $settings['data_sections']; ++$i) {
            $labels[] = round($i * $jump, $settings['data_precision']);
        }
        $alias = $labels;
        foreach ($custom as $item) {
            $index = array_search($item['Value'], $labels);
            if ($index !== false) {
                $alias[$index] = $item['Alias'];
            }
        }
        return ['labels' => $labels, 'alias' => $custom === [] ? [] : $alias];
    }

    public static function Build(array $model, Closure $read, int $now): array
    {
        $labels = self::Labels($model['settings']);
        $queries = [];
        $planned = [];
        foreach ($model['bindings'] as $index => $binding) {
            if ($binding['role'] === 'reference') {
                continue;
            }
            $window = EChartsJSLiveTime::Window($model['time'], 'radar-history', $now, $binding['settings']['Offset']);
            $mode = $window['period'] >= 6 ? 'raw' : 'aggregated';
            $source = $model['sources'][$binding['sourceKey']];
            $reference = $source;
            foreach ($model['sources'] as $candidate) {
                if ($candidate['variableID'] === $binding['settings']['Reference']) {
                    $reference = $candidate;
                }
            }
            $valueQuery = EChartsJSLiveSourceReader::Query($source, $mode, $window);
            $referenceQuery = EChartsJSLiveSourceReader::Query($reference, $mode, $window);
            $queries[] = $valueQuery;
            $queries[] = $referenceQuery;
            $planned[$index] = compact('valueQuery', 'referenceQuery', 'reference', 'source', 'mode');
        }
        $reader = new EChartsJSLiveSourceReader($queries, $read);
        $series = [];
        foreach ($planned as $index => $query) {
            $binding = $model['bindings'][$index];
            $values = $reader->Read($query['valueQuery']);
            $references = $reader->Read($query['referenceQuery']);
            $points = self::Reduce(
                $labels['labels'],
                $values['Rows'],
                $references['Rows'],
                $query['mode'],
                $binding['settings']['Mode'],
                $binding['settings']['Reference'] > 0,
                $query['reference']['variableType'],
                $model['settings']
            );
            $series[] = ['key' => $binding['key'], 'label' => $binding['settings']['Title'] ?: $query['source']['label'],
                'settings'     => $binding['settings'], 'values' => $points, 'native' => $binding['native'], 'effective' => $binding['effective']];
        }
        return $labels + ['series' => $series];
    }

    public static function Reduce(array $labels, array $values, array $references, string $mode, string $reducer, bool $useReference, int $type, array $settings): array
    {
        if (!in_array($reducer, ['counter', 'average'], true) || !in_array($type, [1, 2], true)) {
            throw new RuntimeException('CALCULATION_UNSUPPORTED: unsupported historical Radar reducer/type.');
        }
        $digits = $settings['data_precision'];
        $jump = ($settings['data_maxValue'] - $settings['data_minValue']) / $settings['data_sections'];
        $jump = $type === 2 ? round($jump, $digits) : (int) $jump;
        $min = $type === 2 ? round($settings['data_minValue'] - $jump / 2, $digits) : (int) ($settings['data_minValue'] - $jump / 2);
        $max = $type === 2 ? round($settings['data_maxValue'] + $jump / 2, $digits) : (int) ($settings['data_maxValue'] + $jump / 2);
        $field = $mode === 'raw' ? 'Value' : 'Avg';
        usort($references, static fn (array $a, array $b): int => ($a['TimeStamp'] <=> $b['TimeStamp']) ?: ($a[$field] <=> $b[$field]));
        $result = array_fill(0, count($labels), 0);
        foreach ($references as $index => $reference) {
            $ref = $reference[$field];
            $value = $ref;
            if ($useReference) {
                if (!isset($references[$index + 1])) {
                    continue;
                }
                $start = $reference['TimeStamp'];
                $end = $references[$index + 1]['TimeStamp'];
                // The source's per-reference kernel calls include both endpoints, intentionally sharing boundary rows.
                $segment = array_filter($values, static fn (array $row): bool => $row['TimeStamp'] >= $start && $row['TimeStamp'] <= $end);
                $value = $reducer === 'counter' ? count($segment) : ($segment === [] ? 0 : round(array_sum(array_column($segment, $field)) / count($segment), $digits));
            }
            if ($type === 2) {
                $ref = round($ref, $digits);
            }
            if ($ref < $min || $ref > $max) {
                continue;
            }
            $closest = null;
            $bucket = -1;
            foreach ($labels as $labelIndex => $label) {
                if ($closest === null || abs($ref - $closest) >= abs($label - $ref)) {
                    $closest = $label;
                    $bucket = $labelIndex; // Equal distances choose the later label, as in JSLive.
                }
            }
            if ($bucket >= 0) {
                $result[$bucket] = $reducer === 'counter' ? $result[$bucket] + ($useReference ? $value : 1) : round($value, $digits);
            }
        }
        return $result;
    }
}
