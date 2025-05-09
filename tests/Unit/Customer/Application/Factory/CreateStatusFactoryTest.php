<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\Factory\CreateStatusFactory;
use App\Core\Customer\Domain\Entity\CustomerStatusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateStatusFactoryTest extends UnitTestCase
{
    public function testCreateStatusCommand(): void
    {
        /** @var CustomerStatusInterface&MockObject $status */
        $status = $this->createMock(CustomerStatusInterface::class);

        $factory = new CreateStatusFactory();
        $command = $factory->create($status);

        $this->assertInstanceOf(CreateStatusCommand::class, $command);
        $this->assertSame($status, $command->status);
    }
}
