<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

abstract class UnitTestCase extends TestCase
{
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    protected function generateUlid(): Ulid
    {
        return $this->faker->ulid();
    }

    protected function generateUlidString(): string
    {
        return (string) $this->faker->ulid();
    }
}
