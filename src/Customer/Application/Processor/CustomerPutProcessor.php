<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerPut;
use App\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Exception\CustomerNotFoundException;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerUpdate;
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
        private IriConverterInterface $iriConverter,
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
        $customerType = $this->convertResource($data->type);
        $customerStatus = $this->convertResource($data->status);
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
        $customer = $this->customerRepository->find($customerId);
        if (!$customer) {
            throw new CustomerNotFoundException();
        }
        return $customer;
    }

    private function convertResource(string $iri): object
    {
        return $this->iriConverter->getResourceFromIri($iri);
    }

    private function executeUpdateCommand(
        Customer    $customer,
        CustomerPut $data,
        object      $customerType,
        object      $customerStatus
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
}
