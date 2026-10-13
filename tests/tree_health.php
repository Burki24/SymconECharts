<?php

declare(strict_types=1);

use SymconECharts\TreeHealth;

require_once dirname(__DIR__) . '/EChartsTree/TreeHealth.php';

function assertTreeHealth(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$rules = TreeHealth::Parse([
    ['VariableID' => 11, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2],
    ['VariableID' => 12, 'Direction' => 'low', 'WarningAt' => 25, 'CriticalAt' => 10]
]);
$root = [
    'id' => 'object-1', 'name' => 'House', 'kind' => 'category', 'children' => [
        ['id' => 'object-2', 'name' => 'Room', 'kind' => 'category', 'children' => [
            ['id' => 'object-11', 'name' => 'Alarm', 'kind' => 'variable', 'children' => []],
            ['id' => 'object-12', 'name' => 'Battery', 'kind' => 'variable', 'children' => []],
            ['id' => 'object-13', 'name' => 'Temperature', 'kind' => 'variable', 'children' => []]
        ]]
    ]
];
$values = [11 => 1, 12 => 8.5];
$read = static fn (int $id): mixed => $values[$id];
$result = TreeHealth::Annotate($root, $rules, $read);
assertTreeHealth($result['health'] === 'critical', 'Root must reflect the worst visible descendant.');
assertTreeHealth($result['children'][0]['health'] === 'critical', 'Room must reflect a critical descendant.');
assertTreeHealth($result['children'][0]['children'][0]['health'] === 'warning', 'High threshold must yield warning.');
assertTreeHealth($result['children'][0]['children'][1]['health'] === 'critical', 'Low threshold must yield critical.');
assertTreeHealth(!isset($result['children'][0]['children'][2]['health']), 'Unmonitored values must remain neutral.');
assertTreeHealth($values === [11 => 1, 12 => 8.5], 'Status evaluation must not write source values.');

$healthy = TreeHealth::Annotate($root, $rules, static fn (int $id): int => $id === 11 ? 0 : 50);
assertTreeHealth($healthy['health'] === 'ok', 'Healthy status must propagate without coloring unmonitored nodes.');
$boolRule = TreeHealth::Parse('[{"VariableID":11,"Direction":"high","WarningAt":1,"CriticalAt":1}]');
$critical = TreeHealth::Annotate($root, $boolRule, static fn (int $id): bool => true);
assertTreeHealth($critical['health'] === 'critical', 'Boolean true must support a critical rule.');
$falseRule = TreeHealth::Parse('[{"VariableID":11,"Direction":"low","WarningAt":0,"CriticalAt":0}]');
$falseAlarm = TreeHealth::Annotate($root, $falseRule, static fn (int $id): bool => false);
assertTreeHealth($falseAlarm['health'] === 'critical', 'Boolean false must support a critical rule.');
$lowWarning = TreeHealth::Annotate($root, $rules, static fn (int $id): float => $id === 11 ? 0 : 20.0);
assertTreeHealth($lowWarning['health'] === 'warning', 'Low-direction warning threshold must be applied.');
assertTreeHealth(TreeHealth::Annotate($root, [], $read) === $root, 'No rules must preserve the old Tree model.');

try {
    TreeHealth::Annotate($root, $boolRule, static fn (int $id): string => 'not numeric');
    throw new RuntimeException('A nonnumeric status source was accepted.');
} catch (RuntimeException $exception) {
    assertTreeHealth($exception->getMessage() === 'A configured Tree status value is not numeric.', 'Wrong read failure.');
}

foreach ([
    [['VariableID' => 11, 'Direction' => 'high', 'WarningAt' => 2, 'CriticalAt' => 1]],
    [['VariableID' => 11, 'Direction' => 'low', 'WarningAt' => 1, 'CriticalAt' => 2]],
    [['VariableID' => 11, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2],
        ['VariableID' => 11, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]],
    [['VariableID' => 0, 'Direction' => 'high', 'WarningAt' => 1, 'CriticalAt' => 2]],
    [['VariableID' => 11, 'Direction' => 'other', 'WarningAt' => 1, 'CriticalAt' => 2]]
] as $invalid) {
    try {
        TreeHealth::Parse($invalid);
        throw new RuntimeException('An invalid status rule was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "Tree health aggregation verified.\n";
