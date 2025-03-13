<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\StatusCreateDto;
use App\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<StatusCreateDto, CustomerStatus>
 */
final readonly class CreateStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateStatusFactoryInterface $statusCommandFactory
    ) {
    }

    /**
     * @param StatusCreateDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerStatus {
        $command = $this->statusCommandFactory->create(
            $data->value
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customerStatus;
    }
}
