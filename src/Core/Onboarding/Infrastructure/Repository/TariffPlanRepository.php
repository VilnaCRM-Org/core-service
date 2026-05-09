<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Infrastructure\Repository;

use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Shared\Domain\ValueObject\UlidInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<TariffPlan>
 *
 * @psalm-suppress UnusedClass Wired through the onboarding repository interface alias.
 */
final class TariffPlanRepository extends ServiceDocumentRepository implements
    TariffPlanRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TariffPlan::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function save(TariffPlan $plan, bool $flush = true): void
    {
        $this->documentManager->persist($plan);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->documentManager->flush();
    }

    public function findByUlid(UlidInterface $ulid): ?TariffPlan
    {
        $plan = parent::find($ulid);

        return $plan instanceof TariffPlan ? $plan : null;
    }

    public function findOneByCode(string $code): ?TariffPlan
    {
        return $this->findOneBy(['code' => $code]);
    }
}
