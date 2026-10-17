<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

/**
 * Reads a required JSON object.
 *
 * @return array<string, mixed>
 */
function readContractJson(string $path): array
{
    $data = json_decode(
        (string) file_get_contents($path),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($data)) {
        throw new RuntimeException($path . ' must contain a JSON object.');
    }

    return $data;
}

/**
 * Records a contract mismatch.
 *
 * @param list<string> $errors
 */
function requireContract(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$library = readContractJson($root . '/library.json');
requireContract(
    ($library['id'] ?? null) === '{66BE21BE-988A-10EB-1BBB-1E2F444E9F85}',
    'The library GUID changed.',
    $errors
);
requireContract(($library['name'] ?? null) === 'SymconECharts', 'The library name changed.', $errors);
requireContract(
    ($library['author'] ?? null) === 'Burkhard Kneiseler',
    'The library author changed.',
    $errors
);
requireContract(
    ($library['url'] ?? null) === 'https://github.com/Burki24/SymconECharts',
    'The library URL changed.',
    $errors
);
requireContract(
    ($library['compatibility'] ?? null) === ['version' => '9.0'],
    'The library must target IP-Symcon 9.0.',
    $errors
);

$expectedModules = [
    'EChartsGateway' => [
        'id'                 => '{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}',
        'type'               => 2,
        'prefix'             => 'ECGW',
        'parentRequirements' => [],
        'childRequirements'  => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}'],
        'implemented'        => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}']
    ],
    'EChartsGaugeSingle' => [
        'id'                 => '{0CAA2780-342F-E5B9-2865-CB8DCED0BE7C}',
        'type'               => 3,
        'prefix'             => 'ECGS',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsGaugeMulti' => [
        'id'                 => '{E667D9C1-379D-44ED-A313-FF21FEFC355F}',
        'type'               => 3,
        'prefix'             => 'ECGM',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsGaugeTacho' => [
        'id'                 => '{9D072BFE-45B4-4442-B1D2-4FC458C3BABD}',
        'type'               => 3,
        'prefix'             => 'ECGT',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsGaugeChronograph' => [
        'id'                 => '{B99C3ADA-9A90-486F-97E3-96C39554D9A6}',
        'type'               => 3,
        'prefix'             => 'ECGC',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsTimeSeries' => [
        'id'                 => '{EF172F3B-50F5-41D1-B18E-6BCDEAECCABA}',
        'type'               => 3,
        'prefix'             => 'ECTS',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsBarCategory' => [
        'id'                 => '{F9A88437-FE9E-4038-ADAF-775623D2DDC1}',
        'type'               => 3,
        'prefix'             => 'ECBC',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsBarHistory' => [
        'id'                 => '{BCC80B03-2FC9-45CA-B004-2C81D0689F50}',
        'type'               => 3,
        'prefix'             => 'ECBH',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsBarWaterfall' => [
        'id'                 => '{792B12B3-E608-4301-868D-5FE96DDB8864}',
        'type'               => 3,
        'prefix'             => 'ECBW',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsBarPolar' => [
        'id'                 => '{B22B5AB4-CDA4-416F-9FF1-4126734E078B}',
        'type'               => 3,
        'prefix'             => 'ECBP',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsPie' => [
        'id'                 => '{176F1531-ABC4-4AE5-9DE5-8F3F006C8237}',
        'type'               => 3,
        'prefix'             => 'ECPI',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsScatter' => [
        'id'                 => '{9865CDB4-5CA9-4C34-A148-983E0B5F2FA6}',
        'type'               => 3,
        'prefix'             => 'ECSC',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsHeatmap' => [
        'id'                 => '{62F06D1A-873F-4B0E-9F3E-6AA9AA1A8274}',
        'type'               => 3,
        'prefix'             => 'ECHM',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsGraph' => [
        'id'                 => '{BD3C8351-82EA-4B6B-BD82-1E034B7E68C6}',
        'type'               => 3,
        'prefix'             => 'ECGR',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsGraphZ2M' => [
        'id'                 => '{A9C67877-1CF7-4E9E-9B17-C9AC2C200483}',
        'type'               => 3,
        'prefix'             => 'ECGZ',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsTreemap' => [
        'id'                 => '{9A4CDA7E-00FE-49FB-A017-20FDA75E7C04}',
        'type'               => 3,
        'prefix'             => 'ECTM',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsSunburst' => [
        'id'                 => '{A90CEE66-8708-4C2B-A475-1BAEDC74C359}',
        'type'               => 3,
        'prefix'             => 'ECSB',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsSankey' => [
        'id'                 => '{D0F77DFF-6B50-4901-A2F0-5A98159E2559}',
        'type'               => 3,
        'prefix'             => 'ECSA',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsFunnel' => [
        'id'                 => '{491282D5-3E26-4601-AEA7-ABD7404642B2}',
        'type'               => 3,
        'prefix'             => 'ECFU',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsBoxplot' => [
        'id'                 => '{B9B5BCD1-BC63-42A6-8132-98CE744DAE1C}',
        'type'               => 3,
        'prefix'             => 'ECBX',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsCandlestick' => [
        'id'                 => '{A5C60024-BB95-4417-9632-763CBE9C14D7}',
        'type'               => 3,
        'prefix'             => 'ECCA',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsRadar' => [
        'id'                 => '{B596EE88-C7A6-4A9B-891A-DD81BFF08A94}',
        'type'               => 3,
        'prefix'             => 'ECRA',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsCalendar' => [
        'id'                 => '{3F927445-41C7-44F4-8CEB-069581C5F3D1}',
        'type'               => 3,
        'prefix'             => 'ECCL',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ],
    'EChartsParallel' => [
        'id'                 => '{2B3B30E7-F7F2-47CF-A8AB-1E27F896A7DD}',
        'type'               => 3,
        'prefix'             => 'ECPA',
        'parentRequirements' => ['{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}'],
        'childRequirements'  => [],
        'implemented'        => ['{E4749B72-912B-E3E3-1C57-D19019FFDD84}']
    ]
];

$moduleContracts = [];
$moduleIds = [];
$prefixes = [];
foreach ($expectedModules as $moduleName => $expected) {
    $module = readContractJson($root . '/' . $moduleName . '/module.json');
    $moduleContracts[$moduleName] = $module;

    requireContract(($module['name'] ?? null) === $moduleName, $moduleName . ' changed its name.', $errors);
    requireContract(($module['id'] ?? null) === $expected['id'], $moduleName . ' changed its GUID.', $errors);
    requireContract(($module['type'] ?? null) === $expected['type'], $moduleName . ' changed its type.', $errors);
    requireContract(
        ($module['prefix'] ?? null) === $expected['prefix'],
        $moduleName . ' changed its function prefix.',
        $errors
    );
    requireContract(
        ($module['url'] ?? null) === 'https://github.com/Burki24/SymconECharts',
        $moduleName . ' changed its repository URL.',
        $errors
    );

    foreach (['parentRequirements', 'childRequirements', 'implemented'] as $contractField) {
        requireContract(
            ($module[$contractField] ?? null) === $expected[$contractField],
            $moduleName . ' changed ' . $contractField . '.',
            $errors
        );
    }

    $moduleId = $module['id'] ?? null;
    if (is_string($moduleId)) {
        requireContract(!isset($moduleIds[$moduleId]), 'Duplicate module GUID: ' . $moduleId, $errors);
        $moduleIds[$moduleId] = $moduleName;
    }

    $prefix = $module['prefix'] ?? null;
    if (is_string($prefix)) {
        requireContract(!isset($prefixes[$prefix]), 'Duplicate module prefix: ' . $prefix, $errors);
        $prefixes[$prefix] = $moduleName;
    }

    $source = (string) file_get_contents($root . '/' . $moduleName . '/module.php');
    requireContract(
        preg_match('/class\s+' . preg_quote($moduleName, '/') . '\b/', $source) === 1,
        $moduleName . '/module.php does not declare the expected class.',
        $errors
    );
    requireContract(
        str_contains($source, 'declare(strict_types=1);'),
        $moduleName . '/module.php must enable strict types.',
        $errors
    );
}

$gateway = $moduleContracts['EChartsGateway'];
foreach ([
    'EChartsGaugeSingle', 'EChartsGaugeMulti', 'EChartsGaugeTacho', 'EChartsGaugeChronograph',
    'EChartsTimeSeries', 'EChartsBarCategory', 'EChartsBarHistory', 'EChartsBarWaterfall', 'EChartsBarPolar',
    'EChartsCandlestick', 'EChartsBoxplot', 'EChartsFunnel', 'EChartsPie', 'EChartsScatter', 'EChartsHeatmap', 'EChartsGraph', 'EChartsGraphZ2M', 'EChartsTreemap', 'EChartsSunburst', 'EChartsParallel', 'EChartsRadar', 'EChartsSankey', 'EChartsCalendar'
] as $deviceModuleName) {
    $device = $moduleContracts[$deviceModuleName];
    requireContract(
        ($gateway['implemented'] ?? null) === ($device['parentRequirements'] ?? null),
        $deviceModuleName . '-to-gateway data flow is inconsistent.',
        $errors
    );
    requireContract(
        ($gateway['childRequirements'] ?? null) === ($device['implemented'] ?? null),
        'Gateway-to-' . $deviceModuleName . ' data flow is inconsistent.',
        $errors
    );
}

if ($errors !== []) {
    fwrite(STDERR, "SymconECharts module contract validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo "SymconECharts module contracts verified.\n";
