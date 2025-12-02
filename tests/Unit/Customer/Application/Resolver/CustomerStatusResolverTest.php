<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use ApiPlatform\Metadata\Exception\InvalidArgumentException as ApiPlatformInvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\DTO\StatusPatch;
use App\Core\Customer\Application\Resolver\CustomerStatusResolver;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerStatusResolverTest extends UnitTestCase
{
    private IriConverterInterface|MockObject $iriConverter;
    private CustomerStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->resolver = new CustomerStatusResolver($this->iriConverter);
    }

    public function testResolveReturnsPreviousDataFromContext(): void
    {
        $dto = new StatusPatch($this->faker->word(), null);
        $operation = $this->createMock(Operation::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $this->iriConverter->expects($this->never())->method('getResourceFromIri');

        $result = $this->resolver->resolve(
            $dto,
            ['previous_data' => $customerStatus],
            $operation
        );

        $this->assertSame($customerStatus, $result);
    }

    public function testResolveLoadsEntityFromIri(): void
    {
        $ulid = (string) $this->faker->ulid();
        $iri = sprintf('/api/customer_statuses/%s', $ulid);
        $dto = new StatusPatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $this->iriConverter
            ->expects($this->once())
            ->method('getResourceFromIri')
            ->with($iri, [], $operation)
            ->willReturn($customerStatus);

        $result = $this->resolver->resolve($dto, [], $operation);

        $this->assertSame($customerStatus, $result);
    }

    public function testResolveThrowsExceptionWhenIdMissing(): void
    {
        $dto = new StatusPatch($this->faker->word(), null);
        $operation = $this->createMock(Operation::class);

        $this->expectException(CustomerStatusNotFoundException::class);

        $this->resolver->resolve($dto, [], $operation);
    }

    public function testResolveThrowsExceptionWhenConverterFails(): void
    {
        $ulid = (string) $this->faker->ulid();
        $iri = sprintf('/api/customer_statuses/%s', $ulid);
        $dto = new StatusPatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);

        $this->iriConverter
            ->expects($this->once())
            ->method('getResourceFromIri')
            ->with($iri, [], $operation)
            ->willThrowException(new \RuntimeException());

        $this->expectException(\RuntimeException::class);

        $this->resolver->resolve($dto, [], $operation);
    }

    public function testResolveThrowsExceptionWhenResourceIsInvalid(): void
    {
        $ulid = (string) $this->faker->ulid();
        $iri = sprintf('/api/customer_statuses/%s', $ulid);
        $dto = new StatusPatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);

        $this->iriConverter
            ->expects($this->once())
            ->method('getResourceFromIri')
            ->with($iri, [], $operation)
            ->willReturn(new \stdClass());

        $this->expectException(CustomerStatusNotFoundException::class);

        $this->resolver->resolve($dto, [], $operation);
    }

    public function testResolveThrowsExceptionWhenItemNotFoundIsThrown(): void
    {
        $iri = sprintf('/api/customer_statuses/%s', (string) $this->faker->ulid());
        $dto = new StatusPatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);

        $this->iriConverter
            ->expects($this->once())
            ->method('getResourceFromIri')
            ->with($iri, [], $operation)
            ->willThrowException(new ItemNotFoundException());

        $this->expectException(CustomerStatusNotFoundException::class);

        $this->resolver->resolve($dto, [], $operation);
    }

    public function testResolveThrowsExceptionWhenApiPlatformInvalidArgumentIsThrown(): void
    {
        $iri = sprintf('/api/customer_statuses/%s', (string) $this->faker->ulid());
        $dto = new StatusPatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);

        $this->iriConverter
            ->expects($this->once())
            ->method('getResourceFromIri')
            ->with($iri, [], $operation)
            ->willThrowException(new ApiPlatformInvalidArgumentException());

        $this->expectException(ApiPlatformInvalidArgumentException::class);

        $this->resolver->resolve($dto, [], $operation);
    }
}
