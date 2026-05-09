#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$sourceDirectory = $projectRoot . '/src';
$baselineFile = $projectRoot . '/config/static-methods-baseline.txt';
$staticMethods = collectStaticMethods($sourceDirectory, $projectRoot);
$methodKeys = array_keys($staticMethods);

if (($argv[1] ?? null) === '--dump-baseline') {
    foreach ($methodKeys as $methodKey) {
        echo $methodKey . PHP_EOL;
    }

    exit(0);
}

$baseline = readBaseline($baselineFile);
$newMethods = array_values(array_diff($methodKeys, $baseline));
$staleBaseline = array_values(array_diff($baseline, $methodKeys));

if ($newMethods === [] && $staleBaseline === []) {
    echo 'No new static methods found in src/.' . PHP_EOL;

    exit(0);
}

if ($newMethods !== []) {
    echo 'Static methods are forbidden in src/. Use injected services, listeners, factories, or value objects instead.' . PHP_EOL;
    echo PHP_EOL . 'New static method declarations:' . PHP_EOL;

    foreach ($newMethods as $methodKey) {
        echo ' - ' . $staticMethods[$methodKey] . PHP_EOL;
    }
}

if ($staleBaseline !== []) {
    echo PHP_EOL . 'Static method baseline contains entries that no longer exist:' . PHP_EOL;

    foreach ($staleBaseline as $methodKey) {
        echo ' - ' . $methodKey . PHP_EOL;
    }

    echo PHP_EOL . 'Remove stale entries from config/static-methods-baseline.txt.' . PHP_EOL;
}

exit(1);

/**
 * @return array<string, string>
 */
function collectStaticMethods(string $sourceDirectory, string $projectRoot): array
{
    $methods = [];

    foreach (phpFiles($sourceDirectory) as $filePath) {
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
function phpFiles(string $sourceDirectory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $files[] = $file->getPathname();
    }

    sort($files);

    return $files;
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

/**
 * @return list<string>
 */
function readBaseline(string $baselineFile): array
{
    if (! is_file($baselineFile)) {
        return [];
    }

    $baseline = [];
    $lines = file($baselineFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $entry = trim($line);
        if ($entry === '' || str_starts_with($entry, '#')) {
            continue;
        }

        $baseline[] = $entry;
    }

    $baseline = array_values(array_unique($baseline));
    sort($baseline);

    return $baseline;
}
