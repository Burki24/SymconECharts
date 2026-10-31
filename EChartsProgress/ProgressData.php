<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;

require_once __DIR__ . '/../EChartsGaugeSingle/JSLiveData.php';

/** One current source with signed scale direction; no source value is rewritten or normalized in Symcon. */
final class ProgressData
{
    public static function Build(array $model, Closure $read): array
    {
        $data = JSLiveGaugeData::Build($model, $read);
        $settings = $model['settings'];
        $min = min($settings['data_min'], $settings['data_max']);
        $max = max($settings['data_min'], $settings['data_max']);
        $value = $settings['data_min'] > $settings['data_max'] ? $min + $max - $data['value'] : $data['value'];
        $step = $settings['data_precisionCustom'] != 0 ? $settings['data_precisionCustom'] : $settings['data_precision'];
        if ($step > 0) {
            $value = floor($value / $step + 0.5) * $step;
        } // JavaScript Math.round, including negative half ties.
        $value = max($min, min($max, $value));
        return $data + ['displayValue' => $value, 'ratio' => ($value - $min) / ($max - $min), 'minimum' => $min, 'maximum' => $max];
    }
}
