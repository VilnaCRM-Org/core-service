<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

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
        if ($components === null) {
            return null;
        }

        return new Components(
            $components->getSchemas()
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
        if ($contact === null) {
            return null;
        }

        return new Contact(
            $contact->getName(),
            $contact->getUrl(),
            $contact->getEmail()
        );
    }

    private function normalizeLicense(?License $license): ?License
    {
        if ($license === null) {
            return null;
        }

        return new License(
            $license->getName(),
            $license->getUrl()
        );
    }
}
