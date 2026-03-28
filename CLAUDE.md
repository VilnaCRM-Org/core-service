# CLAUDE.md

Claude Code should not duplicate our contributor guide. Follow these pointers instead:

- **Primary source:** read `AGENTS.md` for every workflow, command, and architectural rule. It is the single source of truth.
- **Skills library:** consult `.claude/skills/` for focused workflows (CI, testing, reviews, load testing, etc.).
- **Optional BMALPH setup:** run `bash scripts/local-coder/install-bmalph.sh --platform claude-code` from the repo root when you want the BMALPH CLI locally, and use `--init --dry-run` first before writing project files.

Nothing else is intentionally documented here—always defer to `AGENTS.md`.
