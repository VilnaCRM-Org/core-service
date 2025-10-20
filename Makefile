# Load environment variables from .env.test
include .env.test

# Parameters
PROJECT       = core-service
GIT_AUTHOR    = Kravalg

# Executables: local only
SYMFONY_BIN   = symfony
DOCKER        = docker
DOCKER_COMPOSE = docker compose
SCHEMATHESIS_IMAGE = schemathesis/schemathesis:latest

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
COVERAGE_CMD = php -d memory_limit=-1 ./vendor/bin/phpunit --coverage-text

GITHUB_HOST ?= github.com
FORMAT ?= markdown
COVERAGE_INTERNAL_CMD = php -d memory_limit=-1 ./vendor/bin/phpunit --testsuite Negative --coverage-clover /coverage/coverage.xml

define DOCKER_EXEC_WITH_ENV
$(DOCKER_COMPOSE) exec -e $(1) php $(2)
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
	$(DOCKER_COMPOSE) exec -e APP_ENV=test -e BATS_LIB_PATH=/usr/lib/bats php bats tests/CLI/bats/

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

phpmd: ## Instant PHP MD quality checks, static analysis, and complexity insights
	$(EXEC_ENV) ./vendor/bin/phpmd src ansi phpmd.xml

phpinsights: phpmd ## Instant PHP quality checks, static analysis, and complexity insights
	$(EXEC_ENV) ./vendor/bin/phpinsights --no-interaction --flush-cache --fix --ansi --disable-security-check
	$(EXEC_ENV) ./vendor/bin/phpinsights analyse tests --no-interaction --flush-cache --fix --disable-security-check --config-path=phpinsights-tests.php

unit-tests: ## Run unit tests with 100% coverage requirement
	@echo "Running unit tests with coverage requirement of 100%..."
	@$(RUN_TESTS_COVERAGE) --testsuite=Unit 2>&1 | tee /tmp/phpunit_output.txt
	@coverage=$$(sed 's/\x1b\[[0-9;]*m//g' /tmp/phpunit_output.txt | grep "^  Lines:" | awk '{print $$2}' | sed 's/%//' | head -1); \
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

setup-test-db: ## Create database for testing purposes
	$(SYMFONY_TEST_ENV) c:c
	-$(SYMFONY_TEST_ENV) doctrine:mongodb:schema:drop
	$(SYMFONY_TEST_ENV) doctrine:mongodb:schema:create

behat: setup-test-db ## A php framework for autotesting business expectations
	$(EXEC_ENV) $(BEHAT)

integration-tests: setup-test-db ## Run integration tests
	$(RUN_TESTS_COVERAGE) --testsuite=Integration

integration-negative-tests: ## Run integration negative tests
	$(EXEC_ENV) $(PHPUNIT) --testsuite=Negative

fixtures-load: ## Run fixtures
	$(SYMFONY_TEST_ENV) doctrine:mongodb:fixtures:load -n

tests-with-coverage: ## Run tests with coverage
	$(RUN_TESTS_COVERAGE)

negative-tests-with-coverage: ## Run negative tests with coverage reporting
	$(RUN_INTERNAL_TESTS_COVERAGE)

all-tests: unit-tests integration-tests behat ## Run unit, integration and e2e tests

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

build-k6-docker:
	$(DOCKER) build -t k6 -f ./tests/Load/Dockerfile .

build-spectral-docker:
	$(DOCKER) build -t core-service-spectral -f ./docker/spectral/Dockerfile .

infection: ## Run mutation testing with 100% MSI requirement
	$(EXEC_ENV) php -d memory_limit=-1 $(INFECTION) --test-framework-options="--testsuite=Unit" --show-mutations --log-verbosity=all -j8 --min-msi=100 --min-covered-msi=100

execute-load-tests-script: build-k6-docker ## Execute single load test scenario.
	tests/Load/execute-load-test.sh $(scenario) $(or $(runSmoke),true) $(or $(runAverage),true) $(or $(runStress),true) $(or $(runSpike),true)

reset-db: ## Recreate the database schema for ephemeral test runs
	@$(SYMFONY) doctrine:cache:clear-metadata
	@$(SYMFONY) doctrine:mongodb:schema:drop --if-exists
	@$(SYMFONY) doctrine:mongodb:schema:create

load-fixtures: ## Build the DB, control the schema validity, and load fixtures
	@$(SYMFONY) doctrine:cache:clear-metadata
	@$(SYMFONY) doctrine:mongodb:schema:drop --if-exists
	@$(SYMFONY) doctrine:mongodb:schema:create
	@$(SYMFONY) doctrine:schema:validate
	@$(EXEC_PHP) php bin/console doctrine:mongodb:fixtures:load --no-interaction

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

validate-openapi-spec: generate-openapi-spec build-spectral-docker ## Generate and lint the OpenAPI spec with Spectral
	./scripts/validate-openapi-spec.sh

openapi-diff: generate-openapi-spec ## Compare the generated OpenAPI spec against the base reference using OpenAPI Diff
	./scripts/openapi-diff.sh $(or $(base_ref),origin/main)

schemathesis-validate: reset-db generate-openapi-spec ## Validate the running API against the OpenAPI spec with Schemathesis
	$(DOCKER) run --rm --network=host -v $(CURDIR)/.github/openapi-spec:/data $(SCHEMATHESIS_IMAGE) run --checks all /data/spec.yaml --url https://localhost --tls-verify=false

generate-graphql-spec: ## Generate GraphQL specification
	$(EXEC_PHP) php bin/console api:graphql:export --output=.github/graphql-spec/spec

aws-load-tests: ## Run load tests on AWS infrastructure
	tests/Load/aws-execute-load-tests.sh

aws-load-tests-cleanup: ## Cleanup AWS infrastructure after testing
	tests/Load/cleanup.sh

start-prod-loadtest: ## Start production environment with load testing capabilities
	$(DOCKER_COMPOSE) -f docker-compose.loadtest.yml up --detach

stop-prod-loadtest: ## Stop production load testing environment
	$(DOCKER_COMPOSE) -f docker-compose.loadtest.yml down --remove-orphans

ci: ## Run comprehensive CI checks (excludes bats and load tests)
	@echo "🚀 Running comprehensive CI checks..."
	@failed_checks=""; \
	echo "1️⃣  Validating composer.json and composer.lock..."; \
	if ! make composer-validate; then failed_checks="$$failed_checks\n❌ composer validation"; fi; \
	echo "2️⃣  Checking Symfony requirements..."; \
	if ! make check-requirements; then failed_checks="$$failed_checks\n❌ Symfony requirements check"; fi; \
	echo "3️⃣  Running security analysis..."; \
	if ! make check-security; then failed_checks="$$failed_checks\n❌ security analysis"; fi; \
	echo "4️⃣  Fixing code style with PHP CS Fixer..."; \
	if ! make phpcsfixer; then failed_checks="$$failed_checks\n❌ PHP CS Fixer"; fi; \
	echo "5️⃣  Running static analysis with Psalm..."; \
	if ! make psalm; then failed_checks="$$failed_checks\n❌ Psalm static analysis"; fi; \
	echo "6️⃣  Running security taint analysis..."; \
	if ! make psalm-security; then failed_checks="$$failed_checks\n❌ Psalm security analysis"; fi; \
	echo "7️⃣  Running code quality analysis with PHPMD..."; \
	if ! make phpmd; then failed_checks="$$failed_checks\n❌ PHPMD quality analysis"; fi; \
	echo "8️⃣  Running code quality analysis with PHPInsights..."; \
	if ! make phpinsights; then failed_checks="$$failed_checks\n❌ PHPInsights quality analysis"; fi; \
	echo "9️⃣  Validating architecture with Deptrac..."; \
	if ! make deptrac; then failed_checks="$$failed_checks\n❌ Deptrac architecture validation"; fi; \
	echo "🔟 Running complete test suite (unit, integration, e2e)..."; \
	if ! make unit-tests; then failed_checks="$$failed_checks\n❌ unit tests"; fi; \
	if ! make integration-tests; then failed_checks="$$failed_checks\n❌ integration tests"; fi; \
	if ! make behat; then failed_checks="$$failed_checks\n❌ Behat e2e tests"; fi; \
	echo "1️⃣1️⃣ Running mutation testing with Infection..."; \
	if ! make infection; then failed_checks="$$failed_checks\n❌ mutation testing"; fi; \
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

pr-comments: ## Retrieve unresolved comments for a GitHub Pull Request
	@if ! command -v gh >/dev/null 2>&1; then \
		echo "Error: GitHub CLI (gh) is required but not installed."; \
		echo "Visit: https://cli.github.com/ for installation instructions"; \
		exit 1; \
	fi
ifdef PR
	@echo "Retrieving unresolved comments for PR #$(PR)..."
	@GITHUB_HOST="$(GITHUB_HOST)" ./scripts/get-pr-comments.sh "$(PR)" "$(FORMAT)"
else
	@echo "Auto-detecting PR from current git branch..."
	@GITHUB_HOST="$(GITHUB_HOST)" ./scripts/get-pr-comments.sh "$(FORMAT)"
endif
