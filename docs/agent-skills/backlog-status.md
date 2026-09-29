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
   Commit this flip on its own only if you will not start the implementation immediately; otherwise stage it together with the implementation commit in step 4.
3. **Do the work** following `default-workflow.md` and the guardrails listed on the task file. The review breakpoint and commit rules live in `default-workflow.md` — do not duplicate them here.
4. **Sync backlog + task file BEFORE the review breakpoint** (default-workflow step 9). All of the following edits must be staged in the **same commit** as the implementation:
   - Tick every satisfied `Acceptance criteria` checkbox on `docs/backlog/NNN-*.md`. If any is not satisfied, either finish it or split the task; do not flip to `done`.
   - Set `## Status: done` on the task file.
   - Flip the row in `docs/BACKLOG.md` to `done`.
   - Update the `Progress` counters in `docs/BACKLOG.md`.
   - Append a change-log line with the commit subject, SHA left as `<pending>`:
     ```
     YYYY-MM-DD  NNN  in-progress → done   <commit subject>   (<pending>)
     ```
   Then enter the review breakpoint in `default-workflow.md` with the diff including these files.
5. **On rejection at the review breakpoint**: revert the `done` flip back to `in-progress` in both files, restore progress counters, and remove/adjust the change-log line. Address feedback, then re-enter step 4.
6. **After commit**: backfill the commit SHA into the `<pending>` change-log line and amend the commit (`git commit --amend --no-edit`) so the SHA ships in the same commit as the status change.
7. **Blocked/skipped**: set the status and add a one-liner explaining why + link to the blocker.

## Rules
- Never mark `done` if any acceptance-criteria checkbox is unchecked.
- Never have more than **one** task in `in-progress` at a time (keeps the story-per-commit discipline).
- Task file `Status` and `BACKLOG.md` row must always agree — if they disagree, the task file wins and `BACKLOG.md` must be fixed immediately.
- Status changes ship **in the same commit** as the code (or as the only content for docs-only tasks). Never commit implementation without the matching backlog + task-file update.
- If a task grows past one commit, split into `NNN-a`, `NNN-b`; do not carry a `done` status forward on partial work.

## Output
A commit whose diff includes both the implementation and the synchronized status update. Reviewers can read `docs/BACKLOG.md` and trust it.
