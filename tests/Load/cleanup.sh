#!/bin/bash
set -e

CONFIG_FILE="./tests/Load/config.sh"
if [ -f "$CONFIG_FILE" ]; then
  . "$CONFIG_FILE"
else
  echo "Configuration file config.sh not found."
  exit 1
fi

if [ -z "$BUCKET_FILE" ]; then
  echo "Error: BUCKET_FILE is not set. Exiting."
  exit 1
elif [ ! -f "$BUCKET_FILE" ]; then
  echo "Error: Bucket name file ($BUCKET_FILE) not found. Exiting."
  exit 1
fi

BUCKET_NAME=$(cat "$BUCKET_FILE")

if [ -z "$BUCKET_NAME" ]; then
  echo "Error: BUCKET_FILE is empty. Exiting."
  exit 1
fi

echo "Found bucket name: $BUCKET_NAME"

echo "Deleting S3 bucket and its contents: $BUCKET_NAME"
if $AWS_CLI s3 ls "s3://$BUCKET_NAME" --region "$REGION" >/dev/null 2>&1; then
    $AWS_CLI s3 rm "s3://$BUCKET_NAME" --recursive --region "$REGION"
    $AWS_CLI s3 rb "s3://$BUCKET_NAME" --region "$REGION"
    echo "S3 bucket $BUCKET_NAME deleted."
else
    echo "S3 bucket $BUCKET_NAME not found."
fi

echo "Terminating EC2 instances with tag: $INSTANCE_TAG"
INSTANCE_IDS=$($AWS_CLI ec2 describe-instances \
  --filters "Name=tag:Name,Values=$INSTANCE_TAG" "Name=instance-state-name,Values=running" \
  --query "Reservations[*].Instances[*].InstanceId" --output text --region "$REGION")

if [ -n "$INSTANCE_IDS" ]; then
    $AWS_CLI ec2 terminate-instances --instance-ids $INSTANCE_IDS --region "$REGION"
    echo "Instances with IDs $INSTANCE_IDS are being terminated."

    $AWS_CLI ec2 wait instance-terminated --instance-ids $INSTANCE_IDS --region "$REGION"
    echo "Instances terminated."
else
    echo "No running instances found with tag: $INSTANCE_TAG"
fi

echo "Detaching IAM role policies..."
POLICY_ARN=$($AWS_CLI iam list-policies --scope Local --query "Policies[?PolicyName=='S3WriteAccessToBucket'].Arn" --output text --region "$REGION")

if [ -n "$POLICY_ARN" ]; then
    $AWS_CLI iam detach-role-policy --role-name "$ROLE_NAME" --policy-arn "$POLICY_ARN" --region "$REGION"
    $AWS_CLI iam delete-policy --policy-arn "$POLICY_ARN" --region "$REGION"
    echo "Deleted policy: $POLICY_ARN"
else
    echo "Policy not found: S3WriteAccessToBucket"
fi

echo "Deleting IAM instance profile and role..."
if $AWS_CLI iam get-instance-profile --instance-profile-name "$ROLE_NAME" --region "$REGION" >/dev/null 2>&1; then
    $AWS_CLI iam remove-role-from-instance-profile --instance-profile-name "$ROLE_NAME" --role-name "$ROLE_NAME" --region "$REGION"
    $AWS_CLI iam delete-instance-profile --instance-profile-name "$ROLE_NAME" --region "$REGION"
fi

if $AWS_CLI iam get-role --role-name "$ROLE_NAME" --region "$REGION" >/dev/null 2>&1; then
    $AWS_CLI iam delete-role --role-name "$ROLE_NAME" --region "$REGION"
    echo "IAM role $ROLE_NAME deleted."
else
    echo "IAM role $ROLE_NAME not found."
fi

echo "Deleting security group: $SECURITY_GROUP_NAME"
SECURITY_GROUP_ID=$($AWS_CLI ec2 describe-security-groups \
    --filters "Name=group-name,Values=$SECURITY_GROUP_NAME" \
    --query 'SecurityGroups[0].GroupId' --output text --region "$REGION")

if [ -n "$SECURITY_GROUP_ID" ]; then
    $AWS_CLI ec2 delete-security-group --group-id "$SECURITY_GROUP_ID" --region "$REGION"
    echo "Security group $SECURITY_GROUP_NAME deleted."
else
    echo "Security group $SECURITY_GROUP_NAME not found."
fi

rm -f "$BUCKET_FILE"

echo "Cleanup complete!"
