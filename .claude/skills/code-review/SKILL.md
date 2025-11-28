---
name: code-review
description: Systematically retrieve and address PR code review comments using make pr-comments. Use when handling code review feedback, refactoring based on reviewer suggestions, or addressing PR comments. Includes DDD architecture verification (class naming, directory placement, layer compliance).
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to verify DDD architecture compliance

## Task (Function)

Retrieve PR comments, categorize by type, verify architecture compliance, and implement all changes systematically.

## Execution Steps

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62       # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

**Output**: All unresolved comments with file/line, author, timestamp, URL

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                               |
| ---------------------- | --------------------------- | -------- | ------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply immediately, commit separately |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement changes    |
| Architecture Concern   | Class naming, file location | High     | Verify DDD compliance (see Step 2.1) |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |

#### Step 2.1: Architecture & DDD Verification

For any code changes (suggestions, prompts, or new files), verify:

**Class Naming Compliance** (see `implementing-ddd-architecture` skill):

| Layer          | Class Type        | Naming Pattern                    | Example                           |
| -------------- | ----------------- | --------------------------------- | --------------------------------- |
| **Domain**     | Entity            | `{EntityName}.php`                | `Customer.php`                    |
|                | Value Object      | `{ConceptName}.php`               | `Email.php`, `Money.php`          |
|                | Domain Event      | `{Entity}{PastTenseAction}.php`   | `CustomerCreated.php`             |
|                | Repository Iface  | `{Entity}RepositoryInterface.php` | `CustomerRepositoryInterface.php` |
|                | Exception         | `{SpecificError}Exception.php`    | `InvalidEmailException.php`       |
| **Application** | Command           | `{Action}{Entity}Command.php`     | `CreateCustomerCommand.php`       |
|                | Command Handler   | `{Action}{Entity}Handler.php`     | `CreateCustomerHandler.php`       |
|                | Event Subscriber  | `{Action}On{Event}.php`           | `SendEmailOnCustomerCreated.php`  |
|                | DTO               | `{Entity}{Type}.php`              | `CustomerInput.php`               |
|                | Processor         | `{Action}{Entity}Processor.php`   | `CreateCustomerProcessor.php`     |
|                | Transformer       | `{From}To{To}Transformer.php`     | `CustomerToArrayTransformer.php`  |
| **Infrastructure** | Repository    | `{Technology}{Entity}Repository.php` | `MongoDBCustomerRepository.php` |
|                | Doctrine Type     | `{ConceptName}Type.php`           | `UlidType.php`                    |
|                | Bus Implementation| `{Framework}{Type}Bus.php`        | `SymfonyCommandBus.php`           |

**Directory Location Compliance**:

```
src/{Context}/
├── Application/
│   ├── Command/          ← Commands
│   ├── CommandHandler/   ← Command Handlers
│   ├── EventSubscriber/  ← Event Subscribers
│   ├── DTO/              ← Data Transfer Objects
│   ├── Processor/        ← API Platform Processors
│   ├── Transformer/      ← Data Transformers
│   └── MutationInput/    ← GraphQL Mutation Inputs
├── Domain/
│   ├── Entity/           ← Entities & Aggregates
│   ├── ValueObject/      ← Value Objects
│   ├── Event/            ← Domain Events
│   ├── Repository/       ← Repository Interfaces
│   └── Exception/        ← Domain Exceptions
└── Infrastructure/
    ├── Repository/       ← Repository Implementations
    ├── DoctrineType/     ← Custom Doctrine Types
    └── Bus/              ← Message Bus Implementations
```

**Verification Questions**:

1. ✅ Is the class name following the DDD naming pattern for its type?
2. ✅ Is the class in the correct directory according to its responsibility?
3. ✅ Does the class name reflect what it actually does?
4. ✅ Is the class in the correct layer (Domain/Application/Infrastructure)?
5. ✅ Does Domain layer have NO framework imports (Symfony/Doctrine/API Platform)?

**Action on Violations**:
- Rename class to follow naming conventions
- Move file to correct directory
- Run `make deptrac` to verify no layer violations
- Update all references to renamed/moved classes

### Step 3: Apply Changes Systematically

#### For Committable Suggestions

1. Apply code change exactly as suggested
2. Commit with reference:

   ```bash
   git commit -m "Apply review suggestion: [brief description]

   Ref: [comment URL]"
   ```

#### For LLM Prompts

1. Copy prompt from comment
2. Execute as instructed
3. Verify output meets requirements
4. Commit with reference

#### For Questions

1. Determine if code change or reply needed
2. If code: implement + commit
3. If reply: respond on GitHub

#### For Feedback

1. Evaluate suggestion merit
2. Implement if beneficial
3. Document reasoning if declined

### Step 4: Verify All Addressed

```bash
make pr-comments  # Should show zero unresolved comments
```

### Step 5: Run Quality Checks

```bash
make ci  # Must show "✅ CI checks successfully passed!"
```

## Comment Resolution Workflow

```mermaid
PR Comments → Categorize → Apply by Priority → Verify → Run CI → Done
```

## Constraints (Parameters)

**NEVER**:

- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Commit without running `make ci`
- Leave questions unanswered
- Accept class names that don't follow DDD naming patterns
- Place files in wrong directories (violates layer architecture)
- Allow Domain layer to import framework code (Symfony/Doctrine/API Platform)

**ALWAYS**:

- Apply suggestions exactly as provided
- Commit each suggestion separately with URL reference
- Verify architecture compliance for any new/modified classes
- Check class naming follows DDD patterns (see Step 2.1)
- Verify files are in correct directories according to layer
- Run `make deptrac` to ensure no layer violations
- Run `make ci` after implementing changes
- Mark conversations resolved after addressing

## Format (Output)

**Commit Message Template**:

```
Apply review suggestion: [concise description]

[Optional: explanation if non-obvious]

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

**Final Verification**:

```bash
✅ make pr-comments shows 0 unresolved
✅ make ci shows "CI checks successfully passed!"
```

## Verification Checklist

- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type
- [ ] Architecture & DDD compliance verified for all changes:
  - [ ] Class names follow DDD naming patterns
  - [ ] Files in correct directories according to layer
  - [ ] Class names reflect what they actually do
  - [ ] Domain layer has NO framework imports
  - [ ] `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] `make pr-comments` shows zero unresolved
- [ ] `make ci` passes with success message
- [ ] All conversations marked resolved on GitHub
