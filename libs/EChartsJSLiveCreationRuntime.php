<?php

declare(strict_types=1);

namespace SymconECharts;

use Closure;
use RuntimeException;

require_once __DIR__ . '/EChartsJSLiveJournal.php';
require_once __DIR__ . '/EChartsJSLivePlan.php';
require_once __DIR__ . '/EChartsJSLiveRuntime.php';

/** Explicit write boundary for newly created, marked target instances. No source/archive write API is used. */
final class EChartsJSLiveCreationRuntime implements EChartsJSLiveImportKernel
{
    private const GATEWAY = '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}';
    private const LOCK = 'SymconECharts.JSLiveImport.1';
    private bool $revalidated = false;

    public function __construct(
        private int $gatewayID,
        private Closure $load,
        private Closure $save,
        private Closure $prepare,
        private Closure $guard
    ) {
    }

    public function Lock(): bool
    {
        if (!function_exists('IPS_SemaphoreEnter') || !function_exists('IPS_SemaphoreLeave')) {
            throw new RuntimeException('IMPORT_BUSY: the required kernel semaphore is unavailable.');
        }
        return IPS_SemaphoreEnter(self::LOCK, 1000);
    }

    public function Unlock(): void
    {
        IPS_SemaphoreLeave(self::LOCK);
    }
    public function Load(): array
    {
        return ($this->load)();
    }
    public function Save(array $journal): void
    {
        EChartsJSLiveJournal::Validate($journal);
        ($this->save)($journal);
        if (EChartsImportJson::Hash(($this->load)()) !== EChartsImportJson::Hash($journal)) {
            throw new RuntimeException('JOURNAL_INVALID: durable journal readback differs.');
        }
    }

    public function Revalidate(array $plan): void
    {
        ($this->guard)($plan);
        // The first check recompiles all target rules and bounded queries under the lock.
        // Subsequent checks verify source/context fingerprints without repeating the full archive load.
        if (!$this->revalidated) {
            $fresh = ($this->prepare)();
            if (!hash_equals($plan['planHash'], $fresh['planHash'])) {
                throw new RuntimeException('PLAN_CHANGED: prepare and confirm a fresh plan.');
            }
            $this->revalidated = true;
        }
    }

    public function Inventory(string $moduleID): array
    {
        $this->Module($moduleID);
        $ids = IPS_GetInstanceListByModuleID($moduleID);
        if (count($ids) > 5000) {
            throw new RuntimeException('LIMIT_EXCEEDED: target inventory exceeds 5000 instances.');
        }
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    public function Find(string $marker): array
    {
        $found = [];
        foreach (EChartsJSLivePlan::Modules() as $module) {
            foreach ($this->Inventory($module['moduleId']) as $id) {
                if (IPS_GetObject($id)['ObjectInfo'] === $marker) {
                    $found[] = $id;
                }
            }
        }
        sort($found, SORT_NUMERIC);
        return $found;
    }

    public function AssertIdentity(string $identity, array $entries): void
    {
        $gateways = IPS_GetInstanceListByModuleID(self::GATEWAY);
        if (count($gateways) > 100) {
            throw new RuntimeException('LIMIT_EXCEEDED: gateway inventory exceeds 100 instances.');
        }
        foreach ($gateways as $id) {
            if ($id === $this->gatewayID) {
                continue;
            }
            if (!function_exists('ECGW_GetJSLiveImportJournal')) {
                throw new RuntimeException('JOURNAL_INVALID: peer journal API is unavailable.');
            }
            $journal = json_decode(ECGW_GetJSLiveImportJournal($id), true, 64, JSON_THROW_ON_ERROR);
            EChartsJSLiveJournal::Validate($journal);
            foreach ($journal['entries'] as $entry) {
                if ($entry['identity'] === $identity) {
                    throw new RuntimeException('TARGET_CHANGED: this origin is reserved by another gateway journal.');
                }
            }
        }
        $allowed = [];
        foreach ($entries as $entry) {
            if ($entry['identity'] === $identity) {
                $allowed[$entry['marker']] = true;
            }
        }
        $prefix = 'SymconECharts:JSLive:1:' . hash('sha256', $identity) . ':';
        foreach (EChartsJSLivePlan::Modules() as $module) {
            foreach ($this->Inventory($module['moduleId']) as $id) {
                $info = IPS_GetObject($id)['ObjectInfo'];
                if (str_starts_with($info, $prefix) && !isset($allowed[$info])) {
                    throw new RuntimeException('RECOVERY_REQUIRED: an origin marker exists without this journal reservation.');
                }
            }
        }
    }

    public function Inspect(int $id): array
    {
        $snapshot = EChartsJSLiveTargetSnapshot::Read($id);
        $module = $this->Module($snapshot['moduleID']);
        $function = $module['prefix'] . '_GetJSLiveCreationGuard';
        if (!function_exists($function)) {
            throw new RuntimeException('FORMAT_UNSUPPORTED: target runtime is not updated.');
        }
        $snapshot['creationGuard'] = $function($id);
        if (str_starts_with($snapshot['info'], 'SymconECharts:JSLive:1:') && $snapshot['children'] !== []) {
            $getGuards = $module['prefix'] . '_GetJSLiveOutputGuards';
            if (!function_exists($getGuards)) {
                throw new RuntimeException('FORMAT_UNSUPPORTED: output ownership API unavailable.');
            }
            $guards = json_decode($getGuards($id), true, 16, JSON_THROW_ON_ERROR);
            foreach ($snapshot['children'] as $child) {
                if (!isset($guards[$child])) {
                    throw new RuntimeException('RECOVERY_REQUIRED: an output has no durable ownership proof.');
                }
                if (!hash_equals($guards[$child], EChartsImportJson::Hash(EChartsJSLiveTargetSnapshot::Output($child)))) {
                    throw new RuntimeException('TARGET_CHANGED: output metadata changed since its creation.');
                }
            }
        }
        return $snapshot;
    }

    public function Create(string $moduleID): int
    {
        $this->Module($moduleID);
        return IPS_CreateInstance($moduleID);
    }

    public function Mark(int $id, string $marker): void
    {
        $snapshot = $this->Inspect($id);
        if ($snapshot['info'] !== '' || !EChartsJSLiveTargetSnapshot::IsUnchangedCreation($snapshot) || $this->HasChanges($id)
            || preg_match('/^SymconECharts:JSLive:1:[a-f0-9]{64}:[a-f0-9]{32}$/D', $marker) !== 1) {
            throw new RuntimeException('TARGET_CHANGED: refusing to claim a changed or previously owned target.');
        }
        if (!IPS_SetInfo($id, $marker)) {
            throw new RuntimeException('IMPORT_FAILED: ownership marker could not be saved.');
        }
    }

    public function Change(int $id, string $field, mixed $value): void
    {
        $this->AssertOwned($id);
        $ok = match ($field) {
            'name'          => IPS_SetName($id, $value), 'parentID' => IPS_SetParent($id, $value),
            'connectionID'  => $value === 0 ? IPS_DisconnectInstance($id) : IPS_ConnectInstance($id, $value),
            'configuration' => IPS_SetConfiguration($id, EChartsImportJson::Canonical($value)),
            default         => throw new RuntimeException('IMPORT_FAILED: invalid target mutation.')
        };
        if (!$ok) {
            throw new RuntimeException('IMPORT_FAILED: target mutation failed.');
        }
    }

    public function Apply(int $id): void
    {
        $this->AssertOwned($id);
        if (!IPS_ApplyChanges($id)) {
            throw new RuntimeException('IMPORT_FAILED: target configuration failed.');
        }
    }

    public function HasChanges(int $id): bool
    {
        return IPS_HasChanges($id);
    }

    public function Verify(int $id): array
    {
        $snapshot = $this->Inspect($id);
        $this->AssertOwned($id);
        if (IPS_GetInstance($id)['InstanceStatus'] !== 102) {
            throw new RuntimeException('IMPORT_FAILED: target is not active.');
        }
        $prefix = $this->Module($snapshot['moduleID'])['prefix'];
        $read = $prefix . '_GetJSLiveImportData';
        $state = json_decode($read($id), true, 64, JSON_THROW_ON_ERROR);
        if (($state['status'] ?? null) !== 'ready') {
            throw new RuntimeException('IMPORT_FAILED: target data is unavailable.');
        }
        $render = $prefix . '_GetJSLiveImportHTML';
        foreach ([false, true] as $ipsView) {
            $html = $render($id, $ipsView);
            if (!is_string($html) || !str_starts_with($html, '<!doctype html>') || strlen($html) > 1048576) {
                throw new RuntimeException('IMPORT_FAILED: native target output is invalid.');
            }
        }
        $outputs = [];
        foreach (IPS_GetChildrenIDs($id) as $child) {
            $object = IPS_GetObject($child);
            if ($object['ObjectType'] !== 2) {
                throw new RuntimeException('TARGET_CHANGED: unexpected target child.');
            }
            $variable = IPS_GetVariable($child);
            $presentation = IPS_GetVariablePresentation($child);
            $html = GetValue($child);
            if ($variable['VariableType'] !== 3 || $variable['VariableAction'] !== 0 || $variable['VariableCustomAction'] !== 0
                || !defined('VARIABLE_PRESENTATION_WEB_CONTENT') || ($presentation['PRESENTATION'] ?? null) !== VARIABLE_PRESENTATION_WEB_CONTENT
                || !is_string($html) || !str_starts_with($html, '<!doctype html>') || strlen($html) > 1048576) {
                throw new RuntimeException('IMPORT_FAILED: target output is not a read-only native WebContent document.');
            }
            $outputs[] = ['id' => $child, 'ident' => $object['ObjectIdent'], 'type' => $variable['VariableType'],
                'action'       => $variable['VariableAction'], 'customAction' => $variable['VariableCustomAction'],
                'native'       => $presentation, 'object' => array_intersect_key(
                    $object,
                    array_flip(['ObjectName', 'ObjectIdent', 'ObjectInfo', 'ObjectPosition', 'ObjectIsHidden', 'ObjectIsDisabled'])
                )];
        }
        usort($outputs, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        if ($snapshot['configuration']['EnableIPSView'] && $outputs === []) {
            throw new RuntimeException('IMPORT_FAILED: the configured IPSView output is missing.');
        }
        return ['tile' => $id, 'outputs' => $outputs];
    }

    private function AssertOwned(int $id): void
    {
        $snapshot = $this->Inspect($id);
        foreach ($this->Load()['entries'] as $entry) {
            if ($entry['targetID'] === $id && $entry['marker'] === $snapshot['info'] && $entry['moduleID'] === $snapshot['moduleID']) {
                return;
            }
        }
        throw new RuntimeException('TARGET_CHANGED: target is not owned by this journal.');
    }

    private function Module(string $moduleID): array
    {
        foreach (EChartsJSLivePlan::Modules() as $module) {
            if ($module['moduleId'] === $moduleID) {
                return $module;
            }
        }
        throw new RuntimeException('FORMAT_UNSUPPORTED: target module is outside the import contract.');
    }
}
