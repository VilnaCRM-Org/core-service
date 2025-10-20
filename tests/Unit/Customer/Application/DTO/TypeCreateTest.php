<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Core\Customer\Application\DTO\TypeCreate;
use App\Tests\Unit\UnitTestCase;

final class TypeCreateTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();
        $dto = new TypeCreate($value);

        $this->assertEquals($value, $dto->value);
    }
}
