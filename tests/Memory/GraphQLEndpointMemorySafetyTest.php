<?php

declare(strict_types=1);

namespace App\Tests\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Integration\BaseGraphQLCase;
use App\Tests\Support\Memory\SameKernelRequestMemoryProbe;
use InvalidArgumentException;

final class GraphQLEndpointMemorySafetyTest extends BaseGraphQLCase
{
    private const GRAPHQL_SCENARIO_METHODS = [
        'customer_query' => 'exerciseCustomerQuery',
        'customer_query_collection' => 'exerciseCustomerQueryCollection',
        'customer_create_mutation' => 'exerciseCustomerCreateMutation',
        'customer_update_mutation' => 'exerciseCustomerUpdateMutation',
        'customer_delete_mutation' => 'exerciseCustomerDeleteMutation',
        'customer_status_query' => 'exerciseCustomerStatusQuery',
        'customer_status_query_collection' => 'exerciseCustomerStatusQueryCollection',
        'customer_status_create_mutation' => 'exerciseCustomerStatusCreateMutation',
        'customer_status_update_mutation' => 'exerciseCustomerStatusUpdateMutation',
        'customer_status_delete_mutation' => 'exerciseCustomerStatusDeleteMutation',
        'customer_type_query' => 'exerciseCustomerTypeQuery',
        'customer_type_query_collection' => 'exerciseCustomerTypeQueryCollection',
        'customer_type_create_mutation' => 'exerciseCustomerTypeCreateMutation',
        'customer_type_update_mutation' => 'exerciseCustomerTypeUpdateMutation',
        'customer_type_delete_mutation' => 'exerciseCustomerTypeDeleteMutation',
        'customer_type_delete_missing' => 'exerciseCustomerTypeDeleteMissing',
    ];

    /**
     * @dataProvider graphQlScenarioProvider
     */
    public function testGraphQlScenarioDoesNotRetainMainRequestAcrossSameKernelRequests(string $scenario): void
    {
        $client = $this->createSameKernelClient();
        $probe = SameKernelRequestMemoryProbe::fromClient($client);

        $probe->assertRequestIsReleasedBetweenSameKernelRequests(
            $this,
            $client,
            $scenario,
            function (Client $client) use ($scenario): void {
                $this->exerciseGraphQlScenario($scenario, $client);
            },
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function graphQlScenarioProvider(): array
    {
        $provider = [];

        foreach (array_keys(self::GRAPHQL_SCENARIO_METHODS) as $scenario) {
            $provider[$scenario] = [$scenario];
        }

        return $provider;
    }

    private function exerciseGraphQlScenario(string $scenario, Client $client): void
    {
        $handlers = $this->graphQlScenarioHandlers();

        if (isset($handlers[$scenario])) {
            $handlers[$scenario]($client);
            return;
        }

        throw new InvalidArgumentException("Unknown GraphQL memory scenario '{$scenario}'.");
    }

    /**
     * @return array<string, \Closure(Client): void>
     */
    private function graphQlScenarioHandlers(): array
    {
        $handlers = [];

        foreach (self::GRAPHQL_SCENARIO_METHODS as $scenario => $method) {
            $handlers[$scenario] = \Closure::fromCallable([$this, $method]);
        }

        return $handlers;
    }

    private function exerciseCustomerQuery(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'GraphQL Query');
        $iri = $this->createEntityWithClient($client, '/api/customers', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['email'], $this->getGraphQLDataField($response, 'customer.email'));
    }

    private function exerciseCustomerQueryCollection(Client $client): void
    {
        $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'GraphQL Collection A'));
        $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'GraphQL Collection B'));

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerCollectionQuery(), ['first' => 10]);

        $this->assertGraphQLSuccess($response);
        self::assertNotEmpty($this->getGraphQLDataField($response, 'customers.edges'));
    }

    private function exerciseCustomerCreateMutation(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'GraphQL Create');

        $response = $this->graphqlMutationWithClient($client, $this->getCreateCustomerMutation(), $payload);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['email'], $this->getGraphQLDataField($response, 'createCustomer.customer.email'));
    }

    private function exerciseCustomerUpdateMutation(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'GraphQL Update');
        $iri = $this->createEntityWithClient($client, '/api/customers', $payload);

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getUpdateCustomerMutation(),
            [
                'id' => $iri,
                'initials' => 'Updated GraphQL',
                'email' => $this->generateUniqueEmailAddress('graphql-updated-customer'),
            ]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame('Updated GraphQL', $this->getGraphQLDataField($response, 'updateCustomer.customer.initials'));
    }

    private function exerciseCustomerDeleteMutation(Client $client): void
    {
        $iri = $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'GraphQL Delete'));

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getDeleteCustomerMutation(),
            ['id' => $iri]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame($iri, $this->getGraphQLDataField($response, 'deleteCustomer.customer.id'));
    }

    private function exerciseCustomerStatusQuery(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('GraphQL Status'));
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerStatusQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'customerStatus.value'));
    }

    private function exerciseCustomerStatusQueryCollection(Client $client): void
    {
        $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Active'))
        );
        $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Pending'))
        );

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerStatusCollectionQuery(), ['first' => 10]);

        $this->assertGraphQLSuccess($response);
        self::assertNotEmpty($this->getGraphQLDataField($response, 'customerStatuses.edges'));
    }

    private function exerciseCustomerStatusCreateMutation(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('Created Status'));

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getCreateCustomerStatusMutation(),
            $payload
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'createCustomerStatus.customerStatus.value'));
    }

    private function exerciseCustomerStatusUpdateMutation(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Before Update'))
        );
        $updatedValue = $this->uniqueLookupValue('After Update');

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getUpdateCustomerStatusMutation(),
            [
                'id' => $iri,
                'value' => $updatedValue,
            ]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame(
            $updatedValue,
            $this->getGraphQLDataField($response, 'updateCustomerStatus.customerStatus.value')
        );
    }

    private function exerciseCustomerStatusDeleteMutation(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_statuses',
            $this->getCustomerStatusPayload($this->uniqueLookupValue('Delete Status'))
        );

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getDeleteCustomerStatusMutation(),
            ['id' => $iri]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame($iri, $this->getGraphQLDataField($response, 'deleteCustomerStatus.customerStatus.id'));
    }

    private function exerciseCustomerTypeQuery(Client $client): void
    {
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('GraphQL Type'));
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerTypeQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'customerType.value'));
    }

    private function exerciseCustomerTypeQueryCollection(Client $client): void
    {
        $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Retail'))
        );
        $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('VIP'))
        );

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerTypeCollectionQuery(), ['first' => 10]);

        $this->assertGraphQLSuccess($response);
        self::assertNotEmpty($this->getGraphQLDataField($response, 'customerTypes.edges'));
    }

    private function exerciseCustomerTypeCreateMutation(Client $client): void
    {
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('Created Type'));

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getCreateCustomerTypeMutation(),
            $payload
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'createCustomerType.customerType.value'));
    }

    private function exerciseCustomerTypeUpdateMutation(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Before Update'))
        );
        $updatedValue = $this->uniqueLookupValue('After Update');

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getUpdateCustomerTypeMutation(),
            [
                'id' => $iri,
                'value' => $updatedValue,
            ]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame(
            $updatedValue,
            $this->getGraphQLDataField($response, 'updateCustomerType.customerType.value')
        );
    }

    private function exerciseCustomerTypeDeleteMutation(Client $client): void
    {
        $iri = $this->createEntityWithClient(
            $client,
            '/api/customer_types',
            $this->getCustomerTypePayload($this->uniqueLookupValue('Delete Type'))
        );

        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getDeleteCustomerTypeMutation(),
            ['id' => $iri]
        );

        $this->assertGraphQLSuccess($response);
        self::assertSame($iri, $this->getGraphQLDataField($response, 'deleteCustomerType.customerType.id'));
    }

    private function exerciseCustomerTypeDeleteMissing(Client $client): void
    {
        $response = $this->graphqlMutationWithClient(
            $client,
            $this->getDeleteCustomerTypeMutation(),
            ['id' => '/api/customer_types/' . $this->faker->ulid()]
        );

        $this->assertGraphQLError($response);
    }

    private function uniqueLookupValue(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, $this->faker->ulid());
    }

    private function getCustomerQuery(): string
    {
        return <<<'GQL'
            query GetCustomer($id: ID!) {
                customer(id: $id) {
                    id
                    initials
                    email
                }
            }
        GQL;
    }

    private function getCustomerCollectionQuery(): string
    {
        return <<<'GQL'
            query GetCustomers($first: Int) {
                customers(first: $first) {
                    edges { node { id email } }
                }
            }
        GQL;
    }

    private function getCreateCustomerMutation(): string
    {
        return <<<'GQL'
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                        email
                    }
                }
            }
        GQL;
    }

    private function getUpdateCustomerMutation(): string
    {
        return <<<'GQL'
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        initials
                        email
                    }
                }
            }
        GQL;
    }

    private function getDeleteCustomerMutation(): string
    {
        return <<<'GQL'
            mutation DeleteCustomer($input: deleteCustomerInput!) {
                deleteCustomer(input: $input) {
                    customer {
                        id
                    }
                }
            }
        GQL;
    }

    private function getCustomerStatusQuery(): string
    {
        return <<<'GQL'
            query GetCustomerStatus($id: ID!) {
                customerStatus(id: $id) {
                    id
                    value
                }
            }
        GQL;
    }

    private function getCustomerStatusCollectionQuery(): string
    {
        return <<<'GQL'
            query GetCustomerStatuses($first: Int) {
                customerStatuses(first: $first) {
                    edges { node { id value } }
                }
            }
        GQL;
    }

    private function getCreateCustomerStatusMutation(): string
    {
        return <<<'GQL'
            mutation CreateCustomerStatus($input: createCustomerStatusInput!) {
                createCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        GQL;
    }

    private function getUpdateCustomerStatusMutation(): string
    {
        return <<<'GQL'
            mutation UpdateCustomerStatus($input: updateCustomerStatusInput!) {
                updateCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        GQL;
    }

    private function getDeleteCustomerStatusMutation(): string
    {
        return <<<'GQL'
            mutation DeleteCustomerStatus($input: deleteCustomerStatusInput!) {
                deleteCustomerStatus(input: $input) {
                    customerStatus {
                        id
                    }
                }
            }
        GQL;
    }

    private function getCustomerTypeQuery(): string
    {
        return <<<'GQL'
            query GetCustomerType($id: ID!) {
                customerType(id: $id) {
                    id
                    value
                }
            }
        GQL;
    }

    private function getCustomerTypeCollectionQuery(): string
    {
        return <<<'GQL'
            query GetCustomerTypes($first: Int) {
                customerTypes(first: $first) {
                    edges { node { id value } }
                }
            }
        GQL;
    }

    private function getCreateCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation CreateCustomerType($input: createCustomerTypeInput!) {
                createCustomerType(input: $input) {
                    customerType {
                        id
                        value
                    }
                }
            }
        GQL;
    }

    private function getUpdateCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation UpdateCustomerType($input: updateCustomerTypeInput!) {
                updateCustomerType(input: $input) {
                    customerType {
                        id
                        value
                    }
                }
            }
        GQL;
    }

    private function getDeleteCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation DeleteCustomerType($input: deleteCustomerTypeInput!) {
                deleteCustomerType(input: $input) {
                    customerType {
                        id
                    }
                }
            }
        GQL;
    }
}
