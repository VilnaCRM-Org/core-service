---
name: Documentation Enhancement
about: Align core-service documentation with user-service standards
title: 'Documentation Enhancement: Align with user-service Documentation Standards'
labels: documentation, enhancement
assignees: ''
---

### Description

The `core-service` repository needs documentation improvements to match the comprehensive style and detail level found in the `user-service` repository. This will ensure consistency across the VilnaCRM ecosystem and provide better guidance for developers.

- **Problem Statement**: Currently, the `core-service` README lacks several important sections that are present in `user-service`, making it harder for new developers to get started and understand the full capabilities of the service. The documentation should be enhanced to provide clear installation steps, endpoint URLs, and detailed explanations of features like template synchronization.

- **Target Audience**:
  - New developers joining the VilnaCRM project
  - Contributors looking to set up the development environment
  - Users wanting to understand the template synchronization feature
  - Developers integrating with the service APIs

- **Use Cases**:
  1. A new developer clones the repository and needs step-by-step instructions with specific commands and URLs to get started
  2. A contributor wants to understand how the template synchronization works and how to configure it
  3. A developer needs to find the GraphQL endpoint URL (if available) to test API queries
  4. A team member wants to view the architecture diagram to understand the system design

#### Example:

"After comparing with user-service, core-service should include detailed installation steps with specific endpoint URLs (e.g., `https://localhost/api/docs`), GraphQL documentation (if applicable), architecture diagram access instructions, and a comprehensive explanation of the repository synchronization feature including its configuration and benefits."

### Tasks

Include specific tasks in the order they need to be done.

- [ ] **Update Minimal Installation Section**
  - [ ] Add explicit command for cloning/using the template
  - [ ] Include the `make install` command step
  - [ ] Add database migration commands (e.g., `make doctrine-migrations-migrate` if applicable)
  - [ ] Provide specific URLs for REST API docs: `https://localhost/api/docs`
  - [ ] Add GraphQL endpoint URL if service supports GraphQL: `https://localhost/api/graphql`
  - [ ] Add architecture diagram access URL: `http://localhost:8080/workspace/diagrams`

- [ ] **Enhance Repository Synchronization Section**
  - [ ] Add "How It Works" subsection explaining automated PR creation
  - [ ] Include scheduling information (mention cron trigger timing)
  - [ ] Add "Configuration" subsection with workflow details and permissions
  - [ ] Expand "Benefits of Synchronization" with detailed explanation
  - [ ] Add link to the workflow file: `.github/workflows/template-sync.yml`
  - [ ] Reference GitHub documentation on token permissions

- [ ] **Review and Update Make Commands List**
  - [ ] Verify all available make commands in the Makefile
  - [ ] Add any missing commands that exist in user-service (if applicable to core-service)
  - [ ] Ensure command descriptions are clear and match actual functionality
  - [ ] Organize commands in logical groups similar to user-service

- [ ] **Add GraphQL Documentation** (if applicable)
  - [ ] Document GraphQL endpoint location
  - [ ] Provide example of how to access GraphQL playground
  - [ ] Add any GraphQL-specific setup instructions

- [ ] **Review and Validate**
  - [ ] Test all documented commands locally
  - [ ] Verify all URLs are correct and accessible
  - [ ] Ensure documentation style matches user-service
  - [ ] Get feedback from at least one team member

### Acceptance Criteria

Ensure that each criterion is **measurable** and **testable** to clearly define what constitutes completion.

- [ ] Installation section includes step-by-step commands matching user-service format
- [ ] All endpoint URLs (REST API, GraphQL if applicable, architecture diagrams) are documented and tested
- [ ] Repository Synchronization section includes "How It Works", "Configuration", and "Benefits" subsections
- [ ] Make commands list is verified and complete
- [ ] Documentation tone and style are consistent with user-service README
- [ ] A new contributor can successfully set up and run the project using only the README
- [ ] All links and URLs in the documentation have been tested and work correctly

### Labels and Milestones

**Remember to use helpful labels and milestones to categorize and track the progress of this feature request.**

- **Labels**: `documentation`, `enhancement`, `good first issue`
- **Milestones**: Assign to next documentation sprint or release milestone

### References

- [user-service README.md](https://github.com/VilnaCRM-Org/user-service/blob/main/README.md) - Reference for documentation improvements
- [core-service README.md](https://github.com/VilnaCRM-Org/core-service/blob/main/README.md) - Current documentation
- [actions-template-sync](https://github.com/AndreasAugustin/actions-template-sync) - Template synchronization tool documentation
