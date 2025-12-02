<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Processor\CustomerPatchUpdateResolver;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use PHPUnit\Framework\MockObject\MockObject;
use UnexpectedValueException;
use App\Tests\Unit\UnitTestCase;

final class CustomerPatchUpdateResolverTest extends UnitTestCase
{
    private IriConverterInterface|MockObject $iriConverter;
    private CustomerPatchUpdateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->resolver = new CustomerPatchUpdateResolver($this->iriConverter);
    }

    public function testBuildUsesTransformedValues(): void
    {
        $dto = new CustomerPatch();
        $dto->initials = 'JD';
        $dto->email = 'john@example.com';
        $dto->phone = '+100000';
        $dto->leadSource = 'Referral';
        $dto->type = '/types/alpha';
        $dto->status = '/statuses/active';
        $dto->confirmed = true;

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

        $this->iriConverter
            ->method('getResourceFromIri')
            ->willReturnMap([
                [$dto->type, [], $this->createMock(\ApiPlatform\Metadata\Operation::class), $type],
                [$dto->status, [], $this->createMock(\ApiPlatform\Metadata\Operation::class), $status],
            ]);

        $update = $this->resolver->build($dto, $customer);

        self::assertSame('JD', $update->newInitials);
        self::assertSame('john@example.com', $update->newEmail);
        self::assertSame('+100000', $update->newPhone);
        self::assertSame('Referral', $update->newLeadSource);
        self::assertSame($type, $update->newType);
        self::assertSame($status, $update->newStatus);
        self::assertTrue($update->newConfirmed);
    }

    public function testBuildThrowsWhenTypeIriIsInvalid(): void
    {
        $dto = new CustomerPatch();
        $dto->type = '/types/broken';

        $customer = $this->createConfiguredMock(Customer::class, [
            'getInitials' => 'OLD',
            'getEmail' => 'old@example.com',
            'getPhone' => '+999',
            'getLeadSource' => 'Legacy',
            'getType' => $this->createMock(CustomerType::class),
            'getStatus' => $this->createMock(CustomerStatus::class),
            'isConfirmed' => true,
        ]);

        $this->iriConverter
            ->method('getResourceFromIri')
            ->willReturn($this->createMock(CustomerStatus::class));

        $this->expectException(UnexpectedValueException::class);

        $this->resolver->build($dto, $customer);
    }
}
