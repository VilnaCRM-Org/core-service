<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Factory;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactory;
use App\Shared\Domain\ValueObject\UlidInterface;
use App\Tests\Unit\UnitTestCase;

final class CustomerFactoryTest extends UnitTestCase
{
    public function testCreateReturnsCustomerInstance(): void
    {
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = $this->faker->name();
        $confirmed = true;

        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $ulid = $this->createMock(UlidInterface::class);

        $factory = new CustomerFactory();

        $customer = $factory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $ulid
        );

        $this->assertInstanceOf(Customer::class, $customer);
    }
}
