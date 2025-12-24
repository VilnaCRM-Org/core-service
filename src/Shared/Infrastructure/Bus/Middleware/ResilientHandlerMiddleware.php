<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Middleware that catches exceptions from handlers and logs them
 *
 * This ensures that observability code (metrics, logging) never breaks
 * the main business operation. Exceptions are logged but not propagated.
 */
final readonly class ResilientHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $this->logger->error('Event subscriber execution failed', [
                'message_class' => $envelope->getMessage()::class,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
                'trace' => $exception->getTraceAsString(),
            ]);

            return $envelope;
        }
    }
}
