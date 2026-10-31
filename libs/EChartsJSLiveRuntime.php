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
