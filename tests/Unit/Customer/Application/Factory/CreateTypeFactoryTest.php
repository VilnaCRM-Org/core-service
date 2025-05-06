<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\Factory\CreateTypeFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeFactoryTest extends UnitTestCase
{
    public function testCreateTypeCommand(): void
    {
        $expectedValue = $this->faker->word();

        $factory = new CreateTypeFactory();
        $command = $factory->create($expectedValue);

        $this->assertInstanceOf(CreateTypeCommand::class, $command);
    }
}
