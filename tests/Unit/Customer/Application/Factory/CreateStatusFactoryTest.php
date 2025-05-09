<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\Factory\CreateStatusFactory;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateStatusFactoryTest extends UnitTestCase
{
    public function testCreateStatusCommand(): void
    {
        // Arrange: mock a CustomerStatus instance
        /** @var CustomerStatus&MockObject $status */
        $status = $this->createMock(CustomerStatus::class);

        // Act: invoke factory with the CustomerStatus
        $factory = new CreateStatusFactory();
        $command = $factory->create($status);

        // Assert: it returns a command wrapping the same CustomerStatus
        $this->assertInstanceOf(CreateStatusCommand::class, $command);
        $this->assertSame($status, $command->status);
    }
}
