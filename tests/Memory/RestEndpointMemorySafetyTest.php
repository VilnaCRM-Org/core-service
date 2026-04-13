<?php

declare(strict_types=1);

namespace App\Tests\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Integration\BaseApiCase;
use App\Tests\Support\Memory\SameKernelRequestMemoryProbe;
use InvalidArgumentException;

final class RestEndpointMemorySafetyTest extends BaseApiCase
{
    private const REST_SCENARIO_METHODS = [
        'health_get' => 'exerciseHealthGet',
        'customers_get_collection' => 'exerciseCustomersGetCollection',
        'customers_get_item' => 'exerciseCustomersGetItem',
        'customers_get_missing' => 'exerciseCustomersGetMissing',
        'customers_post' => 'exerciseCustomersPost',
        'customers_put' => 'exerciseCustomersPut',
        'customers_patch' => 'exerciseCustomersPatch',
        'customers_delete' => 'exerciseCustomersDelete',
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
        $provider = [];

        foreach (array_keys(self::REST_SCENARIO_METHODS) as $scenario) {
            $provider[$scenario] = [$scenario];
        }

        return $provider;
    }

    private function exerciseRestScenario(string $scenario, Client $client): void
    {
        $handlers = $this->restScenarioHandlers();

        if (isset($handlers[$scenario])) {
            $handlers[$scenario]($client);
            return;
        }

        throw new InvalidArgumentException("Unknown REST memory scenario '{$scenario}'.");
    }

    /**
     * @return array<string, \Closure(Client): void>
     */
    private function restScenarioHandlers(): array
    {
        $handlers = [];

        foreach (self::REST_SCENARIO_METHODS as $scenario => $method) {
            $handlers[$scenario] = \Closure::fromCallable([$this, $method]);
        }

        return $handlers;
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

    private function exerciseCustomersGetMissing(Client $client): void
    {
        $response = $client->request('GET', '/api/customers/' . $this->faker->ulid());
        $error = $response->toArray(false);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('An error occurred', $error['title']);
        self::assertSame('Not Found', $error['detail']);
        self::assertSame(404, $error['status']);
        self::assertSame('/errors/404', $error['type']);
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
        $patch = ['email' => $this->generateUniqueEmailAddress('patched-customer')];

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

    private function exerciseCustomerStatusesGetCollection(Client $client): void
    {
        $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Active'))
        );

        $data = $client->request('GET', '/api/customer_statuses')->toArray();

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertArrayHasKey('member', $data);
    }

    private function exerciseCustomerStatusesGetItem(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('Pending'));
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $payload);

        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPost(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('Prospect'));

        $data = $this->jsonRequestWithClient($client, 'POST', '/api/customer_statuses', $payload);

        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPut(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Active'))
        );
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('Inactive'));

        $data = $this->jsonRequestWithClient($client, 'PUT', $iri, $payload);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerStatusesPatch(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Active'))
        );
        $patch = ['value' => $this->uniqueLookupValue('Pending')];

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
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Delete'))
        );

        $response = $client->request('DELETE', $iri);

        self::assertSame(204, $response->getStatusCode());
    }

    private function exerciseCustomerTypesGetCollection(Client $client): void
    {
        $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Retail'))
        );

        $data = $client->request('GET', '/api/customer_types')->toArray();

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertArrayHasKey('member', $data);
    }

    private function exerciseCustomerTypesGetItem(Client $client): void
    {
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('Wholesale'));
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $payload);

        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPost(Client $client): void
    {
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('Partner'));

        $data = $this->jsonRequestWithClient($client, 'POST', '/api/customer_types', $payload);

        self::assertSame(201, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPut(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Lead'))
        );
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('Client'));

        $data = $this->jsonRequestWithClient($client, 'PUT', $iri, $payload);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($payload['value'], $data['value']);
    }

    private function exerciseCustomerTypesPatch(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Lead'))
        );
        $patch = ['value' => $this->uniqueLookupValue('VIP')];

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
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Delete'))
        );

        $response = $client->request('DELETE', $iri);

        self::assertSame(204, $response->getStatusCode());
    }

    private function uniqueLookupValue(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, $this->faker->ulid());
    }
}
