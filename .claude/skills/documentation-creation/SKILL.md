---
name: documentation-creation
description: Create comprehensive project documentation by adapting from a reference repository. Use when setting up initial documentation, creating docs following another project's structure, or building a complete documentation suite from scratch. Covers fetching reference docs, adapting content to target project specifics, verifying accuracy, and maintaining consistent style.
---

# Documentation Creation Skill

## Overview

This skill guides the creation of comprehensive project documentation by adapting content from a well-structured reference repository. It ensures documentation accurately reflects the target project while maintaining consistent style and structure.

## Context (Input)

- Need to create documentation for a project from scratch
- Have a reference repository with well-structured documentation
- Want consistent style across all documentation files
- Need to verify documentation accuracy against actual codebase

## Task (Function)

Create comprehensive, accurate project documentation by:

1. Fetching documentation from a reference repository
2. Adapting content to match target project specifics
3. Verifying all references against actual codebase
4. Ensuring consistent style and cross-linking

**Success Criteria**:

- All documentation files created with consistent structure
- All code references verified against actual project structure
- All directory paths and file mentions exist in codebase
- All links between documentation files work correctly
- Technology stack accurately reflected (no false claims)

---

## Quick Start: Documentation Creation Workflow

### Step 1: Analyze Reference Repository

Fetch and analyze the reference repository's documentation structure:

```bash
# Use WebFetch to retrieve documentation listing
# Identify all documentation files in the reference
```

Key items to identify:

- [ ] List of all documentation files
- [ ] Structure and organization pattern
- [ ] Common sections across documents
- [ ] Cross-linking patterns

### Step 2: Analyze Target Project

Before creating any documentation, thoroughly understand the target project:

```bash
# Check project structure
ls -la src/

# Identify technology stack
cat composer.json
cat Dockerfile
cat docker-compose.yml

# Identify bounded contexts
ls -la src/Core/
ls -la src/Shared/
ls -la src/Internal/

# Check for existing commands, handlers, entities
find src -name "*Command.php" | head -20
find src -name "*Entity.php" | head -20
```

Key items to document:

- [ ] Technology stack (PHP version, framework, database)
- [ ] Architecture style (DDD, hexagonal, CQRS)
- [ ] Bounded contexts and their purposes
- [ ] Main entities and their relationships
- [ ] Available commands and testing tools

### Step 3: Create Documentation Files

Create each documentation file following this order:

1. **main.md** - Project overview and design principles
2. **getting-started.md** - Installation and quick start
3. **design-and-architecture.md** - Architectural decisions and patterns
4. **developer-guide.md** - Code structure and development workflow
5. **api-endpoints.md** - REST and GraphQL API documentation
6. **testing.md** - Testing strategy and commands
7. **glossary.md** - Domain terminology and naming conventions
8. **user-guide.md** - API usage examples
9. **advanced-configuration.md** - Environment and configuration
10. **performance.md** - Benchmarks and optimization
11. **security.md** - Security measures and practices
12. **operational.md** - Operational considerations
13. **onboarding.md** - New contributor guide
14. **community-and-support.md** - Support channels
15. **legal-and-licensing.md** - License and dependencies
16. **release-notes.md** - Release process
17. **versioning.md** - Versioning policy

### Step 4: Adapt Content to Target Project

For each documentation file:

1. **Replace project-specific references**:
   - Project name (e.g., "user-service" → "core-service")
   - Entity names (e.g., "User" → "Customer")
   - Bounded context names
   - URLs and repository links

2. **Update technology references**:
   - Framework versions
   - Database type (PostgreSQL vs MongoDB)
   - Runtime environment (FrankenPHP vs PHP-FPM)
   - Container orchestration

3. **Verify directory paths**:

   ```bash
   # Check that mentioned directories exist
   ls -la src/Core/{Context}/
   ls -la src/Shared/Domain/
   ```

4. **Verify command existence**:

   ```bash
   # Check Makefile for available commands
   grep -E "^[a-zA-Z].*:" Makefile | head -30
   ```

### Step 5: Verify Accuracy

Run comprehensive verification:

1. **Technology Stack Verification**:

   ```bash
   # Verify PHP version
   grep -i "php" Dockerfile

   # Verify framework
   grep -i "symfony" composer.json

   # Verify database
   grep -i "mongo\|postgres" docker-compose.yml
   ```

2. **Directory Structure Verification**:

   ```bash
   # Verify all mentioned src directories
   for dir in "Shared" "Core" "Internal"; do
     ls -la src/$dir/ 2>/dev/null || echo "Missing: src/$dir"
   done
   ```

3. **Command Verification**:

   ```bash
   # Verify mentioned make commands exist
   for cmd in "unit-tests" "integration-tests" "e2e-tests"; do
     grep -q "^$cmd:" Makefile && echo "Found: $cmd" || echo "Missing: $cmd"
   done
   ```

4. **Link Verification**:
   - Check all internal markdown links resolve
   - Verify external links are accurate

---

## Documentation Templates

### Overview Document (main.md)

```markdown
Welcome to the **{Project Name}** GitHub page...

## Design Principles
{List project's core design principles}

## Technology Stack
- **Language:** PHP {version}
- **Framework:** {framework} {version}
- **Database:** {database} {version}
...
```

### Getting Started (getting-started.md)

```markdown
## Prerequisites
{List required software with versions}

## Installation
{Step-by-step installation commands}

## Verification
{Commands to verify installation}
```

See [reference/doc-templates.md](reference/doc-templates.md) for complete templates.

---

## Constraints

### NEVER

- Copy content without verifying accuracy against target project
- Include references to non-existent directories or files
- Claim features or technologies the project doesn't use
- Leave placeholder text from reference repository
- Skip verification step after creating documentation

### ALWAYS

- Verify every directory path mentioned exists
- Confirm technology stack matches project reality
- Test command examples in target project
- Update all cross-references to point to correct files
- Maintain consistent terminology throughout
- Remove or adapt sections that don't apply to target project

---

## Verification Checklist

After creating documentation:

### Technology Accuracy

- [ ] PHP version matches Dockerfile
- [ ] Framework version matches composer.json
- [ ] Database type matches docker-compose.yml
- [ ] Runtime environment correctly described (FPM vs FrankenPHP)
- [ ] No false claims about unused technologies

### Structure Accuracy

- [ ] All mentioned `src/` directories exist
- [ ] All bounded context names are correct
- [ ] Entity names match actual codebase
- [ ] Command and handler names are accurate

### Command Accuracy

- [ ] All `make` commands exist in Makefile
- [ ] Docker commands work as documented
- [ ] Test commands produce expected output

### Link Accuracy

- [ ] All internal markdown links resolve
- [ ] External repository links are correct
- [ ] No broken navigation links

### Content Consistency

- [ ] Project name consistent throughout
- [ ] Terminology consistent across documents
- [ ] No leftover references to source project

---

## Common Pitfalls

### Technology Mismatch

**Problem**: Documenting FrankenPHP when project uses PHP-FPM

**Solution**:

```bash
# Check actual runtime
grep -i "fpm\|frankenphp" Dockerfile
# Remove documentation for unused technology
```

### Missing Directories

**Problem**: Documenting `src/User/` when it doesn't exist

**Solution**:

```bash
# Verify before documenting
ls -la src/
# Update to match actual structure (e.g., src/Core/Customer/)
```

### Outdated Commands

**Problem**: Documenting `make test` when command is `make unit-tests`

**Solution**:

```bash
# Check actual Makefile
grep -E "^[a-z].*:" Makefile
```

---

## Related Skills

- [documentation-sync](../documentation-sync/SKILL.md) - Keep docs in sync with code changes
- [api-platform-crud](../api-platform-crud/SKILL.md) - API documentation patterns
- [testing-workflow](../testing-workflow/SKILL.md) - Testing documentation

---

## Reference Documentation

- **[Doc Templates](reference/doc-templates.md)** - Complete templates for each doc type
- **[Verification Checklist](reference/verification-checklist.md)** - Detailed verification steps
- **[Examples](examples/)** - Real-world documentation examples

---

## Quick Commands

```bash
# Check project structure
ls -laR src/ | head -50

# Find entities
find src -name "*Entity.php"

# Find commands
find src -name "*Command.php"

# Check make commands
grep -E "^[a-zA-Z].*:" Makefile

# Verify runtime
grep -i "fpm\|frankenphp" Dockerfile

# Check database
grep -i "mongo\|postgres" docker-compose.yml
```
