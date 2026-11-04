<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use stdClass;
use Throwable;

require_once __DIR__ . '/EChartsImportJson.php';
require_once __DIR__ . '/TreeHierarchy.php';

/** Inert Tree 1.0 documents. Complete validation always precedes view projection. */
final class EChartsTreeJSON
{
    public const MAXIMUM_BYTES = 1048576;
    public const MAXIMUM_UPLOAD_BYTES = 1398104;
    public const TRANSPORT_RESERVE_BYTES = 256;

    public static function Read(string $json, bool $bindings, callable $object, callable $value, array $forbidden = []): array
    {
        if (strlen($json) > self::MAXIMUM_BYTES) {
            self::Fail('LIMIT_EXCEEDED', '');
        }
        try {
            $document = EChartsImportJson::Decode($json);
        } catch (Throwable $exception) {
            self::Fail(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:') ? 'LIMIT_EXCEEDED' : 'JSON_INVALID', '');
        }
        self::Fields($document, ['format', 'formatVersion', 'rootId', 'nodes'], ['format', 'formatVersion', 'rootId', 'nodes'], '');
        if ($document->format !== 'de.burki24.symconecharts.tree' || $document->formatVersion !== '1.0') {
            self::Fail('FORMAT_UNSUPPORTED', '/formatVersion');
        }
        if (!self::ID($document->rootId)) {
            self::Fail('ROOT_INVALID', '/rootId');
        }
        if (!is_array($document->nodes) || !array_is_list($document->nodes) || $document->nodes === []) {
            self::Fail('JSON_INVALID', '/nodes');
        }
        if (count($document->nodes) > TreeHierarchy::MAXIMUM_NODES) {
            self::Fail('LIMIT_EXCEEDED', '/nodes');
        }
        $nodes = [];
        $children = [];
        $indices = [];
        $roots = [];
        foreach ($document->nodes as $index => $node) {
            $pointer = '/nodes/' . $index;
            self::Fields($node, ['id', 'parentId', 'name', 'kind'], ['id', 'parentId', 'name', 'kind', 'displayValue', 'objectId'], $pointer);
            if (!self::ID($node->id)) {
                self::Fail('JSON_INVALID', $pointer . '/id');
            }
            if ($node->parentId !== null && !self::ID($node->parentId)) {
                self::Fail('JSON_INVALID', $pointer . '/parentId');
            }
            $key = 'id:' . $node->id;
            if (isset($nodes[$key])) {
                self::Fail('DUPLICATE_ID', $pointer . '/id');
            }
            if (!self::Text($node->name, 120) || preg_match('/\S/u', $node->name) !== 1) {
                self::Fail('JSON_INVALID', $pointer . '/name');
            }
            if (!in_array($node->kind, ['category', 'instance', 'variable'], true)) {
                self::Fail('JSON_INVALID', $pointer . '/kind');
            }
            if (property_exists($node, 'displayValue') && !self::Text($node->displayValue, 100)) {
                self::Fail('JSON_INVALID', $pointer . '/displayValue');
            }
            if (property_exists($node, 'objectId') && (!$bindings || !is_int($node->objectId) || $node->objectId < 1
                || property_exists($node, 'displayValue') || in_array($node->objectId, $forbidden, true))) {
                self::Fail('OBJECT_BINDING_INVALID', $pointer . '/objectId');
            }
            $nodes[$key] = $node;
            $indices[$key] = $index;
            if ($node->parentId === null) {
                $roots[] = $node->id;
            } else {
                $children['id:' . $node->parentId][] = $key;
            }
        }
        if ($roots !== [$document->rootId]) {
            self::Fail('ROOT_INVALID', '/rootId');
        }
        // Follow every parent chain, including disconnected components. Prefix keys avoid PHP numeric-key coercion.
        $depths = ['id:' . $document->rootId => 0];
        foreach ($nodes as $key => $node) {
            $chain = [];
            $cursor = $key;
            while (!isset($depths[$cursor])) {
                if (isset($chain[$cursor])) {
                    self::Fail('CYCLE_DETECTED', '/nodes/' . $indices[$cursor] . '/parentId');
                }
                $chain[$cursor] = true;
                $parent = 'id:' . $nodes[$cursor]->parentId;
                if (!isset($nodes[$parent])) {
                    self::Fail('PARENT_MISSING', '/nodes/' . $indices[$cursor] . '/parentId');
                }
                $cursor = $parent;
            }
            $depth = $depths[$cursor];
            foreach (array_reverse(array_keys($chain)) as $child) {
                $depths[$child] = ++$depth;
                if ($depth > TreeHierarchy::MAXIMUM_DEPTH) {
                    self::Fail('LIMIT_EXCEEDED', '/nodes/' . $indices[$child] . '/parentId');
                }
            }
        }
        $resolved = [];
        $variableIDs = [];
        foreach ($nodes as $key => $node) {
            if (!property_exists($node, 'objectId')) {
                continue;
            }
            try {
                if (!isset($resolved[$node->objectId])) {
                    $info = $object($node->objectId);
                    if (!in_array($info['ObjectType'] ?? null, [0, 1, 2], true)) {
                        throw new InvalidArgumentException();
                    }
                    $resolved[$node->objectId] = ['kind' => ['category', 'instance', 'variable'][$info['ObjectType']], 'name' => mb_substr((string) ($info['ObjectName'] ?? ''), 0, 120, 'UTF-8')];
                    if ($info['ObjectType'] === 2) {
                        $resolved[$node->objectId]['displayValue'] = mb_substr($value($node->objectId), 0, 100, 'UTF-8');
                        $variableIDs[$node->objectId] = $node->objectId;
                    }
                }
                if ($resolved[$node->objectId]['kind'] !== $node->kind) {
                    throw new InvalidArgumentException();
                }
            } catch (Throwable) {
                self::Fail('OBJECT_BINDING_INVALID', '/nodes/' . $indices[$key] . '/objectId');
            }
        }
        $build = static function (string $key) use (&$build, $nodes, $children, $resolved): array
        {
            $node = $nodes[$key];
            $result = ['id' => $node->id, 'name' => $node->name, 'kind' => $node->kind, 'children' => []];
            if (property_exists($node, 'displayValue')) {
                $result['displayValue'] = $node->displayValue;
            } elseif (property_exists($node, 'objectId') && isset($resolved[$node->objectId]['displayValue'])) {
                $result['displayValue'] = $resolved[$node->objectId]['displayValue'];
            }
            foreach ($children[$key] ?? [] as $child) {
                $result['children'][] = $build($child);
            }
            return $result;
        };
        $canonical = EChartsImportJson::Canonical($document);
        if (strlen($canonical) > self::MAXIMUM_BYTES) {
            self::Fail('LIMIT_EXCEEDED', '');
        }
        return ['document' => $canonical, 'documentHash' => hash('sha256', $canonical),
            'rootId'       => $document->rootId, 'root' => $build('id:' . $document->rootId),
            'totalNodes'   => count($nodes), 'maximumDepth' => max($depths), 'variableIDs' => array_values($variableIDs),
            'objectIDs'    => array_keys($resolved), 'bindings' => array_map(static fn (int $id, array $binding): array => [
                'objectId' => $id, 'kind' => $binding['kind'], 'name' => $binding['name']
            ], array_keys($resolved), array_values($resolved))];
    }

    public static function Project(array $model, int $depth, int $nodes, string $namespace): array
    {
        if ($depth < 1 || $depth > TreeHierarchy::MAXIMUM_DEPTH || $nodes < 2 || $nodes > TreeHierarchy::MAXIMUM_NODES) {
            self::Fail('LIMIT_EXCEEDED', '');
        }
        $map = static function (array $node) use (&$map, $namespace): array
        {
            $node['id'] = 'json:' . $namespace . ':' . $node['id'];
            $node['children'] = array_map($map, $node['children']);
            return $node;
        };
        return TreeHierarchy::Project($map($model['root']), $depth, $nodes);
    }

    public static function Problem(Throwable $exception): array
    {
        $parts = explode(':', $exception->getMessage(), 2);
        $code = in_array($parts[0], ['JSON_INVALID', 'FORMAT_UNSUPPORTED', 'LIMIT_EXCEEDED', 'DUPLICATE_ID',
            'ROOT_INVALID', 'PARENT_MISSING', 'CYCLE_DETECTED', 'OBJECT_BINDING_INVALID', 'SOURCE_UNAVAILABLE',
            'SOURCE_MODE_MISMATCH', 'CONFIGURATION_PENDING', 'IMPORT_BUSY'], true) ? $parts[0] : 'SOURCE_UNAVAILABLE';
        $pointer = $parts[1] ?? '';
        return ['code' => $code, 'pointer' => preg_match('~^(?:/(?:nodes|\d+|id|parentId|name|kind|displayValue|objectId|rootId|formatVersion))*$~D', $pointer) === 1 ? $pointer : '',
            'message'  => match ($code) {
                'LIMIT_EXCEEDED'         => 'The Tree JSON document exceeds a protection limit.',
                'OBJECT_BINDING_INVALID' => 'A local object binding is unavailable, forbidden or incompatible.',
                'FORMAT_UNSUPPORTED'     => 'The Tree JSON format or version is unsupported.',
                'DUPLICATE_ID'           => 'Every Tree JSON node needs a unique ID.',
                'ROOT_INVALID'           => 'Exactly one root matching rootId is required.',
                'PARENT_MISSING'         => 'A Tree JSON parent node is missing.',
                'CYCLE_DETECTED'         => 'The Tree JSON contains a cycle.',
                'CONFIGURATION_PENDING'  => 'Apply or discard pending configuration changes before using the script setter.',
                'SOURCE_MODE_MISMATCH'   => 'Select the stored JSON source before using the script setter.',
                'SOURCE_UNAVAILABLE'     => 'The Tree JSON source could not be read.',
                'IMPORT_BUSY'            => 'Another Tree JSON update is in progress. Try again.',
                default                  => 'The Tree JSON document is invalid. Check the reported field and the Tree JSON contract.'
            }];
    }

    private static function Fields(mixed $value, array $required, array $allowed, string $pointer): void
    {
        if (!$value instanceof stdClass || array_diff($required, array_keys(get_object_vars($value))) !== []
            || array_diff(array_keys(get_object_vars($value)), $allowed) !== []) {
            self::Fail('JSON_INVALID', $pointer);
        }
    }

    private static function ID(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $value) === 1;
    }

    private static function Text(mixed $value, int $maximum): bool
    {
        return is_string($value) && mb_strlen($value, 'UTF-8') <= $maximum && preg_match('/[\x00-\x1f\x7f-\x9f]/u', $value) === 0;
    }

    private static function Fail(string $code, string $pointer): never
    {
        throw new InvalidArgumentException($code . ':' . $pointer);
    }
}
