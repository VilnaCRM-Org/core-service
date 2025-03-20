<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Entity;

use App\Customer\Domain\Entity\CustomerStatus;
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
        $this->assertSame((string)$expectedUlid, $customerStatus->getUlid());
    }
}
