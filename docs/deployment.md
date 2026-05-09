# Deployment

This guide covers the deployment concerns that are specific to Core Service. It assumes the target platform already provides container orchestration, TLS termination, secret management, and observability.

## Runtime Components

Core Service runs as a PHP/Symfony application with these required dependencies:

- PHP application container
- Caddy or another HTTP edge for local and deployed HTTP routing
- MongoDB for customer, customer type, and customer status documents
- Redis for cache storage
- Amazon SQS-compatible queue transport for asynchronous work
- Structurizr workspace service for architecture diagrams when enabled

The local Docker Compose stack is the reference environment for wiring these services together.

## Configuration Inputs

Deployment configuration is supplied through environment variables. Keep production values in the platform secret store rather than committed `.env` files.

Required production values include:

- `APP_ENV=prod`
- `APP_SECRET`
- `DB_URL`
- `DOMAIN_EVENTS_TRANSPORT_DSN`
- `FAILED_DOMAIN_EVENTS_TRANSPORT_DSN`
- `CACHE_REFRESH_TRANSPORT_DSN`
- `FAILED_CACHE_REFRESH_TRANSPORT_DSN`
- `AWS_SQS_REGION`
- `AWS_SQS_KEY`
- `AWS_SQS_SECRET`
- `EMAIL_QUEUE_NAME`
- `SERVER_NAME`

See [advanced-configuration.md](advanced-configuration.md) for the complete environment-variable catalogue.

## Build and Release Flow

1. Build the application image from the repository Dockerfile.
2. Install Composer dependencies from the committed `composer.lock`.
3. Run CI quality gates before promotion:
   - `make validate-configuration`
   - `make docs-check`
   - `make psalm`
   - `make deptrac`
   - `make unit-tests`
   - `make behat`
4. Publish the immutable image to the registry used by the deployment platform.
5. Roll out the image with the approved production environment variables.
6. Run smoke checks against `/api/health`, `/api/docs`, and one read-only collection endpoint.

## Database and Queue Preparation

Core Service uses MongoDB ODM mappings under `config/doctrine/`. The deployment platform must provide the database before the app starts.

For new environments:

```bash
make up
make setup-test-db
```

Production schema creation should be handled by the deployment runbook for the target platform. Do not run destructive schema-drop commands against production databases.

Queue configuration is handled by Symfony Messenger. Ensure the configured queue exists and the worker runtime has permission to consume and acknowledge messages.

## Health and Smoke Checks

Use the health endpoint for readiness checks:

```bash
curl -k https://localhost/api/health
```

A healthy response confirms that the application can execute its configured health subscribers. For deeper release validation, also confirm that API documentation loads and an authenticated API flow can create, read, update, and delete a test customer in a non-production environment.

## Rollback

Rollback should deploy the previously known-good image and preserve database data. Because MongoDB schema changes are owned by Doctrine ODM mappings, any future migration that changes document shape must include a dedicated rollback note in the same PR.

## Related Documents

- [database.md](database.md)
- [operational.md](operational.md)
- [security.md](security.md)
- [testing.md](testing.md)
