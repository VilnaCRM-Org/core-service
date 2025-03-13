<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Customer\Application\Command\CreateTypeCommand;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $value = $this->faker->word();
        $command = new CreateTypeCommand($value);

        $this->assertInstanceOf(CreateTypeCommand::class, $command);
        $this->assertSame($value, $command->value);
    }

    public function testSetAndGetResponse(): void
    {
        $value = $this->faker->word();
        $command = new CreateTypeCommand($value);

        $response = $this->createMock(CommandResponseInterface::class);
        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
    }
}
