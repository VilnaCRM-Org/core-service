<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerPutDto;
use App\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Exception\CustomerNotFoundException;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerPutDto, Customer>
 */
final readonly class CustomerPutProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerCommandFactoryInterface $updateCustomerCommandFactory,
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * @param CustomerPutDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customerId = $uriVariables['ulid'];
        $customer = $this->customerRepository->find($customerId)
            ?? throw new CustomerNotFoundException();

        $customerType = $this->iriConverter->getResourceFromIri($data->type);
        $customerStatus = $this->iriConverter->getResourceFromIri($data->status);

        $this->commandBus->dispatch(
            $this->updateCustomerCommandFactory->create(
                $customer,
                new CustomerUpdate(
                    $data->initials,
                    $data->email,
                    $data->phone,
                    $data->leadSource,
                    $customerType,
                    $customerStatus,
                    $data->confirmed,
                )
            )
        );

        return $customer;
    }
}
