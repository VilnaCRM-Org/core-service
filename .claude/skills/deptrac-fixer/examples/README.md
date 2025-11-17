# Deptrac Fixer Examples

This directory contains practical before/after examples for fixing common Deptrac architectural violations.

## Examples Overview

1. **[01-domain-symfony-validation.php](01-domain-symfony-validation.php)** - Fixing Domain → Symfony validator constraint violations
2. **[02-domain-doctrine-annotations.php](02-domain-doctrine-annotations.php)** - Removing Doctrine ODM annotations from domain entities
3. **[03-domain-api-platform.php](03-domain-api-platform.php)** - Moving API Platform configuration out of domain
4. **[04-infrastructure-handler.php](04-infrastructure-handler.php)** - Using bus pattern instead of direct handler calls
5. **[05-complete-entity-refactoring.php](05-complete-entity-refactoring.php)** - Full entity refactoring with all patterns combined

## How to Use These Examples

1. **Identify your violation type** from `make deptrac` output
2. **Find matching example** that addresses the violation
3. **Apply the pattern** to your specific code
4. **Verify with** `make deptrac` after changes

## Quick Reference

| Violation Pattern | Example File | Key Solution |
|-------------------|-------------|--------------|
| Domain → Symfony Validator | 01 | Value Objects with self-validation |
| Domain → Doctrine | 02 | XML mappings in config/doctrine/ |
| Domain → API Platform | 03 | YAML config or Application DTOs |
| Infrastructure → Handler | 04 | CommandBusInterface injection |
| All Combined | 05 | Complete refactoring workflow |

## Directory Structure Guide

When moving files, consult **[CODELY-STRUCTURE.md](../CODELY-STRUCTURE.md)** for:

- Complete CodelyTV directory hierarchy
- WHERE files should go after fixing violations
- File naming conventions per layer

## Testing Your Fixes

After applying any fix:

```bash
# Verify architecture
make deptrac

# Ensure tests pass
make unit-tests

# Check for type issues
make psalm
```
