<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EChartsTreeJSON.php';

use SymconECharts\EChartsTreeJSON;

function treeJSONAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$json = file_get_contents(__DIR__ . '/../docs/examples/tree-network-1.0.json');
$read = static fn (string $text): array => EChartsTreeJSON::Read($text, false, static fn (int $id): array => [], static fn (int $id): string => '');
$model = $read($json);
treeJSONAssert($model['totalNodes'] === 6 && $model['maximumDepth'] === 3, 'The real contract example must retain all six nodes.');
$projected = EChartsTreeJSON::Project($model, 12, 3000, 'document');
treeJSONAssert($projected['root']['id'] === 'json:document:network' && $projected['count'] === 6, 'JSON identities must have an isolated namespace.');
treeJSONAssert($projected['root']['children'][0]['children'][0]['children'][0]['name'] === 'Access Point'
    && $projected['root']['children'][0]['children'][1]['children'][0]['name'] === 'Access Point', 'Equal names must retain separate IDs and parents.');
$short = EChartsTreeJSON::Project($model, 1, 80, 'document');
treeJSONAssert($short['count'] === 2 && !$short['truncated'], 'Depth is a view, not truncation.');
$short = EChartsTreeJSON::Project($model, 12, 2, 'document');
treeJSONAssert($short['count'] === 2 && $short['truncated'], 'Node limits must report truncation.');

foreach ([
    'FORMAT_UNSUPPORTED' => static function (array &$d): void
    { $d['formatVersion'] = 1; },
    'DUPLICATE_ID' => static function (array &$d): void
    { $d['nodes'][1]['id'] = 'network'; },
    'ROOT_INVALID' => static function (array &$d): void
    { $d['nodes'][1]['parentId'] = null; },
    'PARENT_MISSING' => static function (array &$d): void
    { $d['nodes'][1]['parentId'] = 'missing'; },
    'CYCLE_DETECTED' => static function (array &$d): void
    { $d['nodes'][1]['parentId'] = 'switch-office'; },
    'JSON_INVALID' => static function (array &$d): void
    { $d['nodes'][0]['ignored'] = true; },
    'OBJECT_BINDING_INVALID' => static function (array &$d): void
    { $d['nodes'][0]['objectId'] = 12000; }
] as $code => $change) {
    $document = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    $change($document);
    try {
        $read(json_encode($document, JSON_THROW_ON_ERROR));
        throw new RuntimeException('Accepted invalid document: ' . $code);
    } catch (InvalidArgumentException $exception) {
        treeJSONAssert(str_starts_with($exception->getMessage(), $code . ':'), 'Expected sanitized error ' . $code);
    }
}
try {
    $read('{"format":"x","format":"y"}');
    throw new RuntimeException('Duplicate JSON keys accepted.');
} catch (InvalidArgumentException $exception) {
    treeJSONAssert(str_starts_with($exception->getMessage(), 'JSON_INVALID:'), 'Duplicate JSON keys must be rejected before decoding.');
}

echo "Tree JSON contract example, identities, projection and invalid whole-document rejection verified.\n";

$document = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
foreach (['', '   ', "name\n", str_repeat('ä', 121), 123] as $name) {
    $bad = $document;
    $bad['nodes'][0]['name'] = $name;
    try {
        $read(json_encode($bad, JSON_THROW_ON_ERROR));
        throw new RuntimeException('Invalid label accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach (["network\n", 'ä', 123, str_repeat('n', 129)] as $id) {
    $bad = $document;
    $bad['nodes'][0]['id'] = $id;
    try {
        $read(json_encode($bad, JSON_THROW_ON_ERROR));
        throw new RuntimeException('Invalid identity accepted.');
    } catch (InvalidArgumentException) {
    }
}
foreach (["\xef\xbb\xbf" . $json, "\xff", '[]', '{}', '{"x":1e400}', str_repeat(' ', 1048577)] as $bad) {
    try {
        $read($bad);
        throw new RuntimeException('Invalid JSON boundary accepted.');
    } catch (InvalidArgumentException) {
    }
}
$large = ['format' => $document['format'], 'formatVersion' => '1.0', 'rootId' => 'root', 'nodes' => []];
for ($i = 0; $i < 3000; ++$i) {
    $large['nodes'][] = ['id' => 'node-' . $i, 'parentId' => 'root', 'name' => 'Same name', 'kind' => 'instance'];
}
$large['nodes'][0]['id'] = 'root';
$large['nodes'][0]['parentId'] = null;
$wide = $read(json_encode($large, JSON_THROW_ON_ERROR));
treeJSONAssert($wide['totalNodes'] === 3000 && EChartsTreeJSON::Project($wide, 12, 80, 'document')['truncated'], 'All 3000 nodes must be checked before view truncation.');
$large['nodes'][] = ['id' => 'overflow', 'parentId' => 'root', 'name' => 'Overflow', 'kind' => 'instance'];
try {
    $read(json_encode($large, JSON_THROW_ON_ERROR));
    throw new RuntimeException('Node budget raised.');
} catch (InvalidArgumentException $exception) {
    treeJSONAssert(str_starts_with($exception->getMessage(), 'LIMIT_EXCEEDED:'), 'The original node protection limit stays at 3000.');
}
$deep = $document;
$deep['nodes'] = [];
$deep['rootId'] = 'n0';
for ($i = 0; $i <= 12; ++$i) {
    $deep['nodes'][] = ['id' => 'n' . $i, 'parentId' => $i === 0 ? null : 'n' . ($i - 1), 'name' => 'Node', 'kind' => 'category'];
}
treeJSONAssert($read(json_encode($deep))['maximumDepth'] === 12, 'Twelve descendants are permitted.');
$deep['nodes'][] = ['id' => 'n13', 'parentId' => 'n12', 'name' => 'Too deep', 'kind' => 'category'];
try {
    $read(json_encode($deep));
    throw new RuntimeException('Depth protection raised.');
} catch (InvalidArgumentException) {
}
$cycle = $document;
$cycle['nodes'][] = ['id' => 'cycle-a', 'parentId' => 'cycle-b', 'name' => 'A', 'kind' => 'instance'];
$cycle['nodes'][] = ['id' => 'cycle-b', 'parentId' => 'cycle-a', 'name' => 'B', 'kind' => 'instance'];
try {
    $read(json_encode($cycle));
    throw new RuntimeException('Disconnected cycle accepted.');
} catch (InvalidArgumentException $exception) {
    treeJSONAssert(str_starts_with($exception->getMessage(), 'CYCLE_DETECTED:'), 'Disconnected cycles are validated too.');
}
$bindings = $document;
$bindings['nodes'][1]['objectId'] = 12000;
$bindings['nodes'][2]['objectId'] = 12000;
unset($bindings['nodes'][2]['displayValue']);
$bindings['nodes'][1]['kind'] = 'variable';
$bindings['nodes'][2]['kind'] = 'variable';
$objectReads = 0;
$valueReads = 0;
$bound = EChartsTreeJSON::Read(
    json_encode($bindings),
    true,
    static function (int $id) use (&$objectReads): array
    {
        ++$objectReads;
        return ['ObjectType' => 2, 'ObjectName' => 'Same name'];
    },
    static function (int $id) use (&$valueReads): string
    {
        ++$valueReads;
        return '<b>23 °C</b>';
    }
);
treeJSONAssert($objectReads === 1 && $valueReads === 1 && $bound['variableIDs'] === [12000], 'Repeated occurrences must deduplicate actual source reads by ID.');
treeJSONAssert(count($bound['root']['children'][0]['children']) === 2, 'Object bindings must never change JSON topology.');
foreach ([false, true] as $forbidden) {
    try {
        EChartsTreeJSON::Read(
            json_encode($bindings),
            true,
            static fn (int $id): array => ['ObjectType' => $forbidden ? 2 : 6],
            static fn (int $id): string => 'value',
            $forbidden ? [12000] : []
        );
        throw new RuntimeException('Forbidden binding accepted.');
    } catch (InvalidArgumentException) {
    }
}
echo "Tree JSON parser, Unicode bounds, whole-tree limits, disconnected cycles, stable identities and binding policies verified.\n";
