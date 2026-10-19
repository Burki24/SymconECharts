<?php

declare(strict_types=1);

use SymconECharts\TreemapModel;

require_once dirname(__DIR__) . '/libs/TreemapModel.php';

$sources = [
    ['VariableID' => 10, 'Group' => 'House', 'Subgroup' => 'Power', 'Label' => ''],
    ['VariableID' => 11, 'Group' => 'House', 'Subgroup' => 'Power', 'Label' => 'Light'],
    ['VariableID' => 12, 'Group' => 'Garden', 'Subgroup' => '', 'Label' => '']
];
$values = [
    10 => ['Value' => 20.0, 'Timestamp' => 100, 'Name' => 'Light'],
    11 => ['Value' => 10.0, 'Timestamp' => 101, 'Name' => 'Other'],
    12 => ['Value' => 0.0, 'Timestamp' => 102, 'Name' => 'Pump']
];
$model = TreemapModel::Build($sources, $values);
if ($model['positiveCount'] !== 2 || $model['total'] !== 30.0
    || $model['data'][0]['name'] !== 'House'
    || $model['data'][0]['value'] !== 30.0
    || $model['data'][0]['children'][0]['value'] !== 30.0
    || $model['data'][0]['children'][0]['children'][0]['id'] !== 'variable-10'
    || $model['data'][0]['children'][0]['children'][1]['id'] !== 'variable-11'
    || $model['data'][1]['children'][0]['id'] !== 'variable-12') {
    throw new RuntimeException('Treemap grouping, sums or variable identities are incorrect.');
}
$mixed = TreemapModel::Build([
    ['VariableID' => 12, 'Group' => 'House', 'Subgroup' => '', 'Label' => 'Pump'],
    ...array_slice($sources, 0, 2)
], $values);
if ($mixed['data'][0]['children'][0]['id'] !== 'variable-12'
    || $mixed['data'][0]['children'][1]['name'] !== 'Power') {
    throw new RuntimeException('Treemap must retain configured order when direct leaves and subgroups are mixed.');
}
$zeros = TreemapModel::Build([$sources[2]], $values);
if ($zeros['positiveCount'] !== 0 || $zeros['total'] !== 0.0) {
    throw new RuntimeException('Zero-valued Treemap data must remain valid and visibly empty.');
}
foreach ([
    [[$sources[0], $sources[0]], $values],
    [$sources, array_replace($values, [10 => ['Value' => -1.0, 'Timestamp' => 100, 'Name' => 'Light']])],
    [$sources, array_replace($values, [10 => ['Value' => INF, 'Timestamp' => 100, 'Name' => 'Light']])],
    [array_fill(0, 101, $sources[0]), $values]
] as [$invalidSources, $invalidValues]) {
    try {
        TreemapModel::Build($invalidSources, $invalidValues);
        throw new RuntimeException('Invalid Treemap data was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Treemap model verified.\n";

$deepSources = [['VariableID' => 10, 'Group' => 'Legacy ignored', 'Subgroup' => 'Old',
    'GroupPath'               => 'House / Floor / Room / Power / Rack / Circuit / Device', 'Label' => 'Leaf']];
$deep = TreemapModel::Build($deepSources, $values);
$sharedPrefixes = TreemapModel::Build([
    $sources[0], ['VariableID' => 11, 'GroupPath' => 'House / Power / Rack', 'Label' => 'Light'],
    ['VariableID' => 12, 'GroupPath' => 'Garden / Power / Rack', 'Label' => 'Light']
], $values);
if (count($sharedPrefixes['data']) !== 2
    || count($sharedPrefixes['data'][0]['children']) !== 1
    || count($sharedPrefixes['data'][0]['children'][0]['children']) !== 2
    || $sharedPrefixes['data'][0]['children'][0]['children'][0]['id'] !== 'variable-10'
    || $sharedPrefixes['data'][0]['children'][0]['children'][1]['children'][0]['id'] !== 'variable-11'
    || $sharedPrefixes['data'][0]['children'][0]['children'][1]['id'] === $sharedPrefixes['data'][1]['children'][0]['children'][0]['id']) {
    throw new RuntimeException('Shared full prefixes must merge in source order, but equal names under other parents remain separate.');
}
$deepNode = $deep['data'][0];
foreach (['House', 'Floor', 'Room', 'Power', 'Rack', 'Circuit', 'Device', 'Leaf'] as $depth => $name) {
    if ($deepNode['name'] !== $name || $deepNode['value'] !== 20.0) {
        throw new RuntimeException('A configured hierarchy must retain all eight levels and correct ancestor sums.');
    }
    if ($depth < 7) {
        $deepNode = $deepNode['children'][0];
    }
}
if ($deep['depth'] !== 8 || $deep['nodeCount'] !== 8 || $deepNode['id'] !== 'variable-10') {
    throw new RuntimeException('Hierarchy depth excludes the synthetic root and includes the variable leaf.');
}
if ($model['data'][0]['id'] !== 'group-' . sha1('House')
    || $model['data'][0]['children'][0]['id'] !== 'subgroup-' . sha1("House\0Power")) {
    throw new RuntimeException('Legacy hierarchy IDs must remain stable without a configuration migration.');
}
$literal = TreemapModel::Build([['VariableID' => 10, 'Group' => 'House / literal', 'Subgroup' => 'Power / literal', 'Label' => 'Leaf']], $values);
if ($literal['data'][0]['name'] !== 'House / literal'
    || $literal['data'][0]['children'][0]['name'] !== 'Power / literal') {
    throw new RuntimeException('Legacy names containing slashes must never be interpreted as paths.');
}
if (TreemapModel::Path(['GroupPath' => ' House \\/ annex / Room \\\\ server ']) !== ['House / annex', 'Room \\ server']) {
    throw new RuntimeException('Explicit paths must support literal slash and backslash escaping.');
}
foreach (['/ House', 'House /', 'House // Room', 'House / \\unknown',
    'A/B/C/D/E/F/G/H', str_repeat('x', 81)] as $badPath) {
    try {
        TreemapModel::Build([['VariableID' => 10, 'GroupPath' => $badPath, 'Label' => 'Leaf']], $values);
        throw new LogicException('Invalid or over-deep hierarchy paths must fail without truncation.');
    } catch (InvalidArgumentException) {
    }
}
foreach ([null, 5, [], ['House']] as $badPath) {
    try {
        TreemapModel::Path(['GroupPath' => $badPath]);
        throw new LogicException('The persisted GroupPath input must be a string.');
    } catch (InvalidArgumentException) {
    }
}
$manySources = $manyValues = [];
for ($index = 0; $index < 100; $index++) {
    $id = 1000 + $index;
    $manySources[] = ['VariableID' => $id, 'Path' => array_map(static fn (int $level): string => 'Branch ' . $index . ':' . $level, range(1, 7)), 'Label' => 'Equal leaf'];
    $manyValues[$id] = ['Value' => 1.0, 'Timestamp' => 100, 'Name' => 'Equal leaf'];
}
$maximumHierarchy = TreemapModel::Build($manySources, $manyValues);
if ($maximumHierarchy['nodeCount'] !== 800 || $maximumHierarchy['depth'] !== 8 || $maximumHierarchy['total'] !== 100.0) {
    throw new RuntimeException('All 100 deepest independent sources must fit the explicit 800-node structural budget.');
}
foreach ($manySources as &$source) {
    $source['Path'] = array_fill(0, 7, str_repeat('&', 80));
}
unset($source);
// Separate long branches are needed to exercise the complete encoded model budget.
foreach ($manySources as $index => &$source) {
    $source['Path'][0] = str_pad((string) $index, 80, '&');
}
unset($source);
try {
    TreemapModel::Build($manySources, $manyValues);
    throw new LogicException('HTML-safe model budgets must reject oversized trees instead of silently cutting branches.');
} catch (InvalidArgumentException) {
}
echo "Eight-level hierarchy paths, stable legacy IDs and resource budgets verified.\n";
