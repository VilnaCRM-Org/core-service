<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\CustomerCreate;
use App\Core\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Core\Customer\Application\Transformer\CustomerTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerCreate, Customer>
 */
final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateCustomerFactoryInterface $createCustomerFactory,
        private IriConverterInterface $iriConverter,
        private CustomerTransformerInterface $transformer,
    ) {
    }

    /**
     * @param CustomerCreate $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customerStatusEntity = $this->iriConverter
            ->getResourceFromIri($data->status);
        $customerTypeEntity = $this->iriConverter
            ->getResourceFromIri($data->type);
        $customer = $this->transformer->transform(
            $data->initials,
            $data->email,
            $data->phone,
            $data->leadSource,
            $customerTypeEntity,
            $customerStatusEntity,
            $data->confirmed
        );
        $command = $this->createCustomerFactory->create(
            $customer
        );
        $this->commandBus->dispatch($command);

        return $command->customer;
    }
}
