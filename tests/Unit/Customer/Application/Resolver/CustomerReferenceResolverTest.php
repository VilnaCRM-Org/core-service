<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use ApiPlatform\Metadata\Exception\InvalidArgumentException as ApiPlatformInvalidArgumentException;
use App\Core\Customer\Application\Resolver\CustomerReferenceResolver;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Application\Transformer\IriTransformerInterface;
use App\Tests\Unit\UnitTestCase;

final class CustomerReferenceResolverTest extends UnitTestCase
{
    public function testResolveTypeTransformsIriBeforeRepositoryLookup(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $input = '/api/customer_types/' . $this->faker->uuid();
        $identifier = $this->faker->uuid();
        $type = $this->createMock(CustomerType::class);

        $iriTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($identifier);
        $typeRepository
            ->expects(self::once())
            ->method('find')
            ->with($identifier)
            ->willReturn($type);

        self::assertSame($type, $resolver->resolveType($input));
    }

    public function testResolveStatusUsesRawIdentifierWhenAlreadyNormalized(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $input = (string) $this->faker->ulid();
        $status = $this->createMock(CustomerStatus::class);

        $iriTransformer
            ->expects(self::never())
            ->method('transform');
        $statusRepository
            ->expects(self::once())
            ->method('find')
            ->with($input)
            ->willReturn($status);

        self::assertSame($status, $resolver->resolveStatus($input));
    }

    public function testResolveTypeThrowsWhenRepositoryDoesNotReturnType(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $input = '/api/customer_types/' . $this->faker->uuid();
        $identifier = $this->faker->uuid();

        $iriTransformer
            ->method('transform')
            ->with($input)
            ->willReturn($identifier);
        $typeRepository
            ->method('find')
            ->with($identifier)
            ->willReturn(null);

        $this->expectException(CustomerTypeNotFoundException::class);
        $resolver->resolveType($input);
    }

    public function testResolveStatusThrowsWhenRepositoryReturnsUnexpectedObject(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $input = '/api/customer_statuses/' . $this->faker->uuid();
        $identifier = $this->faker->uuid();

        $iriTransformer
            ->method('transform')
            ->with($input)
            ->willReturn($identifier);
        $statusRepository
            ->method('find')
            ->with($identifier)
            ->willReturn(new \stdClass());

        $this->expectException(CustomerStatusNotFoundException::class);
        $resolver->resolveStatus($input);
    }

    public function testResolveTypeThrowsApiPlatformInvalidArgumentForMalformedReference(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $typeRepository
            ->expects(self::never())
            ->method('find');
        $iriTransformer
            ->expects(self::never())
            ->method('transform');

        $this->expectException(ApiPlatformInvalidArgumentException::class);
        $this->expectExceptionMessage('No route matches "invalid-iri".');

        $resolver->resolveType('invalid-iri');
    }

    public function testResolveStatusTransformsAbsoluteUrlBeforeRepositoryLookup(): void
    {
        $typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $iriTransformer = $this->createMock(IriTransformerInterface::class);
        $resolver = new CustomerReferenceResolver(
            $typeRepository,
            $statusRepository,
            $iriTransformer
        );

        $input = 'https://api.example.test/api/customer_statuses/' . $this->faker->uuid();
        $identifier = $this->faker->uuid();
        $status = $this->createMock(CustomerStatus::class);

        $iriTransformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($identifier);
        $statusRepository
            ->expects(self::once())
            ->method('find')
            ->with($identifier)
            ->willReturn($status);

        self::assertSame($status, $resolver->resolveStatus($input));
    }
}
