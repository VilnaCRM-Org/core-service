<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Entity;

use App\Customer\Domain\Entity\CustomerType;
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
        $this->assertSame((string)$expectedUlid, $customerType->getUlid());
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

        $this->assertSame((string)$newUlidString, $customerType->getUlid());
    }
}
