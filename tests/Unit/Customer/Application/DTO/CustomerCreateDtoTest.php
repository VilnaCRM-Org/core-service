<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\DTO;

use App\Customer\Application\DTO\CustomerCreateDto;
use App\Tests\Unit\UnitTestCase;

final class CustomerCreateDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = $this->faker->word();
        $type = $this->faker->word();
        $status = $this->faker->word();
        $confirmed = $this->faker->boolean();
        $this->assertDto(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed
        );
    }

    private function assertDto(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        string $type,
        string $status,
        bool $confirmed
    ): void {
        $dto = new CustomerCreateDto(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed
        );
        $this->assertEquals($initials, $dto->initials);
        $this->assertEquals($email, $dto->email);
        $this->assertEquals($phone, $dto->phone);
        $this->assertEquals($leadSource, $dto->leadSource);
        $this->assertEquals($type, $dto->type);
        $this->assertEquals($status, $dto->status);
        $this->assertEquals($confirmed, $dto->confirmed);
    }
}
