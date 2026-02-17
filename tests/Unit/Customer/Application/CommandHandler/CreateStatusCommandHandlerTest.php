<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\CommandHandler\CreateStatusCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateStatusCommandHandlerTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private CreateStatusCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->handler = new CreateStatusCommandHandler($this->repository);
    }

    public function testInvokeSavesStatus(): void
    {
        $status = $this->createMock(CustomerStatus::class);
        $command = new CreateStatusCommand($status);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($status);

        ($this->handler)($command);
    }
}
