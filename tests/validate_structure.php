<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$requiredFiles = [
    'AGENTS.md',
    '.gitattributes',
    '.gitignore',
    'CHANGELOG.md',
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
    'docs/adr/0030-first-historical-bar-vertical.md',
    'docs/adr/0019-timeseries-realtime-without-archive.md',
    'docs/adr/0023-timeseries-source-design-and-svg-area-patterns.md',
    'docs/adr/0027-timeseries-custom-ranges-and-time-axis-labels.md',
    'docs/adr/0029-bar-chart-families-and-category-bar.md',
    'docs/adr/0035-polar-bar-current-values.md',
    'library.json',
    'libs/EChartsAsset.php',
    'libs/EChartsDataProtocol.php',
    'libs/EChartsGaugeDesign.php',
    'libs/EChartsTimeSeriesDesign.php',
    'libs/EChartsArchiveQuery.php',
    'libs/EChartsVariablePresentation.php',
    'libs/echarts-design.js',
    'libs/echarts-pattern.js',
    'libs/EChartsSvgImage.php',
    'libs/EChartsSvgPath.php',
    'libs/echarts/6.1.0/echarts.gauge.min.js',
    'libs/echarts/6.1.0/echarts.cartesian.min.js',
    'libs/echarts/6.1.0/echarts.polar.min.js',
    'libs/echarts/6.1.0/echarts.pie.min.js',
    'libs/echarts/6.1.0/echarts.tree.min.js',
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
    'libs/helper/VariableSearchHelper.php',
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
    'tests/time_series_design.php',
    'tests/gauge_multi_layout.js',
    'tests/gauge_tacho_layout.js',
    'tests/gauge_chronograph_layout.js',
    'tests/fixtures/gauge-pointer-ornate.svg',
    'tests/gateway_gauges.php',
    'tests/helper_integrity.py',
    'tests/module_contracts.php',
    'tests/quality.php',
    'tests/zigbee_graph_export_reader.php',
    'tests/svg_path.php',
    'tests/run.php',
    'tests/symcon_strict.php',
    'tests/test_update_library_metadata.py',
    'tests/time_series_layout.js',
    'tests/bar_category_layout.js',
    'tests/bar_history_layout.js',
    'tests/validate_structure.php',
    '.tools/echarts-runtime/package.json',
    '.tools/echarts-runtime/package-lock.json',
    '.tools/echarts-runtime/README.md',
    '.tools/echarts-runtime/scripts/copy-themes.mjs',
    '.tools/echarts-runtime/src/gauge-runtime.js',
    '.tools/echarts-runtime/src/cartesian-runtime.js',
    '.tools/echarts-runtime/src/polar-runtime.js',
    'EChartsGaugeSingle/visualization/index.html',
    'EChartsGaugeSingle/visualization/style.css',
    'EChartsGaugeSingle/visualization/app.js',
    'EChartsGaugeSingle/GaugePreview.php',
    'EChartsTimeSeries/TimeSeriesPreview.php',
    'EChartsBarCategory/visualization/index.html',
    'EChartsBarCategory/visualization/style.css',
    'EChartsBarCategory/visualization/app.js',
    'EChartsBarHistory/visualization/index.html',
    'EChartsBarHistory/visualization/style.css',
    'EChartsBarHistory/visualization/app.js',
    'EChartsBarWaterfall/visualization/index.html',
    'EChartsBarWaterfall/visualization/style.css',
    'EChartsBarWaterfall/visualization/app.js',
    'tests/bar_waterfall_layout.js',
    'EChartsBarPolar/visualization/index.html',
    'EChartsBarPolar/visualization/style.css',
    'EChartsBarPolar/visualization/app.js',
    'EChartsPie/visualization/index.html',
    'EChartsPie/visualization/style.css',
    'EChartsPie/visualization/app.js',
    'EChartsScatter/ScatterPairing.php',
    'EChartsScatter/visualization/index.html',
    'EChartsScatter/visualization/style.css',
    'EChartsScatter/visualization/app.js',
    'EChartsHeatmap/HeatmapAggregation.php',
    'EChartsHeatmap/visualization/index.html',
    'EChartsHeatmap/visualization/style.css',
    'EChartsHeatmap/visualization/app.js',
    'libs/TreeHierarchy.php',
    'EChartsTree/visualization/index.html',
    'EChartsTree/visualization/style.css',
    'EChartsTree/visualization/app.js',
    'EChartsTreeSystemHealth/TreeHealth.php',
    'EChartsTreeSystemHealth/visualization/index.html',
    'EChartsTreeSystemHealth/visualization/style.css',
    'EChartsTreeSystemHealth/visualization/app.js',
    'libs/GraphModel.php',
    'EChartsGraph/visualization/index.html',
    'EChartsGraph/visualization/style.css',
    'EChartsGraph/visualization/app.js',
    '.tools/echarts-runtime/src/graph-runtime.js',
    'libs/echarts/6.1.0/echarts.graph.min.js',
    'docs/adr/0047-general-and-zigbee-graph-split.md',
    'tests/graph_model.php',
    'tests/graph_layout.js',
    'libs/ZigbeeGraphModel.php',
    'EChartsGraphZ2M/ZigbeeGraphExportReader.php',
    'EChartsGraphZ2M/visualization/index.html',
    'EChartsGraphZ2M/visualization/style.css',
    'EChartsGraphZ2M/visualization/app.js',
    'tests/zigbee_graph_model.php',
    'tests/zigbee_graph_layout.js',
    'libs/TreemapModel.php',
    'EChartsTreemap/visualization/index.html',
    'EChartsTreemap/visualization/style.css',
    'EChartsTreemap/visualization/app.js',
    '.tools/echarts-runtime/src/treemap-runtime.js',
    'libs/echarts/6.1.0/echarts.treemap.min.js',
    'docs/adr/0048-manual-current-value-treemap.md',
    'tests/treemap_model.php',
    'tests/treemap_layout.js',
    'EChartsSunburst/visualization/index.html',
    'EChartsSunburst/visualization/style.css',
    'EChartsSunburst/visualization/app.js',
    '.tools/echarts-runtime/src/sunburst-runtime.js',
    'libs/echarts/6.1.0/echarts.sunburst.min.js',
    'docs/adr/0049-manual-current-value-sunburst.md',
    'tests/sunburst_layout.js',
    'EChartsParallel/ParallelPairing.php',
    'EChartsParallel/visualization/index.html',
    'EChartsParallel/visualization/style.css',
    'EChartsParallel/visualization/app.js',
    '.tools/echarts-runtime/src/parallel-runtime.js',
    'libs/echarts/6.1.0/echarts.parallel.min.js',
    'docs/adr/0050-archive-parallel-chart.md',
    'tests/parallel_pairing.php',
    'tests/parallel_layout.js',
    'EChartsSankey/SankeyModel.php',
    'EChartsSankey/SankeyPreview.php',
    'EChartsSankey/visualization/index.html',
    'EChartsSankey/visualization/style.css',
    'EChartsSankey/visualization/app.js',
    '.tools/echarts-runtime/src/sankey-runtime.js',
    'libs/echarts/6.1.0/echarts.sankey.min.js',
    'docs/adr/0051-manual-current-value-sankey.md',
    'tests/sankey_model.php',
    'tests/sankey_layout.js',
    'EChartsRadar/RadarModel.php',
    'EChartsRadar/RadarPreview.php',
    'libs/EChartsPreviewSymbol.php',
    'EChartsRadar/visualization/index.html',
    'EChartsRadar/visualization/style.css',
    'EChartsRadar/visualization/app.js',
    'libs/echarts/6.1.0/echarts.radar.min.js',
    '.tools/echarts-runtime/src/radar-runtime.js',
    'docs/adr/0053-current-value-radar.md',
    'tests/radar_model.php',
    'tests/radar_integration.php',
    'tests/radar_layout.js',
    'EChartsFunnel/FunnelPreview.php',
    'EChartsFunnel/visualization/index.html',
    'EChartsFunnel/visualization/style.css',
    'EChartsFunnel/visualization/app.js',
    'libs/echarts/6.1.0/echarts.funnel.min.js',
    '.tools/echarts-runtime/src/funnel-runtime.js',
    'docs/adr/0055-current-value-funnel.md',
    'tests/funnel_integration.php',
    'tests/funnel_layout.js',
    'EChartsBoxplot/BoxplotStatistics.php',
    'EChartsBoxplot/BoxplotPreview.php',
    'EChartsBoxplot/visualization/index.html',
    'EChartsBoxplot/visualization/style.css',
    'EChartsBoxplot/visualization/app.js',
    'libs/echarts/6.1.0/echarts.boxplot.min.js',
    '.tools/echarts-runtime/src/boxplot-runtime.js',
    'docs/adr/0054-hourly-archive-boxplot.md',
    'tests/boxplot_statistics.php',
    'tests/boxplot_integration.php',
    'tests/boxplot_layout.js',
    'EChartsCalendar/CalendarData.php',
    'EChartsCalendar/CalendarPreview.php',
    'EChartsCalendar/visualization/index.html',
    'EChartsCalendar/visualization/style.css',
    'EChartsCalendar/visualization/app.js',
    '.tools/echarts-runtime/src/calendar-runtime.js',
    'libs/echarts/6.1.0/echarts.calendar.min.js',
    'docs/adr/0052-calendar-daily-archive-means.md',
    'tests/calendar_data.php',
    'tests/calendar_layout.js',
    'tests/calendar_integration.php',
    '.tools/echarts-runtime/src/tree-runtime.js',
    'docs/adr/0044-tree-symcon-object-hierarchy.md',
    'docs/adr/0046-tree-system-health-module-split.md',
    'tests/tree_hierarchy.php',
    'tests/tree_layout.js',
    'libs/echarts/6.1.0/echarts.heatmap.min.js',
    '.tools/echarts-runtime/src/heatmap-runtime.js',
    'docs/adr/0041-heatmap-weekday-hour-family.md',
    'tests/heatmap_aggregation.php',
    'tests/heatmap_layout.js',
    'tests/bar_polar_layout.js',
    'tests/pie_layout.js',
    'tests/scatter_pairing.php',
    'tests/scatter_layout.js',
    'libs/EChartsCurrentSources.php'
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
        'VariableSearchHelper'           => ['target' => 'libs/helper/VariableSearchHelper.php'],
        'VisualizationAssetHelper'       => ['target' => 'libs/helper/VisualizationAssetHelper.php'],
        'VisualizationThemeHelper'       => ['target' => 'libs/helper/VisualizationThemeHelper.php']
    ]) {
        $errors[] = '.helper-sync.json does not contain the required visualization helper subscriptions.';
    }
}

$expectedModules = [
    'EChartsBarCategory', 'EChartsBarHistory', 'EChartsBarPolar', 'EChartsBarWaterfall', 'EChartsBoxplot', 'EChartsCalendar', 'EChartsFunnel', 'EChartsGateway', 'EChartsGaugeChronograph', 'EChartsGaugeMulti',
    'EChartsGaugeSingle', 'EChartsGaugeTacho', 'EChartsGraph', 'EChartsGraphZ2M', 'EChartsHeatmap', 'EChartsParallel', 'EChartsPie', 'EChartsRadar', 'EChartsSankey', 'EChartsScatter', 'EChartsSunburst', 'EChartsTimeSeries', 'EChartsTree', 'EChartsTreeSystemHealth', 'EChartsTreemap'
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
        if (!str_contains($readme, '](' . $moduleDirectory . '/README.md)')) {
            $errors[] = 'README.md does not link module directory: ' . $moduleDirectory;
        }
    }

    if (!str_contains($readme, '[CHANGELOG.md](CHANGELOG.md)')) {
        $errors[] = 'README.md does not link CHANGELOG.md.';
    }
}

$userReadmePaths = [$root . '/README.md'];
foreach ($expectedModules as $moduleDirectory) {
    $userReadmePaths[] = $root . '/' . $moduleDirectory . '/README.md';
}

foreach ($userReadmePaths as $userReadmePath) {
    $userReadme = (string) file_get_contents($userReadmePath);
    if (preg_match('/^#{1,6}\s+(?:Entwicklung|Entwicklungsstand|Tests?|CI|Debugging|Architektur)\b/mi', $userReadme) === 1) {
        $errors[] = str_replace($root . '/', '', $userReadmePath)
            . ' contains an internal-development heading.';
    }
}

$changelogPath = $root . '/CHANGELOG.md';
if (is_file($changelogPath)) {
    $changelog = (string) file_get_contents($changelogPath);
    if (!str_starts_with($changelog, '# Unveröffentlicht')) {
        $errors[] = 'CHANGELOG.md must start with # Unveröffentlicht during development.';
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
        "__DIR__ . '/time_series_design.php'",
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
