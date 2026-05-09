# Architecture: Floci AWS Emulator Migration

## Runtime Topology

```text
host
  -> localhost:${AWS_EMULATOR_PORT}
  -> docker compose published port
  -> aws-emulator:4566
  -> Floci AWS API router
```

Sibling containers use `http://aws-emulator:4566` through Docker DNS.

## Application Configuration

Symfony SQS clients now receive:

```yaml
endpoint: '%env(AWS_SQS_ENDPOINT)%'
```

Messenger DSNs keep the queue-name path form:

```dotenv
sqs://${AWS_EMULATOR_HOST}:${AWS_EMULATOR_PORT}/cache-refresh?...
```

This preserves Symfony Messenger SQS auto-setup behavior while removing LocalStack-specific host naming.

## Healthchecks

Compose healthchecks use:

```bash
aws --endpoint-url=http://localhost:4566 sqs list-queues
```

The healthcheck validates the AWS SQS API directly. It does not depend on LocalStack-compatible status endpoints that Floci happens to expose for migration compatibility.

## Queue URL Resolution

Floci is configured with:

- `FLOCI_HOSTNAME=aws-emulator`
- `FLOCI_BASE_URL=http://aws-emulator:4566`

This makes returned QueueUrls resolvable from PHP and other sibling containers.

## Helper Script Validation

`scripts/aws-emulator-smoke.sh` validates the local APIs used by repository helper scripts:

- SQS
- S3
- IAM
- STS
- EC2
