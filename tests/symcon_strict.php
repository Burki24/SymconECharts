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
    'SetVisualizationType(1)',
    'public function GetVisualizationTile(): string',
    'UpdateVisualizationValue(',
    'JSON_UNESCAPED_UNICODE',
    'RegisterMessage($variableID, VM_UPDATE)',
    'ConfigurationFormHelper.php',
    'IPSViewHTMLPageHelper.php',
    'ResponsiveVisualizationHelper.php',
    'SVGPreviewHelper.php',
    'VisualizationAssetHelper.php',
    'VisualizationThemeHelper.php',
    'use ConfigurationFormHelper;',
    'use IPSViewHTMLPageHelper;',
    'use ResponsiveVisualizationHelper;',
    'use VisualizationAssetHelper;',
    'use VisualizationThemeHelper;'
] as $visualizationContract) {
    requireStrictContract(
        str_contains($gaugeSingle, $visualizationContract),
        'EChartsGaugeSingle is missing visualization contract: ' . $visualizationContract,
        $errors
    );
}

requireStrictContract(
    !str_contains($gaugeMulti, 'SetVisualizationType('),
    'EChartsGaugeMulti must not claim a visualization before its renderer is implemented.',
    $errors
);

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
            "RegisterPropertyInteger('Decimals', 1)",
            "RegisterPropertyString('GaugePreset', self::PRESET_SIMPLE)",
            "RegisterPropertyString('EChartsTheme', EChartsAsset::THEME_AUTO)",
            "RegisterPropertyString('PointerShape', 'preset')",
            "RegisterPropertyString('CustomPointerSVG', '')",
            "RegisterPropertyString('GaugeArcMode', 'preset')",
            "RegisterPropertyFloat('GaugeStartPosition', 270.0)",
            "RegisterPropertyFloat('GaugeEndPosition', 90.0)",
            "RegisterPropertyString('GaugeColorMode', 'theme')",
            "RegisterPropertyInteger('PointerColor', 0x55CBB5)",
            "RegisterPropertyInteger('ProgressColor', 0x55CBB5)",
            "RegisterPropertyInteger('RingColor', 0x45474C)",
            "RegisterPropertyInteger('ScaleColor', 0xA7A9AE)",
            "RegisterPropertyInteger('ValueColor', 0xF4F5F7)",
            "RegisterPropertyInteger('TitleColor', 0xA7A9AE)",
            'RegisterPropertyInteger($propertyName, self::DESIGN_SCALE_DEFAULT)'
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

        $gaugePresetElement = null;
        $findGaugePreset = static function (array $items) use (&$findGaugePreset, &$gaugePresetElement): void
        {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['name'] ?? null) === 'GaugePreset') {
                    $gaugePresetElement = $item;
                    return;
                }
                if (is_array($item['items'] ?? null)) {
                    $findGaugePreset($item['items']);
                }
            }
        };
        $findGaugePreset($gaugeForm['elements'] ?? []);
        requireStrictContract(
            is_array($gaugePresetElement)
                && ($gaugePresetElement['type'] ?? null) === 'Select'
                && array_column($gaugePresetElement['options'] ?? [], 'value') === [
                    'basic',
                    'simple',
                    'progress',
                    'speed'
                ],
            'EChartsGaugeSingle tile designer must expose the four stable Gauge presets.',
            $errors
        );

        $themeElement = null;
        $findTheme = static function (array $items) use (&$findTheme, &$themeElement): void
        {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['name'] ?? null) === 'EChartsTheme') {
                    $themeElement = $item;
                    return;
                }
                if (is_array($item['items'] ?? null)) {
                    $findTheme($item['items']);
                }
            }
        };
        $findTheme($gaugeForm['elements'] ?? []);
        requireStrictContract(
            is_array($themeElement)
                && ($themeElement['type'] ?? null) === 'Select'
                && array_column($themeElement['options'] ?? [], 'value') === [
                    'auto',
                    'dark',
                    'vintage',
                    'macarons',
                    'infographic',
                    'shine',
                    'roma'
                ],
            'EChartsGaugeSingle tile designer must expose the supported ECharts themes.',
            $errors
        );

        $fineTuningElements = [];
        $findFineTuningElements = static function (array $items) use (&$findFineTuningElements, &$fineTuningElements): void
        {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (is_string($item['name'] ?? null)) {
                    $fineTuningElements[$item['name']] = $item;
                }
                if (is_array($item['items'] ?? null)) {
                    $findFineTuningElements($item['items']);
                }
            }
        };
        $findFineTuningElements($gaugeForm['elements'] ?? []);
        foreach ([
            'ScaleFontSizePercent',
            'ValueFontSizePercent',
            'UnitFontSizePercent',
            'TitleFontSizePercent',
            'RingWidthPercent',
            'PointerWidthPercent',
            'MinorTickLengthPercent',
            'MajorTickLengthPercent'
        ] as $fineTuningName) {
            $fineTuningElement = $fineTuningElements[$fineTuningName] ?? null;
            requireStrictContract(
                is_array($fineTuningElement)
                    && ($fineTuningElement['type'] ?? null) === 'NumberSpinner'
                    && ($fineTuningElement['minimum'] ?? null) === 50
                    && ($fineTuningElement['maximum'] ?? null) === 150
                    && ($fineTuningElement['suffix'] ?? null) === '%',
                'EChartsGaugeSingle tile designer fine tuning is invalid for ' . $fineTuningName . '.',
                $errors
            );
            requireStrictContract(
                str_contains($gauge, "'" . lcfirst($fineTuningName) . "'"),
                'EChartsGaugeSingle is missing the fine-tuning property contract for ' . $fineTuningName . '.',
                $errors
            );
        }

        foreach ([
            'PointerShape'       => ['type' => 'Select', 'values' => ['preset', 'needle', 'line', 'arrow', 'custom']],
            'CustomPointerSVG'   => ['type' => 'SelectFile'],
            'GaugeArcMode'       => ['type' => 'Select', 'values' => ['preset', 'full', 'three-quarter', 'half', 'quarter', 'custom']],
            'GaugeStartPosition' => ['type' => 'Select'],
            'GaugeEndPosition'   => ['type' => 'Select'],
            'GaugeColorMode'     => ['type' => 'Select', 'values' => ['theme', 'custom']],
            'PointerColor'       => ['type' => 'SelectColor'],
            'ProgressColor'      => ['type' => 'SelectColor'],
            'RingColor'          => ['type' => 'SelectColor'],
            'ScaleColor'         => ['type' => 'SelectColor'],
            'ValueColor'         => ['type' => 'SelectColor'],
            'TitleColor'         => ['type' => 'SelectColor']
        ] as $designerName => $contract) {
            $designerElement = $fineTuningElements[$designerName] ?? null;
            $valid = is_array($designerElement) && ($designerElement['type'] ?? null) === $contract['type'];
            if (isset($contract['values'])) {
                $valid = $valid && array_column($designerElement['options'] ?? [], 'value') === $contract['values'];
            }
            if (in_array($designerName, ['GaugeStartPosition', 'GaugeEndPosition'], true)) {
                $valid = $valid
                    && count($designerElement['options'] ?? []) === 16
                    && (float) array_column($designerElement['options'] ?? [], 'value')[0] === 0.0
                    && (float) array_column($designerElement['options'] ?? [], 'value')[15] === 337.5;
            }
            if ($designerName === 'CustomPointerSVG') {
                $valid = $valid && ($designerElement['extensions'] ?? null) === '.svg';
            }
            requireStrictContract(
                $valid,
                'EChartsGaugeSingle tile designer contract is invalid for ' . $designerName . '.',
                $errors
            );
        }
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
