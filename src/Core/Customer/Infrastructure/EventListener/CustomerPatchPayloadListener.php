<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\Validator\Guard\PatchPayloadGuard;
use const JSON_THROW_ON_ERROR;
use JsonException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CustomerPatchPayloadListener
{
    private const SUPPORTED_FIELDS_BY_RESOURCE = [
        Customer::class => [
            'initials',
            'email',
            'phone',
            'leadSource',
            'type',
            'status',
            'confirmed',
        ],
        CustomerStatus::class => [
            'value',
        ],
        CustomerType::class => [
            'value',
        ],
    ];

    public function __construct(
        private PatchPayloadGuard $guard
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 16)]
    public function __invoke(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== Request::METHOD_PATCH) {
            return;
        }

        $supportedFields = $this->supportedFields($request);
        if ($supportedFields === null) {
            return;
        }

        $payload = $this->decodedPayload($request);
        if (! is_iterable($payload)) {
            return;
        }

        $this->guard->assertContainsAnyField($payload, $supportedFields);
    }

    /**
     * @return iterable<non-empty-string>|null
     */
    private function supportedFields(Request $request): ?iterable
    {
        $resourceClass = $request->attributes->get('_api_resource_class');
        if (
            ! is_string($resourceClass)
            || ! isset(self::SUPPORTED_FIELDS_BY_RESOURCE[$resourceClass])
        ) {
            return null;
        }

        return self::SUPPORTED_FIELDS_BY_RESOURCE[$resourceClass];
    }

    /**
     * @return iterable<array-key, object|iterable|string|int|float|bool|null>|null
     */
    private function decodedPayload(Request $request): ?iterable
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        try {
            $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_iterable($payload) ? $payload : null;
    }
}
