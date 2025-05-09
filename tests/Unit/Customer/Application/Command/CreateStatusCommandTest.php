<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusCommandTest extends UnitTestCase
{
    public function testConstructorAcceptsStatusEntity(): void
    {
        // Arrange: create a mock CustomerStatus (or use a real one if you prefer)
        $status = $this->createMock(CustomerStatus::class);

        // Act: construct the command
        $command = new CreateStatusCommand($status);

        // Assert: it holds exactly that same CustomerStatus
        $this->assertInstanceOf(CreateStatusCommand::class, $command);
        $this->assertSame($status, $command->status);
    }
}
