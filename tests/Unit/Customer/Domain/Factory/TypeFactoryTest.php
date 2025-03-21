<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\TypeFactory;
use App\Shared\Domain\ValueObject\UlidInterface;
use App\Tests\Unit\UnitTestCase;

final class TypeFactoryTest extends UnitTestCase
{
    public function testCreateReturnsCustomerTypeInstance(): void
    {
        $value = $this->faker->name();
        $ulid = $this->createMock(UlidInterface::class);

        $factory = new TypeFactory();

        $customerType = $factory->create($value, $ulid);

        $this->assertInstanceOf(CustomerType::class, $customerType);
    }
}
