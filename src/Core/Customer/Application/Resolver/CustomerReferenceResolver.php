<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\Metadata\Exception\InvalidArgumentException as ApiPlatformInvalidArgumentException;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Application\Transformer\IriTransformerInterface;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class CustomerReferenceResolver
{
    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private StatusRepositoryInterface $statusRepository,
        private IriTransformerInterface $iriTransformer,
    ) {
    }

    public function resolveType(string $idOrIri): CustomerType
    {
        return $this->resolveReference(
            $idOrIri,
            $this->typeRepository->find(...),
            CustomerType::class,
            CustomerTypeNotFoundException::withIri(...)
        );
    }

    public function resolveStatus(string $idOrIri): CustomerStatus
    {
        return $this->resolveReference(
            $idOrIri,
            $this->statusRepository->find(...),
            CustomerStatus::class,
            CustomerStatusNotFoundException::withIri(...)
        );
    }

    /**
     * @template T of object
     *
     * @param callable(string): ?object $finder
     * @param class-string<T> $expectedClass
     * @param callable(string): \RuntimeException $exceptionFactory
     *
     * @return T
     */
    private function resolveReference(
        string $idOrIri,
        callable $finder,
        string $expectedClass,
        callable $exceptionFactory
    ): object {
        $identifier = $this->resolveIdentifier($idOrIri);
        $resource = $finder($identifier);

        if (! $resource instanceof $expectedClass) {
            throw $exceptionFactory($idOrIri);
        }

        return $resource;
    }

    private function resolveIdentifier(string $idOrIri): string
    {
        return match (true) {
            $this->isIri($idOrIri) => $this->iriTransformer->transform($idOrIri),
            SymfonyUlid::isValid($idOrIri) => $idOrIri,
            default => throw new ApiPlatformInvalidArgumentException(
                sprintf('No route matches "%s".', $idOrIri)
            ),
        };
    }

    private function isIri(string $idOrIri): bool
    {
        return str_starts_with($idOrIri, '/')
            || filter_var($idOrIri, FILTER_VALIDATE_URL) !== false;
    }
}
