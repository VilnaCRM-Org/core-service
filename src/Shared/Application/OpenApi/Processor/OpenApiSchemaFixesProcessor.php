<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class OpenApiSchemaFixesProcessor implements OpenApiProcessorInterface
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents() ?? new Components(new ArrayObject());
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $schemas = $this->hydraCollectionSchemaFixer->apply($schemas);
        $openApi = $openApi->withComponents($components->withSchemas($schemas));

        return PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->processPathItem($pathItem)
        );
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        return PathItemOperationMapper::map(
            $pathItem,
            fn (Operation $operation): Operation => $this->processOperation($operation)
        );
    }

    private function processOperation(Operation $operation): Operation
    {
        $updatedResponses = $this->updateResponses($operation->getResponses());

        return $updatedResponses === null
            ? $operation
            : $operation->withResponses($updatedResponses);
    }

    private function processResponse(Response $response): Response
    {
        $updatedContent = $this->updateContentItems($response->getContent());

        return $updatedContent === null
            ? $response
            : $response->withContent(new ArrayObject($updatedContent));
    }

    private function processMediaType(MediaType $mediaType): MediaType
    {
        $schema = $mediaType->getSchema();
        if (! $schema instanceof ArrayObject) {
            return $mediaType;
        }

        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema(
            $schema->getArrayCopy()
        );

        return $updatedSchema === null
            ? $mediaType
            : $mediaType->withSchema(new ArrayObject($updatedSchema));
    }

    /**
     * @param array<int|string, Response>|null $responses
     *
     * @return array<int|string, Response|array>|null
     */
    private function updateResponses(?array $responses): ?array
    {
        if ($responses === null) {
            return null;
        }

        $updatedResponses = $responses;
        $hasChanges = false;

        foreach ($responses as $statusCode => $response) {
            if ($this->updateResponseAtStatus(
                $updatedResponses,
                $statusCode,
                $response
            )) {
                $hasChanges = true;
            }
        }

        return $hasChanges ? $updatedResponses : null;
    }

    /**
     * @param array<int|string, Response> $responses
     */
    private function updateResponseAtStatus(
        array &$responses,
        int|string $statusCode,
        Response|array $response
    ): bool {
        if (! $response instanceof Response) {
            return false;
        }

        $updatedResponse = $this->processResponse($response);
        if ($updatedResponse === $response) {
            return false;
        }

        $responses[$statusCode] = $updatedResponse;

        return true;
    }

    /**
     * @return array<string, array|MediaType>|null
     */
    private function updateContentItems(?ArrayObject $content): ?array
    {
        if (! $content instanceof ArrayObject) {
            return null;
        }

        $contentItems = $content->getArrayCopy();
        $hasChanges = false;

        foreach ($contentItems as $mediaType => $definition) {
            if ($this->updateContentDefinition(
                $contentItems,
                $mediaType,
                $definition
            )) {
                $hasChanges = true;
            }
        }

        return $hasChanges ? $contentItems : null;
    }

    /**
     * @param array<string, array|MediaType> $contentItems
     */
    private function updateContentDefinition(
        array &$contentItems,
        int|string $mediaType,
        array|MediaType $definition
    ): bool {
        $updatedDefinition = $definition instanceof MediaType
            ? $this->processMediaType($definition)
            : $this->processArrayDefinition($definition);

        if ($updatedDefinition === null || $updatedDefinition === $definition) {
            return false;
        }

        $contentItems[$mediaType] = $updatedDefinition;

        return true;
    }

    /**
     * @return array<string, SchemaValue>|null
     */
    private function processArrayDefinition(?array $definition): ?array
    {
        $normalizedDefinition = SchemaNormalizer::normalize($definition);
        if (! array_key_exists('schema', $normalizedDefinition)) {
            return null;
        }

        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema(
            SchemaNormalizer::normalize($normalizedDefinition['schema'])
        );
        if ($updatedSchema === null) {
            return null;
        }

        $normalizedDefinition['schema'] = $updatedSchema;

        return $normalizedDefinition;
    }
}
