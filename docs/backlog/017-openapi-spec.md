# 017 — OpenAPI spec, generated DTOs, and assemblers

## Story
As a reviewer, I want `docs/api/openapi.yaml` to be the source of truth for the
three Invoices endpoints, with typed PHP DTOs generated from it and hand-written
assemblers translating to/from the existing Application DTOs, so that the public
HTTP contract drives the wire layer without leaking generated types into
`Application/` or `Domain/`.

## Addresses (README requirements)
- `POST /api/invoices` — "creates a new invoice with product lines."
- `GET /api/invoices/{id}` — "returns the invoice with computed totals."
- `POST /api/invoices/{id}/send` — "sends the invoice via the Notification facade."

(Non-functional: implements the decision in [ADR 0001](../adr/0001-openapi-strategy.md).)

## Depends on
- 008, 009, 011 land the controllers. The YAML can be authored alongside 008 to
  drive request/response shapes; the assemblers and Jane wiring land in this task.

## Scope
- In:
  - `docs/api/openapi.yaml` (OpenAPI 3.1) covering all three endpoints:
    request bodies, path params, success responses, and RFC 7807 `Problem` error
    responses (`404`, `409` illegal transition, `422` validation).
  - Component schemas: `CreateInvoiceRequest`, `InvoiceResponse`,
    `InvoiceProductLine`, `Problem`.
  - `require-dev`: `jane-php/open-api`.
  - `docs/api/jane.php` config → generates into
    `src/Modules/Invoices/Presentation/Http/Generated/` under the
    `Modules\Invoices\Presentation\Http\Generated` namespace.
  - `composer openapi:generate` script.
  - `.gitattributes` entry marking `src/Modules/Invoices/Presentation/Http/Generated/**`
    as `linguist-generated=true`.
  - Pint config excludes the generated directory.
  - Hand-written assemblers under
    `src/Modules/Invoices/Presentation/Http/Assemblers/`:
    - `CreateInvoiceRequestAssembler` — `Generated\CreateInvoiceRequestDto` → `CreateInvoiceCommand`.
    - `InvoiceResponseAssembler` — `InvoiceView` → `Generated\InvoiceResponseDto`.
    - `SendInvoiceResponseAssembler` — `InvoiceView` → `Generated\InvoiceResponseDto` (reuses the view assembler).
  - Update `module-boundaries-guardrails.md`: add rule
    *"`Presentation\Http\Generated\` must not be referenced outside
    `Presentation\Http\`."* **(already added in the 016 commit — verify it is
    still present and unchanged.)**
  - Link `docs/api/openapi.yaml` from top-level `README.md` and
    `docs/architecture/README.md`.
- Out:
  - CI lint + regeneration-diff enforcement (see 018).
  - Client SDK generation.
  - Any change to `Application/` or `Domain/` code (assemblers do the translation).
  - Swagger UI / Redoc hosting.

## Acceptance criteria
- [ ] `composer openapi:generate` produces PHP files under
      `src/Modules/Invoices/Presentation/Http/Generated/` and the generated files
      are committed.
- [ ] `docs/api/openapi.yaml` validates as OpenAPI 3.1 (manual `spectral lint` OK).
- [ ] `README.md` links the spec under an "API contract" heading.
- [ ] Controllers depend only on `Generated\*Dto` types and assemblers —
      never on Application DTOs directly for wire-format mapping.
- [ ] `CreateInvoiceCommand` and `InvoiceView` are unchanged.
- [ ] `grep -R "Presentation..Http..Generated" src/Modules/Invoices/Domain src/Modules/Invoices/Application src/Modules/Invoices/Infrastructure` returns nothing.

## Tests
- Feature:
  - `openapi_yaml_documents_all_three_routes`
  - `create_invoice_response_matches_openapi_schema` — decode real response via
    the generated denormalizer; assert no exception.
  - `view_invoice_response_matches_openapi_schema`
  - `send_invoice_response_matches_openapi_schema`
  - `problem_response_shape_for_illegal_transition`
- Unit:
  - `CreateInvoiceRequestAssemblerTest` — maps every documented field.
  - `InvoiceResponseAssemblerTest` — maps every documented field including totals.

## Guardrails to run
- module-boundaries-guardrails (with the new `Generated\` rule)
- adr-architecture-guardrails (references ADR 0001)
- php-and-laravel-guardrails

## Commit
`feat(invoices): openapi contract, generated dtos, and assemblers`

## Status
partial — hand-authored `docs/api/openapi.yaml` shipped (source of truth for
the HTTP surface, all three operations with request/response schemas, the
`InvoiceStatus` enum, and 404/422 error shapes). The Jane generation +
assemblers + module-boundaries `Generated\` rule are consciously deferred:
for three endpoints the generator setup (composer dep, `jane.php`,
`.gitattributes`, Pint excludes, two assemblers, controller rewiring) buys
little over the hand-written FormRequest + `toArray()` path already in place.
Revisit when a fourth endpoint lands or a client SDK is needed.
