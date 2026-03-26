#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make generate-openapi-spec command creates spec file" {
  run make generate-openapi-spec
  assert_success
  assert [ -f ".github/openapi-spec/spec.yaml" ]
}

@test "make generate-graphql-spec command creates spec files" {
  run make generate-graphql-spec
  assert_success
  assert [ -d ".github/graphql-spec" ]
}

@test "make validate-openapi-spec command validates spec" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make openapi-diff command compares specs" {
  skip "Requires git branch setup"
}

@test "openapi-diff workflow normalizes each checkout with its own fixer" {
  run sed -n '/Generate openapi spec/,/Run OpenAPI Diff/p' .github/workflows/openapi-diff.yml
  assert_success
  assert_output --partial 'php scripts/fix-openapi-spec.php .github/openapi-spec/spec.yaml'
  refute_output --partial 'php ../scripts/fix-openapi-spec.php'
}
