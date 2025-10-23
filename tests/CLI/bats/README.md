# Bats Testing Suite

This directory contains comprehensive Bats (Bash Automated Testing System) tests for all Makefile commands in the VilnaCRM Core Service project.

## 📂 Test Structure

```
tests/CLI/bats/
├── test_helper/
│   └── common.bash              # Shared helper functions
├── php/                         # Test fixtures (bad code examples)
│   ├── FailingTest.php
│   ├── PartlyCoveredEventBus.php
│   ├── PsalmErrorExample.php
│   ├── SomeEntity.php
│   ├── UuidTransformer.php
│   └── temp_bad_code.php
├── make_general_tests.bats      # General commands (help, install, cache, etc.)
├── make_static_analyzes_tests.bats  # Static analysis (psalm, deptrac, phpcsfixer)
├── make_test_tests.bats         # Basic test commands
├── make_coverage_tests.bats     # Coverage and test suite commands
├── make_negative_tests.bats     # Negative scenarios (failures)
├── make_ci_tests.bats           # CI workflow commands
├── make_database_tests.bats     # Database operations
├── make_api_spec_tests.bats     # API specification generation
├── make_load_tests.bats         # Load testing commands
├── make_aws_load_tests.bats     # AWS load testing
└── make_underlying_command_tests.bats  # Docker commands
```

## 🚀 Running Tests

### Run All Bats Tests

```bash
make bats
```

### Run Specific Test File

```bash
docker compose exec -e APP_ENV=test php bats tests/CLI/bats/make_general_tests.bats
```

### Run Specific Test Case

```bash
docker compose exec -e APP_ENV=test php bats tests/CLI/bats/make_general_tests.bats -f "make help"
```

### Run Tests in CI Mode

```bash
CI=1 make bats
```

## 📋 Test Categories

### 1. General Commands (`make_general_tests.bats`)

Tests for utility and helper commands:
- ✅ `make help` - Lists all available targets
- ✅ `make composer-validate` - Validates composer files
- ✅ `make check-requirements` - Checks Symfony requirements
- ✅ `make phpinsights` - Code quality analysis
- ✅ `make check-security` - Security vulnerability checks
- ✅ `make infection` - Mutation testing
- ✅ `make cache-clear` - Cache clearing
- ✅ `make install` - Dependency installation
- ✅ `make update` - Dependency updates
- ✅ `make cache-warmup` - Cache warming
- ✅ `make purge` - Cache and log purging
- ✅ `make commands` - Lists Symfony commands
- ✅ `make generate-openapi-spec` - OpenAPI spec generation

### 2. Static Analysis (`make_static_analyzes_tests.bats`)

Tests for code quality tools:
- ✅ `make phpcsfixer` - PHP CS Fixer
- ✅ `make psalm` - Psalm static analysis
- ✅ `make psalm-security` - Security taint analysis
- ✅ `make deptrac` - Architecture validation
- ✅ `make deptrac-debug` - Deptrac debugging

### 3. Test Suite (`make_test_tests.bats`)

Basic test execution commands:
- ✅ `make integration-tests` - Integration tests
- ✅ `make tests-with-coverage` - Tests with coverage

### 4. Coverage Tests (`make_coverage_tests.bats`)

Comprehensive test coverage commands:
- ✅ `make coverage-html` - HTML coverage report
- ✅ `make coverage-xml` - XML coverage report
- ✅ `make all-tests` - All test types
- ✅ `make unit-tests` - Unit tests (100% coverage required)
- ✅ `make behat` - E2E BDD tests
- ✅ `make integration-negative-tests` - Negative integration tests
- ✅ `make negative-tests-with-coverage` - Negative tests with coverage

### 5. Negative Tests (`make_negative_tests.bats`)

Tests that verify failure scenarios:
- ✅ Security vulnerabilities detection
- ✅ Infection mutation escapes
- ✅ Behat scenario failures
- ✅ Psalm error detection
- ✅ Deptrac violations
- ✅ PHPInsights quality failures
- ✅ Unit test failures
- ✅ PHP CS Fixer violations
- ✅ Composer validation failures

### 6. CI Workflow (`make_ci_tests.bats`)

CI/CD pipeline commands:
- ✅ `make ci` - Comprehensive CI checks
- ✅ `make pr-comments` - GitHub PR comment retrieval

### 7. Database Operations (`make_database_tests.bats`)

Database management commands:
- ✅ `make setup-test-db` - Test database setup
- ✅ `make reset-db` - Database schema reset
- ✅ `make load-fixtures` - Fixture loading
- ✅ `make fixtures-load` - Alternative fixture loading

### 8. API Specifications (`make_api_spec_tests.bats`)

API documentation commands:
- ✅ `make generate-openapi-spec` - OpenAPI spec generation
- ✅ `make generate-graphql-spec` - GraphQL spec generation
- ✅ `make validate-openapi-spec` - OpenAPI spec validation
- ✅ `make openapi-diff` - OpenAPI spec comparison
- ✅ `make schemathesis-validate` - API validation

### 9. Load Testing (`make_load_tests.bats`)

Performance testing commands:
- ✅ `make smoke-load-tests` - Smoke tests
- ✅ `make average-load-tests` - Average load tests
- ✅ `make stress-load-tests` - Stress tests
- ✅ `make spike-load-tests` - Spike tests
- ✅ `make load-tests` - All load tests
- ✅ `make prepare-test-data` - Test data preparation
- ✅ `make cleanup-test-data` - Test data cleanup
- ✅ `make build-k6-docker` - k6 Docker image build
- ✅ `make execute-load-tests-script` - Custom load test execution
- ✅ `make start-prod-loadtest` - Production load test environment
- ✅ `make stop-prod-loadtest` - Stop load test environment

### 10. AWS Load Testing (`make_aws_load_tests.bats`)

AWS infrastructure testing:
- ✅ `make aws-load-tests` - AWS load tests
- ✅ `make aws-load-tests-cleanup` - AWS resource cleanup

### 11. Docker Commands (`make_underlying_command_tests.bats`)

Docker infrastructure commands:
- ✅ `make sh` - PHP container shell access
- ✅ `make build` - Docker image building
- ✅ `make stop` - Stop containers
- ✅ `make down` - Remove containers
- ✅ `make up` - Start containers
- ✅ `make ps` - List containers

## 🛠️ Helper Functions

The `test_helper/common.bash` file provides reusable functions:

### Environment Functions
- `is_ci()` - Check if running in CI environment
- `run_with_ci()` - Run command with CI flag
- `skip_if_no_docker()` - Skip test if Docker unavailable
- `skip_if_ci()` - Skip test in CI environment
- `command_exists()` - Check if command exists

### File Management
- `create_temp_file(content, filename)` - Create temporary test file
- `cleanup_temp_file(filename)` - Remove temporary file
- `backup_file(file)` - Backup file before modification
- `restore_file(file)` - Restore file from backup
- `move_test_file(src, dest)` - Move file for testing
- `restore_test_file(src, dest)` - Restore moved file

### Test Assertions
- `assert_output_contains_all(patterns...)` - Assert multiple patterns in output
- `run_make(command)` - Run make command with CI detection

## 📝 Writing New Tests

### Basic Test Structure

```bash
#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'
load 'test_helper/common'

@test "description of what is being tested" {
  run_make command-name
  assert_success
  assert_output --partial "expected output"
}
```

### Negative Test Example

```bash
@test "make command should fail when conditions are wrong" {
  # Setup bad condition
  move_test_file tests/CLI/bats/php/BadCode.php src/SomeDir/
  
  # Run command expecting failure
  run_make command-name
  
  # Cleanup
  restore_test_file src/SomeDir/BadCode.php tests/CLI/bats/php/
  
  # Assert failure
  assert_failure
  assert_output --partial "error message"
}
```

### Docker-Required Test

```bash
@test "make docker-command requires Docker" {
  skip_if_no_docker
  run_make docker-command
  assert_success
}
```

## 🔍 Best Practices

1. **Use Helper Functions**: Always use `run_make` instead of `run make` for CI compatibility
2. **Skip Appropriately**: Use `skip_if_no_docker` for Docker-dependent tests
3. **Clean Up**: Always restore files/state after negative tests
4. **Descriptive Names**: Use clear, descriptive test names
5. **Partial Matching**: Use `assert_output --partial` for flexible matching
6. **Test Independence**: Each test should be independent and repeatable

## 🐛 Debugging Tests

### Verbose Output

```bash
bats -t tests/CLI/bats/make_general_tests.bats
```

### Debug Single Test

```bash
bats -f "specific test name" tests/CLI/bats/make_general_tests.bats
```

### Manual Execution

```bash
# Inside container
docker compose exec php sh
cd /app
bats tests/CLI/bats/make_general_tests.bats
```

## 📊 Test Coverage

All Makefile commands with `##` documentation are covered by bats tests. Current coverage:
- ✅ General commands: 100%
- ✅ Static analysis: 100%
- ✅ Test suites: 100%
- ✅ CI workflow: 100%
- ✅ Database operations: 100%
- ✅ API specs: 100%
- ✅ Load testing: 100%
- ✅ Docker commands: 100%

## 🔗 Dependencies

- **Bats Core**: Main testing framework
- **bats-support**: Additional test helpers
- **bats-assert**: Assertion library
- **Docker Compose**: Container orchestration
- **jq**: JSON processing (for some tests)

## 📚 References

- [Bats Documentation](https://bats-core.readthedocs.io/)
- [bats-assert](https://github.com/bats-core/bats-assert)
- [bats-support](https://github.com/bats-core/bats-support)

