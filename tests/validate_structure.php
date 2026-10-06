<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$requiredFiles = [
    'AGENTS.md',
    '.gitattributes',
    '.gitignore',
    'README.md',
    'README1.md',
    'THIRD_PARTY_NOTICES.md',
    'docs/ENTWICKLUNG.md',
    'docs/adr/0001-chart-family-modules.md',
    'docs/adr/0002-gateway-gauge-contract.md',
    'docs/adr/0003-single-and-multi-gauge-modules.md',
    'docs/adr/0004-gauge-multi-source-contract.md',
    'docs/adr/0005-echarts-runtime-and-native-renderer.md',
    'docs/adr/0006-gauge-single-tile-designer.md',
    'docs/adr/0007-echarts-theme-assets-and-selection.md',
    'docs/adr/0016-dedicated-tacho-and-chronograph-modules.md',
    'docs/adr/0019-timeseries-realtime-without-archive.md',
    'library.json',
    'libs/EChartsAsset.php',
    'libs/EChartsDataProtocol.php',
    'libs/EChartsGaugeDesign.php',
    'libs/EChartsVariablePresentation.php',
    'libs/EChartsSvgImage.php',
    'libs/EChartsSvgPath.php',
    'libs/echarts/6.1.0/echarts.gauge.min.js',
    'libs/echarts/6.1.0/echarts.timeseries.min.js',
    'libs/echarts/6.1.0/themes/dark.js',
    'libs/echarts/6.1.0/themes/vintage.js',
    'libs/echarts/6.1.0/themes/macarons.js',
    'libs/echarts/6.1.0/themes/infographic.js',
    'libs/echarts/6.1.0/themes/shine.js',
    'libs/echarts/6.1.0/themes/roma.js',
    'libs/echarts/6.1.0/LICENSE.txt',
    'libs/echarts/6.1.0/NOTICE.txt',
    'libs/helper/ConfigurationFormHelper.php',
    'libs/helper/DataFlowHelper.php',
    'libs/helper/HelperTranslationHelper.php',
    'libs/helper/IPSViewHTMLPageHelper.php',
    'libs/helper/ResponsiveVisualizationHelper.php',
    'libs/helper/SVGPreviewHelper.php',
    'libs/helper/VisualizationAssetHelper.php',
    'libs/helper/VisualizationThemeHelper.php',
    'libs/helper/translations/IPSViewHTMLPageHelper.json',
    'libs/helper/README.md',
    'libs/helper/manifest.json',
    '.helper-sync.json',
    '.gitmodules',
    '.github/scripts/update_library_metadata.py',
    '.github/workflows/style.yml',
    '.github/workflows/tests.yml',
    '.github/workflows/update-library-metadata.yml',
    'tests/README.md',
    'tests/data_protocol.php',
    'tests/echarts_assets.php',
    'tests/gauge_design.php',
    'tests/gauge_multi_layout.js',
    'tests/gauge_tacho_layout.js',
    'tests/gauge_chronograph_layout.js',
    'tests/fixtures/gauge-pointer-ornate.svg',
    'tests/gateway_gauges.php',
    'tests/helper_integrity.py',
    'tests/module_contracts.php',
    'tests/svg_path.php',
    'tests/run.php',
    'tests/symcon_strict.php',
    'tests/test_update_library_metadata.py',
    'tests/validate_structure.php',
    '.tools/echarts-runtime/package.json',
    '.tools/echarts-runtime/package-lock.json',
    '.tools/echarts-runtime/README.md',
    '.tools/echarts-runtime/scripts/copy-themes.mjs',
    '.tools/echarts-runtime/src/gauge-runtime.js',
    '.tools/echarts-runtime/src/timeseries-runtime.js',
    'EChartsGaugeSingle/visualization/index.html',
    'EChartsGaugeSingle/visualization/style.css',
    'EChartsGaugeSingle/visualization/app.js',
    'EChartsGaugeSingle/GaugePreview.php'
];

foreach ($requiredFiles as $requiredFile) {
    if (!is_file($root . '/' . $requiredFile)) {
        $errors[] = 'Missing required repository file: ' . $requiredFile;
    }
}

foreach (['.shared', '.tests'] as $forbiddenDirectory) {
    if (is_dir($root . '/' . $forbiddenDirectory)) {
        $errors[] = 'Forbidden repository directory: ' . $forbiddenDirectory;
    }
}

$symconLibraryDirectories = ['actions', 'docs', 'imgs', 'libs', 'tests'];
foreach (new DirectoryIterator($root) as $entry) {
    if ($entry->isDot() || !$entry->isDir()) {
        continue;
    }

    $directoryName = $entry->getFilename();
    if (str_starts_with($directoryName, '.')
        || in_array($directoryName, $symconLibraryDirectories, true)
        || is_file($entry->getPathname() . '/module.json')
    ) {
        continue;
    }

    $containsFiles = false;
    $contents = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($entry->getPathname(), FilesystemIterator::SKIP_DOTS)
    );
    foreach ($contents as $content) {
        if ($content->isFile()) {
            $containsFiles = true;
            break;
        }
    }

    // Git does not distribute empty directories, so local empty placeholders
    // cannot invalidate the installed library package.
    if (!$containsFiles) {
        continue;
    }

    $errors[] = 'Unsupported top-level directory for an IP-Symcon library: '
        . $directoryName
        . ' (non-dot directories must be a supported library directory or contain module.json).';
}

$gitAttributesPath = $root . '/.gitattributes';
if (is_file($gitAttributesPath)) {
    $gitAttributes = (string) file_get_contents($gitAttributesPath);
    if (!str_contains($gitAttributes, '/libs/echarts/** -text')) {
        $errors[] = '.gitattributes must disable line-ending conversion for vendored ECharts artifacts.';
    }
}

if (is_file($root . '/libs/helper/EChartsDataProtocol.php')) {
    $errors[] = 'Project-specific EChartsDataProtocol.php must not be stored with synchronized ModuleHelpers.';
}

if (is_file($root . '/libs/echarts/6.1.0/echarts.min.js')) {
    $errors[] = 'The full ECharts build exceeds the Symcon tile output-buffer budget and must not be distributed.';
}

$gitmodulesPath = $root . '/.gitmodules';
if (is_file($gitmodulesPath)) {
    $gitmodules = (string) file_get_contents($gitmodulesPath);
    foreach ([
        '[submodule ".style"]',
        'path = .style',
        'url = https://github.com/symcon/StylePHP'
    ] as $requiredSubmoduleContent) {
        if (!str_contains($gitmodules, $requiredSubmoduleContent)) {
            $errors[] = '.gitmodules is missing required content: ' . $requiredSubmoduleContent;
        }
    }
}

foreach (['.style/.php-cs-fixer.php', '.style/json-check.php'] as $requiredStyleFile) {
    if (!is_file($root . '/' . $requiredStyleFile)) {
        $errors[] = 'StylePHP submodule is not initialized: ' . $requiredStyleFile;
    }
}

/**
 * Reads a JSON object and records a validation error on failure.
 *
 * @param list<string> $errors
 *
 * @return array<string, mixed>|null
 */
function readJsonObject(string $path, array &$errors): ?array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = 'Cannot read ' . $path;

        return null;
    }

    try {
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $errors[] = $path . ': ' . $exception->getMessage();

        return null;
    }

    if (!is_array($data)) {
        $errors[] = $path . ' must contain a JSON object.';

        return null;
    }

    return $data;
}

/**
 * Checks an IP-Symcon GUID.
 */
function isSymconGuid(mixed $value): bool
{
    return is_string($value)
        && preg_match('/^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$/i', $value) === 1;
}

$library = readJsonObject($root . '/library.json', $errors);
if ($library !== null) {
    foreach (['id', 'name', 'author', 'url', 'compatibility', 'version', 'build', 'date'] as $requiredField) {
        if (!array_key_exists($requiredField, $library)) {
            $errors[] = 'library.json is missing required field: ' . $requiredField;
        }
    }

    if (!isSymconGuid($library['id'] ?? null)) {
        $errors[] = 'library.json contains an invalid id.';
    }

    $compatibility = $library['compatibility'] ?? null;
    if (!is_array($compatibility)
        || !is_string($compatibility['version'] ?? null)
        || preg_match('/^\d+\.\d+$/', $compatibility['version']) !== 1
    ) {
        $errors[] = 'library.json contains invalid compatibility metadata.';
    }

    $version = $library['version'] ?? null;
    if (!is_string($version) || preg_match('/^\d+\.\d+$/', $version) !== 1) {
        $errors[] = 'library.json version must use the major.minor format.';
    }

    if (!is_int($library['build'] ?? null) || $library['build'] < 0) {
        $errors[] = 'library.json build must be a non-negative integer.';
    }

    if (!is_int($library['date'] ?? null) || $library['date'] < 0) {
        $errors[] = 'library.json date must be a non-negative integer.';
    }
}

$helperSync = is_file($root . '/.helper-sync.json')
    ? readJsonObject($root . '/.helper-sync.json', $errors)
    : null;
if ($helperSync !== null) {
    if (($helperSync['schema'] ?? null) !== 1) {
        $errors[] = '.helper-sync.json must use schema 1.';
    }
    if (($helperSync['source_repository'] ?? null) !== 'Burki24/Symcon_ModuleHelper') {
        $errors[] = '.helper-sync.json contains an unexpected source repository.';
    }
    if (($helperSync['base_branch'] ?? null) !== 'dev') {
        $errors[] = '.helper-sync.json must target the dev branch.';
    }
    if (($helperSync['readme_language'] ?? null) !== 'de') {
        $errors[] = '.helper-sync.json must generate German helper documentation.';
    }
    if (($helperSync['helpers'] ?? null) !== [
        'ConfigurationFormHelper'        => ['target' => 'libs/helper/ConfigurationFormHelper.php'],
        'DataFlowHelper'                 => ['target' => 'libs/helper/DataFlowHelper.php'],
        'IPSViewHTMLPageHelper'          => ['target' => 'libs/helper/IPSViewHTMLPageHelper.php'],
        'ResponsiveVisualizationHelper'  => ['target' => 'libs/helper/ResponsiveVisualizationHelper.php'],
        'SVGPreviewHelper'               => ['target' => 'libs/helper/SVGPreviewHelper.php'],
        'VisualizationAssetHelper'       => ['target' => 'libs/helper/VisualizationAssetHelper.php'],
        'VisualizationThemeHelper'       => ['target' => 'libs/helper/VisualizationThemeHelper.php']
    ]) {
        $errors[] = '.helper-sync.json does not contain the required visualization helper subscriptions.';
    }
}

$expectedModules = [
    'EChartsGateway', 'EChartsGaugeChronograph', 'EChartsGaugeMulti', 'EChartsGaugeSingle', 'EChartsGaugeTacho',
    'EChartsTimeSeries'
];
$discoveredModules = [];
foreach (glob($root . '/*/module.json') ?: [] as $modulePath) {
    $discoveredModules[] = basename(dirname($modulePath));
}
sort($discoveredModules);

if ($discoveredModules !== $expectedModules) {
    $errors[] = 'Unexpected module inventory: ' . implode(', ', $discoveredModules);
}

foreach ($expectedModules as $moduleDirectory) {
    foreach (['README.md', 'form.json', 'locale.json', 'module.json', 'module.php'] as $moduleFile) {
        if (!is_file($root . '/' . $moduleDirectory . '/' . $moduleFile)) {
            $errors[] = $moduleDirectory . ' is missing ' . $moduleFile;
        }
    }

    $form = readJsonObject($root . '/' . $moduleDirectory . '/form.json', $errors);
    if ($form !== null) {
        foreach (['elements', 'actions', 'status'] as $formSection) {
            if (!is_array($form[$formSection] ?? null)) {
                $errors[] = $moduleDirectory . '/form.json field ' . $formSection . ' must be an array.';
            }
        }
    }

    $locale = readJsonObject($root . '/' . $moduleDirectory . '/locale.json', $errors);
    if ($locale !== null) {
        $translations = $locale['translations'] ?? null;
        if (!is_array($translations) || !is_array($translations['de'] ?? null)) {
            $errors[] = $moduleDirectory . '/locale.json must contain German translations.';
        }
    }
}

$readme = file_get_contents($root . '/README.md');
if ($readme === false) {
    $errors[] = 'Cannot read README.md.';
} else {
    foreach ($expectedModules as $moduleDirectory) {
        if (!str_contains($readme, '](' . $moduleDirectory . ')')) {
            $errors[] = 'README.md does not link module directory: ' . $moduleDirectory;
        }
    }
}

$testsWorkflowPath = $root . '/.github/workflows/tests.yml';
if (is_file($testsWorkflowPath)) {
    $testsWorkflow = (string) file_get_contents($testsWorkflowPath);
    foreach ([
        'jobs:',
        '  tests:',
        'name: tests',
        'uses: actions/checkout@v6',
        'uses: Burki24/Symcon_ModuleCI/php-tests@v1.0.0'
    ] as $requiredWorkflowContent) {
        if (!str_contains($testsWorkflow, $requiredWorkflowContent)) {
            $errors[] = 'Tests workflow is missing required content: ' . $requiredWorkflowContent;
        }
    }
}

$styleWorkflowPath = $root . '/.github/workflows/style.yml';
if (is_file($styleWorkflowPath)) {
    $styleWorkflow = (string) file_get_contents($styleWorkflowPath);
    foreach ([
        'jobs:',
        '  style:',
        'name: style',
        'uses: actions/checkout@v6',
        'uses: Burki24/Symcon_ModuleCI/style@v1.0.0'
    ] as $requiredWorkflowContent) {
        if (!str_contains($styleWorkflow, $requiredWorkflowContent)) {
            $errors[] = 'Style workflow is missing required content: ' . $requiredWorkflowContent;
        }
    }
}

$metadataWorkflowPath = $root . '/.github/workflows/update-library-metadata.yml';
if (is_file($metadataWorkflowPath)) {
    $metadataWorkflow = (string) file_get_contents($metadataWorkflowPath);
    foreach ([
        "branches:\n      - dev",
        'actions/create-github-app-token@v3',
        'vars.HELPER_SYNC_APP_CLIENT_ID',
        'secrets.HELPER_SYNC_APP_PRIVATE_KEY',
        'actions/checkout@v6',
        'Burki24/Symcon_ModuleCI/php-tests@v1.0.0',
        'python3 .github/scripts/update_library_metadata.py',
        "'CHORE: Update library metadata'"
    ] as $requiredWorkflowContent) {
        if (!str_contains($metadataWorkflow, $requiredWorkflowContent)) {
            $errors[] = 'Metadata workflow is missing required content: ' . $requiredWorkflowContent;
        }
    }
}

$runner = file_get_contents($root . '/tests/run.php');
if ($runner === false) {
    $errors[] = 'Cannot read tests/run.php.';
} else {
    foreach ([
        "__DIR__ . '/validate_structure.php'",
        "__DIR__ . '/module_contracts.php'",
        "__DIR__ . '/symcon_strict.php'",
        "__DIR__ . '/data_protocol.php'",
        "__DIR__ . '/echarts_assets.php'",
        "__DIR__ . '/svg_path.php'",
        "__DIR__ . '/gateway_gauges.php'",
        'python3 tests/helper_integrity.py',
        'python3 tests/test_update_library_metadata.py'
    ] as $requiredTest) {
        if (!str_contains($runner, $requiredTest)) {
            $errors[] = 'tests/run.php does not execute: ' . $requiredTest;
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "SymconECharts structure validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo 'SymconECharts repository structure is valid (' . count($discoveredModules) . " modules).\n";
