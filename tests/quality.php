<?php

declare(strict_types=1);

const QUALITY_USAGE = <<<'TEXT'
Usage:
  php tests/quality.php          Check formatting and run all local quality checks.
  php tests/quality.php --fix    Fix PHP/JSON formatting, verify it, then run all checks.
  php tests/quality.php --help   Show this help.

Set PHP_CS_FIXER_BINARY when php-cs-fixer is not available on PATH.
TEXT;

$root = dirname(__DIR__);
if (!chdir($root)) {
    fwrite(STDERR, "Unable to switch to the repository root.\n");
    exit(1);
}

$arguments = array_slice($argv, 1);
if ($arguments === ['--help']) {
    echo QUALITY_USAGE . "\n";
    exit(0);
}
if ($arguments !== [] && $arguments !== ['--fix']) {
    fwrite(STDERR, QUALITY_USAGE . "\n");
    exit(2);
}
$fix = $arguments === ['--fix'];

if ($fix && !validateJsonSyntax($root)) {
    fwrite(STDERR, "JSON formatting was not changed because invalid JSON was found.\n");
    exit(1);
}

$fixer = resolvePhpCsFixer();
$fixerCommand = str_ends_with(strtolower($fixer), '.phar')
    ? [PHP_BINARY, $fixer]
    : [$fixer];
$commonFixerArguments = [
    '--config=.style/.php-cs-fixer.php',
    '--allow-risky=yes',
    '--using-cache=no'
];

if ($fix) {
    runQualityCommand(
        'Fix PHP formatting',
        [...$fixerCommand, 'fix', ...$commonFixerArguments]
    );
    runQualityCommand(
        'Fix JSON formatting',
        [PHP_BINARY, '.style/json-check.php', 'fix', '.'],
        [0, 1]
    );
}

runQualityCommand(
    'Check PHP formatting',
    [...$fixerCommand, 'fix', ...$commonFixerArguments, '--dry-run', '--diff']
);
runQualityCommand('Check JSON formatting', [PHP_BINARY, '.style/json-check.php']);
runQualityCommand('Check whitespace errors', ['git', 'diff', '--check']);

echo "Check PHP syntax...\n";
$phpFiles = repositoryFiles($root, '.php');
foreach ($phpFiles as $phpFile) {
    runQualityCommand('', [PHP_BINARY, '-l', $phpFile], [0], false);
}
echo 'PHP syntax verified (' . count($phpFiles) . " files).\n";

runQualityCommand('Run repository test suite', [PHP_BINARY, 'tests/run.php']);
foreach ([
    'tests/gauge_single_layout.js',
    'tests/gauge_multi_layout.js',
    'tests/gauge_tacho_layout.js',
    'tests/gauge_chronograph_layout.js'
] as $layoutTest) {
    runQualityCommand('Run ' . basename($layoutTest), ['node', $layoutTest]);
}

echo $fix
    ? "Formatting corrected and all local quality checks passed.\n"
    : "All local quality checks passed.\n";

/** @param list<string> $command @param list<int> $acceptedExitCodes */
function runQualityCommand(
    string $label,
    array $command,
    array $acceptedExitCodes = [0],
    bool $showCommand = true
): void {
    if ($label !== '') {
        echo $label . "...\n";
    }
    if ($showCommand) {
        echo '  ' . implode(' ', array_map('escapeQualityArgument', $command)) . "\n";
    }
    passthru(implode(' ', array_map('escapeQualityArgument', $command)), $exitCode);
    if (!in_array($exitCode, $acceptedExitCodes, true)) {
        fwrite(STDERR, ($label !== '' ? $label : 'Command') . ' failed with exit code ' . $exitCode . ".\n");
        exit($exitCode > 0 ? $exitCode : 1);
    }
}

function escapeQualityArgument(string $argument): string
{
    return escapeshellarg($argument);
}

function resolvePhpCsFixer(): string
{
    $configured = getenv('PHP_CS_FIXER_BINARY');
    if (is_string($configured) && trim($configured) !== '') {
        return trim($configured);
    }

    $command = PHP_OS_FAMILY === 'Windows'
        ? 'where.exe php-cs-fixer 2>NUL'
        : 'command -v php-cs-fixer 2>/dev/null';
    exec($command, $matches, $exitCode);
    if ($exitCode !== 0 || $matches === []) {
        fwrite(STDERR, "php-cs-fixer was not found. Install it or set PHP_CS_FIXER_BINARY.\n");
        exit(1);
    }
    if (PHP_OS_FAMILY === 'Windows') {
        foreach ($matches as $match) {
            if (preg_match('/\.(?:bat|cmd|exe)$/i', $match) === 1) {
                return $match;
            }
        }
    }

    return $matches[0];
}

/** @return list<string> */
function repositoryFiles(string $root, string $extension): array
{
    exec(
        'git ls-files --cached --others --exclude-standard -- ' . escapeshellarg('*' . $extension),
        $files,
        $exitCode
    );
    if ($exitCode !== 0) {
        fwrite(STDERR, "Unable to enumerate tracked files.\n");
        exit($exitCode > 0 ? $exitCode : 1);
    }

    return array_values(array_filter(
        $files,
        static fn (string $file): bool => is_file($root . DIRECTORY_SEPARATOR . $file)
    ));
}

function validateJsonSyntax(string $root): bool
{
    $valid = true;
    foreach (repositoryFiles($root, '.json') as $jsonFile) {
        $content = file_get_contents($root . DIRECTORY_SEPARATOR . $jsonFile);
        if (!is_string($content)) {
            fwrite(STDERR, 'Unable to read JSON file: ' . $jsonFile . "\n");
            $valid = false;
            continue;
        }
        try {
            json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            fwrite(STDERR, $jsonFile . ': ' . $exception->getMessage() . "\n");
            $valid = false;
        }
    }

    return $valid;
}
