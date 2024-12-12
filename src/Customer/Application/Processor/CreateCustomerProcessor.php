<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerCreateDto;
use App\Customer\Application\Factory\CreateCustomerCommandFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerCreateDto, Customer>
 */
final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateCustomerCommandFactoryInterface $createCustomerCommandFactory
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

        $command = $this->createCustomerCommandFactory->create(
            $data->initials,
            $data->email,
            $data->phone,
            $data->leadSource,
            $data->type,
            $data->status
        );
        $this->commandBus->dispatch($command);

    }
}
