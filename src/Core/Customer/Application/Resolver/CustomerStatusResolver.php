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
        /** @var CustomerStatus|null $existing */
        $existing = $context['previous_data'] ?? null;

        if ($existing instanceof CustomerStatus) {
            return $existing;
        }

        return $this->resolveFromIri($data->id, $context, $operation);
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function fetchResource(
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

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function resolveFromIri(
        ?string $iri,
        array $context,
        Operation $operation
    ): CustomerStatus {
        $resource = $this->fetchResource(
            $iri ?? throw new CustomerStatusNotFoundException(),
            $context,
            $operation
        );

        return $resource instanceof CustomerStatus
            ? $resource
            : throw CustomerStatusNotFoundException::withIri($iri);
    }
}
