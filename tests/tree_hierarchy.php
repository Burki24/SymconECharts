<?php

declare(strict_types=1);

use SymconECharts\TreeHierarchy;

require_once dirname(__DIR__) . '/libs/TreeHierarchy.php';

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

$symconRoot = TreeHierarchy::Build(
    0,
    1,
    20,
    false,
    static fn (int $id): array => $id === 0
        ? ['ObjectType' => 0, 'ObjectName' => 'Symcon', 'ObjectIsHidden' => false]
        : $objects[$id],
    static fn (int $id): array => $id === 0 ? [101] : [],
    $value
);
assertTree(
    $symconRoot['root']['id'] === 'object-0'
        && $symconRoot['root']['children'][0]['id'] === 'object-101'
        && $symconRoot['count'] === 2,
    'The Symcon root must be represented as a stable top-level Tree node.'
);

$shallow = TreeHierarchy::Build(100, 1, 20, false, $object, $getChildren, $value);
assertTree($shallow['count'] === 2 && $shallow['root']['children'][0]['children'] === [], 'Maximum depth was ignored.');

$limited = TreeHierarchy::Build(100, 3, 3, true, $object, $getChildren, $value);
assertTree($limited['count'] === 3 && $limited['truncated'] === true, 'Node limit was not reported.');

$visible = TreeHierarchy::Build(100, 2, 20, true, $object, $getChildren, $value);
assertTree($visible['count'] === 5 && $visible['root']['children'][1]['name'] === 'Intern', 'Hidden branch inclusion failed.');

$filterObjects = [
    500 => ['ObjectType' => 0, 'ObjectName' => 'Haus', 'ObjectIsHidden' => false],
    501 => ['ObjectType' => 0, 'ObjectName' => 'Keller', 'ObjectIsHidden' => false],
    502 => ['ObjectType' => 1, 'ObjectName' => 'Sensor', 'ObjectIsHidden' => false],
    503 => ['ObjectType' => 2, 'ObjectName' => 'Batterie', 'ObjectIsHidden' => false],
    504 => ['ObjectType' => 2, 'ObjectName' => 'Temperatur', 'ObjectIsHidden' => false],
    505 => ['ObjectType' => 0, 'ObjectName' => 'Dach', 'ObjectIsHidden' => false],
    506 => ['ObjectType' => 2, 'ObjectName' => 'Batteriestand', 'ObjectIsHidden' => false],
    507 => ['ObjectType' => 0, 'ObjectName' => 'Versteckt', 'ObjectIsHidden' => true],
    508 => ['ObjectType' => 2, 'ObjectName' => 'Batteriealarm', 'ObjectIsHidden' => false]
];
$filterChildren = [500 => [501, 505, 507], 501 => [502], 502 => [503, 504], 505 => [506], 507 => [508]];
$filterObject = static fn (int $id): array => $filterObjects[$id];
$filterGetChildren = static fn (int $id): array => $filterChildren[$id] ?? [];
$filtered = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, 'bAtTeRiE');
assertTree($filtered['count'] === 6 && $filtered['truncated'] === false, 'Filtered node count is wrong.');
assertTree(
    $filtered['root']['children'][0]['children'][0]['children'][0]['id'] === 'object-503'
        && count($filtered['root']['children'][0]['children'][0]['children']) === 1
        && $filtered['root']['children'][1]['children'][0]['id'] === 'object-506'
        && count($filtered['root']['children']) === 2,
    'Name filtering must retain only matching variables and their visible ancestors.'
);
assertTree($filtered['variableIDs'] === [503, 506], 'Filtered variable IDs must remain object identities.');
$noMatches = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, 'nicht vorhanden');
assertTree($noMatches['count'] === 1 && $noMatches['root']['children'] === [], 'No-match filtering must retain only the root.');
$shallowFilter = TreeHierarchy::Build(500, 1, 20, false, $filterObject, $filterGetChildren, $value, 'Batterie');
assertTree($shallowFilter['count'] === 1, 'Name filtering must respect maximum depth.');
$hiddenFilter = TreeHierarchy::Build(500, 3, 20, true, $filterObject, $filterGetChildren, $value, 'Batterie');
assertTree($hiddenFilter['count'] === 8 && $hiddenFilter['root']['children'][2]['children'][0]['id'] === 'object-508', 'Hidden branches must remain opt-in when filtering.');
$limitedFilter = TreeHierarchy::Build(500, 3, 4, false, $filterObject, $filterGetChildren, $value, 'Batterie');
assertTree($limitedFilter['count'] === 4 && $limitedFilter['truncated'] === true, 'Filtered output must obey the visible node limit.');
$unfilteredWhitespace = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, '   ');
assertTree($unfilteredWhitespace['count'] === 7, 'A blank filter must preserve the unfiltered Tree.');
$ruleFiltered = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, '', [506]);
assertTree(
    $ruleFiltered['count'] === 3
        && $ruleFiltered['variableIDs'] === [506]
        && $ruleFiltered['root']['children'][0]['id'] === 'object-505'
        && $ruleFiltered['root']['children'][0]['children'][0]['id'] === 'object-506',
    'Rule filtering must retain only selected variables and their ancestors.'
);
$ruleNoMatch = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, '', []);
assertTree($ruleNoMatch['count'] === 1 && $ruleNoMatch['root']['children'] === [], 'No selected rules must retain only the root.');
$ruleNameIntersection = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, 'Batterie', [504]);
assertTree($ruleNameIntersection['count'] === 1, 'Name and rule filters must intersect.');
$ruleNameMatch = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, 'Batterie', [503, 504]);
assertTree($ruleNameMatch['variableIDs'] === [503], 'Matching names without rules or rules without matching names must be hidden.');
$ruleHidden = TreeHierarchy::Build(500, 3, 20, false, $filterObject, $filterGetChildren, $value, '', [508]);
assertTree($ruleHidden['count'] === 1, 'Rule filtering must not reveal hidden branches.');
$ruleHiddenIncluded = TreeHierarchy::Build(500, 3, 20, true, $filterObject, $filterGetChildren, $value, '', [508]);
assertTree($ruleHiddenIncluded['variableIDs'] === [508], 'Explicitly included hidden branches may contain rule variables.');
$ruleLimited = TreeHierarchy::Build(500, 3, 4, false, $filterObject, $filterGetChildren, $value, '', [503, 506]);
assertTree($ruleLimited['count'] === 4 && $ruleLimited['truncated'] === true, 'Rule-filtered output must obey the visible node limit.');

$lateChildren = range(10001, 13000);
$lateObject = static fn (int $id): array => [
    'ObjectType'     => $id === 9999 ? 0 : 2,
    'ObjectName'     => $id === 13000 ? 'Batterie' : 'Temperatur',
    'ObjectIsHidden' => false
];
$lateMatch = TreeHierarchy::Build(
    9999,
    1,
    10,
    false,
    $lateObject,
    static fn (int $id): array => $id === 9999 ? $lateChildren : [],
    $value,
    'Batterie'
);
assertTree($lateMatch['count'] === 2 && $lateMatch['root']['children'][0]['id'] === 'object-13000', 'The filter must find matches after the visible node limit.');
$lateRule = TreeHierarchy::Build(
    9999,
    1,
    10,
    false,
    $lateObject,
    static fn (int $id): array => $id === 9999 ? $lateChildren : [],
    $value,
    '',
    [13000]
);
assertTree($lateRule['count'] === 2 && $lateRule['root']['children'][0]['id'] === 'object-13000', 'Rule filtering must find matches after the visible node limit.');

$scanChildren = range(20001, 50000);
$scanObject = static fn (int $id): array => [
    'ObjectType'     => $id === 20000 ? 0 : 2,
    'ObjectName'     => 'Temperatur',
    'ObjectIsHidden' => false
];
try {
    TreeHierarchy::Build(
        20000,
        1,
        10,
        false,
        $scanObject,
        static fn (int $id): array => $id === 20000 ? $scanChildren : [],
        $value,
        'Batterie'
    );
    throw new RuntimeException('Tree must reject a filtered scan beyond its safety limit.');
} catch (RuntimeException $exception) {
    assertTree(str_contains($exception->getMessage(), 'scan limit'), 'The filtered scan must fail visibly at its safety limit.');
}

$wideChildren = range(1001, 4000);
$wideObject = static fn (int $id): array => [
    'ObjectType'     => $id === 1000 ? 0 : 2,
    'ObjectName'     => 'Tree object ' . $id,
    'ObjectIsHidden' => false
];
$wideTree = TreeHierarchy::Build(
    1000,
    1,
    3000,
    false,
    $wideObject,
    static fn (int $id): array => $id === 1000 ? $wideChildren : [],
    static fn (int $id): string => (string) $id
);
assertTree($wideTree['count'] === 3000, 'Tree must accept the configured 3000-node limit.');
assertTree($wideTree['truncated'] === true, 'Tree must report the 3001st node as truncated.');
assertTree(count($wideTree['root']['children']) === 2999, 'Tree must retain exactly 2999 children beside its root.');
$exactTree = TreeHierarchy::Build(
    1000,
    1,
    3000,
    false,
    $wideObject,
    static fn (int $id): array => $id === 1000 ? array_slice($wideChildren, 0, 2999) : [],
    static fn (int $id): string => (string) $id
);
assertTree($exactTree['count'] === 3000 && $exactTree['truncated'] === false, 'Exactly 3000 nodes must not be marked truncated.');

try {
    TreeHierarchy::Build(1000, 1, 3001, false, $wideObject, static fn (int $id): array => [], $value);
    throw new RuntimeException('Tree must reject a node limit above 3000.');
} catch (InvalidArgumentException) {
}

try {
    TreeHierarchy::Build(103, 2, 20, false, $object, $getChildren, $value);
    throw new RuntimeException('A variable must not be accepted as a Tree root.');
} catch (InvalidArgumentException) {
}

$deepObjects = $deepChildren = [];
for ($level = 0; $level <= 13; ++$level) {
    $deepObjects[6000 + $level] = ['ObjectType' => $level === 12 || $level === 13 ? 2 : 0,
        'ObjectName'                            => $level >= 12 ? 'Battery' : 'Level ' . $level, 'ObjectIsHidden' => false];
    if ($level < 11) {
        $deepChildren[6000 + $level] = [6001 + $level];
    }
}
$deepChildren[6011] = [6012, 6013];
$deepObject = static fn (int $id): array => $deepObjects[$id];
$deepGetChildren = static fn (int $id): array => $deepChildren[$id] ?? [];
foreach (['', 'battery'] as $filter) {
    $deepTree = TreeHierarchy::Build(6000, 12, 80, false, $deepObject, $deepGetChildren, $value, $filter);
    assertTree(
        $deepTree['count'] === 14 && $deepTree['variableIDs'] === [6012, 6013],
        'Twelve descendant levels must retain distinct same-name variables and all ancestors.'
    );
    $node = $deepTree['root'];
    for ($level = 0; $level < 12; ++$level) {
        assertTree($node['id'] === 'object-' . (6000 + $level), 'Deep object identities changed.');
        $node = $node['children'][0];
    }
    assertTree($node['id'] === 'object-6012', 'The twelfth descendant level was not retained.');
}
$deepLimited = TreeHierarchy::Build(6000, 12, 10, false, $deepObject, $deepGetChildren, $value);
assertTree($deepLimited['count'] === 10 && $deepLimited['truncated'], 'Deep trees must retain the node budget.');
try {
    TreeHierarchy::Build(6000, 13, 80, false, $deepObject, $deepGetChildren, $value);
    throw new LogicException('Thirteen descendant levels must remain invalid.');
} catch (InvalidArgumentException) {
}

echo "Tree hierarchy verified.\n";
