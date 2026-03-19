<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * OpenAPI spec fixer - addresses JSON-LD @type issues in Hydra output
 *
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 * @phpstan-type SpecSchema array<string, SchemaValue>
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

        // Fix 4: Fix 422 error responses to use correct error type (/errors/422)
        $this->fix422ErrorType($spec);

        // Fix 5: Remove content from 204 responses (no body on success)
        $this->fix204Responses($spec);

        $this->writeSpec($spec);
    }

    /**
     * @return SpecSchema
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
     * @param SpecSchema $spec
     */
    private function writeSpec(array &$spec): void
    {
        try {
            // Use DUMP_NUMERIC_KEY_AS_STRING to ensure HTTP status codes are quoted
            $yaml = Yaml::dump($spec, 10, 2, Yaml::DUMP_NUMERIC_KEY_AS_STRING);

            // Fix known empty-sequence fields: security: { } -> security: []
            // Also handle security: null -> security: []
            // Handle both top-level and indented entries
            $yaml = preg_replace('/^(\s*)security: \{\s*\}$/m', '$1security: []', $yaml);
            $yaml = preg_replace('/^(\s*)security: null$/m', '$1security: []', $yaml);

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
     * @param SpecSchema $data
     */
    private function fixExampleTypeToAtType(array &$data): void
    {
        // Check if this is an example object that needs fixing
        if (isset($data['example']) && is_array($data['example'])) {
            $example = $data['example'];

            // Only transform JSON-LD-like examples (have @context, @id, or @type markers)
            // Skip domain examples that have plain 'type' property (e.g., Customer IRI)
            $hasJsonLdMarker = isset($example['@context']) || isset($example['@id']);
            $hasTypeMarker = isset($example['@type']);
            $hasPlainType = array_key_exists('type', $example);

            if ($hasJsonLdMarker || $hasTypeMarker) {
                // If type exists without @type, move type to @type
                if ($hasPlainType && !$hasTypeMarker) {
                    $data['example']['@type'] = $data['example']['type'];
                    unset($data['example']['type']);
                }
                // If both type and @type exist, remove spurious type (keep @type)
                if ($hasPlainType && $hasTypeMarker) {
                    unset($data['example']['type']);
                }
            }
        }

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
     * @param SpecSchema $components
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
     * @param SpecSchema $components
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
                $ulidRef = $properties['ulid']['$ref'] ?? null;
                if (is_string($ulidRef) && str_contains($ulidRef, 'UlidInterface')) {
                    // Replace $ref with direct type: string
                    $properties['ulid'] = [
                        'type' => 'string',
                    ];
                }
            }
        }
    }

    /**
     * Fix 422 validation error responses to use correct error type
     *
     * @param SpecSchema $spec
     */
    private function fix422ErrorType(array &$spec): void
    {
        if (!isset($spec['paths'])) {
            return;
        }

        foreach ($spec['paths'] as &$path) {
            if (!is_array($path)) {
                continue;
            }

            foreach ($path as &$methodData) {
                if (!is_array($methodData) || !isset($methodData['responses'])) {
                    continue;
                }

                foreach ($methodData['responses'] as &$response) {
                    if (!is_array($response) || !isset($response['content'])) {
                        continue;
                    }

                    // Check if this is a 422 response - use references to persist changes
                    if (!isset($response['content']['application/problem+json'])) {
                        continue;
                    }

                    $problemJson = &$response['content']['application/problem+json'];
                    $responseExample = $problemJson['example'] ?? null;
                    $is422Error = is_array($responseExample)
                        && ($responseExample['status'] ?? null) === 422;
                    if ($is422Error) {
                        // Fix the error type for validation errors
                        $exampleType = $responseExample['type'] ?? null;
                        if ($exampleType === '/errors/500') {
                            $problemJson['example']['type'] = '/errors/422';
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix 204 responses to not declare content (no body on success)
     *
     * @param SpecSchema $spec
     */
    private function fix204Responses(array &$spec): void
    {
        if (!isset($spec['paths'])) {
            return;
        }

        foreach ($spec['paths'] as &$path) {
            if (!is_array($path)) {
                continue;
            }

            foreach ($path as &$methodData) {
                if (!is_array($methodData) || !isset($methodData['responses'])) {
                    continue;
                }

                foreach ($methodData['responses'] as $statusCode => &$response) {
                    // Only process 204 responses (handle both string and integer keys)
                    if (!in_array($statusCode, ['204', 204], true) || !is_array($response)) {
                        continue;
                    }

                    // Remove content section for 204 responses
                    unset($response['content']);
                }
            }
        }
    }
}
