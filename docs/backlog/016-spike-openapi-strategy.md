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
- [x] `docs/adr/0001-openapi-strategy.md` written, following `adr-architecture-guardrails.md`, with: context, options evaluated, decision, consequences, and a prototype code snippet (spike thrown away, no branch merged).
- [x] Decision explicitly answers: (a) ship a spec — **yes**; (b) hand-authored vs generated — **hand-authored YAML is the contract and the generated DTOs are derived from it**; (c) generate code from the spec — **yes**; generated DTOs live in `src/Modules/Invoices/Presentation/Http/Generated/` as infrastructure output, not in `Application/` or `Domain/`.
- [x] Follow-up backlog tasks created: [017 — OpenAPI spec](./017-openapi-spec.md), [018 — OpenAPI CI check](./018-openapi-ci-check.md).
- [x] No production code merged from this spike; only the ADR and follow-up task files land on the main branch.

## Outcome
**Adopt (Option D — contract-first with generated DTOs).** See [ADR 0001](../adr/0001-openapi-strategy.md). `docs/api/openapi.yaml` is the source of truth; `jane-php/open-api` generates typed PHP DTOs and serializer support into `src/Modules/Invoices/Presentation/Http/Generated/`; hand-written assemblers/serializers translate between generated DTOs and the existing Application DTOs (`CreateInvoiceCommand`, `InvoiceView`) so that `Application/` and `Domain/` never import generated code. Scramble (Option C) and annotations (Option B) are rejected; hand-authored-only (Option A) is retained only as the specification authoring step, with generation as an explicit infrastructure step.

## Tests
- N/A (spike). The prototype does not need tests; the ADR is the deliverable.

## Guardrails to run
- adr-architecture-guardrails
- module-boundaries-guardrails (verify generated code, if any, would not violate layer rules)

## Commit
`docs(invoices): spike openapi strategy (ADR)`

## Status
done
