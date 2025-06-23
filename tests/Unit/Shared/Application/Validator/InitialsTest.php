<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;

final class InitialsTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $groups = [$this->faker->word(), $this->faker->word()];
        $payload = [$this->faker->word() => $this->faker->word()];

        $ulid = $this->faker->ulid();
        $this->assertInstanceOf(\Symfony\Component\Uid\Ulid::class, $ulid);

        $provider = new \App\Tests\Unit\UlidProvider($this->faker);
        $ulidDirect = $provider->ulid();
        $this->assertInstanceOf(
            \Symfony\Component\Uid\Ulid::class,
            $ulidDirect
        );

        $ulidObj = $this->generateUlid();
        $ulidStr = $this->generateUlidString();
        $this->assertInstanceOf(\Symfony\Component\Uid\Ulid::class, $ulidObj);
        $this->assertIsString($ulidStr);

        $constraint = new Initials($groups, $payload);

        $this->assertEquals($groups, $constraint->groups);
        $this->assertEquals($payload, $constraint->payload);
    }
}
