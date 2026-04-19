<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Resolver\CustomerPatchUpdateResolver;
use App\Core\Customer\Application\Resolver\CustomerReferenceResolverInterface;
use App\Core\Customer\Application\Resolver\CustomerUpdateScalarResolver;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerPatchUpdateResolverTest extends UnitTestCase
{
    private CustomerReferenceResolverInterface|MockObject $referenceResolver;
    private CustomerPatchUpdateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referenceResolver = $this->createMock(
            CustomerReferenceResolverInterface::class
        );
        $this->resolver = new CustomerPatchUpdateResolver(
            new CustomerUpdateScalarResolver(),
            new CustomerRelationTransformer($this->referenceResolver)
        );
    }

    public function testBuildUsesTransformedValues(): void
    {
        $dto = new CustomerPatch(
            id: null,
            initials: 'JD',
            email: 'john@example.com',
            phone: '+100000',
            leadSource: 'Referral',
            type: '/types/alpha',
            status: '/statuses/active',
            confirmed: true,
        );

        $customer = $this->createConfiguredMock(Customer::class, [
            'getInitials' => 'OLD',
            'getEmail' => 'old@example.com',
            'getPhone' => '+999',
            'getLeadSource' => 'Legacy',
            'getType' => $this->createMock(CustomerType::class),
            'getStatus' => $this->createMock(CustomerStatus::class),
            'isConfirmed' => false,
        ]);

        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);

        $this->referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($dto->type)
            ->willReturn($type);
        $this->referenceResolver
            ->expects(self::once())
            ->method('resolveStatus')
            ->with($dto->status)
            ->willReturn($status);

        $update = $this->resolver->build($dto, $customer);

        self::assertSame('JD', $update->newInitials);
        self::assertSame('john@example.com', $update->newEmail);
        self::assertSame('+100000', $update->newPhone);
        self::assertSame('Referral', $update->newLeadSource);
        self::assertSame($type, $update->newType);
        self::assertSame($status, $update->newStatus);
        self::assertTrue($update->newConfirmed);
    }

    public function testBuildPreservesExistingRelationsWhenReferencesAreOmitted(): void
    {
        $dto = new CustomerPatch(
            id: null,
            initials: null,
            email: null,
            phone: null,
            leadSource: null,
            type: null,
            status: null,
            confirmed: null,
        );

        $type = $this->createConfiguredMock(CustomerType::class, [
            'getUlid' => (string) $this->faker->ulid(),
        ]);
        $status = $this->createConfiguredMock(CustomerStatus::class, [
            'getUlid' => (string) $this->faker->ulid(),
        ]);
        $customer = $this->createConfiguredMock(Customer::class, [
            'getInitials' => 'OLD',
            'getEmail' => 'old@example.com',
            'getPhone' => '+999',
            'getLeadSource' => 'Legacy',
            'getType' => $type,
            'getStatus' => $status,
            'isConfirmed' => true,
        ]);

        $this->referenceResolver
            ->expects(self::never())
            ->method('resolveType');
        $this->referenceResolver
            ->expects(self::never())
            ->method('resolveStatus');

        $update = $this->resolver->build($dto, $customer);

        self::assertSame($type, $update->newType);
        self::assertSame($status, $update->newStatus);
    }

    public function testBuildThrowsWhenTypeIriIsInvalid(): void
    {
        $dto = new CustomerPatch(
            id: null,
            initials: null,
            email: null,
            phone: null,
            leadSource: null,
            type: '/types/broken',
            status: null,
            confirmed: null,
        );

        $customer = $this->createConfiguredMock(Customer::class, [
            'getInitials' => 'OLD',
            'getEmail' => 'old@example.com',
            'getPhone' => '+999',
            'getLeadSource' => 'Legacy',
            'getType' => $this->createMock(CustomerType::class),
            'getStatus' => $this->createMock(CustomerStatus::class),
            'isConfirmed' => true,
        ]);

        $this->referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($dto->type)
            ->willThrowException(CustomerTypeNotFoundException::withIri($dto->type));

        $this->expectException(CustomerTypeNotFoundException::class);

        $this->resolver->build($dto, $customer);
    }
}
