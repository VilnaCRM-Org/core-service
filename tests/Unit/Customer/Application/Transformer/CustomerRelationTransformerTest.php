<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Resolver\CustomerReferenceResolver;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Tests\Unit\UnitTestCase;

final class CustomerRelationTransformerTest extends UnitTestCase
{
    public function testResolveTypeWithProvidedIri(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $customerType = $this->createMock(CustomerType::class);
        $typeIri = '/api/customer_types/' . $this->faker->uuid();

        $referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($typeIri)
            ->willReturn($customerType);

        $result = $resolver->resolveType($typeIri, $customer);

        self::assertSame($customerType, $result);
    }

    public function testResolveTypeWithNullIriUsesDefault(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $existingType = $this->createMock(CustomerType::class);
        $reloadedType = $this->createMock(CustomerType::class);
        $typeUlid = (string) $this->faker->ulid();

        $existingType
            ->expects(self::once())
            ->method('getUlid')
            ->willReturn($typeUlid);
        $customer->method('getType')->willReturn($existingType);
        $referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($typeUlid)
            ->willReturn($reloadedType);

        $result = $resolver->resolveType(null, $customer);

        self::assertSame($reloadedType, $result);
    }

    public function testResolveTypeThrowsWhenResolverFails(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $typeIri = '/api/customer_types/' . $this->faker->uuid();

        $referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($typeIri)
            ->willThrowException(CustomerTypeNotFoundException::withIri($typeIri));

        $this->expectException(CustomerTypeNotFoundException::class);
        $resolver->resolveType($typeIri, $customer);
    }

    public function testResolveStatusWithProvidedIri(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $statusIri = '/api/customer_statuses/' . $this->faker->uuid();

        $referenceResolver
            ->expects(self::once())
            ->method('resolveStatus')
            ->with($statusIri)
            ->willReturn($customerStatus);

        $result = $resolver->resolveStatus($statusIri, $customer);

        self::assertSame($customerStatus, $result);
    }

    public function testResolveStatusWithNullIriUsesDefault(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $existingStatus = $this->createMock(CustomerStatus::class);
        $reloadedStatus = $this->createMock(CustomerStatus::class);
        $statusUlid = (string) $this->faker->ulid();

        $existingStatus
            ->expects(self::once())
            ->method('getUlid')
            ->willReturn($statusUlid);
        $customer->method('getStatus')->willReturn($existingStatus);
        $referenceResolver
            ->expects(self::once())
            ->method('resolveStatus')
            ->with($statusUlid)
            ->willReturn($reloadedStatus);

        $result = $resolver->resolveStatus(null, $customer);

        self::assertSame($reloadedStatus, $result);
    }

    public function testResolveStatusThrowsWhenResolverFails(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $statusIri = '/api/customer_statuses/' . $this->faker->uuid();

        $referenceResolver
            ->expects(self::once())
            ->method('resolveStatus')
            ->with($statusIri)
            ->willThrowException(CustomerStatusNotFoundException::withIri($statusIri));

        $this->expectException(CustomerStatusNotFoundException::class);
        $resolver->resolveStatus($statusIri, $customer);
    }
}
