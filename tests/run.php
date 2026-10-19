<?php

declare(strict_types=1);

$root = dirname(__DIR__);
if (!chdir($root)) {
    fwrite(STDERR, "Unable to switch to the repository root.\n");
    exit(1);
}

$tests = [
    __DIR__ . '/validate_structure.php',
    __DIR__ . '/module_contracts.php',
    __DIR__ . '/symcon_strict.php',
    __DIR__ . '/data_protocol.php',
    __DIR__ . '/archive_windows.php',
    __DIR__ . '/archive_states_gateway.php',
    __DIR__ . '/calendar_pie_data.php',
    __DIR__ . '/calendar_pie_integration.php',
    __DIR__ . '/state_timeline_data.php',
    __DIR__ . '/state_timeline_integration.php',
    __DIR__ . '/source_identity.php',
    __DIR__ . '/echarts_assets.php',
    __DIR__ . '/svg_path.php',
    __DIR__ . '/svg_image.php',
    __DIR__ . '/pictorial_bar_icons.php',
    __DIR__ . '/pictorial_bar_svg.php',
    __DIR__ . '/gauge_design.php',
    __DIR__ . '/time_series_design.php',
    __DIR__ . '/label_design.php',
    __DIR__ . '/scatter_pairing.php',
    __DIR__ . '/parallel_pairing.php',
    __DIR__ . '/theme_river_data.php',
    __DIR__ . '/heatmap_aggregation.php',
    __DIR__ . '/heatmap_window.php',
    __DIR__ . '/tree_hierarchy.php',
    __DIR__ . '/tree_health.php',
    __DIR__ . '/graph_model.php',
    __DIR__ . '/zigbee_graph_model.php',
    __DIR__ . '/treemap_model.php',
    __DIR__ . '/sankey_model.php',
    __DIR__ . '/radar_model.php',
    __DIR__ . '/boxplot_statistics.php',
    __DIR__ . '/candlestick_data.php',
    __DIR__ . '/calendar_data.php',
    __DIR__ . '/zigbee_graph_export_reader.php',
    __DIR__ . '/gateway_gauges.php'
];

$commands = [
    ['Test ThemeRiver Gateway and output integration', 'php tests/gateway_gauges.php --theme-river'],
    ['Verify local quality runner', 'php tests/quality.php --help'],
    ['Test persistent IPSView transport', 'node tests/ipsview_transport.js'],
    ['Test shared ECharts zoom', 'node tests/echarts_zoom.js'],
    ['Test shared ECharts label design', 'node tests/label_design.js'],
    ['Test Time Series tile design layout', 'node tests/time_series_layout.js'],
    ['Test Category Bar layout', 'node tests/bar_category_layout.js'],
    ['Test Historical Bar layout', 'node tests/bar_history_layout.js'],
    ['Test Scatter/Bubble layout', 'node tests/scatter_layout.js'],
    ['Test Heatmap layout', 'node tests/heatmap_layout.js'],
    ['Test Tree layout', 'node tests/tree_layout.js'],
    ['Test Graph layout', 'node tests/graph_layout.js'],
    ['Test Zigbee Graph layout', 'node tests/zigbee_graph_layout.js'],
    ['Test Treemap layout', 'node tests/treemap_layout.js'],
    ['Test Sunburst layout', 'node tests/sunburst_layout.js'],
    ['Test Parallel layout', 'node tests/parallel_layout.js'],
    ['Test ThemeRiver layout', 'node tests/theme_river_layout.js'],
    ['Test StateTimeline layout', 'node tests/state_timeline_layout.js'],
    ['Test CalendarPie layout and navigation', 'node tests/calendar_pie_layout.js'],
    ['Test Boxplot layout', 'node tests/boxplot_layout.js'],
    ['Test Candlestick layout', 'node tests/candlestick_layout.js'],
    ['Test Funnel layout', 'node tests/funnel_layout.js'],
    ['Test PictorialBar layout', 'node tests/pictorial_bar_layout.js'],
    ['Test Radar layout', 'node tests/radar_layout.js'],
    ['Test Sankey layout', 'node tests/sankey_layout.js'],
    ['Test Calendar layout and navigation', 'node tests/calendar_layout.js'],
    ['Verify vendored helper integrity', 'python3 tests/helper_integrity.py'],
    ['Test library metadata updater', 'python3 tests/test_update_library_metadata.py']
];

foreach ($tests as $test) {
    echo 'Running ' . basename($test) . "...\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($test), $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

foreach ($commands as [$label, $command]) {
    echo $label . "...\n";
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

echo "All SymconECharts tests passed.\n";
