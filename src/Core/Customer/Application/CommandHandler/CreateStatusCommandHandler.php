<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\Command\CreateStatusCommandResponse;
use App\Core\Customer\Application\Transformer\CreateStatusTransformer;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final class CreateStatusCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateStatusTransformer $transformer,
        private StatusRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateStatusCommand $command): void
    {
        $customerStatus = $this->transformer->transform($command);
        $this->repository->save($customerStatus);
        $command->setResponse(new CreateStatusCommandResponse($customerStatus));
    }
}
