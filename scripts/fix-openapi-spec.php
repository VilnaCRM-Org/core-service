<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * OpenAPI spec fixer - addresses JSON-LD @type issues in Hydra output
 */
final class OpenApiFixer
{
    private string $specFile;

    public function __construct(string $specFile)
    {
        $this->specFile = $specFile;
    }

    public function run(): void
    {
        $spec = $this->readSpec($this->specFile);

        // Fix 1: Change 'type: string' to '@type: string' in view examples
        $this->fixExampleTypeToAtType($spec);

        // Fix 2: Add ulid property to UlidInterface.jsonld-output (idempotent)
        if (isset($spec['components'])) {
            $this->addUlidProperty($spec['components']);

            // Fix 3: Change ulid $ref to type: string in Customer and CustomerType schemas
            $this->fixUlidRefToType($spec['components']);
        }

        $this->writeSpec($spec);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSpec(string $path): array
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $e) {
            fwrite(STDERR, "Failed to parse OpenAPI spec: {$path} - {$e->getMessage()}\n");
            exit(1);
        }
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function writeSpec(array $spec): void
    {
        try {
            $yaml = Yaml::dump($spec, 10, 2);
            if (file_put_contents($this->specFile, $yaml) === false) {
                fwrite(STDERR, "Failed to write OpenAPI spec: {$this->specFile}\n");
                exit(1);
            }
        } catch (DumpException $e) {
            fwrite(STDERR, "Failed to dump OpenAPI spec: {$e->getMessage()}\n");
            exit(1);
        }
    }

    /**
     * Recursively find and fix 'type: string' to '@type: string' in JSON-LD example objects
     *
     * @param array<string, mixed> $data
     */
    private function fixExampleTypeToAtType(array &$data): void
    {
        if (!is_array($data)) {
            return;
        }

        // Check if this is an example object that needs fixing
        if (isset($data['example']) && is_array($data['example'])) {
            $example = $data['example'];

            // Only transform JSON-LD-like examples (have @context, @id, or @type markers)
            // Skip domain examples that have plain 'type' property (e.g., Customer IRI)
            $isJsonLd = isset($example['@context']) || isset($example['@id']) || isset($example['@type']);
            if (!$isJsonLd) {
                goto recurse;
            }

            // If type exists without @type, move type to @type
            if (array_key_exists('type', $example) && !array_key_exists('@type', $example)) {
                $data['example']['@type'] = $data['example']['type'];
                unset($data['example']['type']);
            }
            // If both type and @type exist, remove spurious type (keep @type)
            if (array_key_exists('type', $example) && array_key_exists('@type', $example)) {
                unset($data['example']['type']);
            }
        }

        recurse:
        // Recurse into nested arrays
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->fixExampleTypeToAtType($value);
            }
        }
    }

    /**
     * Find UlidInterface.jsonld-output schema and idempotently add 'ulid' property
     *
     * @param array<string, mixed> $components
     */
    private function addUlidProperty(array &$components): void
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
     *
     * @param array<string, mixed> $components
     */
    private function fixUlidRefToType(array &$components): void
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
                $ulidRef = $properties['ulid']['$ref'] ?? '';
                $hasUlidRef = strpos($ulidRef, 'UlidInterface') !== false;
                if ($hasUlidRef) {
                    // Replace $ref with direct type: string
                    $properties['ulid'] = [
                        'type' => 'string',
                    ];
                }
            }
        }
    }
}

$specFile = '.github/openapi-spec/spec.yaml';
$fixer = new OpenApiFixer($specFile);
$fixer->run();
