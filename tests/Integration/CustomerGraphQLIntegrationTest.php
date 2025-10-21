<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Integration tests for Customer GraphQL operations.
 *
 * Tests all GraphQL queries and mutations for Customer entity:
 * - Query single customer
 * - Query customers collection
 * - Create customer mutation
 * - Update customer mutation
 * - Delete customer mutation
 */
final class CustomerGraphQLIntegrationTest extends BaseGraphQLIntegrationTest
{
    public function testQuerySingleCustomerSuccess(): void
    {
        $customerData = $this->getCustomerData('John Doe');
        $customerIri = $this->createEntity('/api/customers', $customerData);

        $response = $this->graphqlRequest(
            $this->getSingleCustomerQuery(),
            ['id' => $customerIri]
        );

        $this->assertSingleCustomerResponse($response, $customerIri, $customerData);
    }

    public function testQuerySingleCustomerNotFound(): void
    {
        $nonExistentId = '/api/customers/' . $this->faker->ulid();

        $query = '
            query GetCustomer($id: ID!) {
                customer(id: $id) {
                    id
                    initials
                }
            }
        ';

        $response = $this->graphqlRequest($query, ['id' => $nonExistentId]);

        $this->assertGraphQLSuccess($response);
        $this->assertNull(
            $response['data']['customer'],
            'Non-existent customer should return null'
        );
    }

    public function testQueryCustomersCollectionSuccess(): void
    {
        $customer1Data = $this->getCustomerData('Customer One');
        $customer2Data = $this->getCustomerData('Customer Two');

        $this->createEntity('/api/customers', $customer1Data);
        $this->createEntity('/api/customers', $customer2Data);

        $response = $this->graphqlRequest(
            $this->getCustomersCollectionQuery(),
            ['first' => 10]
        );

        $this->assertCustomersCollectionResponse($response, $customer1Data, $customer2Data);
    }

    public function testCreateCustomerMutationSuccess(): void
    {
        $customerData = $this->getCustomerData('New Customer');

        $response = $this->graphqlMutation(
            $this->getCreateCustomerMutation(),
            $customerData
        );

        $this->assertCreatedCustomer($response, $customerData);
    }

    public function testCreateCustomerMutationValidationError(): void
    {
        // Missing required email field
        $invalidData = [
            'initials' => 'Invalid Customer',
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];

        $mutation = '
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $invalidData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerMutationSuccess(): void
    {
        $initialData = $this->getCustomerData('Original Customer');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'initials' => 'Updated Customer',
            'email' => $this->faker->unique()->safeEmail(),
            'confirmed' => !$initialData['confirmed'],
        ];

        $response = $this->graphqlMutation(
            $this->getUpdateCustomerWithTypeStatusMutation(),
            $updateData
        );

        $this->assertUpdatedCustomer($response, $customerIri, $updateData, $initialData);
    }

    public function testUpdateCustomerMutationNotFound(): void
    {
        $nonExistentUlid = $this->faker->ulid();

        $updateData = [
            'id' => $nonExistentUlid,
            'initials' => 'Updated Customer',
        ];

        $mutation = '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        initials
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerMutationPartialUpdate(): void
    {
        $initialData = $this->getCustomerData('Partial Update Test');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'initials' => 'Only Initials Changed',
        ];

        $response = $this->graphqlMutation(
            $this->getUpdateCustomerWithAllFieldsMutation(),
            $updateData
        );

        $this->assertPartialUpdateResult($response, $customerIri, $updateData, $initialData);
    }

    public function testDeleteCustomerMutationSuccess(): void
    {
        $customerData = $this->getCustomerData('To Be Deleted');
        $customerIri = $this->createEntity('/api/customers', $customerData);

        $response = $this->graphqlMutation(
            $this->getDeleteCustomerMutation(),
            ['id' => $customerIri]
        );

        $this->assertDeletedCustomer($response, $customerIri);
        $this->assertCustomerNoLongerExists($customerIri);
    }

    public function testDeleteCustomerMutationNotFound(): void
    {
        $nonExistentId = '/api/customers/' . $this->faker->ulid();

        $mutation = '
            mutation DeleteCustomer($input: deleteCustomerInput!) {
                deleteCustomer(input: $input) {
                    customer {
                        id
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, ['id' => $nonExistentId]);

        $this->assertGraphQLError($response);
    }

    public function testCustomersCollectionWithFilters(): void
    {
        $this->createConfirmedAndUnconfirmedCustomers();

        $response = $this->graphqlRequest(
            $this->getCustomersWithFilterQuery(),
            ['first' => 10, 'confirmed' => true]
        );

        $this->assertAllCustomersAreConfirmed($response);
    }

    public function testUpdateCustomerMutationWithTypeAndStatus(): void
    {
        $initialData = $this->getCustomerData('Type Status Update Test');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        $newType = $this->createCustomerType('New Type');
        $newStatus = $this->createCustomerStatus('New Status');

        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'type' => $newType,
            'status' => $newStatus,
        ];

        $response = $this->graphqlMutation(
            $this->getUpdateCustomerMutationWithTypeAndStatus(),
            $updateData
        );

        $this->assertUpdateCustomerWithTypeAndStatus($response, $customerIri, $newType, $newStatus);
    }

    public function testUpdateCustomerMutationWithInvalidType(): void
    {
        // Create initial customer
        $initialData = $this->getCustomerData('Invalid Type Test');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        // Update with non-existent type
        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'type' => '/api/customer_types/' . $this->faker->ulid(),
        ];

        $mutation = '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        type {
                            id
                        }
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerMutationWithInvalidStatus(): void
    {
        // Create initial customer
        $initialData = $this->getCustomerData('Invalid Status Test');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        // Update with non-existent status
        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'status' => '/api/customer_statuses/' . $this->faker->ulid(),
        ];

        $mutation = '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        status {
                            id
                        }
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    public function testCreateCustomerMutationWithInvalidType(): void
    {
        $customerData = $this->getCustomerData('Invalid Type Customer');
        $customerData['type'] = '/api/customer_types/' . $this->faker->ulid();

        $mutation = '
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $customerData);

        $this->assertGraphQLError($response);
    }

    public function testCreateCustomerMutationWithInvalidStatus(): void
    {
        $customerData = $this->getCustomerData('Invalid Status Customer');
        $customerData['status'] = '/api/customer_statuses/' . $this->faker->ulid();

        $mutation = '
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $customerData);

        $this->assertGraphQLError($response);
    }

    public function testCreateCustomerMutationWithInvalidEmail(): void
    {
        $customerData = $this->getCustomerData('Invalid Email Customer');
        $customerData['email'] = 'invalid-email';

        $mutation = '
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        email
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $customerData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerMutationWithInvalidEmail(): void
    {
        // Create initial customer
        $initialData = $this->getCustomerData('Email Validation Test');
        $customerIri = $this->createEntity('/api/customers', $initialData);

        // Update with invalid email
        $updateData = [
            'id' => $this->extractUlidFromIri($customerIri),
            'email' => 'invalid-email-format',
        ];

        $mutation = '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        email
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    private function getUpdateCustomerMutationWithTypeAndStatus(): string
    {
        return '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        type {
                            id
                            value
                        }
                        status {
                            id
                            value
                        }
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertUpdateCustomerWithTypeAndStatus(
        array $response,
        string $expectedIri,
        string $expectedType,
        string $expectedStatus
    ): void {
        $this->assertGraphQLSuccess($response);
        $customer = $this->getGraphQLDataField($response, 'updateCustomer.customer');

        $this->assertSame($expectedIri, $customer['id']);
        $this->assertSame($expectedType, $customer['type']['id']);
        $this->assertSame($expectedStatus, $customer['status']['id']);
    }

    private function getUpdateCustomerWithAllFieldsMutation(): string
    {
        return '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        initials
                        email
                        phone
                        leadSource
                        confirmed
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array<string, string|bool> $updateData
     * @param array<string, string|bool> $initialData
     */
    private function assertPartialUpdateResult(
        array $response,
        string $customerIri,
        array $updateData,
        array $initialData
    ): void {
        $this->assertGraphQLSuccess($response);
        $customer = $this->getGraphQLDataField($response, 'updateCustomer.customer');

        $this->assertSame($customerIri, $customer['id']);
        $this->assertSame($updateData['initials'], $customer['initials']);
        $this->assertSame($initialData['email'], $customer['email']);
        $this->assertSame($initialData['phone'], $customer['phone']);
        $this->assertSame($initialData['leadSource'], $customer['leadSource']);
        $this->assertSame($initialData['confirmed'], $customer['confirmed']);
    }

    private function getDeleteCustomerMutation(): string
    {
        return '
            mutation DeleteCustomer($input: deleteCustomerInput!) {
                deleteCustomer(input: $input) {
                    customer {
                        id
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertDeletedCustomer(array $response, string $expectedIri): void
    {
        $this->assertGraphQLSuccess($response);
        $deletedCustomer = $this->getGraphQLDataField($response, 'deleteCustomer.customer');
        $this->assertSame($expectedIri, $deletedCustomer['id']);
    }

    private function assertCustomerNoLongerExists(string $customerIri): void
    {
        $query = '
            query GetCustomer($id: ID!) {
                customer(id: $id) {
                    id
                }
            }
        ';

        $queryResponse = $this->graphqlRequest($query, ['id' => $customerIri]);
        $this->assertGraphQLSuccess($queryResponse);
        $this->assertNull(
            $queryResponse['data']['customer'],
            'Deleted customer should return null'
        );
    }

    private function createConfirmedAndUnconfirmedCustomers(): void
    {
        $confirmedCustomer = $this->getCustomerData('Confirmed Customer');
        $confirmedCustomer['confirmed'] = true;
        $unconfirmedCustomer = $this->getCustomerData('Unconfirmed Customer');
        $unconfirmedCustomer['confirmed'] = false;

        $this->createEntity('/api/customers', $confirmedCustomer);
        $this->createEntity('/api/customers', $unconfirmedCustomer);
    }

    private function getCustomersWithFilterQuery(): string
    {
        return '
            query GetCustomers($first: Int, $confirmed: Boolean) {
                customers(first: $first, confirmed: $confirmed) {
                    edges {
                        node {
                            id
                            initials
                            confirmed
                        }
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertAllCustomersAreConfirmed(array $response): void
    {
        $this->assertGraphQLSuccess($response);
        $customers = $this->getGraphQLDataField($response, 'customers.edges');

        foreach ($customers as $edge) {
            $this->assertTrue(
                $edge['node']['confirmed'],
                'All returned customers should be confirmed'
            );
        }
    }

    private function getCustomersCollectionQuery(): string
    {
        return '
            query GetCustomers($first: Int) {
                customers(first: $first) {
                    edges {
                        node {
                            id
                            initials
                            email
                            confirmed
                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array<string, string|bool> $customer1Data
     * @param array<string, string|bool> $customer2Data
     */
    private function assertCustomersCollectionResponse(
        array $response,
        array $customer1Data,
        array $customer2Data
    ): void {
        $this->assertGraphQLSuccess($response);
        $customers = $this->getGraphQLDataField($response, 'customers');

        $this->assertArrayHasKey('edges', $customers);
        $this->assertArrayHasKey('pageInfo', $customers);
        $this->assertCount(2, $customers['edges']);

        $customerEmails = array_column(
            array_column($customers['edges'], 'node'),
            'email'
        );
        $this->assertContains($customer1Data['email'], $customerEmails);
        $this->assertContains($customer2Data['email'], $customerEmails);
    }

    private function getCreateCustomerMutation(): string
    {
        return '
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                        email
                        phone
                        leadSource
                        confirmed
                        type {
                            id
                        }
                        status {
                            id
                        }
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array<string, string|bool> $customerData
     */
    private function assertCreatedCustomer(array $response, array $customerData): void
    {
        $this->assertGraphQLSuccess($response);
        $customer = $this->getGraphQLDataField($response, 'createCustomer.customer');

        $this->assertNotEmpty($customer['id']);
        $this->assertSame($customerData['initials'], $customer['initials']);
        $this->assertSame($customerData['email'], $customer['email']);
        $this->assertSame($customerData['phone'], $customer['phone']);
        $this->assertSame($customerData['leadSource'], $customer['leadSource']);
        $this->assertSame($customerData['confirmed'], $customer['confirmed']);
        $this->assertSame($customerData['type'], $customer['type']['id']);
        $this->assertSame($customerData['status'], $customer['status']['id']);
    }

    private function getUpdateCustomerWithTypeStatusMutation(): string
    {
        return '
            mutation UpdateCustomer($input: updateCustomerInput!) {
                updateCustomer(input: $input) {
                    customer {
                        id
                        initials
                        email
                        confirmed
                        type {
                            id
                        }
                        status {
                            id
                        }
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array<string, string|bool> $updateData
     * @param array<string, string|bool> $initialData
     */
    private function assertUpdatedCustomer(
        array $response,
        string $customerIri,
        array $updateData,
        array $initialData
    ): void {
        $this->assertGraphQLSuccess($response);
        $customer = $this->getGraphQLDataField($response, 'updateCustomer.customer');

        $this->assertSame($customerIri, $customer['id']);
        $this->assertSame($updateData['initials'], $customer['initials']);
        $this->assertSame($updateData['email'], $customer['email']);
        $this->assertSame($updateData['confirmed'], $customer['confirmed']);
        $this->assertSame($initialData['type'], $customer['type']['id']);
        $this->assertSame($initialData['status'], $customer['status']['id']);
    }

    private function getSingleCustomerQuery(): string
    {
        return '
            query GetCustomer($id: ID!) {
                customer(id: $id) {
                    id
                    initials
                    email
                    phone
                    leadSource
                    confirmed
                    type {
                        id
                        value
                    }
                    status {
                        id
                        value
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array<string, string|bool> $customerData
     */
    private function assertSingleCustomerResponse(
        array $response,
        string $customerIri,
        array $customerData
    ): void {
        $this->assertGraphQLSuccess($response);
        $customer = $this->getGraphQLDataField($response, 'customer');

        $this->assertSame($customerIri, $customer['id']);
        $this->assertSame($customerData['initials'], $customer['initials']);
        $this->assertSame($customerData['email'], $customer['email']);
        $this->assertSame($customerData['phone'], $customer['phone']);
        $this->assertSame($customerData['leadSource'], $customer['leadSource']);
        $this->assertSame($customerData['confirmed'], $customer['confirmed']);
        $this->assertSame($customerData['type'], $customer['type']['id']);
        $this->assertSame($customerData['status'], $customer['status']['id']);
    }
}
