<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\CreateCustomerCommandFactoryInterface;
use App\Customer\Application\DTO\CustomerCreateDto;
use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerCreateDto, Customer>
 */
final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateCustomerCommandFactoryInterface $statusCommandFactory,
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * @param CustomerCreateDto $data
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

        $command = $this->statusCommandFactory->create(
            $data->initials,
            $data->email,
            $data->phone,
            $data->leadSource,
            $customerTypeEntity,
            $customerStatusEntity,
            $data->confirmed
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customer;
    }
}
