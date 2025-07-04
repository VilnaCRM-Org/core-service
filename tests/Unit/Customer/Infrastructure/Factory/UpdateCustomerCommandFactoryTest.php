<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Factory;

use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactory;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerCommandFactoryTest extends UnitTestCase
{
    private UpdateCustomerCommandFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new UpdateCustomerCommandFactory();
    }

    public function testCreate(): void
    {
        $customer = $this->createMock(Customer::class);
        $updateData = $this->createMock(CustomerUpdate::class);

        $command = $this->factory->create($customer, $updateData);

        $this->assertNotNull($command);
    }
}
