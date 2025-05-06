<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Core\Customer\Application\DTO\StatusCreate;
use App\Tests\Unit\UnitTestCase;

final class StatusCreateTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();
        $dto = new StatusCreate($value);

        $this->assertEquals($value, $dto->value);
    }
}
