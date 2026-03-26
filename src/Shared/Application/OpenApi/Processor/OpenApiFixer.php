<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Export-time OpenAPI artifact normalizer for checked-in YAML specs.
 *
 * Runtime schema corrections live in the OpenApiFactory processor pipeline. This fixer is kept
 * for export-only normalization that is specific to the serialized YAML artifact, such as quoted
 * HTTP status keys and empty security sequence formatting used by diff/lint jobs.
 *
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 * @phpstan-type SpecSchema array<string, SchemaValue>
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class OpenApiFixer
{
    private const EMPTY_SECURITY_PLACEHOLDER = '__OPENAPI_EMPTY_SECURITY__';

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
        if (isset($spec['components']) && is_array($spec['components'])) {
            $this->addUlidProperty($spec['components']);

            // Fix 3: Change ulid $ref to type: string in Customer and CustomerType schemas
            $this->fixUlidRefToType($spec['components']);
        }

        // Fix 4: Fix 422 error responses to use correct error type (/errors/422)
        $this->fix422ErrorType($spec);

        // Fix 5: Remove content from 204 responses (no body on success)
        $this->fix204Responses($spec);

        // Fix 6: Normalize only actual OpenAPI security nodes
        $this->fixSecuritySections($spec);

        $this->writeSpec($spec);
    }

    /**
     * @return SpecSchema
     *
     * @throws RuntimeException when spec file cannot be parsed
     */
    private function readSpec(string $path): array
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $e) { // @codeCoverageIgnore
            // @infection-ignore-next-line Exception code is behavior-neutral
            throw new RuntimeException("Failed to parse OpenAPI spec: {$path} - {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @param SpecSchema $spec
     *
     * @throws RuntimeException when spec cannot be written
     */
    private function writeSpec(array $spec): void
    {
        // Use DUMP_NUMERIC_KEY_AS_STRING to ensure HTTP status codes are quoted
        // @infection-ignore-next-line dump depth/indent are formatting-only for this generated spec
        $yaml = Yaml::dump($spec, 10, 2, Yaml::DUMP_NUMERIC_KEY_AS_STRING);
        $yaml = str_replace(
            'security: ' . self::EMPTY_SECURITY_PLACEHOLDER,
            'security: []',
            $yaml
        );

        (new Filesystem())->dumpFile($this->specFile, $yaml);
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
                if ($hasPlainType && ! $hasTypeMarker) {
                    $data['example']['@type'] = $data['example']['type'];
                }

                if ($hasPlainType) {
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
        // @infection-ignore-next-line guard prevents invalid schema access
        if (! isset($components['schemas']) || ! is_array($components['schemas'])) {
            return;
        }

        $schemas = &$components['schemas'];

        // Check if UlidInterface.jsonld-output exists, even if it is explicitly null
        if (! array_key_exists('UlidInterface.jsonld-output', $schemas)) {
            return;
        }

        $ulidInterface = &$schemas['UlidInterface.jsonld-output'];

        if (! is_array($ulidInterface)) {
            $ulidInterface = [];
        }

        if (! isset($ulidInterface['properties']) || ! is_array($ulidInterface['properties'])) {
            $ulidInterface['properties'] = [];
        }

        // Add ulid property if it doesn't exist
        if (! array_key_exists('ulid', $ulidInterface['properties'])) {
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
        // @infection-ignore-next-line guard prevents invalid schema access
        if (! isset($components['schemas']) || ! is_array($components['schemas'])) {
            return;
        }

        $schemas = &$components['schemas'];

        foreach (['Customer.jsonld-output', 'CustomerType.jsonld-output', 'CustomerStatus.jsonld-output'] as $schemaName) {
            // @infection-ignore-next-line loop should skip missing schemas
            if (! isset($schemas[$schemaName])) {
                continue;
            }

            $schema = &$schemas[$schemaName];

            // @infection-ignore-next-line loop should skip invalid properties
            if (! isset($schema['properties']) || ! is_array($schema['properties'])) {
                continue;
            }

            $properties = &$schema['properties'];

            // Check if ulid property exists and has a $ref to UlidInterface
            if (isset($properties['ulid']) && is_array($properties['ulid'])) {
                $ulidRef = $properties['ulid']['$ref'] ?? null;
                // @infection-ignore-next-line both checks are required for safe replacement
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
        // @infection-ignore-line Early return is behavior-neutral
        if (! isset($spec['paths']) || ! is_array($spec['paths'])) {
            return;
        }

        foreach ($spec['paths'] as &$path) {
            $this->processPathFor422Errors($path);
        }
    }

    /**
     * Process a single path item for 422 error fixes
     */
    private function processPathFor422Errors(array|string|null &$path): void
    {
        // @infection-ignore-line Skip is behavior-neutral
        if (! is_array($path)) {
            return;
        }

        foreach ($path as &$methodData) {
            $this->processMethodFor422Errors($methodData);
        }
    }

    /**
     * Process a single method for 422 error fixes
     */
    private function processMethodFor422Errors(array|string|null &$methodData): void
    {
        // @infection-ignore-next-line guard prevents iterating invalid responses
        if (! is_array($methodData) || ! isset($methodData['responses']) || ! is_array($methodData['responses'])) {
            return;
        }

        foreach ($methodData['responses'] as $statusCode => &$response) {
            $this->processResponseFor422Errors($statusCode, $response);
        }
    }

    /**
     * Process a single response for 422 error fixes
     */
    private function processResponseFor422Errors(string|int $statusCode, mixed &$response): void
    {
        if (! in_array($statusCode, ['422', 422], true) || ! is_array($response)) {
            return;
        }

        if (! isset($response['content'])) {
            return;
        }

        // @infection-ignore-line Skip is behavior-neutral
        if (! isset($response['content']['application/problem+json'])) {
            return;
        }

        $problemJson = &$response['content']['application/problem+json'];
        $responseExample = $problemJson['example'] ?? null;
        $is422Error = is_array($responseExample)
            && array_key_exists('status', $responseExample)
            && (int) $responseExample['status'] === 422;
        if ($is422Error) {
            $problemJson['example']['status'] = 422;

            // Fix the error type for validation errors
            $exampleType = $responseExample['type'] ?? null;
            if ($exampleType === '/errors/500') {
                $problemJson['example']['type'] = '/errors/422';
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
        // @infection-ignore-line Early return is behavior-neutral
        if (! isset($spec['paths']) || ! is_array($spec['paths'])) {
            return;
        }

        foreach ($spec['paths'] as &$path) {
            $this->processPathFor204Responses($path);
        }
    }

    /**
     * Process a single path item for 204 response fixes
     */
    private function processPathFor204Responses(array|string|null &$path): void
    {
        // @infection-ignore-line Skip is behavior-neutral
        if (! is_array($path)) {
            return;
        }

        foreach ($path as &$methodData) {
            $this->processMethodFor204Responses($methodData);
        }
    }

    /**
     * Process a single method for 204 response fixes
     */
    private function processMethodFor204Responses(array|string|null &$methodData): void
    {
        // @infection-ignore-next-line guard prevents iterating invalid responses
        if (! is_array($methodData) || ! isset($methodData['responses']) || ! is_array($methodData['responses'])) {
            return;
        }

        foreach ($methodData['responses'] as $statusCode => &$response) {
            $this->processResponseFor204($statusCode, $response);
        }
    }

    /**
     * Process a single response for 204
     */
    private function processResponseFor204(string|int $statusCode, mixed &$response): void
    {
        // Only process 204 responses (handle both string and integer keys)
        // @infection-ignore-next-line handle both YAML string and integer keys
        if (! in_array($statusCode, ['204', 204], true) || ! is_array($response)) {
            return;
        }

        // Remove content section for 204 responses
        unset($response['content']);
    }

    /**
     * Fix empty security nodes without mutating example payload fields named "security".
     *
     * @param SpecSchema $spec
     */
    private function fixSecuritySections(array &$spec): void
    {
        $this->normalizeSecurityValue($spec);

        if (! isset($spec['paths']) || ! is_array($spec['paths'])) {
            return;
        }

        foreach ($spec['paths'] as &$pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            $this->normalizeSecurityValue($pathItem);

            foreach ($pathItem as &$operation) {
                if (! is_array($operation)) {
                    continue;
                }

                $this->normalizeSecurityValue($operation);
            }
        }
    }

    /**
     * @param SpecSchema $node
     */
    private function normalizeSecurityValue(array &$node): void
    {
        if (! array_key_exists('security', $node)) {
            return;
        }

        if ($node['security'] === null || $node['security'] === []) {
            $node['security'] = self::EMPTY_SECURITY_PLACEHOLDER;

            return;
        }

        if ($node['security'] instanceof ArrayObject && $node['security']->count() === 0) {
            $node['security'] = self::EMPTY_SECURITY_PLACEHOLDER;
        }
    }
}
