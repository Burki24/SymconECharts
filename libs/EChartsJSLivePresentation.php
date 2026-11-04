<?php

declare(strict_types=1);

namespace SymconECharts;

use Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper;

/** Migration-only comparison. Original formats and the complete current source fingerprint remain untouched. */
final class EChartsJSLivePresentation
{
    public static function Compare(mixed $expected, mixed $current, int $type): array
    {
        $different = ['status' => 'different', 'normalizations' => []];
        if (EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($expected)) === EChartsImportJson::Canonical(EChartsJSLivePackage::Wire($current))) {
            return ['status' => 'exact', 'normalizations' => []];
        }
        $expected = EChartsJSLivePackage::ArrayValue($expected);
        $current = EChartsJSLivePackage::ArrayValue($current);
        if (!in_array($type, [1, 2], true) || !is_array($expected) || !is_array($current)
            || ($expected['presentationId'] ?? null) !== VariablePresentationNormalizationHelper::VALUE
            || ($current['presentationId'] ?? null) !== $expected['presentationId']
            || !is_array($expected['parameters'] ?? null) || !is_array($current['parameters'] ?? null)
            || array_diff(array_keys($expected), ['presentationId', 'parameters']) !== []
            || array_diff(array_keys($current), ['presentationId', 'parameters']) !== []) {
            return $different;
        }
        $defaults = ['COLOR'      => -1, 'CONTENT_COLOR' => -1, 'DECIMAL_SEPARATOR' => 'Client',
            'DIGITS'              => $type === 1 ? 0 : 2, 'DISPLAY_TYPE' => 0, 'ICON' => '', 'INTERVALS' => [],
            'INTERVALS_ACTIVE'    => false, 'MAX' => 100, 'MIN' => 0, 'PERCENTAGE' => false,
            'PREFIX'              => '', 'PREVIEW_STYLE' => 1, 'SHOW_PREVIEW' => true, 'SUFFIX' => '',
            'THOUSANDS_SEPARATOR' => '', 'USAGE_TYPE' => 0];
        $rules = [];
        $normalized = [];
        foreach ([$expected, $current] as $presentation) {
            $parameters = &$presentation['parameters'];
            foreach ($defaults as $key => $default) {
                if (!array_key_exists($key, $parameters)) {
                    $parameters[$key] = $default;
                    $rules[$key . ':known-default'] = ['parameter' => $key, 'rule' => 'known-value-default'];
                }
            }
            // A stale false string-only flag has no numeric rendering effect. True and unknown fields stay strict.
            if (($parameters['MULTILINE'] ?? null) === false) {
                unset($parameters['MULTILINE']);
                $rules['MULTILINE:inactive'] = ['parameter' => 'MULTILINE', 'rule' => 'inactive-numeric-flag'];
            }
            foreach (['MIN', 'MAX'] as $key) {
                self::Number($parameters[$key], $key, $rules);
            }
            if (!is_array($parameters['INTERVALS']) || !array_is_list($parameters['INTERVALS'])) {
                return $different;
            }
            foreach ($parameters['INTERVALS'] as &$interval) {
                if (is_array($interval)) {
                    foreach (['IntervalMinValue', 'IntervalMaxValue', 'ConversionFactor'] as $key) {
                        if (array_key_exists($key, $interval)) {
                            self::Number($interval[$key], 'INTERVALS.*.' . $key, $rules);
                        }
                    }
                }
            }
            unset($interval, $parameters);
            $normalized[] = $presentation;
        }
        return EChartsImportJson::Canonical($normalized[0]) === EChartsImportJson::Canonical($normalized[1])
            ? ['status' => 'equivalent', 'normalizations' => array_values($rules)] : $different;
    }

    private static function Number(mixed &$value, string $key, array &$rules): void
    {
        // Never round or cast a large integer through float; source IDs, precision and all nonnumeric fields stay typed.
        if (is_float($value) && is_finite($value) && floor($value) === $value && abs($value) <= 9007199254740991
            && $value >= PHP_INT_MIN && $value < PHP_INT_MAX) {
            $value = (int) $value;
            $rules[$key . ':number'] = ['parameter' => $key, 'rule' => 'equal-numeric-value'];
        }
    }
}
