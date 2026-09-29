# 015 — Spike: external state-machine library for Invoice

## Story
As a maintainer, I want a time-boxed spike evaluating an external state-machine library for the Invoice aggregate, so that we make an informed decision before the transition graph grows beyond what a hand-rolled implementation comfortably supports.

## Addresses (README requirements)
- "An invoice can only be created in `draft` status."
- "An invoice can only be sent if it is in `draft` status."
- "An invoice can only be marked as `sent-to-client` if its current status is `sending`."

(Non-functional: keeps the state machine maintainable as the domain evolves.)

## Context
Task 003 implemented the state machine directly on the `Invoice` aggregate:
- 3 states (`Draft`, `Sending`, `SentToClient`), 2 edges.
- Guards live on the aggregate; typed domain exceptions on illegal moves.
- Zero external dependencies, aligned with `module-boundaries-guardrails` (no framework leakage into `Domain`).

This spike revisits that decision if/when the graph grows (e.g. `Cancelled`, `Overdue`, `Paid`, retries, or product asks for a visualizable diagram).

## Scope
- In:
  - Compare at least: `symfony/workflow`, `winzou/state-machine`, `sebdesign/laravel-state-machine`, and an in-house **enum-encoded transition table** (Option B — `StatusEnum::allowedNext()` / `canTransitionTo()`).
  - Evaluate each against:
    - Domain purity (can it stay out of `Modules\Invoices\Domain`? wrapping cost?).
    - Testability of transitions and guards in pure PHPUnit.
    - Ability to express business guards (e.g. "≥1 line, all lines valid") vs. only edge validity.
    - Diagram/visualization output (Graphviz/Mermaid) for docs.
    - Auditability (transition events, listeners).
    - Dependency weight and Laravel-version compatibility.
  - Produce a short ADR (`docs/adr/NNNN-invoice-state-machine.md`) capturing the decision (keep hand-rolled, adopt Option B, or adopt a library) with rationale.
  - If Option B is chosen, implement `StatusEnum::allowedNext()` + `canTransitionTo()` and route the edge check through it (business guards stay on the aggregate).
- Out:
  - Actually replacing the state machine with a third-party library in this task — that would be a follow-up implementation task created by the ADR outcome.
  - Any change to the public domain API (`Invoice::send()`, `Invoice::markSentToClient()`).

## Decision triggers (when to escalate beyond hand-rolled)
- ≥ ~5 states or branching transitions.
- Need for a visualizable diagram shared with non-devs.
- Need for centralized transition-event auditing.
- Multiple aggregates sharing the same transition semantics.

## Acceptance criteria
- [ ] ADR merged under `docs/adr/` recording the comparison and decision.
- [ ] `docs/architecture/README.md` links the ADR from the Invoices section.
- [ ] If Option B is chosen: enum transition table implemented + unit tests; `Invoice` still owns business guards; no behavior change observable from tests written for 003.
- [ ] If a library is chosen: a follow-up implementation backlog item is created (do not implement here).
- [ ] No new runtime dependency added under this task unless the ADR explicitly adopts one.

## Tests
- Unit:
  - `it_lists_allowed_next_statuses_per_state` (only if Option B implemented).
  - `it_rejects_disallowed_edges_via_enum_check` (only if Option B implemented).
  - Existing `InvoiceStateMachineTest` must continue to pass unchanged.

## Guardrails to run
- module-boundaries-guardrails
- adr-architecture-guardrails
- php-and-laravel-guardrails

## Commit
`docs(invoices): spike external state-machine options (ADR)`

## Status
todo

