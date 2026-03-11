#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make phpcsfixer command executes" {
  run make phpcsfixer
  assert_success
}

@test "make psalm command executes successfully" {
  run make psalm
  assert_success
}

@test "make psalm-security command executes successfully" {
  run make psalm-security
  assert_output --partial './vendor/bin/psalm --taint-analysis'
  assert_success
}

@test "make deptrac command executes and reports no violations" {
  run make deptrac
  assert_output --partial './vendor/bin/deptrac analyse'
  assert_success
}

@test "make deptrac-debug command executes" {
  run make deptrac-debug
  assert_success
}
