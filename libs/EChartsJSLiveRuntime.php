<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/EChartsVariablePresentation.php';
require_once __DIR__ . '/EChartsJSLivePackage.php';

/** Only existing metadata is read; archive values, instances and source settings are untouched. */
final class EChartsJSLiveRuntime
{
    public static function Context(int $gatewayID, int $parentID): array
    {
        if (!IPS_InstanceExists($gatewayID) || IPS_GetInstance($gatewayID)['ModuleInfo']['ModuleID'] !== '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}'
            || IPS_GetInstance($gatewayID)['InstanceStatus'] !== 102) {
            throw new RuntimeException('PARENT_MISSING: the selected gateway is not active.');
        }
        $parent = $parentID === 0 ? null : IPS_GetObject($parentID);
        if ($parent !== null && $parent['ObjectType'] !== 0) {
            throw new RuntimeException('PACKAGE_INVALID: target parent must be an existing category.');
        }
        if ($parent !== null) {
            $parent = array_intersect_key($parent, array_flip(['ObjectID', 'ObjectType', 'ParentID', 'ObjectIdent', 'ObjectName', 'ObjectInfo',
                'ObjectIcon', 'ObjectPosition', 'ObjectIsHidden', 'ObjectIsDisabled', 'ObjectIsReadOnly', 'ObjectIsLocked']));
        }
        return ['gatewayID' => $gatewayID, 'parentID' => $parentID,
            'fingerprint'   => EChartsImportJson::Hash([EChartsJSLiveTargetSnapshot::Read($gatewayID), $parent])];
    }

    /** Compare original configuration and controls only on the positively identified same installation. */
    public static function OriginalGuard(array $guards, bool $same): void
    {
        if (!$same) {
            return;
        }
        foreach ($guards as $chart) {
            $id = $chart['instanceID'];
            if (!IPS_InstanceExists($id) || IPS_GetInstance($id)['ModuleInfo']['ModuleID'] !== $chart['moduleID']
                || IPS_GetObject($id)['ObjectName'] !== $chart['name']) {
                throw new RuntimeException('SOURCE_CHANGED: the original chart identity changed; export again.');
            }
            $current = json_decode(IPS_GetConfiguration($id), false, 32, JSON_THROW_ON_ERROR);
            foreach ($chart['configuration'] as $key => $hash) {
                if (!property_exists($current, $key) || EChartsImportJson::Hash($current->{$key}) !== $hash) {
                    throw new RuntimeException('SOURCE_CHANGED: the original chart configuration changed; export again.');
                }
            }
            $connection = IPS_GetInstance($id)['ConnectionID'];
            if ($connection === 0) {
                throw new RuntimeException('SOURCE_CHANGED: the original chart gateway is missing.');
            }
            $global = json_decode(IPS_GetConfiguration($connection), false, 32, JSON_THROW_ON_ERROR);
            foreach ($chart['global'] as $key => $hash) {
                if (!property_exists($global, $key) || EChartsImportJson::Hash($global->{$key}) !== $hash) {
                    throw new RuntimeException('SOURCE_CHANGED: original global configuration changed; export again.');
                }
            }
            foreach ($chart['controls'] as $control) {
                if (!IPS_VariableExists($control['id']) || EChartsImportJson::Hash(GetValue($control['id'])) !== $control['hash']) {
                    throw new RuntimeException('SOURCE_CHANGED: original control values changed; export again.');
                }
            }
        }
    }

    public static function InstallationID(): ?string
    {
        if (!function_exists('SymconJSLive_GetMigrationInstallationID')) {
            return null;
        }
        $identities = [];
        foreach (IPS_GetInstanceListByModuleID('{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}') as $id) {
            try {
                $value = SymconJSLive_GetMigrationInstallationID($id);
                if (is_string($value) && $value !== '') {
                    $identities[$value] = true;
                }
            } catch (Throwable) {
                return null;
            }
        }
        return count($identities) === 1 ? array_key_first($identities) : null;
    }

    public static function Source(int $variableID, ?int $archiveID): array
    {
        if (!IPS_VariableExists($variableID)) {
            throw new RuntimeException('Source variable is unavailable.');
        }
        $variable = IPS_GetVariable($variableID);
        $snapshot = EChartsVariablePresentation::Snapshot($variableID);
        if (($snapshot['conversion']['status'] ?? '') !== 'exact') {
            throw new RuntimeException('Source presentation is unavailable.');
        }
        $archive = null;
        if ($archiveID !== null) {
            $instance = IPS_GetInstance($archiveID);
            if (($instance['ModuleInfo']['ModuleID'] ?? '') !== '{43192F0B-135B-4CE7-A0A7-1475603F3060}') {
                throw new RuntimeException('Mapped archive is not Archive Control.');
            }
            $type = AC_GetAggregationType($archiveID, $variableID);
            $archive = ['logging' => AC_GetLoggingStatus($archiveID, $variableID), 'aggregationType' => $type,
                'counterSettings' => $type === 1 ? ['ignoreZeros' => AC_GetCounterIgnoreZeros($archiveID, $variableID)] : null];
        }
        return ['variableType' => $variable['VariableType'], 'native' => EChartsJSLivePackage::Wire($snapshot['native']),
            'variableAction'   => $variable['VariableAction'] ?? null, 'variableCustomAction' => $variable['VariableCustomAction'] ?? null,
            'archive'          => $archive];
    }
}
