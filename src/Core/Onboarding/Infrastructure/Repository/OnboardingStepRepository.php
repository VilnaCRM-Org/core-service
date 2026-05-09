<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Infrastructure\Repository;

use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Shared\Domain\ValueObject\UlidInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<OnboardingStep>
 *
 * @psalm-suppress UnusedClass Wired through the onboarding repository interface alias.
 */
final class OnboardingStepRepository extends ServiceDocumentRepository implements
    OnboardingStepRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnboardingStep::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function save(OnboardingStep $step, bool $flush = true): void
    {
        $this->documentManager->persist($step);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->documentManager->flush();
    }

    public function findByUlid(UlidInterface $ulid): ?OnboardingStep
    {
        $step = parent::find($ulid);

        return $step instanceof OnboardingStep ? $step : null;
    }

    public function findOneByCode(string $code): ?OnboardingStep
    {
        return $this->findOneBy(['code' => $code]);
    }
}
