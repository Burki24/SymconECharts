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
    __DIR__ . '/echarts_assets.php',
    __DIR__ . '/svg_path.php',
    __DIR__ . '/svg_image.php',
    __DIR__ . '/gauge_design.php',
    __DIR__ . '/gateway_gauges.php'
];

$commands = [
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
