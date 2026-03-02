<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Service;

use Webmozart\Assert\Assert;

final class ResponseDataAccessor
{
    /**
     * @param array<string, mixed> $data
     */
    public function hasField(array $data, string $path): bool
    {
        try {
            $this->getFieldValue($data, $path);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function getFieldValue(array $data, string $path): mixed
    {
        $parts = explode('.', $path);
        $current = $data;

        foreach ($parts as $part) {
            Assert::isArray($current, sprintf('Path "%s" not found in response', $path));
            Assert::keyExists($current, $part, sprintf('Path "%s" not found in response', $path));
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assertFieldContains(array $data, string $path, string $value): void
    {
        $actualValue = $this->getFieldValue($data, $path);

        Assert::contains(
            (string) $actualValue,
            $value,
            sprintf('Expected %s to contain "%s", got "%s"', $path, $value, $actualValue)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assertFieldMatchesRegex(array $data, string $path, string $pattern): void
    {
        $actualValue = $this->getFieldValue($data, $path);

        Assert::regex(
            (string) $actualValue,
            $pattern,
            sprintf('Expected %s to match pattern "%s", got "%s"', $path, $pattern, $actualValue)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assertArrayHasCount(array $data, string $path, int $expectedCount): void
    {
        $value = $this->getFieldValue($data, $path);

        Assert::isArray(
            $value,
            sprintf('Expected %s to be an array, got %s', $path, gettype($value))
        );
        Assert::count(
            $value,
            $expectedCount,
            sprintf('Expected %s to have %d items, got %d', $path, $expectedCount, count($value))
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assertObjectHasProperties(
        array $data,
        string $path,
        string $propertiesString
    ): void {
        $value = $this->getFieldValue($data, $path);

        Assert::isArray(
            $value,
            sprintf('Expected %s to be an array, got %s', $path, gettype($value))
        );

        $propertiesString = str_replace(['[', ']', '"', "'"], '', $propertiesString);
        $expectedProperties = array_map('trim', explode(',', $propertiesString));

        foreach ($expectedProperties as $property) {
            Assert::keyExists(
                $value,
                trim($property),
                sprintf('Expected %s to have property "%s"', $path, $property)
            );
        }
    }
}
