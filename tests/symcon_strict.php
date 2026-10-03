<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

/**
 * Records a strict-module contract failure.
 *
 * @param list<string> $errors
 */
function requireStrictContract(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$gateway = (string) file_get_contents($root . '/EChartsGateway/module.php');
$gauge = (string) file_get_contents($root . '/EChartsGauge/module.php');

foreach (['EChartsGateway' => $gateway, 'EChartsGauge' => $gauge] as $moduleName => $source) {
    requireStrictContract(
        preg_match('/class\s+' . $moduleName . '\s+extends\s+IPSModuleStrict\b/', $source) === 1,
        $moduleName . ' must extend IPSModuleStrict.',
        $errors
    );
    requireStrictContract(!str_contains($source, 'RequireParent('), $moduleName . ' must not call RequireParent().', $errors);
    requireStrictContract(!str_contains($source, 'utf8_decode('), $moduleName . ' must not use legacy UTF-8 conversion.', $errors);
    requireStrictContract(
        str_contains($source, 'DataFlowHelper.php') && str_contains($source, 'use DataFlowHelper;'),
        $moduleName . ' must use the shared DataFlowHelper.',
        $errors
    );
}

foreach ([
    'public function Create(): void',
    'public function ApplyChanges(): void',
    'public function ForwardData(string $JSONString): string'
] as $signature) {
    requireStrictContract(str_contains($gateway, $signature), 'Gateway is missing strict signature: ' . $signature, $errors);
}

foreach ([
    'public function Create(): void',
    'public function ApplyChanges(): void',
    'public function GetCompatibleParents(): string',
    'public function GetGaugeData(): string',
    'public function ReceiveData(string $JSONString): string'
] as $signature) {
    requireStrictContract(str_contains($gauge, $signature), 'Gauge is missing strict signature: ' . $signature, $errors);
}

requireStrictContract(
    str_contains($gauge, "'type'      => 'connect'")
        && str_contains($gauge, 'self::GATEWAY_MODULE_ID'),
    'Gauge must explicitly offer shared existing gateway instances.',
    $errors
);

$propertyContracts = [
    "RegisterPropertyInteger('SourceVariableID', 0)",
    "RegisterPropertyFloat('Minimum', 0.0)",
    "RegisterPropertyFloat('Maximum', 100.0)",
    "RegisterPropertyString('Title', '')",
    "RegisterPropertyString('Unit', '')",
    "RegisterPropertyInteger('Decimals', 1)"
];
foreach ($propertyContracts as $propertyContract) {
    requireStrictContract(str_contains($gauge, $propertyContract), 'Gauge is missing property: ' . $propertyContract, $errors);
}

$gaugeForm = json_decode(
    (string) file_get_contents($root . '/EChartsGauge/form.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$formNames = [];
foreach ($gaugeForm['elements'] ?? [] as $element) {
    if (isset($element['name']) && is_string($element['name'])) {
        $formNames[] = $element['name'];
    }
}
sort($formNames);
$expectedFormNames = ['Decimals', 'Maximum', 'Minimum', 'SourceVariableID', 'Title', 'Unit'];
sort($expectedFormNames);
requireStrictContract($formNames === $expectedFormNames, 'Gauge form properties do not match the module contract.', $errors);

$sourceSelector = null;
foreach ($gaugeForm['elements'] ?? [] as $element) {
    if (($element['name'] ?? null) === 'SourceVariableID') {
        $sourceSelector = $element;
    }
}
requireStrictContract(
    is_array($sourceSelector)
        && ($sourceSelector['type'] ?? null) === 'SelectVariable'
        && ($sourceSelector['validVariableTypes'] ?? null) === [1, 2],
    'Gauge source selection must accept only integer and float variables.',
    $errors
);

if ($errors !== []) {
    fwrite(STDERR, "Symcon Strict contract validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo "Symcon Strict module contracts verified.\n";
