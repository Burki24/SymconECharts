<?php

declare(strict_types=1);

// Optional development comparison against an explicitly supplied, read-only JSLive checkout.
// No source code from that GPL library is copied into the shipped target implementation.
require_once __DIR__ . '/../libs/EChartsJSLiveTime.php';
require_once __DIR__ . '/../EChartsRadarHistory/RadarHistoryData.php';

use SymconECharts\EChartsJSLiveTime;
use SymconECharts\RadarHistoryData;

$reference = $argv[1] ?? '';
if (!is_file($reference . '/SymconJSLiveChart/module.php')) {
    throw new RuntimeException('Provide the local JSLive checkout as the first argument.');
}
class IPSModuleStrict
{
    public function __construct(protected int $InstanceID)
    {
    }
    public function ReadPropertyBoolean(string $name): bool
    {
        return (bool) ($GLOBALS['legacyProperties'][$name] ?? false);
    }
    public function ReadPropertyInteger(string $name): int
    {
        return $GLOBALS['legacyProperties'][$name] ?? 0;
    }
    public function SendDebug(string $message, string $data, int $format): void
    {
    }
    protected function GetValue(string $ident): mixed
    {
        return $GLOBALS['legacyControls'][$ident];
    }
}
function IPS_GetInstanceListByModuleID(string $module): array
{
    return [2];
}
function AC_GetAggregationType(int $archive, int $variable): int
{
    return $GLOBALS['legacyCounter'] ? 1 : 0;
}
function AC_GetLoggedValues(int $archive, int $variable, int $start, int $end, int $limit): array
{
    $rows = $GLOBALS['legacyRowsByVariable'][$variable] ?? $GLOBALS['legacyRows'];
    return array_values(array_filter($rows, static fn (array $row): bool => $row['TimeStamp'] >= $start && $row['TimeStamp'] <= $end));
}
function AC_GetAggregatedValues(int $archive, int $variable, int $level, int $start, int $end, int $limit): array
{
    return AC_GetLoggedValues($archive, $variable, $start, $end, $limit);
}
function GetValue(int $id): mixed
{
    return $GLOBALS['legacyCurrent'];
}
function IPS_VariableExists(int $id): bool
{
    return in_array($id, [13000, 13001], true);
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => $GLOBALS['legacyType'] ?? 2];
}

require_once $reference . '/SymconJSLiveChart/module.php';
require_once $reference . '/SymconJSLiveRadarChart/module.php';
date_default_timezone_set('Europe/Berlin');
$fixtures = [];
$checks = 0;
foreach (['SymconJSLiveChart' => 'cartesian-history', 'SymconJSLiveRadarChart' => 'radar-history'] as $class => $family) {
    $module = new $class(12000);
    foreach (['2024-02-29 12:34:56', '2026-03-29 03:34:56', '2026-10-25 03:34:56', '2025-01-01 12:34:56', '2026-12-31 23:59:59'] as $date) {
        $now = strtotime($date);
        foreach ([false, true] as $relative) {
            for ($period = 0; $period <= 7; ++$period) {
                $GLOBALS['legacyControls'] = ['Period' => $period, 'Relativ' => $relative, 'Now' => true];
                $time = ['timezone' => 'Europe/Berlin', 'selection' => array_map(static fn (mixed $value): array => ['value' => $value], $GLOBALS['legacyControls'])];
                $original = (new ReflectionMethod($class, 'GetCorrectStartDate'))->invoke($module, $now);
                foreach ([0, 1, 2] as $offset) {
                    $expected = $offset === 0 ? ['start' => $original['start'], 'end' => $original['end'], 'interval' => 0]
                        : (new ReflectionMethod($class, 'GetOffsetDate'))->invoke($module, $original['start'], $original['end'], $offset);
                    $actual = EChartsJSLiveTime::Window($time, $family, $now, $offset);
                    $values = [$actual['fromInclusive'], $actual['untilExclusive'], $actual['displayOffsetSeconds'], $actual['aggregationLevel']];
                    $wanted = [$expected['start'], $expected['end'] + 1, $expected['interval'], $original['stufe']];
                    if ($values !== $wanted) {
                        throw new RuntimeException(json_encode([$class, $date, $relative, $period, $offset, $wanted, $values], JSON_THROW_ON_ERROR));
                    }
                    ++$checks;
                    $fixtures[] = ['family' => $family, 'date' => $date, 'relative' => $relative, 'period' => $period, 'offset' => $offset, 'expected' => $wanted];
                }
            }
        }
    }
}
$module = new SymconJSLiveChart(12000);
foreach ([false, true] as $counter) {
    foreach (['raw', 'aggregated'] as $mode) {
        foreach ([[], [['TimeStamp' => 300, 'Value' => 13.456, 'Avg' => 1.51], ['TimeStamp' => 200, 'Value' => 0.0, 'Avg' => 2.25], ['TimeStamp' => 100, 'Value' => 20.0, 'Avg' => 3.12]]] as $rows) {
            foreach ([1, 2, 3] as $step) {
                $GLOBALS['legacyControls'] = ['Period' => 5, 'Relativ' => true];
                $GLOBALS['legacyCounter'] = $counter;
                $GLOBALS['legacyProperties'] = ['data_precision' => 2, 'data_highResSteps' => $step];
                $GLOBALS['legacyRows'] = $rows;
                $GLOBALS['legacyCurrent'] = 11.125;
                $before = time();
                $original = (new ReflectionMethod($module, 'GetArchivData'))->invoke($module, 13000, $mode === 'raw' ? 5 : 6, 0, 1, 400, 0);
                $after = time();
                $rule = ['mode'     => $mode, 'period' => 5, 'counter' => $counter, 'decimation' => $step, 'precision' => $counter ? 5 : 2,
                    'fromInclusive' => 1, 'displayOffsetSeconds' => 0];
                $actual = EChartsJSLiveTime::Cartesian($rows, $rule, $GLOBALS['legacyCurrent'], $before);
                foreach ($original as &$point) {
                    unset($point['c']);
                }
                unset($point);
                // The reference uses its own real clock for an empty-archive fallback.
                if ($rows === [] && $original[count($original) - 1]['x'] !== $before * 1000) {
                    if ($original[count($original) - 1]['x'] !== $after * 1000) {
                        throw new RuntimeException('Unexpected reference clock.');
                    }
                    $actual[count($actual) - 1]['x'] = $after * 1000;
                }
                if ($actual != $original) {
                    throw new RuntimeException(json_encode([$rule, $actual, $original], JSON_THROW_ON_ERROR));
                }
                ++$checks;
            }
        }
    }
}
if (isset($argv[2])) {
    file_put_contents($argv[2], json_encode($fixtures, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
}
$radarFixtures = [];
$module = new SymconJSLiveRadarChart(12000);
foreach ([1, 2] as $type) {
    foreach ([false, true] as $useReference) {
        foreach (['counter', 'average'] as $reducer) {
            foreach (['raw', 'aggregated'] as $mode) {
                $settings = ['data_minValue' => -20, 'data_maxValue' => 40, 'data_sections' => 12, 'data_precision' => 1];
                $labels = [-20, -10, 0, 10, 20, 40];
                $values = [['TimeStamp' => 400, 'Value' => 2.2, 'Avg' => 2.2], ['TimeStamp' => 300, 'Value' => -1.0, 'Avg' => -1.0],
                    ['TimeStamp' => 200, 'Value' => 0.0, 'Avg' => 0.0], ['TimeStamp'        => 100, 'Value' => 15.0, 'Avg' => 15.0]];
                $references = [['TimeStamp' => 400, 'Value' => 100.0, 'Avg' => 100.0], ['TimeStamp' => 300, 'Value' => 5.0, 'Avg' => 5.0],
                    ['TimeStamp' => 200, 'Value' => 0.0, 'Avg' => 0.0], ['TimeStamp'                => 100, 'Value' => -10.0, 'Avg' => -10.0]];
                $GLOBALS['legacyRowsByVariable'] = [13000 => $values, 13001 => $references];
                $GLOBALS['legacyProperties'] = $settings;
                $GLOBALS['legacyControls'] = ['Period' => $mode === 'raw' ? 6 : 5, 'Relativ' => true];
                $GLOBALS['legacyType'] = $type;
                $original = (new ReflectionMethod($module, 'GetArchivData'))->invoke($module, $labels, 13000, $useReference ? 13001 : 0, 0, 1, 400, 0, $reducer);
                $actual = RadarHistoryData::Reduce($labels, $values, $useReference ? $references : $values, $mode, $reducer, $useReference, $type, $settings);
                if ($actual !== $original) {
                    throw new RuntimeException('Radar reference reducer differs: ' . json_encode([$actual, $original]));
                }
                $radarFixtures[] = compact('labels', 'values', 'references', 'mode', 'reducer', 'useReference', 'type', 'settings') + ['expected' => $original];
                ++$checks;
            }
        }
    }
}
if (isset($argv[2])) {
    file_put_contents(dirname($argv[2]) . '/jslive-radar-rules.json', json_encode($radarFixtures, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION) . "\n");
}
echo 'JSLive independent reference comparisons: ' . $checks . " passed.\n";
