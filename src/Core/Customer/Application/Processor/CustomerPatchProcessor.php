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
use App\Shared\Application\Service\StringFieldResolver;
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
        private StringFieldResolver $fieldResolver,
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
        $customerUpdate = $this->buildCustomerUpdate($data, $customer);
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

    private function buildCustomerUpdate(
        CustomerPatch $data,
        Customer $customer
    ): CustomerUpdate {
        return new CustomerUpdate(
            newInitials: $this->fieldResolver->resolve(
                $data->initials,
                $customer->getInitials()
            ),
            newEmail: $this->fieldResolver->resolve($data->email, $customer->getEmail()),
            newPhone: $this->fieldResolver->resolve($data->phone, $customer->getPhone()),
            newLeadSource: $this->fieldResolver->resolve(
                $data->leadSource,
                $customer->getLeadSource()
            ),
            newType: $this->resolveType($data->type, $customer),
            newStatus: $this->resolveStatus($data->status, $customer),
            newConfirmed: $data->confirmed ?? $customer->isConfirmed()
        );
    }

    private function resolveType(?string $typeIri, Customer $customer): CustomerType
    {
        if ($typeIri === null) {
            return $customer->getType();
        }

        return $this->iriConverter->getResourceFromIri($typeIri);
    }

    private function resolveStatus(?string $statusIri, Customer $customer): CustomerStatus
    {
        if ($statusIri === null) {
            return $customer->getStatus();
        }

        return $this->iriConverter->getResourceFromIri($statusIri);
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
