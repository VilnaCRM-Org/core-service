<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\Metadata\Exception\InvalidArgumentException as ApiPlatformInvalidArgumentException;
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
        if ($previous instanceof CustomerStatus) {
            return $previous;
        }

        $iri = $data->id ?? throw new CustomerStatusNotFoundException();

        return $this->resolveFromIri($iri, $context, $operation);
    }

    /**
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    private function resolveFromIri(
        string $iri,
        array $context,
        Operation $operation
    ): CustomerStatus {
        try {
            $resource = $this->iriConverter->getResourceFromIri(
                $iri,
                $context,
                $operation
            );
        } catch (ApiPlatformInvalidArgumentException $exception) {
            if ($exception instanceof ItemNotFoundException) {
                throw CustomerStatusNotFoundException::withIri($iri);
            }

            throw $exception;
        }

        if (!$resource instanceof CustomerStatus) {
            throw CustomerStatusNotFoundException::withIri($iri);
        }

        return $resource;
    }
}
