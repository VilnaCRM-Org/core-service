<?php

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\PutCustomerCommand;
use App\Customer\Application\Command\PutCustomerCommandResponse;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

class PutCustomerCommandHandler implements CommandHandlerInterface
{

    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CreateCustomerTransformer   $transformer
    )
    {
    }


    public function __invoke(PutCustomerCommand $command): void
    {
        $customer = $this->customerRepository->find($command->customerId);

        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found.');
        }
        $customer->setInitials($command->initials ?? '');
        $customer->setEmail($command->email ?? '');
        $customer->setPhone($command->phone ?? '');
        $customer->setLeadSource($command->leadSource ?? '');
        $customer->setType($command->type ?? '');
        $customer->setStatus($command->status ?? '');

        $this->customerRepository->save($customer);

        $response = new PutCustomerCommandResponse($customer);
        $command->setResponse($response);
    }
}
