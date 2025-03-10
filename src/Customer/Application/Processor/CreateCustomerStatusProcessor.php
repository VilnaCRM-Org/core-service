<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\CreateStatusCommandFactoryInterface;
use App\Customer\Application\DTO\CustomerStatusCreateDto;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerStatusCreateDto, Customer>
 */
final readonly class CreateCustomerStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateStatusCommandFactoryInterface $createCustomerCommandFactory
    ) {
    }

    /**
     * @param CustomerStatusCreateDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerStatus {

        $command = $this->createCustomerCommandFactory->create(
            $data->value
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customerType;
    }
}
