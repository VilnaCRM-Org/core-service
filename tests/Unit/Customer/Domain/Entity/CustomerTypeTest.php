<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Entity;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;

final class CustomerTypeTest extends UnitTestCase
{
    public function testGetValueAndGetUlid(): void
    {
        $expectedValue = $this->faker->word();
        $expectedUlid = $this->faker->ulid();

        $ulidTransformer = new UlidTransformer(new UlidFactory());
        $ulid = $ulidTransformer->transformFromSymfonyUlid($expectedUlid);

        $customerType = new CustomerType($expectedValue, $ulid);

        $this->assertSame($expectedValue, $customerType->getValue());
        $this->assertSame((string) $expectedUlid, $customerType->getUlid());
    }

    public function testSetUlid(): void
    {
        $expectedValue = $this->faker->word();
        $initialUlid = $this->faker->ulid();

        $ulidTransformer = new UlidTransformer(new UlidFactory());
        $ulid = $ulidTransformer->transformFromSymfonyUlid($initialUlid);

        $customerType = new CustomerType($expectedValue, $ulid);

        $newUlidString = $this->faker->ulid();
        $newUlid = $ulidTransformer->transformFromSymfonyUlid($newUlidString);

        $customerType->setUlid($newUlid);

        $this->assertSame((string) $newUlidString, $customerType->getUlid());
    }

    public function testSetValue(): void
    {
        $initialValue = $this->faker->word();
        $newValue = $this->faker->word();

        $ulid = $this->createMock(Ulid::class);
        $customerType = new CustomerType($initialValue, $ulid);

        $this->assertSame($initialValue, $customerType->getValue());

        $customerType->setValue($newValue);

        $this->assertSame($newValue, $customerType->getValue());
    }
}
