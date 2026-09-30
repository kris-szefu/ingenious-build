# 013 — ADR + architecture docs

## Story
As a reviewer, I want the key decisions written down, so that trade-offs and boundaries are explicit.

## Addresses (README requirements)
- "Documentation: Candidates are encouraged to document their decisions and reasoning..."
- Evaluation criterion: "Architecture, separation of concerns, and clarity of module boundaries."

## Scope
- In:
  - `docs/adr/0002-invoices-module-structure.md` — DDD layout, sync send flow, invoice-id as notification reference id, event-name mismatch, error mapping (422 uniformly, not 409), delivery listener idempotency policy. (Numbered 0002 because [ADR 0001](../adr/0001-openapi-strategy.md) is the OpenAPI strategy.)
  - Update `docs/architecture/README.md` with Invoices module map, HTTP surface, and cross-module flow diagrams (create / view / send / deliver).
  - **`SUBMISSION.md` at repo root** — how to run + how to test + delivered scope + reviewer notes. Kept separate from the recruiter's task description in `README.md`, which stays untouched.
  - **`docs/agent-skills/submission-updates.md`** — new skill governing when and how `SUBMISSION.md` must be kept in sync with future changes. Registered in `docs/agent-skills/README.md`.
- Out:
  - Any code changes.
  - Any edit to the root `README.md` (recruiter's original task description — off-limits).

## Acceptance criteria
- [x] ADR 0002 follows repo ADR shape (`Status`, `Date`, `Context`, `Decision`, `Consequences`, `Alternatives considered`).
- [x] Architecture doc reflects final module boundaries (module map, dependency direction inside + across modules, HTTP surface, four cross-module flows, ADR index).
- [x] `SUBMISSION.md` exists at repo root with run + test + delivered-scope sections and links to ADRs / architecture docs.
- [x] Root `README.md` is unchanged.
- [x] New agent-skill `submission-updates.md` is registered in `docs/agent-skills/README.md`.

## Guardrails to run
- adr-architecture-guardrails
- submission-updates

## Commit
`docs(invoices): add ADR and update architecture docs`

## Status
done

