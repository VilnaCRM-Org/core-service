# Core Service Documentation Creation Example

This example documents the process of creating documentation for `core-service` based on the `user-service` repository documentation structure.

## Context

- **Source Repository**: VilnaCRM-Org/user-service (branch: copilot/fix-45)
- **Target Repository**: VilnaCRM-Org/core-service
- **Goal**: Create comprehensive documentation following user-service style

## Step 1: Analyze Reference Repository

Fetched documentation listing from user-service:

```
docs/
├── advanced-configuration.md
├── api-endpoints.md
├── community-and-support.md
├── design-and-architecture.md
├── developer-guide.md
├── getting-started.md
├── glossary.md
├── legal-and-licensing.md
├── main.md
├── onboarding.md
├── operational.md
├── performance.md
├── performance-frankenphp.md      # Not applicable
├── php-fpm-vs-frankenphp.md       # Not applicable
├── release-notes.md
├── security.md
├── testing.md
├── user-guide.md
└── versioning.md
```

## Step 2: Analyze Target Project

### Technology Stack Check

```bash
# Dockerfile analysis
grep -i "php:" Dockerfile
# Result: php:8.3-fpm-alpine3.19

# Framework check
grep -i "symfony" composer.json
# Result: symfony/framework-bundle ^7.2

# Database check
grep -i "mongo" docker-compose.yml
# Result: mongo:6.0
```

**Findings**:
- PHP 8.3 with PHP-FPM (NOT FrankenPHP)
- Symfony 7.2
- MongoDB 6.0
- Caddy web server

### Directory Structure Check

```bash
ls -la src/
# Result:
# src/Core/Customer/
# src/Internal/HealthCheck/
# src/Shared/
```

**Bounded Contexts**:
- `Shared` - Cross-cutting concerns, kernel, infrastructure
- `Core/Customer` - Main business domain (Customer, CustomerType, CustomerStatus)
- `Internal/HealthCheck` - Internal health monitoring

### Entity Check

```bash
find src -path "*/Entity/*.php"
# Result:
# src/Core/Customer/Domain/Entity/Customer.php
# src/Core/Customer/Domain/Entity/CustomerType.php
# src/Core/Customer/Domain/Entity/CustomerStatus.php
```

**Main Entities**: Customer, CustomerType, CustomerStatus

## Step 3: Create Documentation

Created 17 documentation files (excluded FrankenPHP docs):

1. **main.md** - Overview with core-service specifics
2. **getting-started.md** - Installation with Docker/Caddy
3. **design-and-architecture.md** - Hexagonal, DDD, CQRS
4. **developer-guide.md** - Directory structure with Customer context
5. **api-endpoints.md** - REST and GraphQL for Customer entities
6. **testing.md** - PHPUnit, Behat, K6, Infection
7. **glossary.md** - Customer domain terminology
8. **user-guide.md** - Customer API examples
9. **advanced-configuration.md** - Environment and K6 config
10. **performance.md** - PHP-FPM benchmarks (NOT FrankenPHP)
11. **security.md** - Security measures
12. **operational.md** - Operational considerations
13. **onboarding.md** - Contributor guide
14. **community-and-support.md** - Support channels
15. **legal-and-licensing.md** - CC0 license
16. **release-notes.md** - Release process
17. **versioning.md** - Versioning policy

## Step 4: Key Adaptations Made

### Entity Substitutions

| User-Service | Core-Service |
| ------------ | ------------ |
| User | Customer |
| N/A | CustomerType |
| N/A | CustomerStatus |

### Technology Adaptations

| Aspect | User-Service | Core-Service |
| ------ | ------------ | ------------ |
| Runtime | FrankenPHP | PHP-FPM + Caddy |
| Database | PostgreSQL | MongoDB |
| Main Entity | User | Customer |

### Removed Content

- `performance-frankenphp.md` - Not applicable (uses PHP-FPM)
- `php-fpm-vs-frankenphp.md` - Not applicable

### Modified Content

- `performance.md` - Removed FrankenPHP references, kept PHP-FPM benchmarks
- All entity references changed from User to Customer
- All context references updated to Core/Customer

## Step 5: Verification

### Technology Verification

```bash
# Verify PHP-FPM (not FrankenPHP)
grep "fpm" Dockerfile
# Confirmed: php:8.3-fpm-alpine3.19
```

### Directory Verification

```bash
# Verify mentioned directories exist
ls src/Core/Customer/Application/  # Exists
ls src/Core/Customer/Domain/       # Exists
ls src/Shared/Infrastructure/      # Exists
```

### Command Verification

```bash
# Verify make commands
grep -E "^(unit-tests|integration-tests|e2e-tests):" Makefile
# All found
```

## Lessons Learned

1. **Always verify runtime environment** - FrankenPHP vs PHP-FPM matters for documentation
2. **Check database type early** - MongoDB vs PostgreSQL affects many sections
3. **Verify entity names** - Don't assume, check the actual codebase
4. **Remove inapplicable sections** - Better to omit than include wrong information
5. **Test all commands** - Every make command should exist

## Final Documentation Structure

```
docs/
├── advanced-configuration.md
├── api-endpoints.md
├── community-and-support.md
├── design-and-architecture.md
├── developer-guide.md
├── getting-started.md
├── glossary.md
├── legal-and-licensing.md
├── main.md
├── onboarding.md
├── operational.md
├── performance.md           # PHP-FPM only, no FrankenPHP
├── release-notes.md
├── security.md
├── testing.md
├── user-guide.md
└── versioning.md

17 files (vs 19 in source - excluded 2 FrankenPHP docs)
```
