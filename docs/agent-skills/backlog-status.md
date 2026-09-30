# backlog-status

## Goal
Keep task statuses in `docs/BACKLOG.md` and the individual task files under `docs/backlog/NNN-*.md` **consistent** with the code changes in the same commit. Prevent stale status.

## When to use
- Before committing a task's implementation (flip `todo` → `done`).
- Whenever scope changes: mark `blocked` or `skipped` with a reason.

## Workflow
1. **Pick the next task** with `Status: todo` in `docs/BACKLOG.md` (top-down order unless dependencies dictate otherwise).
2. **Do the work** following `default-workflow.md` and the guardrails listed on the task file. The review breakpoint and commit rules live in `default-workflow.md` — do not duplicate them here. Task status stays `todo` throughout implementation; there is no `in-progress` state.
3. **Sync backlog + task file BEFORE the review breakpoint** (default-workflow step 9). All of the following edits must be staged in the **same commit** as the implementation:
   - Tick every satisfied `Acceptance criteria` checkbox on `docs/backlog/NNN-*.md`. If any is not satisfied, either finish it or split the task; do not flip to `done`.
   - Set `## Status: done` on the task file.
   - Flip the row in `docs/BACKLOG.md` to `done`.
   - Update the `Progress` counters in `docs/BACKLOG.md`.
   Then enter the review breakpoint in `default-workflow.md` with the diff including these files.
4. **On rejection at the review breakpoint**: revert the `done` flip back to `todo` in both files and restore the progress counters. Address feedback, then re-enter step 3.
5. **Blocked/skipped**: set the status on both files and add a one-liner in the task file's Notes explaining why + link to the blocker.

## Rules
- Never mark `done` if any acceptance-criteria checkbox is unchecked.
- Task file `Status` and `BACKLOG.md` row must always agree — if they disagree, the task file wins and `BACKLOG.md` must be fixed immediately.
- Status changes ship **in the same commit** as the code (or as the only content for docs-only tasks). Never commit implementation without the matching backlog + task-file update.
- If a task grows past one commit, split into `NNN-a`, `NNN-b`; do not flip `done` on partial work.

## Output
A commit whose diff includes both the implementation and the synchronized status update. Reviewers can read `docs/BACKLOG.md` and trust it.
