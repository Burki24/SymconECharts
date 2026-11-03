<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveJournalView.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveJournal;
use SymconECharts\EChartsJSLiveJournalView;

function journalReadAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function journalReadThrows(callable $read, string $code): void
{
    try {
        $read();
    } catch (Throwable $exception) {
        journalReadAssert(str_starts_with($exception->getMessage(), $code), $exception->getMessage());
        return;
    }
    throw new RuntimeException('Expected ' . $code);
}
function journalReadEntry(int $sequence, string $payload = ''): array
{
    $key = substr(hash('sha256', 'synthetic-' . $sequence), 0, 32);
    $identity = 'synthetic-installation:' . $sequence;
    return [$key, ['sequence' => $sequence, 'identity' => $identity, 'revision' => str_repeat('a', 64),
        'moduleID'            => '{11111111-1111-4111-8111-111111111111}', 'targetHash' => str_repeat('b', 64),
        'marker'              => EChartsJSLiveJournal::Marker($identity, $key), 'phase' => 'complete', 'status' => 'complete',
        'targetID'            => 20000 + $sequence, 'creationIntent' => null, 'applyIntent' => true, 'pending' => null, 'receipt' => ['tile' => 20000 + $sequence],
        'snapshot'            => ['creationGuard' => str_repeat('c', 64), 'configuration' => ['JSLiveImportModel' => $payload]], 'error' => null]];
}
$journal = ['version' => 1, 'entries' => []];
for ($i = 1; $i <= 40; ++$i) {
    [$key, $entry] = journalReadEntry($i, str_repeat('synthetic private model ', 1600));
    $journal['entries'][$key] = $entry;
}
$journal['entries'] = array_reverse($journal['entries'], true);
$before = EChartsImportJson::Hash($journal);
journalReadAssert(strlen(EChartsImportJson::Canonical($journal)) > 1048576, 'Fixture must exceed the real native return limit.');
$offset = 0;
$revision = '';
$seen = [];
do {
    $page = EChartsJSLiveJournalView::Page($journal, $offset, 7, $revision);
    EChartsJSLiveJournalView::ValidatePage($page, $offset, 7, $revision);
    $encoded = EChartsImportJson::Canonical($page);
    journalReadAssert(
        strlen($encoded) < 131072 && strlen(json_encode($encoded, JSON_THROW_ON_ERROR)) < 1048576,
        'Page exceeds its own or the native encoded return budget.'
    );
    journalReadAssert(!str_contains($encoded, 'synthetic private model') && !str_contains($encoded, 'configuration'), 'Page leaked model/configuration contents.');
    $page = json_decode($encoded, true, 64, JSON_THROW_ON_ERROR);
    EChartsJSLiveJournalView::ValidatePage($page, $offset, 7, $revision);
    foreach ($page['entries'] as $entry) {
        $key = $entry['entryID'];
        journalReadAssert(!isset($seen[$key]), 'Duplicate journal entry on continuation.');
        $seen[$key] = $entry['sequence'];
    }
    $revision = $page['revision'];
    $offset = $page['nextOffset'];
} while ($offset !== null);
journalReadAssert(
    array_values($seen) === range(1, 40) && EChartsImportJson::Hash($journal) === $before,
    'Pagination lost chronological entries or changed durable state.'
);
$first = EChartsJSLiveJournalView::Page($journal, 0, 7, '');
$changed = $journal;
$changed['entries'][array_key_first($changed['entries'])]['error'] = 'TARGET_CHANGED';
journalReadThrows(fn () => EChartsJSLiveJournalView::Page($changed, 7, 7, $first['revision']), 'JOURNAL_CHANGED:');
foreach ([[-1, 7, ''], [41, 7, ''], [0, 0, ''], [0, 51, ''], [7, 7, ''], [0, 7, 'invalid']] as [$offset, $limit, $revision]) {
    journalReadThrows(fn () => EChartsJSLiveJournalView::Page($journal, $offset, $limit, $revision), 'PACKAGE_INVALID:');
}
$last = EChartsJSLiveJournalView::Page($journal, 40, 7, $first['revision']);
journalReadAssert($last['entries'] === [] && $last['nextOffset'] === null, 'Exact end must be an empty final page.');
foreach (['offset', 'total', 'nextOffset', 'revision', 'entries'] as $field) {
    $bad = $first;
    $bad[$field] = match ($field) {
        'offset' => 1, 'total' => 1001, 'nextOffset' => 0, 'revision' => 'bad', 'entries' => []
    };
    journalReadThrows(fn () => EChartsJSLiveJournalView::ValidatePage($bad, 0, 7, ''), 'JOURNAL_INVALID:');
}
journalReadThrows(fn () => EChartsJSLiveJournalView::Full($journal), 'LIMIT_EXCEEDED:');
$small = ['version' => 1, 'entries' => []];
journalReadAssert(EChartsJSLiveJournalView::Full($small) === EChartsImportJson::Canonical($small), 'Small legacy reads changed shape.');
$empty = EChartsJSLiveJournalView::Page($small, 0, 50, '');
EChartsJSLiveJournalView::ValidatePage($empty, 0, 50, '');
journalReadAssert($empty['total'] === 0 && $empty['nextOffset'] === null, 'Empty journal pagination failed.');
for ($i = 1; $i <= 1000; ++$i) {
    [$key, $entry] = journalReadEntry($i);
    $small['entries'][$key] = $entry;
}
$offset = 0;
$revision = '';
$count = 0;
do {
    $page = EChartsJSLiveJournalView::Page($small, $offset, 50, $revision);
    EChartsJSLiveJournalView::ValidatePage($page, $offset, 50, $revision);
    $count += count($page['entries']);
    $revision = $page['revision'];
    $offset = $page['nextOffset'];
} while ($offset !== null);
journalReadAssert($count === 1000, 'Maximum journal could not be read completely.');
$oversized = ['version' => 1, 'entries' => []];
[$key, $entry] = journalReadEntry(1);
$entry['identity'] = str_repeat('synthetic-identity-', 10000);
$entry['marker'] = EChartsJSLiveJournal::Marker($entry['identity'], $key);
$oversized['entries'][$key] = $entry;
journalReadThrows(fn () => EChartsJSLiveJournalView::Page($oversized, 0, 50, ''), 'LIMIT_EXCEEDED:');
echo "JSLive journal reads: native-size regression, 1000 entries, stable revision, strict continuation and metadata-only pages verified.\n";
