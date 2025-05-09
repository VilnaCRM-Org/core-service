<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\Factory\CreateTypeFactory;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeFactoryTest extends UnitTestCase
{
    public function testCreateTypeCommand(): void
    {
        // Arrange: mock a CustomerType instance
        /** @var CustomerType&MockObject $type */
        $type = $this->createMock(CustomerType::class);

        // Act: invoke factory with the CustomerType
        $factory = new CreateTypeFactory();
        $command = $factory->create($type);

        // Assert: it returns a command wrapping the same CustomerType
        $this->assertInstanceOf(CreateTypeCommand::class, $command);
        $this->assertSame($type, $command->type);
    }
}
