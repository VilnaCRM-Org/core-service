<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerPatchDto;
use App\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Exception\CustomerNotFoundException;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;

/**
 * @implements ProcessorInterface<CustomerPatchDto, Customer>
 */
final readonly class CustomerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerCommandFactoryInterface $updateCustomerCommandFactory,
        private IriConverterInterface $iriConverter,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param CustomerPatchDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $ulid = $uriVariables['ulid'];
        $customer = $this->repository->find(
            $this->ulidTransformer->create($ulid)
        ) ?? throw new CustomerNotFoundException();

        $newInitials = $this->getNewValue($data->initials, $customer
            ->getInitials());
        $newEmail = $this->getNewValue($data->email, $customer->getEmail());
        $newPhone = $this->getNewValue($data->phone, $customer->getPhone());
        $newLeadSource = $this
            ->getNewValue($data->leadSource, $customer->getLeadSource());
        $newType = $data->type ? $this
            ->getCustomerType($data->type) : $customer->getType();
        $newStatus = $data->status ? $this
            ->getCustomerStatus($data->status) : $customer->getStatus();
        $newConfirmed = $data->confirmed ?? $customer->isConfirmed();

        $this->dispatchCommand(
            $customer,
            $newInitials,
            $newEmail,
            $newPhone,
            $newLeadSource,
            $newType,
            $newStatus,
            $newConfirmed
        );

        return $customer;
    }

    private function getNewValue(?string $newValue, string $defaultValue): string
    {
        return strlen(trim($newValue ?? '')) > 0 ? $newValue : $defaultValue;
    }

    private function getCustomerType(string $typeIri): CustomerType
    {
        return $this->iriConverter->getResourceFromIri($typeIri);
    }

    private function getCustomerStatus(string $statusIri): CustomerStatus
    {
        return $this->iriConverter->getResourceFromIri($statusIri);
    }

    private function dispatchCommand(
        Customer $customer,
        string $newInitials,
        string $newEmail,
        string $newPhone,
        string $newLeadSource,
        CustomerType $newType,
        CustomerStatus $newStatus,
        bool $newConfirmed
    ): void {
        $this->commandBus->dispatch(
            $this->updateCustomerCommandFactory->create(
                $customer,
                new CustomerUpdate(
                    newInitials: $newInitials,
                    newEmail: $newEmail,
                    newPhone: $newPhone,
                    newLeadSource: $newLeadSource,
                    newType: $newType,
                    newStatus: $newStatus,
                    newConfirmed: $newConfirmed,
                )
            )
        );
    }
}
