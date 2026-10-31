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
        $this->objects[$id][$field] = $value;
        $this->Checkpoint();
    }
    public function Apply(int $id): void
    {
        ++$this->applies;
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
echo 'JSLive journal: confirmed creation, drift protection, explicit revision and ' . $checkpoints . " interruption checkpoints passed.\n";
