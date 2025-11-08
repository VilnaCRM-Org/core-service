<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
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
        $customer = $this->retrieveCustomer($uriVariables);
        $customerUpdate = $this->prepareCustomerUpdate($data, $customer);
        $this->dispatchUpdateCommand($customer, $customerUpdate);
        return $customer;
    }

    /**
     * @param array<string,string> $uriVariables
     */
    private function retrieveCustomer(array $uriVariables): Customer
    {
        $ulid = $uriVariables['ulid'];
        return $this->repository->find(
            $this->ulidTransformer->create($ulid)
        ) ?? throw new CustomerNotFoundException();
    }

    private function prepareCustomerUpdate(
        CustomerPatch $data,
        Customer $customer
    ): CustomerUpdate {
        return new CustomerUpdate(
            newInitials: $this->getNewValue($data->initials, $customer->getInitials()),
            newEmail: $this->getNewValue($data->email, $customer->getEmail()),
            newPhone: $this->getNewValue($data->phone, $customer->getPhone()),
            newLeadSource: $this->getNewValue($data->leadSource, $customer->getLeadSource()),
            newType: $this->updateType($data, $customer),
            newStatus: $this->updateStatus($data, $customer),
            newConfirmed: $data->confirmed ?? $customer->isConfirmed()
        );
    }

    private function updateType(
        CustomerPatch $data,
        Customer $customer
    ): CustomerType {
        if ($data->type === null) {
            return $customer->getType();
        }

        return $this->getCustomerType($data->type);
    }

    private function updateStatus(
        CustomerPatch $data,
        Customer $customer
    ): CustomerStatus {
        if ($data->status === null) {
            return $customer->getStatus();
        }

        return $this->getCustomerStatus($data->status);
    }

    private function dispatchUpdateCommand(
        Customer $customer,
        CustomerUpdate $update
    ): void {
        $this->commandBus->dispatch(
            $this->commandFactory->create($customer, $update)
        );
    }

    private function getNewValue(
        ?string $newValue,
        string $defaultValue
    ): string {
        return $this->hasValidContent($newValue) ? $newValue : $defaultValue;
    }

    private function hasValidContent(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strlen(trim($value)) > 0;
    }

    private function getCustomerType(
        string $typeIri
    ): CustomerType {
        return $this->iriConverter->getResourceFromIri($typeIri);
    }

    private function getCustomerStatus(
        string $statusIri
    ): CustomerStatus {
        return $this->iriConverter->getResourceFromIri($statusIri);
    }
}
