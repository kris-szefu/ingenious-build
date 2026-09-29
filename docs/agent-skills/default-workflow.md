## Goal
Baseline way of working for any coding task in this repo. Covers planning, TDD, debugging, refactoring, and pre-submission review.

## When to use
Default for any task unless a more specific skill is requested.

## Workflow
1. **Clarify** the request, constraints, non-goals, and expected outcomes.
2. **Plan**: propose a short implementation plan before substantial changes.
3. **Guardrails** (run the ones that apply to the change):
   - Touches module boundaries or namespaces → `module-boundaries-guardrails.md`.
   - Touches `Domain/` or `Application/` code → `php-and-laravel-guardrails.md`.
   - Adds/changes HTTP endpoints, Eloquent, jobs, or config → `laravel-conventions.md`.
   - Includes a major architectural decision → `adr-architecture-guardrails.md`.
4. **TDD loop**: write a failing test at the right layer (see `testing-strategy.md`) → minimal code to pass → refactor with tests green.
5. **Debugging** (when things fail):
   - Reproduce as a failing test if possible.
   - Narrow scope with hypotheses (which layer, which module, which adapter).
   - Use `--filter`, targeted `Log::debug`, `storage/logs/laravel.log`.
   - Fix minimally, add a regression test.
6. **Refactoring** (only with green tests):
   - Small, reversible structural changes.
   - Re-run `php artisan test` after each step.
   - Re-check `module-boundaries-guardrails.md` if code moved across layers.
7. **Verify**: `php artisan test` green, `vendor/bin/pint` clean.
8. **Pre-submission review**:
   - Re-run `module-boundaries-guardrails.md` and `php-and-laravel-guardrails.md` on the diff.
   - Confirm test coverage for happy path, validation errors, and illegal state transitions.
   - Write the `Decision & Docs Impact` note (see `adr-architecture-guardrails.md`).
9. **🛑 Review breakpoint — STOP HERE**:
   - Summarize for the reviewer: files changed, tests added/run (paste last lines of `php artisan test`), `vendor/bin/pint --test` result on touched paths, guardrail re-check, and the proposed commit message quoted verbatim.
   - Show `git status` and `git --no-pager diff --stat`. Offer `git --no-pager diff` on request.
   - **Do not `git add`. Do not `git commit`.** Wait for explicit user approval (e.g. "commit", "ship it", "lgtm").
   - On rejection: address feedback (loop back to step 4 or 6) and re-enter this breakpoint. If the task is a backlog item and status was already flipped to `done`, revert it to `in-progress` per `backlog-status.md`.
10. **Commit** (only after explicit approval):
    - `git add` the exact paths listed in the review summary.
    - `git commit` with the pre-approved message.
    - Report the resulting SHA. For backlog items, backfill the SHA into the `docs/BACKLOG.md` change-log line.

## Commit messages
Follow the Conventional Commits style already used in this repo (e.g. `chore(build): bump to PHP 8.5, refresh deps, simplify start.sh`, `docs(readme): reword testing scope and add evaluation criteria`).

Format:
```
<type>(<scope>): <short imperative summary>
```

Rules:
- **type** (lowercase): `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `build`, `perf`, `style`.
- **scope** (lowercase, required): the affected module or area, e.g. `invoices`, `notifications`, `build`, `readme`, `tests`, `docs`, `ci`.
- **summary**: imperative mood, lowercase, no trailing period, ~72 chars max. Comma-separated clauses allowed for multi-part changes (matches existing style).
- One logical change per commit. Split unrelated changes.
- Optional body (wrap at ~72 chars) explaining the *why*; separate from the subject with a blank line.
- Reference ADRs in the body when applicable: `Refs: docs/adr/NNNN-*.md`.
- Breaking changes: append `!` after scope (`feat(invoices)!: ...`) and add a `BREAKING CHANGE:` footer.

Examples:
- `feat(invoices): add send use-case and status transition`
- `test(invoices): cover illegal state transitions on send`
- `refactor(notifications): extract driver contract`
- `docs(agent-skills): consolidate skills into fewer files`

## Output
A concise update: plan, implemented changes, verification results, `Decision & Docs Impact`, and follow-up options.
# default-workflow
