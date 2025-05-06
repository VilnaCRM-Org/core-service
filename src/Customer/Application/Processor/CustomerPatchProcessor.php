<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerPatch;
use App\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Exception\CustomerNotFoundException;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerUpdate;
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
        $newInitials = $this->updateInitials($data, $customer);
        $newEmail = $this->updateEmail($data, $customer);
        $newPhone = $this->updatePhone($data, $customer);
        $newLeadSource = $this->updateLeadSource($data, $customer);
        $newType = $this->updateType($data, $customer);
        $newStatus = $this->updateStatus($data, $customer);
        $newConfirmed = $data->confirmed ?? $customer->isConfirmed();

        return new CustomerUpdate(
            newInitials: $newInitials,
            newEmail: $newEmail,
            newPhone: $newPhone,
            newLeadSource: $newLeadSource,
            newType: $newType,
            newStatus: $newStatus,
            newConfirmed: $newConfirmed
        );
    }

    private function updateInitials(
        CustomerPatch $data,
        Customer $customer
    ): string {
        return $this->getNewValue(
            $data->initials,
            $customer->getInitials()
        );
    }

    private function updateEmail(
        CustomerPatch $data,
        Customer $customer
    ): string {
        return $this->getNewValue(
            $data->email,
            $customer->getEmail()
        );
    }

    private function updatePhone(
        CustomerPatch $data,
        Customer $customer
    ): string {
        return $this->getNewValue(
            $data->phone,
            $customer->getPhone()
        );
    }

    private function updateLeadSource(
        CustomerPatch $data,
        Customer $customer
    ): string {
        return $this->getNewValue(
            $data->leadSource,
            $customer->getLeadSource()
        );
    }

    private function updateType(
        CustomerPatch $data,
        Customer $customer
    ): CustomerType {
        return $data->type
            ? $this->getCustomerType($data->type)
            : $customer->getType();
    }

    private function updateStatus(
        CustomerPatch $data,
        Customer $customer
    ): CustomerStatus {
        return $data->status
            ? $this->getCustomerStatus($data->status)
            : $customer->getStatus();
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
        return strlen(trim($newValue ?? '')) > 0
            ? $newValue
            : $defaultValue;
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
