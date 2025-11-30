<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<CustomerPatch, Customer>
 */
final readonly class CustomerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerCommandFactoryInterface $commandFactory,
        private IriConverterInterface $iriConverter,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param CustomerPatch       $data
     * @param array<string,string>   $context
     * @param array<string,string>   $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customer = $this->retrieveCustomer($data, $uriVariables);
        $customerUpdate = $this->prepareCustomerUpdate($data, $customer);
        $this->dispatchUpdateCommand($customer, $customerUpdate);

        return $customer;
    }

    /**
     * @param array<string,string> $uriVariables
     */
    private function retrieveCustomer(CustomerPatch $data, array $uriVariables): Customer
    {
        $ulidString = $this->extractUlid($data, $uriVariables);
        $ulid = $this->ulidTransformer->create($ulidString);

        return $this->repository->find($ulid)
            ?? throw new CustomerNotFoundException();
    }

    /**
     * @param array<string,string> $uriVariables
     */
    private function extractUlid(CustomerPatch $data, array $uriVariables): string
    {
        if (isset($uriVariables['ulid'])) {
            return $uriVariables['ulid'];
        }

        if ($data->id !== null) {
            return basename($data->id);
        }

        throw new CustomerNotFoundException();
    }

    private function prepareCustomerUpdate(
        CustomerPatch $data,
        Customer $customer
    ): CustomerUpdate {
        return new CustomerUpdate(
            newInitials: $data->initials ?? $customer->getInitials(),
            newEmail: $data->email ?? $customer->getEmail(),
            newPhone: $data->phone ?? $customer->getPhone(),
            newLeadSource: $data->leadSource ?? $customer->getLeadSource(),
            newType: $data->type
                ? $this->iriConverter->getResourceFromIri($data->type)
                : $customer->getType(),
            newStatus: $data->status
                ? $this->iriConverter->getResourceFromIri($data->status)
                : $customer->getStatus(),
            newConfirmed: $data->confirmed ?? $customer->isConfirmed()
        );
    }

    private function dispatchUpdateCommand(
        Customer $customer,
        CustomerUpdate $update
    ): void {
        $this->commandBus->dispatch(
            $this->commandFactory->create($customer, $update)
        );
    }
}
