# Complete Examples from Codebase

Real-world examples demonstrating processor patterns and complexity reduction techniques in the OpenAPI layer.

## Example 1: ParameterDescriptionAugmenter

Shows all key patterns:

- OPERATIONS constant
- Match expressions
- Functional programming
- Method extraction
- Static pure functions

**Location**: `src/Shared/Application/OpenApi/Processor/ParameterDescriptionAugmenter.php`

**Key Methods**:

- `augmentOperation()`: Uses match expression
- `augmentParameters()`: Uses array_map
- `augmentParameter()`: Static pure function

## Example 2: IriReferenceTypeFixer

Shows complexity reduction:

- Match for null/empty checks
- Extracted `fixProperties()` and `fixProperty()` methods
- array_map for transformation

**Location**: `src/Shared/Application/OpenApi/Processor/IriReferenceTypeFixer.php`

**Complexity Journey**:

- Original: 12 cyclomatic complexity
- After refactoring: 8 cyclomatic complexity

## Example 3: PathParametersSanitizer

Shows delegation pattern:

- Delegates to `PathParameterCleaner`
- Uses OPERATIONS constant
- Match expression for operation processing

**Location**: `src/Shared/Application/OpenApi/Processor/PathParametersSanitizer.php`

---

## Configuration Files

### phpinsights.php

Key configuration:

```php
'requirements' => [
    'min-quality' => 100,
    'min-complexity' => 93,  // Source code standard (NEVER decrease below 93%)
    'min-architecture' => 100,
    'min-style' => 100,
],
'remove' => [
    // DisallowMixedTypeHintSniff disabled for API Platform's dynamic structures
    SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
],
```

### phpmd.xml

```xml
<rule name="CyclomaticComplexity">
    <properties>
        <property name="reportLevel" value="10"/>
    </properties>
</rule>
```

---

## Resources

- **User-Service Reference**: https://github.com/VilnaCRM-Org/user-service/tree/copilot/fix-45
- **API Platform Docs**: https://api-platform.com/docs/
- **OpenAPI 3.1 Spec**: https://spec.openapis.org/oas/v3.1.0
- **Spectral Validation**: https://stoplight.io/open-source/spectral

---

## Troubleshooting

### "Cyclomatic complexity too high"

- Use match expressions instead of if-else
- Extract methods (keep each under 20 lines)
- Replace loops with array functions
- Extract conditions to variables

### "Function too long"

- Split into helper methods
- Each method should do ONE thing
- Aim for ≤15 lines per method

### "Spectral validation errors"

- Check `.spectral.yaml` for rules
- Ensure all operations have descriptions
- Verify parameter schemas are correct
- Run cleanup script: `python3 scripts/cleanup-openapi-parameters.py`

### "Mixed type hint not allowed"

- Already disabled in phpinsights.php
- If you see this error, verify phpinsights.php configuration

### "Architecture score too low"

- Check Deptrac violations
- Ensure dependencies flow correctly: Infrastructure → Application → Domain
- Don't import Domain into Infrastructure

---

## Summary Checklist

When contributing to OpenAPI layer:

- [ ] Use OPERATIONS constant for HTTP methods
- [ ] Use match expressions instead of if-else
- [ ] Keep methods under 20 lines
- [ ] Keep cyclomatic complexity under 10
- [ ] Use functional array operations
- [ ] Make pure functions static
- [ ] Avoid empty() - use explicit checks
- [ ] Use early returns and guard clauses
- [ ] Delegate to specialized classes
- [ ] Test with `make validate-openapi-spec`
- [ ] Verify with `make phpinsights`
- [ ] Run `make unit-tests`

---

**See Also**:

- [Processor Patterns](../reference/processor-patterns.md) - Pattern explanations
- [SKILL.md](../SKILL.md) - How to add new components
