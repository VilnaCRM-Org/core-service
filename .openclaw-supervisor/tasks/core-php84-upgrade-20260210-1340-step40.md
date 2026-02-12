Task: core-php84-upgrade-20260210-1340
Repo: VilnaCRM-Org/core-service
Branch: openclaw/core-php84-upgrade-20260210-1340
PR: #103
Head SHA: 02b0ec2ed1d080ab25cba2c20807c811ab80308a
Review decision: CHANGES_REQUESTED
Current failing checks: Behat, Psalm, Run Bats Core Tests
Current pending checks: none

Goal:

- Resolve required CI failures first (non-Bats first, Run Bats Core Tests last).
- Keep changes minimal and PHP 8.4-upgrade focused.
- Commit and push to the task branch when a fix is ready.

Constraints:

- Do not edit unrelated workflows or refactor.
- Do not expose secrets.
- Keep output concise and execution-focused.
