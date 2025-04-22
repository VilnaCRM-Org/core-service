<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\StatusPatchDto;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusPatchDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $dto = new StatusPatchDto($value);

        $this->assertDto($dto, $value);
    }

    public function testConstructWithNullValue(): void
    {
        $dto = new StatusPatchDto(null);

        $this->assertDto($dto, null);
    }

    private function assertDto(
        StatusPatchDto $dto,
        ?string        $value
    ): void {
        $this->assertEquals($value, $dto->value);
    }
}
