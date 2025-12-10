# Processor Patterns for OpenAPI Development

Key patterns and techniques adopted from the user-service repository for maintaining low complexity in OpenAPI processors.

## 1. Constants for HTTP Operations

**Problem**: Method chaining creates long, repetitive code
**Solution**: Use a constant and loop

```php
private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

private function processPathItem(PathItem $pathItem): PathItem
{
    foreach (self::OPERATIONS as $operation) {
        $pathItem = $pathItem->{'with' . $operation}(
            $this->processOperation($pathItem->{'get' . $operation}())
        );
    }
    return $pathItem;
}
```

**Benefits**:

- Reduces code duplication
- Lower cyclomatic complexity
- Easier to maintain

## 2. Match Expressions Over If-Else

**Problem**: If-else chains increase cyclomatic complexity
**Solution**: Use PHP 8's `match` expression

```php
// ❌ Bad: High complexity
private function processOperation(?Operation $operation): ?Operation
{
    if ($operation === null) {
        return null;
    }
    if ($operation->getParameters() === []) {
        return $operation;
    }
    return $operation->withParameters(...);
}

// ✅ Good: Lower complexity
private function processOperation(?Operation $operation): ?Operation
{
    return match (true) {
        $operation === null => null,
        $operation->getParameters() === [] => $operation,
        default => $operation->withParameters(...),
    };
}
```

**Benefits**:

- Each match branch counts as 1 complexity (vs 2+ for if-else)
- More readable
- Forces exhaustive handling

## 3. Functional Array Operations

**Problem**: Foreach loops with mutations increase complexity
**Solution**: Use `array_map`, `array_filter`, `array_combine`

```php
// ❌ Bad: Procedural with mutation
private function collectRequired(array $params): array
{
    $required = [];
    foreach ($params as $param) {
        if ($param->isRequired()) {
            $required[] = $param->name;
        }
    }
    return $required;
}

// ✅ Good: Functional
private function collectRequired(array $params): array
{
    return array_values(
        array_map(
            static fn (Parameter $parameter) => $parameter->name,
            array_filter(
                $params,
                static fn (Parameter $parameter) => $parameter->isRequired()
            )
        )
    );
}
```

**Benefits**:

- No mutation
- Lower complexity (filter + map count as 1 each vs foreach with if)
- More declarative

## 4. Static Methods for Pure Functions

**Problem**: Instance methods when no state is needed
**Solution**: Use static methods for pure transformations

```php
// ✅ Good: Static for pure function
private static function augmentParameter(mixed $parameter, array $descriptions): mixed
{
    $paramName = $parameter->getName();
    $description = $parameter->getDescription();
    $hasDescription = $description !== null && $description !== '';

    return match (true) {
        !isset($descriptions[$paramName]) => $parameter,
        $hasDescription => $parameter,
        default => $parameter->withDescription($descriptions[$paramName]),
    };
}
```

**Benefits**:

- Clear that function has no side effects
- Can be tested in isolation
- Signals immutability

## 5. Method Extraction for Complexity Reduction

**Problem**: Methods with too many branches or too long
**Solution**: Extract focused helper methods

```php
// ❌ Bad: 21 lines, complexity 9
private function processContent(Operation $operation): Operation
{
    $content = $operation->getRequestBody()->getContent();
    $modified = false;

    foreach ($content as $mediaType => $mediaTypeObject) {
        if (!isset($mediaTypeObject['schema']['properties'])) {
            continue;
        }
        foreach ($mediaTypeObject['schema']['properties'] as $propName => $propSchema) {
            if (!isset($propSchema['type']) || $propSchema['type'] !== 'iri-reference') {
                continue;
            }
            // mutation logic...
            $modified = true;
        }
    }
    return $modified ? $operation->withRequestBody(...) : $operation;
}

// ✅ Good: 14 lines, complexity 4
private function processContent(Operation $operation): Operation
{
    $requestBody = $operation->getRequestBody();
    $content = $requestBody->getContent();
    $modified = false;

    foreach ($content as $mediaType => $mediaTypeObject) {
        $fixedProperties = $this->fixProperties($mediaTypeObject);
        if ($fixedProperties !== null) {
            $content[$mediaType]['schema']['properties'] = $fixedProperties;
            $modified = true;
        }
    }

    return $modified
        ? $operation->withRequestBody($requestBody->withContent(new ArrayObject($content->getArrayCopy())))
        : $operation;
}

// Extracted method: 15 lines, complexity 4
private function fixProperties(array $mediaTypeObject): ?array
{
    if (!isset($mediaTypeObject['schema']['properties'])) {
        return null;
    }

    $properties = $mediaTypeObject['schema']['properties'];
    $fixedProperties = array_map(
        static fn ($propSchema) => self::fixProperty($propSchema),
        $properties
    );

    return $fixedProperties === $properties ? null : $fixedProperties;
}
```

**Benefits**:

- Each method stays under 20 lines (PHPInsights limit)
- Each method has complexity ≤ 10 (PHPMD limit)
- Better testability
- Clear naming documents intent

## 6. Avoiding empty() for Type Safety

**Problem**: `empty()` is forbidden by PHPInsights
**Solution**: Use explicit type checks

```php
// ❌ Bad: Using empty()
if (empty($parameters)) {
    return $operation;
}
if (empty($parameter->getDescription())) {
    // ...
}

// ✅ Good: Explicit checks
if ($parameters === []) {
    return $operation;
}
$description = $parameter->getDescription();
if ($description === null || $description === '') {
    // ...
}
```

**Benefits**:

- Type-safe
- Clear intent
- Passes PHPInsights

## 7. Delegation Over Implementation

**Problem**: Large classes with many responsibilities
**Solution**: Delegate to specialized classes

```php
// ✅ Good: OpenApiFactory delegates to processors
$this->parameterDescriptionAugmenter->augment($openApi);
$openApi = $this->tagDescriptionAugmenter->augment($openApi);
$this->iriReferenceTypeFixer->fix($openApi);
$openApi = $this->pathParametersSanitizer->sanitize($openApi);
```

**Benefits**:

- Each processor has single responsibility
- Easy to add new processors
- Clear execution pipeline

---

**See Also**:

- [Complete Examples](../examples/complete-examples.md) - Real implementations using these patterns
- [SKILL.md](../SKILL.md) - How to add new components
