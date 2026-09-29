# 013 — ADR + architecture docs

## Story
As a reviewer, I want the key decisions written down, so that trade-offs and boundaries are explicit.

## Addresses (README requirements)
- "Documentation: Candidates are encouraged to document their decisions and reasoning..."
- Evaluation criterion: "Architecture, separation of concerns, and clarity of module boundaries."

## Scope
- In:
  - `docs/adr/0001-invoices-module-structure.md` — DDD layout, sync send flow, invoice-id as notification reference id, event-name mismatch, error mapping (422 vs 409).
  - Update `docs/architecture/README.md` with Invoices module map and cross-module flow.
  - Short "How to run + how to test" section in root `README.md` (kept separate from the task description).
- Out:
  - Any code changes.

## Acceptance criteria
- [ ] ADR follows repo ADR shape.
- [ ] Architecture doc reflects final module boundaries.

## Guardrails to run
- adr-architecture-guardrails

## Commit
`docs(invoices): add ADR and update architecture docs`

## Status
todo

