<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\UpdateStatusMutationInput;
use App\Tests\Unit\UnitTestCase;

final class UpdateStatusMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsValue(): void
    {
        $value = $this->faker->word();

        $input = new UpdateStatusMutationInput($value);

        self::assertSame($value, $input->value);
    }

    public function testConstructorDefaultIsNull(): void
    {
        $input = new UpdateStatusMutationInput();

        self::assertNull($input->value);
    }
}
