<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\CustomerPut;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerPut, Customer>
 */
final readonly class CustomerPutProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerCommandFactoryInterface $updateCommandFactory,
        private CustomerRelationTransformerInterface $relationTransformer,
    ) {
    }

    /**
     * @param CustomerPut $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customer = $this->retrieveCustomer($uriVariables['ulid']);
        $customerType = $this->resolveCustomerType($data, $customer);
        $customerStatus = $this->resolveCustomerStatus($data, $customer);
        $this->executeUpdateCommand(
            $customer,
            $data,
            $customerType,
            $customerStatus
        );
        return $customer;
    }

    private function retrieveCustomer(string $customerId): Customer
    {
        $customer = $this->customerRepository->findFresh($customerId);
        if (! $customer instanceof Customer) {
            throw new CustomerNotFoundException();
        }

        return $customer;
    }

    private function executeUpdateCommand(
        Customer $customer,
        CustomerPut $data,
        object $customerType,
        object $customerStatus
    ): void {
        $customerUpdate = new CustomerUpdate(
            $data->initials,
            $data->email,
            $data->phone,
            $data->leadSource,
            $customerType,
            $customerStatus,
            $data->confirmed
        );
        $command = $this->updateCommandFactory
            ->create($customer, $customerUpdate);
        $this->commandBus->dispatch($command);
    }

    private function resolveCustomerType(
        CustomerPut $data,
        Customer $customer
    ): object {
        return $this->relationTransformer->resolveType($data->type, $customer);
    }

    private function resolveCustomerStatus(
        CustomerPut $data,
        Customer $customer
    ): object {
        return $this->relationTransformer->resolveStatus($data->status, $customer);
    }
}
