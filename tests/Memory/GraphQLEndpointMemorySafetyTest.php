<?php

declare(strict_types=1);

namespace App\Tests\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Integration\BaseGraphQLCase;
use App\Tests\Support\Memory\SameKernelRequestMemoryProbe;
use InvalidArgumentException;

final class GraphQLEndpointMemorySafetyTest extends BaseGraphQLCase
{
    private const GRAPHQL_SCENARIOS = [
        'customerQuery' => 'customer_query',
        'customerQueryCollection' => 'customer_query_collection',
        'customerCreateMutation' => 'customer_create_mutation',
        'customerUpdateMutation' => 'customer_update_mutation',
        'customerDeleteMutation' => 'customer_delete_mutation',
        'customerStatusQuery' => 'customer_status_query',
        'customerStatusQueryCollection' => 'customer_status_query_collection',
        'customerStatusCreateMutation' => 'customer_status_create_mutation',
        'customerStatusUpdateMutation' => 'customer_status_update_mutation',
        'customerStatusDeleteMutation' => 'customer_status_delete_mutation',
        'customerTypeQuery' => 'customer_type_query',
        'customerTypeQueryCollection' => 'customer_type_query_collection',
        'customerTypeCreateMutation' => 'customer_type_create_mutation',
        'customerTypeUpdateMutation' => 'customer_type_update_mutation',
        'customerTypeDeleteMutation' => 'customer_type_delete_mutation',
        'customerTypeDeleteMissing' => 'customer_type_delete_missing',
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
        return array_combine(
            array_values(self::GRAPHQL_SCENARIOS),
            array_map(
                static fn (string $scenario): array => [$scenario],
                array_values(self::GRAPHQL_SCENARIOS)
            )
        );
    }

    private function exerciseGraphQlScenario(string $scenario, Client $client): void
    {
        match ($scenario) {
            self::GRAPHQL_SCENARIOS['customerQuery'] => $this->exerciseCustomerQuery($client),
            self::GRAPHQL_SCENARIOS['customerQueryCollection'] => $this->exerciseCustomerQueryCollection($client),
            self::GRAPHQL_SCENARIOS['customerCreateMutation'] => $this->exerciseCustomerCreateMutation($client),
            self::GRAPHQL_SCENARIOS['customerUpdateMutation'] => $this->exerciseCustomerUpdateMutation($client),
            self::GRAPHQL_SCENARIOS['customerDeleteMutation'] => $this->exerciseCustomerDeleteMutation($client),
            self::GRAPHQL_SCENARIOS['customerStatusQuery'] => $this->exerciseCustomerStatusQuery($client),
            self::GRAPHQL_SCENARIOS['customerStatusQueryCollection'] => $this->exerciseCustomerStatusQueryCollection($client),
            self::GRAPHQL_SCENARIOS['customerStatusCreateMutation'] => $this->exerciseCustomerStatusCreateMutation($client),
            self::GRAPHQL_SCENARIOS['customerStatusUpdateMutation'] => $this->exerciseCustomerStatusUpdateMutation($client),
            self::GRAPHQL_SCENARIOS['customerStatusDeleteMutation'] => $this->exerciseCustomerStatusDeleteMutation($client),
            self::GRAPHQL_SCENARIOS['customerTypeQuery'] => $this->exerciseCustomerTypeQuery($client),
            self::GRAPHQL_SCENARIOS['customerTypeQueryCollection'] => $this->exerciseCustomerTypeQueryCollection($client),
            self::GRAPHQL_SCENARIOS['customerTypeCreateMutation'] => $this->exerciseCustomerTypeCreateMutation($client),
            self::GRAPHQL_SCENARIOS['customerTypeUpdateMutation'] => $this->exerciseCustomerTypeUpdateMutation($client),
            self::GRAPHQL_SCENARIOS['customerTypeDeleteMutation'] => $this->exerciseCustomerTypeDeleteMutation($client),
            self::GRAPHQL_SCENARIOS['customerTypeDeleteMissing'] => $this->exerciseCustomerTypeDeleteMissing($client),
            default => throw new InvalidArgumentException("Unknown GraphQL memory scenario '{$scenario}'."),
        };
    }

    protected function exerciseCustomerQuery(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'GraphQL Query');
        $iri = $this->createEntityWithClient($client, '/api/customers', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['email'], $this->getGraphQLDataField($response, 'customer.email'));
    }

    protected function exerciseCustomerQueryCollection(Client $client): void
    {
        $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'GraphQL Collection A'));
        $this->createEntityWithClient($client, '/api/customers', $this->getCustomerPayloadWithClient($client, 'GraphQL Collection B'));

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerCollectionQuery(), ['first' => 10]);

        $this->assertGraphQLSuccess($response);
        self::assertNotEmpty($this->getGraphQLDataField($response, 'customers.edges'));
    }

    protected function exerciseCustomerCreateMutation(Client $client): void
    {
        $payload = $this->getCustomerPayloadWithClient($client, 'GraphQL Create');

        $response = $this->graphqlMutationWithClient($client, $this->getCreateCustomerMutation(), $payload);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['email'], $this->getGraphQLDataField($response, 'createCustomer.customer.email'));
    }

    protected function exerciseCustomerUpdateMutation(Client $client): void
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

    protected function exerciseCustomerDeleteMutation(Client $client): void
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

    protected function exerciseCustomerStatusQuery(Client $client): void
    {
        $payload = $this->getCustomerStatusPayload($this->uniqueLookupValue('GraphQL Status'));
        $iri = $this->createEntityWithClient($client, '/api/customer_statuses', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerStatusQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'customerStatus.value'));
    }

    protected function exerciseCustomerStatusQueryCollection(Client $client): void
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

    protected function exerciseCustomerStatusCreateMutation(Client $client): void
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

    protected function exerciseCustomerStatusUpdateMutation(Client $client): void
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

    protected function exerciseCustomerStatusDeleteMutation(Client $client): void
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

    protected function exerciseCustomerTypeQuery(Client $client): void
    {
        $payload = $this->getCustomerTypePayload($this->uniqueLookupValue('GraphQL Type'));
        $iri = $this->createEntityWithClient($client, '/api/customer_types', $payload);

        $response = $this->graphqlRequestWithClient($client, $this->getCustomerTypeQuery(), ['id' => $iri]);

        $this->assertGraphQLSuccess($response);
        self::assertSame($payload['value'], $this->getGraphQLDataField($response, 'customerType.value'));
    }

    protected function exerciseCustomerTypeQueryCollection(Client $client): void
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

    protected function exerciseCustomerTypeCreateMutation(Client $client): void
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

    protected function exerciseCustomerTypeUpdateMutation(Client $client): void
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

    protected function exerciseCustomerTypeDeleteMutation(Client $client): void
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

    protected function exerciseCustomerTypeDeleteMissing(Client $client): void
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
