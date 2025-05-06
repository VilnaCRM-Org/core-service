<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateStatusTransformer
{
    public function __construct(
        private StatusFactoryInterface $statusFactory,
        private UlidTransformer $transformer,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function transform(
        CreateStatusCommand $command
    ): CustomerStatus {
        return $this->statusFactory->create(
            $command->value,
            $this->transformer->transformFromSymfonyUlid(
                $this->ulidFactory->create()
            )
        );
    }
}
