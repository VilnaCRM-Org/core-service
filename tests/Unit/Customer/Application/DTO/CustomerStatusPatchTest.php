<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Core\Customer\Application\DTO\StatusPatch;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusPatchTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $dto = new StatusPatch($value, null);

        $this->assertDto($dto, $value);
    }

    public function testConstructWithNullValue(): void
    {
        $dto = new StatusPatch(null, null);

        $this->assertDto($dto, null);
    }

    private function assertDto(
        StatusPatch $dto,
        ?string $value
    ): void {
        $this->assertEquals($value, $dto->value);
    }
}
