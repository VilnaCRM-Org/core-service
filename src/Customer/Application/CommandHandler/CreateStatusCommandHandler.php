<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateStatusCommandResponse;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Repository\CustomerTypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerStatusCommand;

class CreateStatusCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateCustomerTransformer $transformer,
        private CustomerTypeRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerStatusCommand $command): void
    {
        $customerStatus = $this->transformer->transformToStatus($command);
        $this->repository->save($customerStatus);
        $command->setResponse(new CreateStatusCommandResponse($customerStatus));
    }
}
