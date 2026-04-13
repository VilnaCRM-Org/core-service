<?php

declare(strict_types=1);

namespace App\Tests\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Integration\BaseApiCase;
use App\Tests\Support\Memory\SameKernelRequestMemoryProbe;
use InvalidArgumentException;

final class RestEndpointMemorySafetyTest extends BaseApiCase
{
    private const REST_SCENARIOS = [
        'healthGet' => 'health_get',
        'customersGetCollection' => 'customers_get_collection',
        'customersGetItem' => 'customers_get_item',
        'customersGetMissing' => 'customers_get_missing',
        'customersPost' => 'customers_post',
        'customersPut' => 'customers_put',
        'customersPatch' => 'customers_patch',
        'customersDelete' => 'customers_delete',
        'customerStatusesGetCollection' => 'customer_statuses_get_collection',
        'customerStatusesGetItem' => 'customer_statuses_get_item',
        'customerStatusesPost' => 'customer_statuses_post',
        'customerStatusesPut' => 'customer_statuses_put',
        'customerStatusesPatch' => 'customer_statuses_patch',
        'customerStatusesDelete' => 'customer_statuses_delete',
        'customerTypesGetCollection' => 'customer_types_get_collection',
        'customerTypesGetItem' => 'customer_types_get_item',
        'customerTypesPost' => 'customer_types_post',
        'customerTypesPut' => 'customer_types_put',
        'customerTypesPatch' => 'customer_types_patch',
        'customerTypesDelete' => 'customer_types_delete',
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
        return array_combine(
            array_values(self::REST_SCENARIOS),
            array_map(
                static fn (string $scenario): array => [$scenario],
                array_values(self::REST_SCENARIOS)
            )
        );
    }

    private function exerciseRestScenario(string $scenario, Client $client): void
    {
        match ($scenario) {
            self::REST_SCENARIOS['healthGet'] => $this->exerciseHealthGet($client),
            self::REST_SCENARIOS['customersGetCollection'] => $this->exerciseCustomersGetCollection($client),
            self::REST_SCENARIOS['customersGetItem'] => $this->exerciseCustomersGetItem($client),
            self::REST_SCENARIOS['customersGetMissing'] => $this->exerciseCustomersGetMissing($client),
            self::REST_SCENARIOS['customersPost'] => $this->exerciseCustomersPost($client),
            self::REST_SCENARIOS['customersPut'] => $this->exerciseCustomersPut($client),
            self::REST_SCENARIOS['customersPatch'] => $this->exerciseCustomersPatch($client),
            self::REST_SCENARIOS['customersDelete'] => $this->exerciseCustomersDelete($client),
            self::REST_SCENARIOS['customerStatusesGetCollection'] => $this->exerciseCustomerStatusesGetCollection($client),
            self::REST_SCENARIOS['customerStatusesGetItem'] => $this->exerciseCustomerStatusesGetItem($client),
            self::REST_SCENARIOS['customerStatusesPost'] => $this->exerciseCustomerStatusesPost($client),
            self::REST_SCENARIOS['customerStatusesPut'] => $this->exerciseCustomerStatusesPut($client),
            self::REST_SCENARIOS['customerStatusesPatch'] => $this->exerciseCustomerStatusesPatch($client),
            self::REST_SCENARIOS['customerStatusesDelete'] => $this->exerciseCustomerStatusesDelete($client),
            self::REST_SCENARIOS['customerTypesGetCollection'] => $this->exerciseCustomerTypesGetCollection($client),
            self::REST_SCENARIOS['customerTypesGetItem'] => $this->exerciseCustomerTypesGetItem($client),
            self::REST_SCENARIOS['customerTypesPost'] => $this->exerciseCustomerTypesPost($client),
            self::REST_SCENARIOS['customerTypesPut'] => $this->exerciseCustomerTypesPut($client),
            self::REST_SCENARIOS['customerTypesPatch'] => $this->exerciseCustomerTypesPatch($client),
            self::REST_SCENARIOS['customerTypesDelete'] => $this->exerciseCustomerTypesDelete($client),
            default => throw new InvalidArgumentException("Unknown REST memory scenario '{$scenario}'."),
        };
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
