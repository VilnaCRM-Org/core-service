<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Factory;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\TypeFactory;
use App\Shared\Domain\ValueObject\UlidInterface;
use App\Tests\Unit\UnitTestCase;

final class TypeFactoryTest extends UnitTestCase
{
    public function testCreateReturnsCustomerTypeInstance(): void
    {
        $value = $this->faker->name();
        $ulid = $this->createMockUlid();

        $factory = new TypeFactory();

        $customerType = $factory->create($value, $ulid);

        $this->assertInstanceOf(CustomerType::class, $customerType);
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
