<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\ValueObject;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Infrastructure\Factory\UlidFactory as UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CustomerUpdateTest extends UnitTestCase
{
    private UlidFactory $ulidFactory;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ulidFactory = new UlidFactory();
        $this->ulidTransformer = new UlidTransformer(
            new UlidFactoryInterface()
        );
    }

    public function testCreateCustomerUpdate(): void
    {
        $typeUlid = $this->ulidTransformer->transformFromSymfonyUlid(
            $this->ulidFactory->create()
        );
        $statusUlid = $this->ulidTransformer->transformFromSymfonyUlid(
            $this->ulidFactory->create()
        );

        $customerType = new CustomerType('individual', $typeUlid);
        $customerStatus = new CustomerStatus('active', $statusUlid);

        $update = new CustomerUpdate(
            newInitials: 'John Doe',
            newEmail: 'john@example.com',
            newPhone: '+1234567890',
            newLeadSource: 'website',
            newType: $customerType,
            newStatus: $customerStatus,
            newConfirmed: true,
        );

        $this->assertEquals('John Doe', $update->newInitials);
        $this->assertEquals('john@example.com', $update->newEmail);
        $this->assertEquals('+1234567890', $update->newPhone);
        $this->assertEquals('website', $update->newLeadSource);
        $this->assertSame($customerType, $update->newType);
        $this->assertSame($customerStatus, $update->newStatus);
        $this->assertTrue($update->newConfirmed);
    }
}
