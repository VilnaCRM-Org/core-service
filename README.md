[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://supportukrainenow.org/)

# Core Service

[![CodeScene Code Health](https://img.shields.io/badge/CodeScene%20%7C%20Hotspot%20Code%20Health-9.7-brightgreen)](https://codescene.io/projects/39797)
[![CodeScene System Mastery](https://img.shields.io/badge/CodeScene%20%7C%20Average%20Code%20Health-9.8-brightgreen)](https://codescene.io/projects/39797)
[![codecov](https://codecov.io/gh/VilnaCRM-Org/core-service/branch/main/graph/badge.svg?token=FgXtmFulVd)](https://app.codecov.io/gh/VilnaCRM-Org/core-service)
![PHPInsights code](https://img.shields.io/badge/PHPInsights%20%7C%20Code%20-100.0%25-success.svg)
![PHPInsights style](https://img.shields.io/badge/PHPInsights%20%7C%20Style%20-100.0%25-success.svg)
![PHPInsights complexity](https://img.shields.io/badge/PHPInsights%20%7C%20Complexity%20-100.0%25-success.svg)
![PHPInsights architecture](https://img.shields.io/badge/PHPInsights%20%7C%20Architecture%20-100.0%25-success.svg)

## Possibilities

- Modern PHP stack for services: [API Platform 3](https://api-platform.com/), PHP 8, [Symfony 7](https://symfony.com/)

- [Hexagonal Architecture, DDD & CQRS in PHP](https://github.com/CodelyTV/php-ddd-example)

- Built-in docker environment and convenient `make` cli command

- A lot of CI checks to ensure the highest code quality that can be ([Psalm](https://psalm.dev/), [PHPInsights](https://phpinsights.com/), Security checks, Code style fixer)

- Configured testing tools: [PHPUnit](https://phpunit.de/), [Behat](https://docs.behat.org/)

- Much more!

## Why you might need it

The Core Service is the backbone of the VilnaCRM ecosystem, providing essential functionalities that power all components of the CRM system.
With a robust API, the Core Service ensures seamless integration and scalability across the VilnaCRM platform,
enabling efficient operations and consistent user experiences across all services.

## License

This software is distributed under the [Creative Commons Zero v1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/deed) license. Please read [LICENSE](https://github.com/VilnaCRM-Org/core-service/blob/main/LICENSE) for information on the software availability and distribution.

### Minimal installation

Install the latest [docker](https://docs.docker.com/engine/install/) and [docker compose](https://docs.docker.com/compose/install/)

Use `make` command to set up project and automatically install all needed dependencies

> make start

Go to browser and open the link below to access REST API docs

> https://localhost/api/docs

You can access the GraphQL endpoint via the link below:

> https://localhost/api/graphql

Also, you can see the architecture diagram using the link below

> http://localhost:8080/workspace/diagrams

That's it. You should now be ready to use core service!

### GitHub Codespaces

This repository ships with a built-in Codespaces definition in `.devcontainer/devcontainer.json`.

When a Codespace is created, the setup script:

- installs `codex` CLI
- installs `claude` CLI (Claude Code)
- provides `gh` CLI
- installs `bats` CLI for `make bats`
- starts the Docker stack with `make start`
- installs PHP dependencies with `make install` if needed

After startup, verify the environment:

```bash
gh --version
codex --version
claude --version
make help
```

#### Secure setup for autonomous AI coding agents

Use Codespaces secrets (do not commit credentials). Prefer repository-level Codespaces secrets for this repository:

- `OPENROUTER_API_KEY`: OpenRouter API key for Codex and Claude Code
- `GH_AUTOMATION_TOKEN`: GitHub token for non-interactive `gh` usage
- optional `GIT_AUTHOR_NAME`, `GIT_AUTHOR_EMAIL`: identity for automated commits
  - if omitted, bootstrap defaults to `codex-bot <codex-bot@users.noreply.github.com>`

The Codespace `post-create` step runs secure bootstrap automatically and then executes startup smoke tests. You can also run scripts manually:

```bash
bash scripts/codespaces/setup-secure-agent-env.sh
bash scripts/codespaces/startup-smoke-tests.sh VilnaCRM-Org
bash scripts/codespaces/verify-gh-codex.sh VilnaCRM-Org
```

What `startup-smoke-tests.sh` checks:

- `gh` authentication is available
- repository listing for `VilnaCRM-Org` works
- `bats` CLI is available
- `codex` can execute one non-interactive task with the `openrouter` profile
- `claude` can execute one non-interactive task via OpenRouter

Repository-tracked defaults for GitHub, Codex, and Claude bootstrap are stored in:

- `.devcontainer/codespaces-settings.env`
- `.devcontainer/post-create.sh`
- `scripts/codespaces/setup-secure-agent-env.sh`

What `verify-gh-codex.sh` checks:

- GitHub auth works
- repository listing for `VilnaCRM-Org` works
- current PR checks can be queried via `gh`
- current branch supports `git push --dry-run`
- `codex` can run basic and tool-calling non-interactive smoke tasks via OpenRouter
- `codex` can complete a tool-calling smoke task required for autonomous coding flows
- `claude` can run a non-interactive smoke task via OpenRouter
- Claude default model is set to `anthropic/claude-sonnet-4.5`

Codex is configured directly (no `make` wrapper) with a single OpenRouter profile:

```toml
profile = "openrouter"

[profiles.openrouter]
model = "openai/gpt-5.2-codex"
model_provider = "openrouter"
model_reasoning_effort = "high"
model_reasoning_summary = "none"
approval_policy = "never"
sandbox_mode = "danger-full-access"

[model_providers.openrouter]
name = "OpenRouter"
base_url = "https://openrouter.ai/api/v1"
env_key = "OPENROUTER_API_KEY"
wire_api = "responses"
```

`approval_policy = "never"` + `sandbox_mode = "danger-full-access"` and `--dangerously-bypass-approvals-and-sandbox` allow Codex to run shell commands without approval.
Use this only in trusted ephemeral Codespaces with least-privilege tokens, protected branches, and strict review/CI gates.

Run Codex directly:

```bash
codex -p openrouter
codex exec -p openrouter --dangerously-bypass-approvals-and-sandbox "Refactor customer update flow to reduce duplication"
```

Claude Code is configured to use OpenRouter by default:

- `ANTHROPIC_AUTH_TOKEN=$OPENROUTER_API_KEY`
- `ANTHROPIC_BASE_URL=https://openrouter.ai/api`
- `ANTHROPIC_MODEL=anthropic/claude-sonnet-4.5`
- `~/.claude/settings.json` contains:

```json
{
  "model": "anthropic/claude-sonnet-4.5"
}
```

Notes:

- secrets are never stored in git; keep them in Codespaces secrets
- Codespaces secrets are mapped into runtime shell environment via `.devcontainer/devcontainer.json` `remoteEnv`
- bootstrap persists required credentials into `~/.config/core-service/agent-secrets.env` with `chmod 600` for future shell sessions in the same Codespace
- no token values are written to repository files
- if you do not provide `GH_AUTOMATION_TOKEN`, run interactive login:
  `gh auth login -h github.com -w && gh auth setup-git`
- this setup is OpenRouter-only

## Using

You can use `make` command to easily control and work with project locally.

Execute `make` or `make help` to see the full list of project commands.

The list of the `make` possibilities:

```
aws-load-tests               Execute load tests on AWS
aws-load-tests-cleanup       Clean up AWS resources
bats                         Bats is a TAP-compliant testing framework for Bash
behat                        A php framework for autotesting business expectations
build                        Builds the images (PHP, caddy)
cache-clear                  Clears and warms up the application cache for a given environment and debug mode
cache-warmup                 Warmup the Symfony cache
changelog-generate           Generate changelog from a project's commit messages
check-requirements           Checks requirements for running Symfony and gives useful recommendations to optimize PHP for Symfony.
check-security               Checks security issues in project dependencies. Without arguments, it looks for a "composer.lock" file in the current directory. Pass it explicitly to check a specific "composer.lock" file.
commands                     List all Symfony commands
composer-validate            The validate command validates a given composer.json and composer.lock
coverage-xml                 Create the code coverage report in XML format with PHPUnit
down                         Stop the docker hub
install                      Install vendors according to the current composer.lock file
update                       update vendors according to the current composer.json file
logs                         Show all logs
new-logs                     Show live logs
phpcsfixer                   A tool to automatically fix PHP Coding Standards issues
phpinsights                  Instant PHP quality checks and static analysis tool
phpunit                      The PHP unit testing framework
psalm                        A static analysis tool for finding errors in PHP applications
psalm-security               Psalm security analysis
purge                        Purge cache and logs
sh                           Log to the docker container
start                        Start docker
stop                         Stop docker and the Symfony binary server
up                           Start the docker hub (PHP, caddy)
```

## Documentation

Start reading at the [GitHub wiki](https://github.com/VilnaCRM-Org/core-service/wiki). If you're having trouble, head for [the troubleshooting guide](https://github.com/VilnaCRM-Org/core-service/wiki/Troubleshooting) as it's frequently updated.

You can generate complete API-level documentation by running `phpdoc` in the top-level folder, and documentation will appear in the `docs` folder, though you'll need to have [PHPDocumentor](http://www.phpdoc.org) installed.

If the documentation doesn't cover what you need, search the [many questions on Stack Overflow](http://stackoverflow.com/questions/tagged/vilnacrm), and before you ask a question, [read the troubleshooting guide](https://github.com/VilnaCRM-Org/core-service/wiki/Troubleshooting).

## Tests

[Tests](https://github.com/VilnaCRM-Org/core-service/tree/main/tests/) use PHPUnit 9 and [Behat](https://github.com/Behat/Behat).

[Test status](https://github.com/VilnaCRM-Org/core-service/actions)

If this isn't passing, is there something you can do to help?

## Running Load Tests in AWS

This template supports running load tests on AWS using k6, a modern load testing tool, to evaluate the performance of your application under various conditions. You can automate this process using a custom bash script that provisions an EC2 instance, attaches an IAM role, creates an S3 bucket for storing the results, and executes the k6 load tests.

### Steps for Running AWS Load Tests

#### 1. **Configure AWS CLI**:

Before you can interact with AWS, you'll need to [configure the AWS CLI](https://docs.aws.amazon.com/cli/v1/userguide/cli-chap-configure.html) with your credentials.
Run the following command and provide your AWS Access Key and Secret Access Key. Ensure that your AWS credentials and region are properly set to avoid any permission or region-based issues.

#### 2. **Run Load Tests**:

The `make aws-load-tests` runs the script that provisions an EC2 instance, attaches an IAM role, creates an S3 bucket for storing the results, and executes the load tests.

#### 3. **Configure CLI Options**:

To configure the AWS load testing, pass options through the CLI command to define the AWS environment settings, as needed for your project:

- `-r REGION`: Specifies the AWS region where the EC2 instance will be launched (e.g., `us-east-1`)
- `-a AMI_ID`: Defines the Amazon Machine Image (AMI) ID to use for the EC2 instance (e.g., `ami-0e86e20dae9224db8`)
- `-t INSTANCE_TYPE`: Sets the EC2 instance type (e.g., `t2.micro`)
- `-i INSTANCE_TAG`: Provides a tag to identify the EC2 instance (e.g., `LoadTestInstance`)
- `-o ROLE_NAME`: Specifies the IAM role name for the EC2 instance with write access to S3 (e.g., `EC2S3WriteAccessRole`)
- `-b BRANCH_NAME`: Sets the branch name for the project (e.g., `main`)
- `-s SECURITY_GROUP_NAME`: Defines the name of the security group to be used for the EC2 instance (e.g., `LoadTestSecurityGroup`)

#### 4. **Executing Load Tests**:

Once the EC2 instance is up, the predefined load tests are executed, simulating real-world conditions and workloads on your application.

#### 5. **Saving Results to S3**:

The results of the load tests are automatically uploaded to an S3 bucket for review and analysis.

#### 6. **Scaling and Flexibility**:

This approach allows you to scale the infrastructure to suit different performance testing needs, providing insights into how your service performs in a cloud-based, production-like environment.

### Cleanup AWS Infrastructure

After the load tests have been completed, it's important to clean up the AWS resources.
The `make aws-load-tests-cleanup` command automates the process of tearing down the EC2 instance, security groups, and other related AWS resources.

**Note:** This project utilizes AWS free tier services (EC2 micro instances, free security groups, free images, and volumes up to 30 GB), which minimizes cost concerns during AWS operations. However, it's still important to clean up resources to avoid any potential charges beyond the free tier limits.

## Repository Synchronization

This template is automatically synchronized with other repositories in our ecosystem. Whenever changes are made to the template, those changes are propagated to dependent projects, ensuring they stay up to date with the latest improvements and best practices.

We use this synchronization feature, for example, in the [user-service](https://github.com/VilnaCRM-Org/user-service) repository.

The synchronization is powered by the [actions-template-sync](https://github.com/AndreasAugustin/actions-template-sync) GitHub Action, which automates the process of propagating updates from this template to other projects.

### Handling Workflow Permissions Error

When setting up the repository synchronization, you may encounter permission-related issues. Below are two methods to resolve common workflow permissions errors: using a Personal Access Token (PAT) or using a GitHub App.

#### Option 1: Using a Personal Access Token (PAT)

Details on how to configure and use a PAT for repository synchronization can be found in the [TEMPLATE_SYNC_PAT.md](.github/TEMPLATE_SYNC_PAT.md) file inside the `.github` directory.

#### Option 2: Using a GitHub App

For projects that prefer GitHub App authentication, please refer to the [TEMPLATE_SYNC_APP.md](.github/TEMPLATE_SYNC_APP.md) file in the `.github` directory for setup instructions and examples.

## Security

Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

See [SECURITY](https://github.com/VilnaCRM-Org/core-service/tree/main/SECURITY.md) and [Security advisories on GitHub](https://github.com/VilnaCRM-Org/core-service/security).

## Contributing

Please submit bug reports, suggestions, and pull requests to the [GitHub issue tracker](https://github.com/VilnaCRM-Org/core-service/issues).

We're particularly interested in fixing edge cases, expanding test coverage, and updating translations.

If you found a mistake in the docs, or want to add something, go ahead and amend the wiki – anyone can edit it.

## Sponsorship

Development time and resources for this repository are provided by [VilnaCRM](https://vilnacrm.com/), the free and opensource CRM system.

Donations are very welcome, whether in beer 🍺, T-shirts 👕, or cold, hard cash 💰. Sponsorship through GitHub is a simple and convenient way to say "thank you" to maintainers and contributors – just click the "Sponsor" button [on the project page](https://github.com/VilnaCRM-Org/core-service). If your company uses this template, consider taking part in the VilnaCRM's enterprise support program.

## Changelog

See [changelog](CHANGELOG.md).
