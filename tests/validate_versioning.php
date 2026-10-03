<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$requiredFiles = [
    '.github/scripts/update_library_metadata.py',
    '.github/workflows/update-library-metadata.yml',
    'library.json',
    'tests/run.php',
    'tests/test_update_library_metadata.py'
];

foreach ($requiredFiles as $requiredFile) {
    if (!is_file($root . '/' . $requiredFile)) {
        $errors[] = 'Missing versioning file: ' . $requiredFile;
    }
}

try {
    $library = json_decode(
        (string) file_get_contents($root . '/library.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException $exception) {
    $library = [];
    $errors[] = 'Invalid library.json: ' . $exception->getMessage();
}

if (!is_array($library)) {
    $library = [];
    $errors[] = 'library.json must contain a JSON object.';
}

$version = $library['version'] ?? null;
if (!is_string($version) || preg_match('/^\d+\.\d+$/', $version) !== 1) {
    $errors[] = 'library.json version must use the major.minor format.';
}

if (!is_int($library['build'] ?? null)) {
    $errors[] = 'library.json build must be an integer.';
}

if (!is_int($library['date'] ?? null)) {
    $errors[] = 'library.json date must be an integer.';
}

$workflowPath = $root . '/.github/workflows/update-library-metadata.yml';
if (is_file($workflowPath)) {
    $workflow = (string) file_get_contents($workflowPath);
    $requiredWorkflowContent = [
        "branches:\n      - dev",
        'actions/create-github-app-token@v3',
        'vars.HELPER_SYNC_APP_CLIENT_ID',
        'secrets.HELPER_SYNC_APP_PRIVATE_KEY',
        'actions/checkout@v6',
        'Burki24/Symcon_ModuleCI/php-tests@v1.0.0',
        'python3 .github/scripts/update_library_metadata.py',
        "'CHORE: Update library metadata'"
    ];

    foreach ($requiredWorkflowContent as $requiredContent) {
        if (!str_contains($workflow, $requiredContent)) {
            $errors[] = 'Metadata workflow is missing required content: ' . $requiredContent;
        }
    }
}

$runner = (string) file_get_contents($root . '/tests/run.php');
if (!str_contains($runner, 'python3 tests/test_update_library_metadata.py')) {
    $errors[] = 'tests/run.php must execute the metadata updater regression test.';
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Library metadata automation structure verified.\n";
