<?php

declare(strict_types=1);

$content = file_get_contents('.github/openapi-spec/spec.yaml');

// Fix 1: Change 'type: string' to '@type: string' in view examples
$content = preg_replace(
    "/example: \{ '@id': string, type: string,/",
    "example: { '@id': string, '@type': string,",
    $content
);

// Fix 2: Add ulid property to UlidInterface.jsonld-output
$content = preg_replace(
    '/(UlidInterface\.jsonld-output:.*?properties:)\s*(\'@context\':)/s',
    "$1\n        ulid:\n          type: string\n        $2",
    $content
);

file_put_contents('.github/openapi-spec/spec.yaml', $content);
