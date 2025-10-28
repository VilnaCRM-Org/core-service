<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use ApiPlatform\Metadata\IriConverterInterface;
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
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $customerType = $this->createMock(CustomerType::class);
        $typeIri = '/api/customer_types/' . $this->faker->uuid();

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($typeIri)
            ->willReturn($customerType);

        $result = $resolver->resolveType($typeIri, $customer);

        self::assertSame($customerType, $result);
    }

    public function testResolveTypeWithNullIriUsesDefault(): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $customerType = $this->createMock(CustomerType::class);
        $typeUlid = $this->faker->uuid();

        $existingType = $this->createMock(CustomerType::class);
        $existingType->method('getUlid')->willReturn($typeUlid);
        $customer->method('getType')->willReturn($existingType);

        $expectedIri = '/api/customer_types/' . $typeUlid;

        $iriConverter
            ->expects(self::once())
            ->method('getIriFromResource')
            ->with($existingType)
            ->willReturn($expectedIri);

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($expectedIri)
            ->willReturn($customerType);

        $result = $resolver->resolveType(null, $customer);

        self::assertSame($customerType, $result);
    }

    public function testResolveTypeThrowsWhenInvalidResourceReturned(): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $typeIri = '/api/customer_types/' . $this->faker->uuid();

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($typeIri)
            ->willReturn(new \stdClass());

        $this->expectException(CustomerTypeNotFoundException::class);
        $resolver->resolveType($typeIri, $customer);
    }

    public function testResolveStatusWithProvidedIri(): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $statusIri = '/api/customer_statuses/' . $this->faker->uuid();

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($statusIri)
            ->willReturn($customerStatus);

        $result = $resolver->resolveStatus($statusIri, $customer);

        self::assertSame($customerStatus, $result);
    }

    public function testResolveStatusWithNullIriUsesDefault(): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $statusUlid = $this->faker->uuid();

        $existingStatus = $this->createMock(CustomerStatus::class);
        $existingStatus->method('getUlid')->willReturn($statusUlid);
        $customer->method('getStatus')->willReturn($existingStatus);

        $expectedIri = '/api/customer_statuses/' . $statusUlid;

        $iriConverter
            ->expects(self::once())
            ->method('getIriFromResource')
            ->with($existingStatus)
            ->willReturn($expectedIri);

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($expectedIri)
            ->willReturn($customerStatus);

        $result = $resolver->resolveStatus(null, $customer);

        self::assertSame($customerStatus, $result);
    }

    public function testResolveStatusThrowsWhenInvalidResourceReturned(): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $resolver = new CustomerRelationTransformer($iriConverter);

        $customer = $this->createMock(Customer::class);
        $statusIri = '/api/customer_statuses/' . $this->faker->uuid();

        $iriConverter
            ->expects(self::once())
            ->method('getResourceFromIri')
            ->with($statusIri)
            ->willReturn(new \stdClass());

        $this->expectException(CustomerStatusNotFoundException::class);
        $resolver->resolveStatus($statusIri, $customer);
    }
}
