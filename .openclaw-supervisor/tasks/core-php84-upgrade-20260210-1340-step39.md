You are continuing PR #103 on branch openclaw/core-php84-upgrade-20260210-1340 for the PHP 8.4 upgrade.

Current head SHA: b3d8d7edaf2646258d53023f3aef2cc807a404b9.

Goal (CI FIRST): Fix failing required checks with minimal, PHP 8.4-focused changes. Right now, prioritize Infection failure first. Do not address CodeRabbit requests until required CI checks are green.

Constraints:

- Keep changes minimal and directly related to this upgrade.
- Do not change unrelated workflows or refactor unrelated code.
- No secrets.
- Commit and push to openclaw/core-php84-upgrade-20260210-1340.

What to do:

1. Pull latest branch and inspect failing CI logs for Infection on current head.
2. Apply minimal fix for the Infection failure.
3. Run the smallest relevant local validation if feasible.
4. Commit with a concise message and push.
5. Report root cause, files changed, commands run, and commit SHA.
