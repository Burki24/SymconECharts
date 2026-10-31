<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/EChartsJSLiveModel.php';
require_once __DIR__ . '/EChartsJSLivePage.php';
require_once __DIR__ . '/../EChartsTimeSeries/JSLiveData.php';
require_once __DIR__ . '/../EChartsPie/JSLiveData.php';
require_once __DIR__ . '/../EChartsGaugeSingle/JSLiveData.php';
require_once __DIR__ . '/../EChartsRadarHistory/RadarHistoryData.php';
require_once __DIR__ . '/../EChartsProgress/ProgressData.php';

/** Dispatch and output contract only; calculation and geometry stay in the owning families. */
final class EChartsJSLiveRendering
{
    public static function Target(array $model): string
    {
        return match ($model['family']) {
            'gauge'             => 'EChartsGaugeSingle', 'pie' => 'EChartsPie', 'progress' => 'EChartsProgress', 'radar-history' => 'EChartsRadarHistory',
            'cartesian-history' => array_unique(array_column(array_column($model['bindings'], 'settings'), 'Type')) === ['bar'] ? 'EChartsBarHistory' : 'EChartsTimeSeries',
            default             => throw new RuntimeException('FORMAT_UNSUPPORTED: unknown target family.')
        };
    }

    public static function State(array $model, Closure $reader, int $now, array $design = []): array
    {
        $data = match ($model['family']) {
            'gauge'             => JSLiveGaugeData::Build($model, $reader),
            'pie'               => JSLivePieData::Build($model, $reader),
            'progress'          => ProgressData::Build($model, $reader),
            'radar-history'     => RadarHistoryData::Build($model, $reader, $now),
            'cartesian-history' => JSLiveCartesianData::Build($model, $reader, $now)
        };
        $settings = $model['settings'];
        foreach (['title_text' => 'title', 'title_display' => 'showTitle', 'legend_display' => 'showLegend', 'tooltips_enabled' => 'showTooltip'] as $field => $key) {
            if (array_key_exists($key, $design)) {
                $settings[$field] = $design[$key];
            }
        }
        $settings['__fontScale'] = ($design['fontSizePercent'] ?? 100) / 100;
        $settings['__animate'] = $design['animationEnabled'] ?? true;
        $background = $design['background'] ?? -1;
        if (!is_int($background) || $background < -1 || $background > 0xFFFFFF || $settings['__fontScale'] < 0.5 || $settings['__fontScale'] > 2.0) {
            throw new RuntimeException('PACKAGE_INVALID: invalid imported design override.');
        }
        $state = ['schemaVersion' => 1, 'status' => 'ready', 'chart' => ['family' => $model['family'], 'settings' => $settings, 'data' => $data,
            'resources'                                                           => $model['resources'], 'timezone' => $model['time']['timezone'] ?? 'UTC', 'background' => $background === -1 ? 'transparent' : sprintf('#%06X', $background)], 'error' => null];
        $limit = $model['family'] === 'cartesian-history' ? 900000 : 524288;
        if (strlen(EChartsImportJson::Canonical($state)) > $limit) {
            throw new RuntimeException('LIMIT_EXCEEDED: imported output model exceeds its byte budget.');
        }
        return $state;
    }
}
