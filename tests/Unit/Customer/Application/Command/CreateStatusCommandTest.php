<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $value = $this->faker->word();
        $command = new CreateStatusCommand($value);

        $this->assertInstanceOf(CreateStatusCommand::class, $command);
        $this->assertSame($value, $command->value);
    }

    public function testSetAndGetResponse(): void
    {
        $value = $this->faker->word();
        $command = new CreateStatusCommand($value);

        $response = $this->createMock(CommandResponseInterface::class);
        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
    }
}
