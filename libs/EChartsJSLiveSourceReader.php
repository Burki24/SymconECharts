<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/EChartsJSLiveArchive.php';
require_once __DIR__ . '/EChartsImportJson.php';

/** A complete query plan is budgeted before its first read; exact duplicate reads are shared. */
final class EChartsJSLiveSourceReader
{
    private array $queries = [];
    private array $cache = [];

    public function __construct(array $queries, private Closure $read, int $totalBudget = 32000)
    {
        foreach ($queries as $query) {
            $this->queries[self::Key($query)] = $query;
        }
        $archived = array_filter($this->queries, static fn (array $query): bool => $query['Mode'] !== 'current');
        if (count($archived) > 63 || $totalBudget < 1 || $totalBudget > 32000) {
            throw new RuntimeException('LIMIT_EXCEEDED: shared query/point budget exceeded.');
        }
        $limit = min(2000, intdiv($totalBudget, max(1, count($archived))));
        foreach ($this->queries as &$query) {
            $query['Limit'] = $query['Mode'] === 'current' ? 1 : $limit;
        }
        unset($query);
    }

    public function Read(array $query): array
    {
        $key = self::Key($query);
        if (!isset($this->queries[$key])) {
            throw new RuntimeException('INVALID_REQUEST: unplanned archive query.');
        }
        if (!isset($this->cache[$key])) {
            $result = ($this->read)($this->queries[$key]);
            if (!is_array($result) || ($result['Truncated'] ?? true) !== false || !is_array($result['Rows'] ?? null)
                || count($result['Rows']) > $this->queries[$key]['Limit'] || ($result['VariableID'] ?? null) !== $query['VariableID']) {
                throw new RuntimeException('LIMIT_EXCEEDED: incomplete or invalid source/archive snapshot.');
            }
            if (($result['VariableType'] ?? null) !== $query['ExpectedVariableType']) {
                throw new RuntimeException('SOURCE_CHANGED: snapshot variable type changed.');
            }
            $this->cache[$key] = $result;
        }
        return $this->cache[$key];
    }

    public static function Query(array $source, string $mode = 'current', ?array $window = null): array
    {
        return ['VariableID'             => $source['variableID'], 'ArchiveID' => $source['archiveID'], 'Mode' => $mode,
            'FromInclusive'              => $window['fromInclusive'] ?? null, 'UntilExclusive' => $window['untilExclusive'] ?? null,
            'AggregationLevel'           => $window['aggregationLevel'] ?? null,
            'ExpectedVariableType'       => $source['variableType'],
            'ExpectedAggregationType'    => $source['archive']['aggregationType'] ?? null,
            'ExpectedCounterIgnoreZeros' => $source['archive']['counterSettings']['ignoreZeros'] ?? null];
    }

    private static function Key(array $query): string
    {
        unset($query['Limit']);
        return EChartsImportJson::Hash($query);
    }
}
