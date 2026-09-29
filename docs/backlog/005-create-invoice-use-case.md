# 005 — Create-invoice use-case

## Story
As an API client, I want to create a draft invoice (optionally with product lines), so that I can start the invoicing flow.

## Addresses (README requirements)
- "Create Invoice: Initialize a new invoice."
- "An invoice can only be created in `draft` status."
- "An invoice can be created with empty product lines."

## Scope
- In:
  - `Application/UseCases/CreateInvoice` accepts a typed input DTO (customer + optional lines).
  - Persists a `draft` `Invoice` via repo. Returns `InvoiceId`.
- Out:
  - HTTP layer (story 008).

## Acceptance criteria
- [ ] Creating with zero lines succeeds and status = `draft`.
- [ ] Creating with valid lines succeeds and totals compute.
- [ ] Invalid line values are rejected at VO construction (`InvalidProductLine`).

## Tests
- Unit `tests/Unit/Invoices/Application/`:
  - Creates empty draft.
  - Creates draft with lines and correct totals.
  - Rejects invalid quantity/unit price.

## Guardrails to run
- php-and-laravel-guardrails
- module-boundaries-guardrails

## Commit
`feat(invoices): create-invoice use-case`

## Status
todo

