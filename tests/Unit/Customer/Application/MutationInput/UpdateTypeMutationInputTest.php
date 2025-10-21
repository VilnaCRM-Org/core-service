<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\UpdateTypeMutationInput;
use App\Tests\Unit\UnitTestCase;

final class UpdateTypeMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsValue(): void
    {
        $value = $this->faker->word();

        $input = new UpdateTypeMutationInput($value);

        self::assertSame($value, $input->value);
    }

    public function testConstructorDefaultIsNull(): void
    {
        $input = new UpdateTypeMutationInput();

        self::assertNull($input->value);
    }
}
