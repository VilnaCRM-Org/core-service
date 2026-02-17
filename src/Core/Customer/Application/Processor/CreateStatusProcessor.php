<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\StatusCreate;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<StatusCreate, CustomerStatus>
 */
final readonly class CreateStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateStatusFactoryInterface $statusCommandFactory,
        private StatusTransformerInterface $transformer,
    ) {
    }

    /**
     * @param StatusCreate $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CustomerStatus {
        $customerStatus = $this->transformer->transform($data->value);
        $command = $this->statusCommandFactory->create(
            $customerStatus
        );

        $this->commandBus->dispatch($command);

        return $customerStatus;
    }
}
