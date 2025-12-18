# Documentation Verification Checklist

Use this checklist to verify documentation accuracy after creation.

## Pre-Creation Verification

### Source Analysis

- [ ] Identified all documentation files in reference repository
- [ ] Understood structure and organization pattern
- [ ] Noted cross-linking patterns between documents
- [ ] Identified project-specific content that needs adaptation

### Target Analysis

- [ ] Explored `src/` directory structure
- [ ] Identified all bounded contexts
- [ ] Listed main entities
- [ ] Checked available Makefile commands
- [ ] Verified technology stack from config files

---

## Technology Stack Verification

### PHP Version

```bash
# Check Dockerfile
grep -i "php:" Dockerfile

# Expected: php:X.Y-fpm or php:X.Y
# Document the actual version found
```

- [ ] PHP version in docs matches Dockerfile
- [ ] No claims about PHP features not in that version

### Framework Version

```bash
# Check composer.json
grep -i "symfony/framework-bundle" composer.json

# Expected: Version constraint like "^7.2"
```

- [ ] Framework version in docs matches composer.json
- [ ] No references to framework features not available in that version

### Database Type

```bash
# Check docker-compose.yml
grep -i "mongo\|postgres\|mysql" docker-compose.yml

# Check environment variables
grep -i "DB_\|DATABASE_" .env.example
```

- [ ] Database type correctly identified (MongoDB vs PostgreSQL vs MySQL)
- [ ] Connection configuration examples match actual environment vars
- [ ] No references to incorrect database driver

### Runtime Environment

```bash
# Check Dockerfile for PHP-FPM vs FrankenPHP
grep -i "fpm\|frankenphp" Dockerfile

# Check for Caddy/Nginx/Apache
grep -i "caddy\|nginx\|apache" Dockerfile docker-compose.yml
```

- [ ] Runtime correctly documented (PHP-FPM vs FrankenPHP)
- [ ] Web server correctly identified
- [ ] No documentation for unused runtime

---

## Directory Structure Verification

### Bounded Contexts

```bash
# List all bounded contexts
ls -la src/

# Verify each one exists
for dir in Shared Core Internal; do
  ls -la src/$dir/ 2>/dev/null || echo "Missing: src/$dir"
done
```

- [ ] All documented bounded contexts exist
- [ ] Context names spelled correctly
- [ ] Purpose of each context accurately described

### Domain Structure

```bash
# Check Application layer
ls -la src/Core/*/Application/

# Check Domain layer
ls -la src/Core/*/Domain/

# Check Infrastructure layer
ls -la src/Core/*/Infrastructure/
```

- [ ] Application/Domain/Infrastructure structure verified
- [ ] Subdirectories (Command/, Entity/, Repository/) verified
- [ ] No references to non-existent directories

### Entity Names

```bash
# Find all entities
find src -name "*Entity.php" -o -name "*.php" -path "*/Entity/*" | head -20

# Check specific entity exists
ls src/Core/*/Domain/Entity/
```

- [ ] All documented entities exist in codebase
- [ ] Entity names match exactly (case-sensitive)
- [ ] Relationships between entities accurately described

---

## Command Verification

### Makefile Commands

```bash
# List all available make commands
grep -E "^[a-zA-Z][a-zA-Z0-9_-]*:" Makefile

# Check specific commands
for cmd in "unit-tests" "integration-tests" "behat" "all-tests" "infection" "ci" "build" "up" "down"; do
  grep -q "^$cmd:" Makefile && echo "Found: $cmd" || echo "Missing: $cmd"
done
```

- [ ] All documented make commands exist
- [ ] Command names spelled correctly
- [ ] Command descriptions match actual behavior

### Docker Commands

```bash
# Verify docker-compose commands work
docker compose config --services
```

- [ ] All documented docker commands work
- [ ] Service names match docker-compose.yml
- [ ] No references to non-existent services

### Test Commands

```bash
# Verify test locations
ls tests/Unit/ 2>/dev/null || echo "No Unit tests dir"
ls tests/Integration/ 2>/dev/null || echo "No Integration tests dir"
ls tests/Behat/ 2>/dev/null || echo "No Behat tests dir"
ls tests/Load/ 2>/dev/null || echo "No Load tests dir"
```

- [ ] Test directory locations verified
- [ ] Test framework names correct (PHPUnit, Behat, K6)
- [ ] Coverage thresholds match actual configuration

---

## Link Verification

### Internal Links

```bash
# Find all markdown links
grep -r "\[.*\](.*\.md)" docs/

# Check each linked file exists
for link in $(grep -ohr '\](.*\.md)' docs/ | tr -d ']()' | sort -u); do
  ls docs/$link 2>/dev/null || echo "Missing: $link"
done
```

- [ ] All internal markdown links resolve
- [ ] No broken relative paths
- [ ] Navigation links work correctly

### External Links

- [ ] Repository URL is correct
- [ ] Organization name is correct
- [ ] External documentation links are valid

### Anchor Links

```bash
# Check for heading anchors referenced
grep -r "#[A-Za-z]" docs/*.md
```

- [ ] All anchor links reference existing headings
- [ ] Heading IDs match expected format

---

## Content Consistency

### Project Name

```bash
# Check for references to wrong project
grep -ri "<reference-repo-name>\|<reference_repo_name>" docs/
```

- [ ] Project name consistent throughout
- [ ] No references to source/reference project name
- [ ] URLs use correct project name

### Terminology

```bash
# Check for entity name consistency
grep -ri "customer\|user" docs/
```

- [ ] Entity names consistent (e.g., always "Customer" not mixed with "User")
- [ ] Technical terms used consistently
- [ ] No conflicting definitions in glossary

### Version Numbers

```bash
# Check version references
grep -ri "version\|v[0-9]" docs/
```

- [ ] Version numbers match actual project state
- [ ] No outdated version references
- [ ] Changelog/release notes accurate

---

## Final Checks

### Completeness

- [ ] All planned documentation files created
- [ ] Each section has meaningful content
- [ ] No placeholder text remaining
- [ ] Examples are functional

### Readability

- [ ] Markdown renders correctly
- [ ] Code blocks use correct language tags
- [ ] Tables are properly formatted
- [ ] Headings follow logical hierarchy

### Accuracy

- [ ] All code examples tested
- [ ] All command examples executed
- [ ] All directory paths verified
- [ ] All links checked

### CI Validation

After creating documentation, ensure CI still passes:

```bash
# Run full CI to ensure no issues introduced
make ci

# Expected output: "✅ CI checks successfully passed!"
```

- [ ] `make ci` passes successfully
- [ ] No markdown linting errors (if configured)
- [ ] Documentation doesn't break any existing tests

---

## VilnaCRM-Specific Checks

For VilnaCRM ecosystem projects:

### Quality Standards

- [ ] Coverage requirements show 100% (not lower thresholds)
- [ ] PHPInsights thresholds documented correctly (93%+ complexity, 100% quality/arch/style)
- [ ] Mutation testing shows 100% MSI requirement

### Architecture

- [ ] Hexagonal architecture described correctly
- [ ] DDD patterns match actual implementation
- [ ] CQRS pattern documented accurately
- [ ] Bounded contexts match `src/` structure

### Commands

- [ ] All `make` commands verified against Makefile
- [ ] Load test commands documented (smoke, average, stress, spike)
- [ ] CI command documented (`make ci`)

---

## Verification Commands Summary

```bash
# Full verification script
echo "=== Technology Stack ==="
grep -i "php:" Dockerfile
grep -i "symfony" composer.json
grep -i "mongo\|postgres" docker-compose.yml

echo "=== Directory Structure ==="
ls -la src/

echo "=== Available Commands ==="
grep -E "^[a-zA-Z].*:" Makefile | head -20

echo "=== Test Directories ==="
ls tests/

echo "=== Entities ==="
find src -path "*/Entity/*.php" | head -10

echo "=== Link Check ==="
for file in docs/*.md; do
  echo "Checking: $file"
done
```
