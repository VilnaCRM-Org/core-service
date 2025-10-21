---
name: Documentation Synchronization
description: Keep documentation in sync with code changes. Use when implementing features, modifying APIs, changing architecture, or adding configuration options to ensure docs/ directory stays updated.
---

# Documentation Synchronization Skill

This skill ensures documentation in the `docs/` directory remains synchronized with codebase changes.

## When to Use This Skill

Activate this skill when:

- Implementing new features or modifying existing ones
- Adding or changing API endpoints (REST or GraphQL)
- Modifying database schema or entities
- Changing architecture or design patterns
- Adding configuration options or environment variables
- Implementing security or authentication changes
- Adding new testing strategies
- Making performance optimizations

## Core Documentation Files

### API and Integration

- `docs/api-endpoints.md` - API endpoints, schemas, examples
- `docs/user-guide.md` - User-facing feature usage
- `docs/security.md` - Authentication, authorization, security practices

### Architecture and Design

- `docs/design-and-architecture.md` - System design, components, patterns
- `docs/developer-guide.md` - Development patterns, code examples
- `docs/glossary.md` - Domain terms and definitions

### Operations and Configuration

- `docs/advanced-configuration.md` - Environment variables, configuration
- `docs/getting-started.md` - Setup and initial configuration
- `docs/operational.md` - Monitoring, logging, maintenance

### Development

- `docs/testing.md` - Testing strategies, coverage, commands
- `docs/performance.md` - Performance benchmarks, optimizations
- `docs/onboarding.md` - New developer onboarding

### Versioning and Changes

- `docs/versioning.md` - Version information
- `docs/release-notes.md` - Changelog and significant changes

## Update Scenarios

### When Adding New REST API Endpoints

**Update** `docs/api-endpoints.md`:

- Endpoint URL and HTTP method
- Request/response schemas with examples
- Authentication/authorization requirements
- Error codes and responses
- Rate limiting information

**Update** `docs/user-guide.md`:

- Usage examples for the new endpoint
- Integration patterns

**Update** `.github/openapi-spec/`:

```bash
make generate-openapi-spec
```

**Example**:

```markdown
### POST /api/users/confirm

Confirms a user's email address using a confirmation token.

**Request Body**:
\`\`\`json
{
"token": "abc123def456"
}
\`\`\`

**Response**: 204 No Content

**Errors**:

- 400 Bad Request: Invalid token format
- 404 Not Found: Token not found or expired
- 401 Unauthorized: Missing authentication

**Authentication**: Required (OAuth Bearer token)
```

### When Adding New GraphQL Operations

**Update** `docs/api-endpoints.md`:

- Query/mutation schemas
- Input/output types
- Example requests and responses
- Authentication requirements

**Update** `.github/graphql-spec/`:

```bash
make generate-graphql-spec
```

**Update** `docs/user-guide.md`:

- Client integration examples

**Example**:

```markdown
### Mutation: confirmUser

\`\`\`graphql
mutation ConfirmUser($input: ConfirmUserInput!) {
confirmUser(input: $input) {
user {
id
email
confirmed
}
}
}
\`\`\`

**Input**:
\`\`\`json
{
"input": {
"token": "abc123def456"
}
}
\`\`\`
```

### When Modifying Database Schema

**Update** `docs/design-and-architecture.md`:

- Updated entity relationships
- New database tables or fields
- Migration considerations

**Update** `docs/developer-guide.md`:

- New entity usage patterns
- Repository method examples

**Example**:

```markdown
#### User Entity

The User entity represents registered users in the system.

**Fields**:

- `id`: UUID (primary key)
- `email`: Unique email address
- `password`: Bcrypt hashed password
- `confirmed`: Boolean flag for email confirmation
- `confirmationToken`: Token for email confirmation (nullable)
- `createdAt`: Registration timestamp

**Relationships**:

- One-to-many with ConfirmationToken
```

### When Adding Configuration Options

**Update** `docs/advanced-configuration.md`:

- New environment variables
- Configuration examples
- Default values and validation rules
- Docker compose updates if needed

**Update** `docs/getting-started.md` (if required for basic setup)

**Example**:

```markdown
### EMAIL_CONFIRMATION_TOKEN_LENGTH

**Type**: Integer
**Default**: 10
**Required**: No

Length of the random hex token generated for email confirmation.

\`\`\`bash
EMAIL_CONFIRMATION_TOKEN_LENGTH=16
\`\`\`

**Validation**: Must be between 8 and 32 characters.
```

### When Implementing Domain Features

**Update** `docs/design-and-architecture.md`:

- New domain models and aggregates
- Command/query handlers
- Domain events and their handlers
- Bounded context interactions

**Update** `docs/glossary.md`:

- New domain terms

**Update** `docs/developer-guide.md`:

- Usage examples

**Example**:

```markdown
## User Confirmation Bounded Context

### Aggregates

**ConfirmationEmail**: Manages the email confirmation workflow

- **Commands**:

  - `SendConfirmationEmailCommand`: Triggers email sending

- **Events**:

  - `ConfirmationEmailSentEvent`: Email successfully sent

- **Handlers**:
  - `SendConfirmationEmailHandler`: Processes email sending

### Workflow

1. User registers → `UserRegisteredEvent` emitted
2. Event subscriber triggers `SendConfirmationEmailCommand`
3. Handler generates token and sends email
4. User clicks link → `ConfirmUserCommand` processed
5. `UserConfirmedEvent` emitted
```

### When Modifying Authentication/Authorization

**Update** `docs/security.md`:

- New OAuth flows or grant types
- Permission changes
- Security considerations

**Update** `docs/api-endpoints.md`:

- Updated auth requirements per endpoint

**Update** `docs/user-guide.md`:

- Client authentication examples

**Example**:

```markdown
### OAuth Password Grant

Used for trusted first-party clients to authenticate users with username/password.

**Endpoint**: `POST /api/oauth/token`

**Request**:
\`\`\`json
{
"grant_type": "password",
"client_id": "your_client_id",
"client_secret": "your_client_secret",
"username": "user@example.com",
"password": "userpassword"
}
\`\`\`

**Security Considerations**:

- Only use over HTTPS
- Store client secrets securely
- Implement rate limiting
```

### When Adding Testing Strategies

**Update** `docs/testing.md`:

- New test categories or patterns
- Updated coverage requirements
- New testing commands or procedures

**Update** `docs/developer-guide.md` (if workflow changes)

**Example**:

```markdown
### Contract Testing

Added contract tests to verify API compatibility with client expectations.

**Command**:
\`\`\`bash
make contract-tests
\`\`\`

**Location**: `tests/Contract/`

**Purpose**: Ensure API responses match documented contracts using Schemathesis
```

### When Implementing Performance Optimizations

**Update** `docs/performance.md`:

- Performance benchmarks and improvements
- New caching strategies
- Resource usage optimizations

**Update** `docs/php-fpm-vs-frankenphp.md` (if runtime comparisons change)

**Example**:

```markdown
### Redis Caching for Rate Limiting

Migrated rate limiting from database to Redis cache.

**Performance Impact**:

- 60% reduction in database queries
- 40% improvement in rate limit check latency
- P99 latency: 5ms → 2ms

**Configuration**:
\`\`\`bash
REDIS_URL=redis://redis:6379/0
\`\`\`
```

## Documentation Quality Standards

### Consistency Requirements

- Follow existing documentation structure and formatting
- Use consistent terminology from `docs/glossary.md`
- Include practical code examples with syntax highlighting
- Add cross-references to related documentation sections

### Completeness Requirements

- Document all public APIs, endpoints, and user-facing features
- Include error handling and edge cases
- Provide both basic and advanced usage examples
- Update version information in `docs/versioning.md` when applicable

### Maintenance Requirements

- Remove outdated information when features are deprecated
- Update `docs/release-notes.md` with significant changes
- Ensure all links and references remain valid
- Update screenshots or diagrams if UI/architecture changes

## Documentation Update Workflow

### Before Committing Code Changes

1. **Review Documentation Impact**:

   - Identify which documentation files need updates
   - List all affected docs files

2. **Update Relevant Files**:

   - Make comprehensive updates to all affected documentation
   - Ensure consistency across all docs

3. **Cross-Reference Check**:

   - Verify all internal links remain valid
   - Check references between documentation files

4. **Example Validation**:

   - Test all code examples
   - Ensure they work with current implementation

5. **Consistency Check**:
   - Verify terminology alignment with `docs/glossary.md`
   - Follow existing formatting patterns

### Documentation Update Checklist

Before committing:

- [ ] API documentation updated for endpoint/schema changes
- [ ] Architecture documentation reflects structural changes
- [ ] Configuration documentation includes new options
- [ ] Testing documentation covers new test scenarios
- [ ] User guide includes new feature usage examples
- [ ] Security documentation addresses new auth/security aspects
- [ ] Performance documentation reflects optimization changes
- [ ] Glossary updated with new domain terms
- [ ] Release notes updated for significant changes
- [ ] All code examples tested and validated

## Integration with CI/CD

### During Pull Requests

- Documentation updates should be in the same PR as code changes
- Reviewers verify documentation accuracy
- CI validates documentation links and formatting

### Version Synchronization

- Keep documentation version aligned with application version
- Update `docs/versioning.md` for releases
- Maintain backward compatibility notes

## Success Criteria

- All affected documentation files updated
- Code examples tested and working
- Internal links and references valid
- Terminology consistent with glossary
- Release notes updated for significant changes
- Documentation reflects actual code behavior
