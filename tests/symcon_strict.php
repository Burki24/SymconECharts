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
$gaugeSingle = (string) file_get_contents($root . '/EChartsGaugeSingle/module.php');
$gaugeMulti = (string) file_get_contents($root . '/EChartsGaugeMulti/module.php');
$sharedGatewayGuidance = 'One shared EChartsGateway is sufficient for all ECharts chart instances. '
    . 'When adding further charts, select the existing gateway instead of creating another one.';

foreach ([
    'EChartsGateway'     => $gateway,
    'EChartsGaugeSingle' => $gaugeSingle,
    'EChartsGaugeMulti'  => $gaugeMulti
] as $moduleName => $source) {
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
    'EChartsGaugeSingle' => ['source' => $gaugeSingle, 'prefix' => 'ECGS'],
    'EChartsGaugeMulti'  => ['source' => $gaugeMulti, 'prefix' => 'ECGM']
] as $gaugeModuleName => $gaugeContract) {
    $gauge = $gaugeContract['source'];
    foreach ([
        'public function Create(): void',
        'public function ApplyChanges(): void',
        'public function GetCompatibleParents(): string',
        'public function GetGaugeData(): string',
        'public function ReceiveData(string $JSONString): string'
    ] as $signature) {
        requireStrictContract(
            str_contains($gauge, $signature),
            $gaugeModuleName . ' is missing strict signature: ' . $signature,
            $errors
        );
    }

    requireStrictContract(
        str_contains($gauge, "'type'      => 'connect'")
            && str_contains($gauge, 'self::GATEWAY_MODULE_ID'),
        $gaugeModuleName . ' must explicitly offer shared existing gateway instances.',
        $errors
    );

    $propertyContracts = $gaugeModuleName === 'EChartsGaugeSingle'
        ? [
            "RegisterPropertyInteger('SourceVariableID', 0)",
            "RegisterPropertyFloat('Minimum', 0.0)",
            "RegisterPropertyFloat('Maximum', 100.0)",
            "RegisterPropertyString('Title', '')",
            "RegisterPropertyString('Unit', '')",
            "RegisterPropertyInteger('Decimals', 1)"
        ]
        : [
            "RegisterPropertyString('Sources', '[]')",
            "RegisterPropertyString('Title', '')"
        ];
    foreach ($propertyContracts as $propertyContract) {
        requireStrictContract(
            str_contains($gauge, $propertyContract),
            $gaugeModuleName . ' is missing property: ' . $propertyContract,
            $errors
        );
    }

    $gaugeForm = json_decode(
        (string) file_get_contents($root . '/' . $gaugeModuleName . '/form.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    requireStrictContract(
        in_array(
            ['type' => 'Label', 'caption' => $sharedGatewayGuidance],
            $gaugeForm['elements'] ?? [],
            true
        ),
        $gaugeModuleName . ' form must explain that one shared gateway is sufficient.',
        $errors
    );
    $gaugeLocale = json_decode(
        (string) file_get_contents($root . '/' . $gaugeModuleName . '/locale.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    requireStrictContract(
        isset($gaugeLocale['translations']['de'][$sharedGatewayGuidance]),
        $gaugeModuleName . ' must translate the shared gateway guidance.',
        $errors
    );
    $formNames = [];
    foreach ($gaugeForm['elements'] ?? [] as $element) {
        if (isset($element['name']) && is_string($element['name'])) {
            $formNames[] = $element['name'];
        }
    }
    sort($formNames);
    $expectedFormNames = $gaugeModuleName === 'EChartsGaugeSingle'
        ? ['Decimals', 'Maximum', 'Minimum', 'SourceVariableID', 'Title', 'Unit']
        : ['Sources', 'Title'];
    sort($expectedFormNames);
    requireStrictContract(
        $formNames === $expectedFormNames,
        $gaugeModuleName . ' form properties do not match the module contract.',
        $errors
    );

    $sourceElement = null;
    foreach ($gaugeForm['elements'] ?? [] as $element) {
        $expectedSourceName = $gaugeModuleName === 'EChartsGaugeSingle' ? 'SourceVariableID' : 'Sources';
        if (($element['name'] ?? null) === $expectedSourceName) {
            $sourceElement = $element;
        }
    }
    if ($gaugeModuleName === 'EChartsGaugeSingle') {
        requireStrictContract(
            is_array($sourceElement)
                && ($sourceElement['type'] ?? null) === 'SelectVariable'
                && ($sourceElement['validVariableTypes'] ?? null) === [1, 2],
            'EChartsGaugeSingle source selection must accept only integer and float variables.',
            $errors
        );
    } else {
        requireStrictContract(
            is_array($sourceElement)
                && ($sourceElement['type'] ?? null) === 'List'
                && ($sourceElement['changeOrder'] ?? null) === true,
            'EChartsGaugeMulti sources must be an ordered configuration list.',
            $errors
        );
        $columns = array_column($sourceElement['columns'] ?? [], null, 'name');
        requireStrictContract(
            isset($columns['VariableID'])
                && (($columns['VariableID']['edit']['type'] ?? null) === 'SelectVariable')
                && (($columns['VariableID']['edit']['validVariableTypes'] ?? null) === [1, 2]),
            'EChartsGaugeMulti source rows must select numeric variables.',
            $errors
        );
    }

    $expectedAction = 'echo ' . $gaugeContract['prefix'] . '_GetGaugeData($id);';
    $actionScripts = array_column($gaugeForm['actions'] ?? [], 'onClick');
    requireStrictContract(
        in_array($expectedAction, $actionScripts, true),
        $gaugeModuleName . ' form must use its own public function prefix.',
        $errors
    );
}

if ($errors !== []) {
    fwrite(STDERR, "Symcon Strict contract validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo "Symcon Strict module contracts verified.\n";
