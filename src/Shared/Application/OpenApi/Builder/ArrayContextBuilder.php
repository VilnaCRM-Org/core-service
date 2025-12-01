<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\ValueObject\Parameter;
use ArrayObject;

final class ArrayContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): ArrayObject
    {
        $content = new ArrayObject([
            'application/ld+json' => [
                'example' => [''],
            ],
        ]);

        if (count($params) > 0) {
            $items = [];
            $example = [];
            $required = [];

            foreach ($params as $param) {
                if ($param->isRequired()) {
                    $required[] = $param->name;
                }
                $this->addParameterToItems($items, $param);
                $example[$param->name] = $param->example;
            }

            $content = $this->buildContent($items, $example, $required);
        }

        return $content;
    }

    /**
     * @param array<string, string> $items
     */
    private function addParameterToItems(array &$items, Parameter $param): void
    {
        $items[$param->name] = array_filter(
            [
                'type' => $param->type,
                'maxLength' => $param->maxLength,
                'format' => $param->format,
            ],
            static fn ($value) => $value !== null
        );
    }

    /**
     * @param array<string, string> $items
     * @param array<string, string|int|array> $example
     * @param array<string> $required
     */
    private function buildContent(
        array $items,
        array $example,
        array $required
    ): ArrayObject {
        return new ArrayObject([
            'application/ld+json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['properties' => $items],
                    'required' => $required,
                ],
                'example' => [$example],
            ],
        ]);
    }
}
