#!/usr/bin/env bash
set -euo pipefail

endpoint="${AWS_EMULATOR_ENDPOINT:-http://localhost:${AWS_EMULATOR_PORT:-4566}}"
region="${AWS_SQS_REGION:-${AWS_DEFAULT_REGION:-us-east-1}}"

export AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-${AWS_SQS_KEY:-fake}}"
export AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-${AWS_SQS_SECRET:-fake}}"
export AWS_DEFAULT_REGION="${AWS_DEFAULT_REGION:-${region}}"

aws_cli() {
    aws --endpoint-url="${endpoint}" --region="${region}" "$@"
}

aws_cli sqs list-queues >/dev/null
aws_cli s3api list-buckets >/dev/null
aws_cli iam list-roles >/dev/null
aws_cli sts get-caller-identity >/dev/null
aws_cli ec2 describe-regions >/dev/null

printf 'AWS emulator smoke check passed for SQS, S3, IAM, STS, and EC2 at %s\n' "${endpoint}"
