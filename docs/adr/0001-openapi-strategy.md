# ADR 0001 — OpenAPI strategy for the Invoices module

- **Status:** Accepted
- **Date:** 2026-09-30
- **Deciders:** maintainer
- **Related backlog:** [016 — Spike: OpenAPI strategy](../backlog/016-spike-openapi-strategy.md)
- **Follow-ups:** [017 — OpenAPI spec + codegen wiring](../backlog/017-openapi-spec.md), [018 — OpenAPI CI check](../backlog/018-openapi-ci-check.md)

## Revision history
- 2026-09-30 — initial decision: Option A (hand-authored YAML) + Option C (Scramble local preview).
- 2026-09-30 — **superseded within the same day** by the current decision: Option D (contract-first with generated DTOs). Rationale: the maintainer explicitly wants the "infra" story of code generation from the contract, and is willing to pay the extra assembler layer to keep `Application/` and `Domain/` pure. Scramble dropped — a contract-first setup should not also expose an introspection-based preview that could disagree with the YAML.

## Context

The README describes three HTTP endpoints in prose:

- `POST /api/invoices` — create a draft invoice with product lines.
- `GET  /api/invoices/{id}` — return the invoice with computed totals.
- `POST /api/invoices/{id}/send` — trigger the send flow via the Notification facade.

The HTTP layer has not been implemented yet (tasks 008/009/011). Before it lands, we
need to decide whether a machine-readable API contract is part of the submission bar,
and — if so — how it is authored, where any generated artifacts live, and how it
interacts with the module boundaries defined in
[`../agent-skills/module-boundaries-guardrails.md`](../agent-skills/module-boundaries-guardrails.md):

- `Domain` — no framework, no HTTP.
- `Application` — pure PHP DTOs (`CreateInvoiceCommand`, `InvoiceView`), no HTTP.
- `Infrastructure` — Eloquent, mailers, event bus.
- `Presentation/Http` — the *only* place where request/response shapes exist.

The contract must therefore live entirely under `Presentation/Http` or under `docs/`,
and must not force generated types into `Application/` or `Domain/`.

## Options considered

### Option A — Hand-authored `docs/api/openapi.yaml`

- Single YAML file under `docs/api/`, versioned with the code.
- Optional Spectral lint step in CI (`stoplight/spectral-cli` via npx).
- No runtime dependency, no controller annotations.
- Drift risk: **medium** — spec is only kept true by discipline + a feature-test that
  asserts response shape matches the documented schema (cheap to add).
- Boundary impact: **zero** — nothing generated, nothing injected into PHP code.

### Option B — Annotation-driven (`zircote/swagger-php` or `darkaonline/l5-swagger`)

- Attributes (`#[OA\Post]`, `#[OA\Schema]`, …) on controllers/FormRequests generate the spec.
- Runtime dep: swagger-php (dev-only if we only generate at build time).
- Drift risk: **low for shape, high for semantics** — attributes are next to the code
  but duplicate the FormRequest rules, and reviewers stop reading long attribute blobs.
- Boundary impact: attributes live in `Presentation/Http` — acceptable — but they
  clutter thin controllers that we deliberately want to keep short.

### Option C — Introspection (`dedoc/scramble`)

- Zero annotations. Spec is inferred from routes + FormRequest rules + `JsonResource`
  return types at request time (or exported via `php artisan scramble:export`).
- Runtime dep: one Composer package.
- Drift risk: **inverted** — the spec always matches the code, but the code becomes
  the contract by accident. FormRequest edits silently reshape the public API.
- Boundary impact: reads only `Presentation/Http` artifacts. Safe.

### Option D — Contract-first with generated DTOs (`jane-php/open-api`)

- `docs/api/openapi.yaml` is the source of truth.
- `jane-php/open-api` (pure PHP, no Java/Node runtime for codegen) generates typed
  PHP DTOs + `symfony/serializer` normalizers into
  `src/Modules/Invoices/Presentation/Http/Generated/`.
- Generated files are **committed** (marked `linguist-generated=true`, excluded from
  Pint) so CI does not need the codegen toolchain to run tests.
- Regeneration via `composer openapi:generate`.
- Hand-written **assemblers** under `Presentation/Http/Assemblers/` translate:
  - inbound: `Generated\*RequestDto` → `CreateInvoiceCommand` (Application DTO).
  - outbound: `InvoiceView` (Application DTO) → `Generated\*ResponseDto` → JSON.
- Boundary impact: generated classes live *only* under `Presentation/Http/Generated/`.
  `Application/` and `Domain/` never import them. The `module-boundaries-guardrails`
  check must be extended to fail if `Generated\` is referenced outside `Presentation/`.
- Setup cost: **medium** — one-time Jane configuration file
  (`docs/api/jane.php`) + assembler stubs per endpoint.
- Drift risk: **low** — the contract *is* the type system for the HTTP edge.
  Any schema change forces a regeneration diff that must be reviewed.

## Decision

**Adopt Option D.** Contract-first with generated DTOs.

1. `docs/api/openapi.yaml` is the single source of truth for the public HTTP contract.
2. `jane-php/open-api` generates PHP DTOs + normalizers into
   `src/Modules/Invoices/Presentation/Http/Generated/` via `composer openapi:generate`.
   Generated output is committed and marked as such.
3. Hand-written assemblers under `src/Modules/Invoices/Presentation/Http/Assemblers/`
   map between generated DTOs and the existing Application DTOs
   (`CreateInvoiceCommand`, `InvoiceView`). **`Application/` and `Domain/` never see
   the generated types.**
4. A feature test (`OpenApiContractTest`) round-trips real controller responses
   through the generated denormalizer to assert the response payload validates
   against the spec. This is the drift alarm.
5. `dedoc/scramble` is **not** adopted — contract-first + introspection would compete.
6. CI lint of the YAML via `npx @stoplight/spectral-cli` (see task 018).

Answers to the three questions from 016's acceptance criteria:

- **(a) Do we ship a spec with the submission?** Yes — `docs/api/openapi.yaml`.
- **(b) Hand-authored vs generated?** Hand-authored YAML; **code is generated from
  the YAML**.
- **(c) Do we generate code from the spec? Where does it live?** Yes.
  `src/Modules/Invoices/Presentation/Http/Generated/`. Generated code never crosses
  the module boundary into `Application/` or `Domain/`; assemblers form the seam.

## Consequences

Easier:

- Public API contract is diff-reviewable and machine-checkable.
- Wire-format changes cannot land silently — they force a regeneration diff.
- Demonstrates the codegen infrastructure story explicitly (recruitment-visible).
- Reviewers stop reading long swagger-php attribute blobs in controllers.
- Domain and Application layers remain 100% free of HTTP/OpenAPI concerns.

Harder:

- Two extra classes per endpoint (`RequestAssembler`, `ResponseAssembler`) — real
  but bounded cost for 3 endpoints.
- Contributors need to run `composer openapi:generate` after touching the YAML.
  Enforced by a CI step that regenerates and fails on diff (see 018).
- `module-boundaries-guardrails` must gain a rule:
  *"`Presentation/Http/Generated/` must not be referenced outside `Presentation/`."*
- Slightly more moving parts than Option A. Justified by the explicit intent to
  show code-generation as an infrastructure capability.

Layer clarification (why `Presentation/`, not `Infrastructure/`):

- `Infrastructure/` in this codebase means "adapters to external systems" (Eloquent,
  mail, event bus). It is *not* a generic "build output" folder.
- HTTP request/response DTOs describe the *wire protocol*, which is a
  `Presentation/Http` concern. Placing generated DTOs there keeps layer semantics
  consistent with the rest of the module.

Rejected paths and why we won't re-open them:

- **Option A alone (hand-authored only)**: does not demonstrate the codegen
  capability the maintainer explicitly asked for. Retained as the *authoring*
  step of Option D.
- **Option B (annotations)**: duplicates FormRequests, clutters thin controllers,
  and the spec still needs a build step.
- **Option C (Scramble)**: incompatible with contract-first — introspection would
  produce a second, silently-diverging view of the API.
- **`openapi-generator` (Java) instead of Jane**: requires a JVM in CI and its PHP
  templates emit a Guzzle-client shape, not the server-DTO shape we need.

## Prototype notes

No production code merged from this spike. Sketch of the intended wiring, for
tasks 017/018 to implement:

```php
// composer.json
"scripts": {
    "openapi:generate": "jane-openapi generate --config-file docs/api/jane.php",
    "openapi:lint":     "npx --yes @stoplight/spectral-cli lint docs/api/openapi.yaml"
}
```

```php
// docs/api/jane.php  (illustrative)
return [
    'openapi-file'      => __DIR__ . '/openapi.yaml',
    'namespace'         => 'Modules\\Invoices\\Presentation\\Http\\Generated',
    'directory'         => __DIR__ . '/../../src/Modules/Invoices/Presentation/Http/Generated',
    'strict'            => true,
    'use-fixer'         => false,
];
```

```php
// src/Modules/Invoices/Presentation/Http/Assemblers/CreateInvoiceRequestAssembler.php
final class CreateInvoiceRequestAssembler
{
    public function toCommand(Generated\CreateInvoiceRequestDto $dto): CreateInvoiceCommand
    {
        // pure mapping — no framework calls
    }
}
```

```yaml
# docs/api/openapi.yaml (skeleton)
openapi: 3.1.0
info: { title: Invoices API, version: 0.1.0 }
paths:
  /api/invoices:            { post: { operationId: createInvoice, … } }
  /api/invoices/{id}:       { get:  { operationId: viewInvoice, … } }
  /api/invoices/{id}/send:  { post: { operationId: sendInvoice, … } }
components:
  schemas:
    CreateInvoiceRequest: { … }
    InvoiceResponse:      { … }
    Problem:              { … }   # RFC 7807 error shape
```

## Docs impact

- New file: this ADR.
- Updated: `docs/architecture/README.md` — link this ADR under "API contract".
- Updated: `docs/agent-skills/module-boundaries-guardrails.md` — add rule that
  `Presentation/Http/Generated/` is not referenced outside `Presentation/`.
- New backlog tasks: 017 (write spec + wire Jane + assemblers), 018 (CI lint +
  regenerate-diff guard).
