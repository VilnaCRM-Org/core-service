<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeCommandTest extends UnitTestCase
{
    public function testConstructorAcceptsTypeEntity(): void
    {
        // Arrange: create a mock CustomerType
        $type = $this->createMock(CustomerType::class);

        // Act: construct the command with the entity
        $command = new CreateTypeCommand($type);

        // Assert: it holds exactly that same CustomerType
        $this->assertInstanceOf(CreateTypeCommand::class, $command);
        $this->assertSame($type, $command->type);
    }
}
