<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsJSLiveJournal.php';

use SymconECharts\EChartsImportJson;
use SymconECharts\EChartsJSLiveImportKernel;
use SymconECharts\EChartsJSLiveJournal;

set_error_handler(static function (int $severity, string $message): never
{ throw new RuntimeException($message); });

final class ImportJournalKernel implements EChartsJSLiveImportKernel
{
    public array $journal = ['version' => 1, 'entries' => []];
    public array $objects = [];
    public array $plannedConfiguration = [];
    public int $stages = 0;
    public bool $interruptAfterStage = false;
    public int $creates = 0;
    public int $applies = 0;
    public int $calls = 0;
    public ?int $crashAfter = null;
    public bool $busy = false;
    public bool $locked = false;
    public bool $stale = false;
    public bool $collision = false;
    public function Lock(): bool
    {
        return !$this->busy && ($this->locked = true);
    }
    public function Unlock(): void
    {
        $this->locked = false;
    }
    public function Load(): array
    {
        return $this->journal;
    }
    public function Revalidate(array $plan): void
    {
        if (!$this->locked || $this->stale) {
            throw new RuntimeException('PLAN_CHANGED: source changed under lock');
        }
    }
    public function AssertIdentity(string $identity, array $entries): void
    {
        if ($this->collision) {
            throw new RuntimeException('TARGET_CHANGED: foreign journal reservation');
        }
    }
    public function Save(array $journal): void
    {
        $this->journal = json_decode(EChartsImportJson::Canonical($journal), true, 64, JSON_THROW_ON_ERROR);
        $this->Checkpoint();
    }
    public function Inventory(string $moduleID): array
    {
        return array_keys($this->objects);
    }
    public function Find(string $marker): array
    {
        return array_keys(array_filter($this->objects, static fn (array $o): bool => $o['info'] === $marker));
    }
    public function Inspect(int $id): array
    {
        return $this->objects[$id] ?? throw new RuntimeException('Missing object');
    }
    public function Create(string $moduleID): int
    {
        $id = 20000 + ++$this->creates;
        $this->objects[$id] = ['moduleID' => $moduleID, 'name' => 'New', 'parentID' => 0, 'connectionID' => 0,
            'info'                        => '', 'children' => [], 'configuration' => ['Title' => '', 'SourceVariableID' => 0]];
        $this->objects[$id]['creationGuard'] = \SymconECharts\EChartsJSLiveTargetSnapshot::CreationHash($this->objects[$id]);
        $this->Checkpoint();
        return $id;
    }
    public function Mark(int $id, string $marker): void
    {
        $this->objects[$id]['info'] = $marker;
        $this->Checkpoint();
    }
    public function Change(int $id, string $field, mixed $value): void
    {
        if ($field === 'configuration') {
            ++$this->stages;
            $this->plannedConfiguration[$id] = $value;
        } else {
            $this->objects[$id][$field] = $value;
        }
        $this->Checkpoint();
        if ($field === 'configuration' && $this->interruptAfterStage) {
            throw new RuntimeException('Simulated interruption after staging and before activation');
        }
    }
    public function HasChanges(int $id): bool
    {
        return isset($this->plannedConfiguration[$id])
            && EChartsImportJson::Hash($this->plannedConfiguration[$id]) !== EChartsImportJson::Hash($this->objects[$id]['configuration']);
    }
    public function Apply(int $id): void
    {
        ++$this->applies;
        if (isset($this->plannedConfiguration[$id])) {
            $this->objects[$id]['configuration'] = $this->plannedConfiguration[$id];
            unset($this->plannedConfiguration[$id]);
        }
        $this->objects[$id]['children'] = [$id + 10000];
        $this->Checkpoint();
    }
    public function Verify(int $id): array
    {
        $this->Checkpoint();
        return ['ipsview' => $id + 1];
    }
    private function Checkpoint(): void
    {
        if (++$this->calls === $this->crashAfter) {
            throw new RuntimeException('Simulated interruption');
        }
    }
}

function journalAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function journalThrows(callable $work, string $prefix): void
{
    try {
        $work();
    } catch (RuntimeException $e) {
        journalAssert(str_starts_with($e->getMessage(), $prefix), $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected ' . $prefix);
}

$chart = ['identity' => 'synthetic-installation:instance:12000', 'revision' => str_repeat('a', 64), 'blocked' => false,
    'differences'    => ['title-font-approximation'], 'target' => ['moduleId' => '{11111111-1111-4111-8111-111111111111}',
        'name'                                                                => 'Imported', 'parentID' => 15000, 'gatewayID' => 16000, 'configuration' => ['Title' => 'Kept', 'SourceVariableID' => 13000]]];
$plan = ['planHash' => str_repeat('b', 64), 'charts' => ['instance:12000' => $chart]];
$confirm = ['planHash' => $plan['planHash'], 'charts' => [['chartKey' => 'instance:12000', 'accept' => $chart['differences']]]];
$kernel = new ImportJournalKernel();
$engine = new EChartsJSLiveJournal($kernel);
$result = $engine->Execute($plan, $confirm);
journalAssert($result['charts']['instance:12000']['status'] === 'complete' && $kernel->creates === 1, 'Confirmed import must complete.');
$journalID = $result['charts']['instance:12000']['journalID'];
$id = $result['charts']['instance:12000']['targetID'];
$again = $engine->Execute($plan, $confirm);
journalAssert($again['charts']['instance:12000']['targetID'] === $id && $kernel->creates === 1 && $kernel->applies === 1, 'Retry must reuse target without ApplyChanges.');
$kernel->objects[$id]['configuration']['Title'] = 'User change';
$edited = $kernel->objects;
$changed = $engine->Execute($plan, $confirm);
journalAssert($changed['charts']['instance:12000']['error'] === 'TARGET_CHANGED' && $kernel->objects === $edited, 'Never overwrite changed targets.');
$bad = $confirm;
$bad['charts'][0]['accept'] = [];
journalThrows(fn () => $engine->Execute($plan, $bad), 'IMPORT_BLOCKED:');
$bad = $confirm;
$bad['planHash'] = str_repeat('c', 64);
journalThrows(fn () => $engine->Execute($plan, $bad), 'PLAN_CHANGED:');
$kernel->busy = true;
journalThrows(fn () => $engine->Execute($plan, $confirm), 'IMPORT_BUSY:');
$kernel->busy = false;

// The actual version-1 failure shape: phase created, desired pending snapshot, unchanged active defaults.
// Synthetic identities only; the retained installation's target ID or journal contents must not enter fixtures.
foreach (['staged', 'stage-lost', 'activated-on-update', 'activated-new-draft', 'active-edited', 'draft-edited'] as $mode) {
    $legacy = new ImportJournalKernel();
    $legacy->interruptAfterStage = true;
    $failed = (new EChartsJSLiveJournal($legacy))->Execute($plan, $confirm)['charts']['instance:12000'];
    $legacyID = $failed['targetID'];
    $legacyEntryID = $failed['journalID'];
    journalAssert(
        $failed['status'] === 'failed' && $legacy->HasChanges($legacyID)
        && $legacy->objects[$legacyID]['configuration']['Title'] === '' && $legacy->applies === 0,
        'SetConfiguration must leave active settings unchanged before ApplyChanges.'
    );
    $legacy->journal['entries'][$legacyEntryID]['phase'] = 'created';
    $legacy->journal['entries'][$legacyEntryID]['applyIntent'] = false;
    $legacy->journal['entries'][$legacyEntryID]['error'] = 'TARGET_CHANGED';
    $legacy->interruptAfterStage = false;
    $guard = $legacy->objects[$legacyID]['creationGuard'];
    if ($mode === 'stage-lost') {
        unset($legacy->plannedConfiguration[$legacyID]);
    }
    if (in_array($mode, ['activated-on-update', 'activated-new-draft'], true)) {
        $legacy->Apply($legacyID);
    }
    if ($mode === 'activated-new-draft') {
        $legacy->plannedConfiguration[$legacyID] = $legacy->objects[$legacyID]['configuration'];
        $legacy->plannedConfiguration[$legacyID]['Title'] = 'Owner draft after activation';
    }
    if ($mode === 'active-edited') {
        $legacy->objects[$legacyID]['configuration']['Title'] = 'Owner active title';
    }
    if ($mode === 'draft-edited') {
        $legacy->plannedConfiguration[$legacyID]['Title'] = 'Owner planned title';
    }
    $stages = $legacy->stages;
    $applies = $legacy->applies;
    $resumed = (new EChartsJSLiveJournal($legacy))->Execute($plan, $confirm)['charts']['instance:12000'];
    journalAssert(
        $resumed['targetID'] === $legacyID && $resumed['journalID'] === $legacyEntryID && $legacy->creates === 1
        && $legacy->objects[$legacyID]['creationGuard'] === $guard && $legacy->journal['version'] === 1,
        'Compatible recovery must keep the existing identity, target, guard and journal format.'
    );
    if ($mode === 'activated-new-draft') {
        journalAssert($resumed['error'] === 'TARGET_CHANGED' && $legacy->stages === $stages && $legacy->applies === $applies
            && $legacy->plannedConfiguration[$legacyID]['Title'] === 'Owner draft after activation', 'A fresh draft after activation must remain unapplied.');
    } elseif ($mode === 'active-edited') {
        journalAssert($resumed['error'] === 'TARGET_CHANGED' && $legacy->stages === $stages && $legacy->applies === $applies
            && $legacy->objects[$legacyID]['configuration']['Title'] === 'Owner active title', 'Active owner changes must stop before any configuration write or apply.');
    } elseif ($mode === 'draft-edited') {
        journalAssert($resumed['error'] === 'TARGET_CHANGED' && $legacy->stages === $stages
            && $legacy->objects[$legacyID]['configuration']['Title'] === 'Owner planned title', 'A divergent recorded draft must never be overwritten by restaging or corrective writes.');
        $again = (new EChartsJSLiveJournal($legacy))->Execute($plan, $confirm)['charts']['instance:12000'];
        journalAssert($again['error'] === 'TARGET_CHANGED' && $legacy->applies === $applies + 1, 'A mismatching activation must not be retried against changed active settings.');
    } else {
        journalAssert($resumed['status'] === 'complete' && !$legacy->HasChanges($legacyID)
            && EChartsImportJson::Hash($legacy->objects[$legacyID]['configuration']) === EChartsImportJson::Hash($chart['target']['configuration']), 'Version-1 recovery failed: ' . $mode);
        journalAssert($legacy->stages === $stages + ($mode === 'stage-lost' ? 1 : 0), 'Recovery must reuse a staged or already active configuration.');
        $applies = $legacy->applies;
        (new EChartsJSLiveJournal($legacy))->Execute($plan, $confirm);
        journalAssert($legacy->applies === $applies && $legacy->creates === 1, 'Completed recovery must stay idempotent.');
    }
}

// Old Create wrote a new creation hash on reload. Only that exact, owned, otherwise unchanged shape may recover.
foreach (['staged', 'stage-lost', 'activated', 'legacy-stage-lost', 'legacy-activated', 'complete', 'owner-name', 'owner-design', 'owner-parent', 'owner-marker', 'random-guard'] as $mode) {
    $reloaded = new ImportJournalKernel();
    $reloaded->interruptAfterStage = $mode !== 'complete';
    $row = (new EChartsJSLiveJournal($reloaded))->Execute($plan, $confirm)['charts']['instance:12000'];
    $targetID = $row['targetID'];
    $entryID = $row['journalID'];
    $originalGuard = $reloaded->objects[$targetID]['creationGuard'];
    $reloaded->interruptAfterStage = false;
    if (str_starts_with($mode, 'legacy-')) {
        $reloaded->journal['entries'][$entryID]['phase'] = 'created';
        $reloaded->journal['entries'][$entryID]['applyIntent'] = false;
    }
    if (in_array($mode, ['stage-lost', 'legacy-stage-lost'], true)) {
        unset($reloaded->plannedConfiguration[$targetID]);
    }
    if (in_array($mode, ['activated', 'legacy-activated'], true)) {
        $reloaded->Apply($targetID);
    }
    if ($mode === 'owner-name') {
        $reloaded->objects[$targetID]['name'] = 'Owner name';
    }
    if ($mode === 'owner-design') {
        $reloaded->objects[$targetID]['configuration']['Title'] = 'Owner title';
    }
    if ($mode === 'owner-parent') {
        $reloaded->objects[$targetID]['parentID'] = 15001;
    }
    if ($mode === 'owner-marker') {
        $reloaded->objects[$targetID]['info'] = 'Owner info';
    }
    $reloaded->objects[$targetID]['creationGuard'] = $mode === 'random-guard' ? str_repeat('f', 64)
        : \SymconECharts\EChartsJSLiveTargetSnapshot::CreationHash($reloaded->objects[$targetID]);
    $beforeReload = $reloaded->objects;
    $applies = $reloaded->applies;
    $stages = $reloaded->stages;
    $resumed = (new EChartsJSLiveJournal($reloaded))->Execute($plan, $confirm)['charts']['instance:12000'];
    if (in_array($mode, ['staged', 'stage-lost', 'activated', 'legacy-stage-lost', 'legacy-activated', 'complete'], true)) {
        journalAssert($resumed['status'] === 'complete' && $resumed['targetID'] === $targetID
            && $resumed['journalID'] === $entryID && $reloaded->creates === 1, 'Reload recovery lost the retained target: ' . $mode);
        journalAssert(
            $reloaded->journal['entries'][$entryID]['originalCreationGuard'] === $originalGuard,
            'Reload recovery must retain the historical creation proof.'
        );
        $applies = $reloaded->applies;
        (new EChartsJSLiveJournal($reloaded))->Execute($plan, $confirm);
        journalAssert($reloaded->applies === $applies, 'Reload recovery must remain idempotent.');
    } else {
        journalAssert($resumed['error'] === 'TARGET_CHANGED' && $reloaded->objects === $beforeReload
            && $reloaded->stages === $stages && $reloaded->applies === $applies, 'Reload recovery masked owner changes: ' . $mode);
    }
}

$draftKernel = new ImportJournalKernel();
$draftEngine = new EChartsJSLiveJournal($draftKernel);
$completed = $draftEngine->Execute($plan, $confirm)['charts']['instance:12000'];
$draftID = $completed['targetID'];
$draftKernel->plannedConfiguration[$draftID] = $draftKernel->objects[$draftID]['configuration'];
$draftKernel->plannedConfiguration[$draftID]['Title'] = 'Unapplied owner customization';
$draftBefore = $draftKernel->plannedConfiguration;
$applies = $draftKernel->applies;
$draftResult = $draftEngine->Execute($plan, $confirm)['charts']['instance:12000'];
journalAssert(
    $draftResult['error'] === 'TARGET_CHANGED' && $draftKernel->plannedConfiguration === $draftBefore
    && $draftKernel->applies === $applies && $draftKernel->objects[$draftID]['configuration']['Title'] === 'Kept',
    'A new draft on a completed target must stay unmodified and unapplied.'
);
$new = $plan;
$new['charts']['instance:12000']['revision'] = str_repeat('c', 64);
journalThrows(fn () => $engine->Execute($new, $confirm), 'TARGET_CHANGED:');
$separate = $confirm;
$separate['charts'][0]['separateFrom'] = $journalID;
$result = $engine->Execute($new, $separate);
journalAssert($kernel->creates === 2 && $kernel->objects[$id] === $edited[$id], 'Explicit separate creation must preserve old target.');

// Interrupt after every durable write/kernel operation. Recover without duplicates or blind re-creation.
$reference = new ImportJournalKernel();
(new EChartsJSLiveJournal($reference))->Execute($plan, $confirm);
$checkpoints = $reference->calls;
for ($stop = 1; $stop <= $checkpoints; ++$stop) {
    $k = new ImportJournalKernel();
    $k->crashAfter = $stop;
    $e = new EChartsJSLiveJournal($k);
    try {
        $e->Execute($plan, $confirm);
    } catch (RuntimeException) {
    }
    $k->crashAfter = null;
    $r = $e->Execute($plan, $confirm);
    $row = $r['charts']['instance:12000'];
    if ($row['error'] === 'RECOVERY_REQUIRED') {
        journalAssert($k->creates <= 1, 'Ambiguous creation must not create again.');
        $entry = $k->journal['entries'][$row['journalID']];
        $e->Resolve($row['journalID'], array_key_first($k->objects) ?? 0, $entry['revision']);
        $r = $e->Execute($plan, $confirm);
    }
    journalAssert($r['charts']['instance:12000']['status'] === 'complete' && $k->creates === 1 && !$k->locked, 'Recovery failed after checkpoint ' . $stop);
}

$stale = new ImportJournalKernel();
$stale->stale = true;
journalThrows(fn () => (new EChartsJSLiveJournal($stale))->Execute($plan, $confirm), 'PLAN_CHANGED:');
journalAssert($stale->creates === 0 && $stale->journal['entries'] === [] && !$stale->locked, 'Stale under-lock validation must precede every write.');
$foreign = new ImportJournalKernel();
$foreign->collision = true;
journalThrows(fn () => (new EChartsJSLiveJournal($foreign))->Execute($plan, $confirm), 'TARGET_CHANGED:');
journalAssert($foreign->creates === 0 && !$foreign->locked, 'Foreign identity reservation must block duplicate creation.');

$editedCreation = new ImportJournalKernel();
$editedCreation->crashAfter = 3;
$recovery = new EChartsJSLiveJournal($editedCreation);
$gap = $recovery->Execute($plan, $confirm)['charts']['instance:12000'];
$editedCreation->crashAfter = null;
$differentGapPlan = $plan;
$differentGapPlan['charts']['instance:12000']['revision'] = str_repeat('f', 64);
$differentGapChoice = $confirm;
$differentGapChoice['charts'][0]['separateFrom'] = $gap['journalID'];
journalThrows(fn () => $recovery->Execute($differentGapPlan, $differentGapChoice), 'RECOVERY_REQUIRED:');
$candidate = array_key_first($editedCreation->objects);
$editedCreation->objects[$candidate]['name'] = 'Owner edited unclaimed object';
$unchanged = $editedCreation->objects;
journalThrows(fn () => $recovery->Resolve($gap['journalID'], $candidate, $chart['revision']), 'RECOVERY_REQUIRED:');
journalAssert($editedCreation->objects === $unchanged && $editedCreation->creates === 1, 'Explicit recovery must preserve modified unclaimed objects.');

$separateKernel = new ImportJournalKernel();
$separateEngine = new EChartsJSLiveJournal($separateKernel);
$first = $separateEngine->Execute($plan, $confirm)['charts']['instance:12000'];
$separateChoice = $confirm;
$separateChoice['charts'][0]['separateFrom'] = $first['journalID'];
journalThrows(fn () => $separateEngine->Execute($plan, $separateChoice), 'PLAN_CHANGED:');
$separateKernel->objects[$first['targetID']]['configuration']['Title'] = 'Owner customization';
$oldTarget = $separateKernel->objects[$first['targetID']];
$newTarget = $separateEngine->Execute($plan, $separateChoice)['charts']['instance:12000'];
journalAssert($newTarget['status'] === 'complete' && $newTarget['targetID'] !== $first['targetID']
    && $separateKernel->objects[$first['targetID']] === $oldTarget, 'Explicit separate creation for changed same-revision targets must preserve the old target.');
$nextRevision = $plan;
$nextRevision['charts']['instance:12000']['revision'] = str_repeat('d', 64);
$nextChoice = $confirm;
$nextChoice['charts'][0]['separateFrom'] = $newTarget['journalID'];
$nextTarget = $separateEngine->Execute($nextRevision, $nextChoice)['charts']['instance:12000'];
journalAssert($nextTarget['status'] === 'complete' && $separateKernel->creates === 3, 'Chronological revision selection must survive canonical journal key sorting.');

$partialPlan = $plan;
$partialChart = $chart;
$partialChart['identity'] = 'synthetic-installation:instance:12001';
$partialChart['revision'] = str_repeat('e', 64);
$partialChart['target']['name'] = 'Second imported chart';
$partialPlan['charts']['instance:12001'] = $partialChart;
$partialChoice = $confirm;
$partialChoice['charts'][] = ['chartKey' => 'instance:12001', 'accept' => $chart['differences']];
$partialKernel = new ImportJournalKernel();
$partialKernel->crashAfter = $checkpoints + 3;
$partialEngine = new EChartsJSLiveJournal($partialKernel);
$partial = $partialEngine->Execute($partialPlan, $partialChoice);
journalAssert($partial['charts']['instance:12000']['status'] === 'complete' && $partial['charts']['instance:12001']['status'] === 'failed', 'Per-chart partial completion must be retained.');
$partialKernel->crashAfter = null;
$partial = $partialEngine->Execute($partialPlan, $partialChoice);
$gap = $partial['charts']['instance:12001'];
journalAssert($gap['error'] === 'RECOVERY_REQUIRED' && $partialKernel->creates === 2, 'Partial retry must not duplicate either chart.');
$candidates = array_keys($partialKernel->objects);
$partialEngine->Resolve($gap['journalID'], $candidates[1], $partialChart['revision']);
$partial = $partialEngine->Execute($partialPlan, $partialChoice);
journalAssert($partial['charts']['instance:12001']['status'] === 'complete' && $partialKernel->creates === 2, 'Partial batch must resume at its own journal entry.');
echo 'JSLive journal: pending/active settings, compatible version-1 recovery, draft/active drift protection and ' . $checkpoints . " interruption checkpoints passed.\n";
