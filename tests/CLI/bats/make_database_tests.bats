#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make setup-test-db command executes successfully" {
  run make setup-test-db
  assert_success
  assert_output --partial "Clearing the cache"
}

@test "make reset-db command recreates database schema" {
  run make reset-db
  assert_success
}

@test "make load-fixtures command loads test data" {
  run make load-fixtures
  assert_success
}

@test "make fixtures-load command executes" {
  run make fixtures-load
  assert_success
}

