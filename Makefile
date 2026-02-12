# Load environment variables from .env.test
include .env.test

# Parameters
PROJECT       = core-service
GIT_AUTHOR    = Kravalg

# Executables: local only
SYMFONY_BIN   = symfony
DOCKER        = docker
DOCKER_COMPOSE = docker compose

# Executables
EXEC_PHP      = $(DOCKER_COMPOSE) exec php
COMPOSER      = $(EXEC_PHP) composer
GIT           = git
EXEC_PHP_TEST_ENV = $(DOCKER_COMPOSE) exec -e APP_ENV=test php

# Alias
SYMFONY       = $(EXEC_PHP) bin/console
SYMFONY_TEST_ENV = $(EXEC_PHP_TEST_ENV) bin/console

# Executables: vendors
BEHAT         = ./vendor/bin/behat --stop-on-failure -n
PHPUNIT       = ./vendor/bin/phpunit
PSALM         = ./vendor/bin/psalm
PHP_CS_FIXER  = ./vendor/bin/php-cs-fixer
DEPTRAC       = ./vendor/bin/deptrac
INFECTION     = ./vendor/bin/infection

# Misc
.DEFAULT_GOAL = help
.RECIPEPREFIX +=
.PHONY: $(filter-out vendor node_modules,$(MAKECMDGOALS))

# Conditional execution based on CI environment variable
EXEC_ENV ?= $(EXEC_PHP_TEST_ENV)
ifeq ($(CI),1)
  EXEC_ENV =
endif

# Variables for environment and commands
FIXER_ENV = PHP_CS_FIXER_IGNORE_ENV=1
PHP_CS_FIXER_CMD = php ./vendor/bin/php-cs-fixer fix $(git ls-files -om --exclude-standard) --allow-risky=yes --config .php-cs-fixer.dist.php
COVERAGE_CMD = php -d memory_limit=-1 -d xdebug.mode=coverage ./vendor/bin/phpunit --coverage-text=coverage.txt --colors=never

GITHUB_HOST ?= github.com
FORMAT ?= markdown
COVERAGE_INTERNAL_CMD = php -d memory_limit=-1 -d xdebug.mode=coverage ./vendor/bin/phpunit --testsuite Negative --coverage-clover /coverage/coverage.xml

define DOCKER_EXEC_WITH_ENV
$(DOCKER_COMPOSE) exec -T -e $(1) php $(2)
endef

# Conditional execution based on CI environment variable
ifeq ($(CI),1)
    RUN_PHP_CS_FIXER = $(FIXER_ENV) $(PHP_CS_FIXER_CMD)
    RUN_TESTS_COVERAGE = XDEBUG_MODE=coverage $(COVERAGE_CMD)
    RUN_INTERNAL_TESTS_COVERAGE = XDEBUG_MODE=coverage $(COVERAGE_INTERNAL_CMD)
else
    RUN_PHP_CS_FIXER = $(call DOCKER_EXEC_WITH_ENV,$(FIXER_ENV),$(PHP_CS_FIXER_CMD))
    RUN_TESTS_COVERAGE = $(call DOCKER_EXEC_WITH_ENV,APP_ENV=test -e XDEBUG_MODE=coverage,$(COVERAGE_CMD))
    RUN_INTERNAL_TESTS_COVERAGE = $(call DOCKER_EXEC_WITH_ENV,APP_ENV=test -e XDEBUG_MODE=coverage,$(COVERAGE_INTERNAL_CMD))
endif

export SYMFONY

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

bats: ## Run tests for bash commands
	bats tests/CLI/bats/

phpcsfixer: ## A tool to automatically fix PHP Coding Standards issues
	$(RUN_PHP_CS_FIXER)

composer-validate: ## The validate command validates a given composer.json and composer.lock
	$(COMPOSER) validate

check-requirements: ## Checks requirements for running Symfony and gives useful recommendations to optimize PHP for Symfony.
	$(EXEC_ENV) $(SYMFONY_BIN) check:requirements

check-security: ## Checks security issues in project dependencies. Without arguments, it looks for a "composer.lock" file in the current directory. Pass it explicitly to check a specific "composer.lock" file.
	$(EXEC_ENV) $(SYMFONY_BIN) security:check

psalm: ## A static analysis tool for finding errors in PHP applications
	$(EXEC_ENV) $(PSALM)

psalm-security: ## Psalm security analysis
	$(EXEC_ENV) $(PSALM) --taint-analysis

psalm-security-report: ## Psalm security analysis with SARIF output
	$(EXEC_ENV) $(PSALM) --taint-analysis --report=results.sarif

phpmd: ## Instant PHP MD quality checks, static analysis, and complexity insights
	$(EXEC_ENV) ./vendor/bin/phpmd src ansi phpmd.xml --exclude vendor
	$(EXEC_ENV) ./vendor/bin/phpmd tests ansi phpmd.tests.xml --exclude vendor,tests/CLI/bats

phpinsights: phpmd ## Instant PHP quality checks, static analysis, and complexity insights
	$(EXEC_ENV) ./vendor/bin/phpinsights --no-interaction --flush-cache --fix --ansi --disable-security-check
	$(EXEC_ENV) ./vendor/bin/phpinsights analyse tests --no-interaction --flush-cache --fix --disable-security-check --config-path=phpinsights-tests.php

unit-tests: ## Run unit tests with 100% coverage requirement
	@echo "Running unit tests with coverage requirement of 100%..."
	@cleanup_coverage() { rm -f coverage.txt; }; \
	trap cleanup_coverage EXIT; \
	$(RUN_TESTS_COVERAGE) --testsuite=Unit; \
	test_status=$$?; \
	if [ $$test_status -ne 0 ]; then \
		echo "❌ TEST FAILURE: Unit tests returned a non-zero exit code ($$test_status)."; \
		exit $$test_status; \
	fi; \
	if [ -z "$$CI" ]; then \
		sleep 2; \
		wait_count=0; \
		while [ $$wait_count -lt 10 ] && [ ! -f coverage.txt ]; do \
			$(DOCKER_COMPOSE) cp php:/srv/app/coverage.txt coverage.txt >/dev/null 2>&1 || true; \
			if [ -f coverage.txt ]; then \
				break; \
			fi; \
			wait_count=$$((wait_count + 1)); \
			sleep 2; \
		done; \
	fi; \
	if [ ! -f coverage.txt ]; then \
		echo "❌ ERROR: coverage.txt was not generated."; \
		exit 1; \
	fi; \
	coverage=$$(sed 's/\x1b\[[0-9;]*m//g' coverage.txt | tr -d '\r' | sed -n 's/.*Lines:[[:space:]]*\([0-9.]*\)%.*/\1/p' | head -1); \
	if [ -n "$$coverage" ]; then \
		if [ $$(echo "$$coverage < 100" | bc -l) -eq 1 ]; then \
			echo "❌ COVERAGE FAILURE: Line coverage is $$coverage%, but 100% is required. Please cover all lines of code and achieve the 100% code coverage"; \
			exit 1; \
		else \
			echo "✅ COVERAGE SUCCESS: Line coverage is $$coverage%"; \
		fi; \
	else \
		echo "❌ ERROR: Could not parse coverage from output"; \
		exit 1; \
	fi

deptrac: ## Check directory structure
	$(EXEC_ENV) $(DEPTRAC) analyse --config-file=deptrac.yaml --report-uncovered --fail-on-uncovered

deptrac-debug: ## Find files unassigned for Deptrac
	$(EXEC_ENV) $(DEPTRAC) debug:unassigned --config-file=deptrac.yaml

ensure-test-services: ## Ensure required Docker services for test suites are running
	$(DOCKER_COMPOSE) up --detach database redis php caddy localstack

setup-test-db: ensure-test-services ## Create database for testing purposes
	$(SYMFONY_TEST_ENV) c:c
	-$(SYMFONY_TEST_ENV) doctrine:mongodb:schema:drop
	-$(SYMFONY_TEST_ENV) doctrine:mongodb:schema:create

behat: setup-test-db ## A php framework for autotesting business expectations
	$(EXEC_ENV) $(BEHAT)

integration-tests: setup-test-db ## Run integration tests
	$(RUN_TESTS_COVERAGE) --testsuite=Integration

integration-negative-tests: ## Run integration negative tests
	$(EXEC_ENV) $(PHPUNIT) --testsuite=Negative

fixtures-load: ## Run fixtures
	$(SYMFONY_TEST_ENV) doctrine:mongodb:fixtures:load -n || true

tests-with-coverage: ## Run tests with coverage
	$(RUN_TESTS_COVERAGE)

negative-tests-with-coverage: ## Run negative tests with coverage reporting
	$(RUN_INTERNAL_TESTS_COVERAGE)

all-tests: unit-tests integration-tests behat ## Run unit, integration and e2e tests

prepare-test-data: build-k6-docker ## Prepare test data for load tests
	tests/Load/prepare-test-data.sh

cleanup-test-data: build-k6-docker ## Clean up test data after load tests
	tests/Load/cleanup-test-data.sh

smoke-load-tests: build-k6-docker ## Run load tests with minimal load
	tests/Load/run-smoke-load-tests.sh

average-load-tests: build-k6-docker ## Run load tests with average load
	tests/Load/run-average-load-tests.sh

stress-load-tests: build-k6-docker ## Run load tests with high load
	tests/Load/run-stress-load-tests.sh

spike-load-tests: build-k6-docker ## Run load tests with a spike of extreme load
	tests/Load/run-spike-load-tests.sh

load-tests: build-k6-docker ## Run load tests
	tests/Load/run-load-tests.sh

cache-performance-tests: setup-test-db ## Run cache performance integration tests
	$(EXEC_ENV) $(PHPUNIT) tests/Integration/Customer/Infrastructure/Repository/CachePerformanceTest.php --testdox

cache-performance-load-tests: build-k6-docker ## Run cache performance K6 load tests
	tests/Load/execute-load-test.sh rest-api/cachePerformance true false false false smoke-

build-k6-docker:
	$(DOCKER) build -t k6 -f ./tests/Load/Dockerfile .

build-spectral-docker:
	$(DOCKER) build -t core-service-spectral -f ./docker/spectral/Dockerfile .

infection: ## Run mutation testing with 100% MSI requirement
	$(EXEC_ENV) php -d memory_limit=-1 $(INFECTION) --initial-tests-php-options="-d memory_limit=-1" --test-framework-options="--testsuite=Unit" --show-mutations --log-verbosity=all -j8 --min-msi=100 --min-covered-msi=100

execute-load-tests-script: build-k6-docker ## Execute single load test scenario.
	tests/Load/execute-load-test.sh $(scenario) $(or $(runSmoke),true) $(or $(runAverage),true) $(or $(runStress),true) $(or $(runSpike),true)

analyze-complexity: ## Analyze and report top N most complex classes using PHPMetrics (default: 20)
	@bash scripts/analyze-complexity.sh text $(if $(N),$(N),20)

analyze-complexity-json: ## Export complexity analysis as JSON using PHPMetrics
	@bash scripts/analyze-complexity.sh json $(if $(N),$(N),20)

analyze-complexity-csv: ## Export complexity analysis as CSV using PHPMetrics
	@bash scripts/analyze-complexity.sh csv $(if $(N),$(N),20)

reset-db: ## Recreate the database schema for ephemeral test runs
	@$(SYMFONY) doctrine:mongodb:cache:clear-metadata
	-@$(SYMFONY) doctrine:mongodb:schema:drop
	@$(SYMFONY) doctrine:mongodb:schema:create

load-fixtures: ## Build the DB, control the schema validity, and load fixtures
	@$(SYMFONY) doctrine:mongodb:cache:clear-metadata
	-@$(SYMFONY) doctrine:mongodb:schema:drop
	@$(SYMFONY) doctrine:mongodb:schema:create
	@$(EXEC_PHP) php bin/console doctrine:mongodb:fixtures:load --no-interaction || true

cache-clear: ## Clears and warms up the application cache for a given environment and debug mode
	$(SYMFONY) c:c

install: composer.lock ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --no-progress --prefer-dist --optimize-autoloader

update: ## Update vendors according to the current composer.json file
	@$(COMPOSER) update --no-progress --prefer-dist --optimize-autoloader

cache-warmup: ## Warmup the Symfony cache
	@$(SYMFONY) cache:warmup

purge: ## Purge cache and logs
	@rm -rf var/cache/* var/logs/*

up: ## Start the docker hub (PHP, caddy)
	$(DOCKER_COMPOSE) up --detach

build: ## Builds the images (PHP, caddy)
	$(DOCKER_COMPOSE) build --pull --no-cache

down: ## Stop the docker hub
	$(DOCKER_COMPOSE) down --remove-orphans

sh: ## Log to the docker container
	@echo "Connecting to core-service PHP container..."
	@$(EXEC_PHP) sh

logs: ## Show all logs
	@$(DOCKER_COMPOSE) logs

new-logs: ## Show live logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

.PHONY: start
start: up build-k6-docker ## Start docker with k6

ps: ## Check docker containers
	$(DOCKER_COMPOSE) ps

stop: ## Stop docker and the Symfony binary server
	$(DOCKER_COMPOSE) stop

commands: ## List all Symfony commands
	@$(SYMFONY) list

coverage-html: ## Create the code coverage report with PHPUnit
	$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage php php -d memory_limit=-1 vendor/bin/phpunit --coverage-html=coverage/html

coverage-xml: ## Create the code coverage report with PHPUnit
	$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage php php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover coverage/coverage.xml

generate-openapi-spec: ## Generate OpenAPI specification
	$(EXEC_PHP) php bin/console api:openapi:export --yaml --output=.github/openapi-spec/spec.yaml

generate-graphql-spec: ## Generate GraphQL specification
	$(EXEC_PHP) php bin/console api:graphql:export --output=.github/graphql-spec/spec

validate-openapi-spec: generate-openapi-spec build-spectral-docker ## Generate and lint the OpenAPI spec with Spectral
	./scripts/validate-openapi-spec.sh

aws-load-tests: ## Run load tests on AWS infrastructure
	tests/Load/aws-execute-load-tests.sh

aws-load-tests-cleanup: ## Cleanup AWS infrastructure after testing
	tests/Load/cleanup.sh

start-prod-loadtest: ## Start production environment with load testing capabilities
	$(DOCKER_COMPOSE) -f docker-compose.loadtest.yml up --detach

stop-prod-loadtest: ## Stop production load testing environment
	$(DOCKER_COMPOSE) -f docker-compose.loadtest.yml down --remove-orphans

validate-configuration: ## Validate configuration structure and detect locked file modifications
	@./scripts/validate-configuration.sh

ci: ## Run comprehensive CI checks (excludes bats and load tests)
	@echo "🚀 Running comprehensive CI checks..."
	@failed_checks=""; \
	echo "2️⃣  Validating composer.json and composer.lock..."; \
	if ! make composer-validate; then failed_checks="$$failed_checks\n❌ composer validation"; fi; \
	echo "3️⃣  Checking Symfony requirements..."; \
	if ! make check-requirements; then failed_checks="$$failed_checks\n❌ Symfony requirements check"; fi; \
	echo "4️⃣  Running security analysis..."; \
	if ! make check-security; then failed_checks="$$failed_checks\n❌ security analysis"; fi; \
	echo "5️⃣  Fixing code style with PHP CS Fixer..."; \
	if ! make phpcsfixer; then failed_checks="$$failed_checks\n❌ PHP CS Fixer"; fi; \
	echo "6️⃣  Running static analysis with Psalm..."; \
	if ! make psalm; then failed_checks="$$failed_checks\n❌ Psalm static analysis"; fi; \
	echo "7️⃣  Running security taint analysis..."; \
	if ! make psalm-security; then failed_checks="$$failed_checks\n❌ Psalm security analysis"; fi; \
	echo "8️⃣  Running code quality analysis with PHPMD..."; \
	if ! make phpmd; then failed_checks="$$failed_checks\n❌ PHPMD quality analysis"; fi; \
	echo "9️⃣  Running code quality analysis with PHPInsights..."; \
	if ! make phpinsights; then failed_checks="$$failed_checks\n❌ PHPInsights quality analysis"; fi; \
	echo "🔟  Validating architecture with Deptrac..."; \
	if ! make deptrac; then failed_checks="$$failed_checks\n❌ Deptrac architecture validation"; fi; \
	echo "1️⃣1️⃣ Running complete test suite (unit, integration, e2e)..."; \
	if ! make unit-tests; then failed_checks="$$failed_checks\n❌ unit tests"; fi; \
	if ! make integration-tests; then failed_checks="$$failed_checks\n❌ integration tests"; fi; \
	if ! make behat; then failed_checks="$$failed_checks\n❌ Behat e2e tests"; fi; \
	echo "1️⃣2️⃣ Running mutation testing with Infection..."; \
	if ! make infection; then failed_checks="$$failed_checks\n❌ mutation testing"; fi; \
	echo "1️⃣3️⃣ Validating OpenAPI specification..."; \
	if ! make validate-openapi-spec; then failed_checks="$$failed_checks\n❌ OpenAPI spec validation"; fi; \
	if [ -n "$$failed_checks" ]; then \
		echo ""; \
		echo "💥 CI checks completed with failures:"; \
		printf "$$failed_checks\n"; \
		echo ""; \
		echo "❌ CI checks failed! Please fix the above issues."; \
		exit 1; \
	else \
		echo "✅ CI checks successfully passed!"; \
	fi

pr-comments: ## Retrieve ALL unresolved comments (including outdated) for current PR (markdown format)
	@if ! command -v gh >/dev/null 2>&1; then \
		echo "Error: GitHub CLI (gh) is required but not installed."; \
		echo "Visit: https://cli.github.com/ for installation instructions"; \
		exit 1; \
	fi
	@if ! command -v jq >/dev/null 2>&1; then \
		echo "Error: jq is required but not installed."; \
		echo "Install via package manager (e.g., apt-get install jq, brew install jq)"; \
		exit 1; \
	fi
ifdef PR
	@echo "Retrieving ALL unresolved comments (including outdated) for PR #$(PR)..."
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="true" \
		./scripts/get-pr-comments.sh "$(PR)" "$${FORMAT:-markdown}"
else
	@echo "Auto-detecting PR from current git branch..."
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="true" \
		./scripts/get-pr-comments.sh "$${FORMAT:-markdown}"
endif

pr-comments-current: ## Retrieve only NON-OUTDATED unresolved comments (markdown format)
	@if ! command -v gh >/dev/null 2>&1; then \
		echo "Error: GitHub CLI (gh) is required but not installed."; \
		echo "Visit: https://cli.github.com/ for installation instructions"; \
		exit 1; \
	fi
	@if ! command -v jq >/dev/null 2>&1; then \
		echo "Error: jq is required but not installed."; \
		echo "Install via package manager (e.g., apt-get install jq, brew install jq)"; \
		exit 1; \
	fi
ifdef PR
	@echo "Retrieving non-outdated comments for PR #$(PR)..."
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="false" \
		./scripts/get-pr-comments.sh "$(PR)" "$${FORMAT:-markdown}"
else
	@echo "Auto-detecting PR from current git branch..."
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="false" \
		./scripts/get-pr-comments.sh "$${FORMAT:-markdown}"
endif

pr-comments-all: ## Retrieve ALL unresolved comments (with pagination) for a GitHub Pull Request
	@if ! command -v gh >/dev/null 2>&1; then \
		echo "Error: GitHub CLI (gh) is required but not installed."; \
		echo "Visit: https://cli.github.com/ for installation instructions"; \
		exit 1; \
	fi
	@if ! command -v jq >/dev/null 2>&1; then \
		echo "Error: jq is required but not installed."; \
		echo "Install via package manager (e.g., apt-get install jq, brew install jq)"; \
		exit 1; \
	fi
ifdef PR
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="$${INCLUDE_OUTDATED:-false}" VERBOSE="$${VERBOSE:-false}" \
		./scripts/get-pr-comments.sh "$(PR)" "$${FORMAT:-text}"
else
	@GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="$${INCLUDE_OUTDATED:-false}" VERBOSE="$${VERBOSE:-false}" \
		./scripts/get-pr-comments.sh "$${FORMAT:-text}"
endif

pr-comments-to-file: ## Fetch ALL unresolved PR comments and save to pr-comments-errors.txt
	@if ! command -v gh >/dev/null 2>&1; then \
		echo "Error: GitHub CLI (gh) is required but not installed."; \
		echo "Visit: https://cli.github.com/ for installation instructions"; \
		exit 1; \
	fi
	@if ! command -v jq >/dev/null 2>&1; then \
		echo "Error: jq is required but not installed."; \
		echo "Install via package manager (e.g., apt-get install jq, brew install jq)"; \
		exit 1; \
	fi
	@echo "📝 Fetching all unresolved PR comments and saving to file..."
	@output_file="$${OUTPUT_FILE:-pr-comments-errors.txt}"; \
	if [ -f "$$output_file" ]; then \
		echo "⚠️  File $$output_file already exists. Creating backup..."; \
		mv "$$output_file" "$$output_file.backup.$(shell date +%Y%m%d_%H%M%S)"; \
	fi; \
	{ \
		echo "========================================"; \
		echo "PR Comments and Errors Report"; \
		echo "Generated: $(shell date '+%Y-%m-%d %H:%M:%S')"; \
		echo "========================================"; \
		echo ""; \
	} > "$$output_file"; \
	if [ -n "$(PR)" ]; then \
		GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="$${INCLUDE_OUTDATED:-true}" VERBOSE="false" \
			./scripts/get-pr-comments.sh "$(PR)" "text" >> "$$output_file" 2>&1 || true; \
	else \
		GITHUB_HOST="$(GITHUB_HOST)" INCLUDE_OUTDATED="$${INCLUDE_OUTDATED:-true}" VERBOSE="false" \
			./scripts/get-pr-comments.sh "text" >> "$$output_file" 2>&1 || true; \
	fi; \
	comment_count=$$(grep -c "^Comment ID:" "$$output_file" || echo "0"); \
	echo "" >> "$$output_file"; \
	echo "========================================" >> "$$output_file"; \
	echo "Report Summary:" >> "$$output_file"; \
	echo "Total Comments Found: $$comment_count" >> "$$output_file"; \
	echo "Report saved to: $$output_file" >> "$$output_file"; \
	echo "========================================" >> "$$output_file"; \
	if [ "$$comment_count" -gt 0 ]; then \
		echo "✅ Report successfully saved to: $$output_file"; \
		echo "📊 Total comments found: $$comment_count"; \
		echo "📄 Total lines in report: $$(wc -l < "$$output_file")"; \
	else \
		echo "⚠️  No unresolved comments found in this PR"; \
		echo "📄 Report saved to: $$output_file"; \
	fi
