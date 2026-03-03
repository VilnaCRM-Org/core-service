<?php

declare(strict_types=1);

function readSpec(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        fwrite(STDERR, "Failed to read OpenAPI spec: {$path}\n");
        exit(1);
    }

    return $content;
}

function writeSpec(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "Failed to write OpenAPI spec: {$path}\n");
        exit(1);
    }
}

// Fix 1: Change 'type: string' to '@type: string' in view examples
$specFile = '.github/openapi-spec/spec.yaml';
$content = readSpec($specFile);
$content = str_replace(
    "example: { '@id': string, type: string,",
    "example: { '@id': string, '@type': string,",
    $content
);
writeSpec($specFile, $content);

// Fix 2: Add ulid property to UlidInterface.jsonld-output
$content = readSpec('.github/openapi-spec/spec.yaml');
$lines = explode("\n", $content);
$output = [];
$inUlidInterface = false;
$addedUlid = false;

foreach ($lines as $line) {
    $output[] = $line;

    if (trim($line) === 'UlidInterface.jsonld-output:') {
        $inUlidInterface = true;
        continue;
    }

    if ($inUlidInterface && ! $addedUlid && strpos($line, 'properties:') !== false) {
        $output[] = '        ulid:';
        $output[] = '          type: string';
        $addedUlid = true;
        continue;
    }

    if ($addedUlid && strpos($line, '@context') !== false) {
        $inUlidInterface = false;
    }
}

$content = implode("\n", $output);
writeSpec('.github/openapi-spec/spec.yaml', $content);

// Fix 3: Change ulid $ref to type: string in Customer.jsonld-output and CustomerType.jsonld-output
$content = readSpec('.github/openapi-spec/spec.yaml');
$lines = explode("\n", $content);
$lineCount = count($lines) - 2;
for ($i = 0; $i < $lineCount; $i++) {
    $currentLine = trim($lines[$i]);
    $nextLine = $lines[$i + 1];
    $followingLine = trim($lines[$i + 2]);
    $hasRef = strpos($nextLine, '$ref') !== false;
    $hasUlidInterface = strpos($nextLine, 'UlidInterface') !== false;
    if ($currentLine === 'ulid:' && $hasRef && $hasUlidInterface) {
        // Check if this is the Customer ulid (followed by createdAt) or CustomerType ulid (followed by Error)
        if ($followingLine === 'createdAt:' || $followingLine === 'Error:') {
            $lines[$i + 1] = '          type: string';
        }
    }
}
$content = implode("\n", $lines);
writeSpec('.github/openapi-spec/spec.yaml', $content);
