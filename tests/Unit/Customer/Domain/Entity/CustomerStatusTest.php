<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Entity;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusTest extends UnitTestCase
{
    public function testGetValueAndGetUlid(): void
    {
        $expectedValue = $this->faker->word();
        $expectedUlid = $this->faker->ulid();

        $ulidTransformer = new UlidTransformer(new UlidFactory());
        $ulid = $ulidTransformer->transformFromSymfonyUlid($expectedUlid);

        $customerStatus = new CustomerStatus($expectedValue, $ulid);

        $this->assertSame($expectedValue, $customerStatus->getValue());
        $this->assertSame((string) $expectedUlid, $customerStatus->getUlid());
    }

    public function testSetValue(): void
    {
        $initialValue = $this->faker->word();
        $newValue = $this->faker->word();

        $ulid = $this->createMock(Ulid::class);
        $customerStatus = new CustomerStatus($initialValue, $ulid);

        $this->assertSame($initialValue, $customerStatus->getValue());

        $customerStatus->setValue($newValue);

        $this->assertSame($newValue, $customerStatus->getValue());
    }
}
