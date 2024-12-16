<?php

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Factory\Event\CustomerCreatedEventFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CreateCustomerTransformer $transformer,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private CustomerCreatedEventFactoryInterface $customerCreatedEventFactory,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = $this->transformer->transformToCustomer($command);

        $this->customerRepository->save($customer);
        $command->setResponse(new CreateCustomerCommandResponse($customer));
    }
}