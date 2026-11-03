<?php

declare(strict_types=1);

namespace SymconECharts;

require_once __DIR__ . '/EChartsImportJson.php';

/** Stable object/configuration fields only. Live values, status and timestamps are not ownership evidence. */
final class EChartsJSLiveTargetSnapshot
{
    public static function Read(int $id): array
    {
        if (!IPS_InstanceExists($id)) {
            throw new \RuntimeException('TARGET_CHANGED: target instance is missing.');
        }
        $object = IPS_GetObject($id);
        $instance = IPS_GetInstance($id);
        $children = IPS_GetChildrenIDs($id);
        sort($children, SORT_NUMERIC);
        return ['moduleID'  => $instance['ModuleInfo']['ModuleID'], 'name' => $object['ObjectName'],
            'parentID'      => $object['ParentID'], 'connectionID' => $instance['ConnectionID'], 'info' => $object['ObjectInfo'],
            'ident'         => $object['ObjectIdent'], 'icon' => $object['ObjectIcon'], 'position' => $object['ObjectPosition'],
            'hidden'        => $object['ObjectIsHidden'], 'disabled' => $object['ObjectIsDisabled'],
            'locked'        => $object['ObjectIsLocked'] ?? false, 'readOnly' => $object['ObjectIsReadOnly'] ?? false,
            'hiddenTitle'   => $object['ObjectIsHiddenTitle'] ?? false, 'children' => $children,
            'configuration' => json_decode(IPS_GetConfiguration($id), true, 64, JSON_THROW_ON_ERROR)];
    }

    public static function Output(int $id): array
    {
        $object = IPS_GetObject($id);
        $variable = IPS_GetVariable($id);
        return ['object' => array_intersect_key($object, array_flip(['ObjectID', 'ParentID', 'ObjectType', 'ObjectName', 'ObjectInfo', 'ObjectIdent',
            'ObjectIcon', 'ObjectPosition', 'ObjectIsHidden', 'ObjectIsDisabled', 'ObjectIsReadOnly', 'ObjectIsLocked', 'ObjectIsHiddenTitle'])),
            'variable'     => array_intersect_key($variable, array_flip(['VariableType', 'VariableAction', 'VariableCustomAction', 'VariableProfile', 'VariableCustomProfile'])),
            'presentation' => IPS_GetVariablePresentation($id)];
    }

    public static function CreationHash(array $snapshot): string
    {
        unset($snapshot['creationGuard']);
        $snapshot['info'] = '';
        return EChartsImportJson::Hash($snapshot);
    }

    public static function IsUnchangedCreation(array $snapshot): bool
    {
        return is_string($snapshot['creationGuard'] ?? null)
            && hash_equals($snapshot['creationGuard'], self::CreationHash($snapshot));
    }
}
