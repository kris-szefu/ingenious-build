# backlog-status

## Goal
Keep task statuses in `docs/BACKLOG.md` and the individual task files under `docs/backlog/NNN-*.md` **consistent** with the code changes in the same commit. Prevent stale status.

## When to use
- Before starting a task (flip `todo` → `in-progress`).
- Before committing a task's implementation (flip `in-progress` → `done`).
- Whenever scope changes: mark `blocked` or `skipped` with a reason.

## Workflow
1. **Pick the next task** with `Status: todo` in `docs/BACKLOG.md` (top-down order unless dependencies dictate otherwise).
2. **Start**: set `Status: in-progress` in both:
   - `docs/BACKLOG.md` row.
   - `docs/backlog/NNN-*.md` `## Status` section.
   Add a change-log line in `docs/BACKLOG.md`:
   ```
   YYYY-MM-DD  NNN  todo → in-progress   <reason>
   ```
3. **Do the work** following `default-workflow.md` and the guardrails listed on the task file.
4. **Finish**: before staging the commit:
   - Tick every `Acceptance criteria` checkbox that is satisfied. If any is not, either finish it or split the task.
   - Set `Status: done` in both files.
   - Update the `Progress` counters in `docs/BACKLOG.md`.
   - Add a change-log line with the commit subject (sha added post-commit, optional).
5. **Commit**: status changes ship **in the same commit** as the code (or as the only content for docs-only tasks).
6. **Blocked/skipped**: set the status and add a one-liner explaining why + link to the blocker.

## Rules
- Never mark `done` if any acceptance-criteria checkbox is unchecked.
- Never have more than **one** task in `in-progress` at a time (keeps the story-per-commit discipline).
- Task file `Status` and `BACKLOG.md` row must always agree — if they disagree, the task file wins and `BACKLOG.md` must be fixed immediately.
- If a task grows past one commit, split into `NNN-a`, `NNN-b`; do not carry a `done` status forward on partial work.

## Pre-commit checklist
- [ ] Task file acceptance criteria all checked (or task split).
- [ ] Task file `Status` updated.
- [ ] `docs/BACKLOG.md` row `Status` updated.
- [ ] `docs/BACKLOG.md` `Progress` counters updated.
- [ ] Change-log line appended in `docs/BACKLOG.md`.

## Output
A commit whose diff includes both the implementation and the synchronized status update. Reviewers can read `docs/BACKLOG.md` and trust it.

