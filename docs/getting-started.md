# Getting Started

Welcome to the Core Service, a modern PHP microservice for customer management within the VilnaCRM ecosystem. This guide will help you set up the service, configure it, and quickly get started with its basic functionalities.

## Installation Instructions

### Prerequisites

Before you begin, ensure you have the following installed on your system:

- Docker 25.0.3+
- Docker Compose 2.24.5+
- Git 2.34.1+

If you prefer a workspace-managed environment, use the included devcontainer setup in `.devcontainer/` and skip local prerequisite installation.

### CLI commands

As you will see, we use Make commands to manage the project. Run `make help` after setting up Core Service to see a list of all available commands.

### Steps

1. **Clone the Repository**

   We recommend using Linux to set up this service.

   Then, start by cloning the repository to your local machine. Note, that the recommended way of doing it is using SSH. Check [this link](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/adding-a-new-ssh-key-to-your-github-account) for more information.

   ```bash
   git clone git@github.com:VilnaCRM-Org/core-service.git
   cd core-service
   ```

2. **Configuration**

   Configuration is managed through environment variables. You can copy `.env` to `.env.local` and customize the environment variables for local development.
   Here's an example configuration:

   ```bash
   DB_URL=mongodb://root:secret@database:27017
   APP_ENV=dev
   APP_SECRET=your-secret-key
   ```

3. **Start the project**

   Use the make command to start the project. It will up the container, install dependencies, and set up the database.

   ```bash
   make start
   ```

   **It will be better to wait a few minutes after this command executes, before moving further. You can run `make logs` to check the state of service**

   That's it! Now the service is ready for work.

4. **Quick start guide**

   Once the service runs, you can check these **local** URLs for a list of available endpoints and detailed info about them.

   [REST API docs](https://localhost/api/docs) (available when running locally)

   [GraphQL docs](https://localhost/api/graphql) (available when running locally)

   [Architecture Diagrams](http://localhost:8080/workspace/diagrams) (available when running locally)

   You can also view the API specifications directly on GitHub:
   - [OpenAPI Specification](https://github.com/VilnaCRM-Org/core-service/blob/main/.github/openapi-spec/spec.yaml)

5. **FAQ**

   If something goes wrong, try executing this sequence of commands:

   ```bash
   make cache-clear
   make install
   ```

   For database issues, you can reset the test database:

   ```bash
   make setup-test-db
   ```

Learn more about [Design and Architecture Documentation](design-and-architecture.md).

## Workspace Setup

This repository includes a ready-to-use devcontainer environment in `.devcontainer/devcontainer.json`.
It is designed to run in a local Coder workspace with Docker access.
Primary bootstrap path: `scripts/local-coder/*`. `scripts/codespaces/*` remains only as a compatibility alias.

### What you get

- Docker support so the existing `make` commands continue to work
- GitHub CLI (`gh`)
- Codex CLI (`codex`) when workspace auth is available
- Bats CLI (`bats`) for `make bats`
- Automatic bootstrap on create:
  - secure agent bootstrap (`scripts/local-coder/setup-secure-agent-env.sh`)
  - `make start`
  - `make install` when `vendor/autoload.php` is missing

### How to start

1. Open the repository in your workspace.
2. Wait for the post-create setup to finish.
3. Verify tools:

```bash
gh --version
codex --version
make help
```

For autonomous AI coding in a workspace, set workspace secrets:

- `OPENAI_API_KEY`
- `GH_AUTOMATION_TOKEN`
- bootstrap sets git identity for automated commits to `vilnacrm ai bot <info@vilnacrm.com>`

The bootstrap persists those values into `~/.config/core-service/agent-secrets.env` with `chmod 600` inside the workspace.

Non-secret defaults are persisted in git:

- `.devcontainer/workspace-settings.env`
- `.devcontainer/post-create.sh`
- `scripts/local-coder/setup-secure-agent-env.sh`
- `scripts/codespaces/setup-secure-agent-env.sh` as compatibility alias

If you prefer manual authentication in the workspace:

```bash
gh auth login -h github.com -w
gh auth setup-git
```

Then run:

```bash
bash scripts/local-coder/startup-smoke-tests.sh VilnaCRM-Org
bash scripts/local-coder/verify-gh-codex.sh VilnaCRM-Org
```

`startup-smoke-tests.sh` runs the default startup checks:

- `gh` is authenticated
- org repository listing works
- `bats` CLI is available
- `codex` can execute one non-interactive task

`verify-gh-codex.sh` includes Codex basic and tool-calling smoke checks.

### Working in the workspace

All project operations remain the same as local usage:

```bash
make start
make install
make ci
```

Use the forwarded ports tab in your workspace to access the service endpoints exposed by Docker Compose.
