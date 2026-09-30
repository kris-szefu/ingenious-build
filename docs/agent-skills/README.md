# Agent skills

Reusable playbooks for AI agents working on this repo.
One file per concern. Read `default-workflow.md` first.

## Playbooks (process)
- `default-workflow.md` — baseline loop: plan → guardrails → TDD → debug → refactor → review → commit.
- `testing-strategy.md` — TDD loop + PHPUnit layout used here.

## Guardrails (enforcement)
- `architecture.md` — module boundary map (read).
- `module-boundaries-guardrails.md` — layout enforcement checklist.
- `php-and-laravel-guardrails.md` — strict types + no Laravel magic in Domain/Application + keep-it-simple.
- `laravel-conventions.md` — how to use Laravel 13 at the framework edges (controllers, FormRequests, Eloquent, jobs, security, testing, deploy).
- `adr-architecture-guardrails.md` — decision mapping + ADR/architecture-doc updates.
- `backlog-status.md` — keep `docs/BACKLOG.md` and per-task files in sync with the code.
- `submission-updates.md` — keep the reviewer-facing `/SUBMISSION.md` in sync with what ships; root `README.md` stays untouched.

## Reference notes (task-specific facts, not process)
- `invoice-domain.md` — invoice invariants, state machine, HTTP status codes, and Invoice ↔ Notifications flow.

## Convention
- One skill per file, kebab-case filename.
- Keep skills short and imperative. Link out to `docs/architecture/` and `docs/adr/` when useful.
- If a task doesn't request a mode, use `default-workflow.md`.

