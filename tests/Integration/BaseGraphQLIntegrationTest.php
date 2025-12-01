<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Base class for GraphQL integration tests.
 *
 * Provides common functionality for testing GraphQL queries and mutations
 * including request helpers, response validation, and assertion methods.
 */
abstract class BaseGraphQLIntegrationTest extends BaseGraphQLTest
{
    protected const GRAPHQL_ENDPOINT = '/api/graphql';

    /**
     * Execute a GraphQL query or mutation.
     *
     * @param array<string, string|int|float|bool|array|null> $variables
     * @param array<string, string> $headers
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    protected function graphqlRequest(
        string $query,
        array $variables = [],
        array $headers = []
    ): array {
        $client = self::createClient();

        $defaultHeaders = [
            'Content-Type' => 'application/json',
        ];
        $headers = array_merge($defaultHeaders, $headers);

        $payload = ['query' => $query];
        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        $response = $client->request('POST', self::GRAPHQL_ENDPOINT, [
            'headers' => $headers,
            'body' => json_encode($payload),
        ]);

        return $response->toArray();
    }

    /**
     * Execute a GraphQL mutation with input variables.
     *
     * @param array<string, string|int|float|bool|array|null> $input
     * @param array<string, string> $headers
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    protected function graphqlMutation(
        string $mutation,
        array $input,
        array $headers = []
    ): array {
        return $this->graphqlRequest($mutation, ['input' => $input], $headers);
    }

    /**
     * Assert that GraphQL response is successful (no errors).
     *
     * @param array<string, string|int|float|bool|array|null> $response
     */
    protected function assertGraphQLSuccess(array $response): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey(
            'errors',
            $response,
            'GraphQL response should not contain errors: ' . json_encode($response['errors'] ?? [])
        );
        $this->assertArrayHasKey(
            'data',
            $response,
            'GraphQL response should contain data'
        );
    }

    /**
     * Assert that GraphQL response contains specific errors.
     *
     * @param array<string, string|int|float|bool|array|null> $response
     */
    protected function assertGraphQLError(array $response, ?string $expectedMessage = null): void
    {
        $this->assertArrayHasKey(
            'errors',
            $response,
            'GraphQL response should contain errors'
        );
        $this->assertIsArray($response['errors']);
        $this->assertNotEmpty($response['errors']);

        if ($expectedMessage !== null) {
            $errorMessages = array_column($response['errors'], 'message');
            $this->assertContains(
                $expectedMessage,
                $errorMessages,
                'Expected error message not found in: ' . json_encode($errorMessages)
            );
        }
    }

    /**
     * Assert that GraphQL response data contains expected field.
     *
     * @param array<string, string|int|float|bool|array|null> $response
     */
    protected function assertGraphQLDataHasField(array $response, string $field): void
    {
        $this->assertGraphQLSuccess($response);
        $this->assertArrayHasKey(
            $field,
            $response['data'],
            "GraphQL data should contain field '{$field}'"
        );
    }

    /**
     * Assert that GraphQL response data field equals expected value.
     *
     * @param array<string, string|int|float|bool|array|null> $response
     */
    protected function assertGraphQLDataFieldEquals(
        array $response,
        string $field,
        array|string|int|float|bool|null $expectedValue
    ): void {
        $this->assertGraphQLDataHasField($response, $field);
        $this->assertSame(
            $expectedValue,
            $response['data'][$field],
            "GraphQL data field '{$field}' should equal expected value"
        );
    }

    /**
     * Extract nested field value from GraphQL response data.
     *
     * @param array<string, string|int|float|bool|array|null> $response
     */
    protected function getGraphQLDataField(
        array $response,
        string $path
    ): array|string|int|float|bool|null {
        $this->assertGraphQLSuccess($response);

        $keys = explode('.', $path);
        $data = $response['data'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $data,
                "GraphQL data path '{$path}' not found at key '{$key}'"
            );
            $data = $data[$key];
        }

        return $data;
    }

    /**
     * Create a customer type for testing.
     */
    protected function createCustomerType(?string $value = null): string
    {
        $value = $value ?? $this->faker->word();
        $payload = ['value' => $value];
        return $this->createEntity('/api/customer_types', $payload);
    }

    /**
     * Create a customer status for testing.
     */
    protected function createCustomerStatus(?string $value = null): string
    {
        $value = $value ?? $this->faker->word();
        $payload = ['value' => $value];
        return $this->createEntity('/api/customer_statuses', $payload);
    }

    /**
     * Generate customer test data.
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    protected function getCustomerData(?string $initials = null): array
    {
        return [
            'initials' => $initials ?? $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /**
     * Generate customer type test data.
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    protected function getCustomerTypeData(?string $value = null): array
    {
        return [
            'value' => $value ?? $this->faker->word(),
        ];
    }

    /**
     * Generate customer status test data.
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    protected function getCustomerStatusData(?string $value = null): array
    {
        return [
            'value' => $value ?? $this->faker->word(),
        ];
    }

}
