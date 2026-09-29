# backlog/

Per-task specs for the Invoices module. One file per task = one commit.

## Where things live
- **Status dashboard**: [`../BACKLOG.md`](../BACKLOG.md) — single source of truth for task status, progress counters, and change log. Do **not** duplicate the index here.
- **Task specs**: `NNN-kebab-title.md` in this folder — story, README requirements addressed, scope, acceptance criteria, tests, guardrails, commit subject, status.
- **Template**: [`_TEMPLATE.md`](./_TEMPLATE.md) — copy when adding a new task.
- **Skill**: [`../agent-skills/backlog-status.md`](../agent-skills/backlog-status.md) — rules for keeping statuses in sync.

## Conventions
- File name: `NNN-kebab-title.md`, zero-padded, monotonically increasing.
- Each task maps to **one Conventional Commit** (see `../agent-skills/default-workflow.md`).
- Each task quotes the README requirement(s) it addresses verbatim.
- Status values: `todo` · `in-progress` · `done` · `blocked` · `skipped`.
- Status lives in **two** places and must stay in sync: this file's `## Status` section and the row in `../BACKLOG.md`. The task file wins on conflict.
- If a task grows past one commit, split into `NNN-a`, `NNN-b`.

