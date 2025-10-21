# Claude Code Skills

This directory contains modular Skills that extend Claude Code's capabilities for this project. Skills are automatically discovered by Claude and activated when relevant to the task at hand.

## Available Skills

### 1. CI Workflow (`ci-workflow/`)
**Purpose**: Run comprehensive CI checks before committing changes

**When activated**:
- User asks to "run CI" or "run quality checks"
- Before finishing any task involving code changes
- Before creating pull requests

**What it does**:
- Executes `make ci` with all quality checks
- Guides through fixing failures (code style, static analysis, tests, mutations)
- Ensures "✅ CI checks successfully passed!" message appears
- Protects quality standards (never allows decreasing thresholds)

**Key commands**: `make ci`, `make phpcsfixer`, `make psalm`, `make phpinsights`, `make infection`

---

### 2. Testing Workflow (`testing-workflow/`)
**Purpose**: Run and manage different types of tests

**When activated**:
- Running tests (unit, integration, E2E, mutation, load)
- Debugging test failures
- Checking test coverage

**What it does**:
- Provides comprehensive testing guidance for all test types
- Explains debugging strategies for different failure types
- Covers mutation testing (Infection) strategies
- Documents load testing procedures
- Enforces 100% coverage and 0 escaped mutants

**Key commands**: `make unit-tests`, `make integration-tests`, `make behat`, `make infection`, `make load-tests`

---

### 3. Code Review Workflow (`code-review/`)
**Purpose**: Systematically retrieve and address PR code review comments

**When activated**:
- Handling code review feedback
- Addressing PR comments
- Refactoring based on reviewer suggestions

**What it does**:
- Uses `make pr-comments` to retrieve all unresolved comments
- Categorizes comments (committable suggestions, LLM prompts, questions, feedback)
- Provides systematic approach to implementing suggestions
- Guides comment response strategy
- Ensures all feedback is addressed with quality checks

**Key commands**: `make pr-comments`, `make ci`

---

### 4. Quality Standards (`quality-standards/`)
**Purpose**: Maintain and improve code quality without decreasing standards

**When activated**:
- PHPInsights reports quality issues
- Cyclomatic complexity is too high
- Architecture violations detected
- Code quality needs improvement

**What it does**:
- Protects quality metrics (never allows lowering thresholds)
- Reduces cyclomatic complexity with refactoring strategies
- Fixes architecture violations (Deptrac layer rules)
- Improves code quality score (removes duplication, improves naming)
- Enforces SOLID principles and best practices

**Key commands**: `make phpinsights`, `make phpmd`, `make psalm`, `make deptrac`

**Protected thresholds**:
- PHPInsights quality: 100%
- PHPInsights complexity: 95%
- PHPInsights architecture: 100%
- PHPInsights style: 100%
- Mutation testing MSI: 100%

---

### 5. Documentation Synchronization (`documentation-sync/`)
**Purpose**: Keep `docs/` directory synchronized with code changes

**When activated**:
- Implementing new features or modifying existing ones
- Adding/changing API endpoints (REST or GraphQL)
- Modifying database schema or entities
- Changing architecture or configuration

**What it does**:
- Identifies which documentation files need updates
- Provides templates for documenting different change types
- Ensures consistency across documentation
- Validates examples and cross-references
- Updates API specifications (OpenAPI, GraphQL)

**Key files updated**: `docs/api-endpoints.md`, `docs/design-and-architecture.md`, `docs/developer-guide.md`, `docs/user-guide.md`

**Key commands**: `make generate-openapi-spec`, `make generate-graphql-spec`

---

### 6. Database Migrations (`database-migrations/`)
**Purpose**: Create and manage database migrations using Doctrine ODM for MongoDB

**When activated**:
- Adding new entities
- Modifying entity fields
- Managing database schema changes
- Setting up test database

**What it does**:
- Guides entity creation (Domain layer, XML mapping, API Platform config)
- Documents MongoDB-specific features (custom types, indexes, embedded docs)
- Provides repository implementation patterns
- Explains migration best practices
- Covers testing with database

**Key commands**: `make doctrine-migrations-migrate`, `make doctrine-migrations-generate`, `make setup-test-db`

---

## How Skills Work

### Automatic Discovery
Claude Code automatically discovers and loads Skills from this directory. No manual activation is required.

### Invocation
Skills are **model-invoked** — Claude autonomously decides when to use them based on:
- Task context and user request
- Skill descriptions (the `description` field in YAML frontmatter)
- Relevance to current work

### Skill Structure
Each Skill consists of:
- A directory (e.g., `ci-workflow/`)
- A `SKILL.md` file with YAML frontmatter:
  ```yaml
  ---
  name: Skill Name
  description: What this skill does and when to use it
  ---

  Detailed instructions...
  ```

### Creating New Skills

To add a new Skill:

1. Create a directory: `.claude/skills/your-skill-name/`
2. Create `SKILL.md` with YAML frontmatter
3. Write clear description with usage triggers
4. Provide detailed, actionable instructions
5. Include examples and commands

**Best practices**:
- Keep Skills focused on single capabilities
- Write specific descriptions with concrete trigger terms
- Include relevant commands and examples
- Test Skills with actual use cases

## Skill vs CLAUDE.md vs AGENTS.md

### CLAUDE.md (238 lines)
- **Purpose**: Concise project instructions automatically loaded by Claude
- **Content**: Essential project overview, commands, architecture basics
- **Location**: Root directory
- **Usage**: Automatic context for every conversation

### AGENTS.md (1,327 lines)
- **Purpose**: Comprehensive repository guidelines and best practices
- **Content**: Complete development workflow, quality standards, troubleshooting
- **Location**: Root directory
- **Usage**: Reference documentation for complex scenarios

### Skills (1,933 lines total across 6 Skills)
- **Purpose**: Modular, reusable capabilities for specific workflows
- **Content**: Step-by-step instructions for focused tasks
- **Location**: `.claude/skills/`
- **Usage**: Automatically activated when relevant to task

## Integration with Development Workflow

Skills integrate seamlessly with the project's development workflow:

1. **Before coding**: Documentation Sync Skill identifies what needs updating
2. **During coding**: Quality Standards Skill maintains high code quality
3. **After coding**: Testing Workflow ensures comprehensive coverage
4. **Before commit**: CI Workflow validates all quality checks pass
5. **During review**: Code Review Workflow handles PR feedback systematically
6. **Schema changes**: Database Migrations Skill guides proper entity management

## Success Metrics

All Skills enforce these project standards:
- ✅ CI checks successfully passed
- 100% test coverage maintained
- 100% mutation score (0 escaped mutants)
- All quality thresholds met or exceeded
- Documentation synchronized with code
- Architecture boundaries respected

## Questions or Issues?

If a Skill needs improvement or you encounter issues:
1. Review the Skill's SKILL.md for detailed instructions
2. Check AGENTS.md for comprehensive guidelines
3. Consult CLAUDE.md for quick reference
4. Ask Claude to use a specific Skill if needed
