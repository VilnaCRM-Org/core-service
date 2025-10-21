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

@test "make schemathesis-validate command validates API" {
  skip "Requires Docker - skipped in CI environment"
}

