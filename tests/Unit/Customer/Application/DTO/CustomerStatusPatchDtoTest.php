<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\CustomerStatusPatchDto;
use App\Tests\Unit\UnitTestCase;
use Faker\Factory;

final class CustomerStatusPatchDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $faker = Factory::create();
        $value = $faker->word();

        $dto = new CustomerStatusPatchDto($value);

        $this->assertDto($dto, $value);
    }

    public function testConstructWithNullValue(): void
    {
        $dto = new CustomerStatusPatchDto(null);

        $this->assertDto($dto, null);
    }

    private function assertDto(
        CustomerStatusPatchDto $dto,
        ?string $value
    ): void {
        $this->assertEquals($value, $dto->value);
    }
}
