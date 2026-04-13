# Preserve environment-provided workspace port overrides before loading .env.test.
HTTP_PORT_OVERRIDE := $(value HTTP_PORT)
HTTPS_PORT_OVERRIDE := $(value HTTPS_PORT)
HTTP3_PORT_OVERRIDE := $(value HTTP3_PORT)
DB_PORT_OVERRIDE := $(value DB_PORT)
REDIS_PORT_OVERRIDE := $(value REDIS_PORT)
LOCALSTACK_PORT_OVERRIDE := $(value LOCALSTACK_PORT)
STRUCTURIZR_PORT_OVERRIDE := $(value STRUCTURIZR_PORT)

# Load environment variables from .env.test
include .env.test

ifneq ($(strip $(HTTP_PORT_OVERRIDE)),)
HTTP_PORT := $(HTTP_PORT_OVERRIDE)
endif
ifneq ($(strip $(HTTPS_PORT_OVERRIDE)),)
HTTPS_PORT := $(HTTPS_PORT_OVERRIDE)
endif
ifneq ($(strip $(HTTP3_PORT_OVERRIDE)),)
HTTP3_PORT := $(HTTP3_PORT_OVERRIDE)
endif
ifneq ($(strip $(DB_PORT_OVERRIDE)),)
DB_PORT := $(DB_PORT_OVERRIDE)
endif
ifneq ($(strip $(REDIS_PORT_OVERRIDE)),)
REDIS_PORT := $(REDIS_PORT_OVERRIDE)
endif
ifneq ($(strip $(LOCALSTACK_PORT_OVERRIDE)),)
LOCALSTACK_PORT := $(LOCALSTACK_PORT_OVERRIDE)
endif
ifneq ($(strip $(STRUCTURIZR_PORT_OVERRIDE)),)
STRUCTURIZR_PORT := $(STRUCTURIZR_PORT_OVERRIDE)
endif

# Parameters
PROJECT       = core-service
GIT_AUTHOR    = Kravalg

# Executables: local only
SYMFONY_BIN   = symfony
DOCKER        = docker
DOCKER_COMPOSE = docker compose
SCHEMATHESIS_VERSION ?= 4.15.1
SCHEMATHESIS_IMAGE ?= schemathesis/schemathesis:$(SCHEMATHESIS_VERSION)
SCHEMATHESIS_API_URL ?= http://localhost$(if $(strip $(HTTP_PORT)),:$(HTTP_PORT),)
SCHEMATHESIS_REPORT_DIR ?= /tmp/$(PROJECT)-schemathesis-report
SCHEMATHESIS_PHASES ?= examples,coverage,fuzzing
SCHEMATHESIS_REPORT_FORMATS ?= junit,har,ndjson
SCHEMATHESIS_MAX_EXAMPLES ?= 5
SCHEMATHESIS_MAX_FAILURES ?= 20
SCHEMATHESIS_REQUEST_TIMEOUT ?= 10
SCHEMATHESIS_REQUEST_RETRIES ?= 2
SCHEMATHESIS_EXCLUDED_CHECKS ?= negative_data_rejection,positive_data_acceptance

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
PSALM         = php -d display_errors=0 -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' ./vendor/bin/psalm
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
MEMORY_COVERAGE_TEXT_FILE = memory-coverage.txt
MEMORY_COVERAGE_XML_FILE = coverage/memory-coverage.xml
MEMORY_COVERAGE_HOST_FILE ?= /tmp/memory-coverage.xml
MEMORY_COVERAGE_CMD = php -d memory_limit=-1 -d xdebug.mode=coverage ./vendor/bin/phpunit --configuration phpunit.memory.xml.dist --coverage-text=$(MEMORY_COVERAGE_TEXT_FILE) --coverage-clover $(MEMORY_COVERAGE_XML_FILE) --colors=never
SOAK_ITERATIONS ?= 3
WORKER_MEMORY_SERVICE ?= caddy
WORKER_MEMORY_REPORT ?= tests/Load/results/frankenphp-worker-memory.txt
WORKER_MEMORY_ALLOWED_GROWTH_MIB ?= 32

GITHUB_HOST ?= github.com
FORMAT ?= markdown
COVERAGE_INTERNAL_CMD = php -d memory_limit=-1 -d xdebug.mode=coverage ./vendor/bin/phpunit --testsuite Negative --coverage-clover /coverage/coverage.xml
BATS_BIN ?= bats
BATS_FILES ?= tests/CLI/bats/
BATS_ARGS ?=
DOCKER_TTY_FLAG = $(if $(CI),-T,)
BMALPH_PLATFORM ?= codex
BMALPH_DRY_RUN ?= false

define DOCKER_EXEC_WITH_ENV
$(DOCKER_COMPOSE) exec $(DOCKER_TTY_FLAG) -e $(1) php $(2)
endef

define RUN_SCHEMA_CREATE_TOLERANT
output=$$($(1) doctrine:mongodb:schema:create 2>&1); status=$$?; \
printf '%s\n' "$$output"; \
if [ $$status -ne 0 ] && ! printf '%s' "$$output" | grep -q 'already exists'; then exit $$status; fi
endef

# Conditional execution based on CI environment variable
ifeq ($(CI),1)
    RUN_PHP_CS_FIXER = $(FIXER_ENV) $(PHP_CS_FIXER_CMD)
    RUN_TESTS_COVERAGE = XDEBUG_MODE=coverage $(COVERAGE_CMD)
    RUN_MEMORY_TESTS_COVERAGE = XDEBUG_MODE=coverage $(MEMORY_COVERAGE_CMD)
    RUN_INTERNAL_TESTS_COVERAGE = XDEBUG_MODE=coverage $(COVERAGE_INTERNAL_CMD)
else
    RUN_PHP_CS_FIXER = $(call DOCKER_EXEC_WITH_ENV,$(FIXER_ENV),$(PHP_CS_FIXER_CMD))
    RUN_TESTS_COVERAGE = $(call DOCKER_EXEC_WITH_ENV,APP_ENV=test -e XDEBUG_MODE=coverage,$(COVERAGE_CMD))
    RUN_MEMORY_TESTS_COVERAGE = $(call DOCKER_EXEC_WITH_ENV,APP_ENV=test -e XDEBUG_MODE=coverage,$(MEMORY_COVERAGE_CMD))
    RUN_INTERNAL_TESTS_COVERAGE = $(call DOCKER_EXEC_WITH_ENV,APP_ENV=test -e XDEBUG_MODE=coverage,$(COVERAGE_INTERNAL_CMD))
endif

export SYMFONY
export HTTP_PORT HTTPS_PORT HTTP3_PORT DB_PORT REDIS_PORT LOCALSTACK_PORT STRUCTURIZR_PORT

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -hE '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

bmalph-install: ## Install and verify BMALPH for BMALPH_PLATFORM=codex|claude-code
	bash scripts/local-coder/install-bmalph.sh --platform "$(BMALPH_PLATFORM)"

bmalph-codex: ## Install and verify BMALPH for Codex
	@$(MAKE) bmalph-install BMALPH_PLATFORM=codex

bmalph-claude: ## Install and verify BMALPH for Claude Code
	@$(MAKE) bmalph-install BMALPH_PLATFORM=claude-code

bmalph-init: ## Initialize BMALPH for current project; set BMALPH_DRY_RUN=true to preview safely
	bash scripts/local-coder/install-bmalph.sh --platform "$(BMALPH_PLATFORM)" --init $(if $(filter true TRUE 1 yes YES,$(BMALPH_DRY_RUN)),--dry-run,)

bmalph-setup: ## Install and initialize BMALPH for current project; defaults to BMALPH_PLATFORM=codex
	@$(MAKE) bmalph-init BMALPH_PLATFORM="$(BMALPH_PLATFORM)" BMALPH_DRY_RUN="$(BMALPH_DRY_RUN)"

bats: ## Run tests for bash commands
	$(BATS_BIN) $(BATS_ARGS) $(BATS_FILES)

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
	@rm -f coverage.txt; \
	tmpfile=$$(mktemp); \
	script -qec "$(RUN_TESTS_COVERAGE) --testsuite=Unit" /dev/null > $$tmpfile 2>&1; \
	test_status=$$?; \
	cat $$tmpfile; \
	if [ $$test_status -ne 0 ]; then \
		echo "❌ TEST FAILURE: Unit tests returned a non-zero exit code ($$test_status)."; \
		rm -f $$tmpfile; \
		exit $$test_status; \
	fi; \
	if sed 's/\x1b\[[0-9;]*m//g' $$tmpfile | grep -Eq 'FAILURES!|ERRORS!|[Ii]ncomplete'; then \
		echo "❌ TEST FAILURE: Unit tests reported failures, errors, or incomplete tests."; \
		rm -f $$tmpfile; \
		exit 1; \
	fi; \
	if [ ! -f coverage.txt ]; then \
		echo "❌ ERROR: coverage.txt was not generated."; \
		rm -f $$tmpfile; \
		exit 1; \
	fi; \
	coverage=$$(sed 's/\x1b\[[0-9;]*m//g' coverage.txt | tr -d '\r' | sed -n 's/.*Lines:[[:space:]]*\([0-9.]*\)%.*/\1/p' | head -1); \
	rm -f $$tmpfile coverage.txt; \
	if [ -n "$$coverage" ]; then \
		if perl -e 'exit(($$ARGV[0] < 100) ? 0 : 1)' "$$coverage"; then \
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
	@attempt=1; \
	max_attempts=$${DOCKER_COMPOSE_UP_RETRIES:-5}; \
	retry_delay=$${DOCKER_COMPOSE_UP_RETRY_DELAY_SECONDS:-5}; \
	until $(DOCKER_COMPOSE) up --detach --wait database redis php caddy localstack; do \
		if [ $$attempt -ge $$max_attempts ]; then \
			echo "❌ Failed to start required test services after $$attempt attempts."; \
			$(DOCKER_COMPOSE) ps || true; \
			exit 1; \
		fi; \
		echo "⚠️  Failed to start required test services (attempt $$attempt/$$max_attempts). Retrying..."; \
		$(DOCKER_COMPOSE) ps || true; \
		attempt=$$((attempt + 1)); \
		sleep $$retry_delay; \
	done; \
	$(DOCKER_COMPOSE) exec php sh -lc 'mkdir -p var/cache/dev/doctrine/odm/mongodb/Proxies var/cache/test var/log && chmod -R 777 var/cache var/log'

setup-test-db: ensure-test-services ## Create database for testing purposes
	$(SYMFONY_TEST_ENV) c:c
	-$(SYMFONY_TEST_ENV) doctrine:mongodb:schema:drop
	@$(call RUN_SCHEMA_CREATE_TOLERANT,$(SYMFONY_TEST_ENV))

behat: setup-test-db ## A php framework for autotesting business expectations
	$(EXEC_ENV) $(BEHAT)

integration-tests: setup-test-db ## Run integration tests
	$(RUN_TESTS_COVERAGE) --testsuite=Integration

memory-tests: setup-test-db ## Run memory-safety tests with 100% coverage requirement for memory-support helpers
	@echo "Running memory tests with coverage requirement of 100%..."
	@mkdir -p coverage
	@rm -f $(MEMORY_COVERAGE_TEXT_FILE) $(MEMORY_COVERAGE_XML_FILE); \
	tmpfile=$$(mktemp); \
	script -qec "$(RUN_MEMORY_TESTS_COVERAGE)" /dev/null > $$tmpfile 2>&1; \
	test_status=$$?; \
	cat $$tmpfile; \
	if [ $$test_status -ne 0 ]; then \
		echo "❌ TEST FAILURE: Memory tests returned a non-zero exit code ($$test_status)."; \
		rm -f $$tmpfile $(MEMORY_COVERAGE_TEXT_FILE); \
		exit $$test_status; \
	fi; \
	if sed 's/\x1b\[[0-9;]*m//g' $$tmpfile | grep -Eq 'FAILURES!|ERRORS!|[Ii]ncomplete'; then \
		echo "❌ TEST FAILURE: Memory tests reported failures, errors, or incomplete tests."; \
		rm -f $$tmpfile $(MEMORY_COVERAGE_TEXT_FILE); \
		exit 1; \
	fi; \
	if [ ! -f $(MEMORY_COVERAGE_TEXT_FILE) ]; then \
		echo "❌ ERROR: $(MEMORY_COVERAGE_TEXT_FILE) was not generated."; \
		rm -f $$tmpfile; \
		exit 1; \
	fi; \
	if [ ! -f $(MEMORY_COVERAGE_XML_FILE) ]; then \
		echo "❌ ERROR: $(MEMORY_COVERAGE_XML_FILE) was not generated."; \
		rm -f $$tmpfile $(MEMORY_COVERAGE_TEXT_FILE); \
		exit 1; \
	fi; \
	coverage=$$(sed 's/\x1b\[[0-9;]*m//g' $(MEMORY_COVERAGE_TEXT_FILE) | tr -d '\r' | sed -n 's/.*Lines:[[:space:]]*\([0-9.]*\)%.*/\1/p' | head -1); \
	rm -f $$tmpfile $(MEMORY_COVERAGE_TEXT_FILE); \
	if [ -n "$$coverage" ]; then \
		if perl -e 'exit(($$ARGV[0] < 100) ? 0 : 1)' "$$coverage"; then \
			echo "❌ COVERAGE FAILURE: Memory-support helper coverage is $$coverage%, but 100% is required."; \
			exit 1; \
		else \
			echo "✅ COVERAGE SUCCESS: Memory-support helper coverage is $$coverage%"; \
		fi; \
	else \
		echo "❌ ERROR: Could not parse coverage from memory test output"; \
		exit 1; \
	fi

integration-negative-tests: ## Run integration negative tests
	$(EXEC_ENV) $(PHPUNIT) --testsuite=Negative

fixtures-load: ## Run fixtures
	$(SYMFONY_TEST_ENV) doctrine:mongodb:fixtures:load -n || true

tests-with-coverage: ## Run tests with coverage
	$(RUN_TESTS_COVERAGE)

negative-tests-with-coverage: ## Run negative tests with coverage reporting
	$(RUN_INTERNAL_TESTS_COVERAGE)

all-tests: unit-tests integration-tests memory-tests behat ## Run unit, integration, memory and e2e tests

worker-mode-verification: memory-tests ## Run same-kernel memory tests and repeated smoke load tests against FrankenPHP worker mode
	@LOAD_TEST_API_HOST="$${LOAD_TEST_API_HOST:-localhost}" \
	LOAD_TEST_API_PORT="$${LOAD_TEST_API_PORT:-$(if $(strip $(HTTP_PORT)),$(HTTP_PORT),80)}" \
	SOAK_ITERATIONS="$(SOAK_ITERATIONS)" \
	WORKER_MEMORY_SERVICE="$(WORKER_MEMORY_SERVICE)" \
	WORKER_MEMORY_REPORT="$(WORKER_MEMORY_REPORT)" \
	WORKER_MEMORY_ALLOWED_GROWTH_MIB="$(WORKER_MEMORY_ALLOWED_GROWTH_MIB)" \
	bash tests/Load/verify-frankenphp-worker-memory.sh

export-memory-coverage: ## Copy the memory-suite coverage report from the PHP container to the host
	@mkdir -p "$(dir $(MEMORY_COVERAGE_HOST_FILE))"
	$(DOCKER_COMPOSE) cp php:/srv/app/$(MEMORY_COVERAGE_XML_FILE) $(MEMORY_COVERAGE_HOST_FILE)

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

reset-db: ensure-test-services ## Recreate the database schema for ephemeral test runs
	@$(SYMFONY) doctrine:mongodb:cache:clear-metadata
	-@$(SYMFONY) doctrine:mongodb:schema:drop
	@$(call RUN_SCHEMA_CREATE_TOLERANT,$(SYMFONY))

load-fixtures: ## Build the DB, control the schema validity, and load fixtures
	@$(SYMFONY) doctrine:mongodb:cache:clear-metadata
	-@$(SYMFONY) doctrine:mongodb:schema:drop
	@$(call RUN_SCHEMA_CREATE_TOLERANT,$(SYMFONY))
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
start: ensure-test-services build-k6-docker ## Start docker, wait for required services, and build k6

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

schemathesis-validate: ensure-test-services reset-db generate-openapi-spec ## Run Schemathesis contract validation against the live API
	@rm -rf "$(SCHEMATHESIS_REPORT_DIR)"
	@mkdir -p "$(SCHEMATHESIS_REPORT_DIR)"
	@chmod 0777 "$(SCHEMATHESIS_REPORT_DIR)"
	$(EXEC_PHP) php bin/console app:seed-schemathesis-data
	@phases="$(SCHEMATHESIS_PHASES)"; \
	if grep -Eq '^[[:space:]]+links:' .github/openapi-spec/spec.yaml; then \
		phases="$$phases,stateful"; \
		echo "OpenAPI links detected; enabling Schemathesis stateful phase."; \
	else \
		echo "No OpenAPI links detected; skipping Schemathesis stateful phase."; \
	fi; \
	$(DOCKER) run --rm --network=host \
		-v "$(CURDIR)/.github/openapi-spec:/schema" \
		-v "$(SCHEMATHESIS_REPORT_DIR):/reports" \
		$(SCHEMATHESIS_IMAGE) run /schema/spec.yaml \
		--url "$(SCHEMATHESIS_API_URL)" \
		--checks all \
		--exclude-checks "$(SCHEMATHESIS_EXCLUDED_CHECKS)" \
		--phases="$$phases" \
		--workers 1 \
		--mode all \
		--max-examples "$(SCHEMATHESIS_MAX_EXAMPLES)" \
		--generation-deterministic \
		--generation-unique-inputs \
		--max-failures "$(SCHEMATHESIS_MAX_FAILURES)" \
		--request-timeout "$(SCHEMATHESIS_REQUEST_TIMEOUT)" \
		--request-retries "$(SCHEMATHESIS_REQUEST_RETRIES)" \
		--report "$(SCHEMATHESIS_REPORT_FORMATS)" \
		--report-dir /reports \
		--report-junit-path /reports/junit.xml \
		--report-har-path /reports/har.json \
		--report-ndjson-path /reports/events.ndjson \
		--coverage-format html,markdown \
		--coverage-report-html-path /reports/schema-coverage.html \
		--coverage-report-markdown-path /reports/schema-coverage.md \
		--coverage-show-missing parameters \
		--header "X-Schemathesis-Test: cleanup-customers"

aws-load-tests: ## Run load tests on AWS infrastructure
	@if [ "$(LOCAL_MODE_ENV)" = "true" ]; then $(MAKE) ensure-test-services; fi
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
	echo "1️⃣1️⃣ Running complete test suite (unit, integration, memory, e2e)..."; \
	if ! make unit-tests; then failed_checks="$$failed_checks\n❌ unit tests"; fi; \
	if ! make integration-tests; then failed_checks="$$failed_checks\n❌ integration tests"; fi; \
	if ! make memory-tests; then failed_checks="$$failed_checks\n❌ memory tests"; fi; \
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
