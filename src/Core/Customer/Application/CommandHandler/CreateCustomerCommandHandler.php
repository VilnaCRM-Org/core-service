<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Core\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
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
