<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\CommandHandler\CreateTypeCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateTypeCommandHandlerTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private CreateTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->handler = new CreateTypeCommandHandler($this->repository);
    }

    public function testInvokeSavesType(): void
    {
        $type = $this->createMock(CustomerType::class);
        $command = new CreateTypeCommand($type);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($type);

        ($this->handler)($command);
    }
}
