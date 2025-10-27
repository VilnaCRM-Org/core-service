<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class GraphQLContext implements Context, SnippetAcceptingContext
{
    private ?Response $response = null;

    /** @var array<string, string|int|bool|float|array|null>|null */
    private ?array $responseData = null;

    public function __construct(
        private readonly KernelInterface $kernel
    ) {
    }

    /**
     * @When I send the following GraphQL query:
     * @When I send the following GraphQL mutation:
     */
    public function iSendTheFollowingGraphQLQuery(PyStringNode $query): void
    {
        $this->sendGraphQLRequest($query->getRaw());
    }

    /**
     * @When I send a GraphQL query :query
     */
    public function iSendAGraphQLQuery(string $query): void
    {
        $this->sendGraphQLRequest($query);
    }

    /**
     * @When I send the following GraphQL request with variables:
     */
    public function iSendGraphQLRequestWithVariables(PyStringNode $requestBody): void
    {
        $body = json_decode($requestBody->getRaw(), true);
        $this->sendGraphQLRequest($body['query'], $body['variables'] ?? []);
    }

    /**
     * @When I send a GraphQL query with first :first and after endCursor
     */
    public function iSendGraphQLQueryWithPagination(int $first): void
    {
        $endCursor = $this->getFieldValue($this->responseData, 'data.customers.pageInfo.endCursor');

        $query = sprintf(
            '{
                customers(first: %d, after: "%s") {
                    edges {
                        node {
                            id
                            email
                        }
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }',
            $first,
            $endCursor
        );

        $this->sendGraphQLRequest($query);
    }

    /**
     * @Then the GraphQL response status code should be :statusCode
     */
    public function theGraphQLResponseStatusCodeShouldBe(int $statusCode): void
    {
        if ($this->response === null) {
            throw new \RuntimeException('No response received');
        }

        $actualStatusCode = $this->response->getStatusCode();
        if ($actualStatusCode !== $statusCode) {
            throw new \RuntimeException(
                sprintf('Expected status code %d, got %d', $statusCode, $actualStatusCode)
            );
        }
    }

    /**
     * @Then the GraphQL response should contain :field
     */
    public function theGraphQLResponseShouldContain(string $field): void
    {
        $this->ensureResponseDataAvailable();

        if (! $this->hasField($this->responseData, $field)) {
            throw new \RuntimeException(
                sprintf('Field "%s" not found in response', $field)
            );
        }
    }

    /**
     * @Then the GraphQL response :path should be :value
     */
    public function theGraphQLResponseFieldShouldBe(string $path, string $value): void
    {
        $this->ensureResponseDataAvailable();
        $actualValue = $this->getFieldValue($this->responseData, $path);

        if ($this->isBooleanValue($value)) {
            $this->assertBooleanValue($path, $value, $actualValue);
            return;
        }

        if ($this->isNullValue($value)) {
            $this->assertNullValue($path, $actualValue);
            return;
        }

        $this->assertStringValue($path, $value, $actualValue);
    }

    /**
     * @Then the GraphQL response :path should contain :value
     */
    public function theGraphQLResponseFieldShouldContain(string $path, string $value): void
    {
        $this->ensureResponseDataAvailable();

        $actualValue = $this->getFieldValue($this->responseData, $path);

        if (! str_contains((string) $actualValue, $value)) {
            throw new \RuntimeException(
                sprintf('Expected %s to contain "%s", got "%s"', $path, $value, $actualValue)
            );
        }
    }

    /**
     * @Then the GraphQL response :path should match regex :pattern
     */
    public function theGraphQLResponseFieldShouldMatchRegex(string $path, string $pattern): void
    {
        $this->ensureResponseDataAvailable();

        $actualValue = $this->getFieldValue($this->responseData, $path);

        if (preg_match($pattern, (string) $actualValue) !== 1) {
            throw new \RuntimeException(
                sprintf('Expected %s to match pattern "%s", got "%s"', $path, $pattern, $actualValue)
            );
        }
    }

    /**
     * @Then the GraphQL error :index message should contain :message
     */
    public function theGraphQLErrorAtIndexMessageShouldContain(int $index, string $message): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors'][$index]['message'])) {
            throw new \RuntimeException(
                sprintf('No error found at index %d', $index)
            );
        }

        $errorMessage = $this->responseData['errors'][$index]['message'];
        if (! str_contains($errorMessage, $message)) {
            throw new \RuntimeException(
                sprintf('Expected error message to contain "%s", got "%s"', $message, $errorMessage)
            );
        }
    }

    /**
     * @Then the GraphQL error :index extensions code should be :code
     */
    public function theGraphQLErrorExtensionsCodeShouldBe(int $index, string $code): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors'][$index]['extensions']['code'])) {
            throw new \RuntimeException(
                sprintf('No error extensions code found at index %d', $index)
            );
        }

        $actualCode = $this->responseData['errors'][$index]['extensions']['code'];
        if ($actualCode !== $code) {
            throw new \RuntimeException(
                sprintf('Expected error code "%s", got "%s"', $code, $actualCode)
            );
        }
    }

    /**
     * @Then the GraphQL error :index path :pathIndex should be :value
     */
    public function theGraphQLErrorPathShouldBe(int $index, int $pathIndex, string $value): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors'][$index]['path'][$pathIndex])) {
            throw new \RuntimeException(
                sprintf('No error path found at index %d, path index %d', $index, $pathIndex)
            );
        }

        $actualPath = $this->responseData['errors'][$index]['path'][$pathIndex];
        if ($actualPath !== $value) {
            throw new \RuntimeException(
                sprintf('Expected error path "%s", got "%s"', $value, $actualPath)
            );
        }
    }

    /**
     * @Then the GraphQL response should not have errors
     */
    public function theGraphQLResponseShouldNotHaveErrors(): void
    {
        $this->ensureResponseDataAvailable();

        if (isset($this->responseData['errors'])) {
            $errors = json_encode($this->responseData['errors'], JSON_PRETTY_PRINT);
            throw new \RuntimeException(
                sprintf('GraphQL response contains errors: %s', $errors)
            );
        }
    }

    /**
     * @Then the GraphQL response should have errors
     */
    public function theGraphQLResponseShouldHaveErrors(): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors'])) {
            throw new \RuntimeException(
                'Expected GraphQL response to contain errors, but none found'
            );
        }
    }

    /**
     * @Then the GraphQL response errors should contain field :field
     */
    public function theGraphQLResponseErrorsShouldContainField(string $field): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors']) || empty($this->responseData['errors'])) {
            throw new \RuntimeException('No errors in GraphQL response');
        }

        $errorsString = json_encode($this->responseData['errors']);

        if (strpos($errorsString, '"' . $field . '"') === false) {
            throw new \RuntimeException(
                sprintf('Field "%s" not found in GraphQL errors', $field)
            );
        }
    }

    /**
     * @Then the GraphQL error message should contain :message
     */
    public function theGraphQLErrorMessageShouldContain(string $message): void
    {
        $this->ensureResponseDataAvailable();

        if (! isset($this->responseData['errors'])) {
            throw new \RuntimeException('No errors in GraphQL response');
        }

        $found = false;
        foreach ($this->responseData['errors'] as $error) {
            if (isset($error['message']) && str_contains($error['message'], $message)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \RuntimeException(
                sprintf('Error message "%s" not found in errors', $message)
            );
        }
    }

    /**
     * @Then the GraphQL response data should be empty
     */
    public function theGraphQLResponseDataShouldBeEmpty(): void
    {
        $this->ensureResponseDataAvailable();

        if (($this->responseData['data'] ?? null) !== null && $this->responseData['data'] !== []) {
            throw new \RuntimeException(
                sprintf('Expected empty data, got: %s', json_encode($this->responseData['data']))
            );
        }
    }

    /**
     * @param array<string, string|int|bool|float|array|null> $variables
     */
    private function sendGraphQLRequest(string $query, array $variables = []): void
    {
        $requestData = ['query' => $query];
        if ($variables !== []) {
            $requestData['variables'] = $variables;
        }

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->response = $this->kernel->handle($request);
        $content = $this->response->getContent();

        if ($content !== false) {
            $this->responseData = json_decode($content, true);
        }
    }

    private function ensureResponseDataAvailable(): void
    {
        if ($this->responseData === null) {
            throw new \RuntimeException('No GraphQL response data available');
        }
    }

    private function isBooleanValue(string $value): bool
    {
        return in_array(strtolower($value), ['true', 'false'], true);
    }

    private function isNullValue(string $value): bool
    {
        return strtolower($value) === 'null';
    }

    private function assertBooleanValue(string $path, string $value, mixed $actualValue): void
    {
        $expectedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($actualValue !== $expectedValue) {
            throw new \RuntimeException(
                sprintf(
                    'Expected %s to be %s, got %s',
                    $path,
                    var_export($expectedValue, true),
                    var_export($actualValue, true)
                )
            );
        }
    }

    private function assertNullValue(string $path, mixed $actualValue): void
    {
        if ($actualValue !== null) {
            throw new \RuntimeException(
                sprintf('Expected %s to be null, got %s', $path, var_export($actualValue, true))
            );
        }
    }

    private function assertStringValue(
        string $path,
        string $expectedValue,
        mixed $actualValue
    ): void {
        if ((string) $actualValue !== $expectedValue) {
            throw new \RuntimeException(
                sprintf('Expected %s to be "%s", got "%s"', $path, $expectedValue, $actualValue)
            );
        }
    }

    /**
     * @param array<string, string|int|bool|float|array|null> $data
     */
    private function hasField(array $data, string $path): bool
    {
        try {
            $this->getFieldValue($data, $path);
            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * @param array<string, string|int|bool|float|array|null> $data
     *
     * @return string|int|bool|float|array<array-key, string|int|bool|float|array|null>|null
     */
    private function getFieldValue(array $data, string $path): string|int|bool|float|array|null
    {
        $parts = explode('.', $path);
        $current = $data;

        foreach ($parts as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                throw new \RuntimeException(
                    sprintf('Path "%s" not found in response', $path)
                );
            }
            $current = $current[$part];
        }

        return $current;
    }
}
