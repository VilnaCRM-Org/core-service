<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\ValueObject;

use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Tests\Unit\UnitTestCase;

final class CustomerStatusUpdateTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();

        $update = new CustomerStatusUpdate($value);

        $this->assertEquals($value, $update->value);
    }
}
