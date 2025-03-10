<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateStatusCommandResponse;
use App\Customer\Application\Transformer\CreateStatusTransformer;
use App\Customer\Domain\Repository\CustomerStatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerStatusCommand;

final class CreateStatusCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateStatusTransformer $transformer,
        private CustomerStatusRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerStatusCommand $command): void
    {
        $customerStatus = $this->transformer->transformToStatus($command);
        $this->repository->save($customerStatus);
        $command->setResponse(new CreateStatusCommandResponse($customerStatus));
    }
}
