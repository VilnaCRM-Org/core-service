<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Command\SchemathesisCustomerSeeder;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class SchemathesisCustomerSeederTest extends UnitTestCase
{
    private CustomerRepositoryInterface $customerRepository;
    private UlidFactory $ulidFactory;
    private SchemathesisCustomerSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->seeder = new SchemathesisCustomerSeeder(
            $this->customerRepository,
            $this->ulidFactory
        );
    }

    public function testSeedCustomersCreatesNewCustomers(): void
    {
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $ulid = $this->createMock(Ulid::class);

        $this->customerRepository
            ->expects($this->exactly(5))
            ->method('find')
            ->willReturn(null);

        $this->ulidFactory
            ->expects($this->exactly(5))
            ->method('create')
            ->willReturn($ulid);

        $this->customerRepository
            ->expects($this->exactly(5))
            ->method('save');

        $result = $this->seeder->seedCustomers(['default' => $type], ['default' => $status]);

        $this->assertCount(5, $result);
        $this->assertArrayHasKey('primary', $result);
        $this->assertArrayHasKey('update', $result);
        $this->assertArrayHasKey('delete', $result);
        $this->assertArrayHasKey('replace', $result);
        $this->assertArrayHasKey('get', $result);
    }

    public function testSeedCustomersUpdatesExistingCustomers(): void
    {
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);

        $this->customerRepository
            ->expects($this->exactly(5))
            ->method('find')
            ->willReturn($customer);

        $customer->expects($this->exactly(5))->method('setEmail');
        $customer->expects($this->exactly(5))->method('setInitials');
        $customer->expects($this->exactly(5))->method('setPhone');
        $customer->expects($this->exactly(5))->method('setLeadSource');
        $customer->expects($this->exactly(5))->method('setType');
        $customer->expects($this->exactly(5))->method('setStatus');
        $customer->expects($this->exactly(5))->method('setConfirmed');

        $this->customerRepository
            ->expects($this->exactly(5))
            ->method('save')
            ->with($customer);

        $result = $this->seeder->seedCustomers(['default' => $type], ['default' => $status]);

        $this->assertCount(5, $result);
    }
}
