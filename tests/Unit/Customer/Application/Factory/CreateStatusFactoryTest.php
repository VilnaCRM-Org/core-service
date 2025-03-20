<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Application\Factory\CreateStatusFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusFactoryTest extends UnitTestCase
{
    public function testCreateStatusCommand(): void
    {
        $expectedValue = $this->faker->word();

        $factory = new CreateStatusFactory();
        $command = $factory->create($expectedValue);

        $this->assertInstanceOf(CreateStatusCommand::class, $command);
    }
}
