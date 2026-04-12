<?php

declare(strict_types=1);

namespace App\Tests\Integration\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Integration\BaseApiCase;
use App\Tests\Support\Memory\SameKernelRequestMemoryProbe;
use InvalidArgumentException;

final class RestEndpointMemorySafetyTest extends BaseApiCase
{
    /**
     * @dataProvider restScenarioProvider
     */
    public function testRestScenarioDoesNotRetainMainRequestAcrossSameKernelRequests(string $scenario): void
    {
        $client = $this->createSameKernelClient();
        $probe = SameKernelRequestMemoryProbe::fromClient($client);

        $probe->assertRequestIsReleasedBetweenSameKernelRequests(
            $this,
            $client,
            $scenario,
            function (Client $client) use ($scenario): void {
                $this->exerciseRestScenario($scenario, $client);
            },
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function restScenarioProvider(): array
    {
        return [
            'health_get' => ['health_get'],
            'customers_get_collection' => ['customers_get_collection'],
            'customers_get_item' => ['customers_get_item'],
            'customers_post' => ['customers_post'],
            'customers_put' => ['customers_put'],
            'customers_patch' => ['customers_patch'],
            'customers_delete' => ['customers_delete'],
            'customers_get_missing' => ['customers_get_missing'],
            'customer_statuses_get_collection' => ['customer_statuses_get_collection'],
            'customer_statuses_get_item' => ['customer_statuses_get_item'],
            'customer_statuses_post' => ['customer_statuses_post'],
            'customer_statuses_put' => ['customer_statuses_put'],
            'customer_statuses_patch' => ['customer_statuses_patch'],
            'customer_statuses_delete' => ['customer_statuses_delete'],
            'customer_types_get_collection' => ['customer_types_get_collection'],
            'customer_types_get_item' => ['customer_types_get_item'],
            'customer_types_post' => ['customer_types_post'],
            'customer_types_put' => ['customer_types_put'],
            'customer_types_patch' => ['customer_types_patch'],
            'customer_types_delete' => ['customer_types_delete'],
        ];
    }

    private function exerciseRestScenario(string $scenario, Client $client): void
    {
        $handlers = $this->restScenarioHandlers();

        if (isset($handlers[$scenario])) {
            $handler = $handlers[$scenario];
            $this->{$handler}($client);
            return;
        }

        throw new InvalidArgumentException("Unknown REST memory scenario '{$scenario}'.");
    }

    /**
     * @return array<string, string>
     */
    private function restScenarioHandlers(): array
    {
        return [
            'health_get' => 'exerciseHealthGet',
            'customers_get_collection' => 'exerciseCustomersGetCollection',
            'customers_get_item' => 'exerciseCustomersGetItem',
            'customers_post' => 'exerciseCustomersPost',
            'customers_put' => 'exerciseCustomersPut',
            'customers_patch' => 'exerciseCustomersPatch',
            'customers_delete' => 'exerciseCustomersDelete',
            'customers_get_missing' => 'exerciseCustomersGetMissing',
            'customer_statuses_get_collection' => 'exerciseCustomerStatusesGetCollection',
            'customer_statuses_get_item' => 'exerciseCustomerStatusesGetItem',
            'customer_statuses_post' => 'exerciseCustomerStatusesPost',
            'customer_statuses_put' => 'exerciseCustomerStatusesPut',
            'customer_statuses_patch' => 'exerciseCustomerStatusesPatch',
            'customer_statuses_delete' => 'exerciseCustomerStatusesDelete',
            'customer_types_get_collection' => 'exerciseCustomerTypesGetCollection',
            'customer_types_get_item' => 'exerciseCustomerTypesGetItem',
            'customer_types_post' => 'exerciseCustomerTypesPost',
            'customer_types_put' => 'exerciseCustomerTypesPut',
            'customer_types_patch' => 'exerciseCustomerTypesPatch',
            'customer_types_delete' => 'exerciseCustomerTypesDelete',
        ];
    }

    private function exerciseHealthGet(Client $client): void
    {
        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(204);
    }

    private function exerciseCustomersGetCollection(Client $client): void
    {
        $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'Collection Memory'));

        $response = $client->request('GET', '/api/customers');
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('member', $data);
    }

    private function exerciseCustomersGetItem(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'Item Memory');
        $iri = $this->createEntityWithClient($client, '/api/customers', $payload);

        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($payload['email'], $data['email']);
    }

    private function exerciseCustomersPost(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'Create Memory');

        $data = $this->jsonRequestWithClient($client, 'POST', '/api/customers', $payload);

        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame($payload['email'], $data['email']);
    }

    private function exerciseCustomersPut(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'Replace Memory'));
        $payload = $this->getCustomerPayloadWithClient($client, 'Replaced Memory');

        $data = $this->jsonRequestWithClient($client, 'PUT', $iri, $payload);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($payload['email'], $data['email']);
    }

    private function exerciseCustomersPatch(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'Patch Memory'));
        $patch = ['email' => $this->faker->unique()->safeEmail()];

        $data = $this->jsonRequestWithClient(
            $client,
            'PATCH',
            $iri,
            $patch,
            ['Content-Type' => 'application/merge-patch+json']
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($patch['email'], $data['email']);
    }

    private function exerciseCustomersDelete(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'Delete Memory'));

        $response = $client->request('DELETE', $iri);

        self::assertSame(204, $response->getStatusCode());
    }

    private function exerciseCustomersGetMissing(Client $client): void
    {
        $response = $client->request('GET', '/api/customers/' . $this->faker->ulid());

        self::assertSame(404, $response->getStatusCode());
    }

    private function exerciseCustomerStatusesGetCollection(Client $client): void
    {
        $this->createEntityWithClient($client, '/api/customer_statuses', $this->getCustomerStatusPayload('Active'));

        $data = $client->request('GET', '/api/customer_statuses')->toArray();

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertArrayHasKey('member', $data);
    }

    private function exerciseCustomerStatusesGetItem(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload('Pending');
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $payload);

        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPost(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload('Prospect');

        $data = $this->jsonRequestWithClient($client, 'POST', '/api/customer_statuses', $payload);

        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPut(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $this->getCustomerStatusPayload('Active'));
        $payload = $this->getCustomerStatusPayload('Inactive');

        $data = $this->jsonRequestWithClient($client, 'PUT', $iri, $payload);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPatch(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $this->getCustomerStatusPayload('Active'));
        $patch = ['value' => 'Pending'];

        $data = $this->jsonRequestWithClient(
            $client,
            'PATCH',
            $iri,
            $patch,
            ['Content-Type' => 'application/merge-patch+json']
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($patch['value'], $data['value']);
    }

    private function exerciseCustomerStatusesDelete(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $this->getCustomerStatusPayload('Delete'));

        $response = $client->request('DELETE', $iri);

        self::assertSame(204, $response->getStatusCode());
    }

    private function exerciseCustomerTypesGetCollection(Client $client): void
    {
        $this->createEntityWithClient($client, '/api/customer_types', $this->getCustomerTypePayload('Retail'));

        $data = $client->request('GET', '/api/customer_types')->toArray();

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertArrayHasKey('member', $data);
    }

    private function exerciseCustomerTypesGetItem(Client $client): void
    {
        $payload = $this->getCustomerTypePayload('Wholesale');
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $payload);

        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPost(Client $client): void
    {
        $payload = $this->getCustomerTypePayload('Partner');

        $data = $this->jsonRequestWithClient($client, 'POST', '/api/customer_types', $payload);

        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPut(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $this->getCustomerTypePayload('Lead'));
        $payload = $this->getCustomerTypePayload('Client');

        $data = $this->jsonRequestWithClient($client, 'PUT', $iri, $payload);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPatch(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $this->getCustomerTypePayload('Lead'));
        $patch = ['value' => 'VIP'];

        $data = $this->jsonRequestWithClient(
            $client,
            'PATCH',
            $iri,
            $patch,
            ['Content-Type' => 'application/merge-patch+json']
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($patch['value'], $data['value']);
    }

    private function exerciseCustomerTypesDelete(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $this->getCustomerTypePayload('Delete'));

        $response = $client->request('DELETE', $iri);

        self::assertSame(204, $response->getStatusCode());
    }
}
