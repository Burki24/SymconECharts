<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/EChartsJSLiveCreationRuntime.php';
require_once __DIR__ . '/EChartsJSLiveJournalView.php';

/** Prepared plans are transient. Only the journal is persistent; import never runs from ApplyChanges. */
trait EChartsJSLiveCreationForm
{
    public function GetJSLiveImportJournal(): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function (): string
        {
            return EChartsJSLiveJournalView::Full($this->ReadJSLiveJournal());
        });
    }

    public function GetJSLiveImportJournalPage(int $Offset, int $Limit, string $Revision): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => EChartsImportJson::Canonical(
            EChartsJSLiveJournalView::Page($this->ReadJSLiveJournal(), $Offset, $Limit, $Revision)
        ));
    }

    public function ShowJSLiveImportJournal(): void
    {
        $this->ShowJSLiveImportJournalPage(0, '');
    }

    public function ShowJSLiveImportJournalPage(int $Offset, string $Revision): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Offset, $Revision): void
        {
            try {
                $page = EChartsJSLiveJournalView::Page($this->ReadJSLiveJournal(), $Offset, 25, $Revision);
            } catch (Throwable $exception) {
                $code = strstr($exception->getMessage(), ':', true);
                $code = is_string($code) && preg_match('/^[A-Z_]{3,40}$/D', $code) === 1 ? $code : 'JOURNAL_INVALID';
                $this->UpdateFormField('JSLiveReadyJournal', 'values', '[]');
                $this->UpdateFormField('JSLiveJournalPageSummary', 'caption', $code . ': ' . $this->Translate('Refresh the import journal before continuing.'));
                foreach (['Previous', 'Next'] as $direction) {
                    $this->UpdateFormField('JSLiveJournal' . $direction, 'enabled', false);
                }
                return;
            }
            $rows = [];
            foreach ($page['entries'] as $entry) {
                $rows[] = ['EntryID' => $entry['entryID'], 'Identity' => $entry['identity'], 'Revision' => $entry['revision'],
                    'TargetID'       => $entry['targetID'] ?? 0, 'Phase' => $entry['phase'], 'Status' => $entry['status'], 'Error' => $entry['error'] ?? ''];
            }
            $this->UpdateFormField('JSLiveReadyJournal', 'values', json_encode($rows, JSON_THROW_ON_ERROR));
            $this->UpdateFormField('JSLiveJournalRevision', 'value', $page['revision']);
            $this->UpdateFormField('JSLiveJournalPreviousOffset', 'value', max(0, $Offset - 25));
            $this->UpdateFormField('JSLiveJournalNextOffset', 'value', $page['nextOffset'] ?? $Offset);
            $this->UpdateFormField('JSLiveJournalPrevious', 'enabled', $Offset > 0);
            $this->UpdateFormField('JSLiveJournalNext', 'enabled', $page['nextOffset'] !== null);
            $this->UpdateFormField('JSLiveJournalPageSummary', 'caption', sprintf(
                $this->Translate('Journal entries %d-%d of %d'),
                $page['total'] === 0 ? 0 : $Offset + 1,
                $Offset + count($rows),
                $page['total']
            ));
        }, true);
    }

    public function PrepareJSLiveImport(string $Package, string $Options, int $ParentID): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, fn (): string => EChartsImportJson::Canonical($this->PrepareJSLivePlan($Package, $Options, $ParentID)));
    }

    public function ExecuteJSLiveImport(string $Package, string $Options, int $ParentID, string $Confirmation): string
    {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($Package, $Options, $ParentID, $Confirmation): string
        {
            $confirmation = EChartsImportJson::Decode($Confirmation);
            if (!$confirmation instanceof \stdClass || ($confirmation->confirmedNewInstances ?? false) !== true
                || array_diff(array_keys(get_object_vars($confirmation)), ['confirmedNewInstances', 'planHash', 'charts']) !== []) {
                throw new InvalidArgumentException('IMPORT_BLOCKED: explicitly confirm creation of new instances and the current plan.');
            }
            $choice = EChartsJSLivePackage::ArrayValue($confirmation);
            unset($choice['confirmedNewInstances']);
            $plan = $this->PrepareJSLivePlan($Package, $Options, $ParentID);
            $kernel = $this->JSLiveCreationKernel(
                fn (): array => EChartsJSLivePlan::Recheck($plan, EChartsJSLiveArchive::Read(...), time()),
                function (array $expected) use ($ParentID): void
                {
                    if (EChartsImportJson::Hash(EChartsJSLiveRuntime::Context($this->InstanceID, $ParentID)) !== EChartsImportJson::Hash($expected['context'])) {
                        throw new RuntimeException('PLAN_CHANGED: gateway or target category changed.');
                    }
                    EChartsJSLiveRuntime::OriginalGuard($expected['originGuard'], $expected['sameInstallation']);
                    if (!hash_equals($expected['ruleFingerprint'], EChartsJSLivePlan::RuleFingerprint())) {
                        throw new RuntimeException('PLAN_CHANGED: import rules changed.');
                    }
                    foreach ($expected['sourceGuard'] as $source) {
                        if (!hash_equals($source['fingerprint'], EChartsImportJson::Hash(EChartsJSLiveRuntime::Source($source['variableID'], $source['archiveID'])))) {
                            throw new RuntimeException('SOURCE_CHANGED: source metadata changed; prepare a fresh plan.');
                        }
                    }
                }
            );
            return EChartsImportJson::Canonical((new EChartsJSLiveJournal($kernel))->Execute($plan, $choice));
        });
    }

    public function ResolveJSLiveImport(string $EntryID, int $TargetID, string $Revision, bool $Confirmed): void
    {
        $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($EntryID, $TargetID, $Revision, $Confirmed): void
        {
            if (!$Confirmed || $TargetID < 0) {
                throw new InvalidArgumentException('RECOVERY_REQUIRED: explicitly confirm this recovery assignment.');
            }
            $kernel = $this->JSLiveCreationKernel(static fn (): array => throw new RuntimeException('PLAN_CHANGED'), static function (array $plan): void
            { });
            (new EChartsJSLiveJournal($kernel))->Resolve($EntryID, $TargetID, $Revision);
        }, true);
    }

    /** All eight arguments, including the instance ID, must be supplied by native Symcon callbacks. */
    public function JSLiveImportFromForm(
        string $FileData,
        string $Mappings,
        int $ParentID,
        string $Action,
        string $Confirmation,
        int $Chart,
        bool $IPSView
    ): string {
        return $this->DebugFlowCall(__FUNCTION__, __LINE__, function () use ($FileData, $Mappings, $ParentID, $Action, $Confirmation, $Chart, $IPSView): string
        {
            try {
                $package = EChartsImportJson::Upload($FileData);
                if ($Action === 'execute') {
                    $result = $this->ExecuteJSLiveImport($package, $Mappings, $ParentID, $Confirmation);
                    $lines = [];
                    foreach (json_decode($result, true, 64, JSON_THROW_ON_ERROR)['charts'] as $key => $row) {
                        $lines[] = sprintf(
                            $this->Translate('Chart %s: %s; target %d; journal %s; error %s.'),
                            $key,
                            $row['status'],
                            $row['targetID'] ?? 0,
                            $row['journalID'] ?? '—',
                            $row['error'] ?? '—'
                        );
                    }
                    $this->UpdateFormField('JSLiveReadyResult', 'caption', implode("\n", $lines));
                    $this->UpdateFormField('JSLiveReadyConfirmed', 'value', false);
                    return '';
                }
                if (!in_array($Action, ['prepare', 'report', 'preview'], true)) {
                    throw new InvalidArgumentException('PACKAGE_INVALID: unknown import form action.');
                }
                $plan = $this->PrepareJSLivePlan($package, $Mappings, $ParentID);
                $keys = array_keys($plan['charts']);
                $chartKey = $keys[max(0, min(count($keys) - 1, $Chart))];
                $chart = $plan['charts'][$chartKey];
                if ($Action === 'preview') {
                    if ($chart['blocked']) {
                        throw new RuntimeException('IMPORT_BLOCKED: no native preview is available for a blocked chart.');
                    }
                    $model = EChartsJSLiveModel::Read($chart['target']['configuration']['JSLiveImportModel'], $chart['family']);
                    $state = EChartsJSLiveRendering::State($model, EChartsJSLiveArchive::Read(...), time(), EChartsJSLivePlan::Design($chart['target']['configuration']));
                    $html = EChartsJSLivePage::Render($state, $IPSView, ['tileHeaderInset' => $IPSView ? 0 : 58]);
                    return 'data:text/html;base64,' . base64_encode($html);
                }
                $rows = [];
                foreach ($plan['charts'] as $row) {
                    $rows[] = ['ChartKey' => $row['chartKey'], 'Name' => $row['name'], 'Target' => $row['target']['module'] ?? '—',
                        'Status'          => $this->Translate($row['blocked'] ? 'Blocked' : 'Ready after individual confirmation'), 'Differences' => count($row['differences'])];
                }
                $differences = [];
                foreach ($chart['differenceDetails'] as $detail) {
                    $differences[] = ['ID' => $detail['id'], 'Pointer' => $detail['pointer'],
                        'Original'         => mb_substr(EChartsImportJson::Canonical($detail['original']), 0, 1000), 'Effect' => $detail['effect'], 'Accept' => false];
                }
                $this->UpdateFormField('JSLiveReadyCharts', 'values', json_encode($rows, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveReadyDifferences', 'values', json_encode($differences, JSON_THROW_ON_ERROR));
                $this->UpdateFormField('JSLiveReadyHash', 'value', $plan['planHash']);
                $this->UpdateFormField('JSLiveReadyKey', 'value', $chartKey);
                $this->UpdateFormField('JSLiveReadyConfirmed', 'value', false);
                $this->UpdateFormField('JSLiveReadySeparateFrom', 'value', '');
                $this->UpdateFormField('JSLiveReadySummary', 'caption', $chart['blocked']
                    ? $this->Translate('The selected chart is blocked. Read the complete report; confirmation cannot waive source or model errors.')
                    : sprintf($this->Translate('Chart %s: %d individually listed differences. Compare both native HTML previews before confirming a new instance.'), $chart['name'], count($differences)));
                $this->UpdateFormField('JSLiveReadyResult', 'caption', '');
                return $Action === 'report' ? 'data:application/json;base64,' . base64_encode(EChartsImportJson::Canonical($plan)) : '';
            } catch (Throwable $exception) {
                $code = strstr($exception->getMessage(), ':', true);
                if (!is_string($code) || preg_match('/^[A-Z_]{3,40}$/D', $code) !== 1) {
                    $code = 'IMPORT_FAILED';
                }
                $this->UpdateFormField('JSLiveReadyHash', 'value', '');
                $this->UpdateFormField('JSLiveReadyKey', 'value', '');
                $this->UpdateFormField('JSLiveReadyConfirmed', 'value', false);
                foreach (['Charts', 'Differences'] as $name) {
                    $this->UpdateFormField('JSLiveReady' . $name, 'values', '[]');
                }
                $this->UpdateFormField('JSLiveReadySummary', 'caption', $code . ': ' . $this->Translate('Import was stopped. Existing sources and instances were not overwritten. Check the journal before retrying.'));
                $this->UpdateFormField('JSLiveReadyResult', 'caption', $code);
                return $code . ': ' . $this->Translate('Import was stopped. Existing sources and instances were not overwritten. Check the journal before retrying.');
            }
        });
    }
    protected function RegisterJSLiveImportJournal(): void
    {
        $this->RegisterAttributeString('JSLiveImportJournal', '{"version":1,"entries":[]}');
    }

    private function ReadJSLiveJournal(): array
    {
        return json_decode($this->ReadAttributeString('JSLiveImportJournal'), true, 64, JSON_THROW_ON_ERROR);
    }

    private function PrepareJSLivePlan(string $package, string $options, int $parentID): array
    {
        $mapping = EChartsImportJson::Decode($options);
        if (!$mapping instanceof \stdClass) {
            throw new InvalidArgumentException('PACKAGE_INVALID: mapping must be an object.');
        }
        foreach (get_object_vars($mapping) as $value) {
            if (!$value instanceof \stdClass) {
                throw new InvalidArgumentException('PACKAGE_INVALID: each source/archive mapping must be an object.');
            }
        }
        $plan = EChartsJSLivePlan::Prepare(
            $package,
            EChartsJSLivePackage::ArrayValue($mapping),
            EChartsJSLiveRuntime::Context($this->InstanceID, $parentID),
            EChartsJSLiveRuntime::Source(...),
            EChartsJSLiveArchive::Read(...),
            EChartsJSLiveRuntime::InstallationID(),
            time()
        );
        EChartsJSLiveRuntime::OriginalGuard($plan['originGuard'], $plan['sameInstallation']);
        return $plan;
    }

    private function JSLiveCreationKernel(\Closure $prepare, \Closure $guard): EChartsJSLiveCreationRuntime
    {
        return new EChartsJSLiveCreationRuntime(
            $this->InstanceID,
            fn (): array => $this->ReadJSLiveJournal(),
            fn (array $journal) => $this->WriteAttributeString('JSLiveImportJournal', EChartsImportJson::Canonical($journal)),
            $prepare,
            $guard
        );
    }
}
