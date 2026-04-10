---
name: developing-openapi-specs
description: Default entrypoint for endpoint documentation and OpenAPI-spec work in core-service. Use when adding endpoint factories, parameter descriptions, request or response schema details, or fixing OpenAPI validation errors without refactoring the OpenAPI layer itself.
---

# Developing OpenAPI Specs

**Default entrypoint for endpoint documentation and OpenAPI-spec work** at `src/Shared/Application/OpenApi/`.

This skill covers the core-service patterns, complexity-management techniques, and validation steps used to maintain OpenAPI specifications while keeping code quality high. If the task shifts into processor refactoring or deeper OpenAPI layer-development work, move to [openapi-development](../openapi-development/SKILL.md).

**Related Skills**:

- [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - Domain layer must stay pure; OpenAPI code belongs in Application layer
- [openapi-development](../openapi-development/SKILL.md) - Advanced path for processor and OpenAPI-layer refactoring

## When to Use This Skill

- Adding or refining endpoint factories for documentation-facing changes
- Adding parameter descriptions or validation
- Updating request or response schema details
- Fixing OpenAPI validation errors
- Addressing documentation-facing OpenAPI generation regressions without redesigning the processor pipeline

## Architecture Overview

The OpenAPI layer follows a **Processor Pattern** with clear separation of concerns:

```
src/Shared/Application/OpenApi/
├── Builder/              # Schema and parameter builders
├── Factory/
│   ├── Endpoint/         # Custom endpoint factories
│   ├── Request/          # Request body schemas
│   ├── Response/         # Response schemas
│   └── UriParameter/     # Path parameter factories
├── Processor/            # Spec transformation processors
└── OpenApiFactory.php    # Main coordinator for tagged factories/processors
```

### Key Principles

1. **Single Responsibility**: Each processor/factory handles ONE concern
2. **Immutability**: Use `with*()` methods, never mutate directly
3. **Functional Programming**: Prefer `array_map`, `array_filter` over loops
4. **Flat Control Flow**: Prefer guard clauses, extracted helpers, and explicit branches
5. **Early Returns**: Guard clauses reduce nesting and complexity

## Quick Pattern Reference

### Pattern 1: OPERATIONS Constant

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

### Pattern 2: Guard Clauses and Explicit Branches

```php
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
```

### Pattern 3: Functional Array Operations

```php
// ✅ Functional (complexity: 2)
private function collectRequired(array $params): array
{
    return array_values(
        array_map(
            static fn (Parameter $p) => $p->name,
            array_filter($params, static fn (Parameter $p) => $p->isRequired())
        )
    );
}

// ❌ Procedural (complexity: 3+)
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
```

### Pattern 4: Extract Methods (Keep Under 20 Lines)

```php
// Split long methods into focused helpers
private function processContent(Operation $operation): Operation
{
    $content = $operation->getRequestBody()->getContent();
    $modified = false;

    foreach ($content as $mediaType => $mediaTypeObject) {
        $fixedProperties = $this->fixProperties($mediaTypeObject);  // Extracted!
        if ($fixedProperties !== null) {
            $content[$mediaType]['schema']['properties'] = $fixedProperties;
            $modified = true;
        }
    }

    return $modified ? $operation->withRequestBody(...) : $operation;
}

// Extracted method with single responsibility
private function fixProperties(array $mediaTypeObject): ?array
{
    if (!isset($mediaTypeObject['schema']['properties'])) {
        return null;
    }

    $properties = $mediaTypeObject['schema']['properties'];
    return array_map(
        static fn ($prop) => self::fixProperty($prop),
        $properties
    );
}
```

## Complexity Target Metrics

- **Min Complexity**: 93% (source code threshold)
- **Max Cyclomatic Complexity/Method**: 10
- **Max Method Length**: 20 lines
- **Max Class Complexity**: ≤ 8

## Quick Start Guide

### Adding a Processor

1. Create class in `src/Shared/Application/OpenApi/Processor/`
2. Implement `OpenApiProcessorInterface`
3. Use OPERATIONS constant, guard clauses, and functional style
4. Tag the service with `app.openapi_processor` and an explicit priority
5. Do not add a new dedicated constructor argument to `OpenApiFactory`

See [REFERENCE.md - Adding Processors](REFERENCE.md#adding-a-new-processor) for complete examples.

### Adding an Endpoint Factory

1. Implement `EndpointFactoryInterface`
2. Tag with `app.openapi_endpoint_factory` in services.yaml
3. Auto-discovered by `OpenApiFactory`

See [REFERENCE.md - Adding Endpoint Factories](REFERENCE.md#adding-a-new-endpoint-factory) for step-by-step guide.

### Adding Parameter Descriptions

Extend `ParameterDescriptionProcessor` with a focused provider and wire it into `getParameterDescriptions()`:

```php
private function getYourFilterDescriptions(): array
{
    return [
        'yourParam' => 'Description of parameter',
        'yourParam[]' => 'Array variant description',
    ];
}

private function getParameterDescriptions(): array
{
    return array_merge(
        $this->getOrderDescriptions(),
        $this->getYourFilterDescriptions(),
    );
}
```

## Anti-Patterns to Avoid

1. ❌ **Don't Mutate**: Use `withX()` methods, not direct assignment
2. ❌ **Don't Use empty()**: Explicitly check `$array === []` or `$string === ''`
3. ❌ **Don't Create God Classes**: Split into focused processors
4. ❌ **Don't Repeat HTTP Methods**: Use OPERATIONS constant
5. ❌ **Don't Write Nested Branch Mazes**: flatten control flow with guard clauses and helper extraction

## Testing Your Changes

```bash
make generate-openapi-spec       # Generate spec
make validate-openapi-spec        # Validate with Spectral
make phpinsights                 # Check quality scores
make unit-tests                  # Run tests
```

**Expected Results**:

- OpenAPI validation: "No results with severity 'hint' or higher"
- PHPInsights: Code 100%, Complexity ≥93% (src), Architecture 100%, Style 100%
- Unit tests: 100% coverage

## Architecture Layer (DDD)

OpenAPI code belongs in the **Application layer**:

- ✅ **Application Layer**: `src/Shared/Application/OpenApi/`
- ❌ **Never in Domain**: Domain must have zero framework dependencies
- See [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) for layer rules

OpenAPI components can depend on:

- Domain entities (for type information)
- Symfony/API Platform components
- DTOs and transformers

## Detailed Documentation

For comprehensive patterns, step-by-step guides, and examples:

- **[REFERENCE.md](REFERENCE.md)** - Full directory structure, all patterns with examples, troubleshooting guide
- **Codebase Examples**:
  - `ParameterDescriptionProcessor.php` - Parameter-description provider pattern
  - `IriReferenceTypeProcessor.php` - Schema transformation pattern
  - `PathParametersProcessor.php` - Delegation pattern for path cleanup

## Quick Checklist

Before committing OpenAPI changes:

- [ ] Used OPERATIONS constant for HTTP methods
- [ ] Kept branching flat with guard clauses or extracted helpers
- [ ] Methods under 20 lines
- [ ] Cyclomatic complexity under 10 per method
- [ ] Used functional array operations
- [ ] Pure functions are static
- [ ] No `empty()` - explicit type checks
- [ ] Early returns and guard clauses
- [ ] Delegated to specialized classes
- [ ] `make validate-openapi-spec` passes
- [ ] `make phpinsights` meets thresholds
- [ ] `make unit-tests` pass with 100% coverage
- [ ] Switched to `openapi-development` if the task became processor or layer-development work

---

**Remember**: Low complexity and high quality go hand-in-hand. Use functional programming, guard clauses, and method extraction to keep code maintainable.
