<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext;

use App\Tests\Behat\GraphQLContext\Assertion\ValueAssertionChain;
use App\Tests\Behat\GraphQLContext\Service\ErrorValidator;
use App\Tests\Behat\GraphQLContext\Service\GraphQLRequestSender;
use App\Tests\Behat\GraphQLContext\Service\ResponseDataAccessor;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class GraphQLContext implements Context, SnippetAcceptingContext
{
    private ?Response $response = null;

    /** @var array<string, string|int|bool|float|array|null>|null */
    private ?array $responseData = null;

    private readonly GraphQLRequestSender $requestSender;
    private readonly ResponseDataAccessor $dataAccessor;
    private readonly ErrorValidator $errorValidator;
    private readonly ValueAssertionChain $valueAssertion;

    public function __construct(KernelInterface $kernel)
    {
        $this->requestSender = new GraphQLRequestSender($kernel);
        $this->dataAccessor = new ResponseDataAccessor();
        $this->errorValidator = new ErrorValidator();
        $this->valueAssertion = new ValueAssertionChain();
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
        $this->ensureResponseDataAvailable();
        $endCursorPath = 'data.customers.pageInfo.endCursor';
        $endCursor = $this->dataAccessor->getFieldValue($this->responseData, $endCursorPath);
        $query = $this->buildPaginationQuery($first, $endCursor);
        $this->sendGraphQLRequest($query);
    }

    /**
     * @Then the GraphQL response status code should be :statusCode
     */
    public function theGraphQLResponseStatusCodeShouldBe(int $statusCode): void
    {
        Assert::notNull($this->response, 'No response received');

        $actualStatusCode = $this->response->getStatusCode();
        Assert::eq(
            $actualStatusCode,
            $statusCode,
            sprintf('Expected status code %d, got %d', $statusCode, $actualStatusCode)
        );
    }

    /**
     * @Then the GraphQL response should contain :field
     */
    public function theGraphQLResponseShouldContain(string $field): void
    {
        $this->ensureResponseDataAvailable();

        Assert::true(
            $this->dataAccessor->hasField($this->responseData, $field),
            sprintf('Field "%s" not found in response', $field)
        );
    }

    /**
     * @Then the GraphQL response :path should be :value
     */
    public function theGraphQLResponseFieldShouldBe(string $path, string $value): void
    {
        $this->ensureResponseDataAvailable();
        $actualValue = $this->dataAccessor->getFieldValue($this->responseData, $path);
        $this->valueAssertion->assert($path, $value, $actualValue);
    }

    /**
     * @Then the GraphQL response :path should contain :value
     */
    public function theGraphQLResponseFieldShouldContain(string $path, string $value): void
    {
        $this->ensureResponseDataAvailable();
        $this->dataAccessor->assertFieldContains($this->responseData, $path, $value);
    }

    /**
     * @Then the GraphQL response :path should match regex :pattern
     */
    public function theGraphQLResponseFieldShouldMatchRegex(string $path, string $pattern): void
    {
        $this->ensureResponseDataAvailable();
        $this->dataAccessor->assertFieldMatchesRegex($this->responseData, $path, $pattern);
    }

    /**
     * @Then the GraphQL error :index message should contain :message
     */
    public function theGraphQLErrorAtIndexMessageShouldContain(int $index, string $message): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertErrorMessageContains($this->responseData, $index, $message);
    }

    /**
     * @Then the GraphQL error :index extensions code should be :code
     */
    public function theGraphQLErrorExtensionsCodeShouldBe(int $index, string $code): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertErrorExtensionsCode($this->responseData, $index, $code);
    }

    /**
     * @Then the GraphQL error :index path :pathIndex should be :value
     */
    public function theGraphQLErrorPathShouldBe(int $index, int $pathIndex, string $value): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertErrorPath($this->responseData, $index, $pathIndex, $value);
    }

    /**
     * @Then the GraphQL response should not have errors
     */
    public function theGraphQLResponseShouldNotHaveErrors(): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertNoErrors($this->responseData);
    }

    /**
     * @Then the GraphQL response should have errors
     */
    public function theGraphQLResponseShouldHaveErrors(): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertHasErrors($this->responseData);
    }

    /**
     * @Then the GraphQL response errors should contain field :field
     */
    public function theGraphQLResponseErrorsShouldContainField(string $field): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertErrorsContainField($this->responseData, $field);
    }

    /**
     * @Then the GraphQL error message should contain :message
     */
    public function theGraphQLErrorMessageShouldContain(string $message): void
    {
        $this->ensureResponseDataAvailable();
        $this->errorValidator->assertAnyErrorContainsMessage($this->responseData, $message);
    }

    /**
     * @Then the GraphQL response data should be empty
     */
    public function theGraphQLResponseDataShouldBeEmpty(): void
    {
        $this->ensureResponseDataAvailable();

        $data = $this->responseData['data'] ?? null;
        Assert::true(
            $data === null || $data === [],
            sprintf('Expected empty data, got: %s', json_encode($data))
        );
    }

    /**
     * @Then the GraphQL response :path should have :count items
     */
    public function theGraphQLResponseShouldHaveItems(string $path, int $count): void
    {
        $this->ensureResponseDataAvailable();
        $this->dataAccessor->assertArrayHasCount($this->responseData, $path, $count);
    }

    /**
     * @Then the GraphQL response :path should be equal to :value
     */
    public function theGraphQLResponseShouldBeEqualTo(string $path, string $value): void
    {
        $this->ensureResponseDataAvailable();
        $actualValue = $this->dataAccessor->getFieldValue($this->responseData, $path);
        $this->valueAssertion->assert($path, $value, $actualValue);
    }

    /**
     * @Then the GraphQL response :path should be an object with properties :properties
     * @Then the GraphQL response :path should be an object with properties [:properties]
     * @Then /^the GraphQL response "([^"]*)" should be an object with properties \[([^\]]+)\]$/
     */
    public function theGraphQLResponseShouldBeAnObjectWithProperties(
        string $path,
        string $properties
    ): void {
        $this->ensureResponseDataAvailable();
        $this->dataAccessor->assertObjectHasProperties($this->responseData, $path, $properties);
    }

    private function buildPaginationQuery(int $first, string $endCursor): string
    {
        return sprintf(
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
    }

    /**
     * @param array<string, string|int|bool|float|array|null> $variables
     */
    private function sendGraphQLRequest(string $query, array $variables = []): void
    {
        $result = $this->requestSender->send($query, $variables);
        $this->response = $result['response'];
        $this->responseData = $result['data'];
    }

    private function ensureResponseDataAvailable(): void
    {
        Assert::notNull($this->responseData, 'No GraphQL response data available');
    }
}
