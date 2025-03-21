<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Factory\StatusFactory;
use App\Shared\Domain\ValueObject\UlidInterface;
use App\Tests\Unit\UnitTestCase;

final class StatusFactoryTest extends UnitTestCase
{
    public function testCreateReturnsCustomerStatusInstance(): void
    {
        $value = $this->faker->name();
        $ulid = $this->createMock(UlidInterface::class);

        $factory = new StatusFactory();

        $customerStatus = $factory->create($value, $ulid);

        $this->assertInstanceOf(CustomerStatus::class, $customerStatus);
    }
}
