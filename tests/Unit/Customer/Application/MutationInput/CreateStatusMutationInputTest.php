<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\CreateStatusMutationInput;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsValue(): void
    {
        $value = $this->faker->word();

        $input = new CreateStatusMutationInput($value);

        self::assertSame($value, $input->value);
    }

    public function testConstructorDefaultIsNull(): void
    {
        $input = new CreateStatusMutationInput();

        self::assertNull($input->value);
    }
}
