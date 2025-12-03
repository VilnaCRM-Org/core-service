<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Contact;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\License;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class SpecMetadataCleaner
{
    public function createNormalizedOpenApi(OpenApi $openApi): OpenApi
    {
        $webhooks = $openApi->getWebhooks();

        return new OpenApi(
            $this->cleanInfo($openApi->getInfo()),
            $this->cleanServers($openApi->getServers()),
            $openApi->getPaths(),
            $this->cleanComponents($openApi->getComponents()),
            $openApi->getSecurity(),
            $openApi->getTags(),
            webhooks: $webhooks instanceof ArrayObject ? $webhooks : new ArrayObject()
        );
    }

    public function cleanComponents(?Components $components): ?Components
    {
        return $this->normalizeNullable(
            $components,
            static fn (Components $c): Components => new Components($c->getSchemas())
        );
    }

    private function cleanInfo(Info $info): Info
    {
        return new Info(
            $info->getTitle(),
            $info->getVersion(),
            $info->getDescription(),
            termsOfService: null,
            contact: $this->normalizeContact($info->getContact()),
            license: $this->normalizeLicense($info->getLicense())
        );
    }

    /**
     * @param array<Server> $servers
     *
     * @return array<Server>
     */
    private function cleanServers(array $servers): array
    {
        return array_map(
            static fn (Server $server): Server => new Server(
                $server->getUrl(),
                $server->getDescription()
            ),
            $servers
        );
    }

    private function normalizeContact(?Contact $contact): ?Contact
    {
        return $this->normalizeNullable(
            $contact,
            static fn (Contact $c): Contact => new Contact(
                $c->getName(),
                $c->getUrl(),
                $c->getEmail()
            )
        );
    }

    private function normalizeLicense(?License $license): ?License
    {
        return $this->normalizeNullable(
            $license,
            static fn (License $l): License => new License(
                $l->getName(),
                $l->getUrl()
            )
        );
    }

    /**
     * @template T
     *
     * @param T|null $value
     * @param callable(T): T $transformer
     *
     * @return T|null
     */
    private function normalizeNullable(?object $value, callable $transformer): ?object
    {
        return $value === null ? null : $transformer($value);
    }
}
