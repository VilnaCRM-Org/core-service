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
        $type = $this->createMock(CustomerType::class);

        $command = new CreateTypeCommand($type);

        $this->assertInstanceOf(CreateTypeCommand::class, $command);
        $this->assertSame($type, $command->type);
    }
}
