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

        return $previous instanceof CustomerStatus
            ? $previous
            : $this->resolveFromIri($this->requireIri($data), $context, $operation);
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function resolveFromIri(
        string $iri,
        array $context,
        Operation $operation
    ): CustomerStatus {
        $resource = $this->getResource($iri, $context, $operation);

        if (!$resource instanceof CustomerStatus) {
            throw CustomerStatusNotFoundException::withIri($iri);
        }

        return $resource;
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function getResource(
        string $iri,
        array $context,
        Operation $operation
    ): object {
        try {
            return $this->iriConverter->getResourceFromIri($iri, $context, $operation);
        } catch (ItemNotFoundException) {
            throw CustomerStatusNotFoundException::withIri($iri);
        }
    }

    private function requireIri(StatusPatch $data): string
    {
        if ($data->id === null) {
            throw new CustomerStatusNotFoundException();
        }

        return $data->id;
    }
}
