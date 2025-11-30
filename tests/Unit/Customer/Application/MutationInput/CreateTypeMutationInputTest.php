<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\CreateTypeMutationInput;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsValue(): void
    {
        $value = $this->faker->word();

        $input = new CreateTypeMutationInput($value);

        self::assertSame($value, $input->value);
    }

    public function testConstructorDefaultIsNull(): void
    {
        $input = new CreateTypeMutationInput();

        self::assertNull($input->value);
    }
}
