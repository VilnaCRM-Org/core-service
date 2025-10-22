# Documentation Improvement Issue Template

## Overview

A new GitHub issue template has been created to facilitate the creation of a documentation improvement issue for the core-service repository. The template helps standardize the process of tracking documentation enhancements needed to align core-service with user-service documentation standards.

## Location

The template is located at:

```
.github/ISSUE_TEMPLATE/documentation_improvement.md
```

## How to Use

### Option 1: Via GitHub UI (Recommended)

1. Go to the [core-service repository](https://github.com/VilnaCRM-Org/core-service)
2. Click on the "Issues" tab
3. Click the "New issue" button
4. Select "Documentation Enhancement" from the template options
5. The template will be pre-filled with all the necessary sections
6. Review and customize as needed
7. Click "Submit new issue"

### Option 2: Direct Link

Once this PR is merged, you can create an issue directly using this link:

```
https://github.com/VilnaCRM-Org/core-service/issues/new?template=documentation_improvement.md
```

## Template Contents

The template includes the following sections:

### 1. Description

- **Problem Statement**: Explains the documentation gaps
- **Target Audience**: Identifies who will benefit
- **Use Cases**: Provides specific scenarios

### 2. Tasks

Organized by documentation areas:

- Update Minimal Installation Section
- Enhance Repository Synchronization Section
- Review and Update Make Commands List
- Add GraphQL Documentation (if applicable)
- Review and Validate

### 3. Acceptance Criteria

Clear, measurable criteria for completion:

- Installation section completeness
- URL documentation and testing
- Style consistency
- Contributor onboarding success

### 4. Labels and Milestones

Suggested labels:

- `documentation`
- `enhancement`
- `good first issue`

### 5. References

Links to:

- user-service README for reference
- core-service README (current state)
- Template synchronization documentation

## Key Documentation Improvements Identified

Based on comparison with user-service, the template identifies these gaps:

1. **Installation Steps**
   - Missing step-by-step installation commands
   - No specific endpoint URLs provided
   - GraphQL endpoint not documented (if applicable)
   - Architecture diagram access not mentioned

2. **Repository Synchronization**
   - Lacks "How It Works" explanation
   - Missing "Configuration" details
   - Needs expanded "Benefits" section

3. **Make Commands**
   - Should be verified against Makefile
   - May need additional commands from user-service

## Benefits

- **Standardization**: Ensures consistent documentation quality across VilnaCRM services
- **Onboarding**: Helps new developers get started faster
- **Maintenance**: Makes it easier to keep documentation up-to-date
- **Collaboration**: Provides clear guidelines for contributors

## Next Steps

1. **Merge this PR** to make the template available
2. **Create an issue** using the template
3. **Assign the issue** to appropriate team members
4. **Complete the tasks** outlined in the template
5. **Review and test** the updated documentation

## Questions?

If you have questions about using this template or the documentation improvements needed, please refer to:

- [user-service README](https://github.com/VilnaCRM-Org/user-service/blob/main/README.md) for examples
- The template itself for detailed task descriptions
- The core-service maintainers for clarification
