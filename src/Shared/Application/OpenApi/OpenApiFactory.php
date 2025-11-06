<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }

        $this->addParameterDescriptions($openApi);
        $openApi = $this->addTagDescriptions($openApi);
        $this->fixIriReferenceTypes($openApi);
        $this->removeDeprecatedParameterProperties($openApi);

        return $openApi;
    }

    private function addParameterDescriptions(OpenApi $openApi): void
    {
        $parameterDescriptions = $this->getParameterDescriptions();

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
                $operation = $pathItem->{'get' . $method}();
                if ($operation === null) {
                    continue;
                }

                $parameters = $operation->getParameters();
                if (empty($parameters)) {
                    continue;
                }

                $updatedParameters = [];
                foreach ($parameters as $parameter) {
                    $paramName = $parameter->getName();
                    if (isset($parameterDescriptions[$paramName]) && empty($parameter->getDescription())) {
                        $parameter = $parameter->withDescription($parameterDescriptions[$paramName]);
                    }
                    $updatedParameters[] = $parameter;
                }

                $operation = $operation->withParameters($updatedParameters);
                $pathItem = $pathItem->{'with' . $method}($operation);
            }

            $openApi->getPaths()->addPath($path, $pathItem);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getParameterDescriptions(): array
    {
        return [
            'order[ulid]' => 'Sort by customer unique identifier',
            'order[createdAt]' => 'Sort by creation date',
            'order[updatedAt]' => 'Sort by last update date',
            'order[email]' => 'Sort by customer email address',
            'order[initials]' => 'Sort by customer initials',
            'order[phone]' => 'Sort by customer phone number',
            'order[leadSource]' => 'Sort by lead source',
            'order[type.value]' => 'Sort by customer type',
            'order[status.value]' => 'Sort by customer status',
            'order[value]' => 'Sort by value',
            'initials' => 'Filter by customer initials (exact match)',
            'initials[]' => 'Filter by multiple customer initials (exact match)',
            'email' => 'Filter by customer email address (exact match)',
            'email[]' => 'Filter by multiple customer email addresses (exact match)',
            'phone' => 'Filter by customer phone number (exact match)',
            'phone[]' => 'Filter by multiple customer phone numbers (exact match)',
            'leadSource' => 'Filter by lead source (exact match)',
            'leadSource[]' => 'Filter by multiple lead sources (exact match)',
            'type.value' => 'Filter by customer type value (exact match)',
            'type.value[]' => 'Filter by multiple customer type values (exact match)',
            'status.value' => 'Filter by customer status value (exact match)',
            'status.value[]' => 'Filter by multiple customer status values (exact match)',
            'value' => 'Filter by value (partial match)',
            'value[]' => 'Filter by value (partial match)',
            'confirmed' => 'Filter by customer confirmation status (true/false)',
            'createdAt[before]' => 'Filter customers created before this date',
            'createdAt[strictly_before]' => 'Filter customers created strictly before this date',
            'createdAt[after]' => 'Filter customers created after this date',
            'createdAt[strictly_after]' => 'Filter customers created strictly after this date',
            'updatedAt[before]' => 'Filter customers updated before this date',
            'updatedAt[strictly_before]' => 'Filter customers updated strictly before this date',
            'updatedAt[after]' => 'Filter customers updated after this date',
            'updatedAt[strictly_after]' => 'Filter customers updated strictly after this date',
            'ulid[between]' => 'Filter by ULID range (comma-separated start and end)',
            'ulid[gt]' => 'Filter by ULID greater than',
            'ulid[gte]' => 'Filter by ULID greater than or equal to',
            'ulid[lt]' => 'Filter by ULID less than',
            'ulid[lte]' => 'Filter by ULID less than or equal to',
            'page' => 'Page number for pagination',
            'itemsPerPage' => 'Number of items per page',
        ];
    }

    private function addTagDescriptions(OpenApi $openApi): OpenApi
    {
        $tagDescriptions = [
            'Customer' => 'Operations related to customer management',
            'CustomerStatus' => 'Operations related to customer status management',
            'CustomerType' => 'Operations related to customer type management',
            'HealthCheck' => 'Health check endpoints for monitoring',
        ];

        $tags = [];
        foreach ($openApi->getTags() as $tag) {
            $tagName = $tag->getName();
            if (isset($tagDescriptions[$tagName]) && empty($tag->getDescription())) {
                $tag = $tag->withDescription($tagDescriptions[$tagName]);
            }
            $tags[] = $tag;
        }

        return $openApi->withTags($tags);
    }

    private function fixIriReferenceTypes(OpenApi $openApi): void
    {
        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
                $operation = $pathItem->{'get' . $method}();
                if ($operation === null) {
                    continue;
                }

                $requestBody = $operation->getRequestBody();
                if ($requestBody === null) {
                    continue;
                }

                $content = $requestBody->getContent();
                if ($content === null) {
                    continue;
                }

                $modified = false;
                foreach ($content as $mediaType => $mediaTypeObject) {
                    if (!isset($mediaTypeObject['schema'])) {
                        continue;
                    }

                    $schema = $mediaTypeObject['schema'];
                    if (isset($schema['properties'])) {
                        foreach ($schema['properties'] as $propName => $propSchema) {
                            if (isset($propSchema['type']) && $propSchema['type'] === 'iri-reference') {
                                $schema['properties'][$propName]['type'] = 'string';
                                $schema['properties'][$propName]['format'] = 'iri-reference';
                                $modified = true;
                            }
                        }
                    }

                    if ($modified) {
                        $content[$mediaType]['schema'] = $schema;
                    }
                }

                if ($modified) {
                    $requestBody = $requestBody->withContent(new \ArrayObject($content->getArrayCopy()));
                    $operation = $operation->withRequestBody($requestBody);
                    $pathItem = $pathItem->{'with' . $method}($operation);
                }
            }

            $openApi->getPaths()->addPath($path, $pathItem);
        }
    }

    private function removeDeprecatedParameterProperties(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();
        if ($paths === null) {
            return;
        }

        foreach ($paths->getPaths() as $path => $pathItem) {
            foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
                $operation = $pathItem->{'get' . $method}();
                if ($operation === null) {
                    continue;
                }

                $parameters = $operation->getParameters();
                if (empty($parameters)) {
                    continue;
                }

                $updatedParameters = [];
                foreach ($parameters as $parameter) {
                    // For path parameters, use minimal required properties only
                    if ($parameter->getIn() === 'path') {
                        $updatedParameters[] = new \ApiPlatform\OpenApi\Model\Parameter(
                            name: $parameter->getName(),
                            in: $parameter->getIn(),
                            description: $parameter->getDescription(),
                            required: $parameter->getRequired(),
                            schema: $parameter->getSchema()
                        );
                    } else {
                        // Keep all properties for non-path parameters
                        $updatedParameters[] = $parameter;
                    }
                }

                $operation = $operation->withParameters($updatedParameters);
                $pathItem = $pathItem->{'with' . $method}($operation);
                $paths->addPath($path, $pathItem);
            }
        }
    }
}
