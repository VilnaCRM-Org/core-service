<?php

declare(strict_types=1);

// Fix 1: Change 'type: string' to '@type: string' in view examples
$specFile = '.github/openapi-spec/spec.yaml';
$content = file_get_contents($specFile);
$content = str_replace(
    "example: { '@id': string, type: string,",
    "example: { '@id': string, '@type': string,",
    $content
);
file_put_contents($specFile, $content);

// Fix 2: Add ulid property to UlidInterface.jsonld-output
$content = file_get_contents('.github/openapi-spec/spec.yaml');
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

    if ($inUlidInterface && !$addedUlid && strpos($line, 'properties:') !== false) {
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
file_put_contents('.github/openapi-spec/spec.yaml', $content);

// Fix 3: Change ulid $ref to type: string in Customer.jsonld-output and CustomerType.jsonld-output
$content = file_get_contents('.github/openapi-spec/spec.yaml');
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
file_put_contents('.github/openapi-spec/spec.yaml', $content);
