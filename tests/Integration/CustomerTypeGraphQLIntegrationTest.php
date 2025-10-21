<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Integration tests for CustomerType GraphQL operations.
 */
final class CustomerTypeGraphQLIntegrationTest extends BaseGraphQLIntegrationTest
{
    public function testQuerySingleCustomerTypeSuccess(): void
    {
        $typeData = $this->getCustomerTypeData('Premium');
        $typeIri = $this->createEntity('/api/customer_types', $typeData);

        $query = '
            query GetCustomerType($id: ID!) {
                customerType(id: $id) {
                    id
                    value
                }
            }
        ';

        $response = $this->graphqlRequest($query, ['id' => $typeIri]);

        $this->assertGraphQLSuccess($response);
        $customerType = $this->getGraphQLDataField($response, 'customerType');

        $this->assertSame($typeIri, $customerType['id']);
        $this->assertSame($typeData['value'], $customerType['value']);
    }

    public function testQuerySingleCustomerTypeNotFound(): void
    {
        $nonExistentId = '/api/customer_types/' . $this->faker->ulid();

        $query = '
            query GetCustomerType($id: ID!) {
                customerType(id: $id) {
                    id
                    value
                }
            }
        ';

        $response = $this->graphqlRequest($query, ['id' => $nonExistentId]);

        $this->assertGraphQLSuccess($response);
        $this->assertNull(
            $response['data']['customerType'],
            'Customer type should be null for non-existent id'
        );
    }

    public function testCreateCustomerTypeMutationSuccess(): void
    {
        $typeData = $this->getCustomerTypeData('VIP');
        $response = $this->graphqlMutation($this->getCreateCustomerTypeMutation(), $typeData);
        $this->assertCreatedCustomerType($response, $typeData['value']);
    }

    public function testUpdateCustomerTypeMutationSuccess(): void
    {
        $initialData = $this->getCustomerTypeData('Original Type');
        $typeIri = $this->createEntity('/api/customer_types', $initialData);

        $updateData = [
            'id' => $this->extractUlidFromIri($typeIri),
            'value' => 'Updated Type',
        ];

        $response = $this->graphqlMutation($this->getUpdateCustomerTypeMutation(), $updateData);
        $this->assertUpdatedCustomerType($response, $typeIri, $updateData['value']);
    }

    public function testDeleteCustomerTypeMutationSuccess(): void
    {
        $typeData = $this->getCustomerTypeData('To Be Deleted');
        $typeIri = $this->createEntity('/api/customer_types', $typeData);

        $response = $this->graphqlMutation(
            $this->getDeleteCustomerTypeMutation(),
            ['id' => $typeIri]
        );
        $this->assertDeletedCustomerType($response, $typeIri);

        $queryResponse = $this->graphqlRequest($this->getCustomerTypeQuery(), ['id' => $typeIri]);
        $this->assertGraphQLSuccess($queryResponse);
        $this->assertNull(
            $queryResponse['data']['customerType'],
            'Type should be null after deletion'
        );
    }

    public function testCreateCustomerTypeMutationValidationError(): void
    {
        // Missing required value field
        $invalidData = [];

        $mutation = '
            mutation CreateCustomerType($input: createCustomerTypeInput!) {
                createCustomerType(input: $input) {
                    customerType {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $invalidData);

        $this->assertGraphQLError($response);
    }

    public function testUpdateCustomerTypeMutationNotFound(): void
    {
        $nonExistentId = $this->faker->ulid();

        $updateData = [
            'id' => $nonExistentId,
            'value' => 'Updated Type',
        ];

        $mutation = '
            mutation UpdateCustomerType($input: updateCustomerTypeInput!) {
                updateCustomerType(input: $input) {
                    customerType {
                        id
                        value
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, $updateData);

        $this->assertGraphQLError($response);
    }

    public function testDeleteCustomerTypeMutationNotFound(): void
    {
        $nonExistentId = '/api/customer_types/' . $this->faker->ulid();

        $mutation = '
            mutation DeleteCustomerType($input: deleteCustomerTypeInput!) {
                deleteCustomerType(input: $input) {
                    customerType {
                        id
                    }
                }
            }
        ';

        $response = $this->graphqlMutation($mutation, ['id' => $nonExistentId]);

        $this->assertGraphQLError($response);
    }

    public function testQueryCustomerTypesCollectionSuccess(): void
    {
        $created = $this->createThreeCustomerTypes();
        $response = $this->graphqlRequest($this->getCustomerTypesQuery(), ['first' => 10]);
        $this->assertCustomerTypesCollection($response, $created);
    }

    private function getCreateCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation CreateCustomerType($input: createCustomerTypeInput!) {
                createCustomerType(input: $input) { customerType { id value } }
            }
        GQL;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertCreatedCustomerType(array $response, string $expectedValue): void
    {
        $this->assertGraphQLSuccess($response);
        $customerType = $this->getGraphQLDataField($response, 'createCustomerType.customerType');
        $this->assertNotEmpty($customerType['id']);
        $this->assertSame($expectedValue, $customerType['value']);
    }

    private function getUpdateCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation UpdateCustomerType($input: updateCustomerTypeInput!) {
                updateCustomerType(input: $input) {
                    customerType { id value }
                }
            }
        GQL;
    }

    private function getDeleteCustomerTypeMutation(): string
    {
        return <<<'GQL'
            mutation DeleteCustomerType($input: deleteCustomerTypeInput!) {
                deleteCustomerType(input: $input) { customerType { id } }
            }
        GQL;
    }

    private function getCustomerTypeQuery(): string
    {
        return <<<'GQL'
            query GetCustomerType($id: ID!) { customerType(id: $id) { id } }
        GQL;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertUpdatedCustomerType(
        array $response,
        string $expectedIri,
        string $expectedValue
    ): void {
        $this->assertGraphQLSuccess($response);
        $customerType = $this->getGraphQLDataField($response, 'updateCustomerType.customerType');
        $this->assertSame($expectedIri, $customerType['id']);
        $this->assertSame($expectedValue, $customerType['value']);
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $response
     */
    private function assertDeletedCustomerType(array $response, string $expectedIri): void
    {
        $this->assertGraphQLSuccess($response);
        $deletedType = $this->getGraphQLDataField($response, 'deleteCustomerType.customerType');
        $this->assertSame($expectedIri, $deletedType['id']);
    }

    /**
     * @return array{values: array<int, string>}
     */
    private function createThreeCustomerTypes(): array
    {
        $type1Data = $this->getCustomerTypeData('Standard');
        $type2Data = $this->getCustomerTypeData('Premium');
        $type3Data = $this->getCustomerTypeData('Enterprise');

        $this->createEntity('/api/customer_types', $type1Data);
        $this->createEntity('/api/customer_types', $type2Data);
        $this->createEntity('/api/customer_types', $type3Data);

        return ['values' => [$type1Data['value'], $type2Data['value'], $type3Data['value']]];
    }

    private function getCustomerTypesQuery(): string
    {
        return <<<'GQL'
            query GetCustomerTypes($first: Int) {
                customerTypes(first: $first) {
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
    private function assertCustomerTypesCollection(array $response, array $created): void
    {
        $this->assertGraphQLSuccess($response);
        $customerTypes = $this->getGraphQLDataField($response, 'customerTypes');

        $this->assertArrayHasKey('edges', $customerTypes);
        $this->assertArrayHasKey('pageInfo', $customerTypes);
        $this->assertCount(3, $customerTypes['edges']);

        $typeValues = array_column(
            array_column($customerTypes['edges'], 'node'),
            'value'
        );
        foreach ($created['values'] as $val) {
            $this->assertContains($val, $typeValues);
        }
    }
}
