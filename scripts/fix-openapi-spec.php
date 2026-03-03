<?php

declare(strict_types=1);

// Fix 1: Change 'type: string' to '@type: string' in view examples
$specFile = '.github/openapi-spec/spec.yaml';
$search = "example: { '@id': string, type: string,";
$replace = "example: { '@id': string, '@type': string,";
shell_exec('sed -i "s/' . $search . '/' . $replace . '/g" ' . $specFile);

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
