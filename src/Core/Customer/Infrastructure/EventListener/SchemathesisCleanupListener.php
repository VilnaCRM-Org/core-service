<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::TERMINATE)]
final class SchemathesisCleanupListener
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly SchemathesisCleanupEvaluator $evaluator,
        private readonly SchemathesisEmailExtractor $emailExtractor
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $this->evaluator->shouldCleanup($request, $response)) {
            return;
        }

        $this->deleteCustomers($this->emailExtractor->extract($request));
    }

    /**
     * @param list<string> $emails
     */
    private function deleteCustomers(array $emails): void
    {
        foreach (array_unique($emails) as $email) {
            $this->customerRepository->deleteByEmail($email);
        }
    }
}
