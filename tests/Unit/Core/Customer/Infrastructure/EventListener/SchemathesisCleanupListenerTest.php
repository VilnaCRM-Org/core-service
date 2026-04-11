<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisCleanupEvaluator;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisCleanupListener;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisEmailExtractor;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SchemathesisCleanupListenerTest extends UnitTestCase
{
    private CustomerRepositoryInterface $customerRepository;
    private SchemathesisCleanupEvaluator $evaluator;
    private SchemathesisEmailExtractor $emailExtractor;
    private SchemathesisCleanupListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->evaluator = $this->createMock(SchemathesisCleanupEvaluator::class);
        $this->emailExtractor = $this->createMock(SchemathesisEmailExtractor::class);

        $this->listener = new SchemathesisCleanupListener(
            $this->customerRepository,
            $this->evaluator,
            $this->emailExtractor
        );
    }

    public function testInvokeSkipsWhenShouldNotCleanup(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $this->evaluator
            ->expects($this->once())
            ->method('shouldCleanup')
            ->with($request, $response)
            ->willReturn(false);

        $this->emailExtractor
            ->expects($this->never())
            ->method('extract');

        $this->customerRepository
            ->expects($this->never())
            ->method('deleteByEmail');

        ($this->listener)($event);
    }

    public function testInvokeDeletesCustomersWhenShouldCleanup(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $event = new TerminateEvent($kernel, $request, $response);
        $emails = ['test1@example.com', 'test2@example.com'];
        $deletedEmails = [];

        $this->evaluator
            ->expects($this->once())
            ->method('shouldCleanup')
            ->with($request, $response)
            ->willReturn(true);

        $this->emailExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($emails);

        $this->customerRepository
            ->expects($this->exactly(2))
            ->method('deleteByEmail')
            ->willReturnCallback(
                static function (string $email) use (&$deletedEmails): void {
                    $deletedEmails[] = $email;
                }
            );

        ($this->listener)($event);

        $this->assertSame($emails, $deletedEmails);
    }

    public function testInvokeHandlesDuplicateEmails(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $emails = ['test@example.com', 'test@example.com'];

        $this->evaluator
            ->expects($this->once())
            ->method('shouldCleanup')
            ->with($request, $response)
            ->willReturn(true);

        $this->emailExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($emails);

        $this->customerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with('test@example.com');

        ($this->listener)($event);
    }
}
