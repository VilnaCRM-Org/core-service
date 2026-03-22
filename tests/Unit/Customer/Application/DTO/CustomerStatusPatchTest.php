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
        $id = $this->faker->uuid();

        $dto = new StatusPatch(value: $value, id: $id);

        $this->assertDto($dto, $value, $id);
    }

    public function testConstructWithNullValue(): void
    {
        $dto = new StatusPatch(value: null, id: null);

        $this->assertDto($dto, null, null);
    }

    private function assertDto(
        StatusPatch $dto,
        ?string $value,
        ?string $id
    ): void {
        $this->assertEquals($value, $dto->value);
        $this->assertSame($id, $dto->id);
    }
}
