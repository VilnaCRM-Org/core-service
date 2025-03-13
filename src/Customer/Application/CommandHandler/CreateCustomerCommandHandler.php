<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateCustomerTransformer $transformer,
        private CustomerRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = $this->transformer->transform($command);
        $this->repository->save($customer);
        $command->setResponse(new CreateCustomerCommandResponse($customer));
    }
}
