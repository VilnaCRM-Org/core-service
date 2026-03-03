Task: core-php84-upgrade-20260210-1340
Repo: VilnaCRM-Org/core-service
Branch: openclaw/core-php84-upgrade-20260210-1340
PR: #130
Head SHA: 026f0335e909ae9851357e9bc61c81b6f9bb1abe
Review decision: CHANGES_REQUESTED
Current failing checks: none
Current pending checks: none

Goal:

- Resolve required CI failures first (non-Bats first, Run Bats Core Tests last).
- Keep changes minimal and PHP 8.4-upgrade focused.
- Commit and push to the task branch when a fix is ready.

Constraints:

- Do not edit unrelated workflows or refactor.
- Do not expose secrets.
- Keep output concise and execution-focused.
