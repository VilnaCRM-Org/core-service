# Core Service Documentation

This directory is the maintained documentation hub for VilnaCRM Core Service. Start here when setting up the project, changing customer-domain behavior, operating the service, or reviewing API contracts.

## Quick Navigation

| Area            | Document                                                 | Use it for                                                      |
| --------------- | -------------------------------------------------------- | --------------------------------------------------------------- |
| Overview        | [main.md](main.md)                                       | Service purpose, design principles, and high-level capabilities |
| Setup           | [getting-started.md](getting-started.md)                 | Local prerequisites, Docker startup, and first-run checks       |
| Onboarding      | [onboarding.md](onboarding.md)                           | New contributor workflow and learning path                      |
| Architecture    | [design-and-architecture.md](design-and-architecture.md) | DDD, CQRS, hexagonal architecture, and bounded contexts         |
| Development     | [developer-guide.md](developer-guide.md)                 | Code layout, implementation patterns, and Deptrac usage         |
| API             | [api-endpoints.md](api-endpoints.md)                     | REST, GraphQL, OpenAPI, and request examples                    |
| Database        | [database.md](database.md)                               | MongoDB collections, indexes, schema ownership, and migrations  |
| Testing         | [testing.md](testing.md)                                 | Unit, integration, Behat, mutation, load, and API tests         |
| Deployment      | [deployment.md](deployment.md)                           | Environment setup, release steps, and runtime dependencies      |
| Operations      | [operational.md](operational.md)                         | Runtime checks, security posture, and support operations        |
| Troubleshooting | [troubleshooting.md](troubleshooting.md)                 | Common local, CI, database, queue, and API issues               |
| Configuration   | [advanced-configuration.md](advanced-configuration.md)   | Environment variables and load-test configuration               |
| Security        | [security.md](security.md)                               | Secure development and dependency practices                     |
| Performance     | [performance.md](performance.md)                         | Performance benchmarks and optimization notes                   |
| Versioning      | [versioning.md](versioning.md)                           | Version policy and deprecation expectations                     |
| Releases        | [release-notes.md](release-notes.md)                     | Release process and changelog usage                             |
| Community       | [community-and-support.md](community-and-support.md)     | Support and issue reporting channels                            |
| Glossary        | [glossary.md](glossary.md)                               | Shared domain vocabulary and naming conventions                 |
| Usage           | [user-guide.md](user-guide.md)                           | API usage walkthroughs                                          |
| Licensing       | [legal-and-licensing.md](legal-and-licensing.md)         | License and third-party package notes                           |

## Generated Documentation

Run the documentation workflow from the repository root:

```bash
make docs-check
make docs
```

`make docs-check` validates the required documentation files, root README entry point, local Markdown links, and trailing whitespace. CI runs this target for pull requests.

`make docs` runs `docs-check` and then generates the source API reference with PHPDocumentor into `build/docs/phpdoc`. The generated output is intentionally not committed.

## Live API Documentation

When the local stack is running, API Platform exposes interactive documentation at:

- REST/OpenAPI: `https://localhost/api/docs`
- GraphQL: `https://localhost/api/graphql`

Static API contract files used by CI live under `.github/openapi-spec/` and `.github/graphql-spec/`.

## Updating Documentation

Update documentation in the same pull request as the related code change when behavior, commands, configuration, API contracts, data ownership, or deployment expectations change.

Before opening a PR, run:

```bash
make docs-check
```
