<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Core\Customer\Application\DTO\StatusPut;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusPutTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $dto = new StatusPut($value);

        $this->assertDto($dto, $value);
    }

    private function assertDto(StatusPut $dto, string $value): void
    {
        $this->assertEquals($value, $dto->value);
    }
}
