<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/../libs/EChartsJSLiveSourceReader.php';

final class JSLiveGaugeData
{
    public static function Build(array $model, Closure $read): array
    {
        $binding = $model['bindings'][0];
        $source = $model['sources'][$binding['sourceKey']];
        $query = EChartsJSLiveSourceReader::Query($source);
        $data = (new EChartsJSLiveSourceReader([$query], $read))->Read($query);
        if ((!is_int($data['Value']) && !is_float($data['Value'])) || !is_finite((float) $data['Value'])) {
            throw new RuntimeException('INVALID_VARIABLE_VALUE: Gauge requires a finite numeric value.');
        }
        return ['variableID' => $source['variableID'], 'value' => $data['Value'], 'timestamp' => $data['Timestamp'],
            'native'         => $binding['native'], 'effective' => $binding['effective']];
    }
}
