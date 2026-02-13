[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://supportukrainenow.org/)

# Core Service

[![CodeScene Code Health](https://img.shields.io/badge/CodeScene%20%7C%20Hotspot%20Code%20Health-9.7-brightgreen)](https://codescene.io/projects/39797)
[![CodeScene System Mastery](https://img.shields.io/badge/CodeScene%20%7C%20Average%20Code%20Health-9.8-brightgreen)](https://codescene.io/projects/39797)
[![codecov](https://codecov.io/gh/VilnaCRM-Org/core-service/branch/main/graph/badge.svg?token=FgXtmFulVd)](https://app.codecov.io/gh/VilnaCRM-Org/core-service)
![PHPInsights code](https://img.shields.io/badge/PHPInsights%20%7C%20Code%20-100.0%25-success.svg)
![PHPInsights style](https://img.shields.io/badge/PHPInsights%20%7C%20Style%20-100.0%25-success.svg)
![PHPInsights complexity](https://img.shields.io/badge/PHPInsights%20%7C%20Complexity%20-100.0%25-success.svg)
![PHPInsights architecture](https://img.shields.io/badge/PHPInsights%20%7C%20Architecture%20-100.0%25-success.svg)

## Overview

Core Service is the backbone of the VilnaCRM ecosystem, providing customer-management APIs and shared domain logic for other services.
It follows Hexagonal Architecture, DDD, and CQRS patterns, with a modern Symfony + API Platform stack.

## Stack at a glance

- PHP 8.3 (see docs/performance.md)
- Symfony 7.2
- API Platform (REST + GraphQL)
- MongoDB as the primary datastore
- Docker + Make for local development

## Quick start (local)

Prerequisites

- Docker 25.0.3+
- Docker Compose 2.24.5+
- Git 2.34.1+

Setup

```bash
git clone git@github.com:VilnaCRM-Org/core-service.git
cd core-service
make start
```

**Tip:** Wait a few minutes for the stack to be ready; use `make logs` to check status.

Local endpoints

- REST API docs: https://localhost/api/docs
- GraphQL docs: https://localhost/api/graphql
- Architecture diagrams: http://localhost:8080/workspace/diagrams *(dev only, requires Structurizr Lite from docker-compose.override.yml)*

## Codespaces

This repository includes a ready-to-use Codespaces environment in .devcontainer/devcontainer.json.
On first boot, the post-create script installs required CLIs and runs startup automation.

What you get

- Docker support (all make commands work)
- GitHub CLI (gh)
- Codex CLI (codex)
- Claude Code CLI (claude)
- Bats (bats) for make bats
- Automatic bootstrap:
  - scripts/codespaces/setup-secure-agent-env.sh
  - make start
  - make install (when vendor/autoload.php is missing)

Secrets required for autonomous agents (Codespaces repo secrets)

- OPENROUTER_API_KEY
- GH_AUTOMATION_TOKEN

The bootstrap maps secrets via .devcontainer/devcontainer.json (remoteEnv) and persists them to
~/.config/core-service/agent-secrets.env (chmod 600) for later shells.

If you prefer manual auth in Codespaces:

```bash
gh auth login -h github.com -w
gh auth setup-git
```

Run the smoke tests:

```bash
bash scripts/codespaces/startup-smoke-tests.sh VilnaCRM-Org
bash scripts/codespaces/verify-gh-codex.sh VilnaCRM-Org
```

## Make targets (selected)

- make start / make stop / make logs
- make install
- make phpcsfixer
- make psalm
- make phpinsights
- make phpunit
- make behat

Run make help for the full list.

## Documentation

Start here:

- docs/main.md
- docs/getting-started.md
- docs/onboarding.md
- docs/testing.md
- docs/security.md

The Wiki is available at https://github.com/VilnaCRM-Org/core-service/wiki

## Contributing & security

- Issues and PRs: https://github.com/VilnaCRM-Org/core-service/issues
- Security: see SECURITY.md
- Contribution guide: CONTRIBUTING.md

## License

This software is distributed under the Creative Commons Zero v1.0 Universal license. Please read LICENSE.
