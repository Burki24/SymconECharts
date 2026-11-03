<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use Throwable;

/** Pure comparison planning; archive reads and presentation remain separate. */
final class HeatmapComparison
{
    public static function Sources(string $json): array
    {
        try {
            if (strlen($json) > 65536) {
                throw new InvalidArgumentException();
            }
            $rows = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($rows) || !array_is_list($rows) || count($rows) < 1 || count($rows) > 4) {
                throw new InvalidArgumentException();
            }
            $sources = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_int($row['VariableID'] ?? null) || $row['VariableID'] < 1
                    || (array_key_exists('Label', $row) && (!is_string($row['Label']) || mb_strlen($row['Label']) > 120))
                    || (array_key_exists('PeriodOffset', $row) && (!is_int($row['PeriodOffset']) || $row['PeriodOffset'] < 0 || $row['PeriodOffset'] > 120))
                    || (array_key_exists('UseOwnPeriod', $row) && !is_bool($row['UseOwnPeriod']))
                    || (array_key_exists('AbsoluteStart', $row) && !is_string($row['AbsoluteStart']))
                    || (array_key_exists('AbsoluteEnd', $row) && !is_string($row['AbsoluteEnd']))) {
                    throw new InvalidArgumentException();
                }
                $sources[] = [
                    'VariableID'    => $row['VariableID'], 'Label' => trim($row['Label'] ?? ''),
                    'PeriodOffset'  => $row['PeriodOffset'] ?? 0, 'UseOwnPeriod' => $row['UseOwnPeriod'] ?? false,
                    'AbsoluteStart' => $row['AbsoluteStart'] ?? '{}', 'AbsoluteEnd' => $row['AbsoluteEnd'] ?? '{}'
                ];
            }
            return $sources;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Configure one to four valid Heatmap comparison views.', 0, $exception);
        }
    }

    public static function Plan(array $sources, string $range, array $settings, int $offset, int $now, string $timezone): array
    {
        $panels = [];
        $requests = [];
        $maximumOffset = 120;
        foreach ($sources as $source) {
            $panelRange = $source['UseOwnPeriod'] ? 'absolute' : $range;
            $panelSettings = $source['UseOwnPeriod'] ? [
                'AbsoluteStart' => $source['AbsoluteStart'], 'AbsoluteEnd' => $source['AbsoluteEnd']
            ] : $settings;
            $query = HeatmapWindow::Resolve($panelRange, $offset + $source['PeriodOffset'], $now, $timezone, $panelSettings);
            $maximumOffset = min(
                $maximumOffset,
                HeatmapWindow::MaximumOffset($panelRange, $now, $timezone, $panelSettings) - $source['PeriodOffset']
            );
            $keys = [];
            foreach ($query['Empty'] ? [] : HeatmapWindow::ArchiveChunks($query['StartTimestamp'], $query['EndTimestamp']) as $chunk) {
                $key = $source['VariableID'] . ':' . $chunk['StartTimestamp'] . ':' . $chunk['EndTimestamp'];
                $keys[] = $key;
                $requests[$key] = ['VariableID' => $source['VariableID'], 'Chunk' => $chunk, 'Limit' => $query['Limit']];
            }
            $panels[] = ['Source' => $source, 'Range' => $panelRange, 'Query' => $query, 'RequestKeys' => $keys];
        }
        if (count($requests) > HeatmapWindow::MAXIMUM_ARCHIVE_REQUESTS) {
            throw new InvalidArgumentException('Heatmap comparison views share at most seven archive queries. Choose shorter periods or fewer views.');
        }
        return ['Panels' => $panels, 'Requests' => $requests, 'MaximumOffset' => max(0, $maximumOffset)];
    }
}
