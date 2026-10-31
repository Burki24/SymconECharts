<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

/** Bounded raw transport only. Legacy window/reduction semantics remain in the device model. */
final class EChartsJSLiveArchive
{
    public const OPERATION = 'archive.snapshot.read';

    public static function Read(array $request): array
    {
        $id = $request['VariableID'] ?? null;
        $archive = $request['ArchiveID'] ?? null;
        $mode = $request['Mode'] ?? null;
        $limit = $request['Limit'] ?? null;
        if (!is_int($id) || $id <= 0 || !IPS_VariableExists($id) || !in_array($mode, ['current', 'raw', 'aggregated'], true)
            || !is_int($limit) || $limit < 1 || $limit > 2000) {
            throw new InvalidArgumentException('INVALID_REQUEST: invalid snapshot source, mode or budget.');
        }
        $variable = IPS_GetVariable($id);
        $type = $variable['VariableType'];
        if (!in_array($type, [0, 1, 2], true) || $type !== ($request['ExpectedVariableType'] ?? null)) {
            throw new InvalidArgumentException('INVALID_REQUEST: only Boolean and numeric snapshots are supported.');
        }
        $value = GetValue($id);
        if ((($type === 0) && !is_bool($value)) || (($type === 1) && !is_int($value))
            || (($type === 2) && (!is_float($value) || !is_finite($value)))) {
            throw new RuntimeException('INVALID_VARIABLE_VALUE: invalid current source value.');
        }
        $result = ['VariableID' => $id, 'VariableType' => $type, 'Value' => $value,
            'Timestamp'         => $variable['VariableUpdated'], 'Rows' => [], 'Truncated' => false];
        if ($mode === 'current') {
            return $result;
        }
        $from = $request['FromInclusive'] ?? null;
        $until = $request['UntilExclusive'] ?? null;
        if (!is_int($archive) || $archive <= 0 || !IPS_InstanceExists($archive)
            || IPS_GetInstance($archive)['ModuleInfo']['ModuleID'] !== '{43192F0B-135B-4CE7-A0A7-1475603F3060}'
            || !is_int($from) || !is_int($until) || $from < 1 || $until <= $from || !AC_GetLoggingStatus($archive, $id)) {
            throw new InvalidArgumentException('ARCHIVE_UNAVAILABLE: explicit compatible archive/range is required.');
        }
        $aggregationType = AC_GetAggregationType($archive, $id);
        if (!in_array($aggregationType, [0, 1], true) || $aggregationType !== ($request['ExpectedAggregationType'] ?? null)
            || ($aggregationType === 1 && AC_GetCounterIgnoreZeros($archive, $id) !== ($request['ExpectedCounterIgnoreZeros'] ?? null))) {
            throw new InvalidArgumentException('SOURCE_CHANGED: archive type or counter policy changed.');
        }
        if ($mode === 'aggregated') {
            $level = $request['AggregationLevel'] ?? null;
            if (!is_int($level) || !in_array($level, [0, 1, 2, 3, 4, 5, 6, 8], true) || $type === 0) {
                throw new InvalidArgumentException('INVALID_REQUEST: invalid snapshot aggregation level or type.');
            }
            $rows = AC_GetAggregatedValues($archive, $id, $level, $from, $until - 1, $limit + 1);
            $field = 'Avg';
        } else {
            $rows = AC_GetLoggedValues($archive, $id, $from, $until - 1, $limit + 1);
            $field = 'Value';
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('INVALID_ARCHIVE_DATA: invalid archive response.');
        }
        if (count($rows) > $limit) {
            $result['Truncated'] = true;
            return $result; // Never expose a partial data set as usable history.
        }
        $previous = PHP_INT_MAX;
        foreach ($rows as $row) {
            $timestamp = $row['TimeStamp'] ?? null;
            $v = $row[$field] ?? null;
            if (!is_int($timestamp) || $timestamp <= 0 || $timestamp > $previous
                || (!is_int($v) && !is_float($v) && !($mode === 'raw' && $type === 0 && is_bool($v)))
                || (is_float($v) && !is_finite($v))) {
                throw new RuntimeException('INVALID_ARCHIVE_DATA: invalid or unsorted snapshot row.');
            }
            $previous = $timestamp;
            $result['Rows'][] = ['TimeStamp' => $timestamp, $field => $v];
        }
        return $result;
    }
}
