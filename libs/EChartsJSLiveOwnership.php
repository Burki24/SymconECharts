<?php

declare(strict_types=1);

namespace eCharts;

require_once __DIR__ . '/EChartsIdentity.php';
require_once __DIR__ . '/EChartsImportJson.php';
require_once __DIR__ . '/EChartsJSLiveTargetSnapshot.php';

/** Both prefixes exist on installed targets. Their original spelling is part of the sealed snapshot. */
final class EChartsJSLiveOwnership
{
    public const PREFIX = 'eCharts:JSLive:1:';

    public static function Body(mixed $marker): ?string
    {
        if (is_string($marker)) {
            foreach ([self::PREFIX, EChartsIdentity::LegacyProductName() . ':JSLive:1:'] as $prefix) {
                if (str_starts_with($marker, $prefix)) {
                    return substr($marker, strlen($prefix));
                }
            }
        }
        return null;
    }

    public static function IsValid(string $marker): bool
    {
        return preg_match('/^[a-f0-9]{64}:[a-f0-9]{32}$/D', self::Body($marker) ?? '') === 1;
    }

    public static function Canonical(string $marker): string
    {
        return self::IsValid($marker) ? self::PREFIX . self::Body($marker) : $marker;
    }

    /** Only the marker of this target and this exact child ident can be translated. */
    public static function CanonicalOutput(string $info, string $targetMarker, string $ident): string
    {
        $suffix = ':output:' . $ident;
        if ($ident !== '' && str_ends_with($info, $suffix)) {
            $owner = substr($info, 0, -strlen($suffix));
            if (self::IsValid($owner) && self::Canonical($owner) === $targetMarker) {
                return $targetMarker . $suffix;
            }
        }
        return $info;
    }

    public static function Historical(string $canonical): string
    {
        return EChartsIdentity::LegacyProductName() . ':JSLive:1:' . self::Body($canonical);
    }

    /** During a recorded migration only, compare every sealed field using the previous spelling of this exact output. */
    public static function OutputGuardMatches(array $snapshot, string $guard, string $marker, bool $migrating): bool
    {
        if (hash_equals($guard, EChartsImportJson::Hash($snapshot))) {
            return true;
        }
        $object = $snapshot['object'];
        if (!$migrating || $object['ObjectInfo'] !== $marker . ':output:' . $object['ObjectIdent']) {
            return false;
        }
        $snapshot['object']['ObjectInfo'] = self::Historical($marker) . ':output:' . $object['ObjectIdent'];
        return hash_equals($guard, EChartsImportJson::Hash($snapshot));
    }

    /** Never enroll a new output. Re-seal only children already proven by their full snapshot hash. */
    public static function RenewOutputGuards(int $targetID, string $marker, array $guards): array
    {
        if (!self::IsValid($marker) || !str_starts_with($marker, self::PREFIX) || IPS_GetObject($targetID)['ObjectInfo'] !== $marker) {
            throw new \RuntimeException('TARGET_CHANGED: target ownership differs.');
        }
        $children = IPS_GetChildrenIDs($targetID);
        if (count($children) !== count($guards)) {
            throw new \RuntimeException('TARGET_CHANGED: output inventory differs.');
        }
        foreach ($children as $child) {
            $snapshot = EChartsJSLiveTargetSnapshot::Output($child);
            $object = $snapshot['object'];
            if ($object['ParentID'] !== $targetID || $object['ObjectInfo'] !== $marker . ':output:' . $object['ObjectIdent']
                || !isset($guards[$child]) || !self::OutputGuardMatches($snapshot, $guards[$child], $marker, true)) {
                throw new \RuntimeException('TARGET_CHANGED: refusing to re-seal an unproven or modified output.');
            }
            $guards[$child] = EChartsImportJson::Hash($snapshot);
        }
        return $guards;
    }

    public static function Matches(mixed $marker, string $identity, string $entryID): bool
    {
        return self::Body($marker) === hash('sha256', $identity) . ':' . $entryID;
    }
}
