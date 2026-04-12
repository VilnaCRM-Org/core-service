#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make generate-openapi-spec command creates spec file" {
  run make generate-openapi-spec
  assert_success
  assert [ -f ".github/openapi-spec/spec.yaml" ]

  run sed -n "/^    Customer.CustomerCreate:/,/^    Customer.CustomerPatch.jsonMergePatch:/p" .github/openapi-spec/spec.yaml
  assert_success
  assert_output --partial $'confirmed:\n          type: boolean'

  run sed -n "/^  \\/api\\/customer_types:/,/^  '\\/api\\/customer_types\\/{ulid}':/p" .github/openapi-spec/spec.yaml
  assert_success
  assert_output --partial "\$ref: '#/components/schemas/CustomerType.TypeCreate'"
}

@test "make generate-graphql-spec command creates spec files" {
  run make generate-graphql-spec
  assert_success
  assert [ -d ".github/graphql-spec" ]
}

@test "make validate-openapi-spec command validates spec" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make schemathesis-validate command runs multi-phase contract validation" {
  run sed -n '40,60p; /schemathesis-validate:/,/^$/p' Makefile
  assert_success
  assert_output --partial 'SCHEMATHESIS_PHASES ?= examples,coverage,fuzzing'
  assert_output --partial 'SCHEMATHESIS_REPORT_FORMATS ?= junit,har,ndjson'
  assert_output --partial 'SCHEMATHESIS_MAX_EXAMPLES ?= 5'
  assert_output --partial 'SCHEMATHESIS_EXCLUDED_CHECKS ?= negative_data_rejection,positive_data_acceptance'
  assert_output --partial 'chmod 0777 "$(SCHEMATHESIS_REPORT_DIR)"'
  assert_output --partial 'app:seed-schemathesis-data'
  assert_output --partial 'links:'
  assert_output --partial '--mode all'
  assert_output --partial '--exclude-checks "$(SCHEMATHESIS_EXCLUDED_CHECKS)"'
  assert_output --partial '--coverage-format html,markdown'
  assert_output --partial '--report "$(SCHEMATHESIS_REPORT_FORMATS)"'
  refute_output --partial '--phases=examples'
}

@test "graphql-diff workflow uses GraphQL Inspector action" {
  run sed -n '/GraphQL Inspector/,$p' .github/workflows/graphql-diff.yml
  assert_success
  assert_output --partial 'uses: kamilkisiela/graphql-inspector@91cefc9d934ccac1b4be9f26f44b6f533c300247'
  assert_output --partial "schema: '\${{ github.base_ref }}:.github/graphql-spec/spec'"
  refute_output --partial 'temporary fallback'
  refute_output --partial 'npx -y @graphql-inspector/cli'
}

@test "make openapi-diff command compares specs" {
  skip "Requires git branch setup"
}

@test "openapi-diff workflow generates specs without post-export fixer scripts" {
  run sed -n '/Generate openapi spec/,/Run OpenAPI Diff/p' .github/workflows/openapi-diff.yml
  assert_success
  [ -n "$output" ]
  refute_output --partial 'php scripts/fix-openapi-spec.php .github/openapi-spec/spec.yaml'
  refute_output --partial 'php ../scripts/fix-openapi-spec.php'
}

@test "openapi-diff workflow compares against the pull request base ref" {
  run sed -n '/Check out master branch/,/Run OpenAPI Diff/p' .github/workflows/openapi-diff.yml
  assert_success
  assert_output --partial 'ref: ${{ github.event.pull_request.base.ref }}'
  refute_output --partial 'ref: main'
}

@test "openapi-diff workflow uses checked-in base spec for diff" {
  run sed -n '/Check out master branch/,/upload-artifact/p' .github/workflows/openapi-diff.yml
  assert_success
  assert_output --partial 'uses: docker://openapitools/openapi-diff@sha256:82291446e5554742d9c0725d7b315d18e93958c5526f9a663e8885227bdd6cb6'
  assert_output --partial "args: '/github/workspace/head/.github/openapi-spec/spec.yaml /github/workspace/base/.github/openapi-spec/spec.yaml'"
  refute_output --partial 'Generate openapi spec for base'
  refute_output --partial 'working-directory: base'
}

@test "schemathesis workflow starts the app and uses make targets only" {
  run cat .github/workflows/schemathesis.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make schemathesis-validate'
  assert_output --partial 'Upload Schemathesis Report'
  assert_output --partial 'run: make down'
  refute_output --partial 'composer install'
  refute_output --partial 'setup-php'
}
