<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/../libs/EChartsJSLiveSourceReader.php';
require_once __DIR__ . '/../libs/EChartsJSLiveTime.php';

/** Legacy Cartesian series, including independent axes, repeated bindings, offsets and mixed lines/bars. */
final class JSLiveCartesianData
{
    public static function Build(array $model, Closure $read, int $now): array
    {
        $queries = [];
        $rules = [];
        $axes = array_column($model['axes'], null, 'Ident');
        foreach ($axes as &$axis) {
            if ($axis['Bool']) {
                $axis['booleanLabels'] = self::BooleanLabels($axis);
            }
        }
        unset($axis);
        foreach ($model['bindings'] as $index => $binding) {
            $settings = $binding['settings'];
            $source = $model['sources'][$binding['sourceKey']];
            $window = EChartsJSLiveTime::Window($model['time'], 'cartesian-history', $now, $settings['Offset']);
            $mode = $settings['HighRes'] <= $window['period'] ? 'raw' : 'aggregated';
            $query = EChartsJSLiveSourceReader::Query($source, $mode, $window);
            $queries[] = $query;
            $rules[$index] = ['window' => $window, 'query' => $query, 'mode' => $mode];
        }
        $reader = new EChartsJSLiveSourceReader($queries, $read);
        $series = [];
        foreach ($model['bindings'] as $index => $binding) {
            $source = $model['sources'][$binding['sourceKey']];
            $rule = $rules[$index];
            $data = $reader->Read($rule['query']); // Hidden bindings remain in the shared load/validation budget.
            if (($data['VariableType'] ?? null) !== $source['variableType']) {
                throw new RuntimeException('SOURCE_CHANGED: source type changed.');
            }
            $settings = $binding['settings'];
            $axis = $axes[$settings['Axes']] ?? null;
            $boolean = ($axis['Bool'] ?? false) === true;
            $labels = $boolean ? $axis['booleanLabels'] : null;
            $counter = $source['archive']['aggregationType'] === 1;
            $points = EChartsJSLiveTime::Cartesian($data['Rows'], $rule['window'] + ['mode' => $rule['mode'],
                'counter'                                                                   => $counter, 'decimation' => $model['settings']['data_highResSteps'],
                'precision'                                                                 => $counter ? 5 : $model['settings']['data_precision']], $data['Value'], $now, $labels);
            $series[] = ['key' => $binding['key'], 'variableID' => $source['variableID'], 'enabled' => $binding['enabled'],
                'label'        => $settings['Title'] ?: $source['label'], 'settings' => $settings, 'axis' => $settings['Axes'],
                'native'       => $binding['native'], 'effective' => $binding['effective'], 'points' => $points];
        }
        return ['window' => EChartsJSLiveTime::Window($model['time'], 'cartesian-history', $now), 'axes' => array_values($axes), 'series' => $series];
    }

    public static function BooleanLabels(array $axis): array
    {
        $on = 'ON';
        $off = 'OFF';
        if ($axis['OverrideBoolText']) {
            $on = $axis['BoolTrue'];
            $off = $axis['BoolFalse'];
        }
        else {
            foreach ($axis['native']['parameters']['OPTIONS'] ?? [] as $option) {
                if ($option['Value']) {
                    $on = $option['Caption'];
                } else {
                    $off = $option['Caption'];
                }
            }
        }
        return [$on, $on === $off ? $off . ' ' : $off];
    }
}
