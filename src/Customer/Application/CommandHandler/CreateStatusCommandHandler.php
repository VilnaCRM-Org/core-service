<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Application\Command\CreateStatusCommandResponse;
use App\Customer\Application\Transformer\CreateStatusTransformer;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
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
        $customerStatus = $this->transformer->transformToStatus($command);
        $this->repository->save($customerStatus);
        $command->setResponse(new CreateStatusCommandResponse($customerStatus));
    }
}
