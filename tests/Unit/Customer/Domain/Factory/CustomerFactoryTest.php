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
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $ulid = $this->createMockUlid();

        $factory = new CustomerFactory();

        $customer = $factory->create(
            $this->faker->name(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->name(),
            $type,
            $status,
            true,
            $ulid
        );

        $this->assertInstanceOf(Customer::class, $customer);
    }

    private function createMockUlid(): UlidInterface
    {
        return new class() implements UlidInterface {
            public function __toString(): string
            {
                return '01JKX8XGHVDZ46MWYMZT94YER4';
            }
        };
    }
}
