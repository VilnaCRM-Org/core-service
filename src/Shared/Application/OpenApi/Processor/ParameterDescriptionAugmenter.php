<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class ParameterDescriptionAugmenter
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

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
     * @return array<string, string>
     */
    private function getParameterDescriptions(): array
    {
        return array_merge(
            $this->getOrderDescriptions(),
            $this->getFilterDescriptions(),
            $this->getDateFilterDescriptions(),
            $this->getUlidFilterDescriptions(),
            $this->getPaginationDescriptions()
        );
    }

    /**
     * @return array<string, string>
     */
    private function getOrderDescriptions(): array
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
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getFilterDescriptions(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getDateFilterDescriptions(): array
    {
        return [
            'createdAt[before]' => 'Filter customers created before this date',
            'createdAt[strictly_before]' => 'Filter customers created strictly before this date',
            'createdAt[after]' => 'Filter customers created after this date',
            'createdAt[strictly_after]' => 'Filter customers created strictly after this date',
            'updatedAt[before]' => 'Filter customers updated before this date',
            'updatedAt[strictly_before]' => 'Filter customers updated strictly before this date',
            'updatedAt[after]' => 'Filter customers updated after this date',
            'updatedAt[strictly_after]' => 'Filter customers updated strictly after this date',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getUlidFilterDescriptions(): array
    {
        return [
            'ulid[between]' => 'Filter by ULID range (comma-separated start and end)',
            'ulid[gt]' => 'Filter by ULID greater than',
            'ulid[gte]' => 'Filter by ULID greater than or equal to',
            'ulid[lt]' => 'Filter by ULID less than',
            'ulid[lte]' => 'Filter by ULID less than or equal to',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getPaginationDescriptions(): array
    {
        return [
            'page' => 'Page number for pagination',
            'itemsPerPage' => 'Number of items per page',
        ];
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentPathItem(PathItem $pathItem, array $descriptions): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->augmentOperation(
                    $pathItem->{'get' . $operation}(),
                    $descriptions
                )
            );
        }

        return $pathItem;
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentOperation(?Operation $operation, array $descriptions): ?Operation
    {
        return match (true) {
            $operation === null => null,
            $operation->getParameters() === [] => $operation,
            default => $operation->withParameters(
                $this->augmentParameters($operation->getParameters(), $descriptions)
            ),
        };
    }

    /**
     * @param array<mixed> $parameters
     * @param array<string, string> $descriptions
     *
     * @return array<mixed>
     */
    private function augmentParameters(array $parameters, array $descriptions): array
    {
        return array_map(
            static fn ($parameter) => self::augmentParameter($parameter, $descriptions),
            $parameters
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private static function augmentParameter(mixed $parameter, array $descriptions): mixed
    {
        $paramName = $parameter->getName();
        $description = $parameter->getDescription();
        $hasDescription = $description !== null && $description !== '';

        return match (true) {
            !isset($descriptions[$paramName]) => $parameter,
            $hasDescription => $parameter,
            default => $parameter->withDescription($descriptions[$paramName]),
        };
    }
}
