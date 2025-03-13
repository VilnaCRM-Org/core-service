<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\StatusCreateDto;
use App\Tests\Unit\UnitTestCase;

final class StatusCreateDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();
        $dto = new StatusCreateDto($value);

        $this->assertEquals($value, $dto->value);
    }
}
