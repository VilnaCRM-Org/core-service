<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\CustomerStatusPutDto;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusPutDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $dto = new CustomerStatusPutDto($value);

        $this->assertDto($dto, $value);
    }

    private function assertDto(CustomerStatusPutDto $dto, string $value): void
    {
        $this->assertEquals($value, $dto->value);
    }
}
