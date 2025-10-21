---
name: Code Review Workflow
description: Systematically retrieve and address PR code review comments using make pr-comments. Use when handling code review feedback, refactoring based on reviewer suggestions, or addressing PR comments.
---

# Code Review Workflow Skill

This skill provides a systematic approach to retrieving and addressing all code review comments on Pull Requests.

## When to Use This Skill

Activate this skill when:
- User asks to address code review comments
- Working on PR feedback
- Refactoring based on reviewer suggestions
- Need to retrieve unresolved PR comments
- Preparing to respond to reviewers

## Step 1: Retrieve All PR Comments

### Auto-detect PR from current branch
```bash
make pr-comments
```

### Specify PR number explicitly
```bash
make pr-comments PR=215
```

### Different output formats
```bash
make pr-comments FORMAT=json      # JSON format
make pr-comments FORMAT=markdown  # Markdown format
```

### What You Get

The command outputs all **unresolved** comments with:
- File path and line number
- Author and timestamp
- Full comment content
- Direct GitHub URL for context

## Step 2: Categorize Comments by Type

Analyze each comment and categorize:

### A. Committable Suggestions (Highest Priority)

**Characteristics**:
- Contains code suggestions
- Usually prefixed with "suggestion"
- Has code blocks to apply

**Action**:
1. Apply the suggested change exactly as provided
2. Commit immediately with descriptive message
3. Reference comment URL in commit message

**Example**:
```bash
# Apply suggestion directly
# Commit the change
git add .
git commit -m "Apply code review suggestion: improve variable naming

Ref: https://github.com/user/repo/pull/215#discussion_r123456"
```

### B. LLM Prompts and Instructions (High Priority)

**Characteristics**:
- Provides specific refactoring instructions
- Includes architectural guidance
- Describes implementation approach

**Action**:
1. Use comment as detailed prompt for code generation
2. Analyze current implementation
3. Design and implement changes following instructions
4. Update tests accordingly
5. Verify with `make ci`

### C. Questions and Clarifications (Medium Priority)

**Characteristics**:
- Asks for explanation
- Requests clarification of implementation

**Action**:
1. Reply with clear explanation
2. Make code more self-documenting if needed
3. Add documentation if helpful

### D. General Feedback (Low Priority)

**Characteristics**:
- General observations
- Praise or acknowledgment

**Action**:
- Consider for future improvements
- No immediate action needed

## Step 3: Systematic Implementation

### For Committable Suggestions

Work through each suggestion:

1. **Locate the code** referenced in comment
2. **Apply the suggestion** exactly as provided
3. **Verify the change** makes sense
4. **Run quality checks**:
   ```bash
   make phpcsfixer
   make psalm
   ```
5. **Commit immediately**:
   ```bash
   git add .
   git commit -m "Apply suggestion: [brief description]"
   ```

### For LLM Prompts

Break down complex refactoring:

1. **Create interfaces/abstractions first**
2. **Implement new classes/methods**
3. **Update existing code** to use new structure
4. **Remove deprecated code**
5. **Update tests and documentation**
6. **Commit each logical change separately**

### For Complex Refactoring

Create separate commits:

```bash
git commit -m "refactor: extract validation strategy interface"
git commit -m "refactor: implement concrete validation strategies"
git commit -m "refactor: update validator to use strategies"
git commit -m "refactor: remove old validation logic"
git commit -m "test: update tests for new validation approach"
```

## Step 4: Quality Assurance

**After each change or group of related changes**:

### For Code Changes
```bash
make phpcsfixer      # Fix code style
make psalm           # Static analysis
make unit-tests      # Run unit tests
```

### For Significant Changes
```bash
make ci              # Full CI suite
```

### For Test Changes
```bash
make unit-tests      # Verify tests pass
make infection       # Check mutation coverage
```

## Step 5: Comment Response Strategy

### Reply Systematically

**For Questions**:
```markdown
Good question! [Clear, concise answer]

[Optional: Reference to documentation or code]
```

**For Implemented Suggestions**:
```markdown
✅ Implemented in [commit hash]

[Optional: Brief explanation of approach if needed]
```

**For Complex Refactoring**:
```markdown
Refactored as suggested across these commits:
- [commit hash]: [brief description]
- [commit hash]: [brief description]

[Explanation of approach and any trade-offs]
```

**For Cannot Implement**:
```markdown
I understand the concern, but [technical constraint].

Alternative approach: [propose alternative]

What do you think?
```

## Step 6: Verification

### Before Completing Review Cycle

```bash
# Ensure clean working directory
git status

# Get latest changes
git pull origin main

# Check current comment status
make pr-comments

# Run full CI
make ci

# Verify success message
# Must see: "✅ CI checks successfully passed!"
```

### Push Changes

```bash
git push
```

## Advanced Patterns

### Handling Conflicting Comments

1. **Prioritize architectural concerns** over stylistic preferences
2. **Discuss with reviewers** before implementing conflicting suggestions
3. **Document decision** in commit message or PR comment

### Large-Scale Refactoring

1. **Create separate commits** for each logical change
2. **Maintain backward compatibility** when possible
3. **Update tests incrementally** with code changes
4. **Use feature flags** for risky changes

### Performance and Security Comments

1. **Address security concerns immediately** (highest priority)
2. **Benchmark performance changes** when suggested
3. **Document trade-offs** in code comments

## Integration with Workflow

### Before Starting
```bash
git status                  # Clean working directory
git pull origin main        # Latest changes
make pr-comments           # Current comment status
```

### During Refactoring
- Work on one comment or related group at a time
- Commit frequently with descriptive messages
- Reference comment URLs in commit messages

### After Completing
```bash
make ci                    # Full quality check
make pr-comments           # Verify no new unresolved comments
git push                   # Push all changes
```

## Success Criteria

- All unresolved comments addressed
- Clear responses provided to questions
- All suggested changes implemented or alternatives proposed
- All commits have descriptive messages
- CI checks pass: "✅ CI checks successfully passed!"
- Changes pushed to remote branch
