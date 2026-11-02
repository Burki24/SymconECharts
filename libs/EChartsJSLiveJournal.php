<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/EChartsImportJson.php';
require_once __DIR__ . '/EChartsJSLiveTargetSnapshot.php';

/** The only write boundary used by the confirmed import. Never addresses source objects. */
interface EChartsJSLiveImportKernel
{
    public function Lock(): bool;
    public function Unlock(): void;
    public function Load(): array;
    public function Save(array $journal): void;
    public function Inventory(string $moduleID): array;
    public function Find(string $marker): array;
    public function Inspect(int $id): array;
    public function Create(string $moduleID): int;
    public function Mark(int $id, string $marker): void;
    public function Change(int $id, string $field, mixed $value): void;
    public function HasChanges(int $id): bool;
    public function Apply(int $id): void;
    public function Verify(int $id): array;
    public function Revalidate(array $plan): void;
    public function AssertIdentity(string $identity, array $entries): void;
}

/** Persistent per-chart state machine. Package re-upload is required; no package is stored here. */
final class EChartsJSLiveJournal
{
    public const VERSION = 1;
    public const MAX_BYTES = 4194304;
    public const MAX_ENTRIES = 1000;

    public function __construct(private EChartsJSLiveImportKernel $kernel)
    {
    }

    /** Only a freshly compiled internal plan may reach this method; the public API never accepts target properties. */
    public function Execute(array $plan, array $confirmation): array
    {
        if (($confirmation['planHash'] ?? null) !== $plan['planHash'] || !is_array($confirmation['charts'] ?? null)
            || !array_is_list($confirmation['charts']) || $confirmation['charts'] === [] || count($confirmation['charts']) > 100) {
            throw new RuntimeException('PLAN_CHANGED: confirm the current plan and selected charts.');
        }
        $selected = [];
        foreach ($confirmation['charts'] as $choice) {
            $key = $choice['chartKey'] ?? null;
            if (!is_string($key) || isset($selected[$key]) || !isset($plan['charts'][$key])
                || array_diff(array_keys($choice), ['chartKey', 'accept', 'separateFrom']) !== []) {
                throw new RuntimeException('PACKAGE_INVALID: invalid chart confirmation.');
            }
            $chart = $plan['charts'][$key];
            $accept = $choice['accept'] ?? [];
            if ($chart['blocked'] || !is_array($accept) || !array_is_list($accept)
                || count(array_filter($accept, static fn (mixed $id): bool => !is_string($id) || strlen($id) > 128)) > 0
                || count($accept) !== count(array_unique($accept, SORT_REGULAR))
                || array_diff($chart['differences'], $accept) !== [] || array_diff($accept, $chart['differences']) !== []) {
                throw new RuntimeException('IMPORT_BLOCKED: resolve blockers and accept each concrete difference.');
            }
            $selected[$key] = $choice;
        }
        if (!$this->kernel->Lock()) {
            throw new RuntimeException('IMPORT_BUSY: another import is running.');
        }
        try {
            // Recompile against current source metadata while holding the installation-wide lock.
            $this->kernel->Revalidate($plan);
            $journal = $this->kernel->Load();
            self::Validate($journal);
            $result = [];
            foreach ($selected as $key => $choice) {
                $chart = $plan['charts'][$key];
                $identity = $chart['identity'];
                $this->kernel->AssertIdentity($identity, $journal['entries']);
                $previous = array_filter($journal['entries'], static fn (array $entry): bool => $entry['identity'] === $identity);
                uasort($previous, static fn (array $a, array $b): int => $a['sequence'] <=> $b['sequence']);
                $entryID = null;
                $separateMatched = false;
                foreach ($previous as $candidateID => $candidate) {
                    if ($candidate['revision'] === $chart['revision'] && ($candidate['targetID'] !== null
                        || $candidate['creationIntent'] !== null || $candidateID === array_key_last($previous))) {
                        $entryID = $candidateID;
                    }
                }
                if ($entryID !== null && isset($choice['separateFrom'])) {
                    if ($choice['separateFrom'] !== $entryID) {
                        if (!$this->IsSeparateReplay($previous, $entryID, $choice['separateFrom'], $plan['planHash'])) {
                            throw new RuntimeException('PLAN_CHANGED: separate creation must identify the reviewed previous target.');
                        }
                    } elseif ($previous[$entryID]['targetID'] === null) {
                        throw new RuntimeException('PLAN_CHANGED: separate creation must identify the reviewed previous target.');
                    } else {
                        $old = $previous[$entryID];
                        try {
                            $unchanged = EChartsImportJson::Hash($this->kernel->Inspect($old['targetID'])) === EChartsImportJson::Hash($old['snapshot'])
                                && !$this->kernel->HasChanges($old['targetID']);
                        }
                        catch (Throwable) {
                            $unchanged = false;
                        }
                        if ($unchanged) {
                            throw new RuntimeException('PLAN_CHANGED: unchanged imports must reuse the existing target.');
                        }
                        $separateMatched = true;
                        $entryID = null;
                    }
                }
                if ($entryID === null) {
                    foreach ($previous as $candidate) {
                        if ($candidate['targetID'] === null && $candidate['creationIntent'] !== null) {
                            throw new RuntimeException('RECOVERY_REQUIRED: resolve the previous creation gap before creating a separate revision.');
                        }
                    }
                    if ($previous !== [] && !$separateMatched && ($choice['separateFrom'] ?? null) !== array_key_last($previous)) {
                        throw new RuntimeException('TARGET_CHANGED: a previous revision exists; review a separate creation plan.');
                    }
                    if ($previous === [] && isset($choice['separateFrom'])) {
                        throw new RuntimeException('PLAN_CHANGED: the referenced previous import does not exist.');
                    }
                    $entryID = bin2hex(random_bytes(16));
                    $entry = ['sequence' => count($journal['entries']) + 1, 'identity' => $identity, 'revision' => $chart['revision'], 'chartKey' => $key,
                        'planHash'       => $plan['planHash'], 'moduleID' => $chart['target']['moduleId'],
                        'separateFrom'   => $choice['separateFrom'] ?? null,
                        'targetHash'     => EChartsImportJson::Hash($chart['target']),
                        'marker'         => self::Marker($identity, $entryID),
                        'status'         => 'planned', 'phase' => 'planned', 'targetID' => null,
                        'creationIntent' => null, 'applyIntent' => false, 'snapshot' => null, 'pending' => null, 'receipt' => null, 'error' => null];
                    $journal['entries'][$entryID] = $entry;
                    $this->Persist($journal);
                }
                $entry = &$journal['entries'][$entryID];
                if ($entry['targetHash'] !== EChartsImportJson::Hash($chart['target'])) {
                    throw new RuntimeException('PLAN_CHANGED: the journal target differs from the current plan.');
                }
                try {
                    $this->kernel->Revalidate($plan);
                    $this->Continue($entry, $journal, $chart['target']);
                } catch (Throwable $exception) {
                    $entry['status'] = 'failed';
                    // Never persist arbitrary kernel exception messages or package contents.
                    $entry['error'] = str_starts_with($exception->getMessage(), 'TARGET_CHANGED:') ? 'TARGET_CHANGED'
                        : (str_starts_with($exception->getMessage(), 'RECOVERY_REQUIRED:') ? 'RECOVERY_REQUIRED' : 'IMPORT_FAILED');
                    $this->Persist($journal);
                }
                $result[$key] = ['journalID' => $entryID, 'status' => $entry['status'], 'targetID' => $entry['targetID'],
                    'error'                  => $entry['error'], 'outputs' => $entry['receipt']];
                unset($entry);
            }
            return ['planHash' => $plan['planHash'], 'charts' => $result];
        } finally {
            $this->kernel->Unlock();
        }
    }

    /** A creation/marker gap is resolved only by a separate explicit object assignment. */
    public function Resolve(string $entryID, int $targetID, string $expectedRevision): void
    {
        if (!$this->kernel->Lock()) {
            throw new RuntimeException('IMPORT_BUSY: another import is running.');
        }
        try {
            $journal = $this->kernel->Load();
            self::Validate($journal);
            if (!isset($journal['entries'][$entryID])) {
                throw new RuntimeException('RECOVERY_REQUIRED: unknown journal entry.');
            }
            $entry = &$journal['entries'][$entryID];
            if ($entry['revision'] !== $expectedRevision || $entry['targetID'] !== null
                || $entry['creationIntent'] === null || $entry['phase'] !== 'planned') {
                throw new RuntimeException('RECOVERY_REQUIRED: this journal entry is not awaiting creation recovery.');
            }
            $marked = $this->kernel->Find($entry['marker']);
            if ($marked !== [] && $marked !== [$targetID]) {
                throw new RuntimeException('RECOVERY_REQUIRED: conflicting ownership markers.');
            }
            if ($targetID === 0) {
                $new = array_diff($this->kernel->Inventory($entry['moduleID']), $entry['creationIntent']['before']);
                if ($new !== [] || $marked !== []) {
                    throw new RuntimeException('RECOVERY_REQUIRED: potential target objects still exist.');
                }
                // Explicitly confirmed absence; never inferred by the normal retry path.
                $entry['creationIntent'] = null;
            } else {
                $snapshot = $this->kernel->Inspect($targetID);
                if ($snapshot['moduleID'] !== $entry['moduleID'] || in_array($targetID, $entry['creationIntent']['before'], true)
                    || ($snapshot['info'] !== '' && $snapshot['info'] !== $entry['marker'])
                    || !EChartsJSLiveTargetSnapshot::IsUnchangedCreation($snapshot) || $this->kernel->HasChanges($targetID)) {
                    throw new RuntimeException('RECOVERY_REQUIRED: selected object is not an unclaimed creation candidate.');
                }
                $entry['targetID'] = $targetID;
                $entry['snapshot'] = $snapshot;
                $entry['pending'] = array_replace($snapshot, ['info' => $entry['marker']]);
                $entry['phase'] = 'created';
                $this->Persist($journal);
                $this->kernel->Mark($targetID, $entry['marker']);
                $entry['snapshot'] = $this->kernel->Inspect($targetID);
                $entry['pending'] = null;
            }
            $entry['status'] = $entry['phase'];
            $entry['error'] = null;
            $this->Persist($journal);
        } finally {
            $this->kernel->Unlock();
        }
    }

    public static function Validate(array $journal): void
    {
        if (($journal['version'] ?? null) !== self::VERSION || !is_array($journal['entries'] ?? null)
            || count($journal['entries']) > self::MAX_ENTRIES || strlen(EChartsImportJson::Canonical($journal)) > self::MAX_BYTES) {
            throw new RuntimeException('JOURNAL_INVALID: unsupported, damaged or oversized import journal.');
        }
        $sequences = [];
        foreach ($journal['entries'] as $id => $entry) {
            if (!is_string($id) || preg_match('/^[a-f0-9]{32}$/D', $id) !== 1 || !is_array($entry)
                || !is_string($entry['identity'] ?? null) || !is_string($entry['revision'] ?? null)
                || !is_int($entry['sequence'] ?? null) || $entry['sequence'] < 1 || $entry['sequence'] > self::MAX_ENTRIES
                || isset($sequences[$entry['sequence']])
                || !in_array($entry['phase'] ?? '', ['planned', 'created', 'configured', 'verified', 'complete'], true)
                || !in_array($entry['status'] ?? '', ['planned', 'created', 'configured', 'verified', 'complete', 'failed'], true)
                || !is_string($entry['moduleID'] ?? null) || !is_string($entry['targetHash'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/D', $entry['revision']) !== 1
                || !array_key_exists('targetID', $entry) || ($entry['targetID'] !== null && (!is_int($entry['targetID']) || $entry['targetID'] < 1))
                || !array_key_exists('pending', $entry) || !array_key_exists('creationIntent', $entry)
                || !array_key_exists('receipt', $entry) || !array_key_exists('snapshot', $entry)
                || !is_bool($entry['applyIntent'] ?? null)
                || (isset($entry['originalCreationGuard']) && (!is_string($entry['originalCreationGuard'])
                    || preg_match('/^[a-f0-9]{64}$/D', $entry['originalCreationGuard']) !== 1))
                || ($entry['targetID'] !== null && !is_array($entry['snapshot']))
                || ($entry['creationIntent'] !== null && (!is_array($entry['creationIntent']['before'] ?? null) || !array_is_list($entry['creationIntent']['before'])))
                || ($entry['marker'] ?? '') !== self::Marker($entry['identity'], $id)) {
                throw new RuntimeException('JOURNAL_INVALID: invalid import entry.');
            }
            $sequences[$entry['sequence']] = true;
        }
        foreach ($journal['entries'] as $entry) {
            if (isset($entry['separateFrom']) && (!is_string($entry['separateFrom'])
                || !isset($journal['entries'][$entry['separateFrom']])
                || $journal['entries'][$entry['separateFrom']]['identity'] !== $entry['identity']
                || $journal['entries'][$entry['separateFrom']]['sequence'] >= $entry['sequence'])) {
                throw new RuntimeException('JOURNAL_INVALID: invalid separate-creation predecessor.');
            }
        }
        if ($sequences !== []) {
            $numbers = array_keys($sequences);
            sort($numbers, SORT_NUMERIC);
            if ($numbers !== range(1, count($journal['entries']))) {
                throw new RuntimeException('JOURNAL_INVALID: journal sequence is incomplete.');
            }
        }
    }

    public static function Marker(string $identity, string $id): string
    {
        return 'SymconECharts:JSLive:1:' . hash('sha256', $identity) . ':' . $id;
    }

    private function Persist(array $journal): void
    {
        self::Validate($journal);
        $this->kernel->Save($journal);
    }

    private function IsSeparateReplay(array $previous, string $entryID, mixed $separateFrom, string $planHash): bool
    {
        $entry = $previous[$entryID];
        if (!is_string($separateFrom) || !isset($previous[$separateFrom])
            || $previous[$separateFrom]['targetID'] === null
            || $previous[$separateFrom]['sequence'] >= $entry['sequence'] || $entry['planHash'] !== $planHash) {
            return false;
        }
        if (array_key_exists('separateFrom', $entry)) {
            return $entry['separateFrom'] === $separateFrom;
        }
        // Existing version-1 entries have no predecessor field. Only the immediately preceding
        // import of this identity can prove their chronological separate-creation relationship.
        $predecessor = null;
        foreach ($previous as $id => $candidate) {
            if ($id === $entryID) {
                break;
            }
            $predecessor = $id;
        }
        return $predecessor === $separateFrom;
    }

    private function Continue(array &$entry, array &$journal, array $target): void
    {
        $entry['error'] = null;
        if ($entry['targetID'] === null) {
            $found = $this->kernel->Find($entry['marker']);
            if ($found !== [] || $entry['creationIntent'] !== null) {
                throw new RuntimeException('RECOVERY_REQUIRED: creation may have happened; assign the target explicitly.');
            }
            $entry['creationIntent'] = ['before' => $this->kernel->Inventory($entry['moduleID'])];
            $this->Persist($journal);
            $id = $this->kernel->Create($entry['moduleID']);
            $snapshot = $this->kernel->Inspect($id);
            if ($snapshot['moduleID'] !== $entry['moduleID'] || $snapshot['info'] !== ''
                || !EChartsJSLiveTargetSnapshot::IsUnchangedCreation($snapshot) || $this->kernel->HasChanges($id)) {
                throw new RuntimeException('TARGET_CHANGED: ownership or module differs.');
            }
            $entry['targetID'] = $id;
            $entry['snapshot'] = $snapshot;
            $entry['phase'] = $entry['status'] = 'created';
            $entry['pending'] = array_replace($snapshot, ['info' => $entry['marker']]);
            $this->Persist($journal);
        }
        $id = $entry['targetID'];
        $current = $this->kernel->Inspect($id);
        $this->RecoverResealedCreationGuard($entry, $journal, $target, $current);
        if ($entry['phase'] === 'created' && $entry['pending'] !== null
            && $this->IsConfigurationIntent($entry, $target)) {
            // Version-1 journals could fail immediately after staging: the active snapshot is still the old one.
            // Preserve the target ID, marker, revision and creation guard; no re-creation or live journal rewrite.
            $entry['phase'] = $entry['status'] = 'configured';
            $entry['applyIntent'] = true;
            $this->Persist($journal);
        }
        if ($entry['phase'] === 'created' && $entry['pending'] !== null) {
            if (EChartsImportJson::Hash($current) === EChartsImportJson::Hash($entry['pending'])) {
                $entry['snapshot'] = $current;
                $entry['pending'] = null;
                $this->Persist($journal);
            } elseif ($entry['pending']['info'] === $entry['marker'] && $current['info'] === '') {
                $this->AssertSnapshot($entry, $current);
                $this->kernel->Mark($id, $entry['marker']);
                $current = $this->kernel->Inspect($id);
                if (EChartsImportJson::Hash($current) !== EChartsImportJson::Hash($entry['pending'])) {
                    throw new RuntimeException('TARGET_CHANGED: ownership marker could not be verified.');
                }
                $entry['snapshot'] = $current;
                $entry['pending'] = null;
                $this->Persist($journal);
            }
        }
        if ($entry['phase'] === 'created') {
            $this->AssertSnapshot($entry, $current);
            if ($this->kernel->HasChanges($id)) {
                throw new RuntimeException('TARGET_CHANGED: refusing to replace unrecorded planned settings.');
            }
            // A disconnect and a connect are separate durable steps, never one hidden compound mutation.
            if ($entry['snapshot']['connectionID'] !== 0 && $entry['snapshot']['connectionID'] !== $target['gatewayID']) {
                $this->ChangeField($entry, $journal, 'connectionID', 0);
            }
            $desired = ['name' => $target['name'], 'parentID' => $target['parentID'], 'connectionID' => $target['gatewayID']];
            foreach ($desired as $field => $value) {
                if (EChartsImportJson::Hash($entry['snapshot'][$field]) === EChartsImportJson::Hash($value)) {
                    continue;
                }
                $this->ChangeField($entry, $journal, $field, $value);
            }
            if ($this->kernel->HasChanges($id)) {
                throw new RuntimeException('TARGET_CHANGED: target acquired unrecorded planned settings before configuration.');
            }
            // SetConfiguration stages settings; it does not change the active configuration returned by Inspect.
            // Persist both the active baseline and expected activated result before either native call.
            $entry['pending'] = $this->ExpectedConfiguration($entry, $target);
            $entry['applyIntent'] = true;
            $entry['phase'] = $entry['status'] = 'configured';
            $this->Persist($journal);
        }
        if ($entry['phase'] === 'configured') {
            $expected = $this->ExpectedConfiguration($entry, $target);
            if ($entry['pending'] !== null && !$this->IsConfigurationIntent($entry, $target)) {
                throw new RuntimeException('JOURNAL_INVALID: recorded configuration intent differs from the reviewed target.');
            }
            $current = $this->kernel->Inspect($id);
            $atBaseline = EChartsImportJson::Hash($current) === EChartsImportJson::Hash($entry['snapshot']);
            $alreadyActive = $this->SameAppliedCore($current, $expected);
            if (!$atBaseline && !$alreadyActive) {
                throw new RuntimeException('TARGET_CHANGED: active settings or target metadata changed.');
            }
            $hasChanges = $this->kernel->HasChanges($id);
            if ($hasChanges && ($entry['pending'] === null || $alreadyActive || !$entry['applyIntent'])) {
                throw new RuntimeException('TARGET_CHANGED: unrecorded planned settings must not be applied.');
            }
            if (!$entry['applyIntent']) {
                $entry['applyIntent'] = true;
                $this->Persist($journal);
            }
            if (!$alreadyActive && !$hasChanges) {
                $this->kernel->Change($id, 'configuration', $expected['configuration']);
                $this->AssertSnapshot($entry, $this->kernel->Inspect($id));
                if (!$this->kernel->HasChanges($id)) {
                    throw new RuntimeException('IMPORT_FAILED: the reviewed configuration was not staged.');
                }
            }
            // Do not overwrite an existing recorded draft on retry. Apply it, then compare the full active result.
            // Public getters cannot read planned settings. A divergent activation is retained and fails closed;
            // no corrective SetConfiguration replaces it. Apply may repeat only for this incomplete owned target.
            $this->kernel->Apply($id);
            $after = $this->kernel->Inspect($id);
            if (!$this->SameAppliedCore($after, $expected) || $this->kernel->HasChanges($id)) {
                throw new RuntimeException('TARGET_CHANGED: target configuration changed during application.');
            }
            $entry['snapshot'] = $after;
            $entry['pending'] = null;
            $this->Persist($journal);
            $entry['receipt'] = $this->kernel->Verify($id);
            $entry['phase'] = $entry['status'] = 'verified';
            $this->Persist($journal);
        }
        if ($entry['phase'] === 'verified' || $entry['phase'] === 'complete') {
            $this->AssertSnapshot($entry, $this->kernel->Inspect($id));
            if ($this->kernel->HasChanges($id)) {
                throw new RuntimeException('TARGET_CHANGED: completed target has unreviewed planned settings.');
            }
            if (EChartsImportJson::Hash($this->kernel->Verify($id)) !== EChartsImportJson::Hash($entry['receipt'])) {
                throw new RuntimeException('TARGET_CHANGED: output identities changed.');
            }
            $entry['phase'] = $entry['status'] = 'complete';
            $this->Persist($journal);
        }
    }

    private function ExpectedConfiguration(array $entry, array $target): array
    {
        return array_replace($entry['snapshot'], ['configuration' => array_replace($entry['snapshot']['configuration'], $target['configuration'])]);
    }

    private function RecoverResealedCreationGuard(array &$entry, array &$journal, array $target, array $current): void
    {
        $previous = $entry['snapshot']['creationGuard'] ?? null;
        $actual = $current['creationGuard'] ?? null;
        if ($previous === $actual || !is_string($previous) || preg_match('/^[a-f0-9]{64}$/D', $previous) !== 1
            || !is_string($actual) || !hash_equals(EChartsJSLiveTargetSnapshot::CreationHash($current), $actual)
            || $entry['snapshot']['info'] !== $entry['marker'] || $current['info'] !== $entry['marker']
            || ($entry['pending'] !== null && !$this->IsConfigurationIntent($entry, $target))) {
            return;
        }
        // Only the known old Create reseal is recoverable. Every object/configuration field must still match.
        // Inspect already checks ownership of each output. Unclaimed creations and arbitrary guard changes stay blocked.
        $withRecordedGuard = array_replace($current, ['creationGuard' => $previous]);
        $atBaseline = EChartsImportJson::Hash($withRecordedGuard) === EChartsImportJson::Hash($entry['snapshot']);
        $applied = $entry['pending'] !== null && ($entry['applyIntent'] || $entry['phase'] === 'created')
            && $this->SameAppliedCore($withRecordedGuard, $this->ExpectedConfiguration($entry, $target));
        if (!$atBaseline && !$applied) {
            return;
        }
        $entry['originalCreationGuard'] ??= $previous;
        $entry['snapshot']['creationGuard'] = $actual;
        if ($entry['pending'] !== null) {
            $entry['pending']['creationGuard'] = $actual;
        }
        $this->Persist($journal);
    }

    private function IsConfigurationIntent(array $entry, array $target): bool
    {
        return EChartsImportJson::Hash($entry['pending']) === EChartsImportJson::Hash($this->ExpectedConfiguration($entry, $target));
    }

    private function SameAppliedCore(array $actual, array $expected): bool
    {
        // Inspect validates protected ownership metadata for every newly created output first.
        unset($actual['children'], $expected['children']);
        return EChartsImportJson::Hash($actual) === EChartsImportJson::Hash($expected);
    }

    private function ChangeField(array &$entry, array &$journal, string $field, mixed $value): void
    {
        $id = $entry['targetID'];
        $this->AssertSnapshot($entry, $this->kernel->Inspect($id));
        $entry['pending'] = array_replace($entry['snapshot'], [$field => $value]);
        $this->Persist($journal);
        $this->kernel->Change($id, $field, $value);
        $after = $this->kernel->Inspect($id);
        if (EChartsImportJson::Hash($after) !== EChartsImportJson::Hash($entry['pending'])) {
            throw new RuntimeException('TARGET_CHANGED: target changed during configuration.');
        }
        $entry['snapshot'] = $after;
        $entry['pending'] = null;
        $this->Persist($journal);
    }

    private function AssertSnapshot(array $entry, array $current): void
    {
        if (EChartsImportJson::Hash($current) !== EChartsImportJson::Hash($entry['snapshot'])) {
            throw new RuntimeException('TARGET_CHANGED: refusing to overwrite a changed or missing target.');
        }
    }
}
