<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class ParameterDescriptionAugmenter
{
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

    public function augment(OpenApi $openApi): void
    {
        $parameterDescriptions = $this->getParameterDescriptions();

        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->augmentPathItem($pathItem, $parameterDescriptions)
            );
        }
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentPathItem(PathItem $pathItem, array $descriptions): PathItem
    {
        return $pathItem
            ->withGet($this->augmentOperation($pathItem->getGet(), $descriptions))
            ->withPost($this->augmentOperation($pathItem->getPost(), $descriptions))
            ->withPut($this->augmentOperation($pathItem->getPut(), $descriptions))
            ->withPatch($this->augmentOperation($pathItem->getPatch(), $descriptions))
            ->withDelete($this->augmentOperation($pathItem->getDelete(), $descriptions));
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentOperation(?Operation $operation, array $descriptions): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        $parameters = $operation->getParameters();
        if (empty($parameters)) {
            return $operation;
        }

        $updatedParameters = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            if (isset($descriptions[$paramName]) && empty($parameter->getDescription())) {
                $parameter = $parameter->withDescription($descriptions[$paramName]);
            }
            $updatedParameters[] = $parameter;
        }

        return $operation->withParameters($updatedParameters);
    }
}
