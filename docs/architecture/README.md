# Architecture docs

Living documentation of module boundaries, flows, and interfaces.
Update these files whenever a change alters boundaries, dependency direction, or public module `Api` contracts. Guardrail: `docs/agent-skills/adr-architecture-guardrails.md`.

Suggested files (add as needed):
- `modules.md` — module map and responsibilities.
- `invoice-flow.md` — create / send / deliver sequence.
- `boundaries.md` — allowed dependency direction and cross-module rules.

## API contract
- [ADR 0001 — OpenAPI strategy](../adr/0001-openapi-strategy.md): contract-first. `docs/api/openapi.yaml` is the source of truth; `jane-php/open-api` generates typed PHP DTOs into `src/Modules/Invoices/Presentation/Http/Generated/`; hand-written assemblers under `Presentation/Http/Assemblers/` translate between generated DTOs and Application DTOs (`CreateInvoiceCommand`, `InvoiceView`). `Application/` and `Domain/` never depend on generated code. Implementation tracked in backlog tasks [017](../backlog/017-openapi-spec.md) and [018](../backlog/018-openapi-ci-check.md).
