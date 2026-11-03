<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;

require_once __DIR__ . '/../EChartsGaugeSingle/JSLiveData.php';

/** One current source with signed scale direction; no source value is rewritten or normalized in Symcon. */
final class ProgressData
{
    /** The original template uses only the default profile's literal affixes. */
    public static function SourceLabel(array $original): array
    {
        $name = $original['variable']['VariableProfile'] ?? '';
        $profile = $original['profiles'][$name] ?? [];
        return ['prefix' => $profile['Prefix'] ?? '', 'suffix' => $profile['Suffix'] ?? ''];
    }

    /** Compatibility for already imported models, resolved in memory, never persisted. */
    public static function CaptureLabel(int $variableID): array
    {
        $variable = IPS_GetVariable($variableID);
        $name = $variable['VariableProfile'] ?? '';
        return self::SourceLabel(['variable' => $variable,
            'profiles'                       => $name !== '' && IPS_VariableProfileExists($name) ? [$name => IPS_GetVariableProfile($name)] : []]);
    }

    public static function Build(array $model, Closure $read): array
    {
        $data = JSLiveGaugeData::Build($model, $read);
        $settings = $model['settings'];
        $min = min($settings['data_min'], $settings['data_max']);
        $max = max($settings['data_min'], $settings['data_max']);
        $value = $settings['data_min'] > $settings['data_max'] ? $min + $max - $data['value'] : $data['value'];
        $step = $settings['data_precisionCustom'] != 0 ? $settings['data_precisionCustom'] : $settings['data_precision'];
        if ($step > 0) {
            $precision = 1 / $step;
            $value = floor($value * $precision + 0.5) / $precision;
        } // JavaScript Math.round, including negative half ties.
        $value = max($min, min($max, $value));
        return $data + ['displayValue' => $value, 'ratio' => ($value - $min) / ($max - $min), 'minimum' => $min, 'maximum' => $max,
            'label'                    => $model['progressLabel'] ?? ['prefix' => '', 'suffix' => '']];
    }
}
