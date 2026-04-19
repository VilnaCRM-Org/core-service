<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Resolver\CustomerReferenceResolverInterface;
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
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
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

    public function testResolveTypeWithNullIriReturnsExistingRelation(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $existingType = $this->createMock(CustomerType::class);

        $customer->method('getType')->willReturn($existingType);
        $referenceResolver
            ->expects(self::never())
            ->method('resolveType');

        $result = $resolver->resolveType(null, $customer);

        self::assertSame($existingType, $result);
    }

    public function testResolveTypeWithExistingUlidSkipsResolver(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $typeUlid = (string) $this->faker->ulid();
        $customer = $this->createConfiguredMock(Customer::class, [
            'getType' => $this->createConfiguredMock(CustomerType::class, [
                'getUlid' => $typeUlid,
            ]),
        ]);

        $referenceResolver
            ->expects(self::never())
            ->method('resolveType');

        $result = $resolver->resolveType('/api/customer_types/' . $typeUlid, $customer);

        self::assertSame($customer->getType(), $result);
    }

    public function testResolveTypeThrowsWhenResolverFails(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
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
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
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

    public function testResolveStatusWithNullIriReturnsExistingRelation(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $customer = $this->createMock(Customer::class);
        $existingStatus = $this->createMock(CustomerStatus::class);

        $customer->method('getStatus')->willReturn($existingStatus);
        $referenceResolver
            ->expects(self::never())
            ->method('resolveStatus');

        $result = $resolver->resolveStatus(null, $customer);

        self::assertSame($existingStatus, $result);
    }

    public function testResolveStatusWithExistingUlidSkipsResolver(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
        $resolver = new CustomerRelationTransformer($referenceResolver);

        $statusUlid = (string) $this->faker->ulid();
        $customer = $this->createConfiguredMock(Customer::class, [
            'getStatus' => $this->createConfiguredMock(CustomerStatus::class, [
                'getUlid' => $statusUlid,
            ]),
        ]);

        $referenceResolver
            ->expects(self::never())
            ->method('resolveStatus');

        $result = $resolver->resolveStatus($statusUlid, $customer);

        self::assertSame($customer->getStatus(), $result);
    }

    public function testResolveStatusThrowsWhenResolverFails(): void
    {
        $referenceResolver = $this->createMock(CustomerReferenceResolverInterface::class);
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
