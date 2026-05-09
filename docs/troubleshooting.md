# Troubleshooting

Use this guide when local setup, CI checks, API access, database connectivity, or queue processing fails.

## Local Stack Does Not Start

Run:

```bash
docker compose ps
docker compose logs --tail=100
```

Common causes:

- a configured port is already in use
- Docker Desktop or the Docker daemon is not running
- a previous stack left stale containers behind
- local environment overrides conflict with `.env.test`

Stop and restart the stack:

```bash
make down
make up
```

If a port conflict persists, override the relevant port when invoking Make, for example `HTTP_PORT=8088 make up`.

## Composer Dependencies Are Missing

If vendor binaries are missing or PHP commands fail with autoload errors, install dependencies inside the PHP container:

```bash
make install
```

Use the committed `composer.lock` for reproducible installs.

## API Documentation Does Not Load

Check that the HTTP stack is running:

```bash
make up
curl -k https://localhost/api/docs
```

If the route returns an error, inspect application logs:

```bash
make logs
```

OpenAPI generation depends on API Platform resource configuration under `config/api_platform/resources/` and OpenAPI customization code under `src/Shared/Application/OpenApi/`.

## Database Connection Fails

Confirm the database container is running and the configured URL matches the active environment:

```bash
docker compose ps database
docker compose logs --tail=100 database
```

For test environments, rebuild the schema:

```bash
make setup-test-db
```

Do not run schema-drop commands against production or shared databases.

## Queue or Async Work Fails

Symfony Messenger uses the configured transport DSN and queue name. Check:

- `DOMAIN_EVENTS_TRANSPORT_DSN`
- `FAILED_DOMAIN_EVENTS_TRANSPORT_DSN`
- `CACHE_REFRESH_TRANSPORT_DSN`
- `FAILED_CACHE_REFRESH_TRANSPORT_DSN`
- `EMAIL_QUEUE_NAME`
- AWS SQS endpoint and credentials for the target environment
- local AWS emulator health when running locally

After configuration changes, restart the PHP container and queue worker process used by the environment.

## Static Analysis Fails

Run the focused command locally before rerunning the full CI job:

```bash
make psalm
make deptrac
make phpinsights
```

Deptrac failures usually mean a new class crosses a forbidden layer or bounded-context boundary. Update the design or the Deptrac configuration only when the new dependency is intentional and documented.

## Tests Fail Locally

Start with the smallest failing target:

```bash
make unit-tests
make behat
make bats
```

If test services are stale, restart them and rebuild the test database:

```bash
make down
make setup-test-db
```

For API-contract failures, compare the generated behavior with [api-endpoints.md](api-endpoints.md), `.github/openapi-spec/spec.yaml`, and `.github/graphql-spec/`.

## Documentation Checks Fail

Run:

```bash
make docs-check
```

The docs check verifies required documentation files, root README navigation, local Markdown links, and trailing whitespace. Fix the reported file and rerun the command before pushing.

## Still Blocked

Open a GitHub issue with:

- the command that failed
- the full error output
- operating system and Docker version
- branch and commit SHA
- whether the failure happens locally, in CI, or both

See [community-and-support.md](community-and-support.md) for support channels.
