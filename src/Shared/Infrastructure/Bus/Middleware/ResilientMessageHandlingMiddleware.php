<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Middleware;

use App\Shared\Domain\Bus\Event\DomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class ResilientMessageHandlingMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $this->logFailure($envelope, $exception);

            return $envelope;
        }
    }

    private function logFailure(Envelope $envelope, \Throwable $exception): void
    {
        $context = $this->buildContext($envelope, $exception);
        $this->logger->error('Event subscriber execution failed', $context);
    }

    /**
     * @return array<string, string|array<string, string|array<int, array<string, string>>>>
     */
    private function buildContext(Envelope $envelope, \Throwable $exception): array
    {
        $message = $envelope->getMessage();
        $context = $this->buildBaseContext($message, $exception);

        if ($message instanceof DomainEvent) {
            $context += $this->buildDomainEventContext($message);
        }

        if ($exception instanceof HandlerFailedException) {
            $context['wrapped_exceptions'] = $this->buildWrappedExceptionsContext(
                $exception
            );
        }

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private function buildBaseContext(object $message, \Throwable $exception): array
    {
        return [
            'message_class' => $message::class,
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
            'trace' => $exception->getTraceAsString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildDomainEventContext(DomainEvent $event): array
    {
        return [
            'event_name' => $event::eventName(),
            'event_id' => $event->eventId(),
            'occurred_on' => $event->occurredOn(),
            'payload' => $event->toPrimitives(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildWrappedExceptionsContext(
        HandlerFailedException $exception
    ): array {
        return array_map(
            static fn (\Throwable $nested): array => [
                'message' => $nested->getMessage(),
                'exception_class' => $nested::class,
                'trace' => $nested->getTraceAsString(),
            ],
            $exception->getWrappedExceptions()
        );
    }
}
