# 004 — Application ports

## Story
As the application layer, I want repository and id-generator ports declared, so that use-cases stay framework-free and are trivially testable.

## Addresses (README requirements)
- Enabler for: "View Invoice", "Create Invoice", "Send Invoice" endpoints.
- Enabler for: "Invoice ID: Auto-generated during creation."

## Scope
- In:
  - `Application/Ports/InvoiceRepositoryInterface` (`getById(InvoiceId): Invoice`, `save(Invoice): void`).
  - `Application/Ports/IdGeneratorInterface` (`next(): InvoiceId`).
  - In-memory fake repo under `tests/Support/Invoices/` for unit tests.
- Out:
  - Eloquent implementation (story 007).

## Acceptance criteria
- [ ] Interfaces live in `Application/Ports/`.
- [ ] Repo throws `InvoiceNotFound` (never returns null).
- [ ] Fake repo used by unit tests in later stories.

## Tests
- Unit: sanity test on the in-memory fake (save + fetch + not-found).

## Guardrails to run
- php-and-laravel-guardrails
- module-boundaries-guardrails

## Commit
`feat(invoices): application ports`

## Status
todo

