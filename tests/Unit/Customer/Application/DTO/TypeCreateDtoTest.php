<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\TypeCreateDto;
use App\Tests\Unit\UnitTestCase;

final class TypeCreateDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();
        $dto = new TypeCreateDto($value);

        $this->assertEquals($value, $dto->value);
    }
}
