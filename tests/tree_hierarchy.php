<?php

declare(strict_types=1);

use SymconECharts\TreeHierarchy;

require_once dirname(__DIR__) . '/EChartsTree/TreeHierarchy.php';

function assertTree(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$objects = [
    100 => ['ObjectType' => 0, 'ObjectName' => 'Haus', 'ObjectIsHidden' => false],
    101 => ['ObjectType' => 0, 'ObjectName' => 'Wohnen', 'ObjectIsHidden' => false],
    102 => ['ObjectType' => 1, 'ObjectName' => 'Sensor', 'ObjectIsHidden' => false],
    103 => ['ObjectType' => 2, 'ObjectName' => 'Temperatur', 'ObjectIsHidden' => false],
    104 => ['ObjectType' => 2, 'ObjectName' => 'Temperatur', 'ObjectIsHidden' => false],
    105 => ['ObjectType' => 0, 'ObjectName' => 'Intern', 'ObjectIsHidden' => true],
    106 => ['ObjectType' => 2, 'ObjectName' => 'Geheim', 'ObjectIsHidden' => false],
    107 => ['ObjectType' => 3, 'ObjectName' => 'Skript', 'ObjectIsHidden' => false]
];
$children = [100 => [101, 105, 107], 101 => [102], 102 => [103, 104], 105 => [106]];
$object = static fn (int $id): array => $objects[$id];
$getChildren = static fn (int $id): array => $children[$id] ?? [];
$value = static fn (int $id): string => $id === 103 ? '21,3 °C' : '22,0 °C';

$tree = TreeHierarchy::Build(100, 3, 20, false, $object, $getChildren, $value);
assertTree($tree['root']['id'] === 'object-100', 'Tree root identity changed.');
assertTree($tree['root']['children'][0]['name'] === 'Wohnen', 'Visible category missing.');
assertTree($tree['root']['children'][0]['children'][0]['children'][0]['id'] === 'object-103', 'First variable identity lost.');
assertTree($tree['root']['children'][0]['children'][0]['children'][1]['id'] === 'object-104', 'Equal-name variable identity lost.');
assertTree($tree['root']['children'][0]['children'][0]['children'][0]['displayValue'] === '21,3 °C', 'Formatted value missing.');
assertTree($tree['count'] === 5 && $tree['truncated'] === false, 'Visible tree count is wrong.');

$shallow = TreeHierarchy::Build(100, 1, 20, false, $object, $getChildren, $value);
assertTree($shallow['count'] === 2 && $shallow['root']['children'][0]['children'] === [], 'Maximum depth was ignored.');

$limited = TreeHierarchy::Build(100, 3, 3, true, $object, $getChildren, $value);
assertTree($limited['count'] === 3 && $limited['truncated'] === true, 'Node limit was not reported.');

$visible = TreeHierarchy::Build(100, 2, 20, true, $object, $getChildren, $value);
assertTree($visible['count'] === 5 && $visible['root']['children'][1]['name'] === 'Intern', 'Hidden branch inclusion failed.');

$wideChildren = range(1001, 1500);
$wideObject = static fn (int $id): array => [
    'ObjectType'     => $id === 1000 ? 0 : 2,
    'ObjectName'     => 'Tree object ' . $id,
    'ObjectIsHidden' => false
];
$wideTree = TreeHierarchy::Build(
    1000,
    1,
    500,
    false,
    $wideObject,
    static fn (int $id): array => $id === 1000 ? $wideChildren : [],
    static fn (int $id): string => (string) $id
);
assertTree($wideTree['count'] === 500, 'Tree must accept the configured 500-node limit.');
assertTree($wideTree['truncated'] === true, 'Tree must report the 501st node as truncated.');
assertTree(count($wideTree['root']['children']) === 499, 'Tree must retain exactly 499 children beside its root.');
$exactTree = TreeHierarchy::Build(
    1000,
    1,
    500,
    false,
    $wideObject,
    static fn (int $id): array => $id === 1000 ? array_slice($wideChildren, 0, 499) : [],
    static fn (int $id): string => (string) $id
);
assertTree($exactTree['count'] === 500 && $exactTree['truncated'] === false, 'Exactly 500 nodes must not be marked truncated.');

try {
    TreeHierarchy::Build(1000, 1, 501, false, $wideObject, static fn (int $id): array => [], $value);
    throw new RuntimeException('Tree must reject a node limit above 500.');
} catch (InvalidArgumentException) {
}

try {
    TreeHierarchy::Build(103, 2, 20, false, $object, $getChildren, $value);
    throw new RuntimeException('A variable must not be accepted as a Tree root.');
} catch (InvalidArgumentException) {
}

echo "Tree hierarchy verified.\n";
