#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$scanDirectories = [
    $projectRoot . '/config',
    $projectRoot . '/scripts',
    $projectRoot . '/src',
    $projectRoot . '/tests',
];
$excludedDirectories = [
    'tests/CLI/bats/php',
    'var',
    'vendor',
];
$excludedFiles = [
    'config/reference.php',
];
$staticMethods = collectStaticMethods($scanDirectories, $projectRoot, $excludedDirectories, $excludedFiles);
$methodKeys = array_keys($staticMethods);

if (($argv[1] ?? null) === '--dump-baseline') {
    foreach ($methodKeys as $methodKey) {
        echo $methodKey . PHP_EOL;
    }

    exit(0);
}

if ($methodKeys === []) {
    echo 'No static methods found in project PHP files.' . PHP_EOL;

    exit(0);
}

echo 'Static methods are forbidden in project PHP files. Use injected services, listeners, factories, or value objects instead.' . PHP_EOL;
echo PHP_EOL . 'Static method declarations:' . PHP_EOL;

foreach ($methodKeys as $methodKey) {
    echo ' - ' . $staticMethods[$methodKey] . PHP_EOL;
}

exit(1);

/**
 * @return array<string, string>
 */
function collectStaticMethods(
    array $scanDirectories,
    string $projectRoot,
    array $excludedDirectories,
    array $excludedFiles
): array {
    $methods = [];

    foreach (phpFiles($scanDirectories, $projectRoot, $excludedDirectories, $excludedFiles) as $filePath) {
        foreach (staticMethodsInFile($filePath, $projectRoot) as $methodKey => $description) {
            $methods[$methodKey] = $description;
        }
    }

    ksort($methods);

    return $methods;
}

/**
 * @return list<string>
 */
function phpFiles(
    array $scanDirectories,
    string $projectRoot,
    array $excludedDirectories,
    array $excludedFiles
): array {
    $files = [];

    foreach ($scanDirectories as $scanDirectory) {
        if (!is_dir($scanDirectory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanDirectory));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();
            $relativePath = relativePath($filePath, $projectRoot);
            if (
                in_array($relativePath, $excludedFiles, true)
                || isExcludedPath($relativePath, $excludedDirectories)
            ) {
                continue;
            }

            $files[] = $filePath;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param list<string> $excludedDirectories
 */
function isExcludedPath(string $relativePath, array $excludedDirectories): bool
{
    foreach ($excludedDirectories as $excludedDirectory) {
        if ($relativePath === $excludedDirectory || str_starts_with($relativePath, $excludedDirectory . DIRECTORY_SEPARATOR)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<string, string>
 */
function staticMethodsInFile(string $filePath, string $projectRoot): array
{
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        return [];
    }

    $tokens = token_get_all($contents);
    $methods = [];
    $namespace = '';
    $braceDepth = 0;
    $pendingClass = null;
    $classStack = [];
    $tokenCount = count($tokens);

    for ($index = 0; $index < $tokenCount; $index++) {
        $token = $tokens[$index];

        if (is_array($token) && $token[0] === T_NAMESPACE) {
            $namespace = readNamespace($tokens, $index);
            continue;
        }

        if (is_array($token) && isClassLikeToken($token[0]) && ! isAnonymousClass($tokens, $index)) {
            $className = readClassName($tokens, $index);
            if ($className !== null) {
                $pendingClass = [
                    'name' => qualifyClassName($namespace, $className),
                    'braceDepth' => null,
                ];
            }

            continue;
        }

        if ($token === '{') {
            $braceDepth++;

            if ($pendingClass !== null) {
                $pendingClass['braceDepth'] = $braceDepth;
                $classStack[] = $pendingClass;
                $pendingClass = null;
            }

            continue;
        }

        if ($token === '}') {
            while ($classStack !== [] && end($classStack)['braceDepth'] === $braceDepth) {
                array_pop($classStack);
            }

            $braceDepth--;
            continue;
        }

        if (! is_array($token) || $token[0] !== T_STATIC || $classStack === []) {
            continue;
        }

        $currentClass = end($classStack);
        if ($currentClass['braceDepth'] !== $braceDepth) {
            continue;
        }

        $functionIndex = nextMethodFunctionTokenIndex($tokens, $index + 1);
        if ($functionIndex === null || tokenId($tokens[$functionIndex]) !== T_FUNCTION) {
            continue;
        }

        $methodName = readFunctionName($tokens, $functionIndex);
        if ($methodName === null) {
            continue;
        }

        $methodKey = $currentClass['name'] . '::' . $methodName;
        $methods[$methodKey] = sprintf(
            '%s (%s:%d)',
            $methodKey,
            relativePath($filePath, $projectRoot),
            $token[2]
        );
    }

    return $methods;
}

function readNamespace(array $tokens, int $index): string
{
    $namespace = '';
    $tokenCount = count($tokens);

    for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
        $token = $tokens[$cursor];
        if ($token === ';' || $token === '{') {
            break;
        }

        if (! is_array($token)) {
            continue;
        }

        if (in_array($token[0], namespaceTokenIds(), true)) {
            $namespace .= $token[1];
        }
    }

    return $namespace;
}

function readClassName(array $tokens, int $index): ?string
{
    $tokenCount = count($tokens);

    for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
        $token = $tokens[$cursor];
        if ($token === '{') {
            return null;
        }

        if (is_array($token) && $token[0] === T_STRING) {
            return $token[1];
        }
    }

    return null;
}

function readFunctionName(array $tokens, int $index): ?string
{
    $tokenCount = count($tokens);

    for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
        $token = $tokens[$cursor];
        if ($token === '&' || tokenId($token) === T_WHITESPACE || tokenId($token) === T_COMMENT || tokenId($token) === T_DOC_COMMENT) {
            continue;
        }

        if (is_array($token) && $token[0] === T_STRING) {
            return $token[1];
        }

        return null;
    }

    return null;
}

function nextSignificantTokenIndex(array $tokens, int $index): ?int
{
    $tokenCount = count($tokens);

    for ($cursor = $index; $cursor < $tokenCount; $cursor++) {
        $tokenId = tokenId($tokens[$cursor]);
        if ($tokenId === T_WHITESPACE || $tokenId === T_COMMENT || $tokenId === T_DOC_COMMENT) {
            continue;
        }

        return $cursor;
    }

    return null;
}

function nextMethodFunctionTokenIndex(array $tokens, int $index): ?int
{
    $cursor = nextSignificantTokenIndex($tokens, $index);

    while ($cursor !== null && isMethodModifierToken(tokenId($tokens[$cursor]))) {
        $cursor = nextSignificantTokenIndex($tokens, $cursor + 1);
    }

    return $cursor;
}

function isMethodModifierToken(?int $tokenId): bool
{
    $modifierTokens = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT];

    if (defined('T_READONLY')) {
        $modifierTokens[] = T_READONLY;
    }

    return $tokenId !== null && in_array($tokenId, $modifierTokens, true);
}

function isAnonymousClass(array $tokens, int $index): bool
{
    for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
        $token = $tokens[$cursor];
        $tokenId = tokenId($token);
        if ($tokenId === T_WHITESPACE || $tokenId === T_COMMENT || $tokenId === T_DOC_COMMENT) {
            continue;
        }

        return $tokenId === T_NEW;
    }

    return false;
}

function isClassLikeToken(int $tokenId): bool
{
    return in_array($tokenId, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true);
}

/**
 * @return list<int>
 */
function namespaceTokenIds(): array
{
    $tokenIds = [T_STRING, T_NS_SEPARATOR];

    if (defined('T_NAME_QUALIFIED')) {
        $tokenIds[] = T_NAME_QUALIFIED;
    }

    if (defined('T_NAME_FULLY_QUALIFIED')) {
        $tokenIds[] = T_NAME_FULLY_QUALIFIED;
    }

    return $tokenIds;
}

function qualifyClassName(string $namespace, string $className): string
{
    return $namespace === '' ? $className : $namespace . '\\' . $className;
}

function tokenId(mixed $token): ?int
{
    return is_array($token) ? $token[0] : null;
}

function relativePath(string $filePath, string $projectRoot): string
{
    $prefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (str_starts_with($filePath, $prefix)) {
        return substr($filePath, strlen($prefix));
    }

    return $filePath;
}
