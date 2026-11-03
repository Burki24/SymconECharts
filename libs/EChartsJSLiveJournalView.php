<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;

require_once __DIR__ . '/EChartsJSLiveJournal.php';

/** Bounded read views. Durable snapshots stay internal; recovery needs identities, revisions and status. */
final class EChartsJSLiveJournalView
{
    public const MAX_PAGE_ENTRIES = 50;
    public const MAX_PAGE_BYTES = 131072;
    public const MAX_FULL_BYTES = 262144;

    public static function Full(array $journal): string
    {
        EChartsJSLiveJournal::Validate($journal);
        $json = EChartsImportJson::Canonical($journal);
        if (strlen($json) > self::MAX_FULL_BYTES) {
            throw new RuntimeException('LIMIT_EXCEEDED: use GetJSLiveImportJournalPage for this journal.');
        }
        return $json;
    }

    public static function Page(array $journal, int $offset, int $limit, string $revision): array
    {
        EChartsJSLiveJournal::Validate($journal);
        $total = count($journal['entries']);
        if ($offset < 0 || $offset > $total || $limit < 1 || $limit > self::MAX_PAGE_ENTRIES
            || ($revision !== '' && preg_match('/^[a-f0-9]{64}$/D', $revision) !== 1) || ($offset > 0 && $revision === '')) {
            throw new InvalidArgumentException('PACKAGE_INVALID: invalid journal page or missing continuation revision.');
        }
        $currentRevision = EChartsImportJson::Hash($journal);
        if ($revision !== '' && !hash_equals($currentRevision, $revision)) {
            throw new RuntimeException('JOURNAL_CHANGED: refresh the first page before continuing.');
        }
        $entries = $journal['entries'];
        uasort($entries, static fn (array $a, array $b): int => $a['sequence'] <=> $b['sequence']);
        $page = ['version' => 1, 'view' => 'summary', 'revision' => $currentRevision, 'total' => $total,
            'offset'       => $offset, 'limit' => $limit, 'nextOffset' => null, 'entries' => []];
        foreach (array_slice($entries, $offset, $limit, true) as $key => $entry) {
            $row = ['entryID' => $key] + array_intersect_key($entry, array_flip(['sequence', 'identity', 'revision', 'moduleID', 'targetHash',
                'marker', 'phase', 'status', 'targetID', 'error', 'applyIntent', 'originalCreationGuard']));
            $row['creationGuard'] = $entry['snapshot']['creationGuard'] ?? null;
            $row['hasPending'] = $entry['pending'] !== null;
            $row['hasReceipt'] = $entry['receipt'] !== null;
            $page['entries'][] = $row;
            if (strlen(EChartsImportJson::Canonical($page)) > self::MAX_PAGE_BYTES - 64) {
                array_pop($page['entries']);
                if ($page['entries'] === []) {
                    throw new RuntimeException('LIMIT_EXCEEDED: journal entry metadata exceeds the page budget.');
                }
                break;
            }
        }
        $next = $offset + count($page['entries']);
        $page['nextOffset'] = $next < $total ? $next : null;
        return $page;
    }

    /** Validate remote pages before using them for cross-gateway duplicate protection. */
    public static function ValidatePage(array $page, int $offset, int $limit, string $revision): void
    {
        if ($offset < 0 || $limit < 1 || $limit > self::MAX_PAGE_ENTRIES
            || ($page['version'] ?? null) !== 1 || ($page['view'] ?? null) !== 'summary'
            || !is_string($page['revision'] ?? null) || preg_match('/^[a-f0-9]{64}$/D', $page['revision']) !== 1
            || ($revision !== '' && !hash_equals($revision, $page['revision']))
            || ($page['offset'] ?? null) !== $offset || ($page['limit'] ?? null) !== $limit
            || !is_int($page['total'] ?? null) || $page['total'] < $offset || $page['total'] > EChartsJSLiveJournal::MAX_ENTRIES
            || !is_array($page['entries'] ?? null) || !array_is_list($page['entries']) || count($page['entries']) > $limit
            || strlen(EChartsImportJson::Canonical($page)) > self::MAX_PAGE_BYTES) {
            throw new RuntimeException('JOURNAL_INVALID: invalid or inconsistent journal page.');
        }
        $next = $offset + count($page['entries']);
        if ($next > $page['total'] || ($offset < $page['total'] && $next === $offset)
            || !array_key_exists('nextOffset', $page) || $page['nextOffset'] !== ($next < $page['total'] ? $next : null)) {
            throw new RuntimeException('JOURNAL_INVALID: invalid journal continuation.');
        }
        $seen = [];
        foreach ($page['entries'] as $entry) {
            $key = $entry['entryID'] ?? null;
            if (!is_array($entry) || !is_string($key) || preg_match('/^[a-f0-9]{32}$/D', $key) !== 1 || isset($seen[$key])
                || ($entry['sequence'] ?? null) !== ++$offset || !is_string($entry['identity'] ?? null)
                || ($entry['marker'] ?? null) !== EChartsJSLiveJournal::Marker($entry['identity'], $key)) {
                throw new RuntimeException('JOURNAL_INVALID: invalid journal summary entry.');
            }
            $seen[$key] = true;
        }
    }
}
