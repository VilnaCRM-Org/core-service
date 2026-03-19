<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

$specFile = '.github/openapi-spec/spec.yaml';

$readSpec = static function (string $path): array {
    try {
        return Yaml::parseFile($path);
    } catch (ParseException $e) {
        fwrite(STDERR, "Failed to parse OpenAPI spec: {$path} - {$e->getMessage()}\n");
        exit(1);
    }
};

$writeSpec = static function (string $path, array $spec): void {
    try {
        $yaml = Yaml::dump($spec, 10, 2);
        if (file_put_contents($path, $yaml) === false) {
            fwrite(STDERR, "Failed to write OpenAPI spec: {$path}\n");
            exit(1);
        }
    } catch (ParseException $e) {
        fwrite(STDERR, "Failed to dump OpenAPI spec: {$e->getMessage()}\n");
        exit(1);
    }
};

/**
 * Recursively find and fix 'type: string' to '@type: string' in example objects
 */
function fixExampleTypeToAtType(array &$data): void
{
    if (!is_array($data)) {
        return;
    }

    // Check if this is an example object that needs fixing
    if (isset($data['example']) && is_array($data['example'])) {
        $example = $data['example'];
        if (array_key_exists('type', $example) && !array_key_exists('@type', $example)) {
            $data['example']['@type'] = $data['example']['type'];
            unset($data['example']['type']);
        }
    }

    // Recurse into nested arrays
    foreach ($data as &$value) {
        if (is_array($value)) {
            fixExampleTypeToAtType($value);
        }
    }
}

/**
 * Find UlidInterface.jsonld-output schema and idempotently add 'ulid' property
 */
function addUlidProperty(array &$components): void
{
    if (!isset($components['schemas']) || !is_array($components['schemas'])) {
        return;
    }

    $schemas = &$components['schemas'];

    // Check if UlidInterface.jsonld-output exists
    if (!isset($schemas['UlidInterface.jsonld-output'])) {
        return;
    }

    $ulidInterface = &$schemas['UlidInterface.jsonld-output'];

    if (!isset($ulidInterface['properties']) || !is_array($ulidInterface['properties'])) {
        $ulidInterface['properties'] = [];
    }

    // Add ulid property if it doesn't exist
    if (!array_key_exists('ulid', $ulidInterface['properties'])) {
        $ulidInterface['properties']['ulid'] = [
            'type' => 'string',
        ];
    }
}

/**
 * Find Customer.jsonld-output and CustomerType.jsonld-output schemas and change
 * ulid $ref to type: string
 */
function fixUlidRefToType(array &$components): void
{
    if (!isset($components['schemas']) || !is_array($components['schemas'])) {
        return;
    }

    $schemas = &$components['schemas'];

    foreach (['Customer.jsonld-output', 'CustomerType.jsonld-output'] as $schemaName) {
        if (!isset($schemas[$schemaName])) {
            continue;
        }

        $schema = &$schemas[$schemaName];

        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            continue;
        }

        $properties = &$schema['properties'];

        // Check if ulid property exists and has a $ref to UlidInterface
        if (isset($properties['ulid']) && is_array($properties['ulid'])) {
            if (isset($properties['ulid']['$ref']) && strpos($properties['ulid']['$ref'], 'UlidInterface') !== false) {
                // Replace $ref with direct type: string
                $properties['ulid'] = [
                    'type' => 'string',
                ];
            }
        }
    }
}

// Main execution
$spec = $readSpec($specFile);

// Fix 1: Change 'type: string' to '@type: string' in view examples
fixExampleTypeToAtType($spec);

// Fix 2: Add ulid property to UlidInterface.jsonld-output (idempotent)
if (isset($spec['components'])) {
    addUlidProperty($spec['components']);

    // Fix 3: Change ulid $ref to type: string in Customer and CustomerType schemas
    fixUlidRefToType($spec['components']);
}

$writeSpec($specFile, $spec);
