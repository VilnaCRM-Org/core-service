<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\DTO\StatusPatch;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;

final readonly class CustomerStatusResolver
{
    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    public function resolve(
        StatusPatch $data,
        array $context,
        Operation $operation
    ): CustomerStatus {
        $previous = $context['previous_data'] ?? null;

        // Pattern: Early return for cached value
        if ($previous instanceof CustomerStatus) {
            return $previous;
        }

        // Pattern: Null coalescing with throw expression (PHP 8.0+)
        $iri = $data->id ?? throw new CustomerStatusNotFoundException();

        try {
            $resource = $this->iriConverter->getResourceFromIri($iri, $context, $operation);
        } catch (ItemNotFoundException) {
            throw CustomerStatusNotFoundException::withIri($iri);
        }

        return $resource instanceof CustomerStatus
            ? $resource
            : throw CustomerStatusNotFoundException::withIri($iri);
    }
}
