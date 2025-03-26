<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\ValueObject;

use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Tests\Unit\UnitTestCase;
use Faker\Factory;

final class CustomerStatusUpdateTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $faker = Factory::create();
        $value = $faker->word();

        $update = new CustomerStatusUpdate($value);

        $this->assertEquals($value, $update->value);
    }
}
