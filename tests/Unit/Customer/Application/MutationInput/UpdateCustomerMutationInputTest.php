<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\UpdateCustomerMutationInput;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsProvidedValues(): void
    {
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = $this->faker->word();
        $type = $this->faker->uuid();
        $status = $this->faker->uuid();
        $confirmed = $this->faker->boolean();

        $input = new UpdateCustomerMutationInput(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed
        );

        self::assertSame($initials, $input->initials);
        self::assertSame($email, $input->email);
        self::assertSame($phone, $input->phone);
        self::assertSame($leadSource, $input->leadSource);
        self::assertSame($type, $input->type);
        self::assertSame($status, $input->status);
        self::assertSame($confirmed, $input->confirmed);
    }

    public function testConstructorDefaultsToNull(): void
    {
        $input = new UpdateCustomerMutationInput();

        self::assertNull($input->initials);
        self::assertNull($input->email);
        self::assertNull($input->phone);
        self::assertNull($input->leadSource);
        self::assertNull($input->type);
        self::assertNull($input->status);
        self::assertNull($input->confirmed);
    }
}
