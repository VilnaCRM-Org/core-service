<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Integration tests for CustomerStatus GraphQL operations.
 */
final class CustomerStatusGraphQLTest extends BaseGraphQLTest
{
    public function testQuerySingleCustomerStatusSuccess(): void
    {
        $statusData = $this->getCustomerStatusData('Active');
        $statusIri = $this->createEntity('/api/customer_statuses', $statusData);

        $query = '
            query GetCustomerStatus($id: ID!) {
                customerStatus(id: $id) {
                    id
                    value
                }
            }
        ';

        $response = $this->graphqlRequest($query, ['id' => $statusIri]);

        $this->assertGraphQLSuccess($response);
        $customerStatus = $this->getGraphQLDataField($response, 'customerStatus');

        $this->assertSame($statusIri, $customerStatus['id']);
        $this->assertSame($statusData['value'], $customerStatus['value']);
    }

    public function testQuerySingleCustomerStatusNotFound(): void
    {
        $nonExistentId = '/api/customer_statuses/' . $this->faker->ulid();

        $query = '
            query GetCustomerStatus($id: ID!) {
                customerStatus(id: $id) {
                    id
                    value
                }
            }
        ';

        $response = $this->graphqlRequest($query, ['id' => $nonExistentId]);

        $this->assertGraphQLSuccess($response);
        $this->assertNull(
            $response['data']['customerStatus'],
            'Customer status should be null for non-existent id'
        );
    }

    public function testCreateCustomerStatusMutationSuccess(): void
    {
        $statusData = $this->getCustomerStatusData('New Status');

        $mutation = '
            mutation CreateCustomerStatus($input: createCustomerStatusInput!) {
                createCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $statusData);

        $this->assertGraphQLSuccess($response);
        $customerStatus = $this->getGraphQLDataField(
            $response,
            'createCustomerStatus.customerStatus'
        );

        $this->assertNotEmpty($customerStatus['id']);
        $this->assertSame($statusData['value'], $customerStatus['value']);
    }

    public function testCreateCustomerStatusMutationDuplicateValue(): void
    {
        // Create initial customer status
        $statusData = $this->getCustomerStatusData('Duplicate');
        $this->createEntity('/api/customer_statuses', $statusData);

        // Try to create another with same value
        // Should succeed as there's no unique constraint
        $mutation = '
            mutation CreateCustomerStatus($input: createCustomerStatusInput!) {
                createCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $statusData);

        // Should succeed as there's no unique constraint on value
        $this->assertGraphQLSuccess($response);
        $customerStatus = $this->getGraphQLDataField(
            $response,
            'createCustomerStatus.customerStatus'
        );
        $this->assertSame($statusData['value'], $customerStatus['value']);
    }

    public function testUpdateCustomerStatusMutationSuccess(): void
    {
        $initialData = $this->getCustomerStatusData('Original Status');
        $statusIri = $this->createEntity('/api/customer_statuses', $initialData);

        $updateData = [
            'id' => $statusIri,
            'value' => 'Updated Status',
        ];

        $response = $this->graphqlMutation(
            $this->getUpdateCustomerStatusMutation(),
            $updateData
        );

        $this->assertUpdatedCustomerStatus($response, $statusIri, $updateData['value']);
    }

    public function testDeleteCustomerStatusMutationSuccess(): void
    {
        $statusData = $this->getCustomerStatusData('To Be Deleted');
        $statusIri = $this->createEntity('/api/customer_statuses', $statusData);

        $response = $this->graphqlMutation(
            $this->getDeleteCustomerStatusMutation(),
            ['id' => $statusIri]
        );
        $this->assertDeletedCustomerStatus($response, $statusIri);

        $queryResponse = $this->graphqlRequest(
            $this->getCustomerStatusQuery(),
            ['id' => $statusIri]
        );
        $this->assertGraphQLSuccess($queryResponse);
        $this->assertNull(
            $queryResponse['data']['customerStatus'],
            'Status should be null after deletion'
        );
    }

    public function testCreateCustomerStatusMutationValidationError(): void
    {
        // Missing required value field
        $invalidData = [];

        $mutation = '
            mutation CreateCustomerStatus($input: createCustomerStatusInput!) {
                createCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $invalidData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerStatusMutationNotFound(): void
    {
        $nonExistentId = $this->faker->ulid();

        $updateData = [
            'id' => '/api/customer_statuses/' . $nonExistentId,
            'value' => 'Updated Status',
        ];

        $mutation = '
            mutation UpdateCustomerStatus($input: updateCustomerStatusInput!) {
                updateCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    public function testDeleteCustomerStatusMutationNotFound(): void
    {
        $nonExistentId = '/api/customer_statuses/' . $this->faker->ulid();

        $mutation = '
            mutation DeleteCustomerStatus($input: deleteCustomerStatusInput!) {
                deleteCustomerStatus(input: $input) {
                    customerStatus {
                        id
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, ['id' => $nonExistentId]);

        $this->assertGraphQLError($response);
    }

    public function testQueryCustomerStatusesCollectionSuccess(): void
    {
        $created = $this->createThreeCustomerStatuses();
        $response = $this->graphqlRequest($this->getCustomerStatusesQuery(), ['first' => 10]);
        $this->assertCustomerStatusesCollection($response, $created);
    }

    private function getDeleteCustomerStatusMutation(): string
    {
        return <<<'GQL'
            mutation DeleteCustomerStatus($input: deleteCustomerStatusInput!) {
                deleteCustomerStatus(input: $input) { customerStatus { id } }
            }
        GQL;
    }

    private function getCustomerStatusQuery(): string
    {
        return <<<'GQL'
            query GetCustomerStatus($id: ID!) { customerStatus(id: $id) { id } }
        GQL;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertDeletedCustomerStatus(array $response, string $expectedIri): void
    {
        $this->assertGraphQLSuccess($response);
        $deletedStatus = $this->getGraphQLDataField(
            $response,
            'deleteCustomerStatus.customerStatus'
        );
        $this->assertSame($expectedIri, $deletedStatus['id']);
    }

    /**
     * @return array{values: array<int, string>}
     */
    private function createThreeCustomerStatuses(): array
    {
        $s1 = $this->getCustomerStatusData('Active');
        $s2 = $this->getCustomerStatusData('Inactive');
        $s3 = $this->getCustomerStatusData('Pending');

        $this->createEntity('/api/customer_statuses', $s1);
        $this->createEntity('/api/customer_statuses', $s2);
        $this->createEntity('/api/customer_statuses', $s3);

        return ['values' => [$s1['value'], $s2['value'], $s3['value']]];
    }

    private function getCustomerStatusesQuery(): string
    {
        return <<<'GQL'
            query GetCustomerStatuses($first: Int) {
                customerStatuses(first: $first) {
                    edges { node { id value } }
                    pageInfo { hasNextPage hasPreviousPage }
                }
            }
        GQL;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     * @param array{values: array<int, string>} $created
     */
    private function assertCustomerStatusesCollection(array $response, array $created): void
    {
        $this->assertGraphQLSuccess($response);
        $customerStatuses = $this->getGraphQLDataField($response, 'customerStatuses');

        $this->assertArrayHasKey('edges', $customerStatuses);
        $this->assertArrayHasKey('pageInfo', $customerStatuses);
        $this->assertCount(3, $customerStatuses['edges']);

        $statusValues = array_column(
            array_column($customerStatuses['edges'], 'node'),
            'value'
        );
        foreach ($created['values'] as $val) {
            $this->assertContains($val, $statusValues);
        }
    }

    private function getUpdateCustomerStatusMutation(): string
    {
        return '
            mutation UpdateCustomerStatus($input: updateCustomerStatusInput!) {
                updateCustomerStatus(input: $input) {
                    customerStatus {
                        id
                        value
                    }
                }
            }
        ';
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertUpdatedCustomerStatus(
        array $response,
        string $expectedIri,
        string $expectedValue
    ): void {
        $this->assertGraphQLSuccess($response);
        $customerStatus = $this->getGraphQLDataField(
            $response,
            'updateCustomerStatus.customerStatus'
        );

        $this->assertSame($expectedIri, $customerStatus['id']);
        $this->assertSame($expectedValue, $customerStatus['value']);
    }
}
