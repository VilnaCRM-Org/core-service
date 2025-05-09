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
        $status = $this->createMock(CustomerStatus::class);

        $command = new CreateStatusCommand($status);

        $this->assertInstanceOf(CreateStatusCommand::class, $command);
        $this->assertSame($status, $command->status);
    }
}
