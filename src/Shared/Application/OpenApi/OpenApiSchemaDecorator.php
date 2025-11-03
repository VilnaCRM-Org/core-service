<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class OpenApiSchemaDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Get the schemas from components
        $schemas = $openApi->getComponents()?->getSchemas();

        if ($schemas === null) {
            return $openApi;
        }

        // Modify UlidInterface schema to be a simple string type
        if (isset($schemas['UlidInterface.jsonld-output'])) {
            $schemas['UlidInterface.jsonld-output'] = new ArrayObject([
                'type' => 'string',
                'description' => 'ULID (Universally Unique Lexicographically Sortable Identifier)',
                'example' => '01K8XS6ASF9JENWM7KFCC626PH',
            ]);
        }

        // Update the components with modified schemas
        $components = $openApi->getComponents();
        if ($components !== null) {
            $openApi = $openApi->withComponents(
                new Components(
                    schemas: $schemas,
                    responses: $components->getResponses(),
                    parameters: $components->getParameters(),
                    examples: $components->getExamples(),
                    requestBodies: $components->getRequestBodies(),
                    headers: $components->getHeaders(),
                    securitySchemes: $components->getSecuritySchemes(),
                    links: $components->getLinks(),
                    callbacks: $components->getCallbacks(),
                )
            );
        }

        return $openApi;
    }
}
