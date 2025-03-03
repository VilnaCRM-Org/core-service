<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\CreateTypeCommandFactoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

class CreateCustomerTypeProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface               $commandBus,
        private CreateTypeCommandFactoryInterface $createTypeCommandFactory
    ) {
    }

    public function process(
        mixed     $data,
        Operation $operation,
        array     $uriVariables = [],
        array     $context = []
    ) {
        $command = $this->createTypeCommandFactory->create(
            $data->value
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customerType;
    }
}
