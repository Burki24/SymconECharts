<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$requiredFiles = [
    'AGENTS.md',
    'README.md',
    'README1.md',
    'THIRD_PARTY_NOTICES.md',
    'docs/ENTWICKLUNG.md',
    'docs/adr/0001-chart-family-modules.md',
    'library.json',
    '.gitmodules',
    '.github/scripts/update_library_metadata.py',
    '.github/workflows/style.yml',
    '.github/workflows/tests.yml',
    '.github/workflows/update-library-metadata.yml',
    'tests/README.md',
    'tests/module_contracts.php',
    'tests/run.php',
    'tests/test_update_library_metadata.py',
    'tests/validate_structure.php'
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

$expectedModules = ['EChartsGateway', 'EChartsGauge'];
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
