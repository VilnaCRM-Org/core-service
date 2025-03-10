<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateTypeCommandResponse;
use App\Customer\Application\Transformer\CreateTypeTransformer;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerTypeCommand;

final class CreateTypeCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateTypeTransformer $transformer,
        private TypeRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerTypeCommand $command): void
    {
        $customerType = $this->transformer->transformToType($command);
        $this->repository->save($customerType);
        $command->setResponse(new CreateTypeCommandResponse($customerType));
    }
}
