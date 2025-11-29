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

**Structure**: Multi-file with supporting guides:

- `entity-creation-guide.md` - Complete entity workflow
- `entity-modification-guide.md` - Modifying entities safely
- `repository-patterns.md` - Repository implementation
- `mongodb-specifics.md` - MongoDB features
- `reference/troubleshooting.md` - Database issues

---

### 7. Code Organization (`code-organization/`)

**Purpose**: Ensure proper code organization following "Directory X contains ONLY class type X"

**When activated**:

- Creating new classes
- Refactoring existing code
- Code review feedback about organization
- Moving classes between directories
- Renaming classes or methods

**What it does**:

- Enforces "Directory X contains ONLY class type X" principle
- Validates class names match functionality
- Ensures namespaces match directory structure
- Checks variable and parameter naming consistency
- Verifies comment accuracy
- Provides refactoring workflow for reorganization

**Key principle**: Each directory contains ONLY the type of class it's named for (Converter/, Transformer/, Validator/, Builder/, Fixer/, Cleaner/, etc.)

**Examples**: UlidTypeConverter in Converter/ (not Transformer/), UlidValidator in Validator/ (not Transformer/)

---

### 8. Load Testing (`load-testing/`)

**Purpose**: Create and manage K6 load tests for REST and GraphQL APIs

**When activated**:

- Creating load tests
- Writing K6 scripts
- Testing API performance
- Debugging load test failures
- Setting up performance monitoring

**What it does**:

- Provides patterns for REST and GraphQL load tests
- Documents K6 script structure and configuration
- Explains deterministic testing (no random operations)
- Covers IRI handling and data generation
- Troubleshoots common load testing issues

**Key commands**: `make load-tests`, `make smoke-load-tests`, `make average-load-tests`, `make stress-load-tests`, `make spike-load-tests`

**Structure**: Multi-file with comprehensive guides:

- `rest-api-patterns.md` - REST API templates
- `graphql-patterns.md` - GraphQL patterns
- `examples/rest-customer-crud.js` - Complete REST example
- `examples/graphql-customer-crud.js` - Complete GraphQL example
- `reference/configuration.md` - K6 configuration
- `reference/utils-extensions.md` - Extending Utils class
- `reference/troubleshooting.md` - Common issues and solutions

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
  name: skill-name
  description: What this skill does and when to use it
  ---
  Detailed instructions...
  ```

**Multi-file Structure** (for complex skills):

Large skills use a multi-file approach following Claude Code best practices:

- **Main `SKILL.md`**: Core workflow and quick reference (<300 lines)
- **Supporting files**: Detailed patterns, guides, and reference documentation
- **`examples/`**: Complete working code examples
- **`reference/`**: Troubleshooting, configuration, and advanced topics
- **`update-scenarios/`**: Scenario-specific patterns (e.g., for documentation-sync)

**Example** - The `load-testing` skill structure:

```
load-testing/
├── SKILL.md (210 lines)
├── rest-api-patterns.md
├── graphql-patterns.md
├── examples/
│   ├── rest-customer-crud.js
│   └── graphql-customer-crud.js
└── reference/
    ├── configuration.md
    ├── utils-extensions.md
    └── troubleshooting.md
```

### Creating New Skills

To add a new Skill:

1. Create a directory: `.claude/skills/your-skill-name/`
2. Create `SKILL.md` with YAML frontmatter
3. Write clear description with usage triggers
4. Keep main file concise (<300 lines for complex skills)
5. Extract details into supporting files if needed
6. Include examples and commands

**Best practices**:

- Keep Skills focused on single capabilities
- Use lowercase-hyphen naming (e.g., `my-skill-name`)
- Write specific descriptions with concrete trigger terms
- Main SKILL.md should be quick reference, details in supporting files
- Include practical examples and actual commands
- Test Skills with real use cases

## Skill vs CLAUDE.md vs AGENTS.md

### CLAUDE.md (Concise reference)

- **Purpose**: Concise project instructions automatically loaded by Claude
- **Content**: Essential project overview, commands, architecture basics
- **Location**: Root directory
- **Usage**: Automatic context for every conversation

### AGENTS.md (Comprehensive guidelines)

- **Purpose**: Comprehensive repository guidelines and best practices
- **Content**: Complete development workflow, quality standards, troubleshooting
- **Location**: Root directory
- **Usage**: Reference documentation for complex scenarios

### Skills (Modular skill set)

- **Purpose**: Modular, reusable capabilities for specific workflows
- **Content**: Step-by-step instructions for focused tasks with supporting documentation
- **Location**: `.claude/skills/`
- **Usage**: Automatically activated when relevant to task
- **Structure**: Each Skill provides a focused quick-reference `SKILL.md` with additional supporting files for deeper guidance when needed
- **Scope**: Combined Skills coverage spans detailed examples, troubleshooting guides, and workflow playbooks

## Integration with Development Workflow

Skills integrate seamlessly with the project's development workflow:

1. **Before coding**: Documentation Sync Skill identifies what needs updating
2. **During coding**:
   - Quality Standards Skill maintains high code quality
   - Database Migrations Skill guides entity and schema changes
3. **After coding**:
   - Testing Workflow ensures comprehensive coverage
   - Load Testing Skill validates API performance
4. **Before commit**:
   - CI Workflow validates all quality checks pass
   - Documentation Sync confirms docs are updated
5. **During review**: Code Review Workflow handles PR feedback systematically

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
