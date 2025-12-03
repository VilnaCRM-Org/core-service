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
        $existing = $context['previous_data'] ?? null;

        return $existing instanceof CustomerStatus
            ? $existing
            : $this->resolveFromIri($data->id, $context, $operation);
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

    private function requireIri(?string $iri): string
    {
        return $iri ?? throw new CustomerStatusNotFoundException();
    }

    private function assertStatus(string $iri, object $resource): CustomerStatus
    {
        return $resource instanceof CustomerStatus
            ? $resource
            : throw CustomerStatusNotFoundException::withIri($iri);
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function resolveFromIri(
        ?string $iri,
        array $context,
        Operation $operation
    ): CustomerStatus {
        $resolvedIri = $this->requireIri($iri);
        $resource = $this->fetchResource($resolvedIri, $context, $operation);

        return $this->assertStatus($resolvedIri, $resource);
    }
}
