# 016 — Spike: OpenAPI usage in this project

## Story
As the maintainer, I want to evaluate whether adopting OpenAPI as the source of truth for the three Invoices endpoints (create / view / send) is worth the cost, so that I can decide before hardening the HTTP layer whether to author a spec, generate code, or skip it entirely.

## Addresses (README requirements)
- None directly — the README describes endpoints in prose and does not require a machine-readable contract. This spike exists to decide whether to *add* that requirement to our own bar.

## Scope
- In (time-boxed research + writeup, ~½ day):
  - Compare at least three approaches against our architecture (Domain/Application/Infrastructure/Presentation split, `InvoiceView` DTO, `CreateInvoiceCommand`):
    1. **Hand-authored `docs/api/openapi.yaml`**, no runtime coupling. Validation via `spectral` (optional CI step).
    2. **Annotation-driven generation** with `zircote/swagger-php` or `darkaonline/l5-swagger` — spec generated from PHP attributes on controllers.
    3. **Laravel-native introspection** with `dedoc/scramble` — spec inferred from FormRequests + resources + route signatures, no annotations.
    4. **Contract-first with generated code** — `openapi.yaml` → generated request/response DTOs (e.g. `jane-php/open-api`, `openapi-generator`). Evaluate whether generated classes can live in `Presentation/Http/Generated/` without leaking into `Application/`.
  - For each option, record: setup cost, runtime deps added, where the generated/annotated code lives, drift risk, how it interacts with our DTOs (`CreateInvoiceCommand`, `InvoiceView`), and CI story.
  - Prototype the winner (or top 2) against the **view invoice** endpoint only — enough to see the shape without committing the full API surface.
  - Decide: **adopt**, **adopt-later**, or **skip**.
- Out:
  - Any change to controllers, DTOs, or routing beyond a throwaway prototype branch.
  - Client-SDK generation (separate concern; only mention in the ADR if relevant).
  - GraphQL / JSON:API alternatives.

## Acceptance criteria
- [ ] `docs/adr/NNNN-openapi-strategy.md` written, following `adr-architecture-guardrails.md`, with: context, options evaluated, decision, consequences, and a link to the prototype branch/commit (or a code snippet if the prototype was thrown away).
- [ ] Decision explicitly answers: (a) do we ship a spec with the submission? (b) if yes, hand-authored vs generated? (c) do we generate code from the spec, and where does it live relative to module boundaries?
- [ ] If decision is **adopt**: follow-up backlog task(s) created (e.g. `017-openapi-spec`, `018-openapi-ci-check`) with clear scope.
- [ ] If decision is **adopt-later** or **skip**: rationale recorded in the ADR so we don't re-open the debate on a whim.
- [ ] No production code merged from this spike; only the ADR (and follow-up task files) land on the main branch.

## Tests
- N/A (spike). The prototype does not need tests; the ADR is the deliverable.

## Guardrails to run
- adr-architecture-guardrails
- module-boundaries-guardrails (verify generated code, if any, would not violate layer rules)

## Commit
`docs(invoices): spike openapi strategy (ADR)`

## Status
todo

