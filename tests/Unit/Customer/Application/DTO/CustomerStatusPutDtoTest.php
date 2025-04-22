<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\StatusPutDto;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusPutDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $dto = new StatusPutDto($value);

        $this->assertDto($dto, $value);
    }

    private function assertDto(StatusPutDto $dto, string $value): void
    {
        $this->assertEquals($value, $dto->value);
    }
}
