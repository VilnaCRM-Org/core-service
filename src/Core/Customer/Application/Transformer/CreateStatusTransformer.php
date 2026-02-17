<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateStatusTransformer implements
    StatusTransformerInterface
{
    public function __construct(
        private StatusFactoryInterface $statusFactory,
        private UlidTransformer $transformer,
        private UlidFactory $ulidFactory,
    ) {
    }

    #[\Override]
    public function transform(
        string $value
    ): CustomerStatus {
        return $this->statusFactory->create(
            $value,
            $this->transformer->transformFromSymfonyUlid(
                $this->ulidFactory->create()
            )
        );
    }
}
