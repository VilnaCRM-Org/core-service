<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;

final readonly class CustomerRelationTransformer implements
    CustomerRelationTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function resolveType(
        ?string $typeIri,
        Customer $customer
    ): CustomerType {
        return $this->resolveRelation(
            $typeIri,
            $customer->getType(),
            CustomerType::class,
            static fn (string $iri): CustomerTypeNotFoundException => CustomerTypeNotFoundException::withIri($iri)
        );
    }

    public function resolveStatus(
        ?string $statusIri,
        Customer $customer
    ): CustomerStatus {
        return $this->resolveRelation(
            $statusIri,
            $customer->getStatus(),
            CustomerStatus::class,
            static fn (string $iri): CustomerStatusNotFoundException => CustomerStatusNotFoundException::withIri($iri)
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $expectedClass
     * @param callable(string): \Exception $exceptionFactory
     *
     * @return T
     */
    private function resolveRelation(
        ?string $iri,
        object $default,
        string $expectedClass,
        callable $exceptionFactory
    ): object {
        $resolvedIri = $iri ?? $this->iriConverter->getIriFromResource($default);
        $resource = $this->iriConverter->getResourceFromIri($resolvedIri);

        if (!$resource instanceof $expectedClass) {
            throw $exceptionFactory($resolvedIri);
        }

        return $resource;
    }
}
