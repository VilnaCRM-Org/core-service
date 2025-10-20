<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\TypeCreate;
use App\Core\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Core\Customer\Application\Transformer\TypeTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<TypeCreate, CustomerType>
 */
final readonly class CreateTypeProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateTypeFactoryInterface $createTypeCommandFactory,
        private TypeTransformerInterface $transformer,
    ) {
    }

    /**
     * @param TypeCreate $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerType {
        $customerType = $this->transformer->transform($data->value);
        $command = $this->createTypeCommandFactory->create(
            $customerType
        );

        $this->commandBus->dispatch($command);

        return $customerType;
    }
}
