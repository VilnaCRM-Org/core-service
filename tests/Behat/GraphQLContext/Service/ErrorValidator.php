<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Service;

use Webmozart\Assert\Assert;

final class ErrorValidator
{
    /**
     * @param array<string, mixed> $responseData
     */
    public function assertNoErrors(array $responseData): void
    {
        Assert::false(
            isset($responseData['errors']),
            sprintf(
                'GraphQL response contains errors: %s',
                json_encode($responseData['errors'] ?? [], JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertHasErrors(array $responseData): void
    {
        Assert::keyExists(
            $responseData,
            'errors',
            'Expected GraphQL response to contain errors, but none found'
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertErrorMessageContains(
        array $responseData,
        int $index,
        string $message
    ): void {
        $errorMessage = sprintf('No error found at index %d', $index);
        Assert::keyExists($responseData, 'errors', $errorMessage);
        Assert::keyExists($responseData['errors'], $index, $errorMessage);
        Assert::keyExists(
            $responseData['errors'][$index],
            'message',
            sprintf('No error message found at index %d', $index)
        );

        $errorMessage = $responseData['errors'][$index]['message'];
        Assert::contains(
            $errorMessage,
            $message,
            sprintf('Expected error message to contain "%s", got "%s"', $message, $errorMessage)
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertErrorExtensionsCode(array $responseData, int $index, string $code): void
    {
        $this->assertErrorExists($responseData, $index);
        $this->assertExtensionsCodeExists($responseData, $index);

        $actualCode = $responseData['errors'][$index]['extensions']['code'];
        Assert::eq(
            $actualCode,
            $code,
            sprintf('Expected error code "%s", got "%s"', $code, $actualCode)
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertErrorPath(
        array $responseData,
        int $index,
        int $pathIndex,
        string $value
    ): void {
        $this->assertErrorExists($responseData, $index);
        $this->assertPathExists($responseData, $index, $pathIndex);

        $actualPath = $responseData['errors'][$index]['path'][$pathIndex];
        Assert::eq(
            $actualPath,
            $value,
            sprintf('Expected error path "%s", got "%s"', $value, $actualPath)
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertErrorsContainField(array $responseData, string $field): void
    {
        Assert::keyExists($responseData, 'errors', 'No errors in GraphQL response');
        Assert::notEmpty($responseData['errors'], 'No errors in GraphQL response');

        $errorsString = json_encode($responseData['errors']);

        Assert::contains(
            $errorsString,
            '"' . $field . '"',
            sprintf('Field "%s" not found in GraphQL errors', $field)
        );
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function assertAnyErrorContainsMessage(array $responseData, string $message): void
    {
        Assert::keyExists($responseData, 'errors', 'No errors in GraphQL response');

        $found = array_filter(
            $responseData['errors'],
            static fn (array $error): bool => isset($error['message'])
                && str_contains($error['message'], $message)
        );

        Assert::notEmpty($found, sprintf('Error message "%s" not found in errors', $message));
    }

    /** @param array<string, mixed> $responseData */
    private function assertErrorExists(array $responseData, int $index): void
    {
        $errorMessage = sprintf('No error found at index %d', $index);
        Assert::keyExists($responseData, 'errors', $errorMessage);
        Assert::keyExists($responseData['errors'], $index, $errorMessage);
    }

    /** @param array<string, mixed> $responseData */
    private function assertPathExists(array $responseData, int $index, int $pathIndex): void
    {
        Assert::keyExists(
            $responseData['errors'][$index],
            'path',
            sprintf('No error path found at index %d', $index)
        );
        Assert::keyExists(
            $responseData['errors'][$index]['path'],
            $pathIndex,
            sprintf('No error path found at index %d, path index %d', $index, $pathIndex)
        );
    }

    /** @param array<string, mixed> $responseData */
    private function assertExtensionsCodeExists(array $responseData, int $index): void
    {
        Assert::keyExists(
            $responseData['errors'][$index],
            'extensions',
            sprintf('No error extensions found at index %d', $index)
        );
        Assert::keyExists(
            $responseData['errors'][$index]['extensions'],
            'code',
            sprintf('No error extensions code found at index %d', $index)
        );
    }
}
