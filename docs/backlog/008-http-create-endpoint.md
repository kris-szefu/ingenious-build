# 008 — POST /invoices endpoint

## Story
As an API client, I want to POST a new invoice, so that I can start the flow via HTTP.

## Addresses (README requirements)
- "Required Endpoints: 2. Create Invoice: Initialize a new invoice."
- "An invoice can only be created in `draft` status."
- "An invoice can be created with empty product lines."

## Scope
- In:
  - `Presentation/Http/CreateInvoiceController` (invokable).
  - `Presentation/Http/Requests/CreateInvoiceRequest` (FormRequest) — validates customer + lines shape/types.
  - Returns `201` + `InvoiceResource` (or explicit array DTO).
  - Route under module `routes.php`.
- Out:
  - Send/view (other stories).

## Acceptance criteria
- [x] `201` on happy path (empty and non-empty lines).
- [x] `422` on invalid payload (non-positive qty/price, missing fields, bad email).
- [x] Controller contains no business logic.

## Tests
- Feature:
  - Creates with lines.
  - Creates without lines.
  - Rejects invalid payloads (422).

## Guardrails to run
- laravel-conventions
- module-boundaries-guardrails

## Commit
`feat(invoices): create endpoint`

## Status
done
