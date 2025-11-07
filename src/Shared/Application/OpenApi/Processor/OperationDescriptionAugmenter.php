<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class OperationDescriptionAugmenter
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function augment(OpenApi $openApi): void
    {
        $operationMetadata = $this->getOperationMetadata();

        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->augmentPathItem($pathItem, $operationMetadata)
            );
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getOperationMetadata(): array
    {
        return [
            'api_customers_get_collection' => [
                'summary' => 'Retrieve customer collection',
                'description' => 'Retrieves a paginated collection of customers with optional filtering and sorting capabilities.',
            ],
            'api_customers_post' => [
                'summary' => 'Create a new customer',
                'description' => 'Creates a new customer resource with the provided data.',
            ],
            'api_customers_ulid_get' => [
                'summary' => 'Retrieve a customer',
                'description' => 'Retrieves a single customer resource by its unique identifier.',
            ],
            'api_customers_ulid_put' => [
                'summary' => 'Replace a customer',
                'description' => 'Replaces an existing customer resource with the provided data.',
            ],
            'api_customers_ulid_delete' => [
                'summary' => 'Delete a customer',
                'description' => 'Deletes an existing customer resource by its unique identifier.',
            ],
            'api_customers_ulid_patch' => [
                'summary' => 'Update a customer',
                'description' => 'Partially updates an existing customer resource with the provided data.',
            ],
            'api_customer_statuses_get_collection' => [
                'summary' => 'Retrieve customer status collection',
                'description' => 'Retrieves a paginated collection of customer statuses with optional filtering and sorting capabilities.',
            ],
            'api_customer_statuses_post' => [
                'summary' => 'Create a new customer status',
                'description' => 'Creates a new customer status resource with the provided data.',
            ],
            'api_customer_statuses_ulid_get' => [
                'summary' => 'Retrieve a customer status',
                'description' => 'Retrieves a single customer status resource by its unique identifier.',
            ],
            'api_customer_statuses_ulid_put' => [
                'summary' => 'Replace a customer status',
                'description' => 'Replaces an existing customer status resource with the provided data.',
            ],
            'api_customer_statuses_ulid_delete' => [
                'summary' => 'Delete a customer status',
                'description' => 'Deletes an existing customer status resource by its unique identifier.',
            ],
            'api_customer_statuses_ulid_patch' => [
                'summary' => 'Update a customer status',
                'description' => 'Partially updates an existing customer status resource with the provided data.',
            ],
            'api_customer_types_get_collection' => [
                'summary' => 'Retrieve customer type collection',
                'description' => 'Retrieves a paginated collection of customer types with optional filtering and sorting capabilities.',
            ],
            'api_customer_types_post' => [
                'summary' => 'Create a new customer type',
                'description' => 'Creates a new customer type resource with the provided data.',
            ],
            'api_customer_types_ulid_get' => [
                'summary' => 'Retrieve a customer type',
                'description' => 'Retrieves a single customer type resource by its unique identifier.',
            ],
            'api_customer_types_ulid_put' => [
                'summary' => 'Replace a customer type',
                'description' => 'Replaces an existing customer type resource with the provided data.',
            ],
            'api_customer_types_ulid_delete' => [
                'summary' => 'Delete a customer type',
                'description' => 'Deletes an existing customer type resource by its unique identifier.',
            ],
            'api_customer_types_ulid_patch' => [
                'summary' => 'Update a customer type',
                'description' => 'Partially updates an existing customer type resource with the provided data.',
            ],
            'api_health_get' => [
                'summary' => 'Health check endpoint',
                'description' => 'Returns the health status of the API service for monitoring purposes.',
            ],
        ];
    }

    /**
     * @param array<string, array<string, string>> $metadata
     */
    private function augmentPathItem(PathItem $pathItem, array $metadata): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->augmentOperation(
                    $pathItem->{'get' . $operation}(),
                    $metadata
                )
            );
        }

        return $pathItem;
    }

    /**
     * @param array<string, array<string, string>> $metadata
     */
    private function augmentOperation(?Operation $operation, array $metadata): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        $operationId = $operation->getOperationId();
        if ($operationId === null || !isset($metadata[$operationId])) {
            return $operation;
        }

        $meta = $metadata[$operationId];
        $summary = $operation->getSummary();
        $description = $operation->getDescription();

        $updatedOperation = $operation;

        if (($summary === null || $summary === '') && isset($meta['summary'])) {
            $updatedOperation = $updatedOperation->withSummary($meta['summary']);
        }

        if (($description === null || $description === '') && isset($meta['description'])) {
            $updatedOperation = $updatedOperation->withDescription($meta['description']);
        }

        return $updatedOperation;
    }
}
